import 'package:flutter/foundation.dart';

@immutable
class AuthUser {
  const AuthUser({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.phone,
    required this.isActive,
    this.phoneVerifiedAt,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: json['id'] as int,
      firstName: json['first_name'] as String,
      lastName: json['last_name'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String,
      phoneVerifiedAt: json['phone_verified_at'] as String?,
      isActive: json['is_active'] as bool,
    );
  }

  final int id;
  final String firstName;
  final String lastName;
  final String email;
  final String phone;
  final String? phoneVerifiedAt;
  final bool isActive;

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone': phone,
      'phone_verified_at': phoneVerifiedAt,
      'is_active': isActive,
    };
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AuthUser &&
          id == other.id &&
          firstName == other.firstName &&
          lastName == other.lastName &&
          email == other.email &&
          phone == other.phone &&
          phoneVerifiedAt == other.phoneVerifiedAt &&
          isActive == other.isActive;

  @override
  int get hashCode => Object.hash(
    id,
    firstName,
    lastName,
    email,
    phone,
    phoneVerifiedAt,
    isActive,
  );
}
