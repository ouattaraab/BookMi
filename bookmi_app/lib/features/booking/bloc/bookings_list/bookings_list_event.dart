import 'package:flutter/foundation.dart';

sealed class BookingsListEvent {
  const BookingsListEvent();
}

/// Fetch (or refresh) bookings for the given [status] tab.
@immutable
final class BookingsListFetched extends BookingsListEvent {
  const BookingsListFetched({this.status});

  /// Filter value: 'pending', 'accepted', 'paid', 'confirmed',
  /// 'completed', 'cancelled', or null for all.
  final String? status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingsListFetched && status == other.status;

  @override
  int get hashCode => status.hashCode;
}

/// Load the next cursor page.
@immutable
final class BookingsListNextPageFetched extends BookingsListEvent {
  const BookingsListNextPageFetched();
}
