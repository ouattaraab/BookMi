import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:flutter/foundation.dart';

@immutable
class AuthResponse {
  const AuthResponse({
    required this.token,
    required this.user,
    required this.roles,
  });

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    return AuthResponse(
      token: json['token'] as String,
      user: AuthUser.fromJson(json['user'] as Map<String, dynamic>),
      roles: (json['roles'] as List<dynamic>).cast<String>(),
    );
  }

  final String token;
  final AuthUser user;
  final List<String> roles;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AuthResponse &&
          token == other.token &&
          user == other.user &&
          listEquals(roles, other.roles);

  @override
  int get hashCode => Object.hash(token, user, Object.hashAll(roles));
}
