import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:dio/dio.dart';

class ProfileStats {
  const ProfileStats({
    required this.bookingCount,
    required this.revenusMoisCourant,
    required this.nombrePrestations,
    required this.mensuels,
    required this.favoriteCount,
    required this.isTalent,
  });

  final int bookingCount;
  final int revenusMoisCourant;
  final int nombrePrestations;
  final List<Map<String, dynamic>> mensuels;
  final int favoriteCount;
  final bool isTalent;
}

class ProfileRepository {
  ProfileRepository({required ApiClient apiClient})
    : _dio = apiClient.dio;

  ProfileRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  Future<ApiResult<ProfileStats>> getStats({required bool isTalent}) async {
    try {
      // Always fetch bookings count
      final bookingsRes = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.bookingRequests,
        queryParameters: {'per_page': 1},
      );
      final bookingCount =
          (bookingsRes.data?['meta']?['total'] as int?) ?? 0;

      // Fetch favorites count
      final favRes = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.myFavorites,
        queryParameters: {'per_page': 1},
      );
      final favoriteCount =
          (favRes.data?['meta']?['total'] as int?) ??
          ((favRes.data?['data'] as List?)?.length ?? 0);

      var revenusMoisCourant = 0;
      var nombrePrestations = 0;
      var mensuels = <Map<String, dynamic>>[];

      if (isTalent) {
        final finRes = await _dio.get<Map<String, dynamic>>(
          ApiEndpoints.meFinancialDashboard,
        );
        final d = finRes.data?['data'] as Map<String, dynamic>? ?? {};
        revenusMoisCourant = (d['revenus_mois_courant'] as int?) ?? 0;
        nombrePrestations = (d['nombre_prestations'] as int?) ?? 0;
        mensuels = ((d['mensuels'] as List?)
                ?.cast<Map<String, dynamic>>()) ??
            [];
      }

      return ApiSuccess(
        ProfileStats(
          bookingCount: bookingCount,
          revenusMoisCourant: revenusMoisCourant,
          nombrePrestations: nombrePrestations,
          mensuels: mensuels,
          favoriteCount: favoriteCount,
          isTalent: isTalent,
        ),
      );
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

  Future<ApiResult<Map<String, dynamic>>> getFinancialDashboard() async {
    try {
      final res = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meFinancialDashboard,
      );
      return ApiSuccess(
        res.data?['data'] as Map<String, dynamic>? ?? {},
      );
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

  Future<ApiResult<List<Map<String, dynamic>>>> getFavorites() async {
    try {
      final res = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.myFavorites,
        queryParameters: {'per_page': 50},
      );
      final items = (res.data?['data'] as List?)
              ?.cast<Map<String, dynamic>>() ??
          [];
      return ApiSuccess(items);
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
