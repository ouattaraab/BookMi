import 'package:bookmi_app/app/env/env.dart';

class EnvStaging implements Env {
  @override
  String get apiBaseUrl => 'https://staging-api.bookmi.ci/api/v1';

  @override
  String get paystackPublicKey => 'pk_test_xxx';

  @override
  String get sentryDsn => '';

  @override
  String get appName => 'BookMi Staging';
}
