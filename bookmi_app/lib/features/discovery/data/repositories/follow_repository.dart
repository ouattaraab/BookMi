import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

class FollowRepository {
  FollowRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  /// Test-only constructor.
  FollowRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  Future<ApiResult<({bool isFollowing, int followersCount})>> follow(
    int talentProfileId,
  ) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.talentFollow(talentProfileId),
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess((
        isFollowing: data['is_following'] as bool,
        followersCount: (data['followers_count'] as num).toInt(),
      ));
    } on DioException catch (e) {
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<({bool isFollowing, int followersCount})>> unfollow(
    int talentProfileId,
  ) async {
    try {
      final response = await _dio.delete<Map<String, dynamic>>(
        ApiEndpoints.talentFollow(talentProfileId),
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      return ApiSuccess((
        isFollowing: data['is_following'] as bool,
        followersCount: (data['followers_count'] as num).toInt(),
      ));
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
