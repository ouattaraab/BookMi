import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/booking/bloc/booking_flow/booking_flow_bloc.dart';
import 'package:bookmi_app/features/booking/bloc/booking_flow/booking_flow_event.dart';
import 'package:bookmi_app/features/booking/bloc/booking_flow/booking_flow_state.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class _MockBookingRepository extends Mock implements BookingRepository {}

void main() {
  late _MockBookingRepository repository;

  const _event = BookingFlowSubmitted(
    talentProfileId: 1,
    servicePackageId: 2,
    eventDate: '2026-06-15',
    startTime: '18:00',
    eventLocation: 'Abidjan',
  );

  final _booking = BookingModel(
    id: 42,
    status: 'pending',
    clientName: 'Test Client',
    talentStageName: 'DJ Alpha',
    talentProfileId: 1,
    packageName: 'Pack Standard',
    packageType: 'standard',
    eventDate: '2026-06-15',
    eventLocation: 'Abidjan',
    cachetAmount: 100000,
    travelCost: 0,
    commissionAmount: 15000,
    totalAmount: 115000,
    expressFeee: 0,
    discountAmount: 0,
    isExpress: false,
    contractAvailable: false,
    hasClientReview: false,
    hasTalentReview: false,
  );

  setUpAll(() {
    registerFallbackValue(_event);
  });

  setUp(() {
    repository = _MockBookingRepository();
  });

  group('BookingFlowBloc', () {
    test('initial state is BookingFlowInitial', () {
      final bloc = BookingFlowBloc(repository: repository);
      expect(bloc.state, isA<BookingFlowInitial>());
    });

    blocTest<BookingFlowBloc, BookingFlowState>(
      'emits [Submitting, Success] on successful submission',
      build: () {
        when(
          () => repository.createBooking(
            talentProfileId: any(named: 'talentProfileId'),
            servicePackageId: any(named: 'servicePackageId'),
            eventDate: any(named: 'eventDate'),
            startTime: any(named: 'startTime'),
            eventLocation: any(named: 'eventLocation'),
            message: any(named: 'message'),
            isExpress: any(named: 'isExpress'),
          ),
        ).thenAnswer((_) async => ApiSuccess(_booking));
        return BookingFlowBloc(repository: repository);
      },
      act: (bloc) => bloc.add(_event),
      expect: () => [
        isA<BookingFlowSubmitting>(),
        isA<BookingFlowSuccess>(),
      ],
    );

    blocTest<BookingFlowBloc, BookingFlowState>(
      'emits [Submitting, Failure] on API error',
      build: () {
        when(
          () => repository.createBooking(
            talentProfileId: any(named: 'talentProfileId'),
            servicePackageId: any(named: 'servicePackageId'),
            eventDate: any(named: 'eventDate'),
            startTime: any(named: 'startTime'),
            eventLocation: any(named: 'eventLocation'),
            message: any(named: 'message'),
            isExpress: any(named: 'isExpress'),
          ),
        ).thenAnswer(
          (_) async => const ApiFailure(
            code: 'BOOKING_001',
            message: 'Talent unavailable',
          ),
        );
        return BookingFlowBloc(repository: repository);
      },
      act: (bloc) => bloc.add(_event),
      expect: () => [
        isA<BookingFlowSubmitting>(),
        isA<BookingFlowFailure>(),
      ],
      verify: (bloc) {
        expect(
          (bloc.state as BookingFlowFailure).code,
          equals('BOOKING_001'),
        );
      },
    );

    test('state is Submitting after add — UI can disable CTA', () async {
      when(
        () => repository.createBooking(
          talentProfileId: any(named: 'talentProfileId'),
          servicePackageId: any(named: 'servicePackageId'),
          eventDate: any(named: 'eventDate'),
          startTime: any(named: 'startTime'),
          eventLocation: any(named: 'eventLocation'),
          message: any(named: 'message'),
          isExpress: any(named: 'isExpress'),
        ),
      ).thenAnswer((_) async {
        await Future<void>.delayed(const Duration(milliseconds: 100));
        return ApiSuccess(_booking);
      });

      final bloc = BookingFlowBloc(repository: repository);
      bloc.add(_event);
      await Future<void>.delayed(Duration.zero);
      expect(bloc.state, isA<BookingFlowSubmitting>());
      await bloc.close();
    });

    blocTest<BookingFlowBloc, BookingFlowState>(
      'emits Initial after Reset',
      build: () => BookingFlowBloc(repository: repository),
      seed: () => BookingFlowSuccess(booking: _booking),
      act: (bloc) => bloc.add(const BookingFlowReset()),
      expect: () => [isA<BookingFlowInitial>()],
    );

    blocTest<BookingFlowBloc, BookingFlowState>(
      'micro package — submits without eventDate/startTime/eventLocation',
      build: () {
        when(
          () => repository.createBooking(
            talentProfileId: any(named: 'talentProfileId'),
            servicePackageId: any(named: 'servicePackageId'),
            eventDate: any(named: 'eventDate'),
            startTime: any(named: 'startTime'),
            eventLocation: any(named: 'eventLocation'),
            message: any(named: 'message'),
            isExpress: any(named: 'isExpress'),
          ),
        ).thenAnswer((_) async => ApiSuccess(_booking));
        return BookingFlowBloc(repository: repository);
      },
      act: (bloc) => bloc.add(
        const BookingFlowSubmitted(
          talentProfileId: 1,
          servicePackageId: 3,
          // No eventDate, startTime, eventLocation — micro package
        ),
      ),
      expect: () => [
        isA<BookingFlowSubmitting>(),
        isA<BookingFlowSuccess>(),
      ],
    );

    blocTest<BookingFlowBloc, BookingFlowState>(
      'passes travelCost to repository when provided',
      build: () {
        when(
          () => repository.createBooking(
            talentProfileId: any(named: 'talentProfileId'),
            servicePackageId: any(named: 'servicePackageId'),
            eventDate: any(named: 'eventDate'),
            startTime: any(named: 'startTime'),
            eventLocation: any(named: 'eventLocation'),
            message: any(named: 'message'),
            isExpress: any(named: 'isExpress'),
            travelCost: any(named: 'travelCost'),
          ),
        ).thenAnswer((_) async => ApiSuccess(_booking));
        return BookingFlowBloc(repository: repository);
      },
      act: (bloc) => bloc.add(
        const BookingFlowSubmitted(
          talentProfileId: 1,
          servicePackageId: 2,
          eventDate: '2026-06-15',
          startTime: '18:00',
          eventLocation: 'Abidjan',
          travelCost: 15000,
        ),
      ),
      expect: () => [
        isA<BookingFlowSubmitting>(),
        isA<BookingFlowSuccess>(),
      ],
      verify: (_) {
        verify(
          () => repository.createBooking(
            talentProfileId: 1,
            servicePackageId: 2,
            eventDate: '2026-06-15',
            startTime: '18:00',
            eventLocation: 'Abidjan',
            travelCost: 15000,
          ),
        ).called(1);
      },
    );
  });

  // ── BookingFlowPaymentInitiated (Paystack) ────────────────────────────────

  group('BookingFlowPaymentInitiated', () {
    const paymentEvent = BookingFlowPaymentInitiated(bookingId: 42);

    blocTest<BookingFlowBloc, BookingFlowState>(
      'emits [PaymentSubmitting, PaystackReady] on successful payment init',
      build: () {
        when(
          () => repository.initiatePayment(
            bookingId: any(named: 'bookingId'),
          ),
        ).thenAnswer(
          (_) async => const ApiSuccess(<String, dynamic>{
            'access_code': 'test_access_code',
          }),
        );
        return BookingFlowBloc(repository: repository);
      },
      act: (bloc) => bloc.add(paymentEvent),
      expect: () => [
        isA<BookingFlowPaymentSubmitting>(),
        isA<BookingFlowPaystackReady>(),
      ],
    );

    blocTest<BookingFlowBloc, BookingFlowState>(
      'emits [PaymentSubmitting, Failure] on payment API error',
      build: () {
        when(
          () => repository.initiatePayment(
            bookingId: any(named: 'bookingId'),
          ),
        ).thenAnswer(
          (_) async => const ApiFailure(
            code: 'PAYMENT_FAILED',
            message: 'Solde insuffisant',
          ),
        );
        return BookingFlowBloc(repository: repository);
      },
      act: (bloc) => bloc.add(paymentEvent),
      expect: () => [
        isA<BookingFlowPaymentSubmitting>(),
        isA<BookingFlowFailure>(),
      ],
      verify: (bloc) {
        expect(
          (bloc.state as BookingFlowFailure).code,
          equals('PAYMENT_FAILED'),
        );
      },
    );

    blocTest<BookingFlowBloc, BookingFlowState>(
      'ignores duplicate payment event when already submitting',
      build: () {
        when(
          () => repository.initiatePayment(
            bookingId: any(named: 'bookingId'),
          ),
        ).thenAnswer((_) async {
          await Future<void>.delayed(const Duration(milliseconds: 100));
          return const ApiSuccess(<String, dynamic>{
            'access_code': 'test_access_code',
          });
        });
        return BookingFlowBloc(repository: repository);
      },
      seed: () => const BookingFlowPaymentSubmitting(),
      act: (bloc) => bloc.add(paymentEvent),
      // No state changes expected — guard in _onPaymentInitiated returns early
      expect: () => <BookingFlowState>[],
    );
  });
}
