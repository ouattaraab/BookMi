import 'package:flutter/foundation.dart';

/// Represents a single attendee booking for a talent-owned experience.
@immutable
class ExperienceAttendee {
  const ExperienceAttendee({
    required this.id,
    required this.clientId,
    required this.firstName,
    required this.lastName,
    required this.seatsCount,
    required this.totalAmount,
    required this.status,
    required this.statusLabel,
    required this.createdAt,
  });

  final int id;
  final int clientId;
  final String firstName;
  final String lastName;
  final int seatsCount;
  final int totalAmount;
  final String status;
  final String statusLabel;
  final String createdAt;

  String get fullName => '$firstName $lastName'.trim();

  factory ExperienceAttendee.fromJson(Map<String, dynamic> json) {
    return ExperienceAttendee(
      id: json['id'] as int,
      clientId: json['client_id'] as int? ?? 0,
      firstName: json['first_name'] as String? ?? '',
      lastName: json['last_name'] as String? ?? '',
      seatsCount: json['seats_count'] as int? ?? 1,
      totalAmount: json['total_amount'] as int? ?? 0,
      status: json['status'] as String? ?? 'pending',
      statusLabel: json['status_label'] as String? ?? 'En attente',
      createdAt: json['created_at'] as String? ?? '',
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExperienceAttendee && id == other.id && status == other.status;

  @override
  int get hashCode => Object.hash(id, status);
}
