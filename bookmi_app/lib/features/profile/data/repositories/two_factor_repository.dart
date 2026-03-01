import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

class TwoFactorRepository {
  TwoFactorRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  /// Test-only constructor.
  TwoFactorRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  /// Returns `{enabled: bool, method: String?}`.
  Future<ApiResult<Map<String, dynamic>>> getStatus() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.auth2faStatus,
      );
      return ApiSuccess(response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  /// Returns `{secret: String, qr_code_svg: String}`.
  Future<ApiResult<Map<String, dynamic>>> setupTotp() async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.auth2faSetupTotp,
      );
      return ApiSuccess(response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  Future<ApiResult<void>> enableTotp(String code) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.auth2faEnableTotp,
        data: {'code': code},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  Future<ApiResult<void>> setupEmail() async {
    try {
      await _dio.post<void>(ApiEndpoints.auth2faSetupEmail);
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  Future<ApiResult<void>> enableEmail(String code) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.auth2faEnableEmail,
        data: {'code': code},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  Future<ApiResult<void>> disable(String password) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.auth2faDisable,
        data: {'password': password},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  ApiFailure<T> _mapError<T>(DioException e) {
    final errorData = e.response?.data as Map<String, dynamic>?;
    final error = errorData?['error'] as Map<String, dynamic>?;
    return ApiFailure(
      code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
      message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
    );
  }
}
