import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

class ReviewsSection extends StatelessWidget {
  const ReviewsSection({
    required this.reviews,
    required this.reviewsCount,
    required this.averageRating,
    super.key,
  });

  final List<Map<String, dynamic>> reviews;
  final int reviewsCount;
  final double averageRating;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildHeader(),
        const SizedBox(height: BookmiSpacing.spaceSm),
        if (reviews.isEmpty) _buildEmptyState() else _buildReviewsList(),
      ],
    );
  }

  Widget _buildHeader() {
    return Row(
      children: [
        const Icon(
          Icons.star,
          size: 20,
          color: BookmiColors.warning,
        ),
        const SizedBox(width: 4),
        Text(
          averageRating.toStringAsFixed(1),
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
        const SizedBox(width: BookmiSpacing.spaceSm),
        Text(
          '($reviewsCount avis)',
          style: TextStyle(
            fontSize: 14,
            color: Colors.white.withValues(alpha: 0.6),
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyState() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: BookmiSpacing.spaceLg),
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.rate_review_outlined,
              size: 48,
              color: Colors.white.withValues(alpha: 0.3),
            ),
            const SizedBox(height: BookmiSpacing.spaceSm),
            Text(
              "Pas encore d'avis — Soyez le premier !",
              style: TextStyle(
                fontSize: 14,
                color: Colors.white.withValues(alpha: 0.6),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReviewsList() {
    return Column(
      children: reviews.map((review) {
        final reviewerName = review['reviewer_name'] as String? ?? 'Anonyme';
        final rating = (review['rating'] as num?)?.toDouble() ?? 0;
        final comment = review['comment'] as String? ?? '';
        final date = review['created_at'] as String? ?? '';
        final reply = review['reply'] as String?;
        final replyAt = review['reply_at'] as String?;

        return Padding(
          padding: const EdgeInsets.only(bottom: BookmiSpacing.spaceSm),
          child: GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        reviewerName,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                      ),
                    ),
                    _buildStars(rating),
                  ],
                ),
                if (date.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(
                    date,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.white.withValues(alpha: 0.5),
                    ),
                  ),
                ],
                if (comment.isNotEmpty) ...[
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  Text(
                    comment,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withValues(alpha: 0.8),
                    ),
                  ),
                ],
                if (reply != null && reply.isNotEmpty) ...[
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  Container(
                    padding: const EdgeInsets.all(BookmiSpacing.spaceSm),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFF6B35).withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: const Color(0xFFFF6B35).withValues(alpha: 0.25),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(
                              Icons.reply_rounded,
                              size: 12,
                              color: Color(0xFFFF6B35),
                            ),
                            const SizedBox(width: 4),
                            const Text(
                              "Réponse du talent",
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFFFF6B35),
                              ),
                            ),
                            if (replyAt != null) ...[
                              const Spacer(),
                              Text(
                                replyAt,
                                style: TextStyle(
                                  fontSize: 10,
                                  color: Colors.white.withValues(alpha: 0.4),
                                ),
                              ),
                            ],
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          reply,
                          style: TextStyle(
                            fontSize: 13,
                            color: Colors.white.withValues(alpha: 0.8),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildStars(double rating) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(5, (index) {
        final starValue = index + 1;
        if (rating >= starValue) {
          return const Icon(Icons.star, size: 14, color: BookmiColors.warning);
        } else if (rating >= starValue - 0.5) {
          return const Icon(
            Icons.star_half,
            size: 14,
            color: BookmiColors.warning,
          );
        }
        return Icon(
          Icons.star_border,
          size: 14,
          color: Colors.white.withValues(alpha: 0.3),
        );
      }),
    );
  }
}
