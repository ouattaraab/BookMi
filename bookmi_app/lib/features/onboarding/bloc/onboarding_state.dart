import 'package:flutter/foundation.dart';

/// The 5 onboarding steps for a new talent.
enum OnboardingStep {
  profile,
  category,
  packages,
  calendar,
  verification,
}

extension OnboardingStepX on OnboardingStep {
  String get label => switch (this) {
        OnboardingStep.profile => 'Profil',
        OnboardingStep.category => 'Catégorie',
        OnboardingStep.packages => 'Packages',
        OnboardingStep.calendar => 'Calendrier',
        OnboardingStep.verification => 'Vérification',
      };

  String get description => switch (this) {
        OnboardingStep.profile => 'Complétez votre nom de scène, biographie et photo.',
        OnboardingStep.category =>
          'Choisissez votre catégorie et sous-catégorie artistique.',
        OnboardingStep.packages =>
          'Créez au moins un package de prestation avec tarif.',
        OnboardingStep.calendar =>
          'Définissez vos disponibilités pour les prochaines semaines.',
        OnboardingStep.verification =>
          'Soumettez votre CNI ou passeport pour être vérifié.',
      };

  String get icon => switch (this) {
        OnboardingStep.profile => '👤',
        OnboardingStep.category => '🎭',
        OnboardingStep.packages => '📦',
        OnboardingStep.calendar => '📅',
        OnboardingStep.verification => '🛡️',
      };
}

@immutable
sealed class OnboardingState {
  const OnboardingState();
}

@immutable
final class OnboardingInitial extends OnboardingState {
  const OnboardingInitial();
}

@immutable
final class OnboardingLoading extends OnboardingState {
  const OnboardingLoading();
}

@immutable
final class OnboardingLoaded extends OnboardingState {
  const OnboardingLoaded({
    required this.completedSteps,
    required this.profileCompletionPct,
  });

  /// Set of steps the user has completed.
  final Set<OnboardingStep> completedSteps;

  /// Overall profile completion percentage (0–100) from the backend.
  final int profileCompletionPct;

  int get totalSteps => OnboardingStep.values.length;
  int get completedCount => completedSteps.length;
  bool get isFullyComplete => completedCount == totalSteps;

  OnboardingStep? get nextStep {
    for (final step in OnboardingStep.values) {
      if (!completedSteps.contains(step)) return step;
    }
    return null;
  }

  bool isCompleted(OnboardingStep step) => completedSteps.contains(step);

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is OnboardingLoaded &&
          setEquals(completedSteps, other.completedSteps) &&
          profileCompletionPct == other.profileCompletionPct;

  @override
  int get hashCode => Object.hash(Object.hashAll(completedSteps), profileCompletionPct);
}

@immutable
final class OnboardingError extends OnboardingState {
  const OnboardingError(this.message);

  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) || other is OnboardingError && message == other.message;

  @override
  int get hashCode => message.hashCode;
}
