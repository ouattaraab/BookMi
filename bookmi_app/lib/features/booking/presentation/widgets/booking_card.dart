import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:flutter/material.dart';

class BookingCard extends StatelessWidget {
  const BookingCard({
    required this.booking,
    required this.onTap,
    super.key,
  });

  final BookingModel booking;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: onTap,
      padding: const EdgeInsets.all(BookmiSpacing.spaceSm),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _StatusIcon(status: booking.status),
          const SizedBox(width: BookmiSpacing.spaceSm),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        booking.talentStageName,
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: BookmiSpacing.spaceXs),
                    _StatusBadge(status: booking.status),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  booking.packageName,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.white.withValues(alpha: 0.65),
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: BookmiSpacing.spaceXs),
                Row(
                  children: [
                    Icon(
                      Icons.calendar_today_outlined,
                      size: 12,
                      color: Colors.white.withValues(alpha: 0.5),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      _formatDate(booking.eventDate),
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.white.withValues(alpha: 0.7),
                      ),
                    ),
                    const SizedBox(width: BookmiSpacing.spaceSm),
                    Icon(
                      Icons.location_on_outlined,
                      size: 12,
                      color: Colors.white.withValues(alpha: 0.5),
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        booking.eventLocation,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.white.withValues(alpha: 0.7),
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: BookmiSpacing.spaceXs),
                Text(
                  TalentCard.formatCachet(booking.totalAmount),
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: BookmiColors.brandBlueLight,
                  ),
                ),
                if (booking.isExpress)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.bolt,
                          size: 12,
                          color: BookmiColors.ctaOrange,
                        ),
                        const SizedBox(width: 2),
                        Text(
                          'Express',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                            color: BookmiColors.ctaOrange,
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          const Icon(
            Icons.chevron_right,
            color: Colors.white38,
            size: 20,
          ),
        ],
      ),
    );
  }

  static String _formatDate(String isoDate) {
    try {
      final parts = isoDate.split('-');
      if (parts.length != 3) return isoDate;
      final months = [
        'jan', 'fév', 'mar', 'avr', 'mai', 'juin',
        'juil', 'aoû', 'sep', 'oct', 'nov', 'déc',
      ];
      final month = int.tryParse(parts[1]);
      if (month == null || month < 1 || month > 12) return isoDate;
      return '${parts[2]} ${months[month - 1]}. ${parts[0]}';
    } catch (_) {
      return isoDate;
    }
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    final (label, color) = _labelAndColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }

  static (String, Color) _labelAndColor(String status) {
    return switch (status) {
      'pending' => ('En attente', BookmiColors.warning),
      'accepted' || 'paid' => ('Confirmée', BookmiColors.success),
      'confirmed' => ('Confirmée', BookmiColors.success),
      'completed' => ('Passée', BookmiColors.brandBlueLight),
      'cancelled' => ('Annulée', BookmiColors.error),
      'disputed' => ('Litige', BookmiColors.ctaOrange),
      _ => (status, Colors.white54),
    };
  }
}

class _StatusIcon extends StatelessWidget {
  const _StatusIcon({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    final (icon, color) = _iconAndColor(status);
    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: color.withValues(alpha: 0.1),
      ),
      child: Icon(icon, size: 20, color: color),
    );
  }

  static (IconData, Color) _iconAndColor(String status) {
    return switch (status) {
      'pending' => (Icons.schedule, BookmiColors.warning),
      'accepted' || 'paid' || 'confirmed' => (
        Icons.check_circle_outline,
        BookmiColors.success,
      ),
      'completed' => (Icons.star_outline, BookmiColors.brandBlueLight),
      'cancelled' => (Icons.cancel_outlined, BookmiColors.error),
      'disputed' => (Icons.report_problem_outlined, BookmiColors.ctaOrange),
      _ => (Icons.receipt_long_outlined, Colors.white54),
    };
  }
}
