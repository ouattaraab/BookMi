import 'dart:async';
import 'dart:developer';

import 'package:bloc/bloc.dart';
import 'package:bookmi_app/app/app_bloc_observer.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:hive_ce_flutter/hive_ce_flutter.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

Future<void> bootstrap(
  FutureOr<Widget> Function() builder, {
  String? sentryDsn,
  String environment = 'development',
}) async {
  WidgetsFlutterBinding.ensureInitialized();

  if (kDebugMode) {
    Bloc.observer = const AppBlocObserver();
  }

  // Initialize Hive for local storage
  await Hive.initFlutter();

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
