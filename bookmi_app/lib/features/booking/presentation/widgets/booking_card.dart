import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class BookingCard extends StatelessWidget {
  const BookingCard({
    required this.booking,
    required this.onTap,
    this.onAccept,
    this.onReject,
    this.onPay,
    super.key,
  });

  final BookingModel booking;
  final VoidCallback onTap;
  final VoidCallback? onAccept;
  final VoidCallback? onReject;
  final VoidCallback? onPay;

  @override
  Widget build(BuildContext context) {
    final showActions =
        booking.status == 'pending' && (onAccept != null || onReject != null);
    final showPayButton =
        booking.status == 'accepted' && onPay != null;

    return GlassCard(
      // No outer onTap — we wrap only the info row below so that action
      // buttons (accept / reject / pay) receive touches independently.
      padding: const EdgeInsets.all(BookmiSpacing.spaceSm),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GestureDetector(
            onTap: onTap,
            behavior: HitTestBehavior.opaque,
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
                          booking.startTime != null
                              ? '${_formatDate(booking.eventDate)} · ${_formatTime(booking.startTime!)}'
                              : _formatDate(booking.eventDate),
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.white.withValues(alpha: 0.7),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    GestureDetector(
                      onTap: () => _openMaps(booking.eventLocation),
                      child: Row(
                        children: [
                          Icon(
                            Icons.location_on_outlined,
                            size: 12,
                            color: BookmiColors.brandBlueLight
                                .withValues(alpha: 0.8),
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              booking.eventLocation,
                              style: TextStyle(
                                fontSize: 12,
                                color: BookmiColors.brandBlueLight
                                    .withValues(alpha: 0.8),
                                decoration: TextDecoration.underline,
                                decorationColor: BookmiColors.brandBlueLight
                                    .withValues(alpha: 0.5),
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
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
                              color: BookmiColors.brandBlueLight,
                            ),
                            const SizedBox(width: 2),
                            const Text(
                              'Express',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w500,
                                color: BookmiColors.brandBlueLight,
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
          ), // GestureDetector (info row)
          if (showActions) ...[
            const SizedBox(height: BookmiSpacing.spaceSm),
            Row(
              children: [
                if (onReject != null)
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: onReject,
                      icon: const Icon(
                        Icons.close,
                        size: 14,
                        color: BookmiColors.error,
                      ),
                      label: const Text(
                        'Refuser',
                        style: TextStyle(
                          fontSize: 12,
                          color: BookmiColors.error,
                        ),
                      ),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 6),
                        side: BorderSide(
                          color: BookmiColors.error.withValues(alpha: 0.5),
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                    ),
                  ),
                if (onReject != null && onAccept != null)
                  const SizedBox(width: 8),
                if (onAccept != null)
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: onAccept,
                      icon: const Icon(Icons.check, size: 14),
                      label: const Text(
                        'Accepter',
                        style: TextStyle(fontSize: 12),
                      ),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 6),
                        backgroundColor: BookmiColors.success,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                        elevation: 0,
                      ),
                    ),
                  ),
              ],
            ),
          ],
          if (showPayButton) ...[
            const SizedBox(height: BookmiSpacing.spaceSm),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: onPay,
                icon: const Icon(Icons.payment_outlined, size: 16),
                label: const Text(
                  'Payer maintenant',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                ),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  backgroundColor: BookmiColors.brandBlueLight,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  elevation: 0,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  static String _formatDate(String isoDate) {
    try {
      final parts = isoDate.split('-');
      if (parts.length != 3) return isoDate;
      const months = [
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

  static String _formatTime(String time) {
    final parts = time.split(':');
    if (parts.length < 2) return time;
    return '${parts[0]}h${parts[1]}';
  }

  static Future<void> _openMaps(String location) async {
    final encoded = Uri.encodeComponent(location);
    final uri = Uri.parse(
      'https://www.google.com/maps/search/?api=1&query=$encoded',
    );
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
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
      'pending'             => ('En attente', BookmiColors.warning),
      'accepted'            => ('Validée', BookmiColors.brandBlueLight),
      'paid' || 'confirmed' => ('Confirmée', BookmiColors.success),
      'completed'           => ('Terminée', BookmiColors.brandBlueLight),
      'cancelled'           => ('Annulée', BookmiColors.error),
      'rejected'            => ('Rejetée', BookmiColors.error),
      'disputed'            => ('Litige', Colors.amber),
      _                     => (status, Colors.white54),
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
      'pending'             => (Icons.schedule, BookmiColors.warning),
      'accepted'            => (Icons.verified_outlined, BookmiColors.brandBlueLight),
      'paid' || 'confirmed' => (Icons.check_circle_outline, BookmiColors.success),
      'completed'           => (Icons.star_outline, BookmiColors.brandBlueLight),
      'cancelled'           => (Icons.cancel_outlined, BookmiColors.error),
      'rejected'            => (Icons.thumb_down_outlined, BookmiColors.error),
      'disputed'            => (Icons.report_problem_outlined, Colors.amber),
      _                     => (Icons.receipt_long_outlined, Colors.white54),
    };
  }
}
