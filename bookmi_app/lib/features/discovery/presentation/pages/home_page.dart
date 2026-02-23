import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_bloc.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_event.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_state.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

// ── Design tokens ────────────────────────────────────────────────
const _primary = Color(0xFF3B9DF2);
const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _border = Color(0xFFE2E8F0);
const _warning = Color(0xFFFBBF24);

// ── Helpers ───────────────────────────────────────────────────────
Color _hexColor(String hex) {
  try {
    final cleaned = hex.replaceAll('#', '');
    return Color(int.parse('FF$cleaned', radix: 16));
  } catch (_) {
    return const Color(0xFF7C4DFF);
  }
}

IconData _iconForSlug(String slug) {
  return switch (slug) {
    'musicien' || 'chanteur' => Icons.music_note,
    'danseur' => Icons.directions_run,
    'photographe' => Icons.camera_alt,
    'dj' || 'groupe-musical' => Icons.headphones,
    'humoriste' || 'humoriste-comedien' => Icons.sentiment_very_satisfied,
    'decorateur' => Icons.auto_fix_high,
    'animateur' || 'mc-animateur' => Icons.mic,
    _ => Icons.star,
  };
}

String _formatCachet(int amount) {
  // cachet_amount is stored in FCFA directly (no centimes conversion needed).
  return NumberFormat('#,###', 'fr_FR')
          .format(amount)
          .replaceAll(RegExp(r'[\s\u00A0\u202F,]'), '\u202F') +
      ' FCFA';
}

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  /// Stores the selected category ID as a string (e.g. '3'), or '' for "Tout".
  String _selectedCategory = '';
  final _searchController = TextEditingController();
  Timer? _searchDebounce;

  @override
  void initState() {
    super.initState();
    context.read<DiscoveryBloc>().add(const DiscoveryFetched());
    _searchController.addListener(_onSearchChanged);
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _searchController
      ..removeListener(_onSearchChanged)
      ..dispose();
    super.dispose();
  }

  void _onSearchChanged() {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 400), () {
      if (!mounted) return;
      context.read<DiscoveryBloc>().add(
        DiscoverySearchChanged(query: _searchController.text.trim()),
      );
    });
  }

  void _onCategoryTap(String key) {
    setState(() => _selectedCategory = key);
    final bloc = context.read<DiscoveryBloc>();
    final currentState = bloc.state;
    // Preserve any active search query when changing category.
    final currentQuery =
        currentState is DiscoveryLoaded
            ? currentState.activeFilters['q'] as String? ?? ''
            : '';

    if (key.isEmpty) {
      if (currentQuery.isNotEmpty) {
        bloc.add(DiscoveryFiltersChanged(filters: {'q': currentQuery}));
      } else {
        bloc.add(const DiscoveryFilterCleared());
      }
    } else {
      final filters = <String, dynamic>{
        'category_id': int.tryParse(key) ?? key,
        if (currentQuery.isNotEmpty) 'q': currentQuery,
      };
      bloc.add(DiscoveryFiltersChanged(filters: filters));
    }
  }

  void _onTalentTap(Map<String, dynamic> talent) {
    final attrs = talent['attributes'] as Map<String, dynamic>? ?? talent;
    final slug = attrs['slug'] as String? ?? '';
    if (slug.isNotEmpty) {
      context.pushNamed(
        RouteNames.talentDetail,
        pathParameters: {'slug': slug},
        extra: {...attrs, 'id': talent['id']},
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _muted,
      body: NestedScrollView(
        headerSliverBuilder: (context, innerBoxIsScrolled) => [
          SliverToBoxAdapter(
            child: _HomeHeader(searchController: _searchController),
          ),
          SliverToBoxAdapter(
            child: BlocBuilder<DiscoveryBloc, DiscoveryState>(
              buildWhen: (prev, curr) {
                final prevCats = prev is DiscoveryLoaded
                    ? prev.categories
                    : const <Map<String, dynamic>>[];
                final currCats = curr is DiscoveryLoaded
                    ? curr.categories
                    : const <Map<String, dynamic>>[];
                return prevCats != currCats;
              },
              builder: (context, state) {
                final categories =
                    state is DiscoveryLoaded
                        ? state.categories
                        : <Map<String, dynamic>>[];
                return _CategoryBar(
                  categories: categories,
                  selected: _selectedCategory,
                  onTap: _onCategoryTap,
                );
              },
            ),
          ),
        ],
        body: RefreshIndicator(
          color: _primary,
          onRefresh: () async {
            context.read<DiscoveryBloc>().add(const DiscoveryFetched());
            await context.read<DiscoveryBloc>().stream.firstWhere(
              (s) => s is DiscoveryLoaded || s is DiscoveryFailure,
            );
          },
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            child: BlocBuilder<DiscoveryBloc, DiscoveryState>(
              builder: (context, state) {
                final talents =
                    state is DiscoveryLoaded
                        ? state.talents
                        : <Map<String, dynamic>>[];
                final categories =
                    state is DiscoveryLoaded
                        ? state.categories
                        : <Map<String, dynamic>>[];
                final isLoading =
                    state is DiscoveryLoading || state is DiscoveryInitial;

                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _FeaturedSection(
                      talents: talents,
                      isLoading: isLoading,
                      onTalentTap: _onTalentTap,
                    ),
                    const SizedBox(height: 24),
                    _PopularCategoriesSection(
                      categories: categories,
                      onCategoryTap: _onCategoryTap,
                    ),
                    const SizedBox(height: 24),
                    _NearbySection(
                      talents: talents,
                      isLoading: isLoading,
                      onTalentTap: _onTalentTap,
                    ),
                    const SizedBox(height: 100),
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}

// ── Header ────────────────────────────────────────────────────────
class _HomeHeader extends StatelessWidget {
  const _HomeHeader({required this.searchController});
  final TextEditingController searchController;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, authState) {
        final user = authState is AuthAuthenticated ? authState.user : null;
        final firstName = user?.firstName ?? 'Utilisateur';
        final initials =
            user != null
                ? '${user.firstName[0]}${user.lastName.isNotEmpty ? user.lastName[0] : ''}'
                    .toUpperCase()
                : 'U';

        return Container(
          color: _secondary,
          padding: EdgeInsets.only(
            top: MediaQuery.of(context).padding.top + 12,
            left: 16,
            right: 16,
            bottom: 16,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  RichText(
                    text: TextSpan(
                      children: [
                        TextSpan(
                          text: 'Book',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                          ),
                        ),
                        TextSpan(
                          text: 'Mi',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            color: _primary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Spacer(),
                  Stack(
                    clipBehavior: Clip.none,
                    children: [
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.notifications_outlined,
                          color: Colors.white,
                          size: 22,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(width: 10),
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [_primary, Color(0xFF1565C0)],
                      ),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Center(
                      child: Text(
                        initials,
                        style: GoogleFonts.manrope(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Text(
                'Bonjour, $firstName',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 14,
                  color: Colors.white.withValues(alpha: 0.7),
                ),
              ),
              const SizedBox(height: 2),
              Text(
                'Trouvez le talent parfait',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 14),
              Container(
                height: 48,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    const SizedBox(width: 14),
                    const Icon(Icons.search, color: _mutedFg, size: 20),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: searchController,
                        style: GoogleFonts.manrope(
                          fontSize: 14,
                          color: _secondary,
                        ),
                        decoration: InputDecoration(
                          hintText: 'Rechercher un talent...',
                          hintStyle: GoogleFonts.manrope(
                            fontSize: 14,
                            color: _mutedFg,
                          ),
                          border: InputBorder.none,
                          isDense: true,
                          contentPadding: EdgeInsets.zero,
                        ),
                      ),
                    ),
                    Container(
                      width: 40,
                      height: 36,
                      margin: const EdgeInsets.only(right: 6),
                      decoration: BoxDecoration(
                        color: _primary.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(
                        Icons.tune_rounded,
                        color: _primary,
                        size: 18,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

// ── Category bar ─────────────────────────────────────────────────
class _CategoryBar extends StatelessWidget {
  const _CategoryBar({
    required this.categories,
    required this.selected,
    required this.onTap,
  });

  final List<Map<String, dynamic>> categories;
  final String selected;
  final ValueChanged<String> onTap;

  @override
  Widget build(BuildContext context) {
    // Prepend "Tout" entry, then one chip per API category.
    // Key is the category ID (as string) so the filter sends category_id (int).
    final items = <(String key, String label)>[
      ('', 'Tout'),
      ...categories.map((c) => (
        (c['id'] as int?)?.toString() ?? c['slug'] as String? ?? '',
        c['name'] as String? ?? '',
      )),
    ];

    return Container(
      color: _secondary,
      padding: const EdgeInsets.only(bottom: 12),
      child: SizedBox(
        height: 40,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: items.length,
          separatorBuilder: (_, __) => const SizedBox(width: 8),
          itemBuilder: (context, i) {
            final (key, label) = items[i];
            final isActive = key == selected;
            return GestureDetector(
              onTap: () => onTap(key),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color:
                      isActive
                          ? _primary
                          : Colors.white.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color:
                        isActive
                            ? _primary
                            : Colors.white.withValues(alpha: 0.15),
                  ),
                ),
                child: Text(
                  label,
                  style: GoogleFonts.manrope(
                    fontSize: 13,
                    fontWeight:
                        isActive ? FontWeight.w600 : FontWeight.w400,
                    color:
                        isActive
                            ? Colors.white
                            : Colors.white.withValues(alpha: 0.7),
                  ),
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

// ── Featured section ──────────────────────────────────────────────
class _FeaturedSection extends StatelessWidget {
  const _FeaturedSection({
    required this.talents,
    required this.isLoading,
    required this.onTalentTap,
  });

  final List<Map<String, dynamic>> talents;
  final bool isLoading;
  final ValueChanged<Map<String, dynamic>> onTalentTap;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 12),
          child: Row(
            children: [
              Text(
                'Talents en vedette',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 17,
                  fontWeight: FontWeight.w700,
                  color: _secondary,
                ),
              ),
              const Spacer(),
              Text(
                'Voir tout',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  color: _primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
        SizedBox(
          height: 260,
          child:
              isLoading
                  ? _buildSkeletonList()
                  : talents.isEmpty
                  ? _buildEmpty()
                  : ListView.separated(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: talents.take(10).length,
                    separatorBuilder: (_, __) => const SizedBox(width: 12),
                    itemBuilder: (context, i) {
                      final talent = talents[i];
                      final attrs =
                          talent['attributes'] as Map<String, dynamic>? ??
                          talent;
                      return _FeaturedTalentCard(
                        talent: talent,
                        attrs: attrs,
                        onTap: () => onTalentTap(talent),
                      );
                    },
                  ),
        ),
      ],
    );
  }

  Widget _buildSkeletonList() {
    return ListView.separated(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.symmetric(horizontal: 16),
      itemCount: 4,
      separatorBuilder: (_, __) => const SizedBox(width: 12),
      itemBuilder:
          (_, __) => Container(
            width: 200,
            decoration: BoxDecoration(
              color: _border,
              borderRadius: BorderRadius.circular(16),
            ),
          ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Text(
        'Aucun talent disponible',
        style: GoogleFonts.manrope(color: _mutedFg),
      ),
    );
  }
}

class _FeaturedTalentCard extends StatelessWidget {
  const _FeaturedTalentCard({
    required this.talent,
    required this.attrs,
    required this.onTap,
  });

  final Map<String, dynamic> talent;
  final Map<String, dynamic> attrs;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final stageName = attrs['stage_name'] as String? ?? 'Talent';
    final photoUrl = attrs['photo_url'] as String? ?? '';
    final cachetAmount = attrs['cachet_amount'] as int? ?? 0;
    final averageRating =
        double.tryParse('${attrs['average_rating']}') ?? 0.0;
    final isVerified = attrs['is_verified'] as bool? ?? false;
    final category = attrs['category'] as Map<String, dynamic>?;
    final categoryName = category?['name'] as String? ?? '';
    final city = attrs['city'] as String? ?? '';

    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 200,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.06),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(16),
                topRight: Radius.circular(16),
              ),
              child: SizedBox(
                height: 140,
                width: double.infinity,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    photoUrl.isNotEmpty
                        ? CachedNetworkImage(
                          imageUrl: photoUrl,
                          fit: BoxFit.cover,
                          placeholder:
                              (_, __) => Container(color: _border),
                          errorWidget:
                              (_, __, ___) => Container(
                                color: _border,
                                child: const Icon(
                                  Icons.person,
                                  color: _mutedFg,
                                  size: 40,
                                ),
                              ),
                        )
                        : Container(
                          color: _border,
                          child: const Icon(
                            Icons.person,
                            color: _mutedFg,
                            size: 40,
                          ),
                        ),
                    Positioned(
                      top: 8,
                      left: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.55),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.star,
                              size: 11,
                              color: _warning,
                            ),
                            const SizedBox(width: 3),
                            Text(
                              averageRating.toStringAsFixed(1),
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Positioned(
                      top: 8,
                      right: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: _primary,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          _formatCachet(cachetAmount),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          stageName,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: _secondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (isVerified) ...[
                        const SizedBox(width: 4),
                        const Icon(
                          Icons.verified,
                          size: 14,
                          color: _primary,
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 2),
                  if (categoryName.isNotEmpty)
                    Text(
                      categoryName,
                      style: GoogleFonts.manrope(
                        fontSize: 11,
                        color: _mutedFg,
                      ),
                    ),
                  if (city.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        const Icon(
                          Icons.location_on_outlined,
                          size: 11,
                          color: _mutedFg,
                        ),
                        const SizedBox(width: 2),
                        Text(
                          city,
                          style: GoogleFonts.manrope(
                            fontSize: 11,
                            color: _mutedFg,
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Popular categories section ────────────────────────────────────
class _PopularCategoriesSection extends StatelessWidget {
  const _PopularCategoriesSection({
    required this.categories,
    required this.onCategoryTap,
  });

  final List<Map<String, dynamic>> categories;
  final ValueChanged<String> onCategoryTap;

  @override
  Widget build(BuildContext context) {
    if (categories.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          child: Text(
            'Catégories populaires',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: _secondary,
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
              childAspectRatio: 1.4,
            ),
            itemCount: categories.length,
            itemBuilder: (context, i) {
              final cat = categories[i];
              final slug = cat['slug'] as String? ?? '';
              final name = cat['name'] as String? ?? '';
              final color = _hexColor(cat['color_hex'] as String? ?? '');
              final icon = _iconForSlug(slug);

              return GestureDetector(
                onTap: () => onCategoryTap(
                  (cat['id'] as int?)?.toString() ?? slug,
                ),
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    boxShadow: [
                      BoxShadow(
                        color: color.withValues(alpha: 0.08),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(icon, color: color, size: 18),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        name,
                        style: GoogleFonts.manrope(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: _secondary,
                        ),
                        textAlign: TextAlign.center,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}

// ── Nearby section ────────────────────────────────────────────────
class _NearbySection extends StatelessWidget {
  const _NearbySection({
    required this.talents,
    required this.isLoading,
    required this.onTalentTap,
  });

  final List<Map<String, dynamic>> talents;
  final bool isLoading;
  final ValueChanged<Map<String, dynamic>> onTalentTap;

  @override
  Widget build(BuildContext context) {
    final nearbyTalents = talents.skip(4).take(6).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          child: Row(
            children: [
              Text(
                'Près de vous',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 17,
                  fontWeight: FontWeight.w700,
                  color: _secondary,
                ),
              ),
              const Spacer(),
              Text(
                'Voir tout',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  color: _primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
        if (isLoading)
          ...List.generate(
            3,
            (_) => Container(
              margin: const EdgeInsets.fromLTRB(16, 0, 16, 10),
              height: 90,
              decoration: BoxDecoration(
                color: _border,
                borderRadius: BorderRadius.circular(14),
              ),
            ),
          )
        else if (nearbyTalents.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              'Aucun talent près de vous',
              style: GoogleFonts.manrope(color: _mutedFg),
            ),
          )
        else
          ...nearbyTalents.map((talent) {
            final attrs =
                talent['attributes'] as Map<String, dynamic>? ?? talent;
            return _NearbyTalentCard(
              talent: talent,
              attrs: attrs,
              onTap: () => onTalentTap(talent),
            );
          }),
      ],
    );
  }
}

class _NearbyTalentCard extends StatelessWidget {
  const _NearbyTalentCard({
    required this.talent,
    required this.attrs,
    required this.onTap,
  });

  final Map<String, dynamic> talent;
  final Map<String, dynamic> attrs;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final stageName = attrs['stage_name'] as String? ?? 'Talent';
    final photoUrl = attrs['photo_url'] as String? ?? '';
    final cachetAmount = attrs['cachet_amount'] as int? ?? 0;
    final averageRating =
        double.tryParse('${attrs['average_rating']}') ?? 0.0;
    final isVerified = attrs['is_verified'] as bool? ?? false;
    final category = attrs['category'] as Map<String, dynamic>?;
    final categoryName = category?['name'] as String? ?? '';
    final city = attrs['city'] as String? ?? '';

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.fromLTRB(16, 0, 16, 10),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: SizedBox(
                width: 64,
                height: 64,
                child:
                    photoUrl.isNotEmpty
                        ? CachedNetworkImage(
                          imageUrl: photoUrl,
                          fit: BoxFit.cover,
                          placeholder:
                              (_, __) => Container(color: _border),
                          errorWidget:
                              (_, __, ___) => Container(
                                color: _border,
                                child: const Icon(
                                  Icons.person,
                                  color: _mutedFg,
                                ),
                              ),
                        )
                        : Container(
                          color: _border,
                          child: const Icon(Icons.person, color: _mutedFg),
                        ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(
                        stageName,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: _secondary,
                        ),
                      ),
                      if (isVerified) ...[
                        const SizedBox(width: 4),
                        const Icon(
                          Icons.verified,
                          size: 13,
                          color: _primary,
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 2),
                  if (categoryName.isNotEmpty)
                    Text(
                      categoryName,
                      style: GoogleFonts.manrope(
                        fontSize: 12,
                        color: _mutedFg,
                      ),
                    ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.star, size: 12, color: _warning),
                      const SizedBox(width: 3),
                      Text(
                        averageRating.toStringAsFixed(1),
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          color: _mutedFg,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      if (city.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        const Icon(
                          Icons.location_on_outlined,
                          size: 12,
                          color: _mutedFg,
                        ),
                        const SizedBox(width: 2),
                        Expanded(
                          child: Text(
                            city,
                            style: GoogleFonts.manrope(
                              fontSize: 12,
                              color: _mutedFg,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  _formatCachet(cachetAmount),
                  style: GoogleFonts.manrope(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: _primary,
                  ),
                ),
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: _primary,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    'Réserver',
                    style: GoogleFonts.manrope(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
