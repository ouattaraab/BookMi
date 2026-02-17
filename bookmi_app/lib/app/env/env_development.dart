import 'package:bookmi_app/app/env/env.dart';

class EnvDevelopment implements Env {
  @override
  String get apiBaseUrl => 'http://10.0.2.2:8080/api/v1';

  @override
  String get paystackPublicKey => 'pk_test_xxx';

  @override
  String get sentryDsn => '';

  @override
  String get appName => 'BookMi Dev';
}
