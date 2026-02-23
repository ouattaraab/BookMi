import 'dart:async';

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
  static const _kTimeoutSeconds = 10;
  Timer? _timeoutTimer;

  @override
  void initState() {
    super.initState();
    final bloc = context.read<AuthBloc>()..add(const AuthCheckRequested());
    _timeoutTimer = Timer(const Duration(seconds: _kTimeoutSeconds), () {
      final state = bloc.state;
      if (state is AuthInitial || state is AuthLoading) {
        bloc.add(const AuthSessionExpired());
      }
    });
  }

  @override
  void dispose() {
    _timeoutTimer?.cancel();
    super.dispose();
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
      child: const ColoredBox(
        color: Colors.white,
        child: Center(
          child: Padding(
            padding: EdgeInsets.symmetric(horizontal: 48),
            child: Image(
              image: AssetImage('assets/images/bookmi_logo.png'),
              width: 260,
              fit: BoxFit.contain,
            ),
          ),
        ),
      ),
    );
  }
}
