import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/finance/data/models/financial_dashboard_model.dart';
import 'package:bookmi_app/features/finance/data/models/payout_model.dart';
import 'package:dio/dio.dart';

class FinancialRepository {
  FinancialRepository({required ApiClient apiClient})
    : _dio = apiClient.dio;

  /// Test-only constructor.
  FinancialRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  /// Fetch the talent financial dashboard summary.
  Future<ApiResult<FinancialDashboardModel>> getDashboard() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meFinancialDashboard,
      );
      return ApiSuccess(FinancialDashboardModel.fromJson(response.data!));
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Fetch paginated payout history.
  Future<ApiResult<List<PayoutModel>>> getPayouts({int page = 1}) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.mePayouts,
        queryParameters: {'page': page},
      );
      final items =
          (response.data!['data'] as List<dynamic>).cast<Map<String, dynamic>>();
      return ApiSuccess(items.map(PayoutModel.fromJson).toList());
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  ApiFailure<T> _mapDioError<T>(DioException e) {
    final errorData = e.response?.data as Map<String, dynamic>?;
    final error = errorData?['error'] as Map<String, dynamic>?;
    return ApiFailure(
      code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
      message:
          (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
    );
  }
}
