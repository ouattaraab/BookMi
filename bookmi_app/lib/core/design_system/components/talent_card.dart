import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/favorites/widgets/favorite_button.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
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

  static String formatCachet(int amountInCents) {
    final amount = amountInCents ~/ 100;
    final formatted = NumberFormat(
      '#,###',
      'fr_FR',
    ).format(amount).replaceAll(RegExp(r'[\s\u00A0\u202F,]'), ' ');
    return '$formatted FCFA';
  }

  @override
  Widget build(BuildContext context) {
    return Hero(
      tag: 'talent-$id',
      child: GlassCard(
        padding: EdgeInsets.zero,
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildPhotoSection(),
            Padding(
              padding: const EdgeInsets.all(BookmiSpacing.spaceSm),
              child: _buildInfoSection(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPhotoSection() {
    return SizedBox(
      height: 120,
      width: double.infinity,
      child: Stack(
        fit: StackFit.expand,
        children: [
          ClipRRect(
            borderRadius: const BorderRadius.only(
              topLeft: Radius.circular(BookmiRadius.card),
              topRight: Radius.circular(BookmiRadius.card),
            ),
            child: CachedNetworkImage(
              imageUrl: photoUrl,
              fit: BoxFit.cover,
              placeholder: (context, url) => const ColoredBox(
                color: BookmiColors.glassDarkMedium,
                child: Center(
                  child: Icon(
                    Icons.person,
                    color: Colors.white38,
                    size: 40,
                  ),
                ),
              ),
              errorWidget: (context, url, error) => const ColoredBox(
                color: BookmiColors.glassDarkMedium,
                child: Center(
                  child: Icon(
                    Icons.person,
                    color: Colors.white38,
                    size: 40,
                  ),
                ),
              ),
            ),
          ),
          // FavoriteButton top-left
          Positioned(
            top: BookmiSpacing.spaceXs,
            left: BookmiSpacing.spaceXs,
            child: FavoriteButton(talentId: id, size: 20),
          ),
          // Verified badge top-right
          if (isVerified)
            Positioned(
              top: BookmiSpacing.spaceXs,
              right: BookmiSpacing.spaceXs,
              child: Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: BookmiColors.glassWhiteMedium,
                  border: Border.all(
                    color: BookmiColors.glassBorder,
                  ),
                ),
                child: const Icon(
                  Icons.check,
                  size: 14,
                  color: Colors.white,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildInfoSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        // Stage name
        Text(
          stageName,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: Colors.white,
            height: 1.3,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: 2),
        // Category
        Text(
          categoryName,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w400,
            color: categoryColor,
            height: 1.3,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: BookmiSpacing.spaceXs),
        // Cachet
        Text(
          formatCachet(cachetAmount),
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: BookmiColors.brandBlueLight,
            height: 1.3,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: 2),
        // Rating
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.star,
              size: 14,
              color: BookmiColors.warning,
            ),
            const SizedBox(width: 2),
            Text(
              averageRating.toStringAsFixed(1),
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w400,
                color: BookmiColors.warning,
                height: 1.3,
              ),
            ),
          ],
        ),
      ],
    );
  }
}
