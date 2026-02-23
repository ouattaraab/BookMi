import 'package:bookmi_app/app/env/env.dart';

class EnvProduction implements Env {
  @override
  String get apiBaseUrl => 'https://bookmi.click/api/v1';

  @override
  String get paystackPublicKey => 'pk_live_xxx';

  @override
  String get sentryDsn => '';

  @override
  String get appName => 'BookMi';
}
