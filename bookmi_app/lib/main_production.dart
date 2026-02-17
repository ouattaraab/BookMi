import 'package:bookmi_app/app/app.dart';
import 'package:bookmi_app/bootstrap.dart';

Future<void> main() async {
  await bootstrap(() => const App());
}
