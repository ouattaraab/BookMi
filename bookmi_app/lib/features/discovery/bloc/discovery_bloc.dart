import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_event.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_state.dart';
import 'package:bookmi_app/features/discovery/data/repositories/discovery_repository.dart';

class DiscoveryBloc extends Bloc<DiscoveryEvent, DiscoveryState> {
  DiscoveryBloc({required DiscoveryRepository repository})
    : _repository = repository,
      super(const DiscoveryInitial()) {
    on<DiscoveryFetched>(_onFetched);
    on<DiscoveryNextPageFetched>(_onNextPageFetched);
    on<DiscoveryFiltersChanged>(_onFiltersChanged);
    on<DiscoveryFilterCleared>(_onFilterCleared);
    on<DiscoverySearchChanged>(_onSearchChanged);
    on<DiscoveryDateSearchRequested>(_onDateSearchRequested);
  }

  final DiscoveryRepository _repository;

  /// Cached categories — loaded once on first fetch, reused on subsequent refreshes.
  List<Map<String, dynamic>> _cachedCategories = [];

  Future<void> _onFetched(
    DiscoveryFetched event,
    Emitter<DiscoveryState> emit,
  ) async {
    emit(const DiscoveryLoading());

    // Load talents and categories in parallel.
    final talentsFuture = _repository.getTalents();
    final categoriesFuture = _repository.getCategories();

    final talentsResult = await talentsFuture;
    final categoriesResult = await categoriesFuture;

    // Update cache when categories are successfully fetched.
    if (categoriesResult case ApiSuccess(:final data)) {
      _cachedCategories = data;
    }

    switch (talentsResult) {
      case ApiSuccess(:final data):
        emit(
          DiscoveryLoaded(
            talents: data.talents,
            hasMore: data.hasMore,
            nextCursor: data.nextCursor,
            activeFilters: const {},
            categories: _cachedCategories,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(DiscoveryFailure(code: code, message: message));
    }
  }

  Future<void> _onNextPageFetched(
    DiscoveryNextPageFetched event,
    Emitter<DiscoveryState> emit,
  ) async {
    final currentState = state;
    if (currentState is DiscoveryLoadingMore ||
        currentState is! DiscoveryLoaded) {
      return;
    }

    if (!currentState.hasMore) return;

    emit(
      DiscoveryLoadingMore(
        talents: currentState.talents,
        hasMore: currentState.hasMore,
        nextCursor: currentState.nextCursor,
        activeFilters: currentState.activeFilters,
        categories: currentState.categories,
        eventDate: currentState.eventDate,
        searchQuery: currentState.searchQuery,
      ),
    );

    final eventDateStr = currentState.eventDate != null
        ? _formatDate(currentState.eventDate!)
        : null;

    final result = await _repository.getTalents(
      cursor: currentState.nextCursor,
      filters: currentState.activeFilters.isNotEmpty
          ? currentState.activeFilters
          : null,
      eventDate: eventDateStr,
    );

    switch (result) {
      case ApiSuccess(:final data):
        emit(
          DiscoveryLoaded(
            talents: [...currentState.talents, ...data.talents],
            hasMore: data.hasMore,
            nextCursor: data.nextCursor,
            activeFilters: currentState.activeFilters,
            categories: currentState.categories,
            eventDate: currentState.eventDate,
            searchQuery: currentState.searchQuery,
          ),
        );
      case ApiFailure():
        emit(
          DiscoveryLoaded(
            talents: currentState.talents,
            hasMore: currentState.hasMore,
            nextCursor: currentState.nextCursor,
            activeFilters: currentState.activeFilters,
            categories: currentState.categories,
            eventDate: currentState.eventDate,
            searchQuery: currentState.searchQuery,
          ),
        );
    }
  }

  Future<void> _onFiltersChanged(
    DiscoveryFiltersChanged event,
    Emitter<DiscoveryState> emit,
  ) async {
    final prevCategories =
        state is DiscoveryLoaded
            ? (state as DiscoveryLoaded).categories
            : _cachedCategories;

    emit(const DiscoveryLoading());

    final result = await _repository.getTalents(
      filters: event.filters.isNotEmpty ? event.filters : null,
    );

    switch (result) {
      case ApiSuccess(:final data):
        emit(
          DiscoveryLoaded(
            talents: data.talents,
            hasMore: data.hasMore,
            nextCursor: data.nextCursor,
            activeFilters: event.filters,
            categories: prevCategories,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(DiscoveryFailure(code: code, message: message));
    }
  }

  Future<void> _onFilterCleared(
    DiscoveryFilterCleared event,
    Emitter<DiscoveryState> emit,
  ) async {
    final prevCategories =
        state is DiscoveryLoaded
            ? (state as DiscoveryLoaded).categories
            : _cachedCategories;

    emit(const DiscoveryLoading());

    final result = await _repository.getTalents();
    switch (result) {
      case ApiSuccess(:final data):
        emit(
          DiscoveryLoaded(
            talents: data.talents,
            hasMore: data.hasMore,
            nextCursor: data.nextCursor,
            activeFilters: const {},
            categories: prevCategories,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(DiscoveryFailure(code: code, message: message));
    }
  }

  Future<void> _onSearchChanged(
    DiscoverySearchChanged event,
    Emitter<DiscoveryState> emit,
  ) async {
    final prevCategories =
        state is DiscoveryLoaded
            ? (state as DiscoveryLoaded).categories
            : _cachedCategories;

    // Preserve the active category filter (if any), replace the search query.
    final prevFilters =
        state is DiscoveryLoaded
            ? Map<String, dynamic>.from((state as DiscoveryLoaded).activeFilters)
            : <String, dynamic>{};

    prevFilters.remove('q');

    emit(const DiscoveryLoading());

    // Pass q via dedicated `query` param; keep other filters (e.g. category_id).
    final filtersWithoutQ = Map<String, dynamic>.from(prevFilters);
    final result = await _repository.getTalents(
      query: event.query.isNotEmpty ? event.query : null,
      filters: filtersWithoutQ.isNotEmpty ? filtersWithoutQ : null,
    );

    switch (result) {
      case ApiSuccess(:final data):
        emit(
          DiscoveryLoaded(
            talents: data.talents,
            hasMore: data.hasMore,
            nextCursor: data.nextCursor,
            activeFilters: event.query.isNotEmpty
                ? {...prevFilters, 'q': event.query}
                : prevFilters,
            categories: prevCategories,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(DiscoveryFailure(code: code, message: message));
    }
  }

  /// Hero search: talent type query + optional event date.
  Future<void> _onDateSearchRequested(
    DiscoveryDateSearchRequested event,
    Emitter<DiscoveryState> emit,
  ) async {
    final prevCategories =
        state is DiscoveryLoaded
            ? (state as DiscoveryLoaded).categories
            : _cachedCategories;

    // Keep only category filter, clear search-specific params.
    final prevFilters =
        state is DiscoveryLoaded
            ? Map<String, dynamic>.from((state as DiscoveryLoaded).activeFilters)
            : <String, dynamic>{};

    prevFilters.remove('q');
    prevFilters.remove('event_date');

    emit(const DiscoveryLoading());

    final eventDateStr =
        event.eventDate != null ? _formatDate(event.eventDate!) : null;

    final result = await _repository.getTalents(
      query: (event.query?.isNotEmpty == true) ? event.query : null,
      eventDate: eventDateStr,
      filters: prevFilters.isNotEmpty ? prevFilters : null,
    );

    switch (result) {
      case ApiSuccess(:final data):
        emit(
          DiscoveryLoaded(
            talents: data.talents,
            hasMore: data.hasMore,
            nextCursor: data.nextCursor,
            activeFilters: {
              ...prevFilters,
              if (event.query?.isNotEmpty == true) 'q': event.query!,
              if (eventDateStr != null) 'event_date': eventDateStr,
            },
            categories: prevCategories,
            eventDate: event.eventDate,
            searchQuery: event.query,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(DiscoveryFailure(code: code, message: message));
    }
  }

  /// Format a DateTime to ISO YYYY-MM-DD.
  String _formatDate(DateTime date) =>
      '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
}
