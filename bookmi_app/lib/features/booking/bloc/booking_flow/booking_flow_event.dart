import 'package:flutter/foundation.dart';

sealed class BookingFlowEvent {
  const BookingFlowEvent();
}

/// Submit the booking to the API after step 3 (recap).
@immutable
final class BookingFlowSubmitted extends BookingFlowEvent {
  const BookingFlowSubmitted({
    required this.talentProfileId,
    required this.servicePackageId,
    this.eventDate,
    this.startTime,
    this.eventLocation,
    this.eventLatitude,
    this.eventLongitude,
    this.message,
    this.isExpress = false,
    this.travelCost,
    this.promoCode,
    this.consents,
  });

  final int talentProfileId;
  final int servicePackageId;

  /// Date only, format YYYY-MM-DD (e.g. "2026-03-15").
  /// Null for micro/digital packages — the backend auto-fills from delivery_days.
  final String? eventDate;

  /// Time only, format HH:MM (e.g. "18:00").
  /// Null for micro packages.
  final String? startTime;

  /// Event location. Null for micro packages (defaults to "Livraison digitale").
  final String? eventLocation;

  /// GPS coordinates of the event location, null if not provided.
  final double? eventLatitude;
  final double? eventLongitude;
  final String? message;
  final bool isExpress;

  /// Optional travel/displacement cost in XOF (0 or null = no travel cost).
  final int? travelCost;

  /// Applied promo code (uppercase), or null if none.
  final String? promoCode;

  /// Transactional consents recorded at booking time.
  final Map<String, bool>? consents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingFlowSubmitted &&
          talentProfileId == other.talentProfileId &&
          servicePackageId == other.servicePackageId &&
          eventDate == other.eventDate &&
          startTime == other.startTime &&
          eventLocation == other.eventLocation &&
          eventLatitude == other.eventLatitude &&
          eventLongitude == other.eventLongitude &&
          message == other.message &&
          isExpress == other.isExpress &&
          travelCost == other.travelCost &&
          promoCode == other.promoCode;

  @override
  int get hashCode => Object.hash(
    talentProfileId,
    servicePackageId,
    eventDate,
    startTime,
    eventLocation,
    eventLatitude,
    eventLongitude,
    message,
    isExpress,
    travelCost,
    promoCode,
  );
}

/// Validate a promo code against the current booking total.
@immutable
final class PromoCodeValidationRequested extends BookingFlowEvent {
  const PromoCodeValidationRequested({
    required this.code,
    required this.bookingAmount,
  });

  final String code;
  final int bookingAmount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PromoCodeValidationRequested &&
          code == other.code &&
          bookingAmount == other.bookingAmount;

  @override
  int get hashCode => Object.hash(code, bookingAmount);
}

/// Initialise la transaction Paystack pour la réservation créée (step 4).
/// Le SDK Paystack prend en charge tous les moyens de paiement
/// (carte, mobile money…) via son propre UI.
@immutable
final class BookingFlowPaymentInitiated extends BookingFlowEvent {
  const BookingFlowPaymentInitiated({required this.bookingId});

  final int bookingId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingFlowPaymentInitiated && bookingId == other.bookingId;

  @override
  int get hashCode => bookingId.hashCode;
}

/// Reset the flow to initial state.
final class BookingFlowReset extends BookingFlowEvent {
  const BookingFlowReset();
}
