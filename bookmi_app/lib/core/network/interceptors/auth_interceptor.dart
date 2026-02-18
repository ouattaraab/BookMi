import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:dio/dio.dart';

class AuthInterceptor extends Interceptor {
  AuthInterceptor({
    required SecureStorage secureStorage,
    this.onSessionExpired,
  }) : _secureStorage = secureStorage;

  final SecureStorage _secureStorage;

  /// Called when a 401 response is received, allowing the app
  /// to dispatch an AuthSessionExpired event to the AuthBloc.
  void Function()? onSessionExpired;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await _secureStorage.getToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    if (err.response?.statusCode == 401) {
      // Token expired or invalid — clear storage
      await _secureStorage.deleteToken();
      onSessionExpired?.call();
    }
    handler.next(err);
  }
}
