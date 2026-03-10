import 'dart:io';

import 'package:bookmi_app/core/network/interceptors/auth_interceptor.dart';
import 'package:bookmi_app/core/network/interceptors/logging_interceptor.dart';
import 'package:bookmi_app/core/network/interceptors/retry_interceptor.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:sentry_dio/sentry_dio.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

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
       ),
       _authInterceptor = AuthInterceptor(secureStorage: secureStorage) {
    _dio.interceptors.addAll([
      _authInterceptor,
      RetryInterceptor(dio: _dio),
      LoggingInterceptor(),
    ]);

    if (Sentry.isEnabled) {
      _dio.addSentry();
    }

    // Certificate pinning: only trust Let's Encrypt ISRG Root X1 (valid until 2035).
    // This rejects MITM attacks using any other CA (e.g. corporate proxies,
    // rogue CAs). Not applied on web (no IOHttpClientAdapter).
    if (!kIsWeb) {
      _configureCertificatePinning();
    }
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
  final AuthInterceptor _authInterceptor;

  Dio get dio => _dio;

  /// A callback invoked when a 401 response is received.
  /// Use this to notify the AuthBloc of session expiration.
  void Function()? get onSessionExpired => _authInterceptor.onSessionExpired;

  set onSessionExpired(void Function()? callback) {
    _authInterceptor.onSessionExpired = callback;
  }

  /// Configures the Dio HTTP client to only trust the Let's Encrypt ISRG Root X1
  /// CA certificate embedded in the app bundle. This pins to the root CA level,
  /// so leaf certificate renewals (every 90 days with Let's Encrypt) do not
  /// break the app as long as the same CA chain is used.
  ///
  /// In debug mode, standard OS trust store is used to allow inspection with
  /// proxies (e.g. Charles, Burp Suite) during development.
  void _configureCertificatePinning() {
    if (kDebugMode) {
      // Allow standard trust store in debug to enable dev proxies.
      (_dio.httpClientAdapter as IOHttpClientAdapter).createHttpClient = () =>
          HttpClient()..badCertificateCallback = (cert, host, port) => false;
      return;
    }

    // Production: only trust our pinned CA cert.
    (_dio.httpClientAdapter as IOHttpClientAdapter).createHttpClient = () {
      late final SecurityContext secCtx;
      // _pinnedCertBytes is loaded asynchronously at startup via
      // loadPinnedCertificate(). If not yet loaded, fall back to default trust.
      final bytes = _pinnedCertBytes;
      if (bytes != null) {
        secCtx =
            SecurityContext(
                withTrustedRoots: false,
              ) // ignore: avoid_redundant_argument_values
              ..setTrustedCertificatesBytes(bytes);
      } else {
        secCtx = SecurityContext.defaultContext;
      }
      return HttpClient(context: secCtx)
        ..badCertificateCallback = (cert, host, port) => false;
    };
  }

  static Uint8List? _pinnedCertBytes;

  /// Must be called once at app startup (before any API call) to load the
  /// pinned CA certificate from the asset bundle.
  static Future<void> loadPinnedCertificate() async {
    if (kDebugMode) return; // Not used in debug mode.
    try {
      final byteData = await rootBundle.load(
        'assets/certs/letsencrypt_isrg_root_x1.pem',
      );
      _pinnedCertBytes = byteData.buffer.asUint8List();
    } on Exception catch (e) {
      // If asset loading fails, log and continue with default trust store.
      // This prevents the app from being completely unusable if the cert file
      // is accidentally removed, while still logging the security degradation.
      debugPrint('[Security] Certificate pinning failed to load: $e');
    }
  }
}
