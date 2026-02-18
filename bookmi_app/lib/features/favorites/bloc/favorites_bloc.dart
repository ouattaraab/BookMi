import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_event.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_state.dart';
import 'package:bookmi_app/features/favorites/data/repositories/favorites_repository.dart';

class FavoritesBloc extends Bloc<FavoritesEvent, FavoritesState> {
  FavoritesBloc({required FavoritesRepository repository})
    : _repository = repository,
      super(const FavoritesInitial()) {
    on<FavoritesFetched>(_onFetched);
    on<FavoriteToggled>(_onToggled);
    on<FavoriteStatusChecked>(_onStatusChecked);
  }

  final FavoritesRepository _repository;
  final Set<int> _pendingToggles = {};

  Future<void> _onFetched(
    FavoritesFetched event,
    Emitter<FavoritesState> emit,
  ) async {
    emit(const FavoritesLoading());

    final result = await _repository.getFavorites();

    switch (result) {
      case ApiSuccess(:final data):
        final ids = <int>{};
        for (final item in data) {
          final talent =
              (item['attributes'] as Map<String, dynamic>?)?['talent']
                  as Map<String, dynamic>?;
          if (talent != null) {
            ids.add(talent['id'] as int);
          } else if (item.containsKey('talent_id')) {
            ids.add(item['talent_id'] as int);
          }
        }
        emit(FavoritesLoaded(favoriteIds: ids, favorites: data));
      case ApiFailure(:final message):
        emit(FavoritesError(message));
    }
  }

  Future<void> _onToggled(
    FavoriteToggled event,
    Emitter<FavoritesState> emit,
  ) async {
    final currentState = state;
    if (currentState is! FavoritesLoaded) return;
    if (_pendingToggles.contains(event.talentId)) return;

    _pendingToggles.add(event.talentId);

    try {
      final isFav = currentState.favoriteIds.contains(event.talentId);

      // Optimistic update
      final updatedIds = Set<int>.from(currentState.favoriteIds);
      if (isFav) {
        updatedIds.remove(event.talentId);
      } else {
        updatedIds.add(event.talentId);
      }
      emit(currentState.copyWith(favoriteIds: updatedIds));

      // API call
      final ApiResult<void> result;
      if (isFav) {
        result = await _repository.removeFavorite(event.talentId);
      } else {
        result = await _repository.addFavorite(event.talentId);
      }

      // Rollback on business error (network errors are queued offline)
      if (result is ApiFailure) {
        emit(currentState);
      }
    } finally {
      _pendingToggles.remove(event.talentId);
    }
  }

  Future<void> _onStatusChecked(
    FavoriteStatusChecked event,
    Emitter<FavoritesState> emit,
  ) async {
    final result = await _repository.isFavorite(event.talentId);

    switch (result) {
      case ApiSuccess(:final data):
        if (state is FavoritesLoaded) {
          final current = state as FavoritesLoaded;
          final updatedIds = Set<int>.from(current.favoriteIds);
          if (data) {
            updatedIds.add(event.talentId);
          } else {
            updatedIds.remove(event.talentId);
          }
          emit(current.copyWith(favoriteIds: updatedIds));
        } else {
          emit(
            FavoritesLoaded(
              favoriteIds: data ? {event.talentId} : {},
            ),
          );
        }
      case ApiFailure():
        break; // Silently ignore status check failures
    }
  }
}
