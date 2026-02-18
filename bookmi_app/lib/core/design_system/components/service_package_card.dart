import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

class ServicePackageCard extends StatelessWidget {
  const ServicePackageCard({
    required this.name,
    required this.cachetAmount,
    required this.type,
    super.key,
    this.description,
    this.durationMinutes,
    this.inclusions,
    this.isRecommended = false,
  });

  final String name;
  final String? description;
  final int cachetAmount;
  final int? durationMinutes;
  final List<String>? inclusions;
  final String type;
  final bool isRecommended;

  static String formatDuration(int? minutes) {
    if (minutes == null || minutes <= 0) return '';
    final hours = minutes ~/ 60;
    final mins = minutes % 60;
    if (hours == 0) return '${mins}min';
    if (mins == 0) return '${hours}h';
    return '${hours}h${mins.toString().padLeft(2, '0')}';
  }

  Color get _borderColor => switch (type) {
    'premium' => BookmiColors.ctaOrange,
    'standard' => BookmiColors.brandBlue,
    _ => BookmiColors.glassBorder,
  };

  String? get _badgeLabel => switch (type) {
    'premium' => 'Recommandé',
    'standard' => 'Populaire',
    _ => null,
  };

  Color get _badgeColor => switch (type) {
    'premium' => BookmiColors.ctaOrange,
    'standard' => BookmiColors.brandBlue,
    _ => BookmiColors.glassBorder,
  };

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        GlassCard(
          borderColor: isRecommended ? BookmiColors.ctaOrange : _borderColor,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Name
              Text(
                name,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: BookmiSpacing.spaceXs),
              // Price
              Text(
                TalentCard.formatCachet(cachetAmount),
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: BookmiColors.brandBlueLight,
                ),
              ),
              // Duration
              if (durationMinutes != null && durationMinutes! > 0) ...[
                const SizedBox(height: BookmiSpacing.spaceXs),
                Text(
                  formatDuration(durationMinutes),
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.white.withValues(alpha: 0.6),
                  ),
                ),
              ],
              // Description
              if (description != null && description!.isNotEmpty) ...[
                const SizedBox(height: BookmiSpacing.spaceSm),
                Text(
                  description!,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.white.withValues(alpha: 0.8),
                  ),
                ),
              ],
              // Inclusions
              if (inclusions != null && inclusions!.isNotEmpty) ...[
                const SizedBox(height: BookmiSpacing.spaceSm),
                ...inclusions!.map(
                  (item) => Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          '•  ',
                          style: TextStyle(
                            fontSize: 14,
                            color: BookmiColors.brandBlueLight,
                          ),
                        ),
                        Expanded(
                          child: Text(
                            item,
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.white.withValues(alpha: 0.8),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
        // Badge
        if (_badgeLabel != null || isRecommended)
          Positioned(
            top: -8,
            right: BookmiSpacing.spaceBase,
            child: Container(
              padding: const EdgeInsets.symmetric(
                horizontal: BookmiSpacing.spaceSm,
                vertical: 4,
              ),
              decoration: BoxDecoration(
                color: isRecommended ? BookmiColors.ctaOrange : _badgeColor,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                isRecommended ? 'Recommandé' : _badgeLabel!,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: Colors.white,
                ),
              ),
            ),
          ),
      ],
    );
  }
}
