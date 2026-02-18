import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:flutter/foundation.dart';

sealed class BookingsListState {
  const BookingsListState();
}

final class BookingsListInitial extends BookingsListState {
  const BookingsListInitial();
}

final class BookingsListLoading extends BookingsListState {
  const BookingsListLoading();
}

@immutable
final class BookingsListLoaded extends BookingsListState {
  const BookingsListLoaded({
    required this.bookings,
    required this.hasMore,
    required this.nextCursor,
    required this.activeStatus,
  });

  final List<BookingModel> bookings;
  final bool hasMore;
  final String? nextCursor;
  final String? activeStatus;

  BookingsListLoaded copyWith({
    List<BookingModel>? bookings,
    bool? hasMore,
    String? nextCursor,
    String? Function()? activeStatus,
  }) {
    return BookingsListLoaded(
      bookings: bookings ?? this.bookings,
      hasMore: hasMore ?? this.hasMore,
      nextCursor: nextCursor ?? this.nextCursor,
      activeStatus: activeStatus != null ? activeStatus() : this.activeStatus,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other.runtimeType == runtimeType &&
          other is BookingsListLoaded &&
          listEquals(bookings, other.bookings) &&
          hasMore == other.hasMore &&
          nextCursor == other.nextCursor &&
          activeStatus == other.activeStatus;

  @override
  int get hashCode => Object.hash(
    runtimeType,
    Object.hashAll(bookings),
    hasMore,
    nextCursor,
    activeStatus,
  );
}

final class BookingsListLoadingMore extends BookingsListLoaded {
  const BookingsListLoadingMore({
    required super.bookings,
    required super.hasMore,
    required super.nextCursor,
    required super.activeStatus,
  });
}

@immutable
final class BookingsListFailure extends BookingsListState {
  const BookingsListFailure({required this.code, required this.message});

  final String code;
  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingsListFailure &&
          code == other.code &&
          message == other.message;

  @override
  int get hashCode => Object.hash(code, message);
}
