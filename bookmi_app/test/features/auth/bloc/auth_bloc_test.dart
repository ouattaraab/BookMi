import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/models/auth_response.dart';
import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:bookmi_app/features/auth/data/repositories/auth_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockAuthRepository extends Mock implements AuthRepository {}

class MockSecureStorage extends Mock implements SecureStorage {}

void main() {
  late MockAuthRepository mockRepo;
  late MockSecureStorage mockStorage;

  const testUser = AuthUser(
    id: 1,
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@test.com',
    phone: '+2250701020304',
    isActive: true,
    isClientVerified: false,
  );

  const testAuthResponse = AuthResponse(
    token: 'test-token-123',
    user: testUser,
    roles: ['client'],
  );

  setUp(() {
    mockRepo = MockAuthRepository();
    mockStorage = MockSecureStorage();
  });

  AuthBloc buildBloc() => AuthBloc(
    authRepository: mockRepo,
    secureStorage: mockStorage,
  );

  group('AuthBloc', () {
    test('initial state is AuthInitial', () async {
      final bloc = buildBloc();
      expect(bloc.state, isA<AuthInitial>());
      await bloc.close();
    });

    group('AuthCheckRequested', () {
      blocTest<AuthBloc, AuthState>(
        'emits [AuthAuthenticated] when token exists and '
        'profile succeeds',
        build: () {
          when(
            () => mockStorage.getToken(),
          ).thenAnswer((_) async => 'valid-token');
          when(
            () => mockRepo.getProfile(),
          ).thenAnswer(
            (_) async => const ApiSuccess(
              AuthResponse(token: '', user: testUser, roles: ['client']),
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(const AuthCheckRequested()),
        expect: () => [
          isA<AuthAuthenticated>().having(
            (s) => s.user,
            'user',
            testUser,
          ),
        ],
      );

      blocTest<AuthBloc, AuthState>(
        'emits [AuthUnauthenticated] when no token',
        build: () {
          when(() => mockStorage.getToken()).thenAnswer((_) async => null);
          return buildBloc();
        },
        act: (bloc) => bloc.add(const AuthCheckRequested()),
        expect: () => [isA<AuthUnauthenticated>()],
      );

      blocTest<AuthBloc, AuthState>(
        'emits [AuthUnauthenticated] when token exists '
        'but profile fails',
        build: () {
          when(
            () => mockStorage.getToken(),
          ).thenAnswer((_) async => 'expired-token');
          when(() => mockRepo.getProfile()).thenAnswer(
            (_) async => const ApiFailure(
              code: 'UNAUTHENTICATED',
              message: 'Token expiré.',
            ),
          );
          when(() => mockStorage.deleteToken()).thenAnswer((_) async {});
          return buildBloc();
        },
        act: (bloc) => bloc.add(const AuthCheckRequested()),
        expect: () => [isA<AuthUnauthenticated>()],
        verify: (_) {
          verify(() => mockStorage.deleteToken()).called(1);
        },
      );
    });

    group('AuthLoginSubmitted', () {
      blocTest<AuthBloc, AuthState>(
        'emits [Loading, Authenticated] on success',
        build: () {
          when(
            () => mockRepo.login('john@test.com', 'password'),
          ).thenAnswer(
            (_) async => const ApiSuccess(testAuthResponse),
          );
          when(
            () => mockStorage.saveToken('test-token-123'),
          ).thenAnswer((_) async {});
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthLoginSubmitted(
            email: 'john@test.com',
            password: 'password',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthAuthenticated>()
              .having((s) => s.user, 'user', testUser)
              .having(
                (s) => s.roles,
                'roles',
                ['client'],
              ),
        ],
        verify: (_) {
          verify(
            () => mockStorage.saveToken('test-token-123'),
          ).called(1);
        },
      );

      blocTest<AuthBloc, AuthState>(
        'emits [Loading, Failure] on invalid credentials',
        build: () {
          when(
            () => mockRepo.login('john@test.com', 'wrong'),
          ).thenAnswer(
            (_) async => const ApiFailure(
              code: 'AUTH_INVALID_CREDENTIALS',
              message: 'Identifiants invalides.',
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthLoginSubmitted(
            email: 'john@test.com',
            password: 'wrong',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthFailure>()
              .having(
                (s) => s.code,
                'code',
                'AUTH_INVALID_CREDENTIALS',
              )
              .having(
                (s) => s.message,
                'message',
                'Identifiants invalides.',
              ),
        ],
      );

      blocTest<AuthBloc, AuthState>(
        'emits [Loading, Failure] when phone not verified',
        build: () {
          when(
            () => mockRepo.login('john@test.com', 'password'),
          ).thenAnswer(
            (_) async => const ApiFailure(
              code: 'AUTH_PHONE_NOT_VERIFIED',
              message: 'Téléphone non vérifié.',
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthLoginSubmitted(
            email: 'john@test.com',
            password: 'password',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthFailure>().having(
            (s) => s.code,
            'code',
            'AUTH_PHONE_NOT_VERIFIED',
          ),
        ],
      );
    });

    group('AuthRegisterSubmitted', () {
      final registerData = <String, dynamic>{
        'first_name': 'John',
        'last_name': 'Doe',
        'email': 'john@test.com',
        'phone': '+2250701020304',
        'password': 'password123',
        'password_confirmation': 'password123',
        'role': 'client',
      };

      blocTest<AuthBloc, AuthState>(
        'emits [Loading, RegistrationSuccess] on success',
        build: () {
          when(
            () => mockRepo.register(registerData),
          ).thenAnswer(
            (_) async => const ApiSuccess(null),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          AuthRegisterSubmitted(data: registerData),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthRegistrationSuccess>().having(
            (s) => s.phone,
            'phone',
            '+2250701020304',
          ),
        ],
      );

      blocTest<AuthBloc, AuthState>(
        'emits [Loading, Failure] on validation error',
        build: () {
          when(
            () => mockRepo.register(registerData),
          ).thenAnswer(
            (_) async => const ApiFailure(
              code: 'VALIDATION_FAILED',
              message: 'Données invalides.',
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          AuthRegisterSubmitted(data: registerData),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthFailure>().having(
            (s) => s.code,
            'code',
            'VALIDATION_FAILED',
          ),
        ],
      );
    });

    group('AuthOtpSubmitted', () {
      blocTest<AuthBloc, AuthState>(
        'emits [Loading, Authenticated] on valid OTP',
        build: () {
          when(
            () => mockRepo.verifyOtp('+2250701020304', '123456'),
          ).thenAnswer(
            (_) async => const ApiSuccess(testAuthResponse),
          );
          when(
            () => mockStorage.saveToken('test-token-123'),
          ).thenAnswer((_) async {});
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthOtpSubmitted(
            phone: '+2250701020304',
            code: '123456',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthAuthenticated>(),
        ],
        verify: (_) {
          verify(
            () => mockStorage.saveToken('test-token-123'),
          ).called(1);
        },
      );

      blocTest<AuthBloc, AuthState>(
        'emits [Loading, Failure] on invalid OTP',
        build: () {
          when(
            () => mockRepo.verifyOtp('+2250701020304', '000000'),
          ).thenAnswer(
            (_) async => const ApiFailure(
              code: 'AUTH_OTP_INVALID',
              message: 'Code invalide.',
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthOtpSubmitted(
            phone: '+2250701020304',
            code: '000000',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthFailure>().having(
            (s) => s.code,
            'code',
            'AUTH_OTP_INVALID',
          ),
        ],
      );

      blocTest<AuthBloc, AuthState>(
        'emits [Loading, Failure] on expired OTP',
        build: () {
          when(
            () => mockRepo.verifyOtp('+2250701020304', '111111'),
          ).thenAnswer(
            (_) async => const ApiFailure(
              code: 'AUTH_OTP_EXPIRED',
              message: 'Code expiré.',
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthOtpSubmitted(
            phone: '+2250701020304',
            code: '111111',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthFailure>().having(
            (s) => s.code,
            'code',
            'AUTH_OTP_EXPIRED',
          ),
        ],
      );
    });

    group('AuthOtpResendRequested', () {
      blocTest<AuthBloc, AuthState>(
        'emits [Loading, OtpResent] on success',
        build: () {
          when(
            () => mockRepo.resendOtp('+2250701020304'),
          ).thenAnswer(
            (_) async => const ApiSuccess(null),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthOtpResendRequested(
            phone: '+2250701020304',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthOtpResent>(),
        ],
      );

      blocTest<AuthBloc, AuthState>(
        'emits [Loading, Failure] on resend limit',
        build: () {
          when(
            () => mockRepo.resendOtp('+2250701020304'),
          ).thenAnswer(
            (_) async => const ApiFailure(
              code: 'AUTH_OTP_RESEND_LIMIT',
              message: 'Limite de renvoi atteinte.',
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthOtpResendRequested(
            phone: '+2250701020304',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthFailure>().having(
            (s) => s.code,
            'code',
            'AUTH_OTP_RESEND_LIMIT',
          ),
        ],
      );
    });

    group('AuthForgotPasswordSubmitted', () {
      blocTest<AuthBloc, AuthState>(
        'emits [Loading, ForgotPasswordSuccess]',
        build: () {
          when(
            () => mockRepo.forgotPassword('john@test.com'),
          ).thenAnswer(
            (_) async => const ApiSuccess(null),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const AuthForgotPasswordSubmitted(
            email: 'john@test.com',
          ),
        ),
        expect: () => [
          isA<AuthLoading>(),
          isA<AuthForgotPasswordSuccess>(),
        ],
      );
    });

    group('AuthLogoutRequested', () {
      blocTest<AuthBloc, AuthState>(
        'emits [AuthUnauthenticated] and clears token',
        build: () {
          when(() => mockRepo.logout()).thenAnswer(
            (_) async => const ApiSuccess(null),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(const AuthLogoutRequested()),
        expect: () => [isA<AuthUnauthenticated>()],
        verify: (_) {
          verify(() => mockRepo.logout()).called(1);
        },
      );
    });

    group('AuthSessionExpired', () {
      blocTest<AuthBloc, AuthState>(
        'emits [AuthUnauthenticated] and deletes token',
        build: () {
          when(() => mockStorage.deleteToken()).thenAnswer((_) async {});
          return buildBloc();
        },
        act: (bloc) => bloc.add(const AuthSessionExpired()),
        expect: () => [isA<AuthUnauthenticated>()],
        verify: (_) {
          verify(() => mockStorage.deleteToken()).called(1);
        },
      );
    });
  });
}
