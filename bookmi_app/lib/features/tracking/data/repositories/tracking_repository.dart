import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/tracking/data/models/tracking_event_model.dart';
import 'package:dio/dio.dart';

class TrackingRepository {
  TrackingRepository({required ApiClient apiClient})
      : _dio = apiClient.dio;

  /// Test-only constructor.
  TrackingRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  Future<ApiResult<List<TrackingEventModel>>> getTrackingEvents(
    int bookingId,
  ) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.bookingTracking(bookingId),
      );
      final list = (response.data!['data'] as List<dynamic>)
          .map((e) => TrackingEventModel.fromJson(e as Map<String, dynamic>))
          .toList();
      return ApiSuccess(list);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  Future<ApiResult<TrackingEventModel>> postTrackingUpdate(
    int bookingId, {
    required String status,
    double? latitude,
    double? longitude,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingTracking(bookingId),
        data: {
          'status': status,
          if (latitude != null) 'latitude': latitude,
          if (longitude != null) 'longitude': longitude,
        },
      );
      return ApiSuccess(
        TrackingEventModel.fromJson(
          response.data!['data'] as Map<String, dynamic>,
        ),
      );
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  Future<ApiResult<TrackingEventModel>> checkIn(
    int bookingId, {
    required double latitude,
    required double longitude,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingCheckin(bookingId),
        data: {'latitude': latitude, 'longitude': longitude},
      );
      return ApiSuccess(
        TrackingEventModel.fromJson(
          response.data!['data'] as Map<String, dynamic>,
        ),
      );
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
