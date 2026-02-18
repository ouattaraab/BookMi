import 'package:flutter/foundation.dart';

sealed class FavoritesState {
  const FavoritesState();
}

final class FavoritesInitial extends FavoritesState {
  const FavoritesInitial();
}

final class FavoritesLoading extends FavoritesState {
  const FavoritesLoading();
}

@immutable
final class FavoritesLoaded extends FavoritesState {
  const FavoritesLoaded({
    required this.favoriteIds,
    this.favorites = const [],
  });

  final Set<int> favoriteIds;
  final List<Map<String, dynamic>> favorites;

  FavoritesLoaded copyWith({
    Set<int>? favoriteIds,
    List<Map<String, dynamic>>? favorites,
  }) {
    return FavoritesLoaded(
      favoriteIds: favoriteIds ?? this.favoriteIds,
      favorites: favorites ?? this.favorites,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FavoritesLoaded &&
          setEquals(favoriteIds, other.favoriteIds) &&
          listEquals(favorites, other.favorites);

  @override
  int get hashCode => Object.hash(
    Object.hashAll(favoriteIds),
    Object.hashAll(favorites),
  );
}

@immutable
final class FavoritesError extends FavoritesState {
  const FavoritesError(this.message);
  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FavoritesError && message == other.message;

  @override
  int get hashCode => message.hashCode;
}
