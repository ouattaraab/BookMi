import 'dart:async';

import 'package:bookmi_app/app/routes/guards/auth_guard.dart';
import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/app/view/shell_page.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/evaluation/data/repositories/review_repository.dart';
import 'package:bookmi_app/features/evaluation/presentation/pages/evaluation_page.dart';
import 'package:bookmi_app/features/onboarding/data/repositories/onboarding_repository.dart';
import 'package:bookmi_app/features/onboarding/presentation/pages/talent_onboarding_page.dart';
import 'package:bookmi_app/features/tracking/data/repositories/tracking_repository.dart';
import 'package:bookmi_app/features/tracking/presentation/pages/tracking_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/forgot_password_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/login_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/onboarding_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/otp_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/register_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/splash_page.dart';
import 'package:bookmi_app/features/discovery/presentation/pages/discovery_page.dart';
import 'package:bookmi_app/features/discovery/presentation/pages/home_page.dart';
import 'package:bookmi_app/features/booking/booking.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_cubit.dart';
import 'package:bookmi_app/features/messaging/data/repositories/messaging_repository.dart';
import 'package:bookmi_app/features/messaging/presentation/pages/conversation_list_page.dart';
import 'package:bookmi_app/features/profile/presentation/pages/profile_page.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_bloc.dart';
import 'package:bookmi_app/features/talent_profile/data/repositories/talent_profile_repository.dart';
import 'package:bookmi_app/features/talent_profile/presentation/pages/talent_profile_page.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

final rootNavigatorKey = GlobalKey<NavigatorState>();

/// Adapts a [Stream] to a [ChangeNotifier] so GoRouter can listen for
/// auth state changes and re-evaluate its redirect.
class _GoRouterRefreshStream extends ChangeNotifier {
  _GoRouterRefreshStream(Stream<AuthState> stream) {
    _subscription = stream.listen((_) => notifyListeners());
  }

  late final StreamSubscription<AuthState> _subscription;

  @override
  void dispose() {
    unawaited(_subscription.cancel());
    super.dispose();
  }
}

GoRouter buildAppRouter(
  TalentProfileRepository talentProfileRepo,
  AuthBloc authBloc,
  BookingRepository bookingRepo,
  TrackingRepository trackingRepo,
  ReviewRepository reviewRepo,
  OnboardingRepository onboardingRepo,
  MessagingRepository messagingRepo,
) {
  return GoRouter(
    navigatorKey: rootNavigatorKey,
    initialLocation: RoutePaths.splash,
    refreshListenable: _GoRouterRefreshStream(authBloc.stream),
    redirect: (context, state) {
      final location = state.matchedLocation;
      return authGuard(context, location);
    },
    routes: [
      // ── Auth routes (outside shell) ──────────────────────
      GoRoute(
        path: RoutePaths.splash,
        name: RouteNames.splash,
        builder: (context, state) => const SplashPage(),
      ),
      GoRoute(
        path: RoutePaths.onboarding,
        name: RouteNames.onboarding,
        builder: (context, state) => const OnboardingPage(),
      ),
      GoRoute(
        path: RoutePaths.login,
        name: RouteNames.login,
        builder: (context, state) => const LoginPage(),
      ),
      GoRoute(
        path: RoutePaths.register,
        name: RouteNames.register,
        builder: (context, state) => const RegisterPage(),
      ),
      GoRoute(
        path: RoutePaths.otp,
        name: RouteNames.otp,
        builder: (context, state) {
          final phone = state.extra as String? ?? '';
          return OtpPage(phone: phone);
        },
      ),
      GoRoute(
        path: RoutePaths.forgotPassword,
        name: RouteNames.forgotPassword,
        builder: (context, state) => const ForgotPasswordPage(),
      ),
      GoRoute(
        path: RoutePaths.talentOnboarding,
        name: RouteNames.talentOnboarding,
        builder: (context, state) =>
            TalentOnboardingPage(repository: onboardingRepo),
      ),

      // ── Main app shell (authenticated) ───────────────────
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) =>
            ShellPage(navigationShell: navigationShell),
        branches: [
          // Branch 0: Home
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.home,
                name: RouteNames.home,
                builder: (context, state) => const HomePage(),
              ),
            ],
          ),
          // Branch 1: Search / Discovery
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.search,
                name: RouteNames.search,
                builder: (context, state) => const DiscoveryPage(),
                routes: [
                  GoRoute(
                    path: RoutePaths.talentDetail,
                    name: RouteNames.talentDetail,
                    parentNavigatorKey: rootNavigatorKey,
                    builder: (context, state) {
                      final slug = state.pathParameters['slug'] ?? '';
                      final extra = state.extra as Map<String, dynamic>?;
                      return MultiRepositoryProvider(
                        providers: [
                          RepositoryProvider.value(value: bookingRepo),
                        ],
                        child: BlocProvider(
                          create: (_) => TalentProfileBloc(
                            repository: talentProfileRepo,
                          ),
                          child: TalentProfilePage(
                            slug: slug,
                            initialData: extra,
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
          // Branch 2: Bookings
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.bookings,
                name: RouteNames.bookings,
                builder: (context, state) => RepositoryProvider.value(
                  value: bookingRepo,
                  child: const BookingsPage(),
                ),
                routes: [
                  GoRoute(
                    path: RoutePaths.bookingDetail,
                    name: RouteNames.bookingDetail,
                    parentNavigatorKey: rootNavigatorKey,
                    builder: (context, state) {
                      final id = int.tryParse(
                            state.pathParameters['id'] ?? '',
                          ) ??
                          0;
                      final preloaded = state.extra as BookingModel?;
                      return RepositoryProvider.value(
                        value: bookingRepo,
                        child: BookingDetailPage(
                          bookingId: id,
                          preloaded: preloaded,
                        ),
                      );
                    },
                    routes: [
                      GoRoute(
                        path: RoutePaths.tracking,
                        name: RouteNames.tracking,
                        parentNavigatorKey: rootNavigatorKey,
                        builder: (context, state) {
                          final id = int.tryParse(
                                state.pathParameters['id'] ?? '',
                              ) ??
                              0;
                          return TrackingPage(
                            bookingId: id,
                            repository: trackingRepo,
                          );
                        },
                      ),
                      GoRoute(
                        path: RoutePaths.evaluation,
                        name: RouteNames.evaluation,
                        parentNavigatorKey: rootNavigatorKey,
                        builder: (context, state) {
                          final id = int.tryParse(
                                state.pathParameters['id'] ?? '',
                              ) ??
                              0;
                          final type =
                              state.uri.queryParameters['type'] ??
                              'client_to_talent';
                          return EvaluationPage(
                            bookingId: id,
                            type: type,
                            repository: reviewRepo,
                          );
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
          // Branch 3: Messages
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.messages,
                name: RouteNames.messages,
                builder: (context, state) => RepositoryProvider.value(
                  value: messagingRepo,
                  child: BlocProvider(
                    create: (_) =>
                        MessagingCubit(repository: messagingRepo),
                    child: const ConversationListPage(),
                  ),
                ),
              ),
            ],
          ),
          // Branch 4: Profile
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.profile,
                name: RouteNames.profile,
                builder: (context, state) => const ProfilePage(),
              ),
            ],
          ),
        ],
      ),
    ],
  );
}
