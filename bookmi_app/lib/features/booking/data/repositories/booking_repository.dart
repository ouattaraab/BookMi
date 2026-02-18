import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_endpoints.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/storage/local_storage.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:dio/dio.dart';

class BookingListResponse {
  const BookingListResponse({
    required this.bookings,
    required this.nextCursor,
    required this.hasMore,
  });

  final List<BookingModel> bookings;
  final String? nextCursor;
  final bool hasMore;
}

class BookingRepository {
  BookingRepository({
    required ApiClient apiClient,
    required LocalStorage localStorage,
  }) : _dio = apiClient.dio,
       _localStorage = localStorage;

  /// Test-only constructor.
  BookingRepository.forTesting({
    required Dio dio,
    required LocalStorage localStorage,
  }) : _dio = dio,
       _localStorage = localStorage;

  final Dio _dio;
  final LocalStorage _localStorage;

  static const _cacheKey = 'bookings_accepted_paid';

  /// Fetch paginated list of bookings for the current user.
  Future<ApiResult<BookingListResponse>> getBookings({
    String? status,
    String? cursor,
  }) async {
    try {
      final queryParameters = <String, dynamic>{
        // ignore: use_null_aware_elements, conflicts with invalid_null_aware_operator
        if (status != null) 'status': status,
        // ignore: use_null_aware_elements, conflicts with invalid_null_aware_operator
        if (cursor != null) 'cursor': cursor,
      };

      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.bookingRequests,
        queryParameters: queryParameters,
      );

      final result = _parseListResponse(response.data!);

      // Cache accepted/paid bookings for offline support (7-day TTL)
      if (status == null || status == 'accepted' || status == 'paid') {
        await _localStorage.put(_cacheKey, response.data);
      }

      return ApiSuccess(result);
    } on DioException catch (e) {
      if (_isNetworkError(e)) {
        final cached = _localStorage.get<Map<dynamic, dynamic>>(_cacheKey);
        if (cached != null) {
          return ApiSuccess(_parseListResponse(Map<String, dynamic>.from(cached)));
        }
      }
      return _mapDioError(e);
    }
  }

  /// Create a new booking request.
  Future<ApiResult<BookingModel>> createBooking({
    required int talentProfileId,
    required int servicePackageId,
    required String eventDate,
    required String eventLocation,
    String? message,
    bool isExpress = false,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingRequests,
        data: {
          'talent_profile_id': talentProfileId,
          'service_package_id': servicePackageId,
          'event_date': eventDate,
          'event_location': eventLocation,
          if (message != null && message.isNotEmpty) 'message': message,
          'is_express': isExpress,
        },
      );

      final booking = BookingModel.fromJson(
        response.data!['data'] as Map<String, dynamic>,
      );
      return ApiSuccess(booking);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Fetch a single booking by id.
  Future<ApiResult<BookingModel>> getBooking(int id) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.bookingRequest(id),
      );
      final booking = BookingModel.fromJson(
        response.data!['data'] as Map<String, dynamic>,
      );
      return ApiSuccess(booking);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  // ─── Helpers ──────────────────────────────────────────────────────────────

  BookingListResponse _parseListResponse(Map<String, dynamic> data) {
    final items = (data['data'] as List<dynamic>).cast<Map<String, dynamic>>();
    final meta = data['meta'] as Map<String, dynamic>;
    final cursorMeta = meta['cursor'] as Map<String, dynamic>;

    return BookingListResponse(
      bookings: items.map(BookingModel.fromJson).toList(),
      nextCursor: cursorMeta['next'] as String?,
      hasMore: cursorMeta['has_more'] as bool,
    );
  }

  bool _isNetworkError(DioException e) =>
      e.type == DioExceptionType.connectionError ||
      e.type == DioExceptionType.connectionTimeout;

  ApiFailure<T> _mapDioError<T>(DioException e) {
    final errorData = e.response?.data as Map<String, dynamic>?;
    final error = errorData?['error'] as Map<String, dynamic>?;
    return ApiFailure(
      code: (error?['code'] as String?) ?? 'NETWORK_ERROR',
      message:
          (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
    );
  }
}
