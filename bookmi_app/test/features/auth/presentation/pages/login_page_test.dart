import 'dart:async';

import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:bookmi_app/features/auth/presentation/pages/login_page.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:mocktail/mocktail.dart';

// ---------------------------------------------------------------------------
// Mocks
// ---------------------------------------------------------------------------

class MockAuthBloc extends MockBloc<AuthEvent, AuthState> implements AuthBloc {}

class MockGoRouter extends Mock implements GoRouter {}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const _testEmail = 'john@test.com';
const _testPassword = 'Secret123!';

const _fakeUser = AuthUser(
  id: 1,
  firstName: 'John',
  lastName: 'Doe',
  email: _testEmail,
  phone: '+2250700000000',
  isActive: true,
  isClientVerified: false,
);

Widget _buildSubject(AuthBloc bloc, {GoRouter? router}) {
  final goRouter = router ?? MockGoRouter();
  return MaterialApp(
    home: BlocProvider<AuthBloc>.value(
      value: bloc,
      child: InheritedGoRouter(
        goRouter: goRouter,
        child: const LoginPage(),
      ),
    ),
  );
}

void main() {
  late MockAuthBloc mockBloc;

  setUpAll(() {
    registerFallbackValue(
      const AuthLoginSubmitted(email: '', password: ''),
    );
    // Avoid platform-specific GPU detection during tests.
    GpuTierProvider.tierForTesting = GpuTier.low;
  });

  setUp(() {
    mockBloc = MockAuthBloc();
    // Default: bloc is in initial state and emits nothing.
    when(() => mockBloc.state).thenReturn(const AuthInitial());
  });

  tearDownAll(GpuTierProvider.resetForTesting);

  // =========================================================================
  // 1. Renders correctly
  // =========================================================================
  group('renders correctly', () {
    testWidgets('displays email field, password field, submit button, '
        'forgot-password link and register link', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      // Email field (label rendered as labelText inside InputDecoration)
      expect(find.text('Email'), findsOneWidget);

      // Password field
      expect(find.text('Mot de passe'), findsOneWidget);

      // Submit button
      expect(find.text('Se connecter'), findsOneWidget);

      // Forgot password link
      expect(find.text('Mot de passe oubli\u00e9 ?'), findsOneWidget);

      // Register link
      expect(find.text("S'inscrire"), findsOneWidget);
    });

    testWidgets('displays the BookMi title', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      expect(find.text('BookMi'), findsOneWidget);
    });

    testWidgets('displays the Connexion heading', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      expect(find.text('Connexion'), findsOneWidget);
    });
  });

  // =========================================================================
  // 2. Email validation
  // =========================================================================
  group('email validation', () {
    testWidgets('shows error when email is empty', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      // Leave email empty, enter a valid password.
      await tester.enterText(
        find.byType(TextFormField).at(1),
        _testPassword,
      );

      // Tap submit.
      await tester.tap(find.text('Se connecter'));
      await tester.pumpAndSettle();

      expect(find.text("L'email est requis."), findsOneWidget);
    });

    testWidgets('shows error for invalid email format', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      // Enter an invalid email.
      await tester.enterText(
        find.byType(TextFormField).at(0),
        'not-an-email',
      );
      await tester.enterText(
        find.byType(TextFormField).at(1),
        _testPassword,
      );

      await tester.tap(find.text('Se connecter'));
      await tester.pumpAndSettle();

      expect(
        find.text('Veuillez entrer une adresse e-mail valide.'),
        findsOneWidget,
      );
    });

    testWidgets('shows no error for a valid email', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      await tester.enterText(
        find.byType(TextFormField).at(0),
        _testEmail,
      );
      await tester.enterText(
        find.byType(TextFormField).at(1),
        _testPassword,
      );

      await tester.tap(find.text('Se connecter'));
      await tester.pumpAndSettle();

      expect(find.text("L'email est requis."), findsNothing);
      expect(
        find.text('Veuillez entrer une adresse e-mail valide.'),
        findsNothing,
      );
    });
  });

  // =========================================================================
  // 3. Password validation
  // =========================================================================
  group('password validation', () {
    testWidgets('shows error when password is empty', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      // Enter valid email, leave password empty.
      await tester.enterText(
        find.byType(TextFormField).at(0),
        _testEmail,
      );

      await tester.tap(find.text('Se connecter'));
      await tester.pumpAndSettle();

      expect(find.text('Le mot de passe est requis.'), findsOneWidget);
    });

    testWidgets('shows no error for a valid password', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      await tester.enterText(
        find.byType(TextFormField).at(0),
        _testEmail,
      );
      await tester.enterText(
        find.byType(TextFormField).at(1),
        _testPassword,
      );

      await tester.tap(find.text('Se connecter'));
      await tester.pumpAndSettle();

      expect(find.text('Le mot de passe est requis.'), findsNothing);
    });
  });

  // =========================================================================
  // 4. Submit dispatches AuthLoginSubmitted
  // =========================================================================
  group('submit', () {
    testWidgets(
      'dispatches AuthLoginSubmitted with trimmed email and password',
      (tester) async {
        await tester.pumpWidget(_buildSubject(mockBloc));

        // Fill in fields (email has leading/trailing spaces to verify trim).
        await tester.enterText(
          find.byType(TextFormField).at(0),
          '  $_testEmail  ',
        );
        await tester.enterText(
          find.byType(TextFormField).at(1),
          _testPassword,
        );

        // Accept terms & conditions checkbox.
        await tester.tap(find.byType(Checkbox));
        await tester.pumpAndSettle();

        await tester.tap(find.text('Se connecter'));
        await tester.pumpAndSettle();

        final captured = verify(() => mockBloc.add(captureAny())).captured;
        expect(captured, hasLength(1));

        final event = captured.first as AuthLoginSubmitted;
        expect(event.email, equals(_testEmail));
        expect(event.password, equals(_testPassword));
      },
    );

    testWidgets('does not dispatch when form is invalid', (tester) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      // Leave both fields empty.
      await tester.tap(find.text('Se connecter'));
      await tester.pumpAndSettle();

      verifyNever(() => mockBloc.add(any()));
    });
  });

  // =========================================================================
  // 5. BlocListener – error SnackBar on AuthFailure
  // =========================================================================
  group('BlocListener error handling', () {
    testWidgets('shows SnackBar with error message on AuthFailure', (
      tester,
    ) async {
      final controller = StreamController<AuthState>();
      addTearDown(controller.close);

      whenListen(
        mockBloc,
        controller.stream,
        initialState: const AuthInitial(),
      );

      await tester.pumpWidget(_buildSubject(mockBloc));

      controller.add(
        const AuthFailure(
          code: 'INVALID_CREDENTIALS',
          message: 'Email ou mot de passe incorrect.',
        ),
      );

      // Allow the BlocListener to process the new state.
      await tester.pumpAndSettle();

      expect(
        find.text('Email ou mot de passe incorrect.'),
        findsOneWidget,
      );
    });
  });

  // =========================================================================
  // 6. BlocListener – navigation on AuthAuthenticated
  // =========================================================================
  group('BlocListener navigation', () {
    testWidgets('navigates to home on AuthAuthenticated', (tester) async {
      final mockRouter = MockGoRouter();
      final controller = StreamController<AuthState>();
      addTearDown(controller.close);

      whenListen(
        mockBloc,
        controller.stream,
        initialState: const AuthInitial(),
      );

      await tester.pumpWidget(_buildSubject(mockBloc, router: mockRouter));

      controller.add(
        const AuthAuthenticated(user: _fakeUser, roles: ['user']),
      );
      await tester.pumpAndSettle();

      verify(() => mockRouter.go(RoutePaths.home)).called(1);
    });

    testWidgets('navigates to OTP on AUTH_PHONE_NOT_VERIFIED failure', (
      tester,
    ) async {
      final mockRouter = MockGoRouter();
      final controller = StreamController<AuthState>();
      addTearDown(controller.close);

      whenListen(
        mockBloc,
        controller.stream,
        initialState: const AuthInitial(),
      );

      await tester.pumpWidget(_buildSubject(mockBloc, router: mockRouter));

      controller.add(
        const AuthFailure(
          code: 'AUTH_PHONE_NOT_VERIFIED',
          message: 'Phone not verified',
          details: {'phone': '+2250700000000'},
        ),
      );
      await tester.pumpAndSettle();

      verify(
        () => mockRouter.go(RoutePaths.otp, extra: '+2250700000000'),
      ).called(1);
    });
  });

  // =========================================================================
  // 7. Loading state
  // =========================================================================
  group('loading state', () {
    testWidgets('shows CircularProgressIndicator when state is AuthLoading', (
      tester,
    ) async {
      when(() => mockBloc.state).thenReturn(const AuthLoading());
      whenListen(
        mockBloc,
        const Stream<AuthState>.empty(),
        initialState: const AuthLoading(),
      );

      await tester.pumpWidget(_buildSubject(mockBloc));

      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      // The label text should NOT be visible when loading.
      expect(find.text('Se connecter'), findsNothing);
    });

    testWidgets('does not show spinner when state is AuthInitial', (
      tester,
    ) async {
      await tester.pumpWidget(_buildSubject(mockBloc));

      expect(find.byType(CircularProgressIndicator), findsNothing);
      expect(find.text('Se connecter'), findsOneWidget);
    });
  });
}
