# Story 7.10 — Onboarding gamifié talent Flutter

## Status: done

## Story

**As a** new talent,
**I want** a gamified onboarding experience on mobile,
**So that** I know exactly what to complete to activate my profile and feel motivated to do so.

## Acceptance Criteria

1. **AC1** — The page shows 5 onboarding steps: Profil, Catégorie, Packages, Calendrier, Vérification.
2. **AC2** — Each step card shows an icon, label, description, and completion status.
3. **AC3** — Completed steps show a green checkmark and strikethrough text.
4. **AC4** — An animated progress bar shows overall completion (from server's `profile_completion_percentage`).
5. **AC5** — 5 star icons at the top fill with orange as steps are completed.
6. **AC6** — When all steps are complete, a `_CompletionBanner` animates in with `FadeTransition` + `ScaleTransition` (elastic spring).
7. **AC7** — Page accessible at `/talent-onboarding`.

## Implementation Notes

### Files Created

- `bookmi_app/lib/features/onboarding/bloc/onboarding_state.dart`
  - Sealed states: `OnboardingInitial | OnboardingLoading | OnboardingLoaded | OnboardingError`
  - `OnboardingStep` enum with 5 values; extension provides `label`, `description`, `icon`
  - `OnboardingLoaded.nextStep` — returns first incomplete step
  - `OnboardingLoaded.isFullyComplete` — true when all 5 done

- `bookmi_app/lib/features/onboarding/bloc/onboarding_cubit.dart`
  - `loadStatus()` → loads from repository → `Loading → Loaded | Error`
  - `markStepCompleted(step)` — optimistic update, then reload from server

- `bookmi_app/lib/features/onboarding/data/models/onboarding_status_model.dart`
  - Parses backend talent profile response to derive completed steps

- `bookmi_app/lib/features/onboarding/data/repositories/onboarding_repository.dart`
  - `getOnboardingStatus()` → `GET /api/v1/talent_profiles/me`
  - Uses `_dio = apiClient.dio` pattern; `OnboardingRepository.forTesting(dio)` for tests

- `bookmi_app/lib/features/onboarding/presentation/pages/talent_onboarding_page.dart`
  - `TalentOnboardingPage(repository)` — provides `OnboardingCubit`
  - `_OnboardingHeader` — star row + animated progress bar + `TweenAnimationBuilder`
  - `_StepCard` — animated container with completion state
  - `_CompletionBanner` — `StatefulWidget` with `FadeTransition` + `ScaleTransition` elastic spring

- `bookmi_app/lib/features/onboarding/onboarding.dart` — barrel export

### Route Integration

`route_names.dart`: added `talentOnboarding = 'talentOnboarding'` and `RoutePaths.talentOnboarding = '/talent-onboarding'`.
`app_router.dart`: `TalentOnboardingPage` registered as a top-level route (outside shell), takes `onboardingRepo`.
`app.dart`: `OnboardingRepository` created in `_AppDependencies.initialize()`, passed to `buildAppRouter`.

### Tests

- `bookmi_app/test/features/onboarding/bloc/onboarding_cubit_test.dart` — 7 test cases:
  - initial state is `OnboardingInitial`
  - `loadStatus` success → `[Loading, Loaded]`
  - `loadStatus` failure → `[Loading, Error]`
  - `markStepCompleted` → optimistic + reload
  - `isFullyComplete` true when all steps done
  - `nextStep` returns first incomplete step
  - `nextStep` is null when all complete
