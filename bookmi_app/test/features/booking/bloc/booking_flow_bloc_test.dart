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
    eventLocation: 'Abidjan',
  );

  final _booking = BookingModel(
    id: 42,
    status: 'pending',
    clientName: 'Test Client',
    talentStageName: 'DJ Alpha',
    packageName: 'Pack Standard',
    packageType: 'standard',
    eventDate: '2026-06-15',
    eventLocation: 'Abidjan',
    cachetAmount: 100000,
    commissionAmount: 15000,
    totalAmount: 115000,
    isExpress: false,
    contractAvailable: false,
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
  });
}
