import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/features/app_update/data/models/app_version_model.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

class AppVersionRepository {
  AppVersionRepository({required ApiClient apiClient})
      : _dio = apiClient.dio;

  @visibleForTesting
  AppVersionRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  Future<AppVersionModel> check() async {
    final response = await _dio.get<Map<String, dynamic>>(
      ApiEndpoints.appVersion,
    );
    final data = response.data ?? {};
    return AppVersionModel.fromJson(data);
  }
}
