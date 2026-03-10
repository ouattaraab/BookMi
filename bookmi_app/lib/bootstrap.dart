import 'dart:async';
import 'dart:developer';

import 'package:bloc/bloc.dart';
import 'package:bookmi_app/app/app_bloc_observer.dart';
import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/services/notification_service.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:bookmi_app/firebase_options.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:hive_ce_flutter/hive_ce_flutter.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

Future<void> bootstrap(
  FutureOr<Widget> Function() builder, {
  required String baseUrl,
  String? sentryDsn,
  String environment = 'development',
}) async {
  WidgetsFlutterBinding.ensureInitialized();

  if (kDebugMode) {
    Bloc.observer = const AppBlocObserver();
  }

  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  // 5-second timeout: requestPermission/getInitialMessage can hang on iOS
  // simulator (no APNs). The app still works — FCM just won't be available.
  await NotificationService.instance.init().timeout(
    const Duration(seconds: 5),
    onTimeout: () {},
  );

  // Print FCM token in debug mode — useful for testing artisan bookmi:test-push
  // Non-blocking: getToken() can hang indefinitely on iOS simulator (no APNs)
  if (kDebugMode) {
    unawaited(
      NotificationService.instance.getFcmToken().then(
        (t) => debugPrint('[BookMi FCM] Device token: $t'),
      ),
    );
  }

  // Initialize Hive for local storage
  await Hive.initFlutter();

  // Initialize ApiClient singleton before the widget tree is built
  ApiClient(baseUrl: baseUrl, secureStorage: SecureStorage());

  final hasSentry = sentryDsn != null && sentryDsn.isNotEmpty;

  if (hasSentry) {
    await SentryFlutter.init(
      (options) {
        options
          ..dsn = sentryDsn
          ..environment = environment
          ..tracesSampleRate = environment == 'production' ? 0.2 : 0.0;
      },
      appRunner: () async {
        FlutterError.onError = (details) {
          log(
            details.exceptionAsString(),
            stackTrace: details.stack,
          );
          unawaited(
            Sentry.captureException(
              details.exception,
              stackTrace: details.stack,
            ),
          );
        };

        PlatformDispatcher.instance.onError = (error, stack) {
          unawaited(
            Sentry.captureException(error, stackTrace: stack),
          );
          return true;
        };

        runApp(await builder());
      },
    );
  } else {
    FlutterError.onError = (details) {
      log(
        details.exceptionAsString(),
        stackTrace: details.stack,
      );
    };

    runApp(await builder());
  }
}
