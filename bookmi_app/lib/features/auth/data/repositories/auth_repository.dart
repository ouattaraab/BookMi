import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:bookmi_app/features/auth/data/models/auth_response.dart';
import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:dio/dio.dart';

class AuthRepository {
  AuthRepository({
    required ApiClient apiClient,
    required SecureStorage secureStorage,
  }) : _dio = apiClient.dio,
       _secureStorage = secureStorage;

  AuthRepository.forTesting({
    required Dio dio,
    required SecureStorage secureStorage,
  }) : _dio = dio,
       _secureStorage = secureStorage;

  final Dio _dio;
  final SecureStorage _secureStorage;

  Future<ApiResult<AuthResponse>> login(String email, String password) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.authLogin,
        data: {'email': email, 'password': password},
      );

      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(AuthResponse.fromJson(data));
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<void>> register(Map<String, dynamic> data) async {
    try {
      await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.authRegister,
        data: data,
      );

      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<AuthResponse>> verifyOtp(String phone, String code) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.authVerifyOtp,
        data: {'phone': phone, 'code': code},
      );

      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(AuthResponse.fromJson(data));
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<void>> resendOtp(String phone) async {
    try {
      await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.authResendOtp,
        data: {'phone': phone},
      );

      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<void>> forgotPassword(String email) async {
    try {
      await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.authForgotPassword,
        data: {'email': email},
      );

      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<void>> logout() async {
    try {
      await _dio.post<Map<String, dynamic>>(ApiEndpoints.authLogout);
      await _secureStorage.deleteToken();

      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<AuthResponse>> getProfile() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(ApiEndpoints.me);

      final data = response.data!['data'] as Map<String, dynamic>;
      final user = AuthUser.fromJson(data['user'] as Map<String, dynamic>);
      final roles =
          (data['roles'] as List<dynamic>?)?.cast<String>() ?? const [];
      return ApiSuccess(
        AuthResponse(token: '', user: user, roles: roles),
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<List<Map<String, dynamic>>>> getCategories() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.categories,
      );
      final data = response.data!['data'] as List<dynamic>;
      return ApiSuccess(data.cast<Map<String, dynamic>>());
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<void> updateFcmToken(String token) async {
    try {
      await _dio.put<Map<String, dynamic>>(
        ApiEndpoints.meUpdateFcmToken,
        data: {'fcm_token': token},
      );
    } catch (_) {
      // Non-critical — ignore failures
    }
  }

  ApiFailure<T> _handleError<T>(DioException e) {
    final data = e.response?.data;

    if (data is Map<String, dynamic> && data.containsKey('error')) {
      final error = data['error'] as Map<String, dynamic>;
      return ApiFailure(
        code: error['code'] as String? ?? 'UNKNOWN_ERROR',
        message: error['message'] as String? ?? 'Une erreur est survenue.',
        details: error['details'] as Map<String, dynamic>?,
      );
    }

    return ApiFailure(
      code: 'NETWORK_ERROR',
      message: e.message ?? 'Erreur de connexion au serveur.',
    );
  }
}
