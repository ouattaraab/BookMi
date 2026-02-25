import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:intl/intl.dart';

class BookingDetailPage extends StatefulWidget {
  const BookingDetailPage({
    required this.bookingId,
    this.preloaded,
    super.key,
  });

  final int bookingId;
  final BookingModel? preloaded;

  @override
  State<BookingDetailPage> createState() => _BookingDetailPageState();
}

class _BookingDetailPageState extends State<BookingDetailPage> {
  BookingModel? _booking;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    if (widget.preloaded != null) {
      _booking = widget.preloaded;
      _loading = false;
    } else {
      _fetch();
    }
  }

  Future<void> _fetch() async {
    // Capture repository reference before the async gap to avoid accessing
    // context after the widget may have been disposed.
    final repo = context.read<BookingRepository>();
    final result = await repo.getBooking(widget.bookingId);
    if (!mounted) return;
    setState(() {
      _loading = false;
      switch (result) {
        case ApiSuccess(:final data):
          _booking = data;
        case ApiFailure(:final message):
          _error = message;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          foregroundColor: Colors.white,
          title: const Text(
            'Réservation',
            style: TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        body: _loading
            ? const Center(
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: BookmiColors.brandBlue,
                ),
              )
            : _error != null
            ? _buildError()
            : _buildContent(),
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(BookmiSpacing.spaceXl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.error_outline,
              size: 48,
              color: Colors.white.withValues(alpha: 0.3),
            ),
            const SizedBox(height: BookmiSpacing.spaceBase),
            Text(
              _error!,
              style: const TextStyle(color: Colors.white70),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: BookmiSpacing.spaceLg),
            TextButton(
              onPressed: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _fetch();
              },
              child: const Text(
                'Réessayer',
                style: TextStyle(color: BookmiColors.brandBlueLight),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContent() {
    final booking = _booking;
    if (booking == null) return const SizedBox.shrink();
    return RefreshIndicator(
      color: BookmiColors.brandBlue,
      onRefresh: () async {
        setState(() {
          _loading = true;
          _error = null;
        });
        await _fetch();
      },
      child: ListView(
        padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
        children: [
          // Status header
          GlassCard(
            child: Row(
              children: [
                _StatusCircle(status: booking.status),
                const SizedBox(width: BookmiSpacing.spaceSm),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        booking.talentStageName,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                      Text(
                        booking.packageName,
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.white.withValues(alpha: 0.65),
                        ),
                      ),
                    ],
                  ),
                ),
                _StatusPill(status: booking.status),
              ],
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),

          // Event details
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const _SectionTitle('Détails de l\'événement'),
                const SizedBox(height: BookmiSpacing.spaceSm),
                _DetailRow(
                  icon: Icons.calendar_today_outlined,
                  label: 'Date',
                  value: booking.eventDate,
                ),
                const SizedBox(height: BookmiSpacing.spaceSm),
                _DetailRow(
                  icon: Icons.location_on_outlined,
                  label: 'Lieu',
                  value: booking.eventLocation,
                ),
                if (booking.isExpress) ...[
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  _DetailRow(
                    icon: Icons.bolt,
                    label: 'Type',
                    value: 'Express',
                    valueColor: BookmiColors.brandBlueLight,
                  ),
                ],
                if (booking.message != null &&
                    booking.message!.isNotEmpty) ...[
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  _DetailRow(
                    icon: Icons.chat_bubble_outline,
                    label: 'Message',
                    value: booking.message!,
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),

          // Devis
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const _SectionTitle('Devis'),
                const SizedBox(height: BookmiSpacing.spaceSm),
                _DevisRow(
                  label: 'Cachet',
                  value: TalentCard.formatCachet(booking.cachetAmount),
                ),
                _DevisRow(
                  label: 'Commission (15%)',
                  value: TalentCard.formatCachet(booking.commissionAmount),
                  small: true,
                ),
                const Divider(color: Colors.white12, height: 20),
                _DevisRow(
                  label: 'Total',
                  value: TalentCard.formatCachet(booking.totalAmount),
                  bold: true,
                  valueColor: BookmiColors.brandBlueLight,
                ),
              ],
            ),
          ),

          // Cancellation info
          if (booking.refundAmount != null) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            GlassCard(
              borderColor: BookmiColors.error.withValues(alpha: 0.4),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const _SectionTitle('Annulation'),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  _DevisRow(
                    label: 'Remboursement',
                    value: TalentCard.formatCachet(booking.refundAmount!),
                    valueColor: BookmiColors.success,
                  ),
                  if (booking.cancellationPolicyApplied != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        _policyLabel(booking.cancellationPolicyApplied!),
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.white.withValues(alpha: 0.5),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],

          // Contract download
          if (booking.contractAvailable) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _ContractButton(bookingId: booking.id),
          ],

          // Status history timeline
          if (booking.statusLogs != null && booking.statusLogs!.isNotEmpty) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _StatusHistoryCard(logs: booking.statusLogs!),
          ],

          const SizedBox(height: BookmiSpacing.spaceXl),
        ],
      ),
    );
  }

  static String _policyLabel(String policy) => switch (policy) {
    'full_refund' => 'Politique : remboursement intégral',
    'partial_refund' => 'Politique : remboursement partiel',
    'mediation' => 'Politique : médiation',
    _ => policy,
  };
}

// ── Private widgets ─────────────────────────────────────────────────────────

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        color: Colors.white,
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
    this.valueColor,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(
          icon,
          size: 16,
          color: Colors.white.withValues(alpha: 0.5),
        ),
        const SizedBox(width: BookmiSpacing.spaceSm),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.white.withValues(alpha: 0.5),
                ),
              ),
              Text(
                value,
                style: TextStyle(
                  fontSize: 13,
                  color: valueColor ?? Colors.white.withValues(alpha: 0.9),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _DevisRow extends StatelessWidget {
  const _DevisRow({
    required this.label,
    required this.value,
    this.valueColor,
    this.bold = false,
    this.small = false,
  });

  final String label;
  final String value;
  final Color? valueColor;
  final bool bold;
  final bool small;

  @override
  Widget build(BuildContext context) {
    final fontSize = small ? 12.0 : 14.0;
    final color =
        small ? Colors.white.withValues(alpha: 0.6) : Colors.white;

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(fontSize: fontSize, color: color)),
        Text(
          value,
          style: TextStyle(
            fontSize: bold ? 16 : fontSize,
            fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
            color: valueColor ?? color,
          ),
        ),
      ],
    );
  }
}

class _StatusCircle extends StatelessWidget {
  const _StatusCircle({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    final (icon, color) = _iconAndColor(status);
    return Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: color.withValues(alpha: 0.12),
      ),
      child: Icon(icon, size: 24, color: color),
    );
  }

  static (IconData, Color) _iconAndColor(String status) => switch (status) {
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

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    final (label, color) = _labelAndColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }

  static (String, Color) _labelAndColor(String status) => switch (status) {
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

// ── Status History Timeline ──────────────────────────────────────────────────

class _StatusHistoryCard extends StatelessWidget {
  const _StatusHistoryCard({required this.logs});
  final List<BookingStatusLog> logs;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionTitle('Historique'),
          const SizedBox(height: BookmiSpacing.spaceSm),
          ...logs.asMap().entries.map((entry) {
            final i = entry.key;
            final log = entry.value;
            final isLast = i == logs.length - 1;
            return _TimelineEntry(log: log, isLast: isLast);
          }),
        ],
      ),
    );
  }
}

class _TimelineEntry extends StatelessWidget {
  const _TimelineEntry({required this.log, required this.isLast});

  final BookingStatusLog log;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
    final (label, icon, color) = _entryMeta(log.fromStatus, log.toStatus);
    final dateStr = _formatDateTime(log.createdAt);
    final byStr = log.performedByName != null ? log.performedByName! : '';

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Timeline spine
          SizedBox(
            width: 28,
            child: Column(
              children: [
                Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: color.withValues(alpha: 0.15),
                    border: Border.all(color: color.withValues(alpha: 0.5)),
                  ),
                  child: Icon(icon, size: 13, color: color),
                ),
                if (!isLast)
                  Expanded(
                    child: Container(
                      width: 1.5,
                      color: Colors.white.withValues(alpha: 0.1),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          // Content
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 0 : 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      Icon(
                        Icons.access_time,
                        size: 11,
                        color: Colors.white.withValues(alpha: 0.4),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        dateStr,
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.white.withValues(alpha: 0.5),
                        ),
                      ),
                      if (byStr.isNotEmpty) ...[
                        Text(
                          '  ·  ',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.white.withValues(alpha: 0.3),
                          ),
                        ),
                        Icon(
                          Icons.person_outline,
                          size: 11,
                          color: Colors.white.withValues(alpha: 0.4),
                        ),
                        const SizedBox(width: 3),
                        Expanded(
                          child: Text(
                            byStr,
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.white.withValues(alpha: 0.5),
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  static (String, IconData, Color) _entryMeta(
    String? fromStatus,
    String toStatus,
  ) {
    if (fromStatus == null && toStatus == 'pending') {
      return ('Demande envoyée', Icons.send_outlined, BookmiColors.warning);
    }
    return switch (toStatus) {
      'accepted'  => ('Réservation validée', Icons.verified_outlined, BookmiColors.brandBlueLight),
      'paid'      => ('Paiement effectué', Icons.payment_outlined, BookmiColors.success),
      'confirmed' => ('Paiement confirmé', Icons.check_circle_outline, BookmiColors.success),
      'completed' => ('Prestation terminée', Icons.star_outline, BookmiColors.brandBlueLight),
      'cancelled' => ('Réservation annulée', Icons.cancel_outlined, BookmiColors.error),
      'rejected'  => ('Demande rejetée', Icons.thumb_down_outlined, BookmiColors.error),
      _           => (toStatus, Icons.info_outline, Colors.white54),
    };
  }

  static String _formatDateTime(DateTime dt) {
    final dateFormatter = DateFormat('d MMM yyyy', 'fr');
    final timeFormatter = DateFormat('HH:mm', 'fr');
    return '${dateFormatter.format(dt)} à ${timeFormatter.format(dt)}';
  }
}

class _ContractButton extends StatelessWidget {
  const _ContractButton({required this.bookingId});
  final int bookingId;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: () {
        // PDF download will be implemented in a future sprint (payment epic)
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Téléchargement du contrat bientôt disponible'),
          ),
        );
      },
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: BookmiColors.brandBlue.withValues(alpha: 0.15),
            ),
            child: const Icon(
              Icons.picture_as_pdf_outlined,
              color: BookmiColors.brandBlueLight,
              size: 20,
            ),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Contrat de prestation',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                Text(
                  'Télécharger en PDF',
                  style: TextStyle(
                    fontSize: 12,
                    color: BookmiColors.brandBlueLight,
                  ),
                ),
              ],
            ),
          ),
          const Icon(
            Icons.download_outlined,
            color: Colors.white38,
            size: 20,
          ),
        ],
      ),
    );
  }
}
