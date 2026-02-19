import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/otp_input.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

class OtpPage extends StatefulWidget {
  const OtpPage({required this.phone, super.key});

  final String phone;

  @override
  State<OtpPage> createState() => _OtpPageState();
}

class _OtpPageState extends State<OtpPage> {
  final _otpKey = GlobalKey<OtpInputState>();
  Timer? _timer;
  int _secondsRemaining = 60;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _startTimer();
  }

  void _startTimer() {
    _secondsRemaining = 60;
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_secondsRemaining > 0) {
        setState(() => _secondsRemaining--);
      } else {
        timer.cancel();
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  String get _maskedPhone {
    final phone = widget.phone;
    // Expects format: +225XXXXXXXXXX (country code 4 chars + 10 local digits)
    if (phone.length < 8) return phone;
    // +225 07 XX XX XX 04
    final country = phone.substring(0, 4); // '+225'
    final localStart = phone.substring(4, 6); // first 2 local digits e.g. '07'
    final suffix = phone.substring(phone.length - 2); // last 2 digits
    return '$country $localStart XX XX XX $suffix';
  }

  void _onOtpCompleted(String code) {
    if (_isSubmitting) return;
    _isSubmitting = true;
    context.read<AuthBloc>().add(
      AuthOtpSubmitted(phone: widget.phone, code: code),
    );
  }

  void _onResend() {
    context.read<AuthBloc>().add(
      AuthOtpResendRequested(phone: widget.phone),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        switch (state) {
          case AuthAuthenticated():
            context.go(RoutePaths.home);
          case AuthOtpResent():
            _isSubmitting = false;
            _otpKey.currentState?.clear();
            _startTimer();
            ScaffoldMessenger.of(context)
              ..hideCurrentSnackBar()
              ..showSnackBar(
                const SnackBar(
                  content: Text('Code renvoyé avec succès.'),
                  backgroundColor: BookmiColors.success,
                ),
              );
          case AuthFailure(:final message, :final details):
            _isSubmitting = false;
            _otpKey.currentState?.clear();
            final remaining =
                details?['remaining_attempts'] as int?;
            final displayMessage = remaining != null
                ? '$message ($remaining tentatives restantes)'
                : message;
            ScaffoldMessenger.of(context)
              ..hideCurrentSnackBar()
              ..showSnackBar(
                SnackBar(
                  content: Text(displayMessage),
                  backgroundColor: BookmiColors.error,
                ),
              );
          default:
            _isSubmitting = false;
        }
      },
      child: Scaffold(
        body: DecoratedBox(
          decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
          child: SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(BookmiSpacing.spaceLg),
                child: Column(
                  children: [
                    const Icon(
                      Icons.sms_outlined,
                      size: 60,
                      color: BookmiColors.ctaOrange,
                    ),
                    const SizedBox(height: BookmiSpacing.spaceBase),
                    Text(
                      'Vérification OTP',
                      style: Theme.of(context).textTheme.headlineLarge
                          ?.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceSm),
                    Text(
                      'Un code a été envoyé au',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.7),
                      ),
                    ),
                    Text(
                      _maskedPhone,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceXl),
                    GlassCard(
                      child: Column(
                        children: [
                          BlocBuilder<AuthBloc, AuthState>(
                            builder: (context, state) {
                              return OtpInput(
                                key: _otpKey,
                                onCompleted: _onOtpCompleted,
                                enabled: state is! AuthLoading,
                              );
                            },
                          ),
                          const SizedBox(height: BookmiSpacing.spaceLg),
                          BlocBuilder<AuthBloc, AuthState>(
                            builder: (context, state) {
                              if (state is AuthLoading) {
                                return const CircularProgressIndicator(
                                  color: BookmiColors.brandBlue,
                                );
                              }
                              return const SizedBox.shrink();
                            },
                          ),
                          const SizedBox(height: BookmiSpacing.spaceBase),
                          // Resend button
                          if (_secondsRemaining > 0)
                            Text(
                              'Renvoyer le code dans '
                              '${_secondsRemaining}s',
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.5),
                              ),
                            )
                          else
                            TextButton(
                              onPressed: _onResend,
                              child: const Text(
                                'Renvoyer le code',
                                style: TextStyle(
                                  color: BookmiColors.ctaOrange,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
