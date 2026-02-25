import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/tracking/bloc/tracking_cubit.dart';
import 'package:bookmi_app/features/tracking/bloc/tracking_state.dart';
import 'package:bookmi_app/features/tracking/data/models/tracking_event_model.dart';
import 'package:bookmi_app/features/tracking/data/repositories/tracking_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Shows the real-time status timeline for a booking.
///
/// Accessed by both client (read-only) and talent (can advance status).
class TrackingPage extends StatelessWidget {
  const TrackingPage({
    required this.bookingId,
    required this.repository,
    super.key,
  });

  final int bookingId;
  final TrackingRepository repository;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) {
        final cubit = TrackingCubit(repository: repository);
        cubit.loadEvents(bookingId); // ignore: discarded_futures
        return cubit;
      },
      child: _TrackingView(bookingId: bookingId),
    );
  }
}

// ── View ─────────────────────────────────────────────────────────────────────

class _TrackingView extends StatelessWidget {
  const _TrackingView({required this.bookingId});

  final int bookingId;

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
            'Suivi Jour J',
            style: TextStyle(
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
                    context.read<TrackingCubit>().loadEvents(bookingId),
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
            TrackingLoaded(:final events, :final isCompleted) => Stack(
              children: [
                _buildContent(context, events),
                if (isCompleted) const _CelebrationOverlay(),
              ],
            ),
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
                  context.read<TrackingCubit>().loadEvents(bookingId),
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

  Widget _buildContent(
    BuildContext context,
    List<TrackingEventModel> events,
  ) {
    return ListView(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      children: [
        _TrackingTimeline(events: events),
        const SizedBox(height: BookmiSpacing.spaceLg),
        _NextStepButton(events: events, bookingId: bookingId),
        const SizedBox(height: BookmiSpacing.spaceXl),
      ],
    );
  }
}

// ── Timeline Widget ───────────────────────────────────────────────────────────

class _TrackingTimeline extends StatelessWidget {
  const _TrackingTimeline({required this.events});

  final List<TrackingEventModel> events;

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
            );
          }),
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
  });

  final IconData icon;
  final String label;
  final bool isDone;
  final bool isCurrent;
  final bool isLast;
  final DateTime? occurredAt;

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
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _dismiss() {
    _controller.reverse().then((_) {
      if (mounted) setState(() => _dismissed = true);
    });
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
