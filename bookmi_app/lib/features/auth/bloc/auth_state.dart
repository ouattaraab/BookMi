import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:flutter/foundation.dart';

sealed class AuthState {
  const AuthState();
}

final class AuthInitial extends AuthState {
  const AuthInitial();
}

final class AuthLoading extends AuthState {
  const AuthLoading();
}

@immutable
final class AuthAuthenticated extends AuthState {
  const AuthAuthenticated({
    required this.user,
    required this.roles,
  });

  final AuthUser user;
  final List<String> roles;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AuthAuthenticated &&
          user == other.user &&
          listEquals(roles, other.roles);

  @override
  int get hashCode => Object.hash(user, Object.hashAll(roles));
}

final class AuthUnauthenticated extends AuthState {
  const AuthUnauthenticated();
}

@immutable
final class AuthRegistrationSuccess extends AuthState {
  const AuthRegistrationSuccess({required this.phone});

  final String phone;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AuthRegistrationSuccess && phone == other.phone;

  @override
  int get hashCode => phone.hashCode;
}

final class AuthOtpResent extends AuthState {
  const AuthOtpResent();
}

final class AuthForgotPasswordSuccess extends AuthState {
  const AuthForgotPasswordSuccess();
}

@immutable
final class AuthFailure extends AuthState {
  const AuthFailure({
    required this.code,
    required this.message,
    this.details,
  });

  final String code;
  final String message;
  final Map<String, dynamic>? details;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AuthFailure && code == other.code && message == other.message;

  @override
  int get hashCode => Object.hash(code, message);
}
