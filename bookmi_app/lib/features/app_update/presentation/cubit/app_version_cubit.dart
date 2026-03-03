import 'package:bookmi_app/features/app_update/data/repositories/app_version_repository.dart';
import 'package:bookmi_app/features/app_update/presentation/cubit/app_version_state.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AppVersionCubit extends Cubit<AppVersionState> {
  AppVersionCubit({required AppVersionRepository repository})
      : _repository = repository,
        super(const AppVersionInitial());

  final AppVersionRepository _repository;

  Future<void> check(String currentVersion) async {
    try {
      final info = await _repository.check();

      // 1. Maintenance takes priority over everything
      if (info.maintenance) {
        emit(AppVersionMaintenance(
          message: info.maintenanceMessage ?? 'Maintenance en cours.',
          endAt: info.maintenanceEndAt,
        ));
        return;
      }

      // 2. Check version (only if update_type != 'none')
      if (info.updateType != 'none' &&
          _isOutdated(currentVersion, info.versionRequired)) {
        emit(AppVersionUpdateRequired(
          updateType: info.updateType,
          version: info.versionRequired,
          message: info.updateMessage,
          features: info.features,
          androidStoreUrl: info.androidStoreUrl,
          iosStoreUrl: info.iosStoreUrl,
        ));
        return;
      }

      emit(const AppVersionOk());
    } catch (_) {
      // Network failure or parse error → let the app continue normally
      emit(const AppVersionOk());
    }
  }

  /// Returns true if [installed] < [required] (semantic comparison).
  bool _isOutdated(String installed, String required) {
    final i = _parse(installed);
    final r = _parse(required);
    for (var idx = 0; idx < 3; idx++) {
      if (i[idx] < r[idx]) return true;
      if (i[idx] > r[idx]) return false;
    }
    return false;
  }

  List<int> _parse(String version) {
    final parts = version.split('.');
    return List.generate(3, (i) {
      if (i >= parts.length) return 0;
      return int.tryParse(parts[i]) ?? 0;
    });
  }
}
