import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

/// A glassmorphism security indicator widget.
///
/// Wraps payment-related UI to signal a secure zone to the user.
/// Uses [BookmiColors.gradientShield] (green→blue) as the border gradient.
class GlassShield extends StatelessWidget {
  const GlassShield({
    required this.child,
    this.label = 'Paiement sécurisé',
    super.key,
  });

  final Widget child;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: BookmiColors.glassDarkMedium,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: BookmiColors.success.withValues(alpha: 0.35),
          width: 1.5,
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Shield header
          Container(
            padding: const EdgeInsets.symmetric(
              horizontal: BookmiSpacing.spaceBase,
              vertical: BookmiSpacing.spaceSm,
            ),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  BookmiColors.success.withValues(alpha: 0.12),
                  BookmiColors.brandBlue.withValues(alpha: 0.12),
                ],
              ),
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(14),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.shield_rounded,
                  size: 16,
                  color: BookmiColors.success.withValues(alpha: 0.9),
                ),
                const SizedBox(width: BookmiSpacing.spaceXs),
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: BookmiColors.success.withValues(alpha: 0.9),
                  ),
                ),
              ],
            ),
          ),
          // Content
          Padding(
            padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
            child: child,
          ),
        ],
      ),
    );
  }
}
