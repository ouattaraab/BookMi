import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:flutter/foundation.dart';

sealed class BookingFlowState {
  const BookingFlowState();
}

final class BookingFlowInitial extends BookingFlowState {
  const BookingFlowInitial();
}

final class BookingFlowSubmitting extends BookingFlowState {
  const BookingFlowSubmitting();
}

@immutable
final class BookingFlowSuccess extends BookingFlowState {
  const BookingFlowSuccess({required this.booking});
  final BookingModel booking;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingFlowSuccess && booking == other.booking;

  @override
  int get hashCode => booking.hashCode;
}

final class BookingFlowPaymentSubmitting extends BookingFlowState {
  const BookingFlowPaymentSubmitting();
}

/// Paystack transaction initialized — SDK should launch with this access code.
@immutable
final class BookingFlowPaystackReady extends BookingFlowState {
  const BookingFlowPaystackReady({required this.accessCode});

  final String accessCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingFlowPaystackReady && accessCode == other.accessCode;

  @override
  int get hashCode => accessCode.hashCode;
}

@immutable
final class BookingFlowFailure extends BookingFlowState {
  const BookingFlowFailure({required this.code, required this.message});
  final String code;
  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingFlowFailure &&
          code == other.code &&
          message == other.message;

  @override
  int get hashCode => Object.hash(code, message);
}
