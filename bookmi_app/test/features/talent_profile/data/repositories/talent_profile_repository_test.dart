import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/storage/local_storage.dart';
import 'package:bookmi_app/features/talent_profile/data/repositories/talent_profile_repository.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockDio extends Mock implements Dio {}

class MockLocalStorage extends Mock implements LocalStorage {}

void main() {
  late MockDio mockDio;
  late MockLocalStorage mockLocalStorage;
  late TalentProfileRepository repository;

  setUp(() {
    mockDio = MockDio();
    mockLocalStorage = MockLocalStorage();
    repository = TalentProfileRepository.forTesting(
      dio: mockDio,
      localStorage: mockLocalStorage,
    );
  });

  const slug = 'dj-arafat';

  const apiResponse = {
    'data': {
      'id': 1,
      'type': 'talent_profile',
      'attributes': {
        'stage_name': 'DJ Arafat',
        'slug': 'dj-arafat',
        'bio': 'Le roi du coupé-décalé',
        'city': 'Abidjan',
        'cachet_amount': 500000,
        'average_rating': '4.50',
        'is_verified': true,
        'talent_level': 'confirme',
        'profile_completion_percentage': 60,
        'social_links': {'instagram': '@djarafat'},
        'reliability_score': 78,
        'reviews_count': 0,
        'portfolio_items': <dynamic>[],
        'service_packages': <dynamic>[
          {
            'id': 1,
            'type': 'service_package',
            'attributes': {
              'name': 'Pack Essentiel',
              'description': 'DJ set basique',
              'cachet_amount': 300000,
              'duration_minutes': 120,
              'inclusions': <dynamic>['Sound system', 'DJ set'],
              'type': 'essentiel',
              'is_active': true,
              'sort_order': 0,
            },
          },
        ],
        'recent_reviews': <dynamic>[],
        'created_at': '2026-02-17T10:00:00+00:00',
        'category': {
          'id': 1,
          'name': 'Musique',
          'slug': 'musique',
          'color_hex': '#FF5733',
        },
        'subcategory': {
          'id': 5,
          'name': 'DJ',
          'slug': 'dj',
        },
      },
    },
    'meta': {
      'similar_talents': <dynamic>[
        {
          'id': 2,
          'type': 'talent_profile',
          'attributes': {
            'stage_name': 'DJ Mix',
            'slug': 'dj-mix',
            'city': 'Abidjan',
            'cachet_amount': 400000,
            'average_rating': '4.00',
            'is_verified': true,
            'talent_level': 'nouveau',
            'category': {
              'id': 1,
              'name': 'Musique',
              'slug': 'musique',
              'color_hex': '#FF5733',
            },
          },
        },
      ],
    },
  };

  group('TalentProfileRepository', () {
    group('getTalentBySlug', () {
      test(
        'returns ApiSuccess with parsed profile and similarTalents',
        () async {
          when(
            () => mockDio.get<Map<String, dynamic>>(
              ApiEndpoints.talentDetail(slug),
            ),
          ).thenAnswer(
            (_) async => Response(
              data: apiResponse,
              statusCode: 200,
              requestOptions: RequestOptions(),
            ),
          );
          when(
            () => mockLocalStorage.put(any<String>(), any<dynamic>()),
          ).thenAnswer((_) async {});

          final result = await repository.getTalentBySlug(slug);

          expect(result, isA<ApiSuccess<TalentProfileResponse>>());
          final data = (result as ApiSuccess<TalentProfileResponse>).data;
          expect(data.profile['stage_name'], 'DJ Arafat');
          expect(data.profile['slug'], 'dj-arafat');
          expect(data.profile['is_verified'], true);
          expect(data.similarTalents.length, 1);
          expect(
            (data.similarTalents[0]['attributes']
                as Map<String, dynamic>)['stage_name'],
            'DJ Mix',
          );
        },
      );

      test('calls API with correct slug in URL', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentDetail(slug),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: apiResponse,
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.put(any<String>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await repository.getTalentBySlug(slug);

        verify(
          () => mockDio.get<Map<String, dynamic>>(
            '/talents/dj-arafat',
          ),
        ).called(1);
      });

      test('caches response after successful API call', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentDetail(slug),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: apiResponse,
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.put(any<String>(), any<dynamic>()),
        ).thenAnswer((_) async {});

        await repository.getTalentBySlug(slug);

        verify(
          () => mockLocalStorage.put('talent_profile_dj-arafat', apiResponse),
        ).called(1);
      });

      test('returns cached data on connection error with cache', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentDetail(slug),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.get<Map<dynamic, dynamic>>(any<String>()),
        ).thenReturn(
          Map<dynamic, dynamic>.from({
            'data': Map<dynamic, dynamic>.from({
              'id': 1,
              'type': 'talent_profile',
              'attributes': Map<dynamic, dynamic>.from({
                'stage_name': 'Cached DJ',
                'slug': 'dj-arafat',
              }),
            }),
            'meta': Map<dynamic, dynamic>.from({
              'similar_talents': <dynamic>[],
            }),
          }),
        );

        final result = await repository.getTalentBySlug(slug);

        expect(result, isA<ApiSuccess<TalentProfileResponse>>());
        final data = (result as ApiSuccess<TalentProfileResponse>).data;
        expect(data.profile['stage_name'], 'Cached DJ');
        expect(data.similarTalents, isEmpty);
      });

      test('returns cached data on connection timeout with cache', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentDetail(slug),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionTimeout,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.get<Map<dynamic, dynamic>>(any<String>()),
        ).thenReturn(
          Map<dynamic, dynamic>.from({
            'data': Map<dynamic, dynamic>.from({
              'id': 1,
              'type': 'talent_profile',
              'attributes': Map<dynamic, dynamic>.from({
                'stage_name': 'Cached DJ',
              }),
            }),
            'meta': Map<dynamic, dynamic>.from({
              'similar_talents': <dynamic>[],
            }),
          }),
        );

        final result = await repository.getTalentBySlug(slug);

        expect(result, isA<ApiSuccess<TalentProfileResponse>>());
      });

      test('returns ApiFailure on network error without cache', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentDetail(slug),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.get<Map<dynamic, dynamic>>(any<String>()),
        ).thenReturn(null);

        final result = await repository.getTalentBySlug(slug);

        expect(result, isA<ApiFailure<TalentProfileResponse>>());
        expect((result as ApiFailure).code, 'NETWORK_ERROR');
      });

      test('returns ApiFailure with server error details', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentDetail(slug),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.badResponse,
            requestOptions: RequestOptions(),
            response: Response(
              statusCode: 404,
              data: {
                'error': {
                  'code': 'TALENT_NOT_FOUND',
                  'message': 'Le profil talent demandé est introuvable.',
                  'status': 404,
                },
              },
              requestOptions: RequestOptions(),
            ),
          ),
        );

        final result = await repository.getTalentBySlug(slug);

        expect(result, isA<ApiFailure<TalentProfileResponse>>());
        final failure = result as ApiFailure<TalentProfileResponse>;
        expect(failure.code, 'TALENT_NOT_FOUND');
        expect(failure.message, 'Le profil talent demandé est introuvable.');
      });
    });
  });
}
