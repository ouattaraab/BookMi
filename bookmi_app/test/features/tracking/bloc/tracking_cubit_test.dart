import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/tracking/bloc/tracking_cubit.dart';
import 'package:bookmi_app/features/tracking/bloc/tracking_state.dart';
import 'package:bookmi_app/features/tracking/data/models/tracking_event_model.dart';
import 'package:bookmi_app/features/tracking/data/repositories/tracking_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class _MockTrackingRepository extends Mock implements TrackingRepository {}

void main() {
  late _MockTrackingRepository repository;
  const bookingId = 1;

  final fakeEvent = TrackingEventModel(
    id: 1,
    bookingRequestId: bookingId,
    status: 'preparing',
    statusLabel: 'En préparation',
    occurredAt: DateTime(2026, 2, 19, 10),
  );

  final fakeEnRoute = TrackingEventModel(
    id: 2,
    bookingRequestId: bookingId,
    status: 'en_route',
    statusLabel: 'En route',
    occurredAt: DateTime(2026, 2, 19, 11),
  );

  final fakeCompleted = TrackingEventModel(
    id: 5,
    bookingRequestId: bookingId,
    status: 'completed',
    statusLabel: 'Terminé',
    occurredAt: DateTime(2026, 2, 19, 15),
  );

  setUp(() {
    repository = _MockTrackingRepository();
  });

  group('TrackingCubit', () {
    test('initial state is TrackingInitial', () {
      expect(
        TrackingCubit(repository: repository).state,
        isA<TrackingInitial>(),
      );
    });

    // ── loadEvents ─────────────────────────────────────────────────────────────

    blocTest<TrackingCubit, TrackingState>(
      'emits [Loading, Loaded] on successful loadEvents',
      build: () {
        when(() => repository.getTrackingEvents(bookingId)).thenAnswer(
          (_) async => ApiSuccess([fakeEvent]),
        );
        return TrackingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadEvents(bookingId),
      expect: () => [
        isA<TrackingLoading>(),
        isA<TrackingLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as TrackingLoaded;
        expect(loaded.events, hasLength(1));
        expect(loaded.events.first.status, equals('preparing'));
        expect(loaded.currentStatus, equals('preparing'));
        expect(loaded.isCompleted, isFalse);
      },
    );

    blocTest<TrackingCubit, TrackingState>(
      'emits [Loading, Error] when getTrackingEvents fails',
      build: () {
        when(() => repository.getTrackingEvents(bookingId)).thenAnswer(
          (_) async => const ApiFailure(
            code: 'NOT_FOUND',
            message: 'Réservation introuvable',
          ),
        );
        return TrackingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadEvents(bookingId),
      expect: () => [
        isA<TrackingLoading>(),
        isA<TrackingError>(),
      ],
      verify: (cubit) {
        expect(
          (cubit.state as TrackingError).message,
          equals('Réservation introuvable'),
        );
      },
    );

    // ── postUpdate ─────────────────────────────────────────────────────────────

    blocTest<TrackingCubit, TrackingState>(
      'emits [Updating, Loaded] on successful postUpdate',
      build: () {
        when(() => repository.getTrackingEvents(bookingId)).thenAnswer(
          (_) async => ApiSuccess([fakeEvent]),
        );
        when(
          () => repository.postTrackingUpdate(
            bookingId,
            status: 'en_route',
          ),
        ).thenAnswer((_) async => ApiSuccess(fakeEnRoute));
        return TrackingCubit(repository: repository);
      },
      act: (cubit) async {
        await cubit.loadEvents(bookingId);
        await cubit.postUpdate(bookingId, 'en_route');
      },
      expect: () => [
        isA<TrackingLoading>(),
        isA<TrackingLoaded>(),
        isA<TrackingUpdating>(),
        isA<TrackingLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as TrackingLoaded;
        expect(loaded.events, hasLength(2));
        expect(loaded.currentStatus, equals('en_route'));
      },
    );

    blocTest<TrackingCubit, TrackingState>(
      'reverts to Loaded on postUpdate failure',
      build: () {
        when(() => repository.getTrackingEvents(bookingId)).thenAnswer(
          (_) async => ApiSuccess([fakeEvent]),
        );
        when(
          () => repository.postTrackingUpdate(
            bookingId,
            status: 'arrived',
          ),
        ).thenAnswer(
          (_) async => const ApiFailure(
            code: 'INVALID_TRANSITION',
            message: 'Transition invalide',
          ),
        );
        return TrackingCubit(repository: repository);
      },
      act: (cubit) async {
        await cubit.loadEvents(bookingId);
        await cubit.postUpdate(bookingId, 'arrived');
      },
      expect: () => [
        isA<TrackingLoading>(),
        isA<TrackingLoaded>(),
        isA<TrackingUpdating>(),
        isA<TrackingLoaded>(), // reverted
      ],
      verify: (cubit) {
        // Events list is unchanged (only preparing)
        final loaded = cubit.state as TrackingLoaded;
        expect(loaded.events, hasLength(1));
        expect(loaded.currentStatus, equals('preparing'));
      },
    );

    // ── checkIn ────────────────────────────────────────────────────────────────

    blocTest<TrackingCubit, TrackingState>(
      'emits [Updating, Loaded] on successful checkIn',
      build: () {
        when(() => repository.getTrackingEvents(bookingId)).thenAnswer(
          (_) async => ApiSuccess([fakeEvent, fakeEnRoute]),
        );
        when(
          () => repository.checkIn(
            bookingId,
            latitude: any(named: 'latitude'),
            longitude: any(named: 'longitude'),
          ),
        ).thenAnswer(
          (_) async => ApiSuccess(
            TrackingEventModel(
              id: 3,
              bookingRequestId: bookingId,
              status: 'arrived',
              statusLabel: 'Arrivé',
              latitude: 5.354,
              longitude: -4.002,
              occurredAt: DateTime(2026, 2, 19, 12),
            ),
          ),
        );
        return TrackingCubit(repository: repository);
      },
      act: (cubit) async {
        await cubit.loadEvents(bookingId);
        await cubit.checkIn(bookingId, latitude: 5.354, longitude: -4.002);
      },
      expect: () => [
        isA<TrackingLoading>(),
        isA<TrackingLoaded>(),
        isA<TrackingUpdating>(),
        isA<TrackingLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as TrackingLoaded;
        expect(loaded.events, hasLength(3));
        expect(loaded.currentStatus, equals('arrived'));
        expect(loaded.events.last.latitude, closeTo(5.354, 0.001));
      },
    );

    // ── isCompleted getter ─────────────────────────────────────────────────────

    blocTest<TrackingCubit, TrackingState>(
      'isCompleted is true when last event is completed',
      build: () {
        when(() => repository.getTrackingEvents(bookingId)).thenAnswer(
          (_) async => ApiSuccess([fakeCompleted]),
        );
        return TrackingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadEvents(bookingId),
      verify: (cubit) {
        final loaded = cubit.state as TrackingLoaded;
        expect(loaded.isCompleted, isTrue);
      },
    );
  });
}
