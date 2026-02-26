import 'dart:async';

import 'package:dio/dio.dart';
import 'package:uuid/uuid.dart';

class AnalyticsService {
  AnalyticsService._();
  static final instance = AnalyticsService._();

  final _queue = <Map<String, dynamic>>[];
  final _session = const Uuid().v4();
  Timer? _timer;
  Dio? _dio;
  String _platform = 'android';
  String _appVersion = '1.0.0';

  void init(Dio dio, String platform, String appVersion) {
    _dio = dio;
    _platform = platform;
    _appVersion = appVersion;
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 30), (_) => _flush());
  }

  void trackPage(String name) => _queue.add({
    'event_type': 'page_view',
    'event_name': name,
    'session_id': _session,
  });

  void trackTap(String label) => _queue.add({
    'event_type': 'button_tap',
    'event_name': label,
    'session_id': _session,
  });

  Future<void> _flush() async {
    if (_queue.isEmpty || _dio == null) return;
    final batch = List<Map<String, dynamic>>.from(_queue);
    _queue.clear();
    try {
      await _dio!.post<void>(
        '/analytics/events',
        data: {
          'events': batch,
          'platform': _platform,
          'app_version': _appVersion,
        },
      );
    } on Exception catch (_) {
      // fire-and-forget — échec silencieux
    }
  }
}
