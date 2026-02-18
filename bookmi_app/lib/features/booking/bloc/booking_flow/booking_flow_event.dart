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
    required this.eventLocation,
    this.message,
    this.isExpress = false,
  });

  final int talentProfileId;
  final int servicePackageId;
  final String eventDate;
  final String eventLocation;
  final String? message;
  final bool isExpress;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingFlowSubmitted &&
          talentProfileId == other.talentProfileId &&
          servicePackageId == other.servicePackageId &&
          eventDate == other.eventDate &&
          eventLocation == other.eventLocation &&
          message == other.message &&
          isExpress == other.isExpress;

  @override
  int get hashCode => Object.hash(
    talentProfileId,
    servicePackageId,
    eventDate,
    eventLocation,
    message,
    isExpress,
  );
}

/// Reset the flow to initial state.
final class BookingFlowReset extends BookingFlowEvent {
  const BookingFlowReset();
}
