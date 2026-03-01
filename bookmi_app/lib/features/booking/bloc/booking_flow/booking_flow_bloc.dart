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
    on<PromoCodeValidationRequested>(_onPromoValidation);
    on<BookingFlowPaymentInitiated>(_onPaymentInitiated);
    on<BookingFlowReset>(_onReset);
  }

  final BookingRepository _repository;

  Future<void> _onSubmitted(
    BookingFlowSubmitted event,
    Emitter<BookingFlowState> emit,
  ) async {
    if (state is BookingFlowSubmitting || state is BookingFlowPromoValidating) {
      return;
    }

    emit(const BookingFlowSubmitting());

    final result = await _repository.createBooking(
      talentProfileId: event.talentProfileId,
      servicePackageId: event.servicePackageId,
      eventDate: event.eventDate,
      startTime: event.startTime,
      eventLocation: event.eventLocation,
      message: event.message,
      isExpress: event.isExpress,
      promoCode: event.promoCode,
    );

    switch (result) {
      case ApiSuccess(:final data):
        emit(BookingFlowSuccess(booking: data));
      case ApiFailure(:final code, :final message):
        emit(BookingFlowFailure(code: code, message: message));
    }
  }

  Future<void> _onPromoValidation(
    PromoCodeValidationRequested event,
    Emitter<BookingFlowState> emit,
  ) async {
    if (state is BookingFlowPromoValidating) return;

    emit(const BookingFlowPromoValidating());

    final result = await _repository.validatePromoCode(
      code: event.code,
      bookingAmount: event.bookingAmount,
    );

    switch (result) {
      case ApiSuccess(:final data):
        final discountAmount = data['discount_amount'] as int? ?? 0;
        final appliedCode = data['code'] as String? ?? event.code;
        emit(
          BookingFlowPromoValidated(
            appliedCode: appliedCode,
            discountAmount: discountAmount,
          ),
        );
      case ApiFailure(:final message):
        emit(BookingFlowPromoError(message: message));
    }
  }

  Future<void> _onPaymentInitiated(
    BookingFlowPaymentInitiated event,
    Emitter<BookingFlowState> emit,
  ) async {
    if (state is BookingFlowPaymentSubmitting) return;

    emit(const BookingFlowPaymentSubmitting());

    final result = await _repository.initiatePayment(
      bookingId: event.bookingId,
    );

    switch (result) {
      case ApiSuccess(:final data):
        // Extract access_code from the response (supports JSON:API and flat formats)
        final txData = data['data'] as Map<String, dynamic>?;
        final attrs = txData?['attributes'] as Map<String, dynamic>?;
        final accessCode =
            attrs?['access_code'] as String? ??
            txData?['access_code'] as String? ??
            data['access_code'] as String?;
        if (accessCode != null && accessCode.isNotEmpty) {
          emit(BookingFlowPaystackReady(accessCode: accessCode));
        } else {
          emit(
            const BookingFlowFailure(
              code: 'PAYMENT_ERROR',
              message: "Impossible d'initialiser le paiement Paystack.",
            ),
          );
        }
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
