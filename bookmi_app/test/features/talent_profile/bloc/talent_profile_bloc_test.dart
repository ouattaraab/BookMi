import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_bloc.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_event.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_state.dart';
import 'package:bookmi_app/features/talent_profile/data/repositories/talent_profile_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockTalentProfileRepository extends Mock
    implements TalentProfileRepository {}

void main() {
  late MockTalentProfileRepository mockRepository;

  setUp(() {
    mockRepository = MockTalentProfileRepository();
  });

  TalentProfileBloc buildBloc() =>
      TalentProfileBloc(repository: mockRepository);

  const sampleProfile = {
    'stage_name': 'DJ Arafat',
    'slug': 'dj-arafat',
    'bio': 'Le roi du coupé-décalé',
    'city': 'Abidjan',
    'cachet_amount': 500000,
    'average_rating': '4.50',
    'is_verified': true,
    'talent_level': 'confirme',
    'reliability_score': 78,
    'reviews_count': 0,
    'portfolio_items': <dynamic>[],
    'service_packages': <dynamic>[],
    'recent_reviews': <dynamic>[],
    'category': {'id': 1, 'name': 'Musique', 'slug': 'musique'},
  };

  const sampleSimilar = [
    {
      'id': 2,
      'type': 'talent_profile',
      'attributes': {
        'stage_name': 'DJ Mix',
        'slug': 'dj-mix',
        'city': 'Abidjan',
      },
    },
  ];

  group('TalentProfileBloc', () {
    test('initial state is TalentProfileInitial', () async {
      final bloc = buildBloc();
      expect(bloc.state, isA<TalentProfileInitial>());
      await bloc.close();
    });

    group('TalentProfileFetched', () {
      blocTest<TalentProfileBloc, TalentProfileState>(
        'emits [Loading, Loaded] when fetch succeeds',
        build: () {
          when(() => mockRepository.getTalentBySlug('dj-arafat')).thenAnswer(
            (_) async => const ApiSuccess(
              TalentProfileResponse(
                profile: sampleProfile,
                similarTalents: sampleSimilar,
              ),
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(const TalentProfileFetched(slug: 'dj-arafat')),
        expect: () => [
          isA<TalentProfileLoading>(),
          isA<TalentProfileLoaded>()
              .having(
                (s) => s.profile['stage_name'],
                'stage_name',
                'DJ Arafat',
              )
              .having(
                (s) => s.similarTalents.length,
                'similar count',
                1,
              ),
        ],
      );

      blocTest<TalentProfileBloc, TalentProfileState>(
        'emits [Loading, Failure] when fetch fails',
        build: () {
          when(() => mockRepository.getTalentBySlug('dj-arafat')).thenAnswer(
            (_) async => const ApiFailure(
              code: 'TALENT_NOT_FOUND',
              message: 'Le profil talent demandé est introuvable.',
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(const TalentProfileFetched(slug: 'dj-arafat')),
        expect: () => [
          isA<TalentProfileLoading>(),
          isA<TalentProfileFailure>()
              .having((s) => s.code, 'code', 'TALENT_NOT_FOUND')
              .having(
                (s) => s.message,
                'message',
                'Le profil talent demandé est introuvable.',
              ),
        ],
      );

      blocTest<TalentProfileBloc, TalentProfileState>(
        'returned data contains profile and similarTalents',
        build: () {
          when(() => mockRepository.getTalentBySlug('dj-arafat')).thenAnswer(
            (_) async => const ApiSuccess(
              TalentProfileResponse(
                profile: sampleProfile,
                similarTalents: sampleSimilar,
              ),
            ),
          );
          return buildBloc();
        },
        act: (bloc) => bloc.add(const TalentProfileFetched(slug: 'dj-arafat')),
        expect: () => [
          isA<TalentProfileLoading>(),
          isA<TalentProfileLoaded>()
              .having(
                (s) => s.profile['slug'],
                'slug',
                'dj-arafat',
              )
              .having(
                (s) => s.profile['is_verified'],
                'is_verified',
                true,
              )
              .having(
                (s) => s.similarTalents,
                'similarTalents',
                isNotEmpty,
              ),
        ],
      );

      blocTest<TalentProfileBloc, TalentProfileState>(
        'is ignored when already loading',
        build: buildBloc,
        seed: () => const TalentProfileLoading(),
        act: (bloc) => bloc.add(const TalentProfileFetched(slug: 'dj-arafat')),
        expect: () => <TalentProfileState>[],
      );
    });

    group('TalentProfileRefreshed', () {
      blocTest<TalentProfileBloc, TalentProfileState>(
        'emits [Loading, Loaded] on refresh (force re-fetch)',
        build: () {
          when(() => mockRepository.getTalentBySlug('dj-arafat')).thenAnswer(
            (_) async => const ApiSuccess(
              TalentProfileResponse(
                profile: sampleProfile,
                similarTalents: sampleSimilar,
              ),
            ),
          );
          return buildBloc();
        },
        act: (bloc) async {
          bloc.add(const TalentProfileFetched(slug: 'dj-arafat'));
          await Future<void>.delayed(Duration.zero);
          bloc.add(const TalentProfileRefreshed());
        },
        expect: () => [
          isA<TalentProfileLoading>(),
          isA<TalentProfileLoaded>(),
          isA<TalentProfileLoading>(),
          isA<TalentProfileLoaded>(),
        ],
      );

      blocTest<TalentProfileBloc, TalentProfileState>(
        'is ignored when no slug was previously fetched',
        build: buildBloc,
        act: (bloc) => bloc.add(const TalentProfileRefreshed()),
        expect: () => <TalentProfileState>[],
      );
    });
  });
}
