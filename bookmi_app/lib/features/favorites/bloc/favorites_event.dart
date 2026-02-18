import 'package:flutter/foundation.dart';

sealed class FavoritesEvent {
  const FavoritesEvent();
}

final class FavoritesFetched extends FavoritesEvent {
  const FavoritesFetched();
}

@immutable
final class FavoriteToggled extends FavoritesEvent {
  const FavoriteToggled(this.talentId);
  final int talentId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FavoriteToggled && talentId == other.talentId;

  @override
  int get hashCode => talentId.hashCode;
}

@immutable
final class FavoriteStatusChecked extends FavoritesEvent {
  const FavoriteStatusChecked(this.talentId);
  final int talentId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FavoriteStatusChecked && talentId == other.talentId;

  @override
  int get hashCode => talentId.hashCode;
}
