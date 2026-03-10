import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorage {
  SecureStorage({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'auth_token';
  static const _biometricEnabledKey = 'biometric_enabled';

  Future<String?> getToken() => _storage.read(key: _tokenKey);

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<void> deleteToken() => _storage.delete(key: _tokenKey);

  Future<void> deleteAll() => _storage.deleteAll();

  // ── Biometric ────────────────────────────────────────────────────
  // Security: biometric login re-uses the existing Sanctum token stored in
  // _tokenKey — no plaintext password is ever persisted.

  Future<bool> isBiometricEnabled() async {
    final value = await _storage.read(key: _biometricEnabledKey);
    return value == 'true';
  }

  Future<void> setBiometricEnabled({required bool enabled}) async {
    await _storage.write(
      key: _biometricEnabledKey,
      value: enabled ? 'true' : 'false',
    );
  }

  /// Returns the stored token if biometric is enabled, null otherwise.
  /// The caller must verify biometric auth BEFORE calling this.
  Future<String?> getBiometricToken() async {
    final enabled = await isBiometricEnabled();
    if (!enabled) return null;
    return getToken();
  }
}
