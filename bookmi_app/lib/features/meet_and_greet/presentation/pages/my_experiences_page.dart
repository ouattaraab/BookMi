import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/network/api_result.dart';
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
const _mutedFg = Color(0xFF94A3B8);
const _border = Color(0x1AFFFFFF);

class MyExperiencesPage extends StatefulWidget {
  const MyExperiencesPage({required this.repository, super.key});

  final ExperienceRepository repository;

  @override
  State<MyExperiencesPage> createState() => _MyExperiencesPageState();
}

class _MyExperiencesPageState extends State<MyExperiencesPage> {
  late Future<ApiResult<List<ExperienceModel>>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.repository.getMyExperiences();
  }

  void _reload() {
    setState(() {
      _future = widget.repository.getMyExperiences();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        backgroundColor: _bg,
        elevation: 0,
        title: ShaderMask(
          shaderCallback: (bounds) => const LinearGradient(
            colors: [_blue, _violet],
          ).createShader(bounds),
          child: Text(
            'Mes Meet & Greet',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
        ),
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back_ios_new_rounded,
            color: Colors.white,
          ),
          onPressed: () => context.pop(),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: _blue,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_rounded),
        label: Text(
          'Créer',
          style: GoogleFonts.manrope(fontWeight: FontWeight.w600),
        ),
        onPressed: () async {
          await context.pushNamed(RouteNames.createExperience);
          _reload();
        },
      ),
      body: FutureBuilder<ApiResult<List<ExperienceModel>>>(
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

          final experiences =
              (result as ApiSuccess<List<ExperienceModel>>).data;
          if (experiences.isEmpty) {
            return _EmptyView(
              onCreateTap: () async {
                await context.pushNamed(RouteNames.createExperience);
                _reload();
              },
            );
          }

          return RefreshIndicator(
            color: _blue,
            backgroundColor: _surface,
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
              itemCount: experiences.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                return _ExperienceCard(
                  experience: experiences[index],
                  onTap: () => context.pushNamed(
                    RouteNames.experienceDetail,
                    pathParameters: {'id': experiences[index].id.toString()},
                    extra: {
                      'experience': experiences[index],
                      'isOwner': true,
                    },
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

// ── Experience Card ───────────────────────────────────────────────

class _ExperienceCard extends StatelessWidget {
  const _ExperienceCard({required this.experience, required this.onTap});

  final ExperienceModel experience;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final seatsUsed = experience.maxSeats - experience.seatsAvailable;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: _surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: _border),
        ),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    experience.title,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const SizedBox(width: 8),
                _StatusBadge(status: experience.status),
              ],
            ),
            const SizedBox(height: 10),
            _InfoRow(
              icon: Icons.calendar_today_outlined,
              text: _formatDate(experience.eventDate),
            ),
            const SizedBox(height: 6),
            _InfoRow(
              icon: Icons.people_outline_rounded,
              text: '$seatsUsed / ${experience.maxSeats} places',
            ),
            const SizedBox(height: 6),
            _InfoRow(
              icon: Icons.monetization_on_outlined,
              text: '${_formatAmount(experience.pricePerSeat)} FCFA / place',
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                Text(
                  'Voir le détail',
                  style: GoogleFonts.manrope(
                    fontSize: 12,
                    color: _blue,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(width: 4),
                const Icon(
                  Icons.arrow_forward_ios_rounded,
                  size: 12,
                  color: _blue,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(String raw) {
    try {
      final dt = DateTime.parse(raw);
      return DateFormat('dd MMM yyyy • HH:mm', 'fr_FR').format(dt);
    } catch (_) {
      return raw;
    }
  }

  String _formatAmount(int amount) {
    return NumberFormat('#,###', 'fr_FR').format(amount).replaceAll(',', ' ');
  }
}

// ── Status Badge ──────────────────────────────────────────────────

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final (label, color) = switch (status) {
      'published' => ('Publié', const Color(0xFF14B8A6)),
      'completed' => ('Terminé', const Color(0xFF6366F1)),
      'cancelled' => ('Annulé', const Color(0xFFEF4444)),
      _ => ('Brouillon', _mutedFg),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: GoogleFonts.manrope(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

// ── Info Row ──────────────────────────────────────────────────────

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 14, color: _mutedFg),
        const SizedBox(width: 6),
        Expanded(
          child: Text(
            text,
            style: GoogleFonts.manrope(
              fontSize: 13,
              color: _mutedFg,
            ),
          ),
        ),
      ],
    );
  }
}

// ── Empty State ───────────────────────────────────────────────────

class _EmptyView extends StatelessWidget {
  const _EmptyView({required this.onCreateTap});

  final VoidCallback onCreateTap;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ShaderMask(
              shaderCallback: (bounds) => const LinearGradient(
                colors: [_blue, _violet],
              ).createShader(bounds),
              child: const Icon(
                Icons.star_outline_rounded,
                size: 64,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 20),
            Text(
              'Aucun Meet & Greet',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Créez votre première expérience exclusive pour vos fans.',
              textAlign: TextAlign.center,
              style: GoogleFonts.manrope(
                fontSize: 14,
                color: _mutedFg,
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: _blue,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 14,
                ),
              ),
              icon: const Icon(Icons.add_rounded),
              label: Text(
                'Créer une expérience',
                style: GoogleFonts.manrope(fontWeight: FontWeight.w600),
              ),
              onPressed: onCreateTap,
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
