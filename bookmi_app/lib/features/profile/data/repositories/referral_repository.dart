import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

/// Referral stats returned by the backend.
class ReferralInfo {
  const ReferralInfo({
    required this.code,
    required this.total,
    required this.completed,
    required this.pending,
  });

  final String code;
  final int total;
  final int completed;
  final int pending;

  factory ReferralInfo.fromJson(Map<String, dynamic> json) {
    return ReferralInfo(
      code: json['code'] as String,
      total: (json['total'] as int?) ?? 0,
      completed: (json['completed'] as int?) ?? 0,
      pending: (json['pending'] as int?) ?? 0,
    );
  }
}

class ReferralRepository {
  ReferralRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  final Dio _dio;

  /// Fetch current user's referral code + stats.
  Future<ApiResult<ReferralInfo>> getReferralInfo() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meReferral,
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(ReferralInfo.fromJson(data));
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  /// Apply a referral code (post-registration).
  Future<ApiResult<void>> applyCode(String code) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.meReferralApply,
        data: {'code': code.trim().toUpperCase()},
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
