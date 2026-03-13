import 'dart:io';

import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/meet_and_greet/data/models/experience_attendee_model.dart';
import 'package:bookmi_app/features/meet_and_greet/data/models/experience_booking_list_item.dart';
import 'package:bookmi_app/features/meet_and_greet/data/models/experience_model.dart';
import 'package:dio/dio.dart';

class ExperienceListResponse {
  const ExperienceListResponse({
    required this.experiences,
    required this.currentPage,
    required this.lastPage,
  });

  final List<ExperienceModel> experiences;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}

class ExperienceRepository {
  ExperienceRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  /// Test-only constructor that accepts a raw Dio instance.
  ExperienceRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  /// Fetch a paginated list of published experiences.
  Future<ApiResult<ExperienceListResponse>> getExperiences({
    int page = 1,
  }) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.experiences,
        queryParameters: {'page': page},
      );

      final data = response.data!;
      final items = ((data['data'] as List<dynamic>?) ?? [])
          .cast<Map<String, dynamic>>()
          .map(ExperienceModel.fromJson)
          .toList();
      final meta = (data['meta'] as Map<String, dynamic>?) ?? {};

      return ApiSuccess(
        ExperienceListResponse(
          experiences: items,
          currentPage: meta['current_page'] as int? ?? 1,
          lastPage: meta['last_page'] as int? ?? 1,
        ),
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /// Fetch the detail of a single experience by [id].
  Future<ApiResult<ExperienceModel>> getExperienceDetail(int id) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.experienceDetail(id),
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(ExperienceModel.fromJson(data));
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /// Book [seatsCount] seats for experience [id].
  /// Returns the updated [ExperienceModel] on success (refreshed from API).
  Future<ApiResult<ExperienceModel>> bookExperience(
    int id,
    int seatsCount,
  ) async {
    try {
      await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookExperience(id),
        data: {'seats_count': seatsCount},
      );
      // Booking succeeded — fetch fresh detail so my_booking is populated
      return getExperienceDetail(id);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /// Cancel the current user's booking for experience [id].
  Future<ApiResult<void>> cancelBooking(int id) async {
    try {
      await _dio.delete<void>(ApiEndpoints.cancelExperienceBooking(id));
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // ── Talent management endpoints ──────────────────────────────────

  /// Fetch the list of experiences owned by the authenticated talent.
  Future<ApiResult<List<ExperienceModel>>> getMyExperiences() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.myExperiences,
      );
      final data = response.data!;
      final items = ((data['data'] as List<dynamic>?) ?? [])
          .cast<Map<String, dynamic>>()
          .map(ExperienceModel.fromJson)
          .toList();
      return ApiSuccess(items);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /// Create a new experience for the authenticated talent.
  Future<ApiResult<ExperienceModel>> createExperience({
    required String title,
    String? description,
    required String eventDate,
    String? venueAddress,
    required int totalPrice,
    required int maxSeats,
  }) async {
    try {
      final body = <String, dynamic>{
        'title': title,
        'event_date': eventDate,
        'total_price': totalPrice,
        'max_seats': maxSeats,
        if (description != null && description.isNotEmpty)
          'description': description,
        if (venueAddress != null && venueAddress.isNotEmpty)
          'venue_address': venueAddress,
      };
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.createExperience,
        data: body,
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(ExperienceModel.fromJson(data));
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /// Fetch the list of attendees for the talent-owned experience [id].
  Future<ApiResult<List<ExperienceAttendee>>> getAttendees(int id) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.experienceAttendees(id),
      );
      final data = response.data!;
      final items = ((data['data'] as List<dynamic>?) ?? [])
          .cast<Map<String, dynamic>>()
          .map(ExperienceAttendee.fromJson)
          .toList();
      return ApiSuccess(items);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /// Upload a cover photo or video for experience [id].
  Future<ApiResult<String>> uploadCover(int id, File file) async {
    try {
      final formData = FormData.fromMap({
        'cover': await MultipartFile.fromFile(file.path),
      });
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.uploadExperienceCover(id),
        data: formData,
      );
      final coverUrl = response.data!['data']?['cover_image'] as String? ?? '';
      return ApiSuccess(coverUrl);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /// Fetch the list of M&G bookings for the authenticated client.
  Future<ApiResult<List<ExperienceBookingListItem>>>
  getMyExperienceBookings() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.myExperienceBookings,
      );
      final data = response.data!;
      final items = ((data['data'] as List<dynamic>?) ?? [])
          .cast<Map<String, dynamic>>()
          .map(ExperienceBookingListItem.fromJson)
          .toList();
      return ApiSuccess(items);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  ApiFailure<T> _handleError<T>(DioException e) {
    final errorData = e.response?.data as Map<String, dynamic>?;
    final error = errorData?['error'] as Map<String, dynamic>?;
    return ApiFailure<T>(
      code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
      message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
    );
  }
}
