import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:bookmi_app/features/auth/data/models/auth_response.dart';
import 'package:bookmi_app/features/auth/data/repositories/auth_repository.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockDio extends Mock implements Dio {}

class MockSecureStorage extends Mock implements SecureStorage {}

void main() {
  late MockDio mockDio;
  late MockSecureStorage mockStorage;
  late AuthRepository repository;

  final userJson = <String, dynamic>{
    'id': 1,
    'first_name': 'John',
    'last_name': 'Doe',
    'email': 'john@test.com',
    'phone': '+2250701020304',
    'phone_verified_at': null,
    'is_active': true,
  };

  final loginResponseJson = <String, dynamic>{
    'data': <String, dynamic>{
      'token': 'test-token',
      'user': userJson,
      'roles': <dynamic>['client'],
    },
  };

  setUp(() {
    mockDio = MockDio();
    mockStorage = MockSecureStorage();
    repository = AuthRepository.forTesting(
      dio: mockDio,
      secureStorage: mockStorage,
    );
  });

  group('AuthRepository', () {
    group('login', () {
      test('returns ApiSuccess with AuthResponse on 200', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authLogin,
            data: any(named: 'data'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: loginResponseJson,
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );

        final result = await repository.login(
          'john@test.com',
          'password',
        );

        expect(result, isA<ApiSuccess<AuthResponse>>());
        final data = (result as ApiSuccess<AuthResponse>).data;
        expect(data.token, 'test-token');
        expect(data.user.email, 'john@test.com');
        expect(data.roles, ['client']);
      });

      test('returns ApiFailure on DioException', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authLogin,
            data: any(named: 'data'),
          ),
        ).thenThrow(
          DioException(
            requestOptions: RequestOptions(),
            response: Response(
              data: <String, dynamic>{
                'error': <String, dynamic>{
                  'code': 'AUTH_INVALID_CREDENTIALS',
                  'message': 'Identifiants invalides.',
                },
              },
              statusCode: 401,
              requestOptions: RequestOptions(),
            ),
          ),
        );

        final result = await repository.login(
          'john@test.com',
          'wrong',
        );

        expect(result, isA<ApiFailure<AuthResponse>>());
        final failure = result as ApiFailure<AuthResponse>;
        expect(failure.code, 'AUTH_INVALID_CREDENTIALS');
        expect(failure.message, 'Identifiants invalides.');
      });
    });

    group('register', () {
      test('returns ApiSuccess on 201', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authRegister,
            data: any(named: 'data'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: <String, dynamic>{
              'data': <String, dynamic>{
                'user': userJson,
                'message': 'Inscription réussie.',
              },
            },
            statusCode: 201,
            requestOptions: RequestOptions(),
          ),
        );

        final result = await repository.register({
          'first_name': 'John',
          'last_name': 'Doe',
          'email': 'john@test.com',
          'phone': '+2250701020304',
          'password': 'password123',
          'role': 'client',
        });

        expect(result, isA<ApiSuccess<void>>());
      });

      test('returns ApiFailure on validation error', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authRegister,
            data: any(named: 'data'),
          ),
        ).thenThrow(
          DioException(
            requestOptions: RequestOptions(),
            response: Response(
              data: <String, dynamic>{
                'error': <String, dynamic>{
                  'code': 'VALIDATION_FAILED',
                  'message': 'Données invalides.',
                  'details': <String, dynamic>{
                    'email': <dynamic>[
                      'Email déjà utilisé.',
                    ],
                  },
                },
              },
              statusCode: 422,
              requestOptions: RequestOptions(),
            ),
          ),
        );

        final result = await repository.register({
          'email': 'exists@test.com',
        });

        expect(result, isA<ApiFailure<void>>());
        final failure = result as ApiFailure<void>;
        expect(failure.code, 'VALIDATION_FAILED');
      });
    });

    group('verifyOtp', () {
      test('returns ApiSuccess with AuthResponse on 200', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authVerifyOtp,
            data: any(named: 'data'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: loginResponseJson,
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );

        final result = await repository.verifyOtp(
          '+2250701020304',
          '123456',
        );

        expect(result, isA<ApiSuccess<AuthResponse>>());
      });

      test(
        'returns ApiFailure on invalid OTP',
        () async {
          when(
            () => mockDio.post<Map<String, dynamic>>(
              ApiEndpoints.authVerifyOtp,
              data: any(named: 'data'),
            ),
          ).thenThrow(
            DioException(
              requestOptions: RequestOptions(),
              response: Response(
                data: <String, dynamic>{
                  'error': <String, dynamic>{
                    'code': 'AUTH_OTP_INVALID',
                    'message': 'Code invalide.',
                  },
                },
                statusCode: 422,
                requestOptions: RequestOptions(),
              ),
            ),
          );

          final result = await repository.verifyOtp(
            '+2250701020304',
            '000000',
          );

          expect(result, isA<ApiFailure<AuthResponse>>());
          final failure = result as ApiFailure<AuthResponse>;
          expect(failure.code, 'AUTH_OTP_INVALID');
        },
      );
    });

    group('resendOtp', () {
      test('returns ApiSuccess on 200', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authResendOtp,
            data: any(named: 'data'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: <String, dynamic>{
              'data': <String, dynamic>{
                'message': 'Code renvoyé.',
              },
            },
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );

        final result = await repository.resendOtp('+2250701020304');
        expect(result, isA<ApiSuccess<void>>());
      });

      test('returns ApiFailure on resend limit', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authResendOtp,
            data: any(named: 'data'),
          ),
        ).thenThrow(
          DioException(
            requestOptions: RequestOptions(),
            response: Response(
              data: <String, dynamic>{
                'error': <String, dynamic>{
                  'code': 'AUTH_OTP_RESEND_LIMIT',
                  'message': 'Limite de renvoi atteinte.',
                },
              },
              statusCode: 429,
              requestOptions: RequestOptions(),
            ),
          ),
        );

        final result = await repository.resendOtp('+2250701020304');
        expect(result, isA<ApiFailure<void>>());
        final failure = result as ApiFailure<void>;
        expect(failure.code, 'AUTH_OTP_RESEND_LIMIT');
      });
    });

    group('forgotPassword', () {
      test('returns ApiSuccess on 200', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authForgotPassword,
            data: any(named: 'data'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: <String, dynamic>{
              'data': <String, dynamic>{
                'message': 'Lien envoyé.',
              },
            },
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );

        final result = await repository.forgotPassword(
          'john@test.com',
        );
        expect(result, isA<ApiSuccess<void>>());
      });
    });

    group('logout', () {
      test('returns ApiSuccess and deletes token', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authLogout,
          ),
        ).thenAnswer(
          (_) async => Response(
            data: <String, dynamic>{
              'data': <String, dynamic>{
                'message': 'Déconnexion réussie.',
              },
            },
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );
        when(() => mockStorage.deleteToken()).thenAnswer((_) async {});

        final result = await repository.logout();

        expect(result, isA<ApiSuccess<void>>());
        verify(() => mockStorage.deleteToken()).called(1);
      });
    });

    group('getProfile', () {
      test('returns ApiSuccess with AuthResponse on 200', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.me,
          ),
        ).thenAnswer(
          (_) async => Response(
            data: <String, dynamic>{
              'data': <String, dynamic>{
                'user': userJson,
                'roles': <dynamic>['client'],
              },
            },
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );

        final result = await repository.getProfile();

        expect(result, isA<ApiSuccess<AuthResponse>>());
        final data = (result as ApiSuccess<AuthResponse>).data;
        expect(data.user.id, 1);
        expect(data.user.firstName, 'John');
        expect(data.user.email, 'john@test.com');
        expect(data.roles, ['client']);
      });
    });

    group('getCategories', () {
      test('returns ApiSuccess with list of categories on 200', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.categories,
          ),
        ).thenAnswer(
          (_) async => Response(
            data: <String, dynamic>{
              'data': <dynamic>[
                <String, dynamic>{'id': 1, 'name': 'DJ'},
              ],
            },
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );

        final result = await repository.getCategories();

        expect(result, isA<ApiSuccess<List<Map<String, dynamic>>>>());
        final categories =
            (result as ApiSuccess<List<Map<String, dynamic>>>).data;
        expect(categories.length, 1);
        expect(categories.first['id'], 1);
        expect(categories.first['name'], 'DJ');
      });

      test('returns ApiFailure on DioException', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.categories,
          ),
        ).thenThrow(
          DioException(
            requestOptions: RequestOptions(),
            response: Response(
              data: <String, dynamic>{
                'error': <String, dynamic>{
                  'code': 'NETWORK_ERROR',
                  'message': 'Une erreur est survenue.',
                },
              },
              statusCode: 500,
              requestOptions: RequestOptions(),
            ),
          ),
        );

        final result = await repository.getCategories();

        expect(result, isA<ApiFailure<List<Map<String, dynamic>>>>());
        final failure = result as ApiFailure<List<Map<String, dynamic>>>;
        expect(failure.code, 'NETWORK_ERROR');
      });
    });

    group('_handleError', () {
      test('parses API error envelope', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authLogin,
            data: any(named: 'data'),
          ),
        ).thenThrow(
          DioException(
            requestOptions: RequestOptions(),
            response: Response(
              data: <String, dynamic>{
                'error': <String, dynamic>{
                  'code': 'AUTH_ACCOUNT_LOCKED',
                  'message': 'Compte verrouillé.',
                },
              },
              statusCode: 423,
              requestOptions: RequestOptions(),
            ),
          ),
        );

        final result = await repository.login('a@b.com', 'x');
        expect(result, isA<ApiFailure<AuthResponse>>());
        final failure = result as ApiFailure<AuthResponse>;
        expect(failure.code, 'AUTH_ACCOUNT_LOCKED');
      });

      test('returns NETWORK_ERROR on no response', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.authLogin,
            data: any(named: 'data'),
          ),
        ).thenThrow(
          DioException(
            requestOptions: RequestOptions(),
            message: 'Connection refused',
          ),
        );

        final result = await repository.login('a@b.com', 'x');
        expect(result, isA<ApiFailure<AuthResponse>>());
        final failure = result as ApiFailure<AuthResponse>;
        expect(failure.code, 'NETWORK_ERROR');
      });
    });
  });
}
