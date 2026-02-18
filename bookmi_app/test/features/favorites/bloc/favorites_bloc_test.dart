import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_bloc.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_event.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_state.dart';
import 'package:bookmi_app/features/favorites/data/repositories/favorites_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockFavoritesRepository extends Mock implements FavoritesRepository {}

void main() {
  late MockFavoritesRepository mockRepository;

  setUp(() {
    mockRepository = MockFavoritesRepository();
  });

  group('FavoritesBloc', () {
    test('initial state is FavoritesInitial', () async {
      final bloc = FavoritesBloc(repository: mockRepository);
      expect(bloc.state, isA<FavoritesInitial>());
      await bloc.close();
    });

    group('FavoritesFetched', () {
      final fakeFavorites = [
        <String, dynamic>{
          'id': 1,
          'type': 'favorite',
          'attributes': <String, dynamic>{
            'talent': <String, dynamic>{
              'id': 42,
              'type': 'talent_profile',
              'attributes': <String, dynamic>{
                'stage_name': 'DJ Test',
              },
            },
            'favorited_at': '2026-02-17T14:30:00Z',
          },
        },
      ];

      blocTest<FavoritesBloc, FavoritesState>(
        'emits [Loading, Loaded] on success',
        build: () {
          when(
            () => mockRepository.getFavorites(),
          ).thenAnswer((_) async => ApiSuccess(fakeFavorites));
          return FavoritesBloc(repository: mockRepository);
        },
        act: (bloc) => bloc.add(const FavoritesFetched()),
        expect: () => [
          isA<FavoritesLoading>(),
          isA<FavoritesLoaded>().having((s) => s.favoriteIds, 'favoriteIds', {
            42,
          }),
        ],
      );

      blocTest<FavoritesBloc, FavoritesState>(
        'emits [Loading, Error] on failure',
        build: () {
          when(() => mockRepository.getFavorites()).thenAnswer(
            (_) async => const ApiFailure(
              code: 'NETWORK_ERROR',
              message: 'No connection',
            ),
          );
          return FavoritesBloc(repository: mockRepository);
        },
        act: (bloc) => bloc.add(const FavoritesFetched()),
        expect: () => [
          isA<FavoritesLoading>(),
          isA<FavoritesError>().having(
            (s) => s.message,
            'message',
            'No connection',
          ),
        ],
      );
    });

    group('FavoriteToggled', () {
      blocTest<FavoritesBloc, FavoritesState>(
        'adds favorite with optimistic update',
        build: () {
          when(
            () => mockRepository.addFavorite(42),
          ).thenAnswer((_) async => const ApiSuccess(null));
          return FavoritesBloc(repository: mockRepository);
        },
        seed: () => const FavoritesLoaded(favoriteIds: {}),
        act: (bloc) => bloc.add(const FavoriteToggled(42)),
        expect: () => [
          isA<FavoritesLoaded>().having(
            (s) => s.favoriteIds.contains(42),
            'contains 42',
            true,
          ),
        ],
        verify: (_) {
          verify(() => mockRepository.addFavorite(42)).called(1);
        },
      );

      blocTest<FavoritesBloc, FavoritesState>(
        'removes favorite with optimistic update',
        build: () {
          when(
            () => mockRepository.removeFavorite(42),
          ).thenAnswer((_) async => const ApiSuccess(null));
          return FavoritesBloc(repository: mockRepository);
        },
        seed: () => const FavoritesLoaded(favoriteIds: {42}),
        act: (bloc) => bloc.add(const FavoriteToggled(42)),
        expect: () => [
          isA<FavoritesLoaded>().having(
            (s) => s.favoriteIds.contains(42),
            'contains 42',
            false,
          ),
        ],
        verify: (_) {
          verify(() => mockRepository.removeFavorite(42)).called(1);
        },
      );

      blocTest<FavoritesBloc, FavoritesState>(
        'rolls back on API failure',
        build: () {
          when(() => mockRepository.addFavorite(42)).thenAnswer(
            (_) async => const ApiFailure(
              code: 'NETWORK_ERROR',
              message: 'Failed',
            ),
          );
          return FavoritesBloc(repository: mockRepository);
        },
        seed: () => const FavoritesLoaded(favoriteIds: {}),
        act: (bloc) => bloc.add(const FavoriteToggled(42)),
        expect: () => [
          // Optimistic add
          isA<FavoritesLoaded>().having(
            (s) => s.favoriteIds.contains(42),
            'contains 42',
            true,
          ),
          // Rollback
          isA<FavoritesLoaded>().having(
            (s) => s.favoriteIds.contains(42),
            'contains 42',
            false,
          ),
        ],
      );

      blocTest<FavoritesBloc, FavoritesState>(
        'does nothing when state is not FavoritesLoaded',
        build: () => FavoritesBloc(repository: mockRepository),
        act: (bloc) => bloc.add(const FavoriteToggled(42)),
        expect: () => <FavoritesState>[],
      );
    });

    group('FavoriteStatusChecked', () {
      blocTest<FavoritesBloc, FavoritesState>(
        'adds talent ID when is_favorite is true',
        build: () {
          when(
            () => mockRepository.isFavorite(42),
          ).thenAnswer((_) async => const ApiSuccess(true));
          return FavoritesBloc(repository: mockRepository);
        },
        seed: () => const FavoritesLoaded(favoriteIds: {}),
        act: (bloc) => bloc.add(const FavoriteStatusChecked(42)),
        expect: () => [
          isA<FavoritesLoaded>().having(
            (s) => s.favoriteIds.contains(42),
            'contains 42',
            true,
          ),
        ],
      );

      blocTest<FavoritesBloc, FavoritesState>(
        'removes talent ID when is_favorite is false',
        build: () {
          when(
            () => mockRepository.isFavorite(42),
          ).thenAnswer((_) async => const ApiSuccess(false));
          return FavoritesBloc(repository: mockRepository);
        },
        seed: () => const FavoritesLoaded(favoriteIds: {42}),
        act: (bloc) => bloc.add(const FavoriteStatusChecked(42)),
        expect: () => [
          isA<FavoritesLoaded>().having(
            (s) => s.favoriteIds.contains(42),
            'contains 42',
            false,
          ),
        ],
      );
    });
  });
}
