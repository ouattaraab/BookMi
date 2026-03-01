import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

@immutable
class CalendarDayModel {
  const CalendarDayModel({
    required this.date,
    required this.status,
    this.slotId,
  });

  final String date;
  final String status; // 'available' | 'blocked' | 'rest' | 'confirmed'
  final int? slotId;

  factory CalendarDayModel.fromJson(Map<String, dynamic> json) {
    return CalendarDayModel(
      date: json['date'] as String,
      status: json['status'] as String,
      slotId: json['slot_id'] as int?,
    );
  }
}

class CalendarRepository {
  CalendarRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  /// Test-only constructor.
  CalendarRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  /// Returns the authenticated talent's profile ID via GET /talent_profiles/me.
  Future<ApiResult<int>> getMyTalentProfileId() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meTalentProfile,
      );
      final id = response.data!['data']['id'] as int;
      return ApiSuccess(id);
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  /// Returns the calendar slots for a given month (YYYY-MM).
  Future<ApiResult<List<CalendarDayModel>>> getCalendar(
    int talentProfileId,
    String month,
  ) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.talentCalendar(talentProfileId.toString()),
        queryParameters: {'month': month},
      );
      final data = response.data!['data'] as List<dynamic>;
      return ApiSuccess(
        data
            .cast<Map<String, dynamic>>()
            .map(CalendarDayModel.fromJson)
            .toList(),
      );
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  /// Creates a calendar slot (POST /calendar_slots).
  Future<ApiResult<CalendarDayModel>> createSlot(
    String date,
    String status,
  ) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.calendarSlots,
        data: {'date': date, 'status': status},
      );
      return ApiSuccess(
        CalendarDayModel.fromJson(
          response.data!['data'] as Map<String, dynamic>,
        ),
      );
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  /// Updates a calendar slot (PUT /calendar_slots/{id}).
  Future<ApiResult<CalendarDayModel>> updateSlot(
    int slotId,
    String status,
  ) async {
    try {
      final response = await _dio.put<Map<String, dynamic>>(
        ApiEndpoints.calendarSlot(slotId),
        data: {'status': status},
      );
      return ApiSuccess(
        CalendarDayModel.fromJson(
          response.data!['data'] as Map<String, dynamic>,
        ),
      );
    } on DioException catch (e) {
      return _mapError(e);
    }
  }

  /// Deletes a calendar slot (DELETE /calendar_slots/{id}).
  Future<ApiResult<void>> deleteSlot(int slotId) async {
    try {
      await _dio.delete<void>(ApiEndpoints.calendarSlot(slotId));
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
