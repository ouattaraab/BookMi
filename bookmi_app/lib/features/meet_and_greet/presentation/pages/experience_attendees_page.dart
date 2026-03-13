import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/meet_and_greet/data/models/experience_attendee_model.dart';
import 'package:bookmi_app/features/meet_and_greet/data/models/experience_model.dart';
import 'package:bookmi_app/features/meet_and_greet/data/repositories/experience_repository.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

// ── Aurora palette (dark) ─────────────────────────────────────────
const _blue = Color(0xFF1AB3FF);
const _violet = Color(0xFF8B5CF6);
const _bg = Color(0xFF0F172A);
const _surface = Color(0xFF1E293B);
const _surfaceAlt = Color(0xFF263148);
const _mutedFg = Color(0xFF94A3B8);
const _border = Color(0x1AFFFFFF);

class ExperienceAttendeesPage extends StatefulWidget {
  const ExperienceAttendeesPage({
    required this.experienceId,
    required this.repository,
    this.experience,
    super.key,
  });

  final int experienceId;
  final ExperienceModel? experience;
  final ExperienceRepository repository;

  @override
  State<ExperienceAttendeesPage> createState() =>
      _ExperienceAttendeesPageState();
}

class _ExperienceAttendeesPageState extends State<ExperienceAttendeesPage> {
  late Future<ApiResult<List<ExperienceAttendee>>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.getAttendees(widget.experienceId);
  }

  void _reload() {
    setState(() {
      _future = widget.repository.getAttendees(widget.experienceId);
    });
  }

  @override
  Widget build(BuildContext context) {
    final exp = widget.experience;
    final title = exp?.title ?? 'Meet & Greet';

    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        backgroundColor: _bg,
        elevation: 0,
        title: Text(
          title,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new_rounded,
            color: Colors.white,
          ),
          onPressed: () => context.pop(),
        ),
      ),
      body: FutureBuilder<ApiResult<List<ExperienceAttendee>>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: _blue),
            );
          }

          final result = snapshot.data;
          if (result == null || result is ApiFailure) {
            final msg = result is ApiFailure
                ? (result as ApiFailure).message
                : 'Erreur inconnue';
            return _ErrorView(message: msg, onRetry: _reload);
          }

          final attendees =
              (result as ApiSuccess<List<ExperienceAttendee>>).data;

          return RefreshIndicator(
            color: _blue,
            backgroundColor: _surface,
            onRefresh: () async => _reload(),
            child: CustomScrollView(
              slivers: [
                // Summary card
                if (exp != null)
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                      child: _SummaryCard(experience: exp),
                    ),
                  ),

                // Attendees header
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
                    child: Row(
                      children: [
                        ShaderMask(
                          shaderCallback: (bounds) => const LinearGradient(
                            colors: [_blue, _violet],
                          ).createShader(bounds),
                          child: const Icon(
                            Icons.people_rounded,
                            color: Colors.white,
                            size: 18,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          'Inscrits (${attendees.length})',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // Attendees list or empty state
                if (attendees.isEmpty)
                  const SliverFillRemaining(
                    hasScrollBody: false,
                    child: _EmptyAttendeesView(),
                  )
                else
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 32),
                    sliver: SliverList.separated(
                      itemCount: attendees.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 10),
                      itemBuilder: (context, index) =>
                          _AttendeeRow(attendee: attendees[index]),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}

// ── Summary Card ──────────────────────────────────────────────────

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.experience});

  final ExperienceModel experience;

  @override
  Widget build(BuildContext context) {
    final seatsUsed = experience.maxSeats - experience.seatsAvailable;

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            _blue.withValues(alpha: 0.15),
            _violet.withValues(alpha: 0.15),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: _blue.withValues(alpha: 0.3),
        ),
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            experience.title,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _StatChip(
                  icon: Icons.event_rounded,
                  label: _formatDate(experience.eventDate),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _StatChip(
                  icon: Icons.people_rounded,
                  label: '$seatsUsed / ${experience.maxSeats} places',
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _StatChip(
                  icon: Icons.monetization_on_rounded,
                  label: '${_formatAmount(experience.pricePerSeat)} FCFA',
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _formatDate(String raw) {
    try {
      final dt = DateTime.parse(raw);
      return DateFormat('dd MMM yyyy', 'fr_FR').format(dt);
    } catch (_) {
      return raw;
    }
  }

  String _formatAmount(int amount) {
    return NumberFormat('#,###', 'fr_FR').format(amount).replaceAll(',', ' ');
  }
}

class _StatChip extends StatelessWidget {
  const _StatChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 13, color: _blue),
        const SizedBox(width: 5),
        Flexible(
          child: Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: 12,
              color: _mutedFg,
              fontWeight: FontWeight.w500,
            ),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}

// ── Attendee Row ──────────────────────────────────────────────────

class _AttendeeRow extends StatelessWidget {
  const _AttendeeRow({required this.attendee});

  final ExperienceAttendee attendee;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _border),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          // Avatar
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: const LinearGradient(
                colors: [_blue, _violet],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Center(
              child: Text(
                _initials(attendee.fullName),
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          // Name + date
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  attendee.fullName,
                  style: GoogleFonts.manrope(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  _formatDate(attendee.createdAt),
                  style: GoogleFonts.manrope(
                    fontSize: 11,
                    color: _mutedFg,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          // Seats + amount
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              _SeatsBadge(count: attendee.seatsCount),
              const SizedBox(height: 4),
              Text(
                '${_formatAmount(attendee.totalAmount)} F',
                style: GoogleFonts.manrope(
                  fontSize: 12,
                  color: _mutedFg,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(width: 8),
          _StatusDot(status: attendee.status, label: attendee.statusLabel),
        ],
      ),
    );
  }

  String _initials(String name) {
    final parts = name.trim().split(' ');
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    return name.isNotEmpty ? name[0].toUpperCase() : '?';
  }

  String _formatDate(String raw) {
    try {
      final dt = DateTime.parse(raw).toLocal();
      return DateFormat('dd MMM yyyy', 'fr_FR').format(dt);
    } catch (_) {
      return raw;
    }
  }

  String _formatAmount(int amount) {
    return NumberFormat('#,###', 'fr_FR').format(amount).replaceAll(',', ' ');
  }
}

// ── Seats Badge ───────────────────────────────────────────────────

class _SeatsBadge extends StatelessWidget {
  const _SeatsBadge({required this.count});

  final int count;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: _blue.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: _blue.withValues(alpha: 0.3)),
      ),
      child: Text(
        '$count place${count > 1 ? 's' : ''}',
        style: GoogleFonts.manrope(
          fontSize: 11,
          color: _blue,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

// ── Status Dot ────────────────────────────────────────────────────

class _StatusDot extends StatelessWidget {
  const _StatusDot({required this.status, required this.label});

  final String status;
  final String label;

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'paid' => const Color(0xFF14B8A6),
      'pending' => const Color(0xFFF59E0B),
      'cancelled' => const Color(0xFFEF4444),
      _ => _mutedFg,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: GoogleFonts.manrope(
          fontSize: 10,
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

// ── Empty State ───────────────────────────────────────────────────

class _EmptyAttendeesView extends StatelessWidget {
  const _EmptyAttendeesView();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: _surfaceAlt,
                border: Border.all(color: _border),
              ),
              child: const Icon(
                Icons.people_outline_rounded,
                size: 30,
                color: _mutedFg,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              'Aucun inscrit pour le moment',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Les fans qui réservent des places apparaîtront ici.',
              textAlign: TextAlign.center,
              style: GoogleFonts.manrope(
                fontSize: 13,
                color: _mutedFg,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Error State ───────────────────────────────────────────────────

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off_rounded, size: 48, color: _mutedFg),
            const SizedBox(height: 16),
            Text(
              message,
              textAlign: TextAlign.center,
              style: GoogleFonts.manrope(fontSize: 14, color: _mutedFg),
            ),
            const SizedBox(height: 20),
            TextButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded, color: _blue),
              label: Text(
                'Réessayer',
                style: GoogleFonts.manrope(color: _blue),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
