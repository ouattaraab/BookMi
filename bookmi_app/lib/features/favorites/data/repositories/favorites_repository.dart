import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/favorites/data/local/favorites_local_source.dart';
import 'package:dio/dio.dart';

class FavoritesRepository {
  FavoritesRepository({
    required ApiClient apiClient,
    required FavoritesLocalSource localSource,
  }) : _dio = apiClient.dio,
       _localSource = localSource;

  /// Test-only constructor that accepts a Dio instance directly.
  FavoritesRepository.forTesting({
    required Dio dio,
    required FavoritesLocalSource localSource,
  }) : _dio = dio,
       _localSource = localSource;

  final Dio _dio;
  final FavoritesLocalSource _localSource;

  Future<ApiResult<List<Map<String, dynamic>>>> getFavorites({
    String? cursor,
    int perPage = 20,
  }) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.myFavorites,
        queryParameters: {
          'per_page': perPage,
          // ignore: use_null_aware_elements, conflicts with invalid_null_aware_operator
          if (cursor != null) 'cursor': cursor,
        },
      );

      final data = response.data!;
      final items = (data['data'] as List<dynamic>)
          .cast<Map<String, dynamic>>();

      // Cache favorite IDs locally
      final ids = <int>[];
      for (final item in items) {
        final talent =
            (item['attributes'] as Map<String, dynamic>)['talent']
                as Map<String, dynamic>;
        ids.add(talent['id'] as int);
      }
      await _localSource.cacheFavoriteIds(ids, append: cursor != null);

      return ApiSuccess(items);
    } on DioException catch (e) {
      // Return cached data on network error
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout) {
        final cached = _localSource.getCachedFavoriteIds();
        if (cached != null) {
          return ApiSuccess(
            cached.map((id) => <String, dynamic>{'talent_id': id}).toList(),
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

  Future<ApiResult<void>> addFavorite(int talentId) async {
    try {
      await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.talentFavorite(talentId),
      );
      await _localSource.addFavoriteId(talentId);
      return const ApiSuccess(null);
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout) {
        await _localSource.addFavoriteId(talentId);
        await _localSource.queuePendingAction({
          'type': 'add',
          'talentId': talentId,
        });
        return const ApiSuccess(null);
      }

      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;

      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<void>> removeFavorite(int talentId) async {
    try {
      await _dio.delete<void>(
        ApiEndpoints.talentFavorite(talentId),
      );
      await _localSource.removeFavoriteId(talentId);
      return const ApiSuccess(null);
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout) {
        await _localSource.removeFavoriteId(talentId);
        await _localSource.queuePendingAction({
          'type': 'remove',
          'talentId': talentId,
        });
        return const ApiSuccess(null);
      }

      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;

      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<bool>> isFavorite(int talentId) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.talentFavorite(talentId),
      );
      final data = response.data!['data'] as Map<String, dynamic>;
      final isFav = data['is_favorite'] as bool;
      return ApiSuccess(isFav);
    } on DioException catch (e) {
      // Fallback to local cache
      final cached = _localSource.isFavorite(talentId);
      if (cached != null) {
        return ApiSuccess(cached);
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
