import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:flutter/foundation.dart';

sealed class AuthEvent {
  const AuthEvent();
}

@immutable
final class AuthCheckRequested extends AuthEvent {
  const AuthCheckRequested();
}

@immutable
final class AuthLoginSubmitted extends AuthEvent {
  const AuthLoginSubmitted({
    required this.email,
    required this.password,
  });

  final String email;
  final String password;

  @override
  bool operator ==(Object other) =>
      other is AuthLoginSubmitted &&
      other.email == email &&
      other.password == password;

  @override
  int get hashCode => Object.hash(email, password);
}

@immutable
final class AuthRegisterSubmitted extends AuthEvent {
  const AuthRegisterSubmitted({required this.data});

  final Map<String, dynamic> data;
}

@immutable
final class AuthOtpSubmitted extends AuthEvent {
  const AuthOtpSubmitted({
    required this.phone,
    required this.code,
  });

  final String phone;
  final String code;

  @override
  bool operator ==(Object other) =>
      other is AuthOtpSubmitted &&
      other.phone == phone &&
      other.code == code;

  @override
  int get hashCode => Object.hash(phone, code);
}

@immutable
final class AuthOtpResendRequested extends AuthEvent {
  const AuthOtpResendRequested({required this.phone});

  final String phone;

  @override
  bool operator ==(Object other) =>
      other is AuthOtpResendRequested && other.phone == phone;

  @override
  int get hashCode => phone.hashCode;
}

@immutable
final class AuthForgotPasswordSubmitted extends AuthEvent {
  const AuthForgotPasswordSubmitted({required this.email});

  final String email;

  @override
  bool operator ==(Object other) =>
      other is AuthForgotPasswordSubmitted && other.email == email;

  @override
  int get hashCode => email.hashCode;
}

@immutable
final class AuthLogoutRequested extends AuthEvent {
  const AuthLogoutRequested();
}

@immutable
final class AuthSessionExpired extends AuthEvent {
  const AuthSessionExpired();
}

@immutable
final class AuthProfileUpdated extends AuthEvent {
  const AuthProfileUpdated(this.user);

  final AuthUser user;
}
