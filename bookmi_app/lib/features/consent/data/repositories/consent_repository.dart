import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

class ConsentRepository {
  ConsentRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  final Dio _dio;

  Future<ApiResult<Map<String, dynamic>>> fetchConsents() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.consents,
      );
      final data = response.data?['data'] as Map<String, dynamic>? ?? {};
      return ApiSuccess(data);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<void>> updateOptIns(Map<String, bool> consents) async {
    try {
      await _dio.patch<void>(
        ApiEndpoints.consentsUpdate,
        data: {'consents': consents},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResult<void>> reconsent(Map<String, bool> consents) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.consentsReconsent,
        data: {'consents': consents},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  ApiResult<T> _handleError<T>(DioException e) {
    final data = e.response?.data;
    if (data is Map) {
      final error = data['error'] as Map?;
      final code = error?['code'] as String? ?? 'API_ERROR';
      final message =
          error?['message'] as String? ?? 'Une erreur est survenue.';
      return ApiFailure(code: code, message: message);
    }
    return ApiFailure(
      code: 'NETWORK_ERROR',
      message: e.message ?? 'Erreur réseau.',
    );
  }
}
