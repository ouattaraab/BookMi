import 'dart:async';

import 'package:hive_ce/hive.dart';

class LocalStorage {
  LocalStorage({required Box<dynamic> box}) : _box = box;

  final Box<dynamic> _box;

  static const _ttlSuffix = '__ttl';
  static const _defaultTtl = Duration(days: 7);

  Future<void> put(String key, dynamic value, {Duration? ttl}) async {
    await _box.put(key, value);
    final expiry = DateTime.now()
        .add(ttl ?? _defaultTtl)
        .millisecondsSinceEpoch;
    await _box.put('$key$_ttlSuffix', expiry);
  }

  T? get<T>(String key) {
    final ttl = _box.get('$key$_ttlSuffix') as int?;
    if (ttl != null && DateTime.now().millisecondsSinceEpoch > ttl) {
      // Expired entry — schedule async cleanup
      unawaited(
        Future.wait([_box.delete(key), _box.delete('$key$_ttlSuffix')]),
      );
      return null;
    }
    return _box.get(key) as T?;
  }

  Future<void> delete(String key) async {
    await _box.delete(key);
    await _box.delete('$key$_ttlSuffix');
  }

  Future<void> clear() => _box.clear();
}
