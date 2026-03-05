import 'dart:convert';

import 'package:hive_ce_flutter/hive_flutter.dart';

/// Simple offline cache for read-only data (bookings, conversations).
/// Uses a dedicated Hive box with TTL of 24h.
class OfflineCache {
  OfflineCache({required Box<dynamic> box}) : _box = box;

  final Box<dynamic> _box;

  static const _ttlHours = 24;
  static const _bookingsKey = 'offline_bookings';
  static const _conversationsKey = 'offline_conversations';

  // ── Bookings ────────────────────────────────────────────────────

  Future<void> saveBookings(List<Map<String, dynamic>> bookings) async {
    await _box.put(_bookingsKey, jsonEncode(bookings));
    await _box.put('${_bookingsKey}_ts', DateTime.now().toIso8601String());
  }

  List<Map<String, dynamic>>? getBookings() {
    if (_isExpired(_bookingsKey)) return null;
    final raw = _box.get(_bookingsKey) as String?;
    if (raw == null) return null;
    try {
      final list = jsonDecode(raw) as List<dynamic>;
      return list.cast<Map<String, dynamic>>();
    } catch (_) {
      return null;
    }
  }

  // ── Conversations ────────────────────────────────────────────────

  Future<void> saveConversations(
    List<Map<String, dynamic>> conversations,
  ) async {
    await _box.put(_conversationsKey, jsonEncode(conversations));
    await _box.put('${_conversationsKey}_ts', DateTime.now().toIso8601String());
  }

  List<Map<String, dynamic>>? getConversations() {
    if (_isExpired(_conversationsKey)) return null;
    final raw = _box.get(_conversationsKey) as String?;
    if (raw == null) return null;
    try {
      final list = jsonDecode(raw) as List<dynamic>;
      return list.cast<Map<String, dynamic>>();
    } catch (_) {
      return null;
    }
  }

  // ── Helpers ──────────────────────────────────────────────────────

  bool _isExpired(String key) {
    final tsRaw = _box.get('${key}_ts') as String?;
    if (tsRaw == null) return true;
    final ts = DateTime.tryParse(tsRaw);
    if (ts == null) return true;
    return DateTime.now().difference(ts).inHours >= _ttlHours;
  }

  Future<void> clear() => _box.clear();
}
