import 'package:bookmi_app/features/meet_and_greet/data/models/experience_model.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

// Meet & Greet palette — blue/violet
const _mgPrimary = Color(0xFF1AB3FF);
const _mgSecondary = Color(0xFF8B5CF6);
const _cardBg = Color(0xFF0F1C3A);
const _cardBorder = Color(0x33FFFFFF);

class ExperienceCard extends StatelessWidget {
  const ExperienceCard({
    required this.experience,
    required this.onTap,
    super.key,
  });

  final ExperienceModel experience;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isFull = experience.isFull;
    final talent = experience.talentProfile;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 220,
        margin: const EdgeInsets.only(right: 12),
        decoration: BoxDecoration(
          color: _cardBg,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: _cardBorder, width: 1),
          boxShadow: [
            BoxShadow(
              color: _mgPrimary.withValues(alpha: 0.15),
              blurRadius: 16,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // ── Talent avatar header ─────────────────────────────
              _TalentAvatarHeader(talent: talent, isFull: isFull),
              // ── Info ─────────────────────────────────────────────
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      experience.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                        height: 1.3,
                      ),
                    ),
                    const SizedBox(height: 6),
                    _DateRow(
                      eventDate: experience.eventDate,
                      eventTime: experience.eventTime,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        _PriceChip(pricePerSeat: experience.pricePerSeat),
                        _SeatsBadge(
                          isFull: isFull,
                          seatsAvailable: experience.seatsAvailable,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Sub-widgets ───────────────────────────────────────────────────

class _TalentAvatarHeader extends StatelessWidget {
  const _TalentAvatarHeader({
    required this.talent,
    required this.isFull,
  });

  final ExperienceTalentInfo? talent;
  final bool isFull;

  @override
  Widget build(BuildContext context) {
    final photoUrl = talent?.profilePhoto;

    return Container(
      height: 110,
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [_mgPrimary, _mgSecondary],
        ),
      ),
      child: Stack(
        children: [
          // Avatar
          Center(
            child: Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.4),
                  width: 2,
                ),
              ),
              child: ClipOval(
                child: photoUrl != null && photoUrl.isNotEmpty
                    ? CachedNetworkImage(
                        imageUrl: photoUrl,
                        fit: BoxFit.cover,
                        placeholder: (_, __) => _AvatarPlaceholder(
                          stageName: talent?.stageName ?? '',
                        ),
                        errorWidget: (_, __, ___) => _AvatarPlaceholder(
                          stageName: talent?.stageName ?? '',
                        ),
                      )
                    : _AvatarPlaceholder(stageName: talent?.stageName ?? ''),
              ),
            ),
          ),
          // Category chip (top-left)
          if (talent?.categoryName != null)
            Positioned(
              top: 8,
              left: 10,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 8,
                  vertical: 3,
                ),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.35),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  talent!.categoryName!,
                  style: GoogleFonts.manrope(
                    fontSize: 9,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
          // "M&G" badge (top-right)
          Positioned(
            top: 8,
            right: 10,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.5),
                  width: 1,
                ),
              ),
              child: Text(
                'M&G',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 9,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                ),
              ),
            ),
          ),
          // Stage name bottom
          Positioned(
            bottom: 8,
            left: 0,
            right: 0,
            child: Text(
              talent?.stageName ?? '',
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AvatarPlaceholder extends StatelessWidget {
  const _AvatarPlaceholder({required this.stageName});
  final String stageName;

  @override
  Widget build(BuildContext context) {
    final initial =
        stageName.isNotEmpty ? stageName[0].toUpperCase() : '?';
    return Container(
      color: Colors.white.withValues(alpha: 0.15),
      child: Center(
        child: Text(
          initial,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 22,
            fontWeight: FontWeight.w800,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

class _DateRow extends StatelessWidget {
  const _DateRow({required this.eventDate, required this.eventTime});
  final String eventDate;
  final String eventTime;

  @override
  Widget build(BuildContext context) {
    String formatted = eventDate;
    try {
      final parsed = DateTime.parse(eventDate);
      formatted = DateFormat('d MMM yyyy', 'fr_FR').format(parsed);
    } catch (_) {}

    // Trim seconds from time if present (HH:mm:ss → HH:mm)
    final time = eventTime.length > 5 ? eventTime.substring(0, 5) : eventTime;

    return Row(
      children: [
        const Icon(Icons.calendar_today_rounded, size: 11, color: _mgPrimary),
        const SizedBox(width: 4),
        Flexible(
          child: Text(
            '$formatted · $time',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: GoogleFonts.manrope(
              fontSize: 11,
              color: Colors.white.withValues(alpha: 0.7),
            ),
          ),
        ),
      ],
    );
  }
}

class _PriceChip extends StatelessWidget {
  const _PriceChip({required this.pricePerSeat});
  final int pricePerSeat;

  @override
  Widget build(BuildContext context) {
    final formatted = NumberFormat('#,###', 'fr_FR')
        .format(pricePerSeat)
        .replaceAll(RegExp(r'[\s\u00A0\u202F,]'), '\u202F');
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [_mgPrimary, _mgSecondary],
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        '$formatted FCFA',
        style: GoogleFonts.plusJakartaSans(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: Colors.white,
        ),
      ),
    );
  }
}

class _SeatsBadge extends StatelessWidget {
  const _SeatsBadge({required this.isFull, required this.seatsAvailable});
  final bool isFull;
  final int seatsAvailable;

  @override
  Widget build(BuildContext context) {
    if (isFull) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: const Color(0xFFFBBF24).withValues(alpha: 0.15),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: const Color(0xFFFBBF24).withValues(alpha: 0.5),
          ),
        ),
        child: Text(
          'COMPLET',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 9,
            fontWeight: FontWeight.w800,
            color: const Color(0xFFFBBF24),
          ),
        ),
      );
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: const Color(0xFF00C853).withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: const Color(0xFF00C853).withValues(alpha: 0.5),
        ),
      ),
      child: Text(
        '$seatsAvailable place${seatsAvailable > 1 ? 's' : ''}',
        style: GoogleFonts.plusJakartaSans(
          fontSize: 9,
          fontWeight: FontWeight.w700,
          color: const Color(0xFF00C853),
        ),
      ),
    );
  }
}
