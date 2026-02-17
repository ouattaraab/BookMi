import 'dart:async';
import 'dart:developer';

import 'package:bloc/bloc.dart';
import 'package:bookmi_app/app/app_bloc_observer.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:hive_ce_flutter/hive_ce_flutter.dart';

Future<void> bootstrap(FutureOr<Widget> Function() builder) async {
  WidgetsFlutterBinding.ensureInitialized();

  FlutterError.onError = (details) {
    log(details.exceptionAsString(), stackTrace: details.stack);
  };

  if (kDebugMode) {
    Bloc.observer = const AppBlocObserver();
  }

  // Initialize Hive for local storage
  await Hive.initFlutter();

  runApp(await builder());
}
