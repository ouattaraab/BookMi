import 'package:flutter/foundation.dart';

/// Compact booking info embedded in an experience when the current user
/// already holds a reservation.
@immutable
class ExperienceBookingInfo {
  const ExperienceBookingInfo({
    required this.id,
    required this.seatsCount,
    required this.totalAmount,
    required this.status,
  });

  final int id;
  final int seatsCount;
  final int totalAmount;
  final String status;

  factory ExperienceBookingInfo.fromJson(Map<String, dynamic> json) {
    return ExperienceBookingInfo(
      id: json['id'] as int,
      seatsCount: json['seats_count'] as int? ?? 1,
      totalAmount: json['total_amount'] as int? ?? 0,
      status: json['status'] as String? ?? 'pending',
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExperienceBookingInfo &&
          id == other.id &&
          status == other.status;

  @override
  int get hashCode => Object.hash(id, status);
}

/// Talent profile info embedded in an experience.
@immutable
class ExperienceTalentInfo {
  const ExperienceTalentInfo({
    required this.id,
    required this.stageName,
    required this.slug,
    this.profilePhoto,
    this.categoryName,
  });

  final int id;
  final String stageName;
  final String slug;
  final String? profilePhoto;
  final String? categoryName;

  factory ExperienceTalentInfo.fromJson(Map<String, dynamic> json) {
    final category = json['category'] as Map<String, dynamic>?;
    return ExperienceTalentInfo(
      id: json['id'] as int,
      stageName: json['stage_name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      profilePhoto: json['profile_photo'] as String?,
      categoryName: category?['name'] as String?,
    );
  }
}

/// Immutable model representing a Meet & Greet private experience.
@immutable
class ExperienceModel {
  const ExperienceModel({
    required this.id,
    required this.title,
    required this.description,
    required this.eventDate,
    required this.eventTime,
    required this.pricePerSeat,
    required this.maxSeats,
    required this.seatsAvailable,
    required this.status,
    this.venueAddress,
    this.venueRevealed = false,
    this.coverImageUrl,
    this.talentProfile,
    this.myBooking,
  });

  final int id;
  final String title;
  final String description;
  final String eventDate;
  final String eventTime;
  final int pricePerSeat;
  final int maxSeats;
  final int seatsAvailable;

  /// 'published' | 'draft' | 'cancelled' | 'completed'
  final String status;

  /// Venue address — only shown when revealed or user has booked.
  final String? venueAddress;
  final bool venueRevealed;

  /// Cover media URL (photo or video uploaded by talent/admin).
  final String? coverImageUrl;

  final ExperienceTalentInfo? talentProfile;

  /// Non-null when the current authenticated user has booked this experience.
  final ExperienceBookingInfo? myBooking;

  bool get isFull => seatsAvailable <= 0;
  bool get isPublished => status == 'published';
  bool get isCancelled => status == 'cancelled';
  bool get isCompleted => status == 'completed';
  bool get hasBooked => myBooking != null;

  factory ExperienceModel.fromJson(Map<String, dynamic> json) {
    final talentJson = json['talent_profile'] as Map<String, dynamic>?;
    final bookingJson = json['my_booking'] as Map<String, dynamic>?;
    return ExperienceModel(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      description: json['description'] as String? ?? '',
      eventDate: json['event_date'] as String? ?? '',
      eventTime: json['event_time'] as String? ?? '',
      pricePerSeat: json['price_per_seat'] as int? ?? 0,
      maxSeats: json['max_seats'] as int? ?? 0,
      seatsAvailable: json['seats_available'] as int? ?? 0,
      status: json['status'] as String? ?? 'draft',
      venueAddress: json['venue_address'] as String?,
      venueRevealed: json['venue_revealed'] as bool? ?? false,
      coverImageUrl: json['cover_image'] as String?,
      talentProfile: talentJson != null
          ? ExperienceTalentInfo.fromJson(talentJson)
          : null,
      myBooking: bookingJson != null
          ? ExperienceBookingInfo.fromJson(bookingJson)
          : null,
    );
  }

  ExperienceModel copyWith({
    ExperienceBookingInfo? myBooking,
    bool clearBooking = false,
    int? seatsAvailable,
    String? status,
    String? coverImageUrl,
  }) {
    return ExperienceModel(
      id: id,
      title: title,
      description: description,
      eventDate: eventDate,
      eventTime: eventTime,
      pricePerSeat: pricePerSeat,
      maxSeats: maxSeats,
      seatsAvailable: seatsAvailable ?? this.seatsAvailable,
      status: status ?? this.status,
      venueAddress: venueAddress,
      venueRevealed: venueRevealed,
      coverImageUrl: coverImageUrl ?? this.coverImageUrl,
      talentProfile: talentProfile,
      myBooking: clearBooking ? null : (myBooking ?? this.myBooking),
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExperienceModel &&
          id == other.id &&
          status == other.status &&
          seatsAvailable == other.seatsAvailable &&
          myBooking == other.myBooking;

  @override
  int get hashCode => Object.hash(id, status, seatsAvailable, myBooking);
}
