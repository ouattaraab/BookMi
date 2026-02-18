import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/repositories/auth_repository.dart';
import 'package:bookmi_app/features/auth/presentation/pages/register_page.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

// ---------------------------------------------------------------------------
// Mocks
// ---------------------------------------------------------------------------

class MockAuthBloc extends MockBloc<AuthEvent, AuthState> implements AuthBloc {}

class MockAuthRepository extends Mock implements AuthRepository {}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

Widget buildSubject(AuthBloc bloc, AuthRepository repo) {
  return MaterialApp(
    home: RepositoryProvider<AuthRepository>.value(
      value: repo,
      child: BlocProvider<AuthBloc>.value(
        value: bloc,
        child: const RegisterPage(),
      ),
    ),
  );
}

void main() {
  late MockAuthBloc mockBloc;
  late MockAuthRepository mockRepo;

  setUpAll(() {
    registerFallbackValue(
      const AuthRegisterSubmitted(data: <String, dynamic>{}),
    );
  });

  setUp(() {
    mockBloc = MockAuthBloc();
    mockRepo = MockAuthRepository();

    // RegisterPage calls getCategories() in initState — stub it.
    when(() => mockRepo.getCategories()).thenAnswer(
      (_) async => const ApiSuccess(<Map<String, dynamic>>[]),
    );

    // Default bloc state.
    when(() => mockBloc.state).thenReturn(const AuthInitial());
  });

  // -----------------------------------------------------------------------
  // 1. Renders title
  // -----------------------------------------------------------------------
  testWidgets('renders "Créer un compte" title', (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    expect(find.text('Créer un compte'), findsOneWidget);
  });

  // -----------------------------------------------------------------------
  // 2. Renders all form fields
  // -----------------------------------------------------------------------
  testWidgets('renders all six TextFormField widgets', (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    // 5 AuthTextField (Prénom, Nom, Email, Mot de passe, Confirmer)
    // + 1 PhoneField = 6 TextFormField total
    expect(find.byType(TextFormField), findsNWidgets(6));
  });

  // -----------------------------------------------------------------------
  // 3. Renders role selector
  // -----------------------------------------------------------------------
  testWidgets('renders Client and Talent role options', (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    expect(find.text('Client'), findsOneWidget);
    expect(find.text('Talent'), findsOneWidget);
  });

  // -----------------------------------------------------------------------
  // 4. Client role selected by default
  // -----------------------------------------------------------------------
  testWidgets('Client role chip is selected by default', (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    // The _RoleChip for "Client" is built with selected: true which sets
    // fontWeight to w600. The "Talent" chip uses w400.
    final clientText = tester.widget<Text>(find.text('Client'));
    expect(clientText.style?.fontWeight, FontWeight.w600);

    final talentText = tester.widget<Text>(find.text('Talent'));
    expect(talentText.style?.fontWeight, FontWeight.w400);
  });

  // -----------------------------------------------------------------------
  // 5. Phone field shows +225 prefix
  // -----------------------------------------------------------------------
  testWidgets('phone field displays +225 prefix', (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    expect(find.text('+225'), findsOneWidget);
  });

  // -----------------------------------------------------------------------
  // 6. Email validation — invalid email
  // -----------------------------------------------------------------------
  testWidgets('shows error for invalid email', (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    // Find the Email field by its label and enter an invalid value.
    final emailField = find.widgetWithText(TextFormField, 'Email');
    await tester.enterText(emailField, 'not-an-email');

    // Tap the submit button to trigger form validation.
    await tester.ensureVisible(find.text("S'inscrire"));
    await tester.tap(find.text("S'inscrire"));
    await tester.pumpAndSettle();

    expect(
      find.text('Veuillez entrer une adresse e-mail valide.'),
      findsOneWidget,
    );
  });

  // -----------------------------------------------------------------------
  // 7. Password length validation
  // -----------------------------------------------------------------------
  testWidgets('shows error for password shorter than 8 characters',
      (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    // Enter a short password.
    final passwordField =
        find.widgetWithText(TextFormField, 'Mot de passe');
    await tester.enterText(passwordField, '1234');

    // Trigger validation via submit.
    await tester.ensureVisible(find.text("S'inscrire"));
    await tester.tap(find.text("S'inscrire"));
    await tester.pumpAndSettle();

    expect(
      find.text(
        'Le mot de passe doit contenir au moins 8 caractères.',
      ),
      findsOneWidget,
    );
  });

  // -----------------------------------------------------------------------
  // 8. Password confirmation mismatch
  // -----------------------------------------------------------------------
  testWidgets('shows error when passwords do not match', (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    final passwordField =
        find.widgetWithText(TextFormField, 'Mot de passe');
    await tester.enterText(passwordField, 'Password123');

    final confirmField =
        find.widgetWithText(TextFormField, 'Confirmer le mot de passe');
    await tester.enterText(confirmField, 'Different456');

    // Trigger validation via submit.
    await tester.ensureVisible(find.text("S'inscrire"));
    await tester.tap(find.text("S'inscrire"));
    await tester.pumpAndSettle();

    expect(
      find.text('Les mots de passe ne correspondent pas.'),
      findsOneWidget,
    );
  });

  // -----------------------------------------------------------------------
  // 9. Shows "Se connecter" link
  // -----------------------------------------------------------------------
  testWidgets('renders "Se connecter" navigation link', (tester) async {
    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    await tester.pumpAndSettle();

    expect(find.text('Se connecter'), findsOneWidget);
  });

  // -----------------------------------------------------------------------
  // 10. Loading state shows spinner
  // -----------------------------------------------------------------------
  testWidgets(
      'shows CircularProgressIndicator when state is AuthLoading',
      (tester) async {
    // Emit AuthLoading so the AuthButton renders the spinner.
    when(() => mockBloc.state).thenReturn(const AuthLoading());
    whenListen(
      mockBloc,
      Stream<AuthState>.value(const AuthLoading()),
      initialState: const AuthLoading(),
    );

    await tester.pumpWidget(buildSubject(mockBloc, mockRepo));
    // pumpAndSettle would timeout: CircularProgressIndicator loops forever
    await tester.pump();

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });
}
