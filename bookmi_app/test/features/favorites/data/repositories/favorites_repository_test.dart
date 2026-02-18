import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/favorites/data/local/favorites_local_source.dart';
import 'package:bookmi_app/features/favorites/data/repositories/favorites_repository.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockDio extends Mock implements Dio {}

class MockFavoritesLocalSource extends Mock implements FavoritesLocalSource {}

// Fake classes for registerFallbackValue
class FakeRequestOptions extends Fake implements RequestOptions {}

void main() {
  late MockDio mockDio;
  late MockFavoritesLocalSource mockLocalSource;
  late FavoritesRepository repository;

  setUpAll(() {
    registerFallbackValue(FakeRequestOptions());
  });

  setUp(() {
    mockDio = MockDio();
    mockLocalSource = MockFavoritesLocalSource();
    repository = FavoritesRepository.forTesting(
      dio: mockDio,
      localSource: mockLocalSource,
    );
  });

  group('FavoritesRepository', () {
    group('getFavorites', () {
      test('returns favorites on success', () async {
        final responseData = <String, dynamic>{
          'data': <dynamic>[
            <String, dynamic>{
              'id': 1,
              'type': 'favorite',
              'attributes': <String, dynamic>{
                'talent': <String, dynamic>{
                  'id': 42,
                  'type': 'talent_profile',
                },
                'favorited_at': '2026-02-17T14:30:00Z',
              },
            },
          ],
        };

        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.myFavorites,
            queryParameters: any(named: 'queryParameters'),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: responseData,
            statusCode: 200,
            requestOptions: RequestOptions(path: ApiEndpoints.myFavorites),
          ),
        );

        when(
          () => mockLocalSource.cacheFavoriteIds(
            any(),
            append: any(named: 'append'),
          ),
        ).thenAnswer((_) async {});

        final result = await repository.getFavorites();

        expect(result, isA<ApiSuccess<List<Map<String, dynamic>>>>());
        final data = (result as ApiSuccess<List<Map<String, dynamic>>>).data;
        expect(data, hasLength(1));
        verify(
          () => mockLocalSource.cacheFavoriteIds([42]),
        ).called(1);
      });

      test('returns cached data on network error', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.myFavorites,
            queryParameters: any(named: 'queryParameters'),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(path: ApiEndpoints.myFavorites),
          ),
        );

        when(() => mockLocalSource.getCachedFavoriteIds()).thenReturn([42, 99]);

        final result = await repository.getFavorites();

        expect(result, isA<ApiSuccess<List<Map<String, dynamic>>>>());
        final data = (result as ApiSuccess<List<Map<String, dynamic>>>).data;
        expect(data, hasLength(2));
        expect(data[0]['talent_id'], 42);
      });

      test('returns failure on network error with no cache', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.myFavorites,
            queryParameters: any(named: 'queryParameters'),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(path: ApiEndpoints.myFavorites),
          ),
        );

        when(() => mockLocalSource.getCachedFavoriteIds()).thenReturn(null);

        final result = await repository.getFavorites();

        expect(result, isA<ApiFailure<List<Map<String, dynamic>>>>());
        expect(
          (result as ApiFailure<List<Map<String, dynamic>>>).code,
          'NETWORK_ERROR',
        );
      });
    });

    group('addFavorite', () {
      test('returns success and updates local cache', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.talentFavorite(42),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: <String, dynamic>{},
            statusCode: 201,
            requestOptions: RequestOptions(
              path: ApiEndpoints.talentFavorite(42),
            ),
          ),
        );

        when(() => mockLocalSource.addFavoriteId(42)).thenAnswer((_) async {});

        final result = await repository.addFavorite(42);

        expect(result, isA<ApiSuccess<void>>());
        verify(() => mockLocalSource.addFavoriteId(42)).called(1);
      });

      test('queues action on network error', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.talentFavorite(42),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(
              path: ApiEndpoints.talentFavorite(42),
            ),
          ),
        );

        when(() => mockLocalSource.addFavoriteId(42)).thenAnswer((_) async {});
        when(
          () => mockLocalSource.queuePendingAction(any()),
        ).thenAnswer((_) async {});

        final result = await repository.addFavorite(42);

        expect(result, isA<ApiSuccess<void>>());
        verify(() => mockLocalSource.addFavoriteId(42)).called(1);
        verify(
          () => mockLocalSource.queuePendingAction({
            'type': 'add',
            'talentId': 42,
          }),
        ).called(1);
      });

      test('returns failure on API error', () async {
        when(
          () => mockDio.post<Map<String, dynamic>>(
            ApiEndpoints.talentFavorite(42),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.badResponse,
            response: Response(
              data: <String, dynamic>{
                'error': <String, dynamic>{
                  'code': 'ALREADY_FAVORITED',
                  'message': 'Déjà en favori',
                },
              },
              statusCode: 409,
              requestOptions: RequestOptions(
                path: ApiEndpoints.talentFavorite(42),
              ),
            ),
            requestOptions: RequestOptions(
              path: ApiEndpoints.talentFavorite(42),
            ),
          ),
        );

        final result = await repository.addFavorite(42);

        expect(result, isA<ApiFailure<void>>());
        expect((result as ApiFailure<void>).code, 'ALREADY_FAVORITED');
        verifyNever(() => mockLocalSource.addFavoriteId(any()));
      });
    });

    group('removeFavorite', () {
      test('returns success and updates local cache', () async {
        when(
          () => mockDio.delete<void>(ApiEndpoints.talentFavorite(42)),
        ).thenAnswer(
          (_) async => Response<void>(
            statusCode: 204,
            requestOptions: RequestOptions(
              path: ApiEndpoints.talentFavorite(42),
            ),
          ),
        );

        when(
          () => mockLocalSource.removeFavoriteId(42),
        ).thenAnswer((_) async {});

        final result = await repository.removeFavorite(42);

        expect(result, isA<ApiSuccess<void>>());
        verify(() => mockLocalSource.removeFavoriteId(42)).called(1);
      });

      test('queues action on network error', () async {
        when(
          () => mockDio.delete<void>(ApiEndpoints.talentFavorite(42)),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionTimeout,
            requestOptions: RequestOptions(
              path: ApiEndpoints.talentFavorite(42),
            ),
          ),
        );

        when(
          () => mockLocalSource.removeFavoriteId(42),
        ).thenAnswer((_) async {});
        when(
          () => mockLocalSource.queuePendingAction(any()),
        ).thenAnswer((_) async {});

        final result = await repository.removeFavorite(42);

        expect(result, isA<ApiSuccess<void>>());
        verify(() => mockLocalSource.removeFavoriteId(42)).called(1);
        verify(
          () => mockLocalSource.queuePendingAction({
            'type': 'remove',
            'talentId': 42,
          }),
        ).called(1);
      });
    });

    group('isFavorite', () {
      test('returns true when API says favorite', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentFavorite(42),
          ),
        ).thenAnswer(
          (_) async => Response(
            data: <String, dynamic>{
              'data': <String, dynamic>{'is_favorite': true},
            },
            statusCode: 200,
            requestOptions: RequestOptions(
              path: ApiEndpoints.talentFavorite(42),
            ),
          ),
        );

        final result = await repository.isFavorite(42);

        expect(result, isA<ApiSuccess<bool>>());
        expect((result as ApiSuccess<bool>).data, isTrue);
      });

      test('falls back to local cache on error', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentFavorite(42),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(
              path: ApiEndpoints.talentFavorite(42),
            ),
          ),
        );

        when(() => mockLocalSource.isFavorite(42)).thenReturn(true);

        final result = await repository.isFavorite(42);

        expect(result, isA<ApiSuccess<bool>>());
        expect((result as ApiSuccess<bool>).data, isTrue);
      });

      test('returns failure when no cache and error', () async {
        when(
          () => mockDio.get<Map<String, dynamic>>(
            ApiEndpoints.talentFavorite(42),
          ),
        ).thenThrow(
          DioException(
            type: DioExceptionType.connectionError,
            requestOptions: RequestOptions(
              path: ApiEndpoints.talentFavorite(42),
            ),
          ),
        );

        when(() => mockLocalSource.isFavorite(42)).thenReturn(null);

        final result = await repository.isFavorite(42);

        expect(result, isA<ApiFailure<bool>>());
      });
    });
  });
}
