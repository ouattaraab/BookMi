import 'package:bookmi_app/features/onboarding/bloc/onboarding_state.dart';

class OnboardingStatusModel {
  const OnboardingStatusModel({
    required this.completedSteps,
    required this.profileCompletionPct,
  });

  final Set<OnboardingStep> completedSteps;
  final int profileCompletionPct;

  factory OnboardingStatusModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;

    // Map profile_completion_percentage to steps
    final pct = (data['profile_completion_percentage'] as num?)?.toInt() ?? 0;
    final hasProfile = data['stage_name'] != null && data['bio'] != null;
    final hasCategory = data['category_id'] != null;
    final hasPackages =
        (data['service_packages_count'] as num?)?.toInt() != null &&
        (data['service_packages_count'] as num).toInt() > 0;
    final hasCalendar =
        (data['calendar_slots_count'] as num?)?.toInt() != null &&
        (data['calendar_slots_count'] as num).toInt() > 0;
    final isVerified = data['is_verified'] == true;

    final completed = <OnboardingStep>{
      if (hasProfile) OnboardingStep.profile,
      if (hasCategory) OnboardingStep.category,
      if (hasPackages) OnboardingStep.packages,
      if (hasCalendar) OnboardingStep.calendar,
      if (isVerified) OnboardingStep.verification,
    };

    return OnboardingStatusModel(
      completedSteps: completed,
      profileCompletionPct: pct,
    );
  }
}
