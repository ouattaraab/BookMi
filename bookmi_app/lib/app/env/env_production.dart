import 'package:bookmi_app/app/env/env.dart';

class EnvProduction implements Env {
  @override
  String get apiBaseUrl => 'https://bookmi.click/api/v1';

  @override
  String get paystackPublicKey => 'pk_live_xxx';

  @override
  String get sentryDsn =>
      'https://6381d60ab6163984665fe5fb7fe95da9@o4510991032975360.ingest.de.sentry.io/4510996942618704';

  @override
  String get appName => 'BookMi';
}
