import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:dio/dio.dart';

class ManagerInvitation {
  const ManagerInvitation({
    required this.id,
    required this.talentProfileId,
    required this.talentName,
    required this.status,
    required this.invitedAt,
    this.managerComment,
    this.talentAvatarUrl,
    this.talentCategoryName,
  });

  final int id;
  final int talentProfileId;
  final String talentName;
  final String status;
  final DateTime invitedAt;
  final String? managerComment;
  final String? talentAvatarUrl;
  final String? talentCategoryName;

  factory ManagerInvitation.fromJson(Map<String, dynamic> json) {
    final profile = json['talent_profile'] as Map<String, dynamic>?;
    final user = profile?['user'] as Map<String, dynamic>?;
    final stageName = profile?['stage_name'] as String?;
    final firstName = user?['first_name'] as String? ?? '';
    final lastName = user?['last_name'] as String? ?? '';
    return ManagerInvitation(
      id: json['id'] as int,
      talentProfileId: (json['talent_profile_id'] as int?) ?? 0,
      talentName: stageName ?? '$firstName $lastName'.trim(),
      status: (json['status'] as String?) ?? 'pending',
      invitedAt:
          DateTime.tryParse(json['invited_at'] as String? ?? '') ??
          DateTime.now(),
      managerComment: json['manager_comment'] as String?,
      talentAvatarUrl: user?['avatar_url'] as String?,
      talentCategoryName:
          (profile?['category'] as Map<String, dynamic>?)?['name'] as String?,
    );
  }
}

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

  Future<ApiResult<List<ManagerInvitation>>> getMyInvitations() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/manager/invitations',
      );
      final list = (response.data!['data']['invitations'] as List<dynamic>)
          .map((e) => ManagerInvitation.fromJson(e as Map<String, dynamic>))
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

  Future<ApiResult<void>> acceptInvitation(
    int invitationId, {
    String? comment,
  }) async {
    try {
      await _dio.post<void>(
        '/manager/invitations/$invitationId/accept',
        data: {'comment': comment},
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

  Future<ApiResult<void>> rejectInvitation(
    int invitationId,
    String comment,
  ) async {
    try {
      await _dio.post<void>(
        '/manager/invitations/$invitationId/reject',
        data: {'comment': comment},
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

  Future<ApiResult<void>> inviteManager(String email) async {
    try {
      await _dio.post<void>('/manager/invite', data: {'email': email});
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

  Future<ApiResult<void>> cancelInvitation(int invitationId) async {
    try {
      await _dio.delete<void>('/manager/invitations/$invitationId');
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

  Future<ApiResult<List<Map<String, dynamic>>>>
  getManagerConversations() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/manager/conversations',
      );
      final rawData = response.data!['data'];
      final items = (rawData is List
              ? rawData
              : ((rawData as Map<String, dynamic>)['data'] as List<dynamic>? ??
                  []))
          .cast<Map<String, dynamic>>();
      return ApiSuccess(items.toList());
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message:
            (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<void>> sendManagerMessage(
    int conversationId,
    String content,
  ) async {
    try {
      await _dio.post<void>(
        '/manager/conversations/$conversationId/messages',
        data: {'content': content},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message:
            (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }
}
