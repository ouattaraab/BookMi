import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/app/routes/guards/auth_guard.dart';
import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockAuthBloc extends MockBloc<AuthEvent, AuthState> implements AuthBloc {
  @override
  bool isNewTalentRegistration = false;
}

void main() {
  late MockAuthBloc mockBloc;

  const fakeUser = AuthUser(
    id: 1,
    firstName: 'Test',
    lastName: 'User',
    email: 'test@example.com',
    phone: '+22890000000',
    isActive: true,
  );

  const authenticatedState = AuthAuthenticated(
    user: fakeUser,
    roles: ['client'],
  );

  setUpAll(() {
    registerFallbackValue(const AuthCheckRequested());
  });

  setUp(() {
    mockBloc = MockAuthBloc();
  });

  /// Pumps a widget tree that provides [mockBloc] via [BlocProvider] and
  /// calls [authGuard] with the given [location] from inside a [Builder].
  ///
  /// Returns the result of the authGuard call.
  Future<String?> pumpAndGuard(
    WidgetTester tester, {
    required AuthState state,
    required String location,
  }) async {
    when(() => mockBloc.state).thenReturn(state);

    late String? result;

    await tester.pumpWidget(
      MaterialApp(
        home: BlocProvider<AuthBloc>.value(
          value: mockBloc,
          child: Builder(
            builder: (context) {
              result = authGuard(context, location);
              return const SizedBox.shrink();
            },
          ),
        ),
      ),
    );

    return result;
  }

  group('authGuard', () {
    // ---------------------------------------------------------------
    // 1. Splash route always returns null regardless of auth state
    // ---------------------------------------------------------------
    group('splash route', () {
      testWidgets('returns null when authenticated on splash', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.splash,
        );

        expect(result, isNull);
      });

      testWidgets('returns null when unauthenticated on splash', (
        tester,
      ) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthUnauthenticated(),
          location: RoutePaths.splash,
        );

        expect(result, isNull);
      });

      testWidgets('returns null when AuthInitial on splash', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthInitial(),
          location: RoutePaths.splash,
        );

        expect(result, isNull);
      });
    });

    // ---------------------------------------------------------------
    // 2. Authenticated user on a public route → redirects to /home
    // ---------------------------------------------------------------
    group('authenticated user on public route', () {
      testWidgets('redirects to /home from /login', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.login,
        );

        expect(result, RoutePaths.home);
      });

      testWidgets('redirects to /home from /register', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.register,
        );

        expect(result, RoutePaths.home);
      });

      testWidgets('redirects to /home from /onboarding', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.onboarding,
        );

        expect(result, RoutePaths.home);
      });

      testWidgets('redirects to /home from /otp', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.otp,
        );

        expect(result, RoutePaths.home);
      });

      testWidgets('redirects to /home from /forgot-password', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.forgotPassword,
        );

        expect(result, RoutePaths.home);
      });
    });

    // ---------------------------------------------------------------
    // 3. Authenticated user on a protected route → returns null
    // ---------------------------------------------------------------
    group('authenticated user on protected route', () {
      testWidgets('returns null on /home', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.home,
        );

        expect(result, isNull);
      });

      testWidgets('returns null on /search', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.search,
        );

        expect(result, isNull);
      });

      testWidgets('returns null on /profile', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: authenticatedState,
          location: RoutePaths.profile,
        );

        expect(result, isNull);
      });
    });

    // ---------------------------------------------------------------
    // 4. Unauthenticated user on a protected route → redirects to /login
    // ---------------------------------------------------------------
    group('unauthenticated user on protected route', () {
      testWidgets('redirects to /login from /home', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthUnauthenticated(),
          location: RoutePaths.home,
        );

        expect(result, RoutePaths.login);
      });

      testWidgets('redirects to /login from /search', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthUnauthenticated(),
          location: RoutePaths.search,
        );

        expect(result, RoutePaths.login);
      });

      testWidgets('redirects to /login from /profile', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthUnauthenticated(),
          location: RoutePaths.profile,
        );

        expect(result, RoutePaths.login);
      });
    });

    // ---------------------------------------------------------------
    // 5. Unauthenticated user on a public route → returns null
    // ---------------------------------------------------------------
    group('unauthenticated user on public route', () {
      testWidgets('returns null on /login', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthUnauthenticated(),
          location: RoutePaths.login,
        );

        expect(result, isNull);
      });

      testWidgets('returns null on /register', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthUnauthenticated(),
          location: RoutePaths.register,
        );

        expect(result, isNull);
      });

      testWidgets('returns null on /onboarding', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthUnauthenticated(),
          location: RoutePaths.onboarding,
        );

        expect(result, isNull);
      });
    });

    // ---------------------------------------------------------------
    // 6. AuthInitial state → returns null
    // ---------------------------------------------------------------
    group('AuthInitial state', () {
      testWidgets('returns null on protected route', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthInitial(),
          location: RoutePaths.home,
        );

        expect(result, isNull);
      });

      testWidgets('returns null on public route', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthInitial(),
          location: RoutePaths.login,
        );

        expect(result, isNull);
      });
    });

    // ---------------------------------------------------------------
    // 7. AuthLoading state → returns null
    // ---------------------------------------------------------------
    group('AuthLoading state', () {
      testWidgets('returns null on protected route', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthLoading(),
          location: RoutePaths.home,
        );

        expect(result, isNull);
      });

      testWidgets('returns null on public route', (tester) async {
        final result = await pumpAndGuard(
          tester,
          state: const AuthLoading(),
          location: RoutePaths.login,
        );

        expect(result, isNull);
      });
    });
  });
}
