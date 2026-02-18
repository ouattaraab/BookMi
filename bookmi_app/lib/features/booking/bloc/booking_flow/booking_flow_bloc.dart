import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/booking/bloc/booking_flow/booking_flow_event.dart';
import 'package:bookmi_app/features/booking/bloc/booking_flow/booking_flow_state.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';

class BookingFlowBloc extends Bloc<BookingFlowEvent, BookingFlowState> {
  BookingFlowBloc({required BookingRepository repository})
    : _repository = repository,
      super(const BookingFlowInitial()) {
    on<BookingFlowSubmitted>(_onSubmitted);
    on<BookingFlowReset>(_onReset);
  }

  final BookingRepository _repository;

  Future<void> _onSubmitted(
    BookingFlowSubmitted event,
    Emitter<BookingFlowState> emit,
  ) async {
    if (state is BookingFlowSubmitting) return;

    emit(const BookingFlowSubmitting());

    final result = await _repository.createBooking(
      talentProfileId: event.talentProfileId,
      servicePackageId: event.servicePackageId,
      eventDate: event.eventDate,
      eventLocation: event.eventLocation,
      message: event.message,
      isExpress: event.isExpress,
    );

    switch (result) {
      case ApiSuccess(:final data):
        emit(BookingFlowSuccess(booking: data));
      case ApiFailure(:final code, :final message):
        emit(BookingFlowFailure(code: code, message: message));
    }
  }

  void _onReset(
    BookingFlowReset event,
    Emitter<BookingFlowState> emit,
  ) {
    emit(const BookingFlowInitial());
  }
}
