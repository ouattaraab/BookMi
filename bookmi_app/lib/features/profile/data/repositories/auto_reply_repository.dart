import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

class AutoReplySettings {
  const AutoReplySettings({
    required this.isActive,
    required this.message,
  });

  final bool isActive;
  final String message;

  factory AutoReplySettings.fromJson(Map<String, dynamic> json) {
    return AutoReplySettings(
      isActive: (json['auto_reply_is_active'] as bool?) ?? false,
      message: (json['auto_reply_message'] as String?) ?? '',
    );
  }
}

class AutoReplyRepository {
  AutoReplyRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  final Dio _dio;

  Future<ApiResult<AutoReplySettings>> getSettings() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meTalentProfile,
      );
      final data = (response.data?['data'] as Map<String, dynamic>?) ?? {};
      return ApiSuccess(AutoReplySettings.fromJson(data));
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'FETCH_ERROR',
        message:
            (error?['message'] as String?) ??
            e.message ??
            'Erreur de chargement.',
      );
    }
  }

  Future<ApiResult<void>> updateSettings({
    required bool isActive,
    required String message,
  }) async {
    try {
      await _dio.put<void>(
        ApiEndpoints.meTalentAutoReply,
        data: {
          'auto_reply_is_active': isActive,
          'auto_reply_message': message,
        },
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'UPDATE_ERROR',
        message:
            (error?['message'] as String?) ??
            e.message ??
            'Erreur de mise à jour.',
      );
    }
  }
}
