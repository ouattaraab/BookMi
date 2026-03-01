import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:dio/dio.dart';

class ManagedTalent {
  const ManagedTalent({
    required this.id,
    required this.stageName,
    required this.totalBookings,
    required this.averageRating,
    this.categoryName,
    this.city,
  });

  final int id;
  final String stageName;
  final int totalBookings;
  final double averageRating;
  final String? categoryName;
  final String? city;

  factory ManagedTalent.fromJson(Map<String, dynamic> json) {
    final category = json['category'] as Map<String, dynamic>?;
    return ManagedTalent(
      id: json['id'] as int,
      stageName: (json['stage_name'] as String?) ?? '',
      totalBookings: (json['total_bookings'] as int?) ?? 0,
      averageRating: ((json['average_rating'] as num?) ?? 0).toDouble(),
      categoryName: category?['name'] as String?,
      city: json['city'] as String?,
    );
  }
}

class ManagerRepository {
  ManagerRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  final Dio _dio;

  Future<ApiResult<List<ManagedTalent>>> getMyTalents() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/manager/talents');
      final list = (response.data!['data']['talents'] as List<dynamic>)
          .map((e) => ManagedTalent.fromJson(e as Map<String, dynamic>))
          .toList();
      return ApiSuccess(list);
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<List<BookingModel>>> getTalentBookings(
    int talentProfileId,
  ) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/manager/talents/$talentProfileId/bookings',
      );
      final items = (response.data!['data'] as List<dynamic>)
          .map((e) => BookingModel.fromJson(e as Map<String, dynamic>))
          .toList();
      return ApiSuccess(items);
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<void>> acceptBooking(
    int talentProfileId,
    int bookingId,
  ) async {
    try {
      await _dio.post<void>(
        '/manager/talents/$talentProfileId/bookings/$bookingId/accept',
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<void>> rejectBooking(
    int talentProfileId,
    int bookingId,
    String reason,
  ) async {
    try {
      await _dio.post<void>(
        '/manager/talents/$talentProfileId/bookings/$bookingId/reject',
        data: {'reason': reason},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }
}
