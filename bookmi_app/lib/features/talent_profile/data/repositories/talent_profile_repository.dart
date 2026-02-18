import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/storage/local_storage.dart';
import 'package:dio/dio.dart';

class TalentProfileResponse {
  const TalentProfileResponse({
    required this.profile,
    required this.similarTalents,
  });

  final Map<String, dynamic> profile;
  final List<Map<String, dynamic>> similarTalents;
}

class TalentProfileRepository {
  TalentProfileRepository({
    required ApiClient apiClient,
    required LocalStorage localStorage,
  }) : _dio = apiClient.dio,
       _localStorage = localStorage;

  TalentProfileRepository.forTesting({
    required Dio dio,
    required LocalStorage localStorage,
  }) : _dio = dio,
       _localStorage = localStorage;

  final Dio _dio;
  final LocalStorage _localStorage;

  static String _cacheKey(String slug) => 'talent_profile_$slug';

  Future<ApiResult<TalentProfileResponse>> getTalentBySlug(
    String slug,
  ) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.talentDetail(slug),
      );

      final data = response.data;
      if (data == null) {
        return const ApiFailure(
          code: 'EMPTY_RESPONSE',
          message: 'Réponse vide du serveur',
        );
      }
      final talentData = data['data'] as Map<String, dynamic>;
      final attributes = talentData['attributes'] as Map<String, dynamic>;
      final meta = data['meta'] as Map<String, dynamic>?;
      final similarTalents =
          (meta?['similar_talents'] as List<dynamic>?)
              ?.cast<Map<String, dynamic>>() ??
          [];

      final result = TalentProfileResponse(
        profile: attributes,
        similarTalents: similarTalents,
      );

      await _localStorage.put(_cacheKey(slug), data);

      return ApiSuccess(result);
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout) {
        final cached = _localStorage.get<Map<dynamic, dynamic>>(
          _cacheKey(slug),
        );
        if (cached != null) {
          final data = Map<String, dynamic>.from(cached);
          final talentData = Map<String, dynamic>.from(data['data'] as Map);
          final attributes = Map<String, dynamic>.from(
            talentData['attributes'] as Map,
          );
          final meta = data['meta'] != null
              ? Map<String, dynamic>.from(data['meta'] as Map)
              : null;
          final similarRaw = meta?['similar_talents'] as List<dynamic>?;
          final similarTalents =
              similarRaw
                  ?.map((e) => Map<String, dynamic>.from(e as Map))
                  .toList() ??
              [];

          return ApiSuccess(
            TalentProfileResponse(
              profile: attributes,
              similarTalents: similarTalents,
            ),
          );
        }
      }

      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;

      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }
}
