import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/meet_and_greet/data/repositories/experience_repository.dart';
import 'package:bookmi_app/features/meet_and_greet/presentation/cubit/experience_state.dart';

/// Cubit managing the list of public experiences shown on the home screen.
class ExperienceCubit extends Cubit<ExperienceState> {
  ExperienceCubit({required ExperienceRepository repository})
      : _repository = repository,
        super(const ExperienceInitial());

  final ExperienceRepository _repository;

  /// Load the first page of experiences.
  Future<void> loadExperiences() async {
    emit(const ExperienceLoading());
    final result = await _repository.getExperiences();
    switch (result) {
      case ApiSuccess(:final data):
        emit(
          ExperienceLoaded(
            experiences: data.experiences,
            currentPage: data.currentPage,
            lastPage: data.lastPage,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(ExperienceFailure(code: code, message: message));
    }
  }

  /// Load the next page and append results (only when [hasMore]).
  Future<void> loadNextPage() async {
    final current = state;
    if (current is ExperienceLoadingMore || current is! ExperienceLoaded) {
      return;
    }
    if (!current.hasMore) return;

    emit(
      ExperienceLoadingMore(
        experiences: current.experiences,
        currentPage: current.currentPage,
        lastPage: current.lastPage,
      ),
    );

    final result = await _repository.getExperiences(
      page: current.currentPage + 1,
    );
    switch (result) {
      case ApiSuccess(:final data):
        emit(
          ExperienceLoaded(
            experiences: [...current.experiences, ...data.experiences],
            currentPage: data.currentPage,
            lastPage: data.lastPage,
          ),
        );
      case ApiFailure():
        // Restore previous loaded state silently on pagination error.
        emit(
          ExperienceLoaded(
            experiences: current.experiences,
            currentPage: current.currentPage,
            lastPage: current.lastPage,
          ),
        );
    }
  }
}
