import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

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
