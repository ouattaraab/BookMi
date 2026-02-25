import 'dart:async';

import 'package:bookmi_app/core/design_system/components/glass_bottom_nav.dart';
import 'package:bookmi_app/core/design_system/components/glass_logo_bar.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/services/notification_service.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/profile/bloc/profile_bloc.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

class ShellPage extends StatefulWidget {
  const ShellPage({
    required this.navigationShell,
    required this.profileRepository,
    super.key,
  });

  final StatefulNavigationShell navigationShell;
  final ProfileRepository profileRepository;

  @override
  State<ShellPage> createState() => _ShellPageState();
}

class _ShellPageState extends State<ShellPage> {
  StreamSubscription<RemoteMessage>? _fcmSub;
  StreamSubscription<void>? _readSub;

  // Kept to dispatch refresh events from outside the BlocProvider subtree.
  ProfileBloc? _profileBloc;

  @override
  void initState() {
    super.initState();

    // Re-fetch stats when a foreground FCM message arrives (badge increment).
    _fcmSub = FirebaseMessaging.onMessage.listen((_) {
      // Small delay so the backend has time to persist the notification record.
      Future.delayed(const Duration(seconds: 2), _refreshStats);
    });

    // Re-fetch stats when the notifications page marks all as read (badge → 0).
    _readSub = NotificationService.instance.onNotificationsRead.listen((_) {
      _refreshStats();
    });
  }

  void _refreshStats() {
    if (!mounted) return;
    final authState = context.read<AuthBloc>().state;
    final isTalent =
        authState is AuthAuthenticated && authState.roles.contains('talent');
    _profileBloc?.add(ProfileStatsFetched(isTalent: isTalent));
  }

  @override
  void dispose() {
    _fcmSub?.cancel();
    _readSub?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) {
        final authState = context.read<AuthBloc>().state;
        final isTalent = authState is AuthAuthenticated &&
            authState.roles.contains('talent');
        final bloc = ProfileBloc(repository: widget.profileRepository)
          ..add(ProfileStatsFetched(isTalent: isTalent));
        _profileBloc = bloc;
        return bloc;
      },
      child: BlocBuilder<ProfileBloc, ProfileState>(
        builder: (context, profileState) {
          int pendingCount = 0;
          if (profileState is ProfileLoaded) {
            pendingCount = profileState.stats.pendingBookingCount;
          }
          return Scaffold(
            backgroundColor: BookmiColors.backgroundDeep,
            appBar: const GlassLogoBar(),
            extendBody: true,
            body: Stack(
              children: [
                // ── Glow 1 : double halo bleu en haut ──────────────
                Positioned(
                  top: -40,
                  left: -20,
                  right: -20,
                  height: 300,
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: RadialGradient(
                        center: const Alignment(0, -0.2),
                        radius: 1.0,
                        colors: [
                          const Color(0xFF2196F3).withValues(alpha: 0.30),
                          const Color(0xFF38BDF8).withValues(alpha: 0.10),
                          Colors.transparent,
                        ],
                        stops: const [0.0, 0.45, 1.0],
                      ),
                    ),
                  ),
                ),
                // ── Glow 2 : orbe teal bas-gauche ──────────────────
                Positioned(
                  bottom: 0,
                  left: -30,
                  width: 240,
                  height: 240,
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: RadialGradient(
                        center: const Alignment(-0.4, 0.6),
                        radius: 0.9,
                        colors: [
                          const Color(0xFF00BCD4).withValues(alpha: 0.18),
                          Colors.transparent,
                        ],
                      ),
                    ),
                  ),
                ),
                // ── Glow 3 : orbe bleu bas-droite ──────────────────
                Positioned(
                  bottom: 0,
                  right: -30,
                  width: 240,
                  height: 240,
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: RadialGradient(
                        center: const Alignment(0.4, 0.6),
                        radius: 0.9,
                        colors: [
                          const Color(0xFF2196F3).withValues(alpha: 0.22),
                          Colors.transparent,
                        ],
                      ),
                    ),
                  ),
                ),
                widget.navigationShell,
              ],
            ),
            bottomNavigationBar: GlassBottomNav(
              currentIndex: widget.navigationShell.currentIndex,
              bookingsBadge: pendingCount,
              onTap: (index) => widget.navigationShell.goBranch(
                index,
                initialLocation: index == widget.navigationShell.currentIndex,
              ),
            ),
          );
        },
      ),
    );
  }
}
