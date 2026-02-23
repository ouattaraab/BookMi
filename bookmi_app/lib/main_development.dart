import 'package:bookmi_app/app/app.dart';
import 'package:bookmi_app/bootstrap.dart';

Future<void> main() async {
  await bootstrap(
    () => const App(),
    // Point to production for testing — change to http://10.0.2.2:8080/api/v1 for local dev
    baseUrl: 'https://bookmi.click/api/v1',
  );
}
