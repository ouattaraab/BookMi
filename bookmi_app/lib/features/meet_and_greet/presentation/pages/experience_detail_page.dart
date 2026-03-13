import 'package:bookmi_app/features/meet_and_greet/data/models/experience_model.dart';
import 'package:bookmi_app/features/meet_and_greet/presentation/cubit/experience_detail_cubit.dart';
import 'package:bookmi_app/features/meet_and_greet/presentation/cubit/experience_detail_state.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

// ── Aurora palette ────────────────────────────────────────────────
const _blue = Color(0xFF1AB3FF);
const _violet = Color(0xFF8B5CF6);
const _titleColor = Color(0xFF1E1B4B);
const _bgStart = Color(0xFFEFF8FF);
const _bgEnd = Color(0xFFF0EEFF);
const _mutedText = Color(0xFF6B7280);

class ExperienceDetailPage extends StatefulWidget {
  const ExperienceDetailPage({
    required this.experienceId,
    this.preloaded,
    super.key,
  });

  final int experienceId;
  final ExperienceModel? preloaded;

  @override
  State<ExperienceDetailPage> createState() => _ExperienceDetailPageState();
}

class _ExperienceDetailPageState extends State<ExperienceDetailPage> {
  int _seatsCount = 1;

  @override
  void initState() {
    super.initState();
    final cubit = context.read<ExperienceDetailCubit>();
    if (widget.preloaded != null) {
      cubit.initWithExperience(widget.preloaded!);
    } else {
      cubit.loadDetail(widget.experienceId);
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocConsumer<ExperienceDetailCubit, ExperienceDetailState>(
      listener: (context, state) {
        if (state is ExperienceDetailBookingSuccess) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: const Color(0xFF00C853),
            ),
          );
          context.read<ExperienceDetailCubit>().acknowledgeBookingResult();
        } else if (state is ExperienceDetailBookingFailure) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.errorMessage),
              backgroundColor: Colors.redAccent,
            ),
          );
          context.read<ExperienceDetailCubit>().acknowledgeBookingResult();
        }
      },
      builder: (context, state) {
        return Scaffold(
          backgroundColor: _bgStart,
          body: Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [_bgStart, _bgEnd],
              ),
            ),
            child: Stack(
              children: [
                // Aurora glows
                Positioned(
                  top: -80,
                  left: -60,
                  child: Container(
                    width: 300,
                    height: 300,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: _blue.withValues(alpha: 0.13),
                    ),
                  ),
                ),
                Positioned(
                  bottom: 100,
                  right: -80,
                  child: Container(
                    width: 280,
                    height: 280,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: _violet.withValues(alpha: 0.11),
                    ),
                  ),
                ),
                // Content
                _buildContent(context, state),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildContent(BuildContext context, ExperienceDetailState state) {
    if (state is ExperienceDetailInitial || state is ExperienceDetailLoading) {
      return const Center(
        child: CircularProgressIndicator(color: _blue),
      );
    }
    if (state is ExperienceDetailFailure) {
      return SafeArea(
        child: Column(
          children: [
            // Back button row
            Padding(
              padding: const EdgeInsets.only(left: 8, top: 4),
              child: Align(
                alignment: Alignment.centerLeft,
                child: IconButton(
                  icon: const Icon(
                    Icons.arrow_back_ios_new_rounded,
                    color: _titleColor,
                  ),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ),
            ),
            Expanded(
              child: _ErrorView(
                message: state.message,
                onRetry: () => context.read<ExperienceDetailCubit>().loadDetail(
                  widget.experienceId,
                ),
              ),
            ),
          ],
        ),
      );
    }
    if (state is ExperienceDetailLoaded) {
      return _LoadedView(
        experience: state.experience,
        isLoading: state is ExperienceDetailBooking,
        seatsCount: _seatsCount,
        onSeatsChanged: (v) => setState(() => _seatsCount = v),
        onBook: () => context.read<ExperienceDetailCubit>().bookSeats(
          state.experience.id,
          _seatsCount,
        ),
        onCancel: () => context.read<ExperienceDetailCubit>().cancelBooking(
          state.experience.id,
        ),
      );
    }
    return const SizedBox.shrink();
  }
}

// ── Hero background ───────────────────────────────────────────────

class _HeroBackground extends StatelessWidget {
  const _HeroBackground({required this.experience, required this.talent});

  final ExperienceModel experience;
  final ExperienceTalentInfo? talent;

  @override
  Widget build(BuildContext context) {
    final coverUrl = experience.coverImageUrl;

    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [_blue, _violet],
        ),
      ),
      child: Stack(
        fit: StackFit.expand,
        children: [
          // Cover image if available
          if (coverUrl != null && coverUrl.isNotEmpty)
            CachedNetworkImage(
              imageUrl: coverUrl,
              fit: BoxFit.cover,
              placeholder: (_, __) => const SizedBox.shrink(),
              errorWidget: (_, __, ___) => const SizedBox.shrink(),
            ),
          // Gradient overlay for readability
          if (coverUrl != null && coverUrl.isNotEmpty)
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.black.withValues(alpha: 0.15),
                    Colors.black.withValues(alpha: 0.55),
                  ],
                ),
              ),
            ),
          // Talent info overlay
          Positioned(
            left: 0,
            right: 0,
            bottom: 16,
            child: Column(
              children: [
                _TalentAvatar(talent: talent),
                const SizedBox(height: 8),
                Text(
                  talent?.stageName ?? '',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                    shadows: [
                      Shadow(
                        color: Colors.black.withValues(alpha: 0.4),
                        blurRadius: 8,
                      ),
                    ],
                  ),
                ),
                if (talent?.categoryName != null)
                  Text(
                    talent!.categoryName!,
                    style: GoogleFonts.manrope(
                      fontSize: 12,
                      color: Colors.white.withValues(alpha: 0.85),
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

// ── Loaded view ───────────────────────────────────────────────────

class _LoadedView extends StatelessWidget {
  const _LoadedView({
    required this.experience,
    required this.isLoading,
    required this.seatsCount,
    required this.onSeatsChanged,
    required this.onBook,
    required this.onCancel,
  });

  final ExperienceModel experience;
  final bool isLoading;
  final int seatsCount;
  final ValueChanged<int> onSeatsChanged;
  final VoidCallback onBook;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    final talent = experience.talentProfile;

    return CustomScrollView(
      slivers: [
        // App bar
        SliverAppBar(
          expandedHeight: 200,
          pinned: true,
          backgroundColor: _bgStart,
          foregroundColor: _titleColor,
          elevation: 0,
          flexibleSpace: FlexibleSpaceBar(
            background: _HeroBackground(
              experience: experience,
              talent: talent,
            ),
          ),
        ),

        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 100),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Status badge
                _StatusBadge(status: experience.status),
                const SizedBox(height: 12),

                // Title
                Text(
                  experience.title,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: _titleColor,
                    height: 1.25,
                  ),
                ),
                const SizedBox(height: 16),

                // Info pills row
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _InfoPill(
                      icon: Icons.calendar_today_rounded,
                      label: _formatDate(experience.eventDate),
                    ),
                    _InfoPill(
                      icon: Icons.access_time_rounded,
                      label: _trimTime(experience.eventTime),
                    ),
                    _InfoPill(
                      icon: Icons.people_rounded,
                      label: experience.isFull
                          ? 'Complet'
                          : '${experience.seatsAvailable} place${experience.seatsAvailable > 1 ? 's' : ''}',
                      color: experience.isFull
                          ? const Color(0xFFFBBF24)
                          : const Color(0xFF00C853),
                    ),
                    if (experience.venueAddress != null &&
                        experience.venueAddress!.isNotEmpty &&
                        experience.venueRevealed)
                      _InfoPill(
                        icon: Icons.location_on_rounded,
                        label: experience.venueAddress!,
                      ),
                  ],
                ),
                const SizedBox(height: 20),

                // Description
                if (experience.description.isNotEmpty) ...[
                  _SectionTitle(label: 'À propos'),
                  const SizedBox(height: 8),
                  _GlassCard(
                    child: Text(
                      experience.description,
                      style: GoogleFonts.manrope(
                        fontSize: 14,
                        color: _mutedText,
                        height: 1.6,
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],

                // Booking card
                _BookingCard(
                  experience: experience,
                  isLoading: isLoading,
                  seatsCount: seatsCount,
                  onSeatsChanged: onSeatsChanged,
                  onBook: onBook,
                  onCancel: onCancel,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  String _formatDate(String raw) {
    try {
      return DateFormat('d MMM yyyy', 'fr_FR').format(DateTime.parse(raw));
    } catch (_) {
      return raw;
    }
  }

  String _trimTime(String t) => t.length > 5 ? t.substring(0, 5) : t;
}

// ── Booking card ──────────────────────────────────────────────────

class _BookingCard extends StatelessWidget {
  const _BookingCard({
    required this.experience,
    required this.isLoading,
    required this.seatsCount,
    required this.onSeatsChanged,
    required this.onBook,
    required this.onCancel,
  });

  final ExperienceModel experience;
  final bool isLoading;
  final int seatsCount;
  final ValueChanged<int> onSeatsChanged;
  final VoidCallback onBook;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    return _GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Price
          Row(
            children: [
              ShaderMask(
                shaderCallback: (bounds) => const LinearGradient(
                  colors: [_blue, _violet],
                ).createShader(bounds),
                child: Text(
                  '${_fmt(experience.pricePerSeat)} FCFA',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ),
              Text(
                ' / place',
                style: GoogleFonts.manrope(
                  fontSize: 14,
                  color: _mutedText,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          if (experience.hasBooked) ...[
            // Already booked
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: _blue.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: _blue.withValues(alpha: 0.25)),
              ),
              child: Row(
                children: [
                  const Icon(
                    Icons.check_circle_rounded,
                    color: _blue,
                    size: 20,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Vous avez réservé ${experience.myBooking!.seatsCount} place${experience.myBooking!.seatsCount > 1 ? 's' : ''}',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: _titleColor,
                          ),
                        ),
                        Text(
                          'Total : ${_fmt(experience.myBooking!.totalAmount)} FCFA',
                          style: GoogleFonts.manrope(
                            fontSize: 12,
                            color: _mutedText,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            // Cancel button
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: isLoading ? null : onCancel,
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.redAccent,
                  side: const BorderSide(color: Colors.redAccent),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
                child: isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.redAccent,
                        ),
                      )
                    : Text(
                        'Annuler ma réservation',
                        style: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
              ),
            ),
          ] else if (!experience.isFull && experience.isPublished) ...[
            // Seats selector
            Row(
              children: [
                Text(
                  'Nombre de places :',
                  style: GoogleFonts.manrope(
                    fontSize: 14,
                    color: _titleColor,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const Spacer(),
                _SeatsSelector(
                  value: seatsCount,
                  max: experience.seatsAvailable,
                  onChanged: onSeatsChanged,
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              'Total : ${_fmt(experience.pricePerSeat * seatsCount)} FCFA',
              style: GoogleFonts.manrope(
                fontSize: 13,
                color: _mutedText,
              ),
            ),
            const SizedBox(height: 14),
            // CTA
            SizedBox(
              width: double.infinity,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [_blue, _violet],
                  ),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: ElevatedButton(
                  onPressed: isLoading ? null : onBook,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.transparent,
                    shadowColor: Colors.transparent,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: isLoading
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : Text(
                          'Réserver maintenant',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                ),
              ),
            ),
          ] else ...[
            Center(
              child: Text(
                experience.isFull
                    ? 'Cet événement est complet'
                    : 'Réservation non disponible',
                style: GoogleFonts.manrope(
                  fontSize: 14,
                  color: _mutedText,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  String _fmt(int amount) => NumberFormat(
    '#,###',
    'fr_FR',
  ).format(amount).replaceAll(RegExp(r'[\s\u00A0\u202F,]'), '\u202F');
}

// ── Seats selector ────────────────────────────────────────────────

class _SeatsSelector extends StatelessWidget {
  const _SeatsSelector({
    required this.value,
    required this.max,
    required this.onChanged,
  });

  final int value;
  final int max;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _ControlButton(
          icon: Icons.remove,
          onTap: value > 1 ? () => onChanged(value - 1) : null,
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Text(
            '$value',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: _titleColor,
            ),
          ),
        ),
        _ControlButton(
          icon: Icons.add,
          onTap: value < max ? () => onChanged(value + 1) : null,
        ),
      ],
    );
  }
}

class _ControlButton extends StatelessWidget {
  const _ControlButton({required this.icon, this.onTap});
  final IconData icon;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final enabled = onTap != null;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          gradient: enabled
              ? const LinearGradient(colors: [_blue, _violet])
              : null,
          color: enabled ? null : Colors.grey.shade200,
        ),
        child: Icon(
          icon,
          size: 16,
          color: enabled ? Colors.white : Colors.grey,
        ),
      ),
    );
  }
}

// ── Helpers ───────────────────────────────────────────────────────

class _TalentAvatar extends StatelessWidget {
  const _TalentAvatar({required this.talent});
  final ExperienceTalentInfo? talent;

  @override
  Widget build(BuildContext context) {
    final photoUrl = talent?.profilePhoto;
    return Container(
      width: 72,
      height: 72,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.6),
          width: 2.5,
        ),
        gradient: const LinearGradient(colors: [_blue, _violet]),
      ),
      child: ClipOval(
        child: photoUrl != null && photoUrl.isNotEmpty
            ? CachedNetworkImage(
                imageUrl: photoUrl,
                fit: BoxFit.cover,
                placeholder: (_, __) => _InitialAvatar(
                  name: talent?.stageName ?? '',
                ),
                errorWidget: (_, __, ___) => _InitialAvatar(
                  name: talent?.stageName ?? '',
                ),
              )
            : _InitialAvatar(name: talent?.stageName ?? ''),
      ),
    );
  }
}

class _InitialAvatar extends StatelessWidget {
  const _InitialAvatar({required this.name});
  final String name;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white.withValues(alpha: 0.2),
      child: Center(
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : '?',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 24,
            fontWeight: FontWeight.w800,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color fg;
    String label;

    switch (status) {
      case 'published':
        bg = _blue.withValues(alpha: 0.12);
        fg = _blue;
        label = 'Disponible';
      case 'completed':
        bg = Colors.grey.withValues(alpha: 0.12);
        fg = Colors.grey.shade600;
        label = 'Terminé';
      case 'cancelled':
        bg = Colors.red.withValues(alpha: 0.1);
        fg = Colors.redAccent;
        label = 'Annulé';
      default:
        bg = Colors.orange.withValues(alpha: 0.1);
        fg = Colors.orange;
        label = 'Brouillon';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: fg.withValues(alpha: 0.3)),
      ),
      child: Text(
        label,
        style: GoogleFonts.manrope(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: fg,
        ),
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({required this.icon, required this.label, this.color});
  final IconData icon;
  final String label;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final c = color ?? _violet;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.85),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _violet.withValues(alpha: 0.15)),
        boxShadow: [
          BoxShadow(
            color: _blue.withValues(alpha: 0.06),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: c),
          const SizedBox(width: 5),
          Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: _titleColor,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: GoogleFonts.plusJakartaSans(
        fontSize: 16,
        fontWeight: FontWeight.w700,
        color: _titleColor,
      ),
    );
  }
}

class _GlassCard extends StatelessWidget {
  const _GlassCard({required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.82),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: _violet.withValues(alpha: 0.15)),
        boxShadow: [
          BoxShadow(
            color: _blue.withValues(alpha: 0.08),
            blurRadius: 20,
            offset: const Offset(0, 4),
          ),
          BoxShadow(
            color: _violet.withValues(alpha: 0.06),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.error_outline_rounded,
              size: 48,
              color: Colors.redAccent,
            ),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: GoogleFonts.manrope(color: _mutedText),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: onRetry,
              child: const Text('Réessayer'),
            ),
          ],
        ),
      ),
    );
  }
}
