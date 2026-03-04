import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/tracking/bloc/tracking_state.dart';
import 'package:bookmi_app/features/tracking/data/models/tracking_event_model.dart';
import 'package:bookmi_app/features/tracking/data/repositories/tracking_repository.dart';

class TrackingCubit extends Cubit<TrackingState> {
  TrackingCubit({
    required TrackingRepository repository,
    bool isClient = false,
    DateTime? clientConfirmedAt,
  }) : _repository = repository,
       _isClient = isClient,
       _clientConfirmedAt = clientConfirmedAt,
       super(const TrackingInitial());

  final TrackingRepository _repository;
  final bool _isClient;
  DateTime? _clientConfirmedAt;

  Future<void> loadEvents(int bookingId) async {
    emit(const TrackingLoading());
    switch (await _repository.getTrackingEvents(bookingId)) {
      case ApiFailure(:final message):
        emit(TrackingError(message));
      case ApiSuccess(:final data):
        emit(
          TrackingLoaded(
            events: data,
            bookingId: bookingId,
            isClient: _isClient,
            clientConfirmedAt: _clientConfirmedAt,
          ),
        );
    }
  }

  Future<void> postUpdate(int bookingId, String status) async {
    final currentEvents = _currentEvents();
    emit(
      TrackingUpdating(
        events: currentEvents,
        bookingId: bookingId,
        isClient: _isClient,
        clientConfirmedAt: _clientConfirmedAt,
      ),
    );
    switch (await _repository.postTrackingUpdate(bookingId, status: status)) {
      case ApiFailure(:final message):
        emit(
          TrackingLoaded(
            events: currentEvents,
            bookingId: bookingId,
            isClient: _isClient,
            clientConfirmedAt: _clientConfirmedAt,
          ),
        );
        addError(Exception(message));
      case ApiSuccess(:final data):
        emit(
          TrackingLoaded(
            events: [...currentEvents, data],
            bookingId: bookingId,
            isClient: _isClient,
            clientConfirmedAt: _clientConfirmedAt,
          ),
        );
    }
  }

  Future<void> checkIn(
    int bookingId, {
    required double latitude,
    required double longitude,
  }) async {
    final currentEvents = _currentEvents();
    emit(
      TrackingUpdating(
        events: currentEvents,
        bookingId: bookingId,
        isClient: _isClient,
        clientConfirmedAt: _clientConfirmedAt,
      ),
    );
    switch (await _repository.checkIn(
      bookingId,
      latitude: latitude,
      longitude: longitude,
    )) {
      case ApiFailure(:final message):
        emit(
          TrackingLoaded(
            events: currentEvents,
            bookingId: bookingId,
            isClient: _isClient,
            clientConfirmedAt: _clientConfirmedAt,
          ),
        );
        addError(Exception(message));
      case ApiSuccess(:final data):
        emit(
          TrackingLoaded(
            events: [...currentEvents, data],
            bookingId: bookingId,
            isClient: _isClient,
            clientConfirmedAt: _clientConfirmedAt,
          ),
        );
    }
  }

  Future<void> confirmArrival(int bookingId) async {
    final currentEvents = _currentEvents();
    emit(
      TrackingUpdating(
        events: currentEvents,
        bookingId: bookingId,
        isClient: _isClient,
        clientConfirmedAt: _clientConfirmedAt,
      ),
    );
    switch (await _repository.confirmArrival(bookingId)) {
      case ApiFailure(:final message):
        emit(
          TrackingLoaded(
            events: currentEvents,
            bookingId: bookingId,
            isClient: _isClient,
            clientConfirmedAt: _clientConfirmedAt,
          ),
        );
        addError(Exception(message));
      case ApiSuccess(:final data):
        final confirmedAtStr = data['client_confirmed_arrival_at'] as String?;
        _clientConfirmedAt = confirmedAtStr != null
            ? DateTime.tryParse(confirmedAtStr)
            : DateTime.now();
        // Reload events to get updated state
        await loadEvents(bookingId);
    }
  }

  List<TrackingEventModel> _currentEvents() => switch (state) {
    TrackingLoaded(:final events) => events,
    _ => const [],
  };
}
