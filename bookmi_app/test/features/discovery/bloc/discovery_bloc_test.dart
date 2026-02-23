import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_bloc.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_event.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_state.dart';
import 'package:bookmi_app/features/discovery/data/repositories/discovery_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockDiscoveryRepository extends Mock implements DiscoveryRepository {}

void main() {
  late MockDiscoveryRepository mockRepository;

  setUp(() {
    mockRepository = MockDiscoveryRepository();
    // Stub getCategories() to return an empty list by default.
    when(() => mockRepository.getCategories()).thenAnswer(
      (_) async => const ApiSuccess(<Map<String, dynamic>>[]),
    );
  });

  DiscoveryBloc buildBloc() => DiscoveryBloc(repository: mockRepository);

  const talentA = {
    'id': 1,
    'type': 'talent_profile',
    'attributes': {
      'stage_name': 'DJ Alpha',
      'city': 'Abidjan',
      'cachet_amount': 10000000,
      'average_rating': '4.5',
      'is_verified': true,
      'photo_url': 'https://example.com/photo1.jpg',
      'category': {'id': 1, 'name': 'DJ', 'slug': 'dj'},
    },
  };

  const talentB = {
    'id': 2,
    'type': 'talent_profile',
    'attributes': {
      'stage_name': 'MC Bravo',
      'city': 'Bouaké',
      'cachet_amount': 5000000,
      'average_rating': '3.8',
      'is_verified': false,
      'photo_url': 'https://example.com/photo2.jpg',
      'category': {'id': 2, 'name': 'MC', 'slug': 'mc-animateur'},
    },
  };

  const successResponse = TalentListResponse(
    talents: [talentA],
    nextCursor: 'cursor_abc',
    hasMore: true,
    total: 10,
  );

  const secondPageResponse = TalentListResponse(
    talents: [talentB],
    nextCursor: null,
    hasMore: false,
    total: 10,
  );

  group('DiscoveryBloc', () {
    test('initial state is DiscoveryInitial', () async {
      final bloc = buildBloc();
      expect(bloc.state, isA<DiscoveryInitial>());
      await bloc.close();
    });

    group('DiscoveryFetched', () {
      blocTest<DiscoveryBloc, DiscoveryState>(
        'emits [Loading, Loaded] when fetch succeeds',
        build: () {
          when(
            () => mockRepository.getTalents(
              cursor: any(named: 'cursor'),
              perPage: any(named: 'perPage'),
              filters: any(named: 'filters'),
            ),
          ).thenAnswer(
            (_) async => const ApiSuccess(successResponse),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(const DiscoveryFetched()),
        expect: () => [
          isA<DiscoveryLoading>(),
          isA<DiscoveryLoaded>()
              .having((s) => s.talents.length, 'talents count', 1)
              .having((s) => s.hasMore, 'hasMore', true)
              .having(
                (s) => s.nextCursor,
                'nextCursor',
                'cursor_abc',
              )
              .having(
                (s) => s.activeFilters,
                'activeFilters',
                isEmpty,
              ),
        ],
      );

      blocTest<DiscoveryBloc, DiscoveryState>(
        'emits [Loading, Failure] when fetch fails',
        build: () {
          when(
            () => mockRepository.getTalents(
              cursor: any(named: 'cursor'),
              perPage: any(named: 'perPage'),
              filters: any(named: 'filters'),
            ),
          ).thenAnswer(
            (_) async => const ApiFailure(
              code: 'NETWORK_ERROR',
              message: 'Erreur réseau',
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(const DiscoveryFetched()),
        expect: () => [
          isA<DiscoveryLoading>(),
          isA<DiscoveryFailure>().having(
            (s) => s.code,
            'code',
            'NETWORK_ERROR',
          ),
        ],
      );
    });

    group('DiscoveryNextPageFetched', () {
      blocTest<DiscoveryBloc, DiscoveryState>(
        'emits [LoadingMore, Loaded] with concatenated talents',
        build: () {
          when(
            () => mockRepository.getTalents(
              cursor: any(named: 'cursor'),
              perPage: any(named: 'perPage'),
              filters: any(named: 'filters'),
            ),
          ).thenAnswer(
            (_) async => const ApiSuccess(secondPageResponse),
          );
          return buildBloc();
        },
        seed: () => const DiscoveryLoaded(
          talents: [talentA],
          hasMore: true,
          nextCursor: 'cursor_abc',
          activeFilters: {},
        ),
        act: (bloc) => bloc.add(const DiscoveryNextPageFetched()),
        expect: () => [
          isA<DiscoveryLoadingMore>(),
          isA<DiscoveryLoaded>()
              .having(
                (s) => s.talents.length,
                'talents count',
                2,
              )
              .having((s) => s.hasMore, 'hasMore', false),
        ],
      );

      blocTest<DiscoveryBloc, DiscoveryState>(
        'reverts to Loaded on pagination failure (preserves data)',
        build: () {
          when(
            () => mockRepository.getTalents(
              cursor: any(named: 'cursor'),
              perPage: any(named: 'perPage'),
              filters: any(named: 'filters'),
            ),
          ).thenAnswer(
            (_) async => const ApiFailure(
              code: 'NETWORK_ERROR',
              message: 'Erreur réseau',
            ),
          );
          return buildBloc();
        },
        seed: () => const DiscoveryLoaded(
          talents: [talentA],
          hasMore: true,
          nextCursor: 'cursor_abc',
          activeFilters: {},
        ),
        act: (bloc) => bloc.add(const DiscoveryNextPageFetched()),
        expect: () => [
          isA<DiscoveryLoadingMore>(),
          isA<DiscoveryLoaded>()
              .having(
                (s) => s.talents.length,
                'talents count',
                1,
              )
              .having((s) => s.hasMore, 'hasMore', true),
        ],
      );

      blocTest<DiscoveryBloc, DiscoveryState>(
        'is ignored when already in LoadingMore state',
        build: buildBloc,
        seed: () => const DiscoveryLoadingMore(
          talents: [talentA],
          hasMore: true,
          nextCursor: 'cursor_abc',
          activeFilters: {},
        ),
        act: (bloc) => bloc.add(const DiscoveryNextPageFetched()),
        expect: () => <DiscoveryState>[],
      );

      blocTest<DiscoveryBloc, DiscoveryState>(
        'is ignored when not in Loaded state',
        build: buildBloc,
        act: (bloc) => bloc.add(const DiscoveryNextPageFetched()),
        expect: () => <DiscoveryState>[],
      );
    });

    group('DiscoveryFiltersChanged', () {
      blocTest<DiscoveryBloc, DiscoveryState>(
        'emits [Loading, Loaded] with filters applied',
        build: () {
          when(
            () => mockRepository.getTalents(
              cursor: any(named: 'cursor'),
              perPage: any(named: 'perPage'),
              filters: any(named: 'filters'),
            ),
          ).thenAnswer(
            (_) async => const ApiSuccess(successResponse),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(
          const DiscoveryFiltersChanged(
            filters: {'category': 'dj'},
          ),
        ),
        expect: () => [
          isA<DiscoveryLoading>(),
          isA<DiscoveryLoaded>().having(
            (s) => s.activeFilters,
            'activeFilters',
            {'category': 'dj'},
          ),
        ],
      );
    });

    group('DiscoveryFilterCleared', () {
      blocTest<DiscoveryBloc, DiscoveryState>(
        'emits [Loading, Loaded] with empty filters',
        build: () {
          when(
            () => mockRepository.getTalents(
              cursor: any(named: 'cursor'),
              perPage: any(named: 'perPage'),
              filters: any(named: 'filters'),
            ),
          ).thenAnswer(
            (_) async => const ApiSuccess(successResponse),
          );
          return buildBloc();
        },
        seed: () => const DiscoveryLoaded(
          talents: [talentA],
          hasMore: true,
          nextCursor: 'cursor_abc',
          activeFilters: {'category': 'dj'},
        ),
        act: (bloc) => bloc.add(const DiscoveryFilterCleared()),
        expect: () => [
          isA<DiscoveryLoading>(),
          isA<DiscoveryLoaded>().having(
            (s) => s.activeFilters,
            'activeFilters',
            isEmpty,
          ),
        ],
      );
    });
  });
}
