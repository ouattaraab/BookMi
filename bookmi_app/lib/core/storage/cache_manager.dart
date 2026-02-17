import 'package:bookmi_app/core/storage/local_storage.dart';

class CacheManager {
  CacheManager({required LocalStorage localStorage})
    : _localStorage = localStorage;

  final LocalStorage _localStorage;

  static const _defaultTtl = Duration(days: 7);

  Future<void> cache(String key, dynamic value, {Duration? ttl}) =>
      _localStorage.put(key, value, ttl: ttl ?? _defaultTtl);

  T? get<T>(String key) => _localStorage.get<T>(key);

  Future<void> invalidate(String key) => _localStorage.delete(key);

  Future<void> clearAll() => _localStorage.clear();
}
