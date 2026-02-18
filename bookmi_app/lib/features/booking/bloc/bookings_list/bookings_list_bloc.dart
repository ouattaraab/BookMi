import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_event.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_state.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';

class BookingsListBloc extends Bloc<BookingsListEvent, BookingsListState> {
  BookingsListBloc({required BookingRepository repository})
    : _repository = repository,
      super(const BookingsListInitial()) {
    on<BookingsListFetched>(_onFetched);
    on<BookingsListNextPageFetched>(_onNextPageFetched);
  }

  final BookingRepository _repository;

  Future<void> _onFetched(
    BookingsListFetched event,
    Emitter<BookingsListState> emit,
  ) async {
    emit(const BookingsListLoading());

    final result = await _repository.getBookings(status: event.status);

    switch (result) {
      case ApiSuccess(:final data):
        emit(
          BookingsListLoaded(
            bookings: data.bookings,
            hasMore: data.hasMore,
            nextCursor: data.nextCursor,
            activeStatus: event.status,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(BookingsListFailure(code: code, message: message));
    }
  }

  Future<void> _onNextPageFetched(
    BookingsListNextPageFetched event,
    Emitter<BookingsListState> emit,
  ) async {
    final currentState = state;
    if (currentState is BookingsListLoadingMore ||
        currentState is! BookingsListLoaded) {
      return;
    }
    if (!currentState.hasMore) return;

    emit(
      BookingsListLoadingMore(
        bookings: currentState.bookings,
        hasMore: currentState.hasMore,
        nextCursor: currentState.nextCursor,
        activeStatus: currentState.activeStatus,
      ),
    );

    final result = await _repository.getBookings(
      status: currentState.activeStatus,
      cursor: currentState.nextCursor,
    );

    switch (result) {
      case ApiSuccess(:final data):
        emit(
          BookingsListLoaded(
            bookings: [...currentState.bookings, ...data.bookings],
            hasMore: data.hasMore,
            nextCursor: data.nextCursor,
            activeStatus: currentState.activeStatus,
          ),
        );
      case ApiFailure():
        // Revert — preserve existing data
        emit(
          BookingsListLoaded(
            bookings: currentState.bookings,
            hasMore: currentState.hasMore,
            nextCursor: currentState.nextCursor,
            activeStatus: currentState.activeStatus,
          ),
        );
    }
  }
}
