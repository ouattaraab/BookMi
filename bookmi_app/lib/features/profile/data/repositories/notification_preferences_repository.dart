import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

class NotificationPreferences {
  const NotificationPreferences({
    required this.newMessage,
    required this.bookingUpdates,
    required this.newReview,
    required this.followUpdate,
    required this.adminBroadcast,
  });

  final bool newMessage;
  final bool bookingUpdates;
  final bool newReview;
  final bool followUpdate;
  final bool adminBroadcast;

  factory NotificationPreferences.fromJson(Map<String, dynamic> json) {
    return NotificationPreferences(
      newMessage: (json['new_message'] as bool?) ?? true,
      bookingUpdates: (json['booking_updates'] as bool?) ?? true,
      newReview: (json['new_review'] as bool?) ?? true,
      followUpdate: (json['follow_update'] as bool?) ?? true,
      adminBroadcast: (json['admin_broadcast'] as bool?) ?? true,
    );
  }

  NotificationPreferences copyWith({
    bool? newMessage,
    bool? bookingUpdates,
    bool? newReview,
    bool? followUpdate,
    bool? adminBroadcast,
  }) {
    return NotificationPreferences(
      newMessage: newMessage ?? this.newMessage,
      bookingUpdates: bookingUpdates ?? this.bookingUpdates,
      newReview: newReview ?? this.newReview,
      followUpdate: followUpdate ?? this.followUpdate,
      adminBroadcast: adminBroadcast ?? this.adminBroadcast,
    );
  }

  Map<String, dynamic> toJson() => {
    'new_message': newMessage,
    'booking_updates': bookingUpdates,
    'new_review': newReview,
    'follow_update': followUpdate,
    'admin_broadcast': adminBroadcast,
  };
}

class NotificationPreferencesRepository {
  NotificationPreferencesRepository({required ApiClient apiClient})
    : _dio = apiClient.dio;

  final Dio _dio;

  Future<ApiResult<NotificationPreferences>> getPreferences() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meNotificationPreferences,
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(NotificationPreferences.fromJson(data));
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<NotificationPreferences>> updatePreferences(
    Map<String, bool> updates,
  ) async {
    try {
      final response = await _dio.put<Map<String, dynamic>>(
        ApiEndpoints.meNotificationPreferences,
        data: updates,
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(NotificationPreferences.fromJson(data));
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
