import 'dart:async';

import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/tracking/bloc/tracking_cubit.dart';
import 'package:bookmi_app/features/tracking/bloc/tracking_state.dart';
import 'package:bookmi_app/features/tracking/data/models/tracking_event_model.dart';
import 'package:bookmi_app/features/tracking/data/repositories/tracking_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:geolocator/geolocator.dart';

/// Shows the real-time status timeline for a booking.
///
/// Accessed by both client (read-only + confirm arrival CTA) and talent (can advance status).
class TrackingPage extends StatelessWidget {
  const TrackingPage({
    required this.bookingId,
    required this.repository,
    this.isClient = false,
    this.clientConfirmedAt,
    super.key,
  });

  final int bookingId;
  final TrackingRepository repository;
  final bool isClient;
  final DateTime? clientConfirmedAt;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) {
        final cubit = TrackingCubit(
          repository: repository,
          isClient: isClient,
          clientConfirmedAt: clientConfirmedAt,
        );
        cubit.loadEvents(bookingId); // ignore: discarded_futures
        return cubit;
      },
      child: _TrackingView(bookingId: bookingId, isClient: isClient),
    );
  }
}

// ── View ─────────────────────────────────────────────────────────────────────

class _TrackingView extends StatefulWidget {
  const _TrackingView({required this.bookingId, required this.isClient});

  final int bookingId;
  final bool isClient;

  @override
  State<_TrackingView> createState() => _TrackingViewState();
}

class _TrackingViewState extends State<_TrackingView> {
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    // Auto-refresh every 30s for client when status < arrived
    if (widget.isClient) {
      _refreshTimer = Timer.periodic(const Duration(seconds: 30), (_) {
        final state = context.read<TrackingCubit>().state;
        if (state is TrackingLoaded) {
          final status = state.currentStatus;
          if (status == null ||
              status == 'preparing' ||
              status == 'en_route') {
            context.read<TrackingCubit>().loadEvents(widget.bookingId);
          }
        }
      });
    }
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
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
          title: Text(
            widget.isClient ? 'Suivi en temps réel' : 'Suivi Jour J',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.w600,
            ),
          ),
          actions: [
            BlocBuilder<TrackingCubit, TrackingState>(
              builder: (context, state) => IconButton(
                icon: const Icon(Icons.refresh, color: Colors.white70),
                onPressed: () =>
                    context.read<TrackingCubit>().loadEvents(widget.bookingId),
              ),
            ),
          ],
        ),
        body: BlocConsumer<TrackingCubit, TrackingState>(
          listener: (context, state) {
            if (state is TrackingError) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(state.message),
                  backgroundColor: BookmiColors.error,
                ),
              );
            }
          },
          builder: (context, state) => switch (state) {
            TrackingInitial() || TrackingLoading() => const Center(
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: BookmiColors.brandBlue,
              ),
            ),
            TrackingError(:final message) => _buildError(context, message),
            TrackingLoaded() => _buildContent(context, state),
          },
        ),
      ),
    );
  }

  Widget _buildError(BuildContext context, String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(BookmiSpacing.spaceXl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, size: 48, color: Colors.white38),
            const SizedBox(height: BookmiSpacing.spaceSm),
            Text(
              message,
              style: const TextStyle(color: Colors.white70),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: BookmiSpacing.spaceLg),
            TextButton(
              onPressed: () =>
                  context.read<TrackingCubit>().loadEvents(widget.bookingId),
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

  Widget _buildContent(BuildContext context, TrackingLoaded state) {
    if (state.isClient) {
      return _ClientTrackingView(
        events: state.events,
        bookingId: widget.bookingId,
        clientConfirmedAt: state.clientConfirmedAt,
        isUpdating: state is TrackingUpdating,
      );
    }
    return Stack(
      children: [
        _TalentTrackingView(
          events: state.events,
          bookingId: widget.bookingId,
          clientConfirmedAt: state.clientConfirmedAt,
        ),
        if (state.isCompleted) const _CelebrationOverlay(),
      ],
    );
  }
}

// ── CLIENT VIEW (taxi-style) ──────────────────────────────────────────────────

class _ClientTrackingView extends StatelessWidget {
  const _ClientTrackingView({
    required this.events,
    required this.bookingId,
    required this.clientConfirmedAt,
    required this.isUpdating,
  });

  final List<TrackingEventModel> events;
  final int bookingId;
  final DateTime? clientConfirmedAt;
  final bool isUpdating;

  // 4-step progress bar (performing/completed shown as text below)
  static const _progressSteps = [
    ('preparing', 'Préparation', Icons.schedule_outlined),
    ('en_route', 'En route', Icons.directions_car_outlined),
    ('arrived', 'Arrivé', Icons.location_on_outlined),
    ('confirmed', 'Confirmé', Icons.check_circle_outline),
  ];

  int get _progressIndex {
    if (clientConfirmedAt != null) return 3;
    final statuses = events.map((e) => e.status).toSet();
    if (statuses.contains('arrived')) return 2;
    if (statuses.contains('en_route')) return 1;
    if (statuses.contains('preparing')) return 0;
    return -1;
  }

  bool get _talentArrived => events.any((e) => e.status == 'arrived');

  TrackingEventModel? _eventForStatus(String status) =>
      events.where((e) => e.status == status).lastOrNull;

  @override
  Widget build(BuildContext context) {
    final currentIdx = _progressIndex;

    return RefreshIndicator(
      color: BookmiColors.brandBlue,
      onRefresh: () => context.read<TrackingCubit>().loadEvents(bookingId),
      child: ListView(
        padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
        children: [
          // ── Header status icon ──
          _ClientStatusHeader(events: events, clientConfirmedAt: clientConfirmedAt),
          const SizedBox(height: BookmiSpacing.spaceLg),

          // ── Progress bar (4 steps) ──
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Progression',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),
                // Horizontal step indicator
                Row(
                  children: List.generate(_progressSteps.length * 2 - 1, (i) {
                    if (i.isOdd) {
                      // Connector line
                      final stepIdx = i ~/ 2;
                      final isDone = currentIdx > stepIdx;
                      return Expanded(
                        child: Container(
                          height: 2,
                          color: isDone
                              ? BookmiColors.success
                              : Colors.white12,
                        ),
                      );
                    }
                    final stepIdx = i ~/ 2;
                    final isDone = currentIdx >= stepIdx;
                    final isCurrent = currentIdx == stepIdx;
                    final (_, label, icon) = _progressSteps[stepIdx];
                    return _StepNode(
                      icon: icon,
                      label: label,
                      isDone: isDone,
                      isCurrent: isCurrent,
                    );
                  }),
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),
                // Timestamps for completed steps
                ..._progressSteps.asMap().entries
                    .where((e) => e.key <= currentIdx && e.key < 3)
                    .map((e) {
                  final (status, label, _) = e.value;
                  final event = _eventForStatus(status);
                  if (event?.occurredAt == null) return const SizedBox.shrink();
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Row(
                      children: [
                        Text(
                          '$label : ',
                          style: const TextStyle(
                            fontSize: 12,
                            color: Colors.white54,
                          ),
                        ),
                        Text(
                          _formatTime(event!.occurredAt!),
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: Colors.white70,
                          ),
                        ),
                        if (event.clientNotifiedAt != null) ...[
                          const SizedBox(width: 6),
                          Text(
                            '· notifié ${_formatTime(event.clientNotifiedAt!)}',
                            style: const TextStyle(
                              fontSize: 11,
                              color: Colors.white38,
                            ),
                          ),
                        ],
                      ],
                    ),
                  );
                }),
                if (clientConfirmedAt != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Row(
                      children: [
                        const Text(
                          'Confirmation : ',
                          style: TextStyle(fontSize: 12, color: Colors.white54),
                        ),
                        Text(
                          _formatTime(clientConfirmedAt!),
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: BookmiColors.success,
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),

          // ── Confirm arrival CTA ──
          if (_talentArrived && clientConfirmedAt == null) ...[
            _ConfirmArrivalCard(
              bookingId: bookingId,
              isUpdating: isUpdating,
            ),
            const SizedBox(height: BookmiSpacing.spaceMd),
          ],

          // ── Confirmation done banner ──
          if (clientConfirmedAt != null) ...[
            _ConfirmationDoneBanner(confirmedAt: clientConfirmedAt!),
            const SizedBox(height: BookmiSpacing.spaceMd),
          ],

          // ── Secondary statuses (performing, completed) ──
          if (events.any((e) => e.status == 'performing' || e.status == 'completed')) ...[
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Prestation',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Colors.white70,
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  for (final e in events.where(
                    (e) => e.status == 'performing' || e.status == 'completed',
                  ))
                    Padding(
                      padding: const EdgeInsets.only(bottom: 4),
                      child: Row(
                        children: [
                          Icon(
                            e.status == 'completed'
                                ? Icons.star_outline
                                : Icons.music_note_outlined,
                            size: 14,
                            color: Colors.white54,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            e.statusLabel,
                            style: const TextStyle(
                              fontSize: 13,
                              color: Colors.white,
                            ),
                          ),
                          if (e.occurredAt != null) ...[
                            const SizedBox(width: 8),
                            Text(
                              _formatTime(e.occurredAt!),
                              style: const TextStyle(
                                fontSize: 11,
                                color: Colors.white38,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(height: BookmiSpacing.spaceMd),
          ],

          const SizedBox(height: BookmiSpacing.spaceXl),
        ],
      ),
    );
  }

  static String _formatTime(DateTime dt) {
    final local = dt.toLocal();
    final h = local.hour.toString().padLeft(2, '0');
    final m = local.minute.toString().padLeft(2, '0');
    return '$h:$m';
  }
}

class _ClientStatusHeader extends StatelessWidget {
  const _ClientStatusHeader({required this.events, required this.clientConfirmedAt});

  final List<TrackingEventModel> events;
  final DateTime? clientConfirmedAt;

  @override
  Widget build(BuildContext context) {
    if (clientConfirmedAt != null) {
      return _headerCard(Icons.check_circle_outline, BookmiColors.success, 'Présence confirmée ✅');
    }
    final status = events.isEmpty ? null : events.last.status;
    return switch (status) {
      'preparing'  => _headerCard(Icons.schedule_outlined, BookmiColors.brandBlue, 'Votre artiste se prépare 🎵'),
      'en_route'   => _headerCard(Icons.directions_car_outlined, BookmiColors.brandBlueLight, 'Votre artiste est en route 🚗'),
      'arrived'    => _headerCard(Icons.location_on_outlined, BookmiColors.success, 'Votre artiste est arrivé ! ✅'),
      'performing' => _headerCard(Icons.music_note_outlined, const Color(0xFFAB47BC), 'La prestation est en cours 🎤'),
      'completed'  => _headerCard(Icons.star_outline, BookmiColors.success, 'Prestation terminée ⭐'),
      _            => _headerCard(Icons.hourglass_empty, Colors.white38, 'En attente du talent…'),
    };
  }

  Widget _headerCard(IconData icon, Color color, String label) {
    return GlassCard(
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: color.withValues(alpha: 0.15),
              border: Border.all(color: color, width: 2),
            ),
            child: Icon(icon, size: 26, color: color),
          ),
          const SizedBox(width: BookmiSpacing.spaceMd),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StepNode extends StatelessWidget {
  const _StepNode({
    required this.icon,
    required this.label,
    required this.isDone,
    required this.isCurrent,
  });

  final IconData icon;
  final String label;
  final bool isDone;
  final bool isCurrent;

  @override
  Widget build(BuildContext context) {
    final color = isDone
        ? BookmiColors.success
        : isCurrent
        ? BookmiColors.brandBlue
        : Colors.white24;

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: color.withValues(alpha: 0.15),
            border: Border.all(color: color, width: 2),
          ),
          child: Icon(icon, size: 16, color: color),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 9,
            color: isDone || isCurrent ? Colors.white70 : Colors.white24,
          ),
        ),
      ],
    );
  }
}

// ── Confirm Arrival CTA Card ──────────────────────────────────────────────────

class _ConfirmArrivalCard extends StatelessWidget {
  const _ConfirmArrivalCard({
    required this.bookingId,
    required this.isUpdating,
  });

  final int bookingId;
  final bool isUpdating;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(BookmiSpacing.spaceMd),
      decoration: BoxDecoration(
        color: BookmiColors.success.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: BookmiColors.success.withValues(alpha: 0.4),
          width: 1.5,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.location_on,
                color: BookmiColors.success,
                size: 20,
              ),
              const SizedBox(width: 8),
              const Expanded(
                child: Text(
                  'L\'artiste est arrivé !',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          const Text(
            'Confirmez sa présence pour libérer le paiement',
            style: TextStyle(fontSize: 13, color: Colors.white70),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),
          SizedBox(
            width: double.infinity,
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: isUpdating ? null : const LinearGradient(
                  colors: [BookmiColors.success, Color(0xFF15803D)],
                ),
                color: isUpdating ? Colors.white12 : null,
                borderRadius: BorderRadius.circular(12),
              ),
              child: TextButton.icon(
                onPressed: isUpdating
                    ? null
                    : () => context
                        .read<TrackingCubit>()
                        .confirmArrival(bookingId),
                style: TextButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    vertical: BookmiSpacing.spaceMd,
                  ),
                  foregroundColor: Colors.white,
                  disabledForegroundColor: Colors.white38,
                ),
                icon: isUpdating
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white38,
                        ),
                      )
                    : const Icon(
                        Icons.check_circle_outline,
                        size: 18,
                        color: Colors.white,
                      ),
                label: isUpdating
                    ? const SizedBox.shrink()
                    : const Text(
                        'Confirmer la présence',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ConfirmationDoneBanner extends StatelessWidget {
  const _ConfirmationDoneBanner({required this.confirmedAt});

  final DateTime confirmedAt;

  static String _formatTime(DateTime dt) {
    final local = dt.toLocal();
    final h = local.hour.toString().padLeft(2, '0');
    final m = local.minute.toString().padLeft(2, '0');
    return '$h:$m';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(BookmiSpacing.spaceMd),
      decoration: BoxDecoration(
        color: BookmiColors.success.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: BookmiColors.success.withValues(alpha: 0.4),
          width: 1.5,
        ),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.check_circle,
            color: BookmiColors.success,
            size: 24,
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Présence confirmée ✅',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: BookmiColors.success,
                  ),
                ),
                Text(
                  'Confirmée à ${_formatTime(confirmedAt)} · Paiement libéré',
                  style: const TextStyle(
                    fontSize: 12,
                    color: Colors.white54,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ── TALENT VIEW (existing, enriched) ─────────────────────────────────────────

class _TalentTrackingView extends StatelessWidget {
  const _TalentTrackingView({
    required this.events,
    required this.bookingId,
    required this.clientConfirmedAt,
  });

  final List<TrackingEventModel> events;
  final int bookingId;
  final DateTime? clientConfirmedAt;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      children: [
        _TrackingTimeline(events: events, clientConfirmedAt: clientConfirmedAt),
        const SizedBox(height: BookmiSpacing.spaceLg),
        _NextStepButton(events: events, bookingId: bookingId),
        const SizedBox(height: BookmiSpacing.spaceXl),
      ],
    );
  }
}

// ── Timeline Widget (talent) ──────────────────────────────────────────────────

class _TrackingTimeline extends StatelessWidget {
  const _TrackingTimeline({required this.events, required this.clientConfirmedAt});

  final List<TrackingEventModel> events;
  final DateTime? clientConfirmedAt;

  static const _steps = [
    ('preparing', 'En préparation', Icons.schedule_outlined),
    ('en_route', 'En route', Icons.directions_car_outlined),
    ('arrived', 'Arrivé sur place', Icons.location_on_outlined),
    ('performing', 'En prestation', Icons.music_note_outlined),
    ('completed', 'Prestation terminée', Icons.star_outline),
  ];

  @override
  Widget build(BuildContext context) {
    final completedStatuses = events.map((e) => e.status).toSet();
    final currentStatus = events.isEmpty ? null : events.last.status;
    final talentArrived = completedStatuses.contains('arrived');

    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Progression',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),
          ..._steps.asMap().entries.map((entry) {
            final i = entry.key;
            final (status, label, icon) = entry.value;
            final isDone = completedStatuses.contains(status);
            final isCurrent = status == currentStatus;
            final isLast = i == _steps.length - 1;

            return _TimelineStep(
              icon: icon,
              label: label,
              isDone: isDone,
              isCurrent: isCurrent,
              isLast: isLast,
              occurredAt: isDone
                  ? events
                        .where((e) => e.status == status)
                        .lastOrNull
                        ?.occurredAt
                  : null,
              clientNotifiedAt: isDone
                  ? events
                        .where((e) => e.status == status)
                        .lastOrNull
                        ?.clientNotifiedAt
                  : null,
            );
          }),
          // ── Client confirmation badge ──
          if (talentArrived) ...[
            const SizedBox(height: BookmiSpacing.spaceSm),
            if (clientConfirmedAt != null)
              _ClientConfirmedBadge(confirmedAt: clientConfirmedAt!)
            else
              _AwaitingConfirmationBadge(),
          ],
        ],
      ),
    );
  }
}

class _TimelineStep extends StatelessWidget {
  const _TimelineStep({
    required this.icon,
    required this.label,
    required this.isDone,
    required this.isCurrent,
    required this.isLast,
    this.occurredAt,
    this.clientNotifiedAt,
  });

  final IconData icon;
  final String label;
  final bool isDone;
  final bool isCurrent;
  final bool isLast;
  final DateTime? occurredAt;
  final DateTime? clientNotifiedAt;

  @override
  Widget build(BuildContext context) {
    final nodeColor = isDone
        ? BookmiColors.success
        : isCurrent
        ? BookmiColors.brandBlue
        : Colors.white24;

    final labelColor = isDone || isCurrent ? Colors.white : Colors.white38;

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Node + vertical line
          SizedBox(
            width: 32,
            child: Column(
              children: [
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: nodeColor.withValues(alpha: 0.15),
                    border: Border.all(color: nodeColor, width: 2),
                  ),
                  child: Icon(icon, size: 16, color: nodeColor),
                ),
                if (!isLast)
                  Expanded(
                    child: Container(
                      width: 2,
                      margin: const EdgeInsets.symmetric(vertical: 4),
                      color: isDone
                          ? BookmiColors.success.withValues(alpha: 0.4)
                          : Colors.white12,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          // Label + time
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(
                bottom: isLast ? 0 : BookmiSpacing.spaceMd,
                top: 6,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: isCurrent ? FontWeight.w700 : FontWeight.w400,
                      color: labelColor,
                    ),
                  ),
                  if (occurredAt != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      _formatTime(occurredAt!),
                      style: const TextStyle(
                        fontSize: 11,
                        color: Colors.white38,
                      ),
                    ),
                  ],
                  if (clientNotifiedAt != null) ...[
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        const Icon(
                          Icons.smartphone,
                          size: 10,
                          color: Color(0xFFAB47BC),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          'Client notifié ${_formatTime(clientNotifiedAt!)}',
                          style: const TextStyle(
                            fontSize: 10,
                            color: Color(0xFFAB47BC),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  static String _formatTime(DateTime dt) {
    final local = dt.toLocal();
    final h = local.hour.toString().padLeft(2, '0');
    final m = local.minute.toString().padLeft(2, '0');
    return '$h:$m';
  }
}

class _ClientConfirmedBadge extends StatelessWidget {
  const _ClientConfirmedBadge({required this.confirmedAt});

  final DateTime confirmedAt;

  static String _formatTime(DateTime dt) {
    final local = dt.toLocal();
    final h = local.hour.toString().padLeft(2, '0');
    final m = local.minute.toString().padLeft(2, '0');
    return '$h:$m';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: BookmiSpacing.spaceSm,
        vertical: 8,
      ),
      decoration: BoxDecoration(
        color: BookmiColors.success.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: BookmiColors.success.withValues(alpha: 0.3),
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(
            Icons.check_circle,
            color: BookmiColors.success,
            size: 14,
          ),
          const SizedBox(width: 6),
          Text(
            'Client a confirmé ✅ à ${_formatTime(confirmedAt)}',
            style: const TextStyle(
              fontSize: 12,
              color: BookmiColors.success,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _AwaitingConfirmationBadge extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: BookmiSpacing.spaceSm,
        vertical: 8,
      ),
      decoration: BoxDecoration(
        color: Colors.orange.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: Colors.orange.withValues(alpha: 0.3),
        ),
      ),
      child: const Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.hourglass_empty, color: Colors.orange, size: 14),
          SizedBox(width: 6),
          Text(
            'En attente de confirmation client',
            style: TextStyle(
              fontSize: 12,
              color: Colors.orange,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Next Step Action Button ───────────────────────────────────────────────────

class _NextStepButton extends StatelessWidget {
  const _NextStepButton({required this.events, required this.bookingId});

  final List<TrackingEventModel> events;
  final int bookingId;

  static const _transitions = [
    ('preparing', 'en_route', 'Je suis en route'),
    ('en_route', 'arrived', 'Je suis arrivé'),
    ('arrived', 'performing', 'Démarrer la prestation'),
    ('performing', 'completed', 'Terminer la prestation'),
  ];

  @override
  Widget build(BuildContext context) {
    final currentStatus = events.isEmpty ? null : events.last.status;

    // Find the next step label
    final transition = _transitions
        .where((t) => t.$1 == currentStatus)
        .firstOrNull;

    if (transition == null) {
      // No next step — show initial start button or nothing when completed
      if (currentStatus == 'completed') return const SizedBox.shrink();
      if (currentStatus != null) return const SizedBox.shrink();

      // Not started — show the "start" button
      return _ActionButton(
        label: 'Démarrer le suivi',
        status: 'preparing',
        bookingId: bookingId,
      );
    }

    // Arriving on site requires GPS check-in via the dedicated endpoint.
    if (transition.$2 == 'arrived') {
      return _CheckInButton(bookingId: bookingId);
    }

    return _ActionButton(
      label: transition.$3,
      status: transition.$2,
      bookingId: bookingId,
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.label,
    required this.status,
    required this.bookingId,
  });

  final String label;
  final String status;
  final int bookingId;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<TrackingCubit, TrackingState>(
      builder: (context, state) {
        final isUpdating = state is TrackingUpdating;
        return SizedBox(
          width: double.infinity,
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: isUpdating ? null : BookmiColors.gradientBrand,
              color: isUpdating ? Colors.white12 : null,
              borderRadius: BorderRadius.circular(12),
            ),
            child: TextButton(
              onPressed: isUpdating
                  ? null
                  : () => context.read<TrackingCubit>().postUpdate(
                      bookingId,
                      status,
                    ),
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(
                  vertical: BookmiSpacing.spaceMd,
                ),
                foregroundColor: Colors.white,
                disabledForegroundColor: Colors.white38,
              ),
              child: isUpdating
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white38,
                      ),
                    )
                  : Text(
                      label,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
            ),
          ),
        );
      },
    );
  }
}

// ── Check-in Button (GPS required) ───────────────────────────────────────────

/// Shown when the talent needs to confirm physical arrival.
/// Requests GPS permission, obtains coordinates, then calls [TrackingCubit.checkIn].
class _CheckInButton extends StatefulWidget {
  const _CheckInButton({required this.bookingId});

  final int bookingId;

  @override
  State<_CheckInButton> createState() => _CheckInButtonState();
}

class _CheckInButtonState extends State<_CheckInButton> {
  bool _fetchingLocation = false;

  Future<void> _onTap() async {
    setState(() => _fetchingLocation = true);
    try {
      // 1. Check service availability.
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        _showError(
          'Le service de localisation est désactivé. '
          'Activez-le dans les paramètres de votre appareil.',
        );
        return;
      }

      // 2. Check / request permission.
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        _showError(
          'Permission de localisation refusée. '
          "Autorisez l'accès dans les paramètres de l'application.",
        );
        return;
      }

      // 3. Obtain position (15 s timeout).
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
          timeLimit: Duration(seconds: 15),
        ),
      );

      if (!mounted) return;

      // 4. Send check-in with GPS coordinates.
      await context.read<TrackingCubit>().checkIn(
        widget.bookingId,
        latitude: position.latitude,
        longitude: position.longitude,
      );
    } on TimeoutException {
      _showError(
        "Impossible d'obtenir votre position. "
        'Vérifiez votre connexion GPS et réessayez.',
      );
    } on Exception catch (_) {
      _showError('Erreur lors de la récupération de votre position.');
    } finally {
      if (mounted) setState(() => _fetchingLocation = false);
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: BookmiColors.error),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<TrackingCubit, TrackingState>(
      builder: (context, state) {
        final busy = _fetchingLocation || state is TrackingUpdating;

        return SizedBox(
          width: double.infinity,
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: busy ? null : BookmiColors.gradientBrand,
              color: busy ? Colors.white12 : null,
              borderRadius: BorderRadius.circular(12),
            ),
            child: TextButton.icon(
              onPressed: busy ? null : _onTap,
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(
                  vertical: BookmiSpacing.spaceMd,
                ),
                foregroundColor: Colors.white,
                disabledForegroundColor: Colors.white38,
              ),
              icon: busy
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white38,
                      ),
                    )
                  : const Icon(
                      Icons.location_on_outlined,
                      size: 18,
                      color: Colors.white,
                    ),
              label: busy
                  ? const SizedBox.shrink()
                  : const Text(
                      'Je suis arrivé',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
            ),
          ),
        );
      },
    );
  }
}

// ── Celebration Overlay ───────────────────────────────────────────────────────

class _CelebrationOverlay extends StatefulWidget {
  const _CelebrationOverlay();

  @override
  State<_CelebrationOverlay> createState() => _CelebrationOverlayState();
}

class _CelebrationOverlayState extends State<_CelebrationOverlay>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _opacity;
  bool _dismissed = false;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _opacity = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeIn,
    );
    unawaited(_controller.forward());
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _dismiss() {
    unawaited(
      _controller.reverse().then((_) {
        if (mounted) setState(() => _dismissed = true);
      }),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_dismissed) return const SizedBox.shrink();

    return FadeTransition(
      opacity: _opacity,
      child: GestureDetector(
        onTap: _dismiss,
        child: Container(
          color: BookmiColors.brandNavy.withValues(alpha: 0.85),
          alignment: Alignment.center,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 96,
                height: 96,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      BookmiColors.success.withValues(alpha: 0.3),
                      Colors.transparent,
                    ],
                  ),
                ),
                child: const Icon(
                  Icons.check_circle,
                  color: BookmiColors.success,
                  size: 64,
                ),
              ),
              const SizedBox(height: BookmiSpacing.spaceLg),
              const Text(
                'Prestation terminée !',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: BookmiSpacing.spaceSm),
              const Text(
                'Merci pour votre excellente prestation.',
                style: TextStyle(fontSize: 15, color: Colors.white70),
              ),
              const SizedBox(height: BookmiSpacing.spaceXl),
              TextButton(
                onPressed: _dismiss,
                child: const Text(
                  'Fermer',
                  style: TextStyle(color: BookmiColors.brandBlueLight),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
