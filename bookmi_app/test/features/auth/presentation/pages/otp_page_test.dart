import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/presentation/pages/otp_page.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockAuthBloc extends MockBloc<AuthEvent, AuthState> implements AuthBloc {}

Widget buildSubject(AuthBloc bloc, {String phone = '+2250701020304'}) {
  return MaterialApp(
    home: BlocProvider<AuthBloc>.value(
      value: bloc,
      child: OtpPage(phone: phone),
    ),
  );
}

void main() {
  late MockAuthBloc mockAuthBloc;

  setUpAll(() {
    registerFallbackValue(const AuthOtpSubmitted(phone: '', code: ''));
  });

  setUp(() {
    mockAuthBloc = MockAuthBloc();
    when(() => mockAuthBloc.state).thenReturn(const AuthInitial());
  });

  tearDown(() async {
    await mockAuthBloc.close();
  });

  group('OtpPage', () {
    testWidgets('renders correctly', (tester) async {
      await tester.pumpWidget(buildSubject(mockAuthBloc));
      await tester.pump();

      // Title
      expect(find.text('Vérification OTP'), findsOneWidget);

      // Informational text
      expect(find.text('Un code a été envoyé au'), findsOneWidget);

      // Masked phone
      expect(find.text('+225 07 XX XX XX 04'), findsOneWidget);

      // 6 TextField inputs for the OTP code
      expect(find.byType(TextField), findsNWidgets(6));

      // Timer text showing 60s initially
      expect(find.textContaining('60s'), findsOneWidget);
    });

    testWidgets('shows masked phone correctly', (tester) async {
      await tester.pumpWidget(
        buildSubject(mockAuthBloc),
      );
      await tester.pump();

      expect(find.text('+225 07 XX XX XX 04'), findsOneWidget);
    });

    testWidgets('timer counts down', (tester) async {
      await tester.pumpWidget(buildSubject(mockAuthBloc));
      await tester.pump();

      // Initially shows 60s
      expect(
        find.text('Renvoyer le code dans 60s'),
        findsOneWidget,
      );

      // Advance 1 second — timer fires and decrements
      await tester.pump(const Duration(seconds: 1));
      expect(
        find.text('Renvoyer le code dans 59s'),
        findsOneWidget,
      );

      // Advance another second
      await tester.pump(const Duration(seconds: 1));
      expect(
        find.text('Renvoyer le code dans 58s'),
        findsOneWidget,
      );
    });

    testWidgets('resend button appears after timer expires', (tester) async {
      await tester.pumpWidget(buildSubject(mockAuthBloc));
      await tester.pump();

      // Initially the resend TextButton should not be present
      expect(find.widgetWithText(TextButton, 'Renvoyer le code'), findsNothing);
      expect(find.text('Renvoyer le code dans 60s'), findsOneWidget);

      // Advance through all 60 seconds — pump each second so the
      // Timer.periodic callback fires on every tick.
      for (var i = 0; i < 60; i++) {
        await tester.pump(const Duration(seconds: 1));
      }

      // Now the resend TextButton should be visible
      expect(
        find.widgetWithText(TextButton, 'Renvoyer le code'),
        findsOneWidget,
      );
    });

    testWidgets('resend button dispatches AuthOtpResendRequested', (
      tester,
    ) async {
      await tester.pumpWidget(buildSubject(mockAuthBloc));
      await tester.pump();

      // Exhaust the timer so the resend button appears
      for (var i = 0; i < 60; i++) {
        await tester.pump(const Duration(seconds: 1));
      }

      // Tap the resend button
      await tester.tap(find.widgetWithText(TextButton, 'Renvoyer le code'));
      await tester.pump();

      // Verify the bloc received the resend event
      verify(
        () => mockAuthBloc.add(
          const AuthOtpResendRequested(phone: '+2250701020304'),
        ),
      ).called(1);
    });

    testWidgets('shows error SnackBar on AuthFailure', (tester) async {
      // Use whenListen so we can emit states after the widget is built.
      whenListen(
        mockAuthBloc,
        Stream<AuthState>.fromIterable([
          const AuthFailure(
            code: 'invalid_otp',
            message: 'Code OTP invalide',
          ),
        ]),
        initialState: const AuthInitial(),
      );

      await tester.pumpWidget(buildSubject(mockAuthBloc));
      await tester.pump(); // Let the BlocListener process the emitted state
      await tester.pump(); // Allow SnackBar animation to start

      // Verify the SnackBar is displayed with the error message
      expect(find.text('Code OTP invalide'), findsOneWidget);
      expect(find.byType(SnackBar), findsOneWidget);
    });
  });
}
