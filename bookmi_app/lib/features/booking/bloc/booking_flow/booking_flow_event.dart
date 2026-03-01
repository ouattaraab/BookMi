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
    required this.eventDate,
    required this.startTime,
    required this.eventLocation,
    this.message,
    this.isExpress = false,
    this.promoCode,
  });

  final int talentProfileId;
  final int servicePackageId;

  /// Date only, format YYYY-MM-DD (e.g. "2026-03-15").
  final String eventDate;

  /// Time only, format HH:MM (e.g. "18:00").
  final String startTime;
  final String eventLocation;
  final String? message;
  final bool isExpress;

  /// Applied promo code (uppercase), or null if none.
  final String? promoCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingFlowSubmitted &&
          talentProfileId == other.talentProfileId &&
          servicePackageId == other.servicePackageId &&
          eventDate == other.eventDate &&
          startTime == other.startTime &&
          eventLocation == other.eventLocation &&
          message == other.message &&
          isExpress == other.isExpress &&
          promoCode == other.promoCode;

  @override
  int get hashCode => Object.hash(
    talentProfileId,
    servicePackageId,
    eventDate,
    startTime,
    eventLocation,
    message,
    isExpress,
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
