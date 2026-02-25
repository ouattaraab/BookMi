import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/notifications/data/models/push_notification_model.dart';
import 'package:dio/dio.dart';

class NotificationRepository {
  const NotificationRepository({required ApiClient apiClient})
    : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResult<List<PushNotificationModel>>> getNotifications({
    int page = 1,
  }) async {
    try {
      final response = await _apiClient.dio.get<Map<String, dynamic>>(
        ApiEndpoints.notifications,
        queryParameters: {'page': page},
      );
      final items = (response.data?['data'] as List<dynamic>? ?? [])
          .map((e) => PushNotificationModel.fromJson(e as Map<String, dynamic>))
          .toList();
      return ApiSuccess(items);
    } on DioException catch (e) {
      return ApiFailure(
        code: 'NETWORK_ERROR',
        message:
            e.response?.data?['message'] as String? ??
            e.message ??
            'Erreur réseau',
      );
    }
  }

  Future<int> getUnreadCount() async {
    try {
      final response = await _apiClient.dio.get<Map<String, dynamic>>(
        ApiEndpoints.notifications,
      );
      final items = (response.data?['data'] as List<dynamic>? ?? []);
      return items
          .where((e) => (e as Map)['attributes']?['read_at'] == null)
          .length;
    } catch (_) {
      return 0;
    }
  }

  Future<ApiResult<void>> markRead(int notificationId) async {
    try {
      await _apiClient.dio.post<void>(
        ApiEndpoints.notificationRead(notificationId),
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return ApiFailure(
        code: 'NETWORK_ERROR',
        message:
            e.response?.data?['message'] as String? ?? e.message ?? 'Erreur',
      );
    }
  }

  Future<ApiResult<void>> markAllRead() async {
    try {
      await _apiClient.dio.post<void>(ApiEndpoints.notificationsReadAll);
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return ApiFailure(
        code: 'NETWORK_ERROR',
        message:
            e.response?.data?['message'] as String? ?? e.message ?? 'Erreur',
      );
    }
  }

  Future<ApiResult<void>> updateFcmToken(String token) async {
    try {
      await _apiClient.dio.put<void>(
        ApiEndpoints.meUpdateFcmToken,
        data: {'fcm_token': token},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return ApiFailure(
        code: 'NETWORK_ERROR',
        message:
            e.response?.data?['message'] as String? ?? e.message ?? 'Erreur',
      );
    }
  }
}
