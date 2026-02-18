import 'dart:async';

import 'package:hive_ce/hive.dart';

class FavoritesLocalSource {
  FavoritesLocalSource({required Box<dynamic> box}) : _box = box;

  final Box<dynamic> _box;

  static const _favoriteIdsKey = 'favorite_ids';
  static const _pendingActionsKey = 'pending_actions';

  /// Cache favorite talent IDs locally.
  /// If [append] is true, merges with existing cache instead of replacing.
  Future<void> cacheFavoriteIds(List<int> ids, {bool append = false}) async {
    if (append) {
      final existing = getCachedFavoriteIds() ?? [];
      final merged = {...existing, ...ids}.toList();
      await _box.put(_favoriteIdsKey, merged);
    } else {
      await _box.put(_favoriteIdsKey, ids);
    }
  }

  /// Get cached favorite IDs, or null if not cached.
  List<int>? getCachedFavoriteIds() {
    final data = _box.get(_favoriteIdsKey);
    if (data == null) return null;
    return (data as List<dynamic>).cast<int>();
  }

  /// Check if a talent is favorited from local cache.
  bool? isFavorite(int talentId) {
    final ids = getCachedFavoriteIds();
    if (ids == null) return null;
    return ids.contains(talentId);
  }

  /// Add a favorite ID to local cache.
  Future<void> addFavoriteId(int talentId) async {
    final ids = getCachedFavoriteIds() ?? [];
    if (!ids.contains(talentId)) {
      await _box.put(_favoriteIdsKey, [...ids, talentId]);
    }
  }

  /// Remove a favorite ID from local cache.
  Future<void> removeFavoriteId(int talentId) async {
    final ids = (getCachedFavoriteIds() ?? [])..remove(talentId);
    await _box.put(_favoriteIdsKey, ids);
  }

  /// Queue an offline action for later synchronization.
  /// Action format: {'type': 'add'|'remove', 'talentId': int}
  Future<void> queuePendingAction(Map<String, dynamic> action) async {
    final pending = getPendingActions()..add(action);
    await _box.put(_pendingActionsKey, pending);
  }

  /// Get all pending offline actions.
  List<Map<String, dynamic>> getPendingActions() {
    final data = _box.get(_pendingActionsKey);
    if (data == null) return [];
    return (data as List<dynamic>)
        .cast<Map<dynamic, dynamic>>()
        .map((m) => m.cast<String, dynamic>())
        .toList();
  }

  /// Clear all pending actions after successful sync.
  Future<void> clearPendingActions() async {
    await _box.delete(_pendingActionsKey);
  }

  /// Clear all cached favorites data.
  Future<void> clear() async {
    await _box.delete(_favoriteIdsKey);
    await _box.delete(_pendingActionsKey);
  }
}
