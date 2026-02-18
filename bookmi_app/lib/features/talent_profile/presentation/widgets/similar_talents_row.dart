import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

class SimilarTalentsRow extends StatelessWidget {
  const SimilarTalentsRow({
    required this.talents,
    required this.onTalentTap,
    super.key,
  });

  final List<Map<String, dynamic>> talents;
  final void Function(Map<String, dynamic> talent) onTalentTap;

  @override
  Widget build(BuildContext context) {
    if (talents.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Talents similaires',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: BookmiSpacing.spaceSm),
        SizedBox(
          height: 240,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: talents.length,
            separatorBuilder: (_, _) =>
                const SizedBox(width: BookmiSpacing.spaceSm),
            itemBuilder: (context, index) {
              final talent = talents[index];
              final attrs =
                  talent['attributes'] as Map<String, dynamic>? ?? talent;
              final category = attrs['category'] as Map<String, dynamic>?;
              final categorySlug = category?['slug'] as String?;
              final categoryName = category?['name'] as String? ?? '';

              return SizedBox(
                width: 160,
                child: TalentCard(
                  id: talent['id'] as int? ?? 0,
                  stageName: attrs['stage_name'] as String? ?? '',
                  categoryName: categoryName,
                  categoryColor: BookmiColors.categoryColor(categorySlug),
                  city: attrs['city'] as String? ?? '',
                  cachetAmount: attrs['cachet_amount'] as int? ?? 0,
                  averageRating:
                      double.tryParse(
                        '${attrs['average_rating']}',
                      ) ??
                      0,
                  isVerified: attrs['is_verified'] as bool? ?? false,
                  photoUrl: attrs['photo_url'] as String? ?? '',
                  onTap: () => onTalentTap(talent),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
