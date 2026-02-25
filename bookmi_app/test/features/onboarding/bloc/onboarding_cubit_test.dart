import 'package:bookmi_app/features/onboarding/bloc/onboarding_cubit.dart';
import 'package:bookmi_app/features/onboarding/bloc/onboarding_state.dart';
import 'package:bookmi_app/features/onboarding/data/models/onboarding_status_model.dart';
import 'package:bookmi_app/features/onboarding/data/repositories/onboarding_repository.dart';
import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockOnboardingRepository extends Mock implements OnboardingRepository {}

void main() {
  late MockOnboardingRepository repository;

  setUp(() {
    repository = MockOnboardingRepository();
  });

  group('OnboardingCubit', () {
    test('initial state is OnboardingInitial', () {
      final cubit = OnboardingCubit(repository: repository);
      expect(cubit.state, const OnboardingInitial());
    });

    blocTest<OnboardingCubit, OnboardingState>(
      'loadStatus emits [Loading, Loaded] on success',
      build: () {
        when(() => repository.getOnboardingStatus()).thenAnswer(
          (_) async => const OnboardingStatusModel(
            completedSteps: {OnboardingStep.profile, OnboardingStep.category},
            profileCompletionPct: 40,
          ),
        );
        return OnboardingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadStatus(),
      expect: () => [
        const OnboardingLoading(),
        OnboardingLoaded(
          completedSteps: const {
            OnboardingStep.profile,
            OnboardingStep.category,
          },
          profileCompletionPct: 40,
        ),
      ],
    );

    blocTest<OnboardingCubit, OnboardingState>(
      'loadStatus emits [Loading, Error] on failure',
      build: () {
        when(
          () => repository.getOnboardingStatus(),
        ).thenThrow(Exception('Erreur réseau'));
        return OnboardingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadStatus(),
      expect: () => [
        const OnboardingLoading(),
        isA<OnboardingError>(),
      ],
    );

    blocTest<OnboardingCubit, OnboardingState>(
      'markStepCompleted optimistically updates and reloads',
      build: () {
        when(() => repository.getOnboardingStatus()).thenAnswer(
          (_) async => const OnboardingStatusModel(
            completedSteps: {
              OnboardingStep.profile,
              OnboardingStep.category,
              OnboardingStep.packages,
            },
            profileCompletionPct: 60,
          ),
        );
        return OnboardingCubit(repository: repository)..emit(
          OnboardingLoaded(
            completedSteps: const {
              OnboardingStep.profile,
              OnboardingStep.category,
            },
            profileCompletionPct: 40,
          ),
        );
      },
      act: (cubit) => cubit.markStepCompleted(OnboardingStep.packages),
      expect: () => [
        // Optimistic update
        OnboardingLoaded(
          completedSteps: const {
            OnboardingStep.profile,
            OnboardingStep.category,
            OnboardingStep.packages,
          },
          profileCompletionPct: 40,
        ),
        // Loading during reload
        const OnboardingLoading(),
        // Reloaded from server
        OnboardingLoaded(
          completedSteps: const {
            OnboardingStep.profile,
            OnboardingStep.category,
            OnboardingStep.packages,
          },
          profileCompletionPct: 60,
        ),
      ],
    );

    test('OnboardingLoaded.isFullyComplete is true when all steps done', () {
      final state = OnboardingLoaded(
        completedSteps: OnboardingStep.values.toSet(),
        profileCompletionPct: 100,
      );
      expect(state.isFullyComplete, isTrue);
    });

    test('OnboardingLoaded.nextStep returns first incomplete step', () {
      final state = OnboardingLoaded(
        completedSteps: const {OnboardingStep.profile},
        profileCompletionPct: 20,
      );
      expect(state.nextStep, OnboardingStep.category);
    });

    test('OnboardingLoaded.nextStep is null when all complete', () {
      final state = OnboardingLoaded(
        completedSteps: OnboardingStep.values.toSet(),
        profileCompletionPct: 100,
      );
      expect(state.nextStep, isNull);
    });
  });
}
