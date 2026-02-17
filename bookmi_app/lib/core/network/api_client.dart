import 'package:bookmi_app/core/network/interceptors/auth_interceptor.dart';
import 'package:bookmi_app/core/network/interceptors/logging_interceptor.dart';
import 'package:bookmi_app/core/network/interceptors/retry_interceptor.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

class ApiClient {
  /// Initialize and access the singleton ApiClient.
  ///
  /// First call creates the instance. Subsequent calls return it.
  factory ApiClient({
    required String baseUrl,
    required SecureStorage secureStorage,
  }) {
    return _instance ??= ApiClient._(
      baseUrl: baseUrl,
      secureStorage: secureStorage,
    );
  }

  ApiClient._({
    required String baseUrl,
    required SecureStorage secureStorage,
  }) : _dio = Dio(
         BaseOptions(
           baseUrl: baseUrl,
           connectTimeout: const Duration(seconds: 15),
           receiveTimeout: const Duration(seconds: 30),
           headers: {
             'Accept': 'application/json',
             'Content-Type': 'application/json',
           },
         ),
       ) {
    _dio.interceptors.addAll([
      AuthInterceptor(secureStorage: secureStorage),
      RetryInterceptor(dio: _dio),
      LoggingInterceptor(),
    ]);
  }

  static ApiClient? _instance;

  /// Access the singleton instance. Throws if not yet created.
  static ApiClient get instance {
    if (_instance == null) {
      throw StateError(
        'ApiClient not initialized. Call ApiClient() first.',
      );
    }
    return _instance!;
  }

  @visibleForTesting
  static void resetForTesting() {
    _instance = null;
  }

  final Dio _dio;

  Dio get dio => _dio;
}
