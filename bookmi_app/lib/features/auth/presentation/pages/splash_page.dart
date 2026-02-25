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

// ── Star model ────────────────────────────────────────────────────────────────

class _Star {
  const _Star({
    required this.x,
    required this.y,
    required this.size,
    required this.phase,
    required this.baseOpacity,
    required this.warm, // true = orange-tinted, false = cool-blue-tinted
  });

  final double x, y, size, phase, baseOpacity;
  final bool warm;
}

List<_Star> _buildStars(int n) {
  final rng = math.Random(7331);
  return List.generate(n, (_) {
    final warm = rng.nextDouble() < 0.18; // 18 % warm stars
    return _Star(
      x: rng.nextDouble(),
      y: rng.nextDouble(),
      size: 0.8 + rng.nextDouble() * 2.8,
      phase: rng.nextDouble() * 2 * math.pi,
      baseOpacity: 0.25 + rng.nextDouble() * 0.55,
      warm: warm,
    );
  });
}

// ── SplashPage ────────────────────────────────────────────────────────────────

class SplashPage extends StatefulWidget {
  const SplashPage({super.key});

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage>
    with SingleTickerProviderStateMixin {
  static const _kTimeoutSeconds = 10;
  static final _stars = _buildStars(62);

  Timer? _timeoutTimer;
  late final AnimationController _ctrl;

  // Interval map (ms → normalised 0–1 over 3 000 ms)
  //  0   –  400 ms  →  0.000 – 0.133   stars fade in
  //  400 –  900 ms  →  0.133 – 0.300   spotlight + logo reveal
  //  900 – 1 400 ms →  0.300 – 0.467   orange pulse
  // 1 400 – 2 000 ms → 0.467 – 0.667   logo breath + tagline
  // 2 200 – 3 000 ms → 0.733 – 1.000   spinner

  late final Animation<double> _starsFade;
  late final Animation<double> _spotlight;
  late final Animation<double> _logoFade;
  late final Animation<double> _logoEntryScale;
  late final Animation<double> _pulseProgress;
  late final Animation<double> _logoBreath;
  late final Animation<double> _taglineFade;
  late final Animation<double> _taglineRise;
  late final Animation<double> _spinnerFade;

  @override
  void initState() {
    super.initState();

    // ── Auth check (unchanged) ────────────────────────────────────────────
    final bloc = context.read<AuthBloc>()..add(const AuthCheckRequested());
    _timeoutTimer = Timer(const Duration(seconds: _kTimeoutSeconds), () {
      final s = bloc.state;
      if (s is AuthInitial || s is AuthLoading) {
        bloc.add(const AuthSessionExpired());
      }
    });

    // ── Controller ───────────────────────────────────────────────────────
    _ctrl = AnimationController(
      duration: const Duration(milliseconds: 3000),
      vsync: this,
    );

    // Stars  0 → 400 ms
    _starsFade = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.0, 0.133, curve: Curves.easeOut),
      ),
    );

    // Spotlight  400 → 900 ms
    _spotlight = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.133, 0.30, curve: Curves.easeOutCubic),
      ),
    );

    // Logo fade in  350 → 800 ms
    _logoFade = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.117, 0.267, curve: Curves.easeOut),
      ),
    );

    // Logo entry scale  350 → 800 ms  (0.86 → 1.0, overshoot)
    _logoEntryScale = Tween<double>(begin: 0.86, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.117, 0.267, curve: Curves.easeOutBack),
      ),
    );

    // Orange pulse  900 → 1 400 ms
    _pulseProgress = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.30, 0.467, curve: Curves.easeOut),
      ),
    );

    // Logo breath  1 400 → 2 000 ms  (1.0 → 1.042 → 1.0)
    _logoBreath =
        TweenSequence<double>([
          TweenSequenceItem(tween: Tween(begin: 1.0, end: 1.042), weight: 50),
          TweenSequenceItem(tween: Tween(begin: 1.042, end: 1.0), weight: 50),
        ]).animate(
          CurvedAnimation(
            parent: _ctrl,
            curve: const Interval(0.467, 0.667, curve: Curves.easeInOut),
          ),
        );

    // Tagline  1 600 → 2 100 ms
    _taglineFade = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.533, 0.70, curve: Curves.easeOut),
      ),
    );
    _taglineRise = Tween<double>(begin: 20.0, end: 0.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.533, 0.70, curve: Curves.easeOut),
      ),
    );

    // Spinner  2 200 → 3 000 ms
    _spinnerFade = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _ctrl,
        curve: const Interval(0.733, 0.90, curve: Curves.easeOut),
      ),
    );

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
        backgroundColor: const Color(0xFF0D1B35),
        body: AnimatedBuilder(
          animation: _ctrl,
          builder: (context, _) {
            final size = MediaQuery.sizeOf(context);
            return Stack(
              children: [
                // ── Deep navy radial gradient background ──────────────────
                Positioned.fill(
                  child: DecoratedBox(
                    decoration: const BoxDecoration(
                      gradient: RadialGradient(
                        center: Alignment.center,
                        radius: 1.3,
                        colors: [Color(0xFF1E2D4E), Color(0xFF080E1E)],
                      ),
                    ),
                  ),
                ),

                // ── Subtle stage-arc decoration ───────────────────────────
                Positioned.fill(
                  child: CustomPaint(
                    painter: _StageArcPainter(
                      opacity: _starsFade.value,
                    ),
                  ),
                ),

                // ── Twinkling star field ──────────────────────────────────
                Positioned.fill(
                  child: CustomPaint(
                    painter: _StarFieldPainter(
                      stars: _stars,
                      animValue: _ctrl.value,
                      fadeIn: _starsFade.value,
                    ),
                  ),
                ),

                // ── Spotlight glow ────────────────────────────────────────
                if (_spotlight.value > 0)
                  Positioned.fill(
                    child: CustomPaint(
                      painter: _SpotlightPainter(
                        progress: _spotlight.value,
                        screenSize: size,
                      ),
                    ),
                  ),

                // ── Orange pulse wave ─────────────────────────────────────
                if (_pulseProgress.value > 0)
                  Positioned.fill(
                    child: CustomPaint(
                      painter: _PulseWavePainter(
                        progress: _pulseProgress.value,
                        screenSize: size,
                      ),
                    ),
                  ),

                // ── Logo + tagline ────────────────────────────────────────
                Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Logo
                      Opacity(
                        opacity: _logoFade.value,
                        child: Transform.scale(
                          scale: _logoEntryScale.value * _logoBreath.value,
                          child: const _LuminousLogo(),
                        ),
                      ),

                      const SizedBox(height: 24),

                      // Tagline
                      Opacity(
                        opacity: _taglineFade.value,
                        child: Transform.translate(
                          offset: Offset(0, _taglineRise.value),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              _TaglineDash(
                                opacity: _taglineFade.value,
                              ),
                              const SizedBox(width: 10),
                              const Text(
                                'RÉSERVEZ LES MEILLEURS TALENTS',
                                style: TextStyle(
                                  color: Color(0xFF8BA8CC),
                                  fontSize: 9.5,
                                  letterSpacing: 2.4,
                                  fontWeight: FontWeight.w500,
                                  height: 1.0,
                                ),
                              ),
                              const SizedBox(width: 10),
                              _TaglineDash(
                                opacity: _taglineFade.value,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                // ── Loading spinner ───────────────────────────────────────
                if (_spinnerFade.value > 0)
                  Positioned(
                    bottom: 64,
                    left: 0,
                    right: 0,
                    child: Opacity(
                      opacity: _spinnerFade.value,
                      child: const Center(
                        child: SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 1.5,
                            valueColor: AlwaysStoppedAnimation<Color>(
                              Color(0xFFFF6B35),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),

                // ── Accent dots ───────────────────────────────────────────
                _AccentDot(
                  x: 0.08,
                  y: 0.82,
                  color: const Color(0xFFFF6B35),
                  size: 5,
                  opacity: _taglineFade.value * 0.7,
                ),
                _AccentDot(
                  x: 0.91,
                  y: 0.15,
                  color: const Color(0xFF60A5FA),
                  size: 4,
                  opacity: _taglineFade.value * 0.5,
                ),
                _AccentDot(
                  x: 0.85,
                  y: 0.75,
                  color: const Color(0xFFFF6B35),
                  size: 3,
                  opacity: _taglineFade.value * 0.45,
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

// ── Reusable widgets ──────────────────────────────────────────────────────────

/// Logo with white luminous shader mask (visible on dark bg)
class _LuminousLogo extends StatelessWidget {
  const _LuminousLogo();

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.center,
      children: [
        // Diffuse glow halo behind the logo
        Container(
          width: 280,
          height: 100,
          decoration: BoxDecoration(
            gradient: RadialGradient(
              colors: [
                const Color(0xFFFFFFFF).withOpacity(0.06),
                const Color(0xFF3B82F6).withOpacity(0.04),
                Colors.transparent,
              ],
              stops: const [0.0, 0.55, 1.0],
            ),
          ),
        ),
        // Logo — white gradient shader so it glows on the dark bg
        ShaderMask(
          shaderCallback: (bounds) => const LinearGradient(
            begin: Alignment.centerLeft,
            end: Alignment.centerRight,
            colors: [
              Color(0xFFFFFFFF), // "Book" — pure white
              Color(0xFFD0E8FF), // "Mi"   — icy blue-white
            ],
            stops: [0.50, 1.0],
          ).createShader(bounds),
          blendMode: BlendMode.srcIn,
          child: Image.asset(
            'assets/images/bookmi_logo.png',
            width: 230,
            fit: BoxFit.contain,
          ),
        ),
      ],
    );
  }
}

/// Short horizontal line on each side of the tagline
class _TaglineDash extends StatelessWidget {
  const _TaglineDash({required this.opacity});

  final double opacity;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 20,
      height: 1,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            const Color(0xFFFF6B35).withOpacity(opacity * 0.8),
            const Color(0xFFFF6B35).withOpacity(0),
          ],
        ),
      ),
    );
  }
}

/// Small glowing dot positioned using fractional screen coordinates
class _AccentDot extends StatelessWidget {
  const _AccentDot({
    required this.x,
    required this.y,
    required this.color,
    required this.size,
    required this.opacity,
  });

  final double x, y, size, opacity;
  final Color color;

  @override
  Widget build(BuildContext context) {
    if (opacity <= 0) return const SizedBox.shrink();
    final screenSize = MediaQuery.sizeOf(context);
    return Positioned(
      left: x * screenSize.width - size / 2,
      top: y * screenSize.height - size / 2,
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: color.withOpacity(opacity),
          shape: BoxShape.circle,
          boxShadow: [
            BoxShadow(
              color: color.withOpacity(opacity * 0.7),
              blurRadius: size * 2.5,
              spreadRadius: size * 0.5,
            ),
          ],
        ),
      ),
    );
  }
}

// ── Custom painters ───────────────────────────────────────────────────────────

/// Concentric arc lines — evokes a performance stage
class _StageArcPainter extends CustomPainter {
  const _StageArcPainter({required this.opacity});

  final double opacity;

  @override
  void paint(Canvas canvas, Size size) {
    if (opacity <= 0) return;

    final center = Offset(size.width / 2, size.height * 1.05);

    // Concentric arcs from bottom-center (like a stage spotlight beam)
    for (var i = 0; i < 6; i++) {
      final r = size.height * (0.25 + i * 0.14);
      final alpha = opacity * (0.038 - i * 0.005).clamp(0.0, 1.0);
      canvas.drawCircle(
        center,
        r,
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 0.5
          ..color = Colors.white.withOpacity(alpha),
      );
    }

    // Two pairs of diagonal accent lines
    final diag = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 0.5
      ..color = const Color(0xFFFF6B35).withOpacity(opacity * 0.07);

    canvas
      ..drawLine(
        Offset(size.width * 0.62, 0),
        Offset(size.width, size.height * 0.38),
        diag,
      )
      ..drawLine(
        Offset(size.width * 0.78, 0),
        Offset(size.width, size.height * 0.22),
        diag,
      )
      ..drawLine(
        Offset(size.width * 0.38, 0),
        Offset(0, size.height * 0.38),
        diag,
      )
      ..drawLine(
        Offset(size.width * 0.22, 0),
        Offset(0, size.height * 0.22),
        diag,
      );
  }

  @override
  bool shouldRepaint(_StageArcPainter old) => old.opacity != opacity;
}

/// Twinkling stars — warm (orange-tinted) and cool (blue-tinted) variants
class _StarFieldPainter extends CustomPainter {
  const _StarFieldPainter({
    required this.stars,
    required this.animValue,
    required this.fadeIn,
  });

  final List<_Star> stars;
  final double animValue;
  final double fadeIn;

  @override
  void paint(Canvas canvas, Size size) {
    if (fadeIn <= 0) return;

    final paint = Paint()..style = PaintingStyle.fill;

    for (final star in stars) {
      // Smooth twinkling with individual phase offset
      final twinkle =
          (math.sin(animValue * 4.5 * math.pi + star.phase) + 1) / 2;
      final opacity = fadeIn * star.baseOpacity * (0.35 + 0.65 * twinkle);
      if (opacity <= 0.01) continue;

      final color = star.warm
          ? const Color(0xFFFFCC80) // warm amber
          : const Color(0xFFB8D4FF); // cool blue-white

      paint
        ..color = color.withOpacity(opacity)
        ..maskFilter = star.size > 2.0
            ? MaskFilter.blur(BlurStyle.normal, star.size * 0.45)
            : null;

      canvas.drawCircle(
        Offset(star.x * size.width, star.y * size.height),
        star.size * 0.5,
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(_StarFieldPainter old) =>
      old.animValue != animValue || old.fadeIn != fadeIn;
}

/// Soft radial spotlight glow expanding from center
class _SpotlightPainter extends CustomPainter {
  const _SpotlightPainter({
    required this.progress,
    required this.screenSize,
  });

  final double progress;
  final Size screenSize;

  @override
  void paint(Canvas canvas, Size size) {
    if (progress <= 0) return;

    final center = Offset(size.width / 2, size.height / 2);
    final maxR = size.shortestSide * 0.72 * progress;

    canvas.drawCircle(
      center,
      maxR,
      Paint()
        ..shader = RadialGradient(
          colors: [
            Colors.white.withOpacity(0.055 * progress),
            Colors.white.withOpacity(0.018 * progress),
            Colors.transparent,
          ],
          stops: const [0.0, 0.5, 1.0],
        ).createShader(Rect.fromCircle(center: center, radius: maxR)),
    );

    // Second subtler halo (larger radius)
    final haloR = maxR * 1.6;
    canvas.drawCircle(
      center,
      haloR,
      Paint()
        ..shader = RadialGradient(
          colors: [
            const Color(0xFF3B82F6).withOpacity(0.022 * progress),
            Colors.transparent,
          ],
        ).createShader(Rect.fromCircle(center: center, radius: haloR)),
    );
  }

  @override
  bool shouldRepaint(_SpotlightPainter old) => old.progress != progress;
}

/// Expanding concentric orange rings (pulse)
class _PulseWavePainter extends CustomPainter {
  const _PulseWavePainter({
    required this.progress,
    required this.screenSize,
  });

  final double progress;
  final Size screenSize;

  @override
  void paint(Canvas canvas, Size size) {
    if (progress <= 0) return;

    final center = Offset(size.width / 2, size.height / 2);
    final maxR = size.shortestSide * 0.58;

    _drawRing(canvas, center, maxR, progress);

    // Trailing second ring (delayed by 30 % of progress)
    if (progress > 0.30) {
      _drawRing(canvas, center, maxR, (progress - 0.30) / 0.70, alpha: 0.65);
    }
  }

  void _drawRing(
    Canvas canvas,
    Offset center,
    double maxR,
    double p, {
    double alpha = 1.0,
  }) {
    final r = maxR * p;
    final fade = (1.0 - p) * alpha;
    if (fade <= 0) return;

    // Blurred glow ring
    canvas.drawCircle(
      center,
      r,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 5
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 10)
        ..color = const Color(0xFFFF6B35).withOpacity(fade * 0.55),
    );

    // Sharp crisp inner ring
    canvas.drawCircle(
      center,
      r,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.2
        ..color = const Color(0xFFFFAB6B).withOpacity(fade * 0.80),
    );
  }

  @override
  bool shouldRepaint(_PulseWavePainter old) => old.progress != progress;
}
