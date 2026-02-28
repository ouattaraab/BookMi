import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
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
import 'package:google_fonts/google_fonts.dart';

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

// ── Auth required sheet ───────────────────────────────────────────────────────

class _AuthRequiredSheet extends StatelessWidget {
  const _AuthRequiredSheet();

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF1A2744).withValues(alpha: 0.97),
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
      ),
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 36,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Handle
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.3),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 28),

          // Icône
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: const Color(0xFFFF6B35).withValues(alpha: 0.12),
              shape: BoxShape.circle,
              border: Border.all(
                color: const Color(0xFFFF6B35).withValues(alpha: 0.3),
              ),
            ),
            child: const Icon(
              Icons.lock_outline_rounded,
              color: Color(0xFFFF6B35),
              size: 26,
            ),
          ),
          const SizedBox(height: 16),

          // Titre
          Text(
            'Connexion requise',
            style: GoogleFonts.nunito(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 8),

          // Sous-titre
          Text(
            'Connectez-vous pour accéder à vos réservations et messages.',
            style: GoogleFonts.manrope(
              fontSize: 14,
              color: Colors.white.withValues(alpha: 0.65),
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 28),

          // Bouton Se connecter
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFFF6B35),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
                elevation: 0,
              ),
              onPressed: () {
                Navigator.of(context).pop();
                context.go(RoutePaths.login);
              },
              child: Text(
                'Se connecter',
                style: GoogleFonts.nunito(
                  fontWeight: FontWeight.w800,
                  fontSize: 15,
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Bouton Créer un compte
          SizedBox(
            width: double.infinity,
            child: OutlinedButton(
              style: OutlinedButton.styleFrom(
                foregroundColor: Colors.white,
                side: BorderSide(color: Colors.white.withValues(alpha: 0.3)),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              onPressed: () {
                Navigator.of(context).pop();
                context.go(RoutePaths.register);
              },
              child: Text(
                'Créer un compte',
                style: GoogleFonts.manrope(
                  fontWeight: FontWeight.w600,
                  fontSize: 15,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Shell page state ──────────────────────────────────────────────────────────

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
    if (authState is! AuthAuthenticated) return;
    final isTalent = authState.roles.contains('talent');
    _profileBloc?.add(ProfileStatsFetched(isTalent: isTalent));
  }

  void _showAuthRequiredSheet(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => const _AuthRequiredSheet(),
    );
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
        final bloc = ProfileBloc(repository: widget.profileRepository);
        if (authState is AuthAuthenticated) {
          final isTalent = authState.roles.contains('talent');
          bloc.add(ProfileStatsFetched(isTalent: isTalent));
        }
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
              onTap: (index) {
                if (index == 2 || index == 3) {
                  final authState = context.read<AuthBloc>().state;
                  if (authState is! AuthAuthenticated) {
                    _showAuthRequiredSheet(context);
                    return;
                  }
                }
                widget.navigationShell.goBranch(
                  index,
                  initialLocation: index == widget.navigationShell.currentIndex,
                );
              },
            ),
          );
        },
      ),
    );
  }
}
