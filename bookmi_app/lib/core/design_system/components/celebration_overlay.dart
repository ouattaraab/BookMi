import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

/// Full-screen success overlay shown after a successful payment.
///
/// Auto-dismisses after [duration] (default: 2.5s).
/// Displays animated checkmark + title + subtitle with fade + scale-in.
class CelebrationOverlay extends StatefulWidget {
  const CelebrationOverlay({
    required this.title,
    required this.subtitle,
    this.duration = const Duration(milliseconds: 2500),
    this.onDismiss,
    super.key,
  });

  final String title;
  final String subtitle;
  final Duration duration;
  final VoidCallback? onDismiss;

  /// Displays the overlay as an [OverlayEntry] inside the given context.
  static OverlayEntry show(
    BuildContext context, {
    required String title,
    required String subtitle,
    Duration duration = const Duration(milliseconds: 2500),
    VoidCallback? onDismiss,
  }) {
    late OverlayEntry entry;
    entry = OverlayEntry(
      builder: (_) => CelebrationOverlay(
        title: title,
        subtitle: subtitle,
        duration: duration,
        onDismiss: () {
          entry.remove();
          onDismiss?.call();
        },
      ),
    );
    Overlay.of(context).insert(entry);
    return entry;
  }

  @override
  State<CelebrationOverlay> createState() => _CelebrationOverlayState();
}

class _CelebrationOverlayState extends State<CelebrationOverlay>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _fadeAnim;
  late final Animation<double> _scaleAnim;

  @override
  void initState() {
    super.initState();

    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 400),
    );

    _fadeAnim = CurvedAnimation(parent: _controller, curve: Curves.easeOut);
    _scaleAnim = Tween<double>(begin: 0.7, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.elasticOut),
    );

    _controller.forward();

    Future.delayed(widget.duration, () {
      if (mounted) widget.onDismiss?.call();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _fadeAnim,
      child: Container(
        color: BookmiColors.brandNavy.withValues(alpha: 0.92),
        child: Center(
          child: ScaleTransition(
            scale: _scaleAnim,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Animated checkmark circle
                Container(
                  width: 100,
                  height: 100,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [BookmiColors.success, Color(0xFF00A040)],
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: BookmiColors.success.withValues(alpha: 0.4),
                        blurRadius: 32,
                        spreadRadius: 4,
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.check_rounded,
                    color: Colors.white,
                    size: 52,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceLg),
                Text(
                  widget.title,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: BookmiSpacing.spaceSm),
                Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: BookmiSpacing.spaceXl,
                  ),
                  child: Text(
                    widget.subtitle,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withValues(alpha: 0.7),
                      height: 1.4,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
