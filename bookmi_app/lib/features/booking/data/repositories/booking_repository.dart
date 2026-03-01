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
          return ApiSuccess(
            _parseListResponse(Map<String, dynamic>.from(cached)),
          );
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
    required String startTime,
    required String eventLocation,
    String? message,
    bool isExpress = false,
    int? travelCost,
    String? promoCode,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingRequests,
        data: {
          'talent_profile_id': talentProfileId,
          'service_package_id': servicePackageId,
          'event_date': eventDate,
          'start_time': startTime,
          'event_location': eventLocation,
          if (message != null && message.isNotEmpty) 'message': message,
          'is_express': isExpress,
          if (travelCost != null && travelCost > 0) 'travel_cost': travelCost,
          if (promoCode != null && promoCode.isNotEmpty)
            'promo_code': promoCode,
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

  /// Download a CSV export of the current user's bookings.
  /// Returns the raw bytes of the CSV file.
  Future<ApiResult<List<int>>> exportBookings({String? status}) async {
    try {
      final response = await _dio.get<List<int>>(
        ApiEndpoints.bookingExport,
        queryParameters: {
          if (status != null) 'status': status,
        },
        options: Options(responseType: ResponseType.bytes),
      );
      return ApiSuccess(response.data ?? []);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Validate a promo code against a booking amount.
  /// Returns discount_amount and final_amount on success.
  Future<ApiResult<Map<String, dynamic>>> validatePromoCode({
    required String code,
    required int bookingAmount,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        '/api/v1/promo_codes/validate',
        data: {'code': code, 'booking_amount': bookingAmount},
      );
      return ApiSuccess(response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Initialize a Paystack card transaction for the given booking.
  ///
  /// Calls the backend which creates a Paystack transaction and returns an
  /// [access_code]. The Flutter SDK uses this code to present the payment UI.
  Future<ApiResult<Map<String, dynamic>>> initiatePayment({
    required int bookingId,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.paymentsInitiate,
        data: {
          'booking_id': bookingId,
          'payment_method': 'card',
        },
      );
      return ApiSuccess(response.data ?? {});
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Cancels the most recent pending/processing transaction for a booking.
  ///
  /// Called when the Paystack SDK returns 'cancelled' or throws, so the next
  /// tap on "Payer maintenant" won't be blocked by the duplicate-transaction check.
  Future<void> cancelPayment({required int bookingId}) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.paymentsCancel,
        data: {'booking_id': bookingId},
      );
    } catch (_) {
      // Best-effort — failure to cancel is non-fatal. The 5-min auto-expiry
      // on the backend will clean up any stale transaction automatically.
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

  /// Get a short-lived download URL (cache token) for the PDF receipt.
  Future<ApiResult<String>> getReceiptUrl(int id) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.bookingReceipt(id),
      );
      final url = response.data!['data']['receipt_url'] as String;
      return ApiSuccess(url);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Get a short-lived download URL (cache token) for the PDF contract.
  Future<ApiResult<String>> getContractUrl(int id) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        ApiEndpoints.bookingContractUrl(id),
      );
      final url = response.data!['data']['contract_url'] as String;
      return ApiSuccess(url);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Accept a pending booking (talent action).
  Future<ApiResult<BookingModel>> acceptBooking(int id) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingAccept(id),
      );
      final booking = BookingModel.fromJson(
        response.data!['data'] as Map<String, dynamic>,
      );
      return ApiSuccess(booking);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Confirm delivery of a completed service (client action).
  ///
  /// Calls POST /booking_requests/{id}/confirm_delivery which transitions the
  /// booking from `paid` → `confirmed` and releases the escrow hold.
  Future<ApiResult<void>> confirmDelivery(int bookingId) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.bookingConfirmDelivery(bookingId),
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Talent fallback confirm delivery (after 24 h without client confirmation).
  ///
  /// Calls POST /booking_requests/{id}/talent_confirm which transitions the
  /// booking from `paid` → `confirmed` and releases the escrow hold.
  /// Only allowed ≥ 24 h after event_date.
  Future<ApiResult<void>> talentConfirmDelivery(int bookingId) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.bookingTalentConfirm(bookingId),
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Complete a confirmed booking (client action).
  ///
  /// Calls POST /booking_requests/{id}/complete which transitions the
  /// booking from `confirmed` → `completed` after the event date has passed.
  Future<ApiResult<void>> completeBooking(int bookingId) async {
    try {
      await _dio.post<void>(ApiEndpoints.bookingComplete(bookingId));
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return ApiFailure(
        code:
            e.response?.data?['error']?['code'] as String? ?? 'COMPLETE_ERROR',
        message:
            e.response?.data?['error']?['message'] as String? ??
            'Erreur lors de la validation.',
      );
    }
  }

  /// Cancel a paid/confirmed booking (client action).
  ///
  /// Calls POST /booking_requests/{id}/cancel which applies the graduated
  /// refund policy: full refund (≥14d), partial 50% (≥7d), mediation (≥2d).
  Future<ApiResult<BookingModel>> cancelBooking(int id) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingCancel(id),
      );
      final booking = BookingModel.fromJson(
        response.data!['data'] as Map<String, dynamic>,
      );
      return ApiSuccess(booking);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Submit a post-event report for a completed booking (client action).
  ///
  /// Calls POST /booking_requests/{id}/reports with reason + optional description.
  /// Allowed reasons: no_show, late_arrival, quality_issue, payment_issue,
  /// inappropriate_behaviour, other.
  Future<ApiResult<void>> reportBooking({
    required int bookingId,
    required String reason,
    String? description,
  }) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.bookingReports(bookingId),
        data: {
          'reason': reason,
          if (description != null && description.isNotEmpty)
            'description': description,
        },
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Reply to a client review (talent action).
  ///
  /// Calls POST /api/v1/reviews/{reviewId}/reply with the reply text.
  /// Only the talent who received the review can call this, and only once.
  Future<ApiResult<void>> replyToReview({
    required int reviewId,
    required String reply,
  }) async {
    try {
      await _dio.post<void>(
        ApiEndpoints.reviewReply(reviewId),
        data: {'reply': reply},
      );
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Reject a pending booking (talent action).
  Future<ApiResult<BookingModel>> rejectBooking(
    int id, {
    String? reason,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingReject(id),
        data: reason != null ? {'reason': reason} : null,
      );
      final booking = BookingModel.fromJson(
        response.data!['data'] as Map<String, dynamic>,
      );
      return ApiSuccess(booking);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Open a dispute on a booking (client action).
  Future<ApiResult<BookingModel>> openDispute(int id) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingDispute(id),
      );
      final booking = BookingModel.fromJson(
        response.data!['data'] as Map<String, dynamic>,
      );
      return ApiSuccess(booking);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Propose a reschedule for a booking (either party).
  Future<ApiResult<Map<String, dynamic>>> proposeReschedule({
    required int bookingId,
    required String proposedDate,
    String? message,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        ApiEndpoints.bookingReschedule(bookingId),
        data: {
          'proposed_date': proposedDate,
          if (message != null && message.isNotEmpty) 'message': message,
        },
      );
      return ApiSuccess(response.data!['data'] as Map<String, dynamic>);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Accept a pending reschedule (counterparty only).
  Future<ApiResult<void>> acceptReschedule(int rescheduleId) async {
    try {
      await _dio.post<void>(ApiEndpoints.rescheduleAccept(rescheduleId));
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  /// Reject a pending reschedule (counterparty only).
  Future<ApiResult<void>> rejectReschedule(int rescheduleId) async {
    try {
      await _dio.post<void>(ApiEndpoints.rescheduleReject(rescheduleId));
      return const ApiSuccess(null);
    } on DioException catch (e) {
      return _mapDioError(e);
    }
  }

  // ─── Helpers ──────────────────────────────────────────────────────────────

  BookingListResponse _parseListResponse(Map<String, dynamic> data) {
    final items = ((data['data'] as List<dynamic>?) ?? [])
        .cast<Map<String, dynamic>>();
    final meta = data['meta'] as Map<String, dynamic>? ?? {};

    return BookingListResponse(
      bookings: items.map(BookingModel.fromJson).toList(),
      nextCursor: meta['next_cursor'] as String?,
      hasMore: meta['has_more'] as bool? ?? false,
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
      message: (error?['message'] as String?) ?? e.message ?? 'Erreur réseau',
    );
  }
}
