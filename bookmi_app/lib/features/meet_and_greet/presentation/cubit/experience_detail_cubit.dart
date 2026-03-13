import 'dart:io';

import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/meet_and_greet/data/models/experience_model.dart';
import 'package:bookmi_app/features/meet_and_greet/data/repositories/experience_repository.dart';
import 'package:bookmi_app/features/meet_and_greet/presentation/cubit/experience_detail_state.dart';

/// Cubit managing the detail page of a single experience and booking actions.
class ExperienceDetailCubit extends Cubit<ExperienceDetailState> {
  ExperienceDetailCubit({required ExperienceRepository repository})
    : _repository = repository,
      super(const ExperienceDetailInitial());

  final ExperienceRepository _repository;

  /// Initialise the cubit from an already-fetched [experience] object
  /// (e.g. when navigating from a card that already has the data).
  /// Shows the cached data immediately, then refreshes in the background to
  /// pick up the latest cover image, seat count, etc.
  void initWithExperience(ExperienceModel experience) {
    emit(ExperienceDetailLoaded(experience: experience));
    // ignore: discarded_futures
    _refreshInBackground(experience.id);
  }

  Future<void> _refreshInBackground(int id) async {
    final result = await _repository.getExperienceDetail(id);
    if (isClosed) return;
    if (result is ApiSuccess<ExperienceModel>) {
      final current = state;
      if (current is ExperienceDetailLoaded && current.experience.id == id) {
        emit(ExperienceDetailLoaded(experience: result.data));
      }
    }
  }

  /// Fetch full detail from the API (always fresh).
  Future<void> loadDetail(int id) async {
    emit(const ExperienceDetailLoading());
    final result = await _repository.getExperienceDetail(id);
    switch (result) {
      case ApiSuccess(:final data):
        emit(ExperienceDetailLoaded(experience: data));
      case ApiFailure(:final code, :final message):
        emit(ExperienceDetailFailure(code: code, message: message));
    }
  }

  /// Book [seatsCount] seats for the current experience.
  Future<void> bookSeats(int experienceId, int seatsCount) async {
    final current = state;
    if (current is! ExperienceDetailLoaded) return;

    emit(ExperienceDetailBooking(experience: current.experience));

    final result = await _repository.bookExperience(experienceId, seatsCount);
    switch (result) {
      case ApiSuccess(:final data):
        emit(
          ExperienceDetailBookingSuccess(
            experience: data,
            message: 'Réservation confirmée avec succès !',
          ),
        );
      case ApiFailure(:final message):
        emit(
          ExperienceDetailBookingFailure(
            experience: current.experience,
            errorMessage: message,
          ),
        );
    }
  }

  /// Cancel the current user's booking.
  Future<void> cancelBooking(int experienceId) async {
    final current = state;
    if (current is! ExperienceDetailLoaded) return;

    emit(ExperienceDetailBooking(experience: current.experience));

    final result = await _repository.cancelBooking(experienceId);
    switch (result) {
      case ApiSuccess():
        // Reload fresh state after cancellation.
        final reloadResult = await _repository.getExperienceDetail(
          experienceId,
        );
        switch (reloadResult) {
          case ApiSuccess(:final data):
            emit(
              ExperienceDetailBookingSuccess(
                experience: data,
                message: 'Réservation annulée.',
              ),
            );
          case ApiFailure():
            // Optimistic: clear booking locally.
            emit(
              ExperienceDetailBookingSuccess(
                experience: current.experience.copyWith(clearBooking: true),
                message: 'Réservation annulée.',
              ),
            );
        }
      case ApiFailure(:final message):
        emit(
          ExperienceDetailBookingFailure(
            experience: current.experience,
            errorMessage: message,
          ),
        );
    }
  }

  /// Upload a new cover photo/video for the experience.
  Future<void> uploadCover(int experienceId, File file) async {
    final current = state;
    if (current is! ExperienceDetailLoaded) return;

    final result = await _repository.uploadCover(experienceId, file);
    if (isClosed) return;
    switch (result) {
      case ApiSuccess(:final data):
        emit(
          ExperienceDetailLoaded(
            experience: current.experience.copyWith(coverImageUrl: data),
          ),
        );
      case ApiFailure(:final message):
        emit(
          ExperienceDetailBookingFailure(
            experience: current.experience,
            errorMessage: message,
          ),
        );
    }
  }

  /// Transition back to [ExperienceDetailLoaded] after showing a success or
  /// error snackbar — call this once the snackbar has been displayed.
  void acknowledgeBookingResult() {
    final current = state;
    if (current is ExperienceDetailBookingSuccess) {
      emit(ExperienceDetailLoaded(experience: current.experience));
    } else if (current is ExperienceDetailBookingFailure) {
      emit(ExperienceDetailLoaded(experience: current.experience));
    }
  }
}
