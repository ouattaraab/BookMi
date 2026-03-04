sealed class AppVersionState {
  const AppVersionState();
}

final class AppVersionInitial extends AppVersionState {
  const AppVersionInitial();
}

final class AppVersionOk extends AppVersionState {
  const AppVersionOk();
}

final class AppVersionMaintenance extends AppVersionState {
  const AppVersionMaintenance({
    required this.message,
    this.endAt,
  });

  final String message;
  final DateTime? endAt;
}

final class AppVersionUpdateRequired extends AppVersionState {
  const AppVersionUpdateRequired({
    required this.updateType,
    required this.version,
    this.message,
    required this.features,
    this.androidStoreUrl,
    this.iosStoreUrl,
  });

  final String updateType; // 'optional' | 'forced'
  final String version;
  final String? message;
  final List<String> features;
  final String? androidStoreUrl;
  final String? iosStoreUrl;
}

final class AppVersionError extends AppVersionState {
  const AppVersionError(this.errorMessage);

  final String errorMessage;
}
