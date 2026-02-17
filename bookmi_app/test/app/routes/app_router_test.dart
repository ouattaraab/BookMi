import 'package:bookmi_app/app/routes/app_router.dart';
import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';

void main() {
  group('AppRouter', () {
    test('has initial location set to home', () {
      expect(appRouter.routeInformationProvider.value.uri.path, '/home');
    });

    test('contains a StatefulShellRoute', () {
      final shellRoute = appRouter.configuration.routes.first;
      expect(shellRoute, isA<StatefulShellRoute>());
    });

    test('has exactly 5 branches for tab navigation', () {
      final shellRoute =
          appRouter.configuration.routes.first as StatefulShellRoute;
      expect(shellRoute.branches.length, 5);
    });

    group('route paths', () {
      test('home path is defined', () {
        expect(RoutePaths.home, '/home');
      });

      test('search path is defined', () {
        expect(RoutePaths.search, '/search');
      });

      test('bookings path is defined', () {
        expect(RoutePaths.bookings, '/bookings');
      });

      test('messages path is defined', () {
        expect(RoutePaths.messages, '/messages');
      });

      test('profile path is defined', () {
        expect(RoutePaths.profile, '/profile');
      });

      test('login path is defined', () {
        expect(RoutePaths.login, '/login');
      });
    });

    group('route names', () {
      test('home name is defined', () {
        expect(RouteNames.home, 'home');
      });

      test('search name is defined', () {
        expect(RouteNames.search, 'search');
      });

      test('bookings name is defined', () {
        expect(RouteNames.bookings, 'bookings');
      });

      test('messages name is defined', () {
        expect(RouteNames.messages, 'messages');
      });

      test('profile name is defined', () {
        expect(RouteNames.profile, 'profile');
      });
    });
  });
}
