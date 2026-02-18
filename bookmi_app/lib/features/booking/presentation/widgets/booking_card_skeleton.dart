import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

/// Shimmer placeholder matching [BookingCard] layout.
class BookingCardSkeleton extends StatelessWidget {
  const BookingCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: BookmiColors.glassDarkMedium,
      highlightColor: BookmiColors.glassWhiteMedium,
      child: Container(
        padding: const EdgeInsets.all(BookmiSpacing.spaceSm),
        decoration: BoxDecoration(
          color: BookmiColors.glassDark,
          borderRadius: BookmiRadius.cardBorder,
          border: Border.all(
            color: BookmiColors.glassBorder,
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Icon circle skeleton
            Container(
              width: 40,
              height: 40,
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
                      _ShimmerBox(width: 120, height: 14),
                      const Spacer(),
                      _ShimmerBox(width: 60, height: 18, radius: 8),
                    ],
                  ),
                  const SizedBox(height: 6),
                  _ShimmerBox(width: 80, height: 11),
                  const SizedBox(height: BookmiSpacing.spaceXs),
                  _ShimmerBox(width: double.infinity, height: 11),
                  const SizedBox(height: BookmiSpacing.spaceXs),
                  _ShimmerBox(width: 100, height: 13),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ShimmerBox extends StatelessWidget {
  const _ShimmerBox({
    required this.width,
    required this.height,
    this.radius = 4,
  });

  final double width;
  final double height;
  final double radius;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(radius),
      ),
    );
  }
}
