import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:hive_ce/hive.dart';

class SplashPage extends StatefulWidget {
  const SplashPage({super.key});

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage> {
  @override
  void initState() {
    super.initState();
    context.read<AuthBloc>().add(const AuthCheckRequested());
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        switch (state) {
          case AuthAuthenticated():
            context.go(RoutePaths.home);
          case AuthUnauthenticated():
            final settingsBox = Hive.box<dynamic>('settings');
            final hasSeen = settingsBox.get('has_seen_onboarding') as bool?;
            if (hasSeen == true) {
              context.go(RoutePaths.login);
            } else {
              context.go(RoutePaths.onboarding);
            }
          case AuthInitial():
          case AuthLoading():
          case AuthRegistrationSuccess():
          case AuthOtpResent():
          case AuthForgotPasswordSuccess():
          case AuthFailure():
            break;
        }
      },
      child: DecoratedBox(
        decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(
                Icons.music_note_rounded,
                size: 80,
                color: BookmiColors.ctaOrange,
              ),
              const SizedBox(height: 16),
              Text(
                'BookMi',
                style: Theme.of(context).textTheme.displayLarge?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
