import 'package:flutter/foundation.dart';

/// Represents a single M&G booking entry returned by GET /me/experience-bookings.
@immutable
class ExperienceBookingListItem {
  const ExperienceBookingListItem({
    required this.bookingId,
    required this.seatsCount,
    required this.pricePerSeat,
    required this.totalAmount,
    required this.status,
    required this.statusLabel,
    required this.experienceId,
    required this.title,
    required this.eventDate,
    required this.eventTime,
    this.venueAddress,
    this.coverImageUrl,
    this.talentId,
    this.talentStageName,
    this.talentPhoto,
  });

  final int bookingId;
  final int seatsCount;
  final int pricePerSeat;
  final int totalAmount;
  final String status;
  final String statusLabel;

  // Nested experience fields
  final int experienceId;
  final String title;
  final String eventDate;
  final String eventTime;
  final String? venueAddress;
  final String? coverImageUrl;

  // Nested talent fields
  final int? talentId;
  final String? talentStageName;
  final String? talentPhoto;

  factory ExperienceBookingListItem.fromJson(Map<String, dynamic> json) {
    final exp = (json['experience'] as Map<String, dynamic>?) ?? {};
    final talent = (exp['talent'] as Map<String, dynamic>?) ?? {};

    return ExperienceBookingListItem(
      bookingId: json['booking_id'] as int? ?? 0,
      seatsCount: json['seats_count'] as int? ?? 1,
      pricePerSeat: json['price_per_seat'] as int? ?? 0,
      totalAmount: json['total_amount'] as int? ?? 0,
      status: json['status'] as String? ?? 'pending',
      statusLabel: json['status_label'] as String? ?? 'En attente',
      experienceId: exp['id'] as int? ?? 0,
      title: exp['title'] as String? ?? '',
      eventDate: exp['event_date'] as String? ?? '',
      eventTime: exp['event_time'] as String? ?? '',
      venueAddress: exp['venue_address'] as String?,
      coverImageUrl: exp['cover_image'] as String?,
      talentId: talent['id'] as int?,
      talentStageName: talent['stage_name'] as String?,
      talentPhoto: talent['profile_photo'] as String?,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExperienceBookingListItem &&
          bookingId == other.bookingId &&
          status == other.status;

  @override
  int get hashCode => Object.hash(bookingId, status);
}
