import 'dart:io';

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
    this.pendingBookingCount = 0,
    this.unreadNotificationCount = 0,
    this.profileViewsToday = 0,
    this.profileViewsWeek = 0,
    this.profileViewsMonth = 0,
    this.profileViewsTotal = 0,
  });

  final int bookingCount;
  final int revenusMoisCourant;
  final int nombrePrestations;
  final List<Map<String, dynamic>> mensuels;
  final int favoriteCount;
  final bool isTalent;
  final int pendingBookingCount;
  final int unreadNotificationCount;
  final int profileViewsToday;
  final int profileViewsWeek;
  final int profileViewsMonth;
  final int profileViewsTotal;
}

class ProfileRepository {
  ProfileRepository({required ApiClient apiClient})
    : _dio = apiClient.dio;

  ProfileRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  Future<ApiResult<ProfileStats>> getStats({required bool isTalent}) async {
    try {
      final statsRes = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meStats,
      );
      final statsData = statsRes.data?['data'] as Map<String, dynamic>? ?? {};
      final bookingCount = (statsData['booking_count'] as int?) ?? 0;
      final favoriteCount = (statsData['favorite_count'] as int?) ?? 0;
      final pendingBookingCount =
          (statsData['pending_booking_count'] as int?) ?? 0;
      final unreadNotificationCount =
          (statsData['unread_notification_count'] as int?) ?? 0;
      final profileViewsToday =
          (statsData['profile_views_today'] as int?) ?? 0;
      final profileViewsWeek =
          (statsData['profile_views_week'] as int?) ?? 0;
      final profileViewsMonth =
          (statsData['profile_views_month'] as int?) ?? 0;
      final profileViewsTotal =
          (statsData['profile_views_total'] as int?) ?? 0;

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
          pendingBookingCount: pendingBookingCount,
          unreadNotificationCount: unreadNotificationCount,
          profileViewsToday: profileViewsToday,
          profileViewsWeek: profileViewsWeek,
          profileViewsMonth: profileViewsMonth,
          profileViewsTotal: profileViewsTotal,
        ),
      );
    } on DioException catch (e) {
      if (e.response?.statusCode == 404) {
        return _getStatsFallback(isTalent: isTalent);
      }
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      return ApiFailure(
        code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
        message:
            (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
      );
    }
  }

  Future<ApiResult<ProfileStats>> _getStatsFallback({
    required bool isTalent,
  }) async {
    try {
      final bookingsRes = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.bookingRequests,
        queryParameters: {'per_page': 200},
      );
      final bookingCount =
          (bookingsRes.data?['data'] as List?)?.length ?? 0;

      final favRes = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.myFavorites,
        queryParameters: {'per_page': 200},
      );
      final favoriteCount = (favRes.data?['data'] as List?)?.length ?? 0;

      var revenusMoisCourant = 0;
      var nombrePrestations = 0;
      var mensuels = <Map<String, dynamic>>[];

      if (isTalent) {
        try {
          final finRes = await _dio.get<Map<String, dynamic>>(
            ApiEndpoints.meFinancialDashboard,
          );
          final d = finRes.data?['data'] as Map<String, dynamic>? ?? {};
          revenusMoisCourant = (d['revenus_mois_courant'] as int?) ?? 0;
          nombrePrestations = (d['nombre_prestations'] as int?) ?? 0;
          mensuels = ((d['mensuels'] as List?)
                  ?.cast<Map<String, dynamic>>()) ??
              [];
        } on DioException {
          // Financial dashboard unavailable — keep defaults.
        }
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

  Future<ApiResult<Map<String, dynamic>>> updateProfile({
    String? firstName,
    String? lastName,
    File? avatarFile,
  }) async {
    try {
      final Map<String, dynamic> fields = {};
      if (firstName != null) fields['first_name'] = firstName;
      if (lastName != null) fields['last_name'] = lastName;

      late Response<Map<String, dynamic>> res;
      if (avatarFile != null) {
        final formData = FormData.fromMap({
          ...fields,
          'avatar': await MultipartFile.fromFile(
            avatarFile.path,
            filename: avatarFile.path.split('/').last,
          ),
        });
        res = await _dio.patch<Map<String, dynamic>>(
          ApiEndpoints.me,
          data: formData,
        );
      } else {
        res = await _dio.patch<Map<String, dynamic>>(
          ApiEndpoints.me,
          data: fields,
        );
      }
      return ApiSuccess(res.data?['data'] as Map<String, dynamic>? ?? {});
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

  Future<ApiResult<void>> deleteAvatar() async {
    try {
      await _dio.delete<Map<String, dynamic>>(ApiEndpoints.meAvatar);
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

  Future<ApiResult<Map<String, dynamic>>> getIdentityStatus() async {
    try {
      final res = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meIdentityStatus,
      );
      return ApiSuccess(res.data?['data'] as Map<String, dynamic>? ?? {});
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

  Future<ApiResult<Map<String, dynamic>>> submitIdentityDocument({
    required String documentType,
    required String documentNumber,
    required File documentFile,
  }) async {
    try {
      final formData = FormData.fromMap({
        'document_type': documentType,
        'document_number': documentNumber,
        'document': await MultipartFile.fromFile(
          documentFile.path,
          filename: documentFile.path.split('/').last,
        ),
      });
      final res = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.meIdentityDocument,
        data: formData,
      );
      return ApiSuccess(res.data?['data'] as Map<String, dynamic>? ?? {});
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

  Future<ApiResult<Map<String, dynamic>>> submitSelfie({
    required File selfieFile,
  }) async {
    try {
      final formData = FormData.fromMap({
        'selfie': await MultipartFile.fromFile(
          selfieFile.path,
          filename: selfieFile.path.split('/').last,
        ),
      });
      final res = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.meIdentitySelfie,
        data: formData,
      );
      return ApiSuccess(res.data?['data'] as Map<String, dynamic>? ?? {});
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

  // ─── Earnings ──────────────────────────────────────────────────────────────

  Future<ApiResult<Map<String, dynamic>>> getEarnings({
    int perPage = 20,
    int? page,
  }) async {
    try {
      final res = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.meEarnings,
        queryParameters: {
          'per_page': perPage,
          if (page != null) 'page': page,
        },
      );
      return ApiSuccess(res.data ?? {});
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  // ─── Portfolio ─────────────────────────────────────────────────────────────

  Future<ApiResult<List<Map<String, dynamic>>>> getPortfolio() async {
    try {
      final res = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.mePortfolio,
      );
      final items = (res.data?['data'] as List?)
              ?.cast<Map<String, dynamic>>() ??
          [];
      return ApiSuccess(items);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  Future<ApiResult<Map<String, dynamic>>> addPortfolioItem({
    required File file,
    String? caption,
  }) async {
    try {
      final formData = FormData.fromMap({
        'file': await MultipartFile.fromFile(
          file.path,
          filename: file.path.split('/').last,
        ),
        if (caption != null && caption.isNotEmpty) 'caption': caption,
      });
      final res = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.mePortfolio,
        data: formData,
      );
      return ApiSuccess(res.data?['data'] as Map<String, dynamic>? ?? {});
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  Future<ApiResult<void>> deletePortfolioItem(int itemId) async {
    try {
      await _dio.delete<Map<String, dynamic>>(
        ApiEndpoints.mePortfolioItem(itemId),
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  // ─── Service packages ─────────────────────────────────────────────────────

  Future<ApiResult<List<Map<String, dynamic>>>> getServicePackages() async {
    try {
      final res = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.servicePackages,
      );
      final items = (res.data?['data'] as List?)
              ?.cast<Map<String, dynamic>>() ??
          [];
      return ApiSuccess(items);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  Future<ApiResult<Map<String, dynamic>>> createServicePackage(
    Map<String, dynamic> data,
  ) async {
    try {
      final res = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.servicePackages,
        data: data,
      );
      return ApiSuccess(res.data?['data'] as Map<String, dynamic>? ?? {});
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  Future<ApiResult<Map<String, dynamic>>> updateServicePackage(
    int id,
    Map<String, dynamic> data,
  ) async {
    try {
      final res = await _dio.patch<Map<String, dynamic>>(
        ApiEndpoints.servicePackage(id),
        data: data,
      );
      return ApiSuccess(res.data?['data'] as Map<String, dynamic>? ?? {});
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  Future<ApiResult<void>> deleteServicePackage(int id) async {
    try {
      await _dio.delete<Map<String, dynamic>>(
        ApiEndpoints.servicePackage(id),
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  // ─── Helpers ──────────────────────────────────────────────────────────────

  ApiFailure<T> _mapDioError<T>(DioException e) {
    final errorData = e.response?.data as Map<String, dynamic>?;
    final error = errorData?['error'] as Map<String, dynamic>?;
    return ApiFailure(
      code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
      message:
          (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
    );
  }
}
