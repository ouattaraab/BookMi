import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/evaluation/data/models/review_model.dart';
import 'package:dio/dio.dart';

class ReviewRepository {
  ReviewRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  /// Test-only constructor.
  ReviewRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  Future<ApiResult<List<ReviewModel>>> getReviews(int bookingId) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.bookingReviews(bookingId),
      );
      final list = (response.data!['data'] as List<dynamic>)
          .map((e) => ReviewModel.fromJson(e as Map<String, dynamic>))
          .toList();
      return ApiSuccess(list);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  Future<ApiResult<ReviewModel>> submitReview(
    int bookingId, {
    required String type,
    required int rating,
    String? comment,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingReviews(bookingId),
        data: {
          'type': type,
          'rating': rating,
          if (comment != null && comment.isNotEmpty) 'comment': comment,
        },
      );
      return ApiSuccess(
        ReviewModel.fromJson(
          response.data!['data'] as Map<String, dynamic>,
        ),
      );
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  ApiFailure<T> _mapError<T>(DioException e) {
    // Safe cast: server may return plain text (e.g. 429 "Too Many Requests").
    final raw = e.response?.data;
    final errorData = raw is Map ? Map<String, dynamic>.from(raw) : null;
    final error = errorData?['error'] is Map
        ? Map<String, dynamic>.from(errorData!['error'] as Map)
        : null;
    return ApiFailure(
      code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
      message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
    );
  }
}
