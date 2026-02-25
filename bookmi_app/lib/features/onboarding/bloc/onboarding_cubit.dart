import 'package:bookmi_app/features/onboarding/bloc/onboarding_state.dart';
import 'package:bookmi_app/features/onboarding/data/repositories/onboarding_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class OnboardingCubit extends Cubit<OnboardingState> {
  OnboardingCubit({required OnboardingRepository repository})
    : _repository = repository,
      super(const OnboardingInitial());

  final OnboardingRepository _repository;

  Future<void> loadStatus() async {
    emit(const OnboardingLoading());
    try {
      final status = await _repository.getOnboardingStatus();
      emit(
        OnboardingLoaded(
          completedSteps: status.completedSteps,
          profileCompletionPct: status.profileCompletionPct,
        ),
      );
    } catch (e) {
      emit(OnboardingError(e.toString()));
    }
  }

  /// Mark a step as completed optimistically, then reload from server.
  Future<void> markStepCompleted(OnboardingStep step) async {
    final current = state;
    if (current is! OnboardingLoaded) return;

    // Optimistic update
    final updated = {...current.completedSteps, step};
    emit(
      OnboardingLoaded(
        completedSteps: updated,
        profileCompletionPct: current.profileCompletionPct,
      ),
    );

    // Re-sync with server
    await loadStatus();
  }
}
