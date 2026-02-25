import 'dart:io';

import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/messaging/data/models/conversation_model.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';
import 'package:bookmi_app/features/notifications/data/models/push_notification_model.dart';
import 'package:dio/dio.dart';

class MessagingRepository {
  MessagingRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  /// Test-only constructor.
  MessagingRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  /// Returns the list of conversations for the authenticated user.
  Future<ApiResult<List<ConversationModel>>> getConversations() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.conversations,
      );
      final items = (response.data!['data'] as List<dynamic>)
          .cast<Map<String, dynamic>>();
      return ApiSuccess(items.map(ConversationModel.fromJson).toList());
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Returns the latest admin broadcast notifications.
  Future<ApiResult<List<PushNotificationModel>>> getAdminBroadcasts() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meBroadcasts,
      );
      final items = (response.data!['data'] as List<dynamic>)
          .cast<Map<String, dynamic>>();
      return ApiSuccess(
        items.map(PushNotificationModel.fromJson).toList(),
      );
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Gets or creates a conversation. Optionally sends [message] as the first
  /// message. When [message] is omitted the backend simply returns the
  /// conversation so the caller can navigate to the chat screen.
  Future<ApiResult<Map<String, dynamic>>> startConversation({
    required int talentProfileId,
    String? message,
    int? bookingRequestId,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.conversations,
        data: {
          'talent_profile_id': talentProfileId,
          if (message != null) 'message': message,
          if (bookingRequestId != null) 'booking_request_id': bookingRequestId,
        },
      );
      return ApiSuccess(response.data ?? {});
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Returns paginated messages for a conversation.
  Future<ApiResult<List<MessageModel>>> getMessages(
    int conversationId, {
    int page = 1,
  }) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.conversationMessages(conversationId),
        queryParameters: {'page': page},
      );
      final items = (response.data!['data'] as List<dynamic>)
          .cast<Map<String, dynamic>>();
      return ApiSuccess(items.map(MessageModel.fromJson).toList());
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Sends a text message in an existing conversation.
  Future<ApiResult<MessageModel>> sendMessage(
    int conversationId, {
    required String content,
    String type = 'text',
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.conversationMessages(conversationId),
        data: {'content': content, 'type': type},
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(MessageModel.fromJson(data));
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Sends a media file (image or video) with an optional caption.
  Future<ApiResult<MessageModel>> sendMediaMessage(
    int conversationId, {
    required File file,
    required String type, // 'image' or 'video'
    String caption = '',
  }) async {
    try {
      final formData = FormData.fromMap({
        'type': type,
        'content': caption,
        'file': await MultipartFile.fromFile(
          file.path,
          filename: file.path.split('/').last,
        ),
      });
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.conversationMessages(conversationId),
        data: formData,
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess(MessageModel.fromJson(data));
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Deletes an entire conversation (soft-delete on backend).
  Future<ApiResult<void>> deleteConversation(int conversationId) async {
    try {
      await _dio.delete<void>(
        ApiEndpoints.conversationDelete(conversationId),
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Deletes a single message (sender only).
  Future<ApiResult<void>> deleteMessage(
    int conversationId,
    int messageId,
  ) async {
    try {
      await _dio.delete<void>(
        ApiEndpoints.messageDelete(conversationId, messageId),
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Marks all messages in the conversation as read.
  Future<ApiResult<int>> markAsRead(int conversationId) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.conversationRead(conversationId),
      );
      final markedRead = (response.data!['data']?['marked_read'] as int?) ?? 0;
      return ApiSuccess(markedRead);
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
