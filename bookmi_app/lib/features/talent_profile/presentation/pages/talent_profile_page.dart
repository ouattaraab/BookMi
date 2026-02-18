import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/components/service_package_card.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/booking/booking.dart';
import 'package:bookmi_app/features/favorites/widgets/favorite_button.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_bloc.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_event.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_state.dart';
import 'package:bookmi_app/features/talent_profile/presentation/widgets/portfolio_gallery.dart';
import 'package:bookmi_app/features/talent_profile/presentation/widgets/reviews_section.dart';
import 'package:bookmi_app/features/talent_profile/presentation/widgets/similar_talents_row.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

class TalentProfilePage extends StatefulWidget {
  const TalentProfilePage({
    required this.slug,
    this.initialData,
    super.key,
  });

  final String slug;
  final Map<String, dynamic>? initialData;

  @override
  State<TalentProfilePage> createState() => _TalentProfilePageState();
}

class _TalentProfilePageState extends State<TalentProfilePage> {
  @override
  void initState() {
    super.initState();
    context.read<TalentProfileBloc>().add(
      TalentProfileFetched(slug: widget.slug),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: BookmiColors.gradientHero,
      ),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: BlocBuilder<TalentProfileBloc, TalentProfileState>(
          builder: (context, state) {
            return switch (state) {
              TalentProfileInitial() ||
              TalentProfileLoading() => _buildLoading(context),
              TalentProfileLoaded(:final profile, :final similarTalents) =>
                _buildContent(context, profile, similarTalents),
              TalentProfileFailure(:final message) => _buildError(
                context,
                message,
              ),
            };
          },
        ),
      ),
    );
  }

  Widget _buildLoading(BuildContext context) {
    final screenHeight = MediaQuery.of(context).size.height;
    return CustomScrollView(
      slivers: [
        SliverAppBar(
          expandedHeight: screenHeight * 0.6,
          pinned: true,
          backgroundColor: BookmiColors.brandNavy,
          foregroundColor: Colors.white,
          flexibleSpace: FlexibleSpaceBar(
            background: Container(color: BookmiColors.glassDarkMedium),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
            child: Column(
              children: List.generate(
                3,
                (_) => Padding(
                  padding: const EdgeInsets.only(
                    bottom: BookmiSpacing.spaceMd,
                  ),
                  child: GlassCard(
                    child: SizedBox(
                      height: 80,
                      child: Center(
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white.withValues(alpha: 0.3),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildContent(
    BuildContext context,
    Map<String, dynamic> profile,
    List<Map<String, dynamic>> similarTalents,
  ) {
    final screenHeight = MediaQuery.of(context).size.height;
    final stageName = profile['stage_name'] as String? ?? 'Talent';
    final photoUrl = widget.initialData?['photo_url'] as String?;
    final talentId =
        widget.initialData?['id'] as int? ?? profile['id'] as int? ?? 0;
    final category = profile['category'] as Map<String, dynamic>?;
    final categoryName = category?['name'] as String? ?? '';
    final categorySlug = category?['slug'] as String?;
    final city = profile['city'] as String? ?? '';
    final isVerified = profile['is_verified'] as bool? ?? false;
    final cachetAmount = profile['cachet_amount'] as int? ?? 0;
    final averageRating = double.tryParse('${profile['average_rating']}') ?? 0;
    final bio = profile['bio'] as String?;
    final talentLevel = profile['talent_level'] as String?;
    final reliabilityScore = profile['reliability_score'] as int?;
    final socialLinks = profile['social_links'] as Map<String, dynamic>?;
    final createdAt = profile['created_at'] as String?;
    final portfolioItems =
        (profile['portfolio_items'] as List<dynamic>?)
            ?.cast<Map<String, dynamic>>() ??
        [];
    final servicePackages =
        (profile['service_packages'] as List<dynamic>?)
            ?.cast<Map<String, dynamic>>() ??
        [];
    final recentReviews =
        (profile['recent_reviews'] as List<dynamic>?)
            ?.cast<Map<String, dynamic>>() ??
        [];
    final reviewsCount = profile['reviews_count'] as int? ?? 0;

    return Stack(
      children: [
        RefreshIndicator(
          color: BookmiColors.brandBlue,
          onRefresh: () async {
            context.read<TalentProfileBloc>().add(
              const TalentProfileRefreshed(),
            );
            await context.read<TalentProfileBloc>().stream.firstWhere(
              (s) => s is TalentProfileLoaded || s is TalentProfileFailure,
            );
          },
          child: CustomScrollView(
            slivers: [
              // Hero header
              SliverAppBar(
                expandedHeight: screenHeight * 0.6,
                pinned: true,
                backgroundColor: BookmiColors.brandNavy,
                foregroundColor: Colors.white,
                actions: [
                  Padding(
                    padding: const EdgeInsets.only(
                      right: BookmiSpacing.spaceBase,
                    ),
                    child: FavoriteButton(talentId: talentId),
                  ),
                ],
                flexibleSpace: FlexibleSpaceBar(
                  background: Stack(
                    fit: StackFit.expand,
                    children: [
                      // Photo hero
                      Hero(
                        tag: 'talent-$talentId',
                        child: photoUrl != null && photoUrl.isNotEmpty
                            ? CachedNetworkImage(
                                imageUrl: photoUrl,
                                fit: BoxFit.cover,
                                placeholder: (_, _) => _photoPlaceholder(),
                                errorWidget: (_, _, _) => _photoPlaceholder(),
                              )
                            : _photoPlaceholder(),
                      ),
                      // Gradient overlay bottom
                      const Positioned(
                        bottom: 0,
                        left: 0,
                        right: 0,
                        height: 160,
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [
                                Colors.transparent,
                                Color(0xCC1A2744),
                              ],
                            ),
                          ),
                        ),
                      ),
                      // Verified badge top-right
                      if (isVerified)
                        Positioned(
                          top:
                              MediaQuery.of(context).padding.top +
                              kToolbarHeight +
                              BookmiSpacing.spaceSm,
                          right: BookmiSpacing.spaceBase,
                          child: Container(
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
                        ),
                      // Name, category, city overlay
                      Positioned(
                        bottom: BookmiSpacing.spaceBase,
                        left: BookmiSpacing.spaceBase,
                        right: BookmiSpacing.spaceBase,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              stageName,
                              style: const TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.w600,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 4),
                            if (categoryName.isNotEmpty)
                              Text(
                                categoryName,
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                  color: BookmiColors.categoryColor(
                                    categorySlug,
                                  ),
                                ),
                              ),
                            if (city.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.location_on,
                                    size: 12,
                                    color: Colors.white.withValues(alpha: 0.7),
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    city,
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.white.withValues(
                                        alpha: 0.7,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                            if (reliabilityScore != null) ...[
                              const SizedBox(height: BookmiSpacing.spaceSm),
                              _buildReliabilityBar(reliabilityScore),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              // Content sections
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Bio section
                      GlassCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Bio
                            Text(
                              bio != null && bio.isNotEmpty
                                  ? bio
                                  : "Ce talent n'a pas encore renseigné sa bio",
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.white.withValues(
                                  alpha: bio != null && bio.isNotEmpty
                                      ? 0.9
                                      : 0.5,
                                ),
                                height: 1.5,
                              ),
                            ),
                            // Social links
                            if (socialLinks != null &&
                                socialLinks.isNotEmpty) ...[
                              const SizedBox(
                                height: BookmiSpacing.spaceMd,
                              ),
                              _buildSocialLinks(socialLinks),
                            ],
                            const SizedBox(height: BookmiSpacing.spaceMd),
                            // Talent level & member since
                            Row(
                              children: [
                                if (talentLevel != null)
                                  _buildLevelChip(talentLevel),
                                const Spacer(),
                                if (createdAt != null)
                                  Text(
                                    _formatMemberSince(createdAt),
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.white.withValues(
                                        alpha: 0.5,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: BookmiSpacing.spaceMd),

                      // Portfolio section
                      GlassCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Portfolio',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: BookmiSpacing.spaceSm),
                            PortfolioGallery(items: portfolioItems),
                          ],
                        ),
                      ),
                      const SizedBox(height: BookmiSpacing.spaceMd),

                      // Packages section
                      if (servicePackages.isNotEmpty) ...[
                        const Padding(
                          padding: EdgeInsets.only(
                            bottom: BookmiSpacing.spaceSm,
                          ),
                          child: Text(
                            'Packages',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        ...servicePackages.map((pkg) {
                          final attrs =
                              pkg['attributes'] as Map<String, dynamic>? ?? pkg;
                          final pkgType = attrs['type'] as String? ?? '';
                          return Padding(
                            padding: const EdgeInsets.only(
                              bottom: BookmiSpacing.spaceMd,
                            ),
                            child: ServicePackageCard(
                              name: attrs['name'] as String? ?? '',
                              description: attrs['description'] as String?,
                              cachetAmount: attrs['cachet_amount'] as int? ?? 0,
                              durationMinutes:
                                  attrs['duration_minutes'] as int?,
                              inclusions:
                                  (attrs['inclusions'] as List<dynamic>?)
                                      ?.cast<String>(),
                              type: pkgType,
                              isRecommended: pkgType == 'premium',
                            ),
                          );
                        }),
                      ],

                      // Reviews section
                      GlassCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Avis',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: BookmiSpacing.spaceSm),
                            ReviewsSection(
                              reviews: recentReviews,
                              reviewsCount: reviewsCount,
                              averageRating: averageRating,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: BookmiSpacing.spaceMd),

                      // Similar talents
                      SimilarTalentsRow(
                        talents: similarTalents,
                        onTalentTap: _onSimilarTalentTap,
                      ),

                      // Bottom spacing for CTA button
                      const SizedBox(height: 80),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
        // CTA Button sticky bottom
        Positioned(
          bottom: 0,
          left: 0,
          right: 0,
          child: Container(
            padding: EdgeInsets.only(
              left: BookmiSpacing.spaceBase,
              right: BookmiSpacing.spaceBase,
              bottom:
                  MediaQuery.of(context).padding.bottom + BookmiSpacing.spaceSm,
              top: BookmiSpacing.spaceSm,
            ),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.transparent,
                  Color(0xCC1A2744),
                ],
              ),
            ),
            child: Container(
              height: 56,
              decoration: BoxDecoration(
                gradient: BookmiColors.gradientCta,
                borderRadius: BorderRadius.circular(BookmiRadius.button),
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  borderRadius: BorderRadius.circular(BookmiRadius.button),
                  onTap: () => BookingFlowSheet.show(
                    context,
                    repository: context.read<BookingRepository>(),
                    talentProfileId: talentId,
                    talentStageName: stageName,
                    servicePackages: servicePackages,
                    enableExpress:
                        profile['enable_express_booking'] as bool? ?? false,
                  ),
                  child: Center(
                    child: Text(
                      'Réserver · ${TalentCard.formatCachet(cachetAmount)}',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildError(BuildContext context, String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(BookmiSpacing.spaceXl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.error_outline,
              size: 64,
              color: Colors.white.withValues(alpha: 0.3),
            ),
            const SizedBox(height: BookmiSpacing.spaceBase),
            Text(
              message,
              style: const TextStyle(
                fontSize: 16,
                color: Colors.white,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: BookmiSpacing.spaceLg),
            ElevatedButton(
              onPressed: () => context.read<TalentProfileBloc>().add(
                TalentProfileFetched(slug: widget.slug),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: BookmiColors.brandBlue,
                foregroundColor: Colors.white,
              ),
              child: const Text('Réessayer'),
            ),
          ],
        ),
      ),
    );
  }

  void _onSimilarTalentTap(Map<String, dynamic> talent) {
    final attrs = talent['attributes'] as Map<String, dynamic>? ?? talent;
    final slug = attrs['slug'] as String? ?? '';
    if (slug.isNotEmpty) {
      unawaited(
        context.pushNamed(
          RouteNames.talentDetail,
          pathParameters: {'slug': slug},
          extra: attrs,
        ),
      );
    }
  }

  static Widget _photoPlaceholder() {
    return const ColoredBox(
      color: BookmiColors.glassDarkMedium,
      child: Center(
        child: Icon(
          Icons.person,
          color: Colors.white38,
          size: 64,
        ),
      ),
    );
  }

  static Widget _buildReliabilityBar(int score) {
    return Row(
      children: [
        Text(
          'Fiabilité',
          style: TextStyle(
            fontSize: 11,
            color: Colors.white.withValues(alpha: 0.6),
          ),
        ),
        const SizedBox(width: BookmiSpacing.spaceSm),
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: score / 100,
              backgroundColor: Colors.white.withValues(alpha: 0.15),
              valueColor: AlwaysStoppedAnimation(
                score >= 70
                    ? BookmiColors.success
                    : score >= 40
                    ? BookmiColors.warning
                    : BookmiColors.error,
              ),
              minHeight: 4,
            ),
          ),
        ),
        const SizedBox(width: BookmiSpacing.spaceSm),
        Text(
          '$score%',
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w500,
            color: Colors.white.withValues(alpha: 0.6),
          ),
        ),
      ],
    );
  }

  Widget _buildSocialLinks(Map<String, dynamic> links) {
    return Row(
      children: links.entries.map((entry) {
        final icon = switch (entry.key) {
          'instagram' => Icons.camera_alt,
          'facebook' => Icons.facebook,
          'twitter' || 'x' => Icons.alternate_email,
          'youtube' => Icons.play_circle_outline,
          'tiktok' => Icons.music_note,
          _ => Icons.link,
        };
        return Padding(
          padding: const EdgeInsets.only(right: BookmiSpacing.spaceSm),
          child: IconButton(
            icon: Icon(icon, size: 20),
            color: BookmiColors.brandBlueLight,
            constraints: const BoxConstraints(
              minWidth: 48,
              minHeight: 48,
            ),
            onPressed: () {},
          ),
        );
      }).toList(),
    );
  }

  Widget _buildLevelChip(String level) {
    final label = switch (level) {
      'nouveau' => 'Nouveau',
      'confirme' => 'Confirmé',
      'populaire' => 'Populaire',
      'elite' => 'Élite',
      _ => level,
    };
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: BookmiSpacing.spaceSm,
        vertical: 4,
      ),
      decoration: BoxDecoration(
        color: BookmiColors.glassWhiteMedium,
        borderRadius: BorderRadius.circular(BookmiRadius.chip),
        border: Border.all(color: BookmiColors.glassBorder),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w500,
          color: Colors.white,
        ),
      ),
    );
  }

  String _formatMemberSince(String isoDate) {
    final date = DateTime.tryParse(isoDate);
    if (date == null) return '';
    const months = [
      '',
      'janvier',
      'février',
      'mars',
      'avril',
      'mai',
      'juin',
      'juillet',
      'août',
      'septembre',
      'octobre',
      'novembre',
      'décembre',
    ];
    return 'Membre depuis ${months[date.month]} ${date.year}';
  }
}
