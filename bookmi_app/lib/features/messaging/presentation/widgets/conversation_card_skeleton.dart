import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

/// Shimmer placeholder matching the conversation list item layout.
class ConversationCardSkeleton extends StatelessWidget {
  const ConversationCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: BookmiColors.glassDarkMedium,
      highlightColor: BookmiColors.glassWhiteMedium,
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: BookmiSpacing.spaceBase,
          vertical: BookmiSpacing.spaceSm,
        ),
        child: Row(
          children: [
            // Avatar circle
            Container(
              width: 48,
              height: 48,
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white,
              ),
            ),
            const SizedBox(width: BookmiSpacing.spaceSm),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      _SkeletonBox(width: 120, height: 13),
                      const Spacer(),
                      _SkeletonBox(width: 40, height: 11),
                    ],
                  ),
                  const SizedBox(height: 6),
                  _SkeletonBox(width: double.infinity, height: 12),
                  const SizedBox(height: 4),
                  _SkeletonBox(width: 160, height: 12),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SkeletonBox extends StatelessWidget {
  const _SkeletonBox({required this.width, required this.height});

  final double width;
  final double height;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(4),
      ),
    );
  }
}
