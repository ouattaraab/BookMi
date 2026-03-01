import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/favorites/widgets/favorite_button.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

/// Minimal talent detail page for Story 1.10.
/// Will be replaced by a full profile page in Story 1.11.
class TalentDetailPage extends StatelessWidget {
  const TalentDetailPage({
    required this.talentId,
    this.talentData,
    super.key,
  });

  final int talentId;
  final Map<String, dynamic>? talentData;

  @override
  Widget build(BuildContext context) {
    final data = talentData ?? const <String, dynamic>{};
    final stageName = (data['stage_name'] as String?) ?? 'Talent';
    final photoUrl = data['photo_url'] as String?;
    final category = data['category'] as Map<String, dynamic>?;
    final categoryName = (category?['name'] as String?) ?? '';
    final city = (data['city'] as String?) ?? '';
    final cachetAmount = (data['cachet_amount'] as int?) ?? 0;
    final averageRating = double.tryParse('${data['average_rating']}') ?? 0;
    final isVerified = (data['is_verified'] as bool?) ?? false;
    final isGroup = (data['is_group'] as bool?) ?? false;
    final groupSize = data['group_size'] as int?;
    final collectiveName = data['collective_name'] as String?;

    return Container(
      decoration: const BoxDecoration(
        gradient: BookmiColors.gradientHero,
      ),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          foregroundColor: Colors.white,
          title: Text(stageName),
          actions: [
            Padding(
              padding: const EdgeInsets.only(right: BookmiSpacing.spaceBase),
              child: FavoriteButton(talentId: talentId),
            ),
          ],
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Hero photo
              Hero(
                tag: 'talent-$talentId',
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(24),
                  child: photoUrl != null && photoUrl.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: photoUrl,
                          width: double.infinity,
                          height: 300,
                          fit: BoxFit.cover,
                          placeholder: (context, url) => _placeholderBox(),
                          errorWidget: (context, url, error) =>
                              _placeholderBox(),
                        )
                      : Container(
                          width: double.infinity,
                          height: 300,
                          color: BookmiColors.glassDarkMedium,
                          child: const Center(
                            child: Icon(
                              Icons.person,
                              color: Colors.white38,
                              size: 64,
                            ),
                          ),
                        ),
                ),
              ),
              const SizedBox(height: BookmiSpacing.spaceLg),
              // Info card
              GlassCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            stageName,
                            style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w600,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        if (isVerified)
                          Container(
                            width: 28,
                            height: 28,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: BookmiColors.glassWhiteMedium,
                              border: Border.all(
                                color: BookmiColors.glassBorder,
                              ),
                            ),
                            child: const Icon(
                              Icons.check,
                              size: 16,
                              color: Colors.white,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: BookmiSpacing.spaceSm),
                    if (categoryName.isNotEmpty)
                      Text(
                        categoryName,
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.white.withValues(alpha: 0.7),
                        ),
                      ),
                    if (city.isNotEmpty) ...[
                      const SizedBox(height: BookmiSpacing.spaceXs),
                      Row(
                        children: [
                          Icon(
                            Icons.location_on,
                            size: 14,
                            color: Colors.white.withValues(alpha: 0.5),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            city,
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.white.withValues(alpha: 0.7),
                            ),
                          ),
                        ],
                      ),
                    ],
                    if (isGroup) ...[
                      const SizedBox(height: BookmiSpacing.spaceSm),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: BookmiColors.glassWhite,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: BookmiColors.glassBorder),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              Icons.group_outlined,
                              size: 13,
                              color: Colors.white.withValues(alpha: 0.8),
                            ),
                            const SizedBox(width: 5),
                            Text(
                              collectiveName != null &&
                                      collectiveName.isNotEmpty
                                  ? collectiveName
                                  : groupSize != null
                                  ? 'Groupe · $groupSize personnes'
                                  : 'Groupe',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.white.withValues(alpha: 0.85),
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                    const SizedBox(height: BookmiSpacing.spaceMd),
                    Row(
                      children: [
                        Text(
                          TalentCard.formatCachet(cachetAmount),
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w600,
                            color: BookmiColors.brandBlueLight,
                          ),
                        ),
                        const Spacer(),
                        const Icon(
                          Icons.star,
                          size: 18,
                          color: BookmiColors.warning,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          averageRating.toStringAsFixed(1),
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w500,
                            color: BookmiColors.warning,
                          ),
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

  static Widget _placeholderBox() {
    return Container(
      width: double.infinity,
      height: 300,
      color: BookmiColors.glassDarkMedium,
      child: const Center(
        child: Icon(
          Icons.person,
          color: Colors.white38,
          size: 64,
        ),
      ),
    );
  }
}
