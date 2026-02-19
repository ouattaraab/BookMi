import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/tracking/bloc/tracking_state.dart';
import 'package:bookmi_app/features/tracking/data/models/tracking_event_model.dart';
import 'package:bookmi_app/features/tracking/data/repositories/tracking_repository.dart';

class TrackingCubit extends Cubit<TrackingState> {
  TrackingCubit({required TrackingRepository repository})
    : _repository = repository,
      super(const TrackingInitial());

  final TrackingRepository _repository;

  Future<void> loadEvents(int bookingId) async {
    emit(const TrackingLoading());
    switch (await _repository.getTrackingEvents(bookingId)) {
      case ApiFailure(:final message):
        emit(TrackingError(message));
      case ApiSuccess(:final data):
        emit(TrackingLoaded(events: data, bookingId: bookingId));
    }
  }

  Future<void> postUpdate(int bookingId, String status) async {
    final currentEvents = _currentEvents();
    emit(TrackingUpdating(events: currentEvents, bookingId: bookingId));
    switch (await _repository.postTrackingUpdate(bookingId, status: status)) {
      case ApiFailure(:final message):
        emit(TrackingLoaded(events: currentEvents, bookingId: bookingId));
        addError(Exception(message));
      case ApiSuccess(:final data):
        emit(TrackingLoaded(events: [...currentEvents, data], bookingId: bookingId));
    }
  }

  Future<void> checkIn(
    int bookingId, {
    required double latitude,
    required double longitude,
  }) async {
    final currentEvents = _currentEvents();
    emit(TrackingUpdating(events: currentEvents, bookingId: bookingId));
    switch (
      await _repository.checkIn(
        bookingId,
        latitude: latitude,
        longitude: longitude,
      )
    ) {
      case ApiFailure(:final message):
        emit(TrackingLoaded(events: currentEvents, bookingId: bookingId));
        addError(Exception(message));
      case ApiSuccess(:final data):
        emit(TrackingLoaded(events: [...currentEvents, data], bookingId: bookingId));
    }
  }

  List<TrackingEventModel> _currentEvents() => switch (state) {
    TrackingLoaded(:final events) => events,
    _ => const [],
  };
}
