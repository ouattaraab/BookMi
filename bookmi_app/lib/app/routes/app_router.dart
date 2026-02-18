import 'dart:async';

import 'package:bookmi_app/app/routes/guards/auth_guard.dart';
import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/app/view/shell_page.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/presentation/pages/forgot_password_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/login_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/onboarding_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/otp_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/register_page.dart';
import 'package:bookmi_app/features/auth/presentation/pages/splash_page.dart';
import 'package:bookmi_app/features/discovery/presentation/pages/discovery_page.dart';
import 'package:bookmi_app/features/placeholder/bookings_placeholder_page.dart';
import 'package:bookmi_app/features/placeholder/home_placeholder_page.dart';
import 'package:bookmi_app/features/placeholder/messages_placeholder_page.dart';
import 'package:bookmi_app/features/placeholder/profile_placeholder_page.dart';
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

      // ── Main app shell (authenticated) ───────────────────
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) =>
            ShellPage(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.home,
                name: RouteNames.home,
                builder: (context, state) => const HomePlaceholderPage(),
              ),
            ],
          ),
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
                      return BlocProvider(
                        create: (_) => TalentProfileBloc(
                          repository: talentProfileRepo,
                        ),
                        child: TalentProfilePage(
                          slug: slug,
                          initialData: extra,
                        ),
                      );
                    },
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.bookings,
                name: RouteNames.bookings,
                builder: (context, state) => const BookingsPlaceholderPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.messages,
                name: RouteNames.messages,
                builder: (context, state) => const MessagesPlaceholderPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: RoutePaths.profile,
                name: RouteNames.profile,
                builder: (context, state) => const ProfilePlaceholderPage(),
              ),
            ],
          ),
        ],
      ),
    ],
  );
}
