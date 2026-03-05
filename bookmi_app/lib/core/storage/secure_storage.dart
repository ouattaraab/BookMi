import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorage {
  SecureStorage({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'auth_token';
  static const _biometricEnabledKey = 'biometric_enabled';
  static const _biometricEmailKey = 'biometric_email';
  static const _biometricPasswordKey = 'biometric_password';

  Future<String?> getToken() => _storage.read(key: _tokenKey);

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<void> deleteToken() => _storage.delete(key: _tokenKey);

  Future<void> deleteAll() => _storage.deleteAll();

  // ── Biometric ────────────────────────────────────────────────────

  Future<bool> isBiometricEnabled() async {
    final value = await _storage.read(key: _biometricEnabledKey);
    return value == 'true';
  }

  Future<void> setBiometricEnabled({
    required bool enabled,
    String? email,
    String? password,
  }) async {
    await _storage.write(key: _biometricEnabledKey, value: enabled ? 'true' : 'false');
    if (enabled && email != null && password != null) {
      await _storage.write(key: _biometricEmailKey, value: email);
      await _storage.write(key: _biometricPasswordKey, value: password);
    } else if (!enabled) {
      await _storage.delete(key: _biometricEmailKey);
      await _storage.delete(key: _biometricPasswordKey);
    }
  }

  Future<({String email, String password})?> getBiometricCredentials() async {
    final email = await _storage.read(key: _biometricEmailKey);
    final password = await _storage.read(key: _biometricPasswordKey);
    if (email == null || password == null) return null;
    return (email: email, password: password);
  }
}
