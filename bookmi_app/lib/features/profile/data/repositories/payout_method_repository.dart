import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/models/payout_method_model.dart';
import 'package:bookmi_app/features/profile/data/models/withdrawal_request_model.dart';
import 'package:dio/dio.dart';

class PayoutMethodRepository {
  PayoutMethodRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  final Dio _dio;

  /// GET /api/v1/talent_profiles/me/payout_method
  Future<ApiResult<PayoutMethodModel>> getPayoutMethod() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.mePayoutMethod,
      );
      return ApiSuccess(
        PayoutMethodModel.fromJson(
          response.data!['data'] as Map<String, dynamic>,
        ),
      );
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// PATCH /api/v1/talent_profiles/me/payout_method
  Future<ApiResult<PayoutMethodModel>> updatePayoutMethod({
    required String payoutMethod,
    required Map<String, dynamic> payoutDetails,
  }) async {
    try {
      final response = await _dio.patch<Map<String, dynamic>>(
        ApiEndpoints.mePayoutMethod,
        data: {
          'payout_method': payoutMethod,
          'payout_details': payoutDetails,
        },
      );
      return ApiSuccess(
        PayoutMethodModel.fromJson(
          response.data!['data'] as Map<String, dynamic>,
        ),
      );
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// GET /api/v1/me/withdrawal_requests
  Future<ApiResult<List<WithdrawalRequestModel>>>
  getWithdrawalRequests() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meWithdrawalRequests,
      );
      final items = (response.data!['data']['data'] as List<dynamic>)
          .cast<Map<String, dynamic>>();
      return ApiSuccess(items.map(WithdrawalRequestModel.fromJson).toList());
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// POST /api/v1/me/withdrawal_requests
  Future<ApiResult<WithdrawalRequestModel>> createWithdrawalRequest({
    required int amount,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.meWithdrawalRequests,
        data: {'amount': amount},
      );
      return ApiSuccess(
        WithdrawalRequestModel.fromJson(
          response.data!['data'] as Map<String, dynamic>,
        ),
      );
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  ApiFailure<T> _mapDioError<T>(DioException e) {
    final errorData = e.response?.data as Map<String, dynamic>?;
    final error = errorData?['error'] as Map<String, dynamic>?;
    return ApiFailure(
      code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
      message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
    );
  }
}
