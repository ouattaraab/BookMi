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
      ),
    );

    final result = await _repository.getTalents(
      cursor: currentState.nextCursor,
      filters: currentState.activeFilters.isNotEmpty
          ? currentState.activeFilters
          : null,
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
}
