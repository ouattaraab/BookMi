import 'package:bookmi_app/app/app.dart';
import 'package:bookmi_app/bootstrap.dart';

Future<void> main() async {
  const dsn = String.fromEnvironment('SENTRY_DSN');
  await bootstrap(
    () => const App(),
    sentryDsn: dsn.isNotEmpty ? dsn : null,
    environment: 'staging',
  );
}
