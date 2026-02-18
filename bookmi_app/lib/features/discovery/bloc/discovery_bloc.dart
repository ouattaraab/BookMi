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
  }

  final DiscoveryRepository _repository;

  Future<void> _onFetched(
    DiscoveryFetched event,
    Emitter<DiscoveryState> emit,
  ) async {
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
    // Guard: ignore if already loading more or not loaded
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
          ),
        );
      case ApiFailure():
        // Revert to loaded state — preserve existing talents
        emit(
          DiscoveryLoaded(
            talents: currentState.talents,
            hasMore: currentState.hasMore,
            nextCursor: currentState.nextCursor,
            activeFilters: currentState.activeFilters,
          ),
        );
    }
  }

  Future<void> _onFiltersChanged(
    DiscoveryFiltersChanged event,
    Emitter<DiscoveryState> emit,
  ) async {
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
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(DiscoveryFailure(code: code, message: message));
    }
  }
}
