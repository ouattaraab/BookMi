import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/storage/local_storage.dart';
import 'package:dio/dio.dart';

typedef CategoryList = List<Map<String, dynamic>>;

class TalentListResponse {
  const TalentListResponse({
    required this.talents,
    required this.nextCursor,
    required this.hasMore,
    required this.total,
  });

  final List<Map<String, dynamic>> talents;
  final String? nextCursor;
  final bool hasMore;
  final int total;
}

class DiscoveryRepository {
  DiscoveryRepository({
    required ApiClient apiClient,
    required LocalStorage localStorage,
  }) : _dio = apiClient.dio,
       _localStorage = localStorage;

  /// Test-only constructor that accepts a Dio instance directly.
  DiscoveryRepository.forTesting({
    required Dio dio,
    required LocalStorage localStorage,
  }) : _dio = dio,
       _localStorage = localStorage;

  final Dio _dio;
  final LocalStorage _localStorage;

  static const _cacheKey = 'last_talents';

  Future<ApiResult<TalentListResponse>> getTalents({
    String? cursor,
    int perPage = 20,
    Map<String, dynamic>? filters,
    String? query,
    String? eventDate,
  }) async {
    try {
      final queryParameters = <String, dynamic>{
        'per_page': perPage,
        // ignore: use_null_aware_elements, conflicts with invalid_null_aware_operator
        if (cursor != null) 'cursor': cursor,
        if (query != null && query.isNotEmpty) 'q': query,
        if (eventDate != null) 'event_date': eventDate,
        ...?filters,
      };

      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.talents,
        queryParameters: queryParameters,
      );

      final data = response.data!;
      final talents = ((data['data'] as List<dynamic>?) ?? [])
          .cast<Map<String, dynamic>>();
      final meta = (data['meta'] as Map<String, dynamic>?) ?? {};

      final result = TalentListResponse(
        talents: talents,
        nextCursor: meta['next_cursor'] as String?,
        hasMore: meta['has_more'] as bool? ?? false,
        total: meta['total'] as int? ?? talents.length,
      );

      // Cache successful response
      await _localStorage.put(_cacheKey, data);

      return ApiSuccess(result);
    } on DioException catch (e) {
      // Fallback to cache on network error
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout) {
        final cached = _localStorage.get<Map<dynamic, dynamic>>(_cacheKey);
        if (cached != null) {
          final data = Map<String, dynamic>.from(cached);
          final talents = (data['data'] as List<dynamic>)
              .cast<Map<String, dynamic>>();
          final meta = Map<String, dynamic>.from(data['meta'] as Map);

          return ApiSuccess(
            TalentListResponse(
              talents: talents,
              nextCursor: meta['next_cursor'] as String?,
              hasMore: meta['has_more'] as bool? ?? false,
              total: meta['total'] as int? ?? talents.length,
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

  /// Ask to be notified when [talentId] becomes available on [eventDate].
  Future<ApiResult<void>> notifyWhenAvailable({
    required int talentId,
    required String eventDate,
  }) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.talentNotifyAvailability(talentId),
        data: {'event_date': eventDate},
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

  /// Fetch the list of talent categories from the API.
  Future<ApiResult<CategoryList>> getCategories() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.categories,
      );
      final data = response.data!;
      final items = ((data['data'] as List<dynamic>?) ?? [])
          .cast<Map<String, dynamic>>();
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
}
