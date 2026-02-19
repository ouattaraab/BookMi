# Story 6.9 — Écran évaluation Flutter

## Status: done

## Story

**As a** client or talent,
**I want** a dedicated evaluation screen on mobile,
**So that** I can rate the other party after a completed booking.

## Acceptance Criteria

1. **AC1** — The evaluation page shows a 5-star rating widget and an optional comment field (max 500 chars).
2. **AC2** — The submit button is disabled until at least 1 star is selected.
3. **AC3** — On submit, `EvaluationCubit.submitReview(bookingId, type, rating, comment)` is called; the button shows a spinner (`EvaluationSubmitting`).
4. **AC4** — On success (`EvaluationSubmitted`), the form is replaced by a success screen with an animated star icon and a "Fermer" button that pops the route.
5. **AC5** — On failure, a `SnackBar` with the error message is shown; the form remains editable.
6. **AC6** — The page supports both `type = client_to_talent` and `type = talent_to_client`, passed as a query parameter via GoRouter.
7. **AC7** — The page is accessible at `/bookings/booking/:id/evaluation?type=client_to_talent`.

## Implementation Notes

### Files created

- `bookmi_app/lib/features/evaluation/data/models/review_model.dart`
  - Fields: `id, bookingRequestId, reviewerId, revieweeId, type, rating, comment?, createdAt?`

- `bookmi_app/lib/features/evaluation/data/repositories/review_repository.dart`
  - `getReviews(bookingId)` → `GET /booking_requests/:id/reviews`
  - `submitReview(bookingId, type, rating, comment?)` → `POST /booking_requests/:id/reviews`
  - Uses `_dio = apiClient.dio` pattern; `ReviewRepository.forTesting(dio)` for tests

- `bookmi_app/lib/features/evaluation/bloc/evaluation_state.dart`
  - Sealed states: `EvaluationInitial | EvaluationLoading | EvaluationLoaded | EvaluationSubmitting | EvaluationSubmitted | EvaluationError`
  - `EvaluationLoaded.hasReviewedAsClient` and `hasReviewedAsTalent` getters
  - `EvaluationSubmitted` carries both the new `review` and the full `reviews` list

- `bookmi_app/lib/features/evaluation/bloc/evaluation_cubit.dart`
  - `loadReviews(bookingId)` → `Loading → Loaded | Error`
  - `submitReview(bookingId, type, rating, comment?)` → `Submitting → Submitted | Error`
    - After submit, best-effort reload via `getReviews`; falls back to `[data]` if reload fails

- `bookmi_app/lib/features/evaluation/presentation/pages/evaluation_page.dart`
  - `EvaluationPage(bookingId, type, repository)` — provides `EvaluationCubit`
  - `_EvaluationForm` (StatefulWidget) — star rating + comment text field + submit button
  - `_StarRating` — 5 `GestureDetector`-wrapped star icons (filled: `ctaOrange`, empty: `white30`)
  - `_SuccessScreen` — `FadeTransition` + `ScaleTransition` (elastic spring) on star icon

- `bookmi_app/lib/features/evaluation/evaluation.dart` — barrel export

### Route integration

`route_names.dart`: added `evaluation = 'evaluation'` (name + path).
`app_router.dart`: `EvaluationPage` registered as sub-route of `bookingDetail`; `type` read from `state.uri.queryParameters['type']`, defaulting to `client_to_talent`.
`app.dart`: `ReviewRepository` created and wired via `buildAppRouter`.

### Test

- `bookmi_app/test/features/evaluation/bloc/evaluation_cubit_test.dart` — 6 test cases:
  - initial state is `EvaluationInitial`
  - `loadReviews` success → `[Loading, Loaded]`, `hasReviewedAsClient: true`
  - `loadReviews` failure → `[Loading, Error]`, message propagated
  - `submitReview` success → `[Submitting, Submitted]`, rating=5, reviews list from reload
  - `submitReview` failure → `[Submitting, Error]`, error message propagated
  - `hasReviewedAsTalent` true when both review types present
  - Fallback: `Submitted` still emitted when reload after submit fails (reviews = `[newReview]`)
