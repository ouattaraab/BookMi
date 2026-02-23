import 'dart:async';
import 'dart:math' as math;

import 'package:bookmi_app/app/routes/route_names.dart';
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

class _SplashPageState extends State<SplashPage>
    with SingleTickerProviderStateMixin {
  static const _kTimeoutSeconds = 10;
  Timer? _timeoutTimer;

  late final AnimationController _ctrl;

  // Phase 1 – entry
  late final Animation<double> _fadeIn;
  late final Animation<double> _scale;
  late final Animation<double> _rotation;

  // Phase 2 – orange energy
  late final Animation<double> _orangeGlow;
  late final Animation<double> _ringGlow;

  // Phase 3 – blue settle
  late final Animation<double> _blueGlow;
  late final Animation<double> _spinnerOpacity;

  @override
  void initState() {
    super.initState();

    // Auth check (unchanged logic)
    final bloc = context.read<AuthBloc>()..add(const AuthCheckRequested());
    _timeoutTimer = Timer(const Duration(seconds: _kTimeoutSeconds), () {
      final state = bloc.state;
      if (state is AuthInitial || state is AuthLoading) {
        bloc.add(const AuthSessionExpired());
      }
    });

    // ── Animation controller (2 500 ms total) ──────────────────────────────
    _ctrl = AnimationController(
      duration: const Duration(milliseconds: 2500),
      vsync: this,
    );

    // Fade in  0ms → 300ms
    _fadeIn = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.0, 0.12, curve: Curves.easeOut),
      ),
    );

    // Scale  0ms → 900ms  (0.82 → 1.0)
    _scale = Tween<double>(begin: 0.82, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.0, 0.36, curve: Curves.easeOutCubic),
      ),
    );

    // Rotation wobble  0ms → 550ms  (0 → 0.024 → 0 rad ≈ 1.4°)
    _rotation = TweenSequence<double>([
      TweenSequenceItem(tween: Tween(begin: 0.0, end: 0.024), weight: 14),
      TweenSequenceItem(tween: Tween(begin: 0.024, end: 0.0), weight: 8),
      TweenSequenceItem(tween: ConstantTween(0.0), weight: 78),
    ]).animate(_ctrl);

    // Orange glow  peak ~1 050 ms
    //   0   -  750ms : off        (weight 30)
    //   750 - 1050ms : 0 → 1      (weight 12)
    //  1050 - 1500ms : 1 → 0      (weight 18)
    //  1500 - 2500ms : off        (weight 40)
    _orangeGlow = TweenSequence<double>([
      TweenSequenceItem(tween: ConstantTween(0.0), weight: 30),
      TweenSequenceItem(tween: Tween(begin: 0.0, end: 1.0), weight: 12),
      TweenSequenceItem(tween: Tween(begin: 1.0, end: 0.0), weight: 18),
      TweenSequenceItem(tween: ConstantTween(0.0), weight: 40),
    ]).animate(_ctrl);

    // Orange ring  peak ~1 250 ms
    //   0   - 1000ms : off        (weight 40)
    //  1000 - 1250ms : 0 → 1      (weight 10)
    //  1250 - 1700ms : 1 → 0      (weight 18)
    //  1700 - 2500ms : off        (weight 32)
    _ringGlow = TweenSequence<double>([
      TweenSequenceItem(tween: ConstantTween(0.0), weight: 40),
      TweenSequenceItem(tween: Tween(begin: 0.0, end: 1.0), weight: 10),
      TweenSequenceItem(tween: Tween(begin: 1.0, end: 0.0), weight: 18),
      TweenSequenceItem(tween: ConstantTween(0.0), weight: 32),
    ]).animate(_ctrl);

    // Blue glow  1 500ms → 2 500ms  (fades to 0.4 at end)
    _blueGlow = TweenSequence<double>([
      TweenSequenceItem(tween: ConstantTween(0.0), weight: 60),
      TweenSequenceItem(tween: Tween(begin: 0.0, end: 1.0), weight: 16),
      TweenSequenceItem(tween: Tween(begin: 1.0, end: 0.4), weight: 24),
    ]).animate(_ctrl);

    // Spinner  2 000ms → 2 500ms
    _spinnerOpacity = TweenSequence<double>([
      TweenSequenceItem(tween: ConstantTween(0.0), weight: 80),
      TweenSequenceItem(tween: Tween(begin: 0.0, end: 1.0), weight: 8),
      TweenSequenceItem(tween: ConstantTween(1.0), weight: 12),
    ]).animate(_ctrl);

    _ctrl.forward();
  }

  @override
  void dispose() {
    _timeoutTimer?.cancel();
    _ctrl.dispose();
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
      child: Scaffold(
        backgroundColor: Colors.white,
        body: AnimatedBuilder(
          animation: _ctrl,
          builder: (context, _) {
            return Stack(
              children: [
                // ── Centred animated logo ──────────────────────────────────
                Center(
                  child: Opacity(
                    opacity: _fadeIn.value,
                    child: Transform.scale(
                      scale: _scale.value,
                      child: Transform.rotate(
                        angle: _rotation.value,
                        child: SizedBox(
                          width: 300,
                          height: 180,
                          child: Stack(
                            alignment: Alignment.center,
                            children: [
                              // Blue glow (back layer)
                              if (_blueGlow.value > 0)
                                _BlueGlowLayer(intensity: _blueGlow.value),
                              // Orange ring
                              if (_ringGlow.value > 0)
                                _OrangeRingLayer(intensity: _ringGlow.value),
                              // Orange burst + sparks
                              if (_orangeGlow.value > 0)
                                _OrangeBurstLayer(intensity: _orangeGlow.value),
                              // Logo (top layer)
                              Image.asset(
                                'assets/images/bookmi_logo.png',
                                width: 260,
                                fit: BoxFit.contain,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ),

                // ── Loading spinner ────────────────────────────────────────
                if (_spinnerOpacity.value > 0)
                  Positioned(
                    bottom: 72,
                    left: 0,
                    right: 0,
                    child: Opacity(
                      opacity: _spinnerOpacity.value,
                      child: const Center(
                        child: SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 1.5,
                            valueColor: AlwaysStoppedAnimation<Color>(
                              Color(0xFFCBD5E1),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

// ── Effect layers ────────────────────────────────────────────────────────────

/// Soft blue radial glow — visible from Frame 9 (2.0 s) onwards
class _BlueGlowLayer extends StatelessWidget {
  const _BlueGlowLayer({required this.intensity});

  final double intensity;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 340,
      height: 220,
      decoration: BoxDecoration(
        gradient: RadialGradient(
          colors: [
            const Color(0xFF3B82F6).withOpacity(0.28 * intensity),
            const Color(0xFF60A5FA).withOpacity(0.10 * intensity),
            Colors.transparent,
          ],
          stops: const [0.0, 0.5, 1.0],
          radius: 0.65,
        ),
      ),
    );
  }
}

/// Orange radial burst + radiating sparks — Frame 5–6 (0.8 s → 1.1 s)
class _OrangeBurstLayer extends StatelessWidget {
  const _OrangeBurstLayer({required this.intensity});

  final double intensity;

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: const Size(320, 200),
      painter: _BurstPainter(intensity: intensity),
    );
  }
}

class _BurstPainter extends CustomPainter {
  const _BurstPainter({required this.intensity});

  final double intensity;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);

    // Soft radial blob
    final blobPaint = Paint()
      ..maskFilter = MaskFilter.blur(BlurStyle.normal, 22 * intensity)
      ..shader = RadialGradient(
        colors: [
          const Color(0xFFFF6B35).withOpacity(0.82 * intensity),
          const Color(0xFFFF8C42).withOpacity(0.42 * intensity),
          const Color(0xFFFFB870).withOpacity(0.12 * intensity),
          Colors.transparent,
        ],
        stops: const [0.0, 0.32, 0.62, 1.0],
      ).createShader(
        Rect.fromCenter(
          center: center,
          width: size.width * 0.88,
          height: size.height * 0.82,
        ),
      );

    canvas.drawOval(
      Rect.fromCenter(
        center: center,
        width: size.width * 0.88,
        height: size.height * 0.82,
      ),
      blobPaint,
    );

    // Radiating sparks (deterministic seed = reproducible each frame)
    final rng = math.Random(42);
    for (var i = 0; i < 28; i++) {
      final angle = (i / 28) * 2 * math.pi + rng.nextDouble() * 0.28;
      final innerR = 36.0 + rng.nextDouble() * 14;
      final outerR = innerR + 16 + rng.nextDouble() * 44;
      final sparkOpacity = (0.38 + rng.nextDouble() * 0.48) * intensity;

      canvas.drawLine(
        Offset(
          center.dx + math.cos(angle) * innerR,
          center.dy + math.sin(angle) * innerR * 0.54,
        ),
        Offset(
          center.dx + math.cos(angle) * outerR,
          center.dy + math.sin(angle) * outerR * 0.54,
        ),
        Paint()
          ..color = Color.lerp(
            const Color(0xFFFF6B35),
            const Color(0xFFFFD000),
            rng.nextDouble(),
          )!
              .withOpacity(sparkOpacity)
          ..strokeWidth = 1.1 + rng.nextDouble() * 0.9
          ..strokeCap = StrokeCap.round,
      );
    }
  }

  @override
  bool shouldRepaint(_BurstPainter old) => old.intensity != intensity;
}

/// Orange torus ring — Frame 7 (1.2 s)
class _OrangeRingLayer extends StatelessWidget {
  const _OrangeRingLayer({required this.intensity});

  final double intensity;

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: const Size(320, 200),
      painter: _RingPainter(intensity: intensity),
    );
  }
}

class _RingPainter extends CustomPainter {
  const _RingPainter({required this.intensity});

  final double intensity;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final rx = size.width * 0.40;
    final ry = size.height * 0.42;
    final rect = Rect.fromCenter(center: center, width: rx * 2, height: ry * 2);

    // Blurred outer ring
    canvas.drawOval(
      rect,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 30 * intensity
        ..maskFilter = MaskFilter.blur(BlurStyle.normal, 18 * intensity)
        ..color = const Color(0xFFFF7A3C).withOpacity(0.52 * intensity),
    );

    // Sharp inner highlight
    canvas.drawOval(
      rect,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2.5 * intensity
        ..color = const Color(0xFFFFB070).withOpacity(0.82 * intensity),
    );
  }

  @override
  bool shouldRepaint(_RingPainter old) => old.intensity != intensity;
}
