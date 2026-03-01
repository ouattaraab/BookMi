import 'dart:io';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/services/analytics_service.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_cubit.dart';
import 'package:bookmi_app/features/messaging/data/models/conversation_model.dart';
import 'package:bookmi_app/features/messaging/data/repositories/messaging_repository.dart';
import 'package:bookmi_app/features/messaging/presentation/pages/chat_page.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

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
      // Show the preloaded booking immediately while the full detail
      // (including statusLogs history) is fetched in the background.
      _booking = widget.preloaded;
      _loading = false;
      _fetch(); // background fetch for history
    } else {
      _fetch();
    }
  }

  /// Returns true when the event ended more than 24 hours ago.
  static bool _isEventPast24h(String eventDate) {
    final deadline = DateTime.parse(eventDate).add(const Duration(hours: 24));
    return DateTime.now().isAfter(deadline);
  }

  /// Returns true when the event date has passed (event is over).
  static bool _eventDatePassed(String eventDate) {
    try {
      final date = DateTime.parse(eventDate);
      return DateTime.now().isAfter(date);
    } catch (_) {
      return false;
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
    final authState = context.read<AuthBloc>().state;
    final isTalent =
        authState is AuthAuthenticated && authState.roles.contains('talent');
    final currentUserId = authState is AuthAuthenticated
        ? authState.user.id
        : 0;
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
                if (booking.message != null && booking.message!.isNotEmpty) ...[
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
                if (booking.travelCost > 0)
                  _DevisRow(
                    label: 'Frais de déplacement',
                    value: TalentCard.formatCachet(booking.travelCost),
                    small: true,
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

          // Message talent (available from payment onwards)
          if (const [
                'paid',
                'confirmed',
                'completed',
              ].contains(booking.status) &&
              booking.talentProfileId != null) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _MessageButton(
              bookingId: booking.id,
              talentProfileId: booking.talentProfileId!,
              talentName: booking.talentStageName,
              talentAvatarUrl: booking.talentAvatarUrl,
              bookingStatus: booking.status,
              eventDate: booking.eventDate,
            ),
          ],

          // Contract download (only after payment — controlled by backend flag)
          if (booking.contractAvailable) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _ContractButton(bookingId: booking.id),
          ],

          // Receipt download (available once paid)
          if (['paid', 'confirmed', 'completed'].contains(booking.status)) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _ReceiptButton(bookingId: booking.id),
          ],

          // ── Action buttons ──────────────────────────────────────────────

          // Reschedule (proposed or accepted)
          if (['accepted', 'paid', 'confirmed'].contains(booking.status)) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            if (booking.pendingReschedule != null)
              _PendingRescheduleCard(
                reschedule: booking.pendingReschedule!,
                currentUserId: currentUserId,
                bookingId: booking.id,
                onResponded: () {
                  setState(() {
                    _loading = true;
                    _error = null;
                  });
                  _fetch();
                },
              )
            else
              _ProposeRescheduleButton(
                bookingId: booking.id,
                onProposed: () {
                  setState(() {
                    _loading = true;
                    _error = null;
                  });
                  _fetch();
                },
              ),
          ],

          // Talent: track the service (during event — before 24h deadline)
          if (isTalent &&
              booking.status == 'paid' &&
              !_isEventPast24h(booking.eventDate)) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _TrackingButton(bookingId: booking.id),
          ],

          // Talent: fallback confirm delivery (≥ 24h after event, client hasn't confirmed)
          if (isTalent &&
              booking.status == 'paid' &&
              _isEventPast24h(booking.eventDate)) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _TalentConfirmDeliveryButton(
              bookingId: booking.id,
              onConfirmed: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _fetch();
              },
            ),
          ],

          // Client: confirm end of service (only when status = paid)
          if (!isTalent && booking.status == 'paid') ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _ConfirmDeliveryButton(
              bookingId: booking.id,
              onConfirmed: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _fetch();
              },
            ),
          ],

          // Client: validate end of service (only when status = confirmed + event date passed)
          if (!isTalent &&
              booking.status == 'confirmed' &&
              _eventDatePassed(booking.eventDate)) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _CompleteBookingButton(
              bookingId: booking.id,
              onCompleted: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _fetch();
              },
            ),
          ],

          // Client: cancel booking (paid/confirmed — only if event is ≥ 2 days away)
          if (!isTalent &&
              ['paid', 'confirmed'].contains(booking.status) &&
              _CancelBookingButton.daysUntilEvent(booking.eventDate) >= 2) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _CancelBookingButton(
              bookingId: booking.id,
              eventDate: booking.eventDate,
              cachetAmount: booking.cachetAmount,
              onCancelled: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _fetch();
              },
            ),
          ],

          // Client: open dispute (paid or confirmed)
          if (!isTalent && ['paid', 'confirmed'].contains(booking.status)) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _DisputeButton(
              bookingId: booking.id,
              onDisputeOpened: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _fetch();
              },
            ),
          ],

          // Client: leave a review (confirmed or completed, not yet reviewed)
          if (!isTalent &&
              ['confirmed', 'completed'].contains(booking.status) &&
              !booking.hasClientReview) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _EvaluationButton(
              bookingId: booking.id,
              reviewType: 'client_to_talent',
              label: 'Laisser un avis',
              icon: Icons.star_rounded,
              onReturn: _fetch,
            ),
          ],

          // Client: report a completed booking (post-event)
          if (!isTalent && booking.status == 'completed') ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _ReportButton(bookingId: booking.id),
          ],

          // Talent: see client reviews and reply (confirmed or completed)
          if (isTalent &&
              booking.hasClientReview &&
              ['confirmed', 'completed'].contains(booking.status)) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _ClientReviewsButton(
              bookingId: booking.id,
              clientName: booking.clientName,
              talentStageName: booking.talentStageName,
              onReturn: _fetch,
            ),
          ],

          // Talent: evaluate the client (confirmed or completed, not yet reviewed)
          if (isTalent &&
              ['confirmed', 'completed'].contains(booking.status) &&
              !booking.hasTalentReview) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            _EvaluationButton(
              bookingId: booking.id,
              reviewType: 'talent_to_client',
              label: 'Évaluer le client',
              icon: Icons.star_border_rounded,
              onReturn: _fetch,
            ),
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
    final color = small ? Colors.white.withValues(alpha: 0.6) : Colors.white;

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(fontSize: fontSize, color: color),
        ),
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
    'pending' => (Icons.schedule, BookmiColors.warning),
    'accepted' => (Icons.verified_outlined, BookmiColors.brandBlueLight),
    'paid' || 'confirmed' => (Icons.check_circle_outline, BookmiColors.success),
    'completed' => (Icons.star_outline, BookmiColors.brandBlueLight),
    'cancelled' => (Icons.cancel_outlined, BookmiColors.error),
    'rejected' => (Icons.thumb_down_outlined, BookmiColors.error),
    'disputed' => (Icons.report_problem_outlined, Colors.amber),
    _ => (Icons.receipt_long_outlined, Colors.white54),
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
    'pending' => ('En attente', BookmiColors.warning),
    'accepted' => ('Validée', BookmiColors.brandBlueLight),
    'paid' || 'confirmed' => ('Confirmée', BookmiColors.success),
    'completed' => ('Terminée', BookmiColors.brandBlueLight),
    'cancelled' => ('Annulée', BookmiColors.error),
    'rejected' => ('Rejetée', BookmiColors.error),
    'disputed' => ('Litige', Colors.amber),
    _ => (status, Colors.white54),
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
      'accepted' => (
        'Réservation validée',
        Icons.verified_outlined,
        BookmiColors.brandBlueLight,
      ),
      'paid' => (
        'Paiement effectué',
        Icons.payment_outlined,
        BookmiColors.success,
      ),
      'confirmed' => (
        'Paiement confirmé',
        Icons.check_circle_outline,
        BookmiColors.success,
      ),
      'completed' => (
        'Prestation terminée',
        Icons.star_outline,
        BookmiColors.brandBlueLight,
      ),
      'cancelled' => (
        'Réservation annulée',
        Icons.cancel_outlined,
        BookmiColors.error,
      ),
      'rejected' => (
        'Demande rejetée',
        Icons.thumb_down_outlined,
        BookmiColors.error,
      ),
      _ => (toStatus, Icons.info_outline, Colors.white54),
    };
  }

  static String _formatDateTime(DateTime dt) {
    final dateFormatter = DateFormat('d MMM yyyy', 'fr');
    final timeFormatter = DateFormat('HH:mm', 'fr');
    return '${dateFormatter.format(dt)} à ${timeFormatter.format(dt)}';
  }
}

class _ContractButton extends StatefulWidget {
  const _ContractButton({required this.bookingId});
  final int bookingId;

  @override
  State<_ContractButton> createState() => _ContractButtonState();
}

class _ContractButtonState extends State<_ContractButton> {
  bool _loading = false;

  Future<void> _download() async {
    if (_loading) return;
    AnalyticsService.instance.trackTap('btn_download_contract');
    setState(() => _loading = true);

    final repo = context.read<BookingRepository>();
    final result = await repo.getContractUrl(widget.bookingId);

    if (!mounted) {
      setState(() => _loading = false);
      return;
    }

    switch (result) {
      case ApiSuccess(:final data):
        try {
          // Download bytes directly — the token URL is never shown to the user.
          final response = await Dio().get<List<int>>(
            data,
            options: Options(responseType: ResponseType.bytes),
          );
          final dir = await getTemporaryDirectory();
          final file = File('${dir.path}/contrat-bookmi.pdf');
          await file.writeAsBytes(response.data!);
          await OpenFilex.open(file.path);
        } on Exception catch (_) {
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Erreur lors du téléchargement')),
          );
        }
      case ApiFailure(:final message):
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message)),
        );
    }

    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: _download,
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: BookmiColors.brandBlue.withValues(alpha: 0.15),
            ),
            child: _loading
                ? const Padding(
                    padding: EdgeInsets.all(10),
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: BookmiColors.brandBlueLight,
                    ),
                  )
                : const Icon(
                    Icons.description_outlined,
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

// ── Message talent button ────────────────────────────────────────────────────

class _MessageButton extends StatefulWidget {
  const _MessageButton({
    required this.bookingId,
    required this.talentProfileId,
    required this.talentName,
    this.talentAvatarUrl,
    this.bookingStatus,
    this.eventDate,
  });

  final int bookingId;
  final int talentProfileId;
  final String talentName;
  final String? talentAvatarUrl;
  final String? bookingStatus;
  final String? eventDate;

  @override
  State<_MessageButton> createState() => _MessageButtonState();
}

class _MessageButtonState extends State<_MessageButton> {
  bool _loading = false;

  Future<void> _openChat() async {
    if (_loading) return;
    AnalyticsService.instance.trackTap('btn_open_chat');
    setState(() => _loading = true);

    final repo = context.read<MessagingRepository>();
    final result = await repo.startConversation(
      talentProfileId: widget.talentProfileId,
      bookingRequestId: widget.bookingId,
    );

    if (!mounted) return;
    setState(() => _loading = false);

    switch (result) {
      case ApiSuccess(:final data):
        final conversationData = data['data'] as Map<String, dynamic>?;
        final conversationId = conversationData?['id'] as int?;
        if (conversationId == null) return;
        if (!mounted) return;
        await Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => BlocProvider<MessagingCubit>(
              create: (_) => MessagingCubit(repository: repo),
              child: ChatPage(
                conversationId: conversationId,
                otherPartyName: widget.talentName,
                talentAvatarUrl: widget.talentAvatarUrl,
                booking: widget.bookingStatus != null
                    ? BookingInfo(
                        id: widget.bookingId,
                        status: widget.bookingStatus!,
                        isClosed: widget.bookingStatus == 'completed',
                        eventDate: widget.eventDate,
                      )
                    : null,
              ),
            ),
          ),
        );
      case ApiFailure(:final message):
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message)),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: _openChat,
      borderColor: BookmiColors.brandBlueLight.withValues(alpha: 0.4),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: BookmiColors.brandBlue.withValues(alpha: 0.15),
            ),
            child: _loading
                ? const Padding(
                    padding: EdgeInsets.all(10),
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: BookmiColors.brandBlueLight,
                    ),
                  )
                : const Icon(
                    Icons.chat_rounded,
                    color: BookmiColors.brandBlueLight,
                    size: 20,
                  ),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Envoyer un message à ${widget.talentName}',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const Text(
                  'Discutez des détails de votre événement',
                  style: TextStyle(
                    fontSize: 12,
                    color: BookmiColors.brandBlueLight,
                  ),
                ),
              ],
            ),
          ),
          const Icon(
            Icons.arrow_forward_ios,
            color: Colors.white38,
            size: 14,
          ),
        ],
      ),
    );
  }
}

// ── Complete booking button (client only, status=confirmed + event past) ────────

class _CompleteBookingButton extends StatefulWidget {
  const _CompleteBookingButton({
    required this.bookingId,
    required this.onCompleted,
  });

  final int bookingId;
  final VoidCallback onCompleted;

  @override
  State<_CompleteBookingButton> createState() => _CompleteBookingButtonState();
}

class _CompleteBookingButtonState extends State<_CompleteBookingButton> {
  bool _loading = false;

  Future<void> _complete() async {
    if (_loading) return;
    setState(() => _loading = true);

    final repo = context.read<BookingRepository>();
    final result = await repo.completeBooking(widget.bookingId);

    if (!mounted) return;
    setState(() => _loading = false);

    switch (result) {
      case ApiSuccess():
        widget.onCompleted();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message)),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: _complete,
      borderColor: BookmiColors.brandBlueLight.withValues(alpha: 0.4),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: BookmiColors.brandBlue.withValues(alpha: 0.15),
            ),
            child: _loading
                ? const Padding(
                    padding: EdgeInsets.all(10),
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: BookmiColors.brandBlueLight,
                    ),
                  )
                : const Icon(
                    Icons.check_circle_outline,
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
                  'Valider la fin de prestation',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                Text(
                  'Marquez la prestation comme terminée',
                  style: TextStyle(
                    fontSize: 12,
                    color: BookmiColors.brandBlueLight,
                  ),
                ),
              ],
            ),
          ),
          const Icon(
            Icons.arrow_forward_ios,
            color: Colors.white38,
            size: 14,
          ),
        ],
      ),
    );
  }
}

// ── Tracking button (talent only, status=paid) ───────────────────────────────

class _TrackingButton extends StatelessWidget {
  const _TrackingButton({required this.bookingId});
  final int bookingId;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: () => context.pushNamed(
        RouteNames.tracking,
        pathParameters: {'id': bookingId.toString()},
      ),
      borderColor: const Color(0xFFFF6B35).withValues(alpha: 0.4),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: const Color(0xFFFF6B35).withValues(alpha: 0.15),
            ),
            child: const Icon(
              Icons.location_on_rounded,
              color: Color(0xFFFF6B35),
              size: 20,
            ),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Suivre la prestation',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                Text(
                  'Mettez à jour le suivi jour-J',
                  style: TextStyle(
                    fontSize: 12,
                    color: Color(0xFFFF6B35),
                  ),
                ),
              ],
            ),
          ),
          const Icon(
            Icons.arrow_forward_ios,
            color: Colors.white38,
            size: 14,
          ),
        ],
      ),
    );
  }
}

// ── Confirm delivery button (client only, status=paid) ───────────────────────

class _ConfirmDeliveryButton extends StatefulWidget {
  const _ConfirmDeliveryButton({
    required this.bookingId,
    required this.onConfirmed,
  });

  final int bookingId;
  final VoidCallback onConfirmed;

  @override
  State<_ConfirmDeliveryButton> createState() => _ConfirmDeliveryButtonState();
}

class _ConfirmDeliveryButtonState extends State<_ConfirmDeliveryButton> {
  bool _loading = false;

  Future<void> _confirm() async {
    if (_loading) return;
    setState(() => _loading = true);

    final repo = context.read<BookingRepository>();
    final result = await repo.confirmDelivery(widget.bookingId);

    if (!mounted) return;
    setState(() => _loading = false);

    switch (result) {
      case ApiSuccess():
        widget.onConfirmed();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message)),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: _confirm,
      borderColor: BookmiColors.success.withValues(alpha: 0.4),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: BookmiColors.success.withValues(alpha: 0.15),
            ),
            child: _loading
                ? const Padding(
                    padding: EdgeInsets.all(10),
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: BookmiColors.success,
                    ),
                  )
                : const Icon(
                    Icons.check_circle_rounded,
                    color: BookmiColors.success,
                    size: 20,
                  ),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Confirmer la fin de prestation',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                Text(
                  'Le paiement sera libéré au talent',
                  style: TextStyle(
                    fontSize: 12,
                    color: BookmiColors.success,
                  ),
                ),
              ],
            ),
          ),
          const Icon(
            Icons.arrow_forward_ios,
            color: Colors.white38,
            size: 14,
          ),
        ],
      ),
    );
  }
}

// ── Talent fallback confirm delivery button ───────────────────────────────────

class _TalentConfirmDeliveryButton extends StatefulWidget {
  const _TalentConfirmDeliveryButton({
    required this.bookingId,
    required this.onConfirmed,
  });

  final int bookingId;
  final VoidCallback onConfirmed;

  @override
  State<_TalentConfirmDeliveryButton> createState() =>
      _TalentConfirmDeliveryButtonState();
}

class _TalentConfirmDeliveryButtonState
    extends State<_TalentConfirmDeliveryButton> {
  bool _loading = false;

  Future<void> _confirm() async {
    if (_loading) return;
    setState(() => _loading = true);

    final repo = context.read<BookingRepository>();
    final result = await repo.talentConfirmDelivery(widget.bookingId);

    if (!mounted) return;
    setState(() => _loading = false);

    switch (result) {
      case ApiSuccess():
        widget.onConfirmed();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message)),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: _confirm,
      borderColor: const Color(0xFFFF6B35).withValues(alpha: 0.4),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: const Color(0xFFFF6B35).withValues(alpha: 0.15),
            ),
            child: _loading
                ? const Padding(
                    padding: EdgeInsets.all(10),
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Color(0xFFFF6B35),
                    ),
                  )
                : const Icon(
                    Icons.flag_rounded,
                    color: Color(0xFFFF6B35),
                    size: 20,
                  ),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Marquer l\'événement comme terminé',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                Text(
                  'Le client n\'a pas confirmé — libérer le paiement',
                  style: TextStyle(
                    fontSize: 12,
                    color: Color(0xFFFF6B35),
                  ),
                ),
              ],
            ),
          ),
          const Icon(
            Icons.arrow_forward_ios,
            color: Colors.white38,
            size: 14,
          ),
        ],
      ),
    );
  }
}

// ── Client reviews button (talent: see reviews + reply) ──────────────────────

class _ClientReviewsButton extends StatelessWidget {
  const _ClientReviewsButton({
    required this.bookingId,
    required this.clientName,
    required this.talentStageName,
    this.onReturn,
  });

  final int bookingId;
  final String clientName;
  final String talentStageName;
  final VoidCallback? onReturn;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: () {
        context
            .pushNamed(
              RouteNames.clientReviews,
              pathParameters: {'id': bookingId.toString()},
              queryParameters: {
                'clientName': clientName,
                'talentStageName': talentStageName,
              },
            )
            .then((_) => onReturn?.call());
      },
      borderColor: const Color(0xFFFF6B35).withValues(alpha: 0.35),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: const Color(0xFFFF6B35).withValues(alpha: 0.12),
            ),
            child: const Icon(
              Icons.rate_review_rounded,
              color: Color(0xFFFF6B35),
              size: 20,
            ),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Voir et répondre aux avis',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                Text(
                  'Consultez les avis clients et répondez-y',
                  style: TextStyle(fontSize: 12, color: Colors.white54),
                ),
              ],
            ),
          ),
          const Icon(
            Icons.arrow_forward_ios,
            color: Colors.white38,
            size: 14,
          ),
        ],
      ),
    );
  }
}

// ── Evaluation button (review / évaluer client) ───────────────────────────────

class _EvaluationButton extends StatelessWidget {
  const _EvaluationButton({
    required this.bookingId,
    required this.reviewType,
    required this.label,
    required this.icon,
    this.onReturn,
  });

  final int bookingId;
  final String reviewType;
  final String label;
  final IconData icon;
  final VoidCallback? onReturn;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: () {
        context
            .pushNamed(
              RouteNames.evaluation,
              pathParameters: {'id': bookingId.toString()},
              queryParameters: {'type': reviewType},
            )
            .then((_) => onReturn?.call());
      },
      borderColor: const Color(0xFFFFD700).withValues(alpha: 0.35),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: const Color(0xFFFFD700).withValues(alpha: 0.12),
            ),
            child: Icon(icon, color: const Color(0xFFFFD700), size: 20),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Colors.white,
              ),
            ),
          ),
          const Icon(
            Icons.arrow_forward_ios,
            color: Colors.white38,
            size: 14,
          ),
        ],
      ),
    );
  }
}

class _ReceiptButton extends StatefulWidget {
  const _ReceiptButton({required this.bookingId});
  final int bookingId;

  @override
  State<_ReceiptButton> createState() => _ReceiptButtonState();
}

class _ReceiptButtonState extends State<_ReceiptButton> {
  bool _loading = false;

  Future<void> _download() async {
    if (_loading) return;
    setState(() => _loading = true);

    final repo = context.read<BookingRepository>();
    final result = await repo.getReceiptUrl(widget.bookingId);

    if (!mounted) {
      setState(() => _loading = false);
      return;
    }

    switch (result) {
      case ApiSuccess(:final data):
        try {
          // Download bytes directly — the token URL is never shown to the user.
          final response = await Dio().get<List<int>>(
            data,
            options: Options(responseType: ResponseType.bytes),
          );
          final dir = await getTemporaryDirectory();
          final file = File('${dir.path}/recu-bookmi.pdf');
          await file.writeAsBytes(response.data!);
          await OpenFilex.open(file.path);
        } on Exception catch (_) {
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Erreur lors du téléchargement')),
          );
        }
      case ApiFailure(:final message):
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message)),
        );
    }

    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      onTap: _download,
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: const Color(0xFF4CAF50).withValues(alpha: 0.15),
            ),
            child: _loading
                ? const Padding(
                    padding: EdgeInsets.all(10),
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Color(0xFF4CAF50),
                    ),
                  )
                : const Icon(
                    Icons.receipt_long_outlined,
                    color: Color(0xFF4CAF50),
                    size: 20,
                  ),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Reçu de paiement',
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
                    color: Color(0xFF4CAF50),
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

// ── Dispute Button ──────────────────────────────────────────────────────────

class _DisputeButton extends StatefulWidget {
  const _DisputeButton({
    required this.bookingId,
    required this.onDisputeOpened,
  });

  final int bookingId;
  final VoidCallback onDisputeOpened;

  @override
  State<_DisputeButton> createState() => _DisputeButtonState();
}

class _DisputeButtonState extends State<_DisputeButton> {
  bool _loading = false;

  Future<void> _openDispute() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1A2233),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(
            color: Colors.amber.withValues(alpha: 0.3),
          ),
        ),
        title: Row(
          children: [
            Icon(
              Icons.report_problem_outlined,
              color: Colors.amber,
              size: 22,
            ),
            const SizedBox(width: 8),
            const Text(
              'Ouvrir un litige',
              style: TextStyle(
                color: Colors.white,
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        content: const Text(
          'En ouvrant un litige, notre équipe sera notifiée et examinera votre dossier. '
          'Cette action ne peut pas être annulée.',
          style: TextStyle(color: Colors.white70, fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text(
              'Annuler',
              style: TextStyle(color: Colors.white54),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: TextButton.styleFrom(foregroundColor: Colors.amber),
            child: const Text(
              'Confirmer le litige',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _loading = true);

    final repo = context.read<BookingRepository>();
    final result = await repo.openDispute(widget.bookingId);

    if (!mounted) return;
    setState(() => _loading = false);

    switch (result) {
      case ApiSuccess():
        widget.onDisputeOpened();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: Colors.red.shade700,
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      child: InkWell(
        onTap: _loading ? null : _openDispute,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 14),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (_loading)
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.amber,
                  ),
                )
              else
                const Icon(
                  Icons.report_problem_outlined,
                  size: 18,
                  color: Colors.amber,
                ),
              const SizedBox(width: 8),
              Text(
                'Ouvrir un litige',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: Colors.amber.shade300,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Reschedule widgets ───────────────────────────────────────────────────────

class _PendingRescheduleCard extends StatefulWidget {
  const _PendingRescheduleCard({
    required this.reschedule,
    required this.currentUserId,
    required this.bookingId,
    required this.onResponded,
  });

  final RescheduleInfo reschedule;
  final int currentUserId;
  final int bookingId;
  final VoidCallback onResponded;

  @override
  State<_PendingRescheduleCard> createState() => _PendingRescheduleCardState();
}

class _PendingRescheduleCardState extends State<_PendingRescheduleCard> {
  bool _loading = false;

  Future<void> _respond(bool accept) async {
    setState(() => _loading = true);
    final repo = context.read<BookingRepository>();
    final result = accept
        ? await repo.acceptReschedule(widget.reschedule.id)
        : await repo.rejectReschedule(widget.reschedule.id);
    if (!mounted) return;
    setState(() => _loading = false);
    switch (result) {
      case ApiSuccess():
        widget.onResponded();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: Colors.red.shade700,
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isRequester = widget.reschedule.requestedById == widget.currentUserId;

    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: Colors.orange.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(
                  Icons.event_repeat_outlined,
                  size: 16,
                  color: Colors.orange,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Report proposé',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      'Nouvelle date : ${widget.reschedule.proposedDate}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.white.withValues(alpha: 0.65),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (widget.reschedule.message != null &&
              widget.reschedule.message!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              widget.reschedule.message!,
              style: TextStyle(
                fontSize: 12,
                color: Colors.white.withValues(alpha: 0.6),
                fontStyle: FontStyle.italic,
              ),
            ),
          ],
          const SizedBox(height: 12),
          if (isRequester)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.orange.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                'En attente de réponse',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.orange.shade300,
                ),
              ),
            )
          else if (_loading)
            const Center(
              child: SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white60,
                ),
              ),
            )
          else
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.white60,
                      side: const BorderSide(color: Colors.white24),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 10),
                    ),
                    onPressed: () => _respond(false),
                    child: const Text(
                      'Refuser',
                      style: TextStyle(fontSize: 13),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: BookmiColors.success,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 10),
                    ),
                    onPressed: () => _respond(true),
                    child: const Text(
                      'Accepter',
                      style: TextStyle(fontSize: 13),
                    ),
                  ),
                ),
              ],
            ),
        ],
      ),
    );
  }
}

class _ProposeRescheduleButton extends StatefulWidget {
  const _ProposeRescheduleButton({
    required this.bookingId,
    required this.onProposed,
  });

  final int bookingId;
  final VoidCallback onProposed;

  @override
  State<_ProposeRescheduleButton> createState() =>
      _ProposeRescheduleButtonState();
}

class _ProposeRescheduleButtonState extends State<_ProposeRescheduleButton> {
  bool _loading = false;

  Future<void> _propose() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now().add(const Duration(days: 1)),
      firstDate: DateTime.now().add(const Duration(days: 1)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      locale: const Locale('fr'),
      builder: (ctx, child) => Theme(
        data: ThemeData.dark().copyWith(
          colorScheme: const ColorScheme.dark(
            primary: BookmiColors.brandBlueLight,
            surface: Color(0xFF1A2233),
          ),
        ),
        child: child!,
      ),
    );
    if (picked == null || !mounted) return;

    final dateStr =
        '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';

    // Optional message
    String? message;
    final msgController = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1A2233),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text(
          'Proposer un report',
          style: TextStyle(color: Colors.white, fontSize: 16),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Nouvelle date : $dateStr',
              style: const TextStyle(color: Colors.white70, fontSize: 13),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: msgController,
              maxLines: 2,
              decoration: InputDecoration(
                labelText: 'Message (optionnel)',
                labelStyle: const TextStyle(color: Colors.white54),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Colors.white24),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(
                    color: BookmiColors.brandBlueLight,
                  ),
                ),
                filled: true,
                fillColor: Colors.white.withValues(alpha: 0.05),
              ),
              style: const TextStyle(color: Colors.white),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text(
              'Annuler',
              style: TextStyle(color: Colors.white54),
            ),
          ),
          TextButton(
            onPressed: () {
              message = msgController.text.trim().isEmpty
                  ? null
                  : msgController.text.trim();
              Navigator.of(ctx).pop(true);
            },
            style: TextButton.styleFrom(
              foregroundColor: BookmiColors.brandBlueLight,
            ),
            child: const Text('Envoyer'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _loading = true);
    final repo = context.read<BookingRepository>();
    final result = await repo.proposeReschedule(
      bookingId: widget.bookingId,
      proposedDate: dateStr,
      message: message,
    );
    if (!mounted) return;
    setState(() => _loading = false);
    switch (result) {
      case ApiSuccess():
        widget.onProposed();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: Colors.red.shade700,
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      child: InkWell(
        onTap: _loading ? null : _propose,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 14),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (_loading)
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white54,
                  ),
                )
              else
                const Icon(
                  Icons.event_repeat_outlined,
                  size: 18,
                  color: Colors.white60,
                ),
              const SizedBox(width: 8),
              const Text(
                'Proposer un report',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  color: Colors.white70,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Cancel booking button (client: paid/confirmed, ≥ 2 days before event) ──

enum _CancelTier { full, partial, mediation }

class _CancelBookingButton extends StatefulWidget {
  const _CancelBookingButton({
    required this.bookingId,
    required this.eventDate,
    required this.cachetAmount,
    required this.onCancelled,
  });

  final int bookingId;
  final String eventDate;
  final int cachetAmount;
  final VoidCallback onCancelled;

  /// Days between now and the event date (negative if past).
  static int daysUntilEvent(String eventDate) {
    try {
      return DateTime.parse(eventDate).difference(DateTime.now()).inDays;
    } catch (_) {
      return -1;
    }
  }

  static _CancelTier tierFor(int daysUntil) {
    if (daysUntil >= 14) return _CancelTier.full;
    if (daysUntil >= 7) return _CancelTier.partial;
    return _CancelTier.mediation;
  }

  @override
  State<_CancelBookingButton> createState() => _CancelBookingButtonState();
}

class _CancelBookingButtonState extends State<_CancelBookingButton> {
  bool _loading = false;

  Future<void> _confirmCancel() async {
    final days = _CancelBookingButton.daysUntilEvent(widget.eventDate);
    final tier = _CancelBookingButton.tierFor(days);

    String title;
    String body;
    Color accentColor;

    switch (tier) {
      case _CancelTier.full:
        title = 'Remboursement intégral';
        body =
            'L\'événement est dans $days jours. Vous serez remboursé intégralement.';
        accentColor = const Color(0xFF4CAF50);
      case _CancelTier.partial:
        final refund = (widget.cachetAmount * 0.5).round();
        title = 'Remboursement partiel (50%)';
        body =
            'L\'événement est dans $days jours. Vous serez remboursé à hauteur de ${TalentCard.formatCachet(refund)}.';
        accentColor = const Color(0xFFFF9800);
      case _CancelTier.mediation:
        title = 'Médiation requise';
        body =
            'L\'événement est dans $days jours. L\'annulation est soumise à médiation — aucun remboursement automatique.';
        accentColor = const Color(0xFFF44336);
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1A2744),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            Icon(Icons.info_outline, color: accentColor, size: 22),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                title,
                style: TextStyle(
                  color: accentColor,
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              body,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.85),
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              'Confirmer l\'annulation de cette réservation ?',
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.6),
                fontSize: 13,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              'Non, garder',
              style: TextStyle(color: Colors.white.withValues(alpha: 0.6)),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              'Oui, annuler',
              style: TextStyle(color: accentColor, fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _loading = true);

    final repo = context.read<BookingRepository>();
    final result = await repo.cancelBooking(widget.bookingId);

    if (!mounted) return;
    setState(() => _loading = false);

    switch (result) {
      case ApiSuccess():
        widget.onCancelled();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: BookmiColors.error,
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: _loading ? null : _confirmCancel,
        icon: _loading
            ? const SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.red,
                ),
              )
            : const Icon(Icons.cancel_outlined, size: 18, color: Colors.red),
        label: Text(
          _loading ? 'Annulation...' : 'Annuler la réservation',
          style: const TextStyle(
            color: Colors.red,
            fontWeight: FontWeight.w600,
          ),
        ),
        style: OutlinedButton.styleFrom(
          side: const BorderSide(color: Colors.red, width: 1.2),
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
    );
  }
}

// ── Report booking (client: completed bookings only) ────────────────────────

class _ReportButton extends StatefulWidget {
  const _ReportButton({required this.bookingId});
  final int bookingId;

  @override
  State<_ReportButton> createState() => _ReportButtonState();
}

class _ReportButtonState extends State<_ReportButton> {
  bool _loading = false;
  String _selectedReason = 'other';
  final _descController = TextEditingController();

  static const _reasons = [
    ('no_show', 'Talent absent'),
    ('late_arrival', 'Retard important'),
    ('quality_issue', 'Qualité insuffisante'),
    ('payment_issue', 'Problème de paiement'),
    ('inappropriate_behaviour', 'Comportement inapproprié'),
    ('other', 'Autre motif'),
  ];

  @override
  void dispose() {
    _descController.dispose();
    super.dispose();
  }

  Future<void> _showReportDialog() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => AlertDialog(
          backgroundColor: const Color(0xFF1A2744),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          title: const Row(
            children: [
              Icon(Icons.flag_outlined, color: Color(0xFFFF9800), size: 20),
              SizedBox(width: 8),
              Text(
                'Signaler la réservation',
                style: TextStyle(
                  color: Color(0xFFFF9800),
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Motif du signalement',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.7),
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 8),
                ..._reasons.map(
                  (r) => RadioListTile<String>(
                    value: r.$1,
                    groupValue: _selectedReason,
                    onChanged: (v) =>
                        setModalState(() => _selectedReason = v ?? 'other'),
                    title: Text(
                      r.$2,
                      style: const TextStyle(color: Colors.white, fontSize: 13),
                    ),
                    activeColor: const Color(0xFFFF9800),
                    dense: true,
                    contentPadding: EdgeInsets.zero,
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _descController,
                  maxLines: 3,
                  maxLength: 500,
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                  decoration: InputDecoration(
                    hintText: 'Description (optionnelle)…',
                    hintStyle: TextStyle(
                      color: Colors.white.withValues(alpha: 0.4),
                      fontSize: 13,
                    ),
                    filled: true,
                    fillColor: Colors.white.withValues(alpha: 0.08),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: BorderSide.none,
                    ),
                    counterStyle: TextStyle(
                      color: Colors.white.withValues(alpha: 0.4),
                    ),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: Text(
                'Annuler',
                style: TextStyle(color: Colors.white.withValues(alpha: 0.5)),
              ),
            ),
            TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text(
                'Envoyer',
                style: TextStyle(
                  color: Color(0xFFFF9800),
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _loading = true);
    final repo = context.read<BookingRepository>();
    final result = await repo.reportBooking(
      bookingId: widget.bookingId,
      reason: _selectedReason,
      description: _descController.text.trim().isEmpty
          ? null
          : _descController.text.trim(),
    );
    if (!mounted) return;
    setState(() => _loading = false);

    switch (result) {
      case ApiSuccess():
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Signalement envoyé. Notre équipe va examiner le dossier.',
            ),
            backgroundColor: Color(0xFFFF9800),
          ),
        );
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: BookmiColors.error,
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: _loading ? null : _showReportDialog,
        icon: _loading
            ? const SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Color(0xFFFF9800),
                ),
              )
            : const Icon(
                Icons.flag_outlined,
                size: 18,
                color: Color(0xFFFF9800),
              ),
        label: Text(
          _loading ? 'Envoi...' : 'Signaler un problème',
          style: const TextStyle(
            color: Color(0xFFFF9800),
            fontWeight: FontWeight.w600,
          ),
        ),
        style: OutlinedButton.styleFrom(
          side: const BorderSide(color: Color(0xFFFF9800), width: 1.2),
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
    );
  }
}
