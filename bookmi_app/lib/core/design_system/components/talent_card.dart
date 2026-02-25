import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/favorites/widgets/favorite_button.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

class TalentCard extends StatelessWidget {
  const TalentCard({
    required this.id,
    required this.stageName,
    required this.categoryName,
    required this.categoryColor,
    required this.city,
    required this.cachetAmount,
    required this.averageRating,
    required this.isVerified,
    required this.photoUrl,
    this.onTap,
    super.key,
  });

  final int id;
  final String stageName;
  final String categoryName;
  final Color categoryColor;
  final String city;
  final int cachetAmount;
  final double averageRating;
  final bool isVerified;
  final String photoUrl;
  final VoidCallback? onTap;

  static String formatCachet(int amount) {
    final formatted = NumberFormat(
      '#,###',
      'fr_FR',
    ).format(amount).replaceAll(RegExp(r'[\s\u00A0\u202F,]'), '\u202F');
    return '$formatted FCFA';
  }

  @override
  Widget build(BuildContext context) {
    return Hero(
      tag: 'talent-$id',
      child: GlassCard(
        padding: EdgeInsets.zero,
        onTap: onTap,
        child: Stack(
          fit: StackFit.expand,
          children: [
            // ── Full-card image ──────────────────────────────────
            ClipRRect(
              borderRadius: BookmiRadius.cardBorder,
              child: photoUrl.isNotEmpty
                  ? CachedNetworkImage(
                      imageUrl: photoUrl,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => _placeholder(),
                      errorWidget: (_, __, ___) => _placeholder(),
                    )
                  : _placeholder(),
            ),

            // ── Gradient overlay (top subtle + bottom strong) ────
            ClipRRect(
              borderRadius: BookmiRadius.cardBorder,
              child: DecoratedBox(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    stops: [0.0, 0.35, 0.65, 1.0],
                    colors: [
                      Color(0x55000000), // légère ombre en haut
                      Colors.transparent,
                      Color(0x99000000),
                      Color(0xE8000000), // opaque en bas
                    ],
                  ),
                ),
              ),
            ),

            // ── Info section anchored at bottom ──────────────────
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(10, 0, 10, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Stage name
                    Text(
                      stageName,
                      style: GoogleFonts.nunito(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                        height: 1.2,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    // Category
                    Text(
                      categoryName,
                      style: GoogleFonts.manrope(
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        color: categoryColor,
                        height: 1.2,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    // Cachet + Rating row
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            formatCachet(cachetAmount),
                            style: GoogleFonts.nunito(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: BookmiColors.brandBlueLight,
                              height: 1.2,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Icon(
                          Icons.star_rounded,
                          size: 13,
                          color: BookmiColors.brandBlueLight,
                        ),
                        const SizedBox(width: 2),
                        Text(
                          averageRating.toStringAsFixed(1),
                          style: GoogleFonts.nunito(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Colors.white70,
                            height: 1.2,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),

            // ── Favorite button — top left ────────────────────────
            Positioned(
              top: BookmiSpacing.spaceXs,
              left: BookmiSpacing.spaceXs,
              child: FavoriteButton(talentId: id, size: 20),
            ),

            // ── Verified badge — top right ────────────────────────
            if (isVerified)
              Positioned(
                top: BookmiSpacing.spaceXs,
                right: BookmiSpacing.spaceXs,
                child: Container(
                  width: 24,
                  height: 24,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: BookmiColors.brandBlue.withValues(alpha: 0.85),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.3),
                      width: 1.5,
                    ),
                  ),
                  child: const Icon(
                    Icons.verified,
                    size: 13,
                    color: Colors.white,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _placeholder() {
    return ColoredBox(
      color: BookmiColors.glassDarkMedium,
      child: Center(
        child: Icon(
          Icons.person,
          color: Colors.white.withValues(alpha: 0.25),
          size: 48,
        ),
      ),
    );
  }
}
