import 'package:bookmi_app/features/favorites/data/local/favorites_local_source.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive_ce/hive.dart';
import 'package:mocktail/mocktail.dart';

class MockBox extends Mock implements Box<dynamic> {}

void main() {
  late MockBox mockBox;
  late FavoritesLocalSource localSource;

  setUp(() {
    mockBox = MockBox();
    localSource = FavoritesLocalSource(box: mockBox);
  });

  group('FavoritesLocalSource', () {
    group('cacheFavoriteIds', () {
      test('stores IDs replacing existing cache', () async {
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.cacheFavoriteIds([1, 2, 3]);

        verify(() => mockBox.put('favorite_ids', [1, 2, 3])).called(1);
      });

      test('appends IDs to existing cache when append is true', () async {
        when(() => mockBox.get('favorite_ids')).thenReturn(<dynamic>[1, 2]);
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.cacheFavoriteIds([3, 4], append: true);

        verify(
          () => mockBox.put(
            'favorite_ids',
            any<dynamic>(
              that: isA<List<dynamic>>()
                  .having((l) => l.length, 'length', 4)
                  .having((l) => l.toSet(), 'unique', {1, 2, 3, 4}),
            ),
          ),
        ).called(1);
      });

      test('creates cache from scratch when append with no existing', () async {
        when(() => mockBox.get('favorite_ids')).thenReturn(null);
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.cacheFavoriteIds([5, 6], append: true);

        verify(
          () => mockBox.put(
            'favorite_ids',
            any<dynamic>(
              that: isA<List<dynamic>>().having((l) => l.toSet(), 'ids', {
                5,
                6,
              }),
            ),
          ),
        ).called(1);
      });
    });

    group('getCachedFavoriteIds', () {
      test('returns null when no cache exists', () {
        when(() => mockBox.get('favorite_ids')).thenReturn(null);

        expect(localSource.getCachedFavoriteIds(), isNull);
      });

      test('returns cached IDs as List<int>', () {
        when(() => mockBox.get('favorite_ids')).thenReturn(<dynamic>[1, 2, 3]);

        final result = localSource.getCachedFavoriteIds();

        expect(result, [1, 2, 3]);
        expect(result, isA<List<int>>());
      });
    });

    group('isFavorite', () {
      test('returns null when no cache exists', () {
        when(() => mockBox.get('favorite_ids')).thenReturn(null);

        expect(localSource.isFavorite(42), isNull);
      });

      test('returns true when ID is in cache', () {
        when(
          () => mockBox.get('favorite_ids'),
        ).thenReturn(<dynamic>[10, 42, 99]);

        expect(localSource.isFavorite(42), isTrue);
      });

      test('returns false when ID is not in cache', () {
        when(() => mockBox.get('favorite_ids')).thenReturn(<dynamic>[10, 99]);

        expect(localSource.isFavorite(42), isFalse);
      });
    });

    group('addFavoriteId', () {
      test('adds ID to existing cache', () async {
        when(() => mockBox.get('favorite_ids')).thenReturn(<dynamic>[1, 2]);
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.addFavoriteId(3);

        verify(() => mockBox.put('favorite_ids', [1, 2, 3])).called(1);
      });

      test('creates cache with single ID when empty', () async {
        when(() => mockBox.get('favorite_ids')).thenReturn(null);
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.addFavoriteId(42);

        verify(() => mockBox.put('favorite_ids', [42])).called(1);
      });

      test('does not duplicate existing ID', () async {
        when(() => mockBox.get('favorite_ids')).thenReturn(<dynamic>[1, 42]);

        await localSource.addFavoriteId(42);

        verifyNever(() => mockBox.put(any<dynamic>(), any<dynamic>()));
      });
    });

    group('removeFavoriteId', () {
      test('removes ID from cache', () async {
        when(() => mockBox.get('favorite_ids')).thenReturn(<dynamic>[1, 42, 3]);
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.removeFavoriteId(42);

        verify(() => mockBox.put('favorite_ids', [1, 3])).called(1);
      });

      test('handles removal from empty cache', () async {
        when(() => mockBox.get('favorite_ids')).thenReturn(null);
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.removeFavoriteId(42);

        verify(() => mockBox.put('favorite_ids', <int>[])).called(1);
      });
    });

    group('queuePendingAction', () {
      test('adds action to pending queue', () async {
        when(() => mockBox.get('pending_actions')).thenReturn(null);
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.queuePendingAction({'type': 'add', 'talentId': 42});

        verify(
          () => mockBox.put(
            'pending_actions',
            [
              {'type': 'add', 'talentId': 42},
            ],
          ),
        ).called(1);
      });

      test('appends to existing queue', () async {
        when(() => mockBox.get('pending_actions')).thenReturn(<dynamic>[
          <dynamic, dynamic>{'type': 'add', 'talentId': 10},
        ]);
        when(
          () => mockBox.put(any<dynamic>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await localSource.queuePendingAction({
          'type': 'remove',
          'talentId': 42,
        });

        verify(
          () => mockBox.put(
            'pending_actions',
            any<dynamic>(
              that: isA<List<dynamic>>().having((l) => l.length, 'length', 2),
            ),
          ),
        ).called(1);
      });
    });

    group('getPendingActions', () {
      test('returns empty list when no actions queued', () {
        when(() => mockBox.get('pending_actions')).thenReturn(null);

        expect(localSource.getPendingActions(), isEmpty);
      });

      test('returns queued actions as typed list', () {
        when(() => mockBox.get('pending_actions')).thenReturn(<dynamic>[
          <dynamic, dynamic>{'type': 'add', 'talentId': 42},
          <dynamic, dynamic>{'type': 'remove', 'talentId': 10},
        ]);

        final actions = localSource.getPendingActions();

        expect(actions, hasLength(2));
        expect(actions[0]['type'], 'add');
        expect(actions[0]['talentId'], 42);
        expect(actions[1]['type'], 'remove');
      });
    });

    group('clearPendingActions', () {
      test('deletes pending actions key', () async {
        when(() => mockBox.delete(any<dynamic>())).thenAnswer((_) async {});

        await localSource.clearPendingActions();

        verify(() => mockBox.delete('pending_actions')).called(1);
      });
    });

    group('clear', () {
      test('deletes both keys', () async {
        when(() => mockBox.delete(any<dynamic>())).thenAnswer((_) async {});

        await localSource.clear();

        verify(() => mockBox.delete('favorite_ids')).called(1);
        verify(() => mockBox.delete('pending_actions')).called(1);
      });
    });
  });
}
