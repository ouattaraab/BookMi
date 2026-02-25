import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/components/talent_card_skeleton.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

class TalentGrid extends StatelessWidget {
  const TalentGrid({
    required this.talents,
    required this.hasMore,
    required this.isLoadingMore,
    required this.scrollController,
    required this.onTalentTap,
    super.key,
  });

  final List<Map<String, dynamic>> talents;
  final bool hasMore;
  final bool isLoadingMore;
  final ScrollController scrollController;
  final ValueChanged<Map<String, dynamic>> onTalentTap;

  /// Creates a skeleton loading grid.
  static Widget skeleton() {
    return CustomScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      slivers: [
        SliverPadding(
          padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
          sliver: SliverGrid.count(
            crossAxisCount: 2,
            crossAxisSpacing: BookmiSpacing.spaceMd,
            mainAxisSpacing: BookmiSpacing.spaceMd,
            childAspectRatio: 0.72,
            children: List.generate(
              6,
              (_) => const TalentCardSkeleton(),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return CustomScrollView(
      controller: scrollController,
      physics: const AlwaysScrollableScrollPhysics(),
      slivers: [
        SliverPadding(
          padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
          sliver: SliverGrid(
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: BookmiSpacing.spaceMd,
              mainAxisSpacing: BookmiSpacing.spaceMd,
              childAspectRatio: 0.72,
            ),
            delegate: SliverChildBuilderDelegate(
              (context, index) => _buildTalentCard(talents[index]),
              childCount: talents.length,
            ),
          ),
        ),
        if (isLoadingMore)
          const SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.all(BookmiSpacing.spaceBase),
              child: Center(
                child: CircularProgressIndicator(
                  color: BookmiColors.brandBlue,
                ),
              ),
            ),
          ),
        if (!hasMore && talents.isNotEmpty)
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
              child: Center(
                child: Text(
                  'Fin des résultats',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.white.withValues(alpha: 0.5),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildTalentCard(Map<String, dynamic> talent) {
    final attributes = talent['attributes'] as Map<String, dynamic>;
    final category = attributes['category'] as Map<String, dynamic>?;
    final categorySlug = category?['slug'] as String?;
    final categoryName = (category?['name'] as String?) ?? '';

    return TalentCard(
      id: talent['id'] as int,
      stageName: attributes['stage_name'] as String,
      categoryName: categoryName,
      categoryColor: BookmiColors.categoryColor(categorySlug),
      city: (attributes['city'] as String?) ?? '',
      cachetAmount: attributes['cachet_amount'] as int,
      averageRating: double.tryParse('${attributes['average_rating']}') ?? 0,
      isVerified: (attributes['is_verified'] as bool?) ?? false,
      photoUrl: (attributes['photo_url'] as String?) ?? '',
      onTap: () => onTalentTap(talent),
    );
  }
}
