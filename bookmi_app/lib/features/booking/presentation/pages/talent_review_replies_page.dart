import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:bookmi_app/features/evaluation/data/models/review_model.dart';
import 'package:bookmi_app/features/evaluation/data/repositories/review_repository.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// Page displaying all client→talent reviews for a booking, with an inline
/// reply form so the talent can respond publicly under each review.
class TalentReviewRepliesPage extends StatefulWidget {
  const TalentReviewRepliesPage({
    required this.bookingId,
    required this.clientName,
    required this.talentStageName,
    required this.reviewRepository,
    required this.bookingRepository,
    super.key,
  });

  final int bookingId;
  final String clientName;
  final String talentStageName;
  final ReviewRepository reviewRepository;
  final BookingRepository bookingRepository;

  @override
  State<TalentReviewRepliesPage> createState() =>
      _TalentReviewRepliesPageState();
}

class _TalentReviewRepliesPageState extends State<TalentReviewRepliesPage> {
  List<ReviewModel>? _reviews;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await widget.reviewRepository.getReviews(widget.bookingId);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _reviews = data.where((r) => r.type == 'client_to_talent').toList();
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          foregroundColor: Colors.white,
          title: const Text(
            'Avis clients',
            style: TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        body: _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFFFF6B35)),
      );
    }

    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(BookmiSpacing.spaceXl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.error_outline,
                color: Colors.white.withValues(alpha: 0.5),
                size: 48,
              ),
              const SizedBox(height: BookmiSpacing.spaceMd),
              Text(
                _error!,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.7),
                  fontSize: 14,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: BookmiSpacing.spaceLg),
              TextButton(
                onPressed: _load,
                child: const Text(
                  'Réessayer',
                  style: TextStyle(color: Color(0xFFFF6B35)),
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (_reviews == null || _reviews!.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.rate_review_outlined,
              size: 56,
              color: Colors.white.withValues(alpha: 0.3),
            ),
            const SizedBox(height: BookmiSpacing.spaceMd),
            Text(
              'Aucun avis client pour cette réservation.',
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.6),
                fontSize: 14,
              ),
            ),
          ],
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      itemCount: _reviews!.length,
      separatorBuilder: (_, __) =>
          const SizedBox(height: BookmiSpacing.spaceSm),
      itemBuilder: (context, index) {
        return _ReviewCard(
          review: _reviews![index],
          clientName: widget.clientName,
          talentStageName: widget.talentStageName,
          bookingRepository: widget.bookingRepository,
          onReplied: _load,
        );
      },
    );
  }
}

// ── Review card with inline reply ─────────────────────────────────────────────

class _ReviewCard extends StatefulWidget {
  const _ReviewCard({
    required this.review,
    required this.clientName,
    required this.talentStageName,
    required this.bookingRepository,
    required this.onReplied,
  });

  final ReviewModel review;
  final String clientName;
  final String talentStageName;
  final BookingRepository bookingRepository;
  final VoidCallback onReplied;

  @override
  State<_ReviewCard> createState() => _ReviewCardState();
}

class _ReviewCardState extends State<_ReviewCard> {
  final _controller = TextEditingController();
  bool _submitting = false;
  bool _showForm = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _submitting = true);
    final result = await widget.bookingRepository.replyToReview(
      reviewId: widget.review.id,
      reply: text,
    );
    if (!mounted) return;
    setState(() => _submitting = false);
    switch (result) {
      case ApiSuccess():
        widget.onReplied();
      case ApiFailure(:final message):
        messenger.showSnackBar(SnackBar(content: Text(message)));
    }
  }

  String _formatDate(String? iso) {
    if (iso == null) return '';
    try {
      return DateFormat('dd/MM/yyyy').format(DateTime.parse(iso).toLocal());
    } catch (_) {
      return iso;
    }
  }

  @override
  Widget build(BuildContext context) {
    final review = widget.review;
    final hasReply = review.reply != null && review.reply!.isNotEmpty;

    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Reviewer header ────────────────────────────────────────────────
          Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white.withValues(alpha: 0.08),
                ),
                child: const Icon(
                  Icons.person_rounded,
                  color: Colors.white54,
                  size: 20,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.clientName,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                    if (review.createdAt != null)
                      Text(
                        DateFormat(
                          'dd/MM/yyyy',
                        ).format(review.createdAt!.toLocal()),
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.white.withValues(alpha: 0.45),
                        ),
                      ),
                  ],
                ),
              ),
              _buildStars(review.rating.toDouble()),
            ],
          ),

          // ── Comment ───────────────────────────────────────────────────────
          if (review.comment != null && review.comment!.isNotEmpty) ...[
            const SizedBox(height: BookmiSpacing.spaceSm),
            Text(
              review.comment!,
              style: TextStyle(
                fontSize: 14,
                color: Colors.white.withValues(alpha: 0.82),
              ),
            ),
          ],

          const SizedBox(height: BookmiSpacing.spaceMd),
          Divider(color: Colors.white.withValues(alpha: 0.1), height: 1),
          const SizedBox(height: BookmiSpacing.spaceMd),

          // ── Reply section ──────────────────────────────────────────────────
          if (hasReply) ...[
            // Existing reply — talent name + text
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: const Color(0xFFFF6B35).withValues(alpha: 0.15),
                  ),
                  child: const Icon(
                    Icons.reply_rounded,
                    color: Color(0xFFFF6B35),
                    size: 16,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            widget.talentStageName,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFFFF6B35),
                            ),
                          ),
                          if (review.replyAt != null) ...[
                            const Spacer(),
                            Text(
                              _formatDate(review.replyAt),
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
                        review.reply!,
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.white.withValues(alpha: 0.8),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ] else ...[
            // No reply yet
            if (!_showForm)
              GestureDetector(
                onTap: () => setState(() => _showForm = true),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: const Color(0xFFFF6B35).withValues(alpha: 0.5),
                    ),
                    color: const Color(0xFFFF6B35).withValues(alpha: 0.06),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.reply_rounded,
                        color: Color(0xFFFF6B35),
                        size: 16,
                      ),
                      SizedBox(width: 6),
                      Text(
                        'Répondre à cet avis',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFFFF6B35),
                        ),
                      ),
                    ],
                  ),
                ),
              )
            else ...[
              TextField(
                controller: _controller,
                enabled: !_submitting,
                maxLines: 3,
                maxLength: 1000,
                autofocus: true,
                style: const TextStyle(color: Colors.white, fontSize: 13),
                decoration: InputDecoration(
                  hintText: 'Votre réponse…',
                  hintStyle: TextStyle(
                    color: Colors.white.withValues(alpha: 0.4),
                  ),
                  filled: true,
                  fillColor: Colors.white.withValues(alpha: 0.04),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: BorderSide(
                      color: Colors.white.withValues(alpha: 0.15),
                    ),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: BorderSide(
                      color: Colors.white.withValues(alpha: 0.15),
                    ),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: const BorderSide(color: Color(0xFFFF6B35)),
                  ),
                  counterStyle: TextStyle(
                    color: Colors.white.withValues(alpha: 0.4),
                    fontSize: 10,
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 10,
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: _submitting
                        ? null
                        : () => setState(() {
                            _showForm = false;
                            _controller.clear();
                          }),
                    child: Text(
                      'Annuler',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.5),
                        fontSize: 13,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  SizedBox(
                    height: 36,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFFF6B35),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 20),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      onPressed: _submitting ? null : _submit,
                      child: _submitting
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text(
                              'Publier',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ],
      ),
    );
  }

  Widget _buildStars(double rating) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(5, (i) {
        final sv = i + 1;
        if (rating >= sv) {
          return const Icon(
            Icons.star_rounded,
            size: 14,
            color: BookmiColors.warning,
          );
        } else if (rating >= sv - 0.5) {
          return const Icon(
            Icons.star_half_rounded,
            size: 14,
            color: BookmiColors.warning,
          );
        }
        return Icon(
          Icons.star_outline_rounded,
          size: 14,
          color: Colors.white.withValues(alpha: 0.3),
        );
      }),
    );
  }
}
