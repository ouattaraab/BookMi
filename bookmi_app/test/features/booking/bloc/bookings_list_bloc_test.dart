import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_bloc.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_event.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_state.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class _MockBookingRepository extends Mock implements BookingRepository {}

BookingModel _makeBooking(int id) => BookingModel(
  id: id,
  status: 'pending',
  clientName: 'Client',
  talentStageName: 'Talent $id',
  packageName: 'Pack',
  packageType: 'standard',
  eventDate: '2026-06-01',
  eventLocation: 'Abidjan',
  cachetAmount: 50000,
  commissionAmount: 7500,
  totalAmount: 57500,
  isExpress: false,
  contractAvailable: false,
);

void main() {
  late _MockBookingRepository repository;

  setUp(() {
    repository = _MockBookingRepository();
  });

  group('BookingsListBloc', () {
    test('initial state is BookingsListInitial', () {
      expect(
        BookingsListBloc(repository: repository).state,
        isA<BookingsListInitial>(),
      );
    });

    blocTest<BookingsListBloc, BookingsListState>(
      'emits [Loading, Loaded] on successful fetch',
      build: () {
        when(
          () => repository.getBookings(
            status: any(named: 'status'),
            cursor: any(named: 'cursor'),
          ),
        ).thenAnswer(
          (_) async => ApiSuccess(
            BookingListResponse(
              bookings: [_makeBooking(1), _makeBooking(2)],
              nextCursor: null,
              hasMore: false,
            ),
          ),
        );
        return BookingsListBloc(repository: repository);
      },
      act: (bloc) => bloc.add(const BookingsListFetched()),
      expect: () => [
        isA<BookingsListLoading>(),
        isA<BookingsListLoaded>(),
      ],
      verify: (bloc) {
        final state = bloc.state as BookingsListLoaded;
        expect(state.bookings.length, equals(2));
        expect(state.hasMore, isFalse);
        expect(state.activeStatus, isNull);
      },
    );

    blocTest<BookingsListBloc, BookingsListState>(
      'emits [Loading, Failure] on API error',
      build: () {
        when(
          () => repository.getBookings(
            status: any(named: 'status'),
            cursor: any(named: 'cursor'),
          ),
        ).thenAnswer(
          (_) async => const ApiFailure(
            code: 'NETWORK_ERROR',
            message: 'Erreur réseau',
          ),
        );
        return BookingsListBloc(repository: repository);
      },
      act: (bloc) => bloc.add(const BookingsListFetched()),
      expect: () => [
        isA<BookingsListLoading>(),
        isA<BookingsListFailure>(),
      ],
    );

    blocTest<BookingsListBloc, BookingsListState>(
      'emits [LoadingMore, Loaded with merged data] on next page',
      build: () {
        var callCount = 0;
        when(
          () => repository.getBookings(
            status: any(named: 'status'),
            cursor: any(named: 'cursor'),
          ),
        ).thenAnswer((_) async {
          callCount++;
          if (callCount == 1) {
            return ApiSuccess(
              BookingListResponse(
                bookings: [_makeBooking(1)],
                nextCursor: 'cursor_abc',
                hasMore: true,
              ),
            );
          }
          return ApiSuccess(
            BookingListResponse(
              bookings: [_makeBooking(2)],
              nextCursor: null,
              hasMore: false,
            ),
          );
        });
        return BookingsListBloc(repository: repository);
      },
      act: (bloc) async {
        bloc.add(const BookingsListFetched());
        await Future<void>.delayed(Duration.zero);
        bloc.add(const BookingsListNextPageFetched());
      },
      expect: () => [
        isA<BookingsListLoading>(),
        isA<BookingsListLoaded>(),
        isA<BookingsListLoadingMore>(),
        isA<BookingsListLoaded>(),
      ],
      verify: (bloc) {
        final state = bloc.state as BookingsListLoaded;
        expect(state.bookings.length, equals(2));
        expect(state.hasMore, isFalse);
      },
    );

    blocTest<BookingsListBloc, BookingsListState>(
      'preserves existing data when next page fails',
      build: () {
        var callCount = 0;
        when(
          () => repository.getBookings(
            status: any(named: 'status'),
            cursor: any(named: 'cursor'),
          ),
        ).thenAnswer((_) async {
          callCount++;
          if (callCount == 1) {
            return ApiSuccess(
              BookingListResponse(
                bookings: [_makeBooking(1)],
                nextCursor: 'cursor_xyz',
                hasMore: true,
              ),
            );
          }
          return const ApiFailure(
            code: 'NETWORK_ERROR',
            message: 'Timeout',
          );
        });
        return BookingsListBloc(repository: repository);
      },
      act: (bloc) async {
        bloc.add(const BookingsListFetched());
        await Future<void>.delayed(Duration.zero);
        bloc.add(const BookingsListNextPageFetched());
      },
      expect: () => [
        isA<BookingsListLoading>(),
        isA<BookingsListLoaded>(),
        isA<BookingsListLoadingMore>(),
        isA<BookingsListLoaded>(), // reverted, original data preserved
      ],
      verify: (bloc) {
        final state = bloc.state as BookingsListLoaded;
        expect(state.bookings.length, equals(1));
        expect(state.hasMore, isTrue);
      },
    );

    blocTest<BookingsListBloc, BookingsListState>(
      'ignores NextPage when hasMore is false',
      build: () {
        when(
          () => repository.getBookings(
            status: any(named: 'status'),
            cursor: any(named: 'cursor'),
          ),
        ).thenAnswer(
          (_) async => ApiSuccess(
            BookingListResponse(
              bookings: [_makeBooking(1)],
              nextCursor: null,
              hasMore: false,
            ),
          ),
        );
        return BookingsListBloc(repository: repository);
      },
      act: (bloc) async {
        bloc.add(const BookingsListFetched());
        await Future<void>.delayed(Duration.zero);
        bloc.add(const BookingsListNextPageFetched());
      },
      expect: () => [
        isA<BookingsListLoading>(),
        isA<BookingsListLoaded>(),
        // no LoadingMore — hasMore is false
      ],
      verify: (_) {
        verify(
          () => repository.getBookings(
            status: any(named: 'status'),
            cursor: any(named: 'cursor'),
          ),
        ).called(1);
      },
    );
  });
}
