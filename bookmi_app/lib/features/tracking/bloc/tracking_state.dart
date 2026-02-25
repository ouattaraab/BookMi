import 'package:bookmi_app/features/tracking/data/models/tracking_event_model.dart';
import 'package:flutter/foundation.dart';

sealed class TrackingState {
  const TrackingState();
}

final class TrackingInitial extends TrackingState {
  const TrackingInitial();
}

final class TrackingLoading extends TrackingState {
  const TrackingLoading();
}

@immutable
final class TrackingLoaded extends TrackingState {
  const TrackingLoaded({
    required this.events,
    required this.bookingId,
  });

  final List<TrackingEventModel> events;
  final int bookingId;

  String? get currentStatus => events.isEmpty ? null : events.last.status;
  bool get isCompleted => events.isNotEmpty && events.last.isCompleted;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      runtimeType == other.runtimeType &&
          other is TrackingLoaded &&
          bookingId == other.bookingId &&
          listEquals(events, other.events);

  @override
  int get hashCode => Object.hash(bookingId, Object.hashAll(events));
}

/// Sub-state: a mutation is in flight; events preserved for optimistic UI.
final class TrackingUpdating extends TrackingLoaded {
  const TrackingUpdating({
    required super.events,
    required super.bookingId,
  });
}

@immutable
final class TrackingError extends TrackingState {
  const TrackingError(this.message);

  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TrackingError && message == other.message;

  @override
  int get hashCode => message.hashCode;
}
