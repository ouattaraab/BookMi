import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

class PendingInvitation {
  const PendingInvitation({
    required this.id,
    required this.managerEmail,
    required this.status,
    required this.invitedAt,
    this.managerComment,
  });

  final int id;
  final String managerEmail;
  final String status;
  final DateTime invitedAt;
  final String? managerComment;

  factory PendingInvitation.fromJson(Map<String, dynamic> json) {
    return PendingInvitation(
      id: json['id'] as int,
      managerEmail: (json['manager_email'] as String?) ?? '',
      status: (json['status'] as String?) ?? 'pending',
      invitedAt:
          DateTime.tryParse(json['invited_at'] as String? ?? '') ??
          DateTime.now(),
      managerComment: json['manager_comment'] as String?,
    );
  }
}

class TalentManagerInvitation {
  const TalentManagerInvitation({
    required this.id,
    required this.managerEmail,
    required this.status,
    required this.invitedAt,
    this.respondedAt,
    this.managerComment,
    this.managerFirstName,
    this.managerLastName,
    this.managerId,
  });

  final int id;
  final String managerEmail;
  final String status; // pending | accepted | rejected
  final DateTime invitedAt;
  final DateTime? respondedAt;
  final String? managerComment;
  final String? managerFirstName;
  final String? managerLastName;
  final int? managerId;

  String get displayName {
    final name = '${managerFirstName ?? ''} ${managerLastName ?? ''}'.trim();
    return name.isNotEmpty ? name : managerEmail;
  }

  factory TalentManagerInvitation.fromJson(Map<String, dynamic> json) {
    final mgr = json['manager'] as Map<String, dynamic>?;
    return TalentManagerInvitation(
      id: json['id'] as int,
      managerEmail: (json['manager_email'] as String?) ?? '',
      status: (json['status'] as String?) ?? 'pending',
      invitedAt:
          DateTime.tryParse(json['invited_at'] as String? ?? '') ??
          DateTime.now(),
      respondedAt: json['responded_at'] != null
          ? DateTime.tryParse(json['responded_at'] as String)
          : null,
      managerComment: json['manager_comment'] as String?,
      managerFirstName: mgr?['first_name'] as String?,
      managerLastName: mgr?['last_name'] as String?,
      managerId: mgr?['id'] as int?,
    );
  }
}

class ManagerInfo {
  const ManagerInfo({
    required this.id,
    required this.name,
    required this.email,
  });

  final int id;
  final String name;
  final String email;

  factory ManagerInfo.fromJson(Map<String, dynamic> json) {
    return ManagerInfo(
      id: json['id'] as int,
      name: (json['name'] as String?) ?? '',
      email: (json['email'] as String?) ?? '',
    );
  }
}

class ManagerAssignmentRepository {
  ManagerAssignmentRepository({required ApiClient apiClient})
    : _dio = apiClient.dio;

  final Dio _dio;

  Future<ApiResult<List<TalentManagerInvitation>>>
  getTalentInvitations() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meTalentManagerInvitations,
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      final invitations = (data['invitations'] as List<dynamic>? ?? [])
          .map(
            (e) => TalentManagerInvitation.fromJson(e as Map<String, dynamic>),
          )
          .toList();
      return ApiSuccess(invitations);
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<List<ManagerInfo>>> getManagers() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meTalentProfile,
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      final managers = (data['managers'] as List<dynamic>? ?? [])
          .map((e) => ManagerInfo.fromJson(e as Map<String, dynamic>))
          .toList();
      return ApiSuccess(managers);
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<void>> assignManager(String email) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.meTalentManagers,
        data: {'manager_email': email},
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

  Future<ApiResult<void>> removeManager(String email) async {
    try {
      await _dio.delete<void>(
        ApiEndpoints.meTalentManagers,
        data: {'manager_email': email},
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
