import 'dart:async';
import 'dart:ui' show FontFeature;

import 'package:flutter/material.dart';

class AppMaintenancePage extends StatefulWidget {
  const AppMaintenancePage({
    super.key,
    required this.message,
    this.endAt,
  });

  final String message;
  final DateTime? endAt;

  @override
  State<AppMaintenancePage> createState() => _AppMaintenancePageState();
}

class _AppMaintenancePageState extends State<AppMaintenancePage>
    with SingleTickerProviderStateMixin {
  Timer? _countdownTimer;
  Duration _remaining = Duration.zero;
  late final AnimationController _pulse;

  @override
  void initState() {
    super.initState();

    _pulse = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 3),
    )..repeat();

    if (widget.endAt != null) {
      _updateRemaining();
      _countdownTimer = Timer.periodic(
        const Duration(seconds: 1),
        (_) => _updateRemaining(),
      );
    }
  }

  void _updateRemaining() {
    final now = DateTime.now();
    final diff = widget.endAt!.difference(now);
    setState(() {
      _remaining = diff.isNegative ? Duration.zero : diff;
    });
  }

  String _pad(int n) => n.toString().padLeft(2, '0');

  String get _countdownText {
    final h = _remaining.inHours;
    final m = _remaining.inMinutes % 60;
    final s = _remaining.inSeconds % 60;
    return '${_pad(h)}:${_pad(m)}:${_pad(s)}';
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    _pulse.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      child: Scaffold(
        backgroundColor: const Color(0xFF0D1B35),
        body: Stack(
          children: [
            // Background gradient
            Positioned.fill(
              child: DecoratedBox(
                decoration: const BoxDecoration(
                  gradient: RadialGradient(
                    center: Alignment.center,
                    radius: 1.4,
                    colors: [Color(0xFF1E2D4E), Color(0xFF080E1E)],
                  ),
                ),
              ),
            ),

            // Pulse rings
            AnimatedBuilder(
              animation: _pulse,
              builder: (_, __) {
                return Positioned.fill(
                  child: CustomPaint(
                    painter: _PulseRingPainter(_pulse.value),
                  ),
                );
              },
            ),

            // Content
            SafeArea(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 24,
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Wrench icon
                      Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: const Color(0xFF64B5F6).withOpacity(0.12),
                          border: Border.all(
                            color: const Color(0xFF64B5F6).withOpacity(0.25),
                          ),
                        ),
                        child: const Icon(
                          Icons.build_outlined,
                          color: Color(0xFF64B5F6),
                          size: 36,
                        ),
                      ),
                      const SizedBox(height: 24),

                      const Text(
                        'Maintenance en cours',
                        style: TextStyle(
                          color: Color(0xFFF1F5F9),
                          fontSize: 22,
                          fontWeight: FontWeight.w700,
                          letterSpacing: -0.4,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 12),

                      Text(
                        widget.message,
                        style: const TextStyle(
                          color: Color(0xFF94A3B8),
                          fontSize: 15,
                          height: 1.5,
                        ),
                        textAlign: TextAlign.center,
                      ),

                      if (widget.endAt != null) ...[
                        const SizedBox(height: 32),
                        const Text(
                          'NOUS REVENONS DANS',
                          style: TextStyle(
                            color: Color(0xFF64748B),
                            fontSize: 11,
                            letterSpacing: 1.5,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          _countdownText,
                          style: const TextStyle(
                            color: Color(0xFF64B5F6),
                            fontSize: 40,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 2,
                            fontFeatures: [FontFeature.tabularFigures()],
                          ),
                        ),
                      ],

                      const SizedBox(height: 40),

                      // Divider
                      Container(
                        width: 60,
                        height: 1,
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [
                              Colors.transparent,
                              const Color(0xFF64B5F6).withOpacity(0.4),
                              Colors.transparent,
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      const Text(
                        'RÉSERVEZ LES MEILLEURS TALENTS',
                        style: TextStyle(
                          color: Color(0xFF475569),
                          fontSize: 9,
                          letterSpacing: 2,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PulseRingPainter extends CustomPainter {
  const _PulseRingPainter(this.value);

  final double value;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final maxR = size.shortestSide * 0.65;

    for (var i = 0; i < 3; i++) {
      final p = ((value + i / 3) % 1.0);
      final r = maxR * p;
      final opacity = (1.0 - p) * 0.12;
      if (opacity <= 0) continue;
      canvas.drawCircle(
        center,
        r,
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 1
          ..color = const Color(0xFF64B5F6).withOpacity(opacity),
      );
    }
  }

  @override
  bool shouldRepaint(_PulseRingPainter old) => old.value != value;
}

