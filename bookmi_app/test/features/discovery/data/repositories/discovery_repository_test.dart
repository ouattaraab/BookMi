import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/storage/local_storage.dart';
import 'package:bookmi_app/features/discovery/data/repositories/discovery_repository.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockDio extends Mock implements Dio {}

class MockLocalStorage extends Mock implements LocalStorage {}

void main() {
  late MockDio mockDio;
  late MockLocalStorage mockLocalStorage;
  late DiscoveryRepository repository;

  setUp(() {
    mockDio = MockDio();
    mockLocalStorage = MockLocalStorage();
    repository = DiscoveryRepository.forTesting(
      dio: mockDio,
      localStorage: mockLocalStorage,
    );
  });

  const apiResponse = {
    'data': [
      {
        'id': 42,
        'type': 'talent_profile',
        'attributes': {
          'stage_name': 'DJ Kerozen',
          'city': 'Abidjan',
          'cachet_amount': 15000000,
          'average_rating': '4.50',
          'is_verified': true,
          'photo_url': 'https://cdn.bookmi.ci/photo.webp',
          'category': {'id': 3, 'name': 'DJ', 'slug': 'dj'},
        },
      },
    ],
    'meta': {
      'next_cursor': 'eyJpZCI6NDN9',
      'prev_cursor': null,
      'per_page': 20,
      'has_more': true,
      'total': 156,
    },
  };

  group('DiscoveryRepository', () {
    group('getTalents', () {
      test('returns ApiSuccess with parsed response on success', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talents,
            queryParameters: any(named: 'queryParameters'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: apiResponse,
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.put(
            any<String>(),
            any<dynamic>(),
          ),
        ).thenAnswer((_) async {});

        final result = await repository.getTalents();

        expect(result, isA<ApiSuccess<TalentListResponse>>());
        final data = (result as ApiSuccess<TalentListResponse>).data;
        expect(data.talents.length, 1);
        expect(data.nextCursor, 'eyJpZCI6NDN9');
        expect(data.hasMore, true);
        expect(data.total, 156);
      });

      test('passes cursor as query parameter', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talents,
            queryParameters: any(named: 'queryParameters'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: apiResponse,
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.put(
            any<String>(),
            any<dynamic>(),
          ),
        ).thenAnswer((_) async {});

        await repository.getTalents(cursor: 'test_cursor');

        final captured =
            verify(
                  () => mockDio.get<Map<String, dynamic>>(
                    ApiEndpoints.talents,
                    queryParameters: captureAny(named: 'queryParameters'),
                  ),
                ).captured.single
                as Map<String, dynamic>;

        expect(captured['cursor'], 'test_cursor');
      });

      test('passes filters as query parameters', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talents,
            queryParameters: any(named: 'queryParameters'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: apiResponse,
            statusCode: 200,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.put(
            any<String>(),
            any<dynamic>(),
          ),
        ).thenAnswer((_) async {});

        await repository.getTalents(
          filters: {'category': 'dj', 'min_rating': 4.0},
        );

        final captured =
            verify(
                  () => mockDio.get<Map<String, dynamic>>(
                    ApiEndpoints.talents,
                    queryParameters: captureAny(named: 'queryParameters'),
                  ),
                ).captured.single
                as Map<String, dynamic>;

        expect(captured['category'], 'dj');
        expect(captured['min_rating'], 4.0);
      });

      test('returns cached data on network error with cache', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talents,
            queryParameters: any(named: 'queryParameters'),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.get<Map<dynamic, dynamic>>(
            any<String>(),
          ),
        ).thenReturn(
          Map<dynamic, dynamic>.from({
            'data': [
              Map<dynamic, dynamic>.from({
                'id': 42,
                'attributes': Map<dynamic, dynamic>.from({
                  'stage_name': 'Cached',
                }),
              }),
            ],
            'meta': Map<dynamic, dynamic>.from({
              'next_cursor': null,
              'has_more': false,
              'total': 1,
            }),
          }),
        );

        final result = await repository.getTalents();

        expect(result, isA<ApiSuccess<TalentListResponse>>());
      });

      test('returns ApiFailure on network error '
          'without cache', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talents,
            queryParameters: any(named: 'queryParameters'),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(),
          ),
        );
        when(
          () => mockLocalStorage.get<Map<dynamic, dynamic>>(
            any<String>(),
          ),
        ).thenReturn(null);

        final result = await repository.getTalents();

        expect(result, isA<ApiFailure<TalentListResponse>>());
        expect(
          (result as ApiFailure).code,
          'NETWORK_ERROR',
        );
      });
    });
  });
}
