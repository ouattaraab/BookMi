import 'package:bookmi_app/core/services/analytics_service.dart';
import 'package:flutter/widgets.dart';

class AnalyticsRouteObserver extends NavigatorObserver {
  @override
  void didPush(Route<dynamic> route, Route<dynamic>? previous) => _track(route);

  @override
  void didReplace({Route<dynamic>? newRoute, Route<dynamic>? oldRoute}) =>
      _track(newRoute);

  void _track(Route<dynamic>? route) {
    if (route == null) return;
    // Only track full-page routes, skip dialogs and bottom sheets
    if (route is! PageRoute) return;
    final name = route.settings.name ?? 'unknown';
    AnalyticsService.instance.trackPage(_sanitizeName(name));
  }

  String _sanitizeName(String name) =>
      name.replaceAll(RegExp(r'/\d+'), '/:id').toLowerCase();
}
