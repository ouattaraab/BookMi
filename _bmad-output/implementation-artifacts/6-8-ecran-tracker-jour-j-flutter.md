# Story 6.8 — Écran tracker Jour J Flutter

## Status: done

## Story

**As a** talent or client,
**I want** a real-time status tracking screen on mobile,
**So that** I can follow the progression of the service day live.

## Acceptance Criteria

1. **AC1** — The tracking screen shows a vertical timeline with 5 steps: En préparation, En route, Arrivé sur place, En prestation, Prestation terminée.
2. **AC2** — Past steps are shown with filled/coloured nodes; the current step is highlighted; future steps are dimmed.
3. **AC3** — The talent sees action buttons to advance to the next status step.
4. **AC4** — Tapping the action button calls `TrackingCubit.postUpdate(bookingId, status)` and updates the timeline optimistically via `TrackingUpdating` state.
5. **AC5** — When the last event is `completed`, a `_CelebrationOverlay` animates in over the page. Tapping it or the "Fermer" button dismisses it.
6. **AC6** — Errors (network, transition, permission) surface as `SnackBar` via `BlocConsumer` listener; the timeline reverts to the last valid state.
7. **AC7** — The page is accessible as a sub-route of `bookingDetail`: `/bookings/booking/:id/tracking`.

## Implementation Notes

### Files created

- `bookmi_app/lib/features/tracking/bloc/tracking_state.dart`
  - Sealed states: `TrackingInitial | TrackingLoading | TrackingLoaded | TrackingUpdating | TrackingError`
  - `TrackingUpdating extends TrackingLoaded` for optimistic UI; `runtimeType` guard in `==` ensures Bloc doesn't suppress the emit.
  - `TrackingLoaded.isCompleted` getter drives the celebration overlay.

- `bookmi_app/lib/features/tracking/bloc/tracking_cubit.dart`
  - `loadEvents(bookingId)` — fetches event list; emits `Loading → Loaded | Error`
  - `postUpdate(bookingId, status)` — emits `Updating → Loaded | Error (reverts on failure)`
  - `checkIn(bookingId, latitude, longitude)` — delegates to `checkIn` endpoint; same optimistic pattern

- `bookmi_app/lib/features/tracking/data/repositories/tracking_repository.dart` (rewritten)
  - Uses `_dio = apiClient.dio` pattern (not `apiClient.get/post`)
  - `getTrackingEvents`, `postTrackingUpdate`, `checkIn` — try/catch with `_mapError`
  - `TrackingRepository.forTesting(dio)` constructor for tests

- `bookmi_app/lib/features/tracking/presentation/pages/tracking_page.dart`
  - `TrackingPage(bookingId, repository)` — provides `TrackingCubit`, triggers `loadEvents`
  - `_TrackingTimeline` — vertical timeline with 5 hard-coded steps, matches DB statuses
  - `_NextStepButton` / `_ActionButton` — action buttons based on current status; disabled during `TrackingUpdating`
  - `_CelebrationOverlay` — `StatefulWidget` with `FadeTransition`, dismissible by tap or button

- `bookmi_app/lib/features/tracking/tracking.dart` — barrel export

### Route integration

`route_names.dart`: added `tracking = 'tracking'` (name + path).
`app_router.dart`: `TrackingPage` registered as sub-route of `bookingDetail` with `parentNavigatorKey: rootNavigatorKey`.
`app.dart`: `TrackingRepository` created in `_AppDependencies.initialize()`, passed to `buildAppRouter`.

### Test

- `bookmi_app/test/features/tracking/bloc/tracking_cubit_test.dart` — 6 test cases:
  - initial state is `TrackingInitial`
  - `loadEvents` success → `[Loading, Loaded]`, events list correct
  - `loadEvents` failure → `[Loading, Error]`, message propagated
  - `postUpdate` success → `[Loading, Loaded, Updating, Loaded]`, 2 events, currentStatus updated
  - `postUpdate` failure → reverts to same `Loaded` with original events
  - `checkIn` success → `[Loading, Loaded, Updating, Loaded]`, 3 events, coords in last event
  - `isCompleted` true when last status is `completed`

## Key Fix

`TrackingUpdating extends TrackingLoaded` — both share `(events, bookingId)`. Without a `runtimeType` guard in `TrackingLoaded.==`, Bloc would see them as equal and suppress the emit. Fix: `runtimeType == other.runtimeType &&` added to equality operator.
