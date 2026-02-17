import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/app/view/shell_page.dart';
import 'package:bookmi_app/features/placeholder/bookings_placeholder_page.dart';
import 'package:bookmi_app/features/placeholder/home_placeholder_page.dart';
import 'package:bookmi_app/features/placeholder/messages_placeholder_page.dart';
import 'package:bookmi_app/features/placeholder/profile_placeholder_page.dart';
import 'package:bookmi_app/features/placeholder/search_placeholder_page.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

final rootNavigatorKey = GlobalKey<NavigatorState>();

final appRouter = GoRouter(
  navigatorKey: rootNavigatorKey,
  initialLocation: RoutePaths.home,
  routes: [
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
              builder: (context, state) => const SearchPlaceholderPage(),
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
