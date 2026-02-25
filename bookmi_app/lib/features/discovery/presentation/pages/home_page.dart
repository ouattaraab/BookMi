import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_bloc.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_event.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_state.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/components/talent_card_skeleton.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/features/discovery/data/repositories/discovery_repository.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:bookmi_app/features/profile/bloc/profile_bloc.dart';

// ── Design tokens (dark) ─────────────────────────────────────────
const _primary = Color(0xFF2196F3);
const _secondary = Colors.white;
const _mutedFg = Color(0xFF94A3B8);
const _border = Color(0x1AFFFFFF);
const _warning = Color(0xFFFBBF24);

String _emojiForSlug(String slug) {
  return switch (slug) {
    'musicien' => '🎸',
    'chanteur' => '🎤',
    'danseur' => '💃',
    'photographe' => '📸',
    'dj' || 'groupe-musical' => '🎧',
    'humoriste' || 'humoriste-comedien' => '🎭',
    'decorateur' => '✨',
    'animateur' || 'mc-animateur' => '🎙️',
    _ => '🎵',
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

  @override
  void initState() {
    super.initState();
    context.read<DiscoveryBloc>().add(const DiscoveryFetched());
  }

  void _onHeroSearch(String? query, DateTime? eventDate) {
    context.read<DiscoveryBloc>().add(
      DiscoveryDateSearchRequested(query: query, eventDate: eventDate),
    );
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
      backgroundColor: Colors.transparent,
      body: NestedScrollView(
        headerSliverBuilder: (context, innerBoxIsScrolled) => [
          SliverToBoxAdapter(
            child: _HomeHeader(onSearch: _onHeroSearch),
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
                final eventDate =
                    state is DiscoveryLoaded ? state.eventDate : null;

                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    BlocBuilder<ProfileBloc, ProfileState>(
                      builder: (context, profileState) {
                        if (profileState is! ProfileLoaded) {
                          return const SizedBox.shrink();
                        }
                        final pending =
                            profileState.stats.pendingBookingCount;
                        if (!profileState.stats.isTalent || pending == 0) {
                          return const SizedBox.shrink();
                        }
                        return GestureDetector(
                          onTap: () => context.go('/bookings'),
                          child: Container(
                            margin: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 12,
                            ),
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                colors: [
                                  Color(0xFF2196F3),
                                  Color(0xFF64B5F6),
                                ],
                              ),
                              borderRadius: BorderRadius.circular(14),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(0xFF2196F3)
                                      .withValues(alpha: 0.35),
                                  blurRadius: 12,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 36,
                                  height: 36,
                                  decoration: BoxDecoration(
                                    color: Colors.white
                                        .withValues(alpha: 0.2),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: const Icon(
                                    Icons.notifications_active,
                                    color: Colors.white,
                                    size: 20,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        "$pending réservation${pending > 1 ? 's' : ''} en attente",
                                        style: GoogleFonts.plusJakartaSans(
                                          fontSize: 14,
                                          fontWeight: FontWeight.w700,
                                          color: Colors.white,
                                        ),
                                      ),
                                      Text(
                                        'Accepter ou refuser maintenant',
                                        style: GoogleFonts.manrope(
                                          fontSize: 12,
                                          color: Colors.white
                                              .withValues(alpha: 0.85),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const Icon(
                                  Icons.chevron_right,
                                  color: Colors.white,
                                  size: 20,
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
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
                      eventDate: eventDate,
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
  const _HomeHeader({required this.onSearch});
  final void Function(String? query, DateTime? eventDate) onSearch;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, authState) {
        final user = authState is AuthAuthenticated ? authState.user : null;
        final firstName = user?.firstName ?? 'Utilisateur';

        return Container(
          color: Colors.transparent,
          padding: const EdgeInsets.only(
            left: 20,
            right: 20,
            bottom: 20,
            top: 16,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${DateTime.now().hour < 12 ? 'Bonjour' : 'Bonsoir'}, $firstName 👋',
                        style: GoogleFonts.nunito(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Colors.white.withValues(alpha: 0.45),
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Découvrir',
                        style: GoogleFonts.nunito(
                          fontSize: 22,
                          fontWeight: FontWeight.w900,
                          color: Colors.white,
                          letterSpacing: -0.5,
                        ),
                      ),
                    ],
                  ),
                  const Spacer(),
                  BlocBuilder<ProfileBloc, ProfileState>(
                    builder: (context, profileState) {
                      final unread = profileState is ProfileLoaded
                          ? profileState.stats.unreadNotificationCount
                          : 0;
                      return GestureDetector(
                        onTap: () => context.pushNamed(RouteNames.notifications),
                        child: Stack(
                          clipBehavior: Clip.none,
                          children: [
                            Container(
                              width: 40,
                              height: 40,
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.08),
                                border: Border.all(
                                  color: Colors.white.withValues(alpha: 0.12),
                                ),
                                borderRadius: BorderRadius.circular(14),
                              ),
                              child: const Icon(
                                Icons.notifications_outlined,
                                color: Colors.white,
                                size: 20,
                              ),
                            ),
                            if (unread > 0)
                              Positioned(
                                top: -3,
                                right: -3,
                                child: Container(
                                  width: 10,
                                  height: 10,
                                  decoration: BoxDecoration(
                                    color: _primary,
                                    shape: BoxShape.circle,
                                    border: Border.all(
                                      color: const Color(0xFF0D1421),
                                      width: 2,
                                    ),
                                    boxShadow: [
                                      BoxShadow(
                                        color: _primary.withValues(alpha: 0.8),
                                        blurRadius: 6,
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                          ],
                        ),
                      );
                    },
                  ),
                ],
              ),
              const SizedBox(height: 14),
              _HeroSearchBar(onSearch: onSearch),
            ],
          ),
        );
      },
    );
  }
}

// ── Hero Search Bar ───────────────────────────────────────────────
class _HeroSearchBar extends StatefulWidget {
  const _HeroSearchBar({required this.onSearch});
  final void Function(String? query, DateTime? eventDate) onSearch;

  @override
  State<_HeroSearchBar> createState() => _HeroSearchBarState();
}

class _HeroSearchBarState extends State<_HeroSearchBar> {
  final _queryController = TextEditingController();
  DateTime? _selectedDate;

  static const _quickLinks = [
    ('dj', 'DJ', '🎧'),
    ('chanteur', 'Chanteur', '🎤'),
    ('groupe-musical', 'Groupe', '🎸'),
    ('danseur', 'Danseur', '💃'),
    ('photographe', 'Photo', '📸'),
  ];

  @override
  void dispose() {
    _queryController.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      locale: const Locale('fr'),
      initialDate: _selectedDate ?? now.add(const Duration(days: 1)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: const ColorScheme.dark(
            primary: Color(0xFF2196F3),
            onPrimary: Colors.white,
            surface: Color(0xFF0D1421),
            onSurface: Colors.white,
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) setState(() => _selectedDate = picked);
  }

  @override
  Widget build(BuildContext context) {
    final dateLabel = _selectedDate != null
        ? DateFormat('d MMM yyyy', 'fr').format(_selectedDate!)
        : null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // ── Search card ───────────────────────────────────────────
        Container(
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.07),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
          ),
          child: Column(
            children: [
              // Talent field
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 10),
                child: Row(
                  children: [
                    Icon(
                      Icons.mic_none_outlined,
                      size: 16,
                      color: Colors.white.withValues(alpha: 0.4),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: _queryController,
                        style: GoogleFonts.nunito(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                        decoration: InputDecoration(
                          hintText: 'Quel talent recherchez-vous ?',
                          hintStyle: GoogleFonts.nunito(
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                            color: Colors.white.withValues(alpha: 0.35),
                          ),
                          border: InputBorder.none,
                          enabledBorder: InputBorder.none,
                          focusedBorder: InputBorder.none,
                          filled: true,
                          fillColor: Colors.transparent,
                          isDense: true,
                          contentPadding: EdgeInsets.zero,
                        ),
                      ),
                    ),
                    if (_queryController.text.isNotEmpty)
                      GestureDetector(
                        onTap: () => setState(() => _queryController.clear()),
                        child: Icon(
                          Icons.close,
                          size: 14,
                          color: Colors.white.withValues(alpha: 0.5),
                        ),
                      ),
                  ],
                ),
              ),
              Divider(
                height: 1,
                thickness: 1,
                color: Colors.white.withValues(alpha: 0.07),
              ),
              // Date + search button row
              Row(
                children: [
                  Expanded(
                    child: GestureDetector(
                      onTap: _pickDate,
                      behavior: HitTestBehavior.opaque,
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(14, 10, 8, 10),
                        child: Row(
                          children: [
                            Icon(
                              Icons.calendar_today_outlined,
                              size: 15,
                              color: dateLabel != null
                                  ? const Color(0xFF64B5F6)
                                  : Colors.white.withValues(alpha: 0.4),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Text(
                                dateLabel ?? 'Pour quelle date ?',
                                style: GoogleFonts.nunito(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                  color: dateLabel != null
                                      ? Colors.white
                                      : Colors.white.withValues(alpha: 0.35),
                                ),
                              ),
                            ),
                            if (_selectedDate != null)
                              GestureDetector(
                                onTap: () =>
                                    setState(() => _selectedDate = null),
                                child: Icon(
                                  Icons.close,
                                  size: 13,
                                  color: Colors.white.withValues(alpha: 0.45),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  // Search button
                  Padding(
                    padding: const EdgeInsets.all(8),
                    child: GestureDetector(
                      onTap: () {
                        final q = _queryController.text.trim();
                        widget.onSearch(q.isNotEmpty ? q : null, _selectedDate);
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 10,
                        ),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [Color(0xFF2196F3), Color(0xFF64B5F6)],
                          ),
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF2196F3)
                                  .withValues(alpha: 0.4),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.search,
                              color: Colors.white,
                              size: 15,
                            ),
                            const SizedBox(width: 5),
                            Text(
                              'Chercher',
                              style: GoogleFonts.nunito(
                                fontSize: 12,
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 10),
        // ── Quick-link chips ──────────────────────────────────────
        SizedBox(
          height: 30,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: _quickLinks.length,
            separatorBuilder: (_, __) => const SizedBox(width: 8),
            itemBuilder: (context, i) {
              final (slug, label, emoji) = _quickLinks[i];
              final isActive = _queryController.text.toLowerCase() == label.toLowerCase() ||
                  _queryController.text.toLowerCase() == slug;
              return GestureDetector(
                onTap: () => setState(() => _queryController.text = label),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: isActive
                        ? const Color(0xFF2196F3).withValues(alpha: 0.2)
                        : Colors.white.withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(100),
                    border: Border.all(
                      color: isActive
                          ? const Color(0xFF64B5F6).withValues(alpha: 0.5)
                          : Colors.white.withValues(alpha: 0.1),
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(emoji, style: const TextStyle(fontSize: 11)),
                      const SizedBox(width: 5),
                      Text(
                        label,
                        style: GoogleFonts.nunito(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: isActive
                              ? const Color(0xFF64B5F6)
                              : Colors.white.withValues(alpha: 0.7),
                        ),
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
      color: Colors.transparent,
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
                'Top Talents',
                style: GoogleFonts.nunito(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  color: _secondary,
                  letterSpacing: -0.3,
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
          height: 280,
          child: isLoading
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
                        final category =
                            attrs['category'] as Map<String, dynamic>?;
                        final categorySlug = category?['slug'] as String?;
                        return SizedBox(
                          // width = height × aspect-ratio (0.72) to match TalentGrid
                          width: 280 * 0.72,
                          child: TalentCard(
                            id: talent['id'] as int,
                            stageName:
                                attrs['stage_name'] as String? ?? 'Talent',
                            categoryName:
                                category?['name'] as String? ?? '',
                            categoryColor:
                                BookmiColors.categoryColor(categorySlug),
                            city: attrs['city'] as String? ?? '',
                            cachetAmount:
                                attrs['cachet_amount'] as int? ?? 0,
                            averageRating: double.tryParse(
                                    '${attrs['average_rating']}') ??
                                0.0,
                            isVerified:
                                attrs['is_verified'] as bool? ?? false,
                            photoUrl:
                                attrs['photo_url'] as String? ?? '',
                            onTap: () => onTalentTap(talent),
                          ),
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
      itemBuilder: (_, __) => SizedBox(
        width: 280 * 0.72,
        child: const TalentCardSkeleton(),
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
          child: Row(
            children: [
              Text(
                'Catégories',
                style: GoogleFonts.nunito(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                  color: _secondary,
                  letterSpacing: -0.3,
                ),
              ),
              const Spacer(),
              Text(
                'Voir tout',
                style: GoogleFonts.nunito(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: const Color(0xFF38BDF8),
                ),
              ),
            ],
          ),
        ),
        SizedBox(
          height: 42,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: categories.length,
            separatorBuilder: (_, __) => const SizedBox(width: 10),
            itemBuilder: (context, i) {
              final cat = categories[i];
              final slug = cat['slug'] as String? ?? '';
              final name = cat['name'] as String? ?? '';
              final emoji = _emojiForSlug(slug);
              // First category is visually active (electric blue accent)
              final isFirst = i == 0;

              return GestureDetector(
                onTap: () => onCategoryTap(
                  (cat['id'] as int?)?.toString() ?? slug,
                ),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: isFirst
                        ? const Color(0xFF38BDF8).withValues(alpha: 0.15)
                        : const Color(0xFFFFFFFF).withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(100),
                    border: Border.all(
                      color: isFirst
                          ? const Color(0xFF38BDF8).withValues(alpha: 0.3)
                          : Colors.white.withValues(alpha: 0.1),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.white.withValues(
                          alpha: isFirst ? 0.1 : 0.06,
                        ),
                        offset: const Offset(0, -1),
                        blurRadius: 0,
                        spreadRadius: 0,
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(emoji, style: const TextStyle(fontSize: 14)),
                      const SizedBox(width: 6),
                      Text(
                        name,
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: isFirst
                              ? const Color(0xFF38BDF8)
                              : Colors.white.withValues(alpha: 0.7),
                        ),
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
    this.eventDate,
  });

  final List<Map<String, dynamic>> talents;
  final bool isLoading;
  final ValueChanged<Map<String, dynamic>> onTalentTap;
  final DateTime? eventDate;

  @override
  Widget build(BuildContext context) {
    // Show all talents in the full list
    final allTalents = talents;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          child: Row(
            children: [
              Text(
                'Tous les talents',
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
        else if (allTalents.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              'Aucun talent disponible',
              style: GoogleFonts.manrope(color: _mutedFg),
            ),
          )
        else
          ...allTalents.map((talent) {
            final attrs =
                talent['attributes'] as Map<String, dynamic>? ?? talent;
            return _NearbyTalentCard(
              talent: talent,
              attrs: attrs,
              onTap: () => onTalentTap(talent),
              eventDate: eventDate,
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
    this.eventDate,
  });

  final Map<String, dynamic> talent;
  final Map<String, dynamic> attrs;
  final VoidCallback onTap;
  final DateTime? eventDate;

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

    // Availability: present only when event_date was sent in the request
    final availabilityRaw = attrs['is_available'];
    final bool? isAvailable =
        eventDate != null && availabilityRaw != null
            ? availabilityRaw as bool?
            : null;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.fromLTRB(16, 0, 16, 10),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isAvailable == false
                ? Colors.white.withValues(alpha: 0.05)
                : Colors.white.withValues(alpha: 0.1),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                // Avatar + availability ring
                Stack(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: SizedBox(
                        width: 64,
                        height: 64,
                        child: ColorFiltered(
                          colorFilter: isAvailable == false
                              ? const ColorFilter.matrix([
                                  0.2, 0, 0, 0, 0,
                                  0, 0.2, 0, 0, 0,
                                  0, 0, 0.2, 0, 0,
                                  0, 0, 0, 1, 0,
                                ])
                              : const ColorFilter.mode(
                                  Colors.transparent,
                                  BlendMode.multiply,
                                ),
                          child: photoUrl.isNotEmpty
                              ? CachedNetworkImage(
                                  imageUrl: photoUrl,
                                  fit: BoxFit.cover,
                                  placeholder: (_, __) =>
                                      Container(color: _border),
                                  errorWidget: (_, __, ___) => Container(
                                    color: _border,
                                    child: const Icon(
                                      Icons.person,
                                      color: _mutedFg,
                                    ),
                                  ),
                                )
                              : Container(
                                  color: _border,
                                  child: const Icon(
                                    Icons.person,
                                    color: _mutedFg,
                                  ),
                                ),
                        ),
                      ),
                    ),
                    if (isAvailable != null)
                      Positioned(
                        bottom: 2,
                        right: 2,
                        child: Container(
                          width: 12,
                          height: 12,
                          decoration: BoxDecoration(
                            color: isAvailable
                                ? const Color(0xFF00C853)
                                : const Color(0xFFEF4444),
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: const Color(0xFF0A0F1E),
                              width: 2,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(width: 12),
                Expanded(
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
                                color: isAvailable == false
                                    ? Colors.white.withValues(alpha: 0.45)
                                    : _secondary,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
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
                          // Availability badge
                          if (isAvailable != null) ...[
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 6,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: isAvailable
                                    ? const Color(0xFF00C853)
                                        .withValues(alpha: 0.15)
                                    : const Color(0xFFEF4444)
                                        .withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(
                                  color: isAvailable
                                      ? const Color(0xFF00C853)
                                          .withValues(alpha: 0.4)
                                      : const Color(0xFFEF4444)
                                          .withValues(alpha: 0.3),
                                ),
                              ),
                              child: Text(
                                isAvailable ? 'Disponible' : 'Indisponible',
                                style: TextStyle(
                                  fontSize: 9,
                                  fontWeight: FontWeight.w700,
                                  color: isAvailable
                                      ? const Color(0xFF00C853)
                                      : const Color(0xFFEF4444),
                                ),
                              ),
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
                const SizedBox(width: 8),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      _formatCachet(cachetAmount),
                      style: GoogleFonts.manrope(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: isAvailable == false
                            ? _mutedFg
                            : _primary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: isAvailable == false
                            ? Colors.white.withValues(alpha: 0.06)
                            : _primary,
                        borderRadius: BorderRadius.circular(8),
                        border: isAvailable == false
                            ? Border.all(
                                color: Colors.white.withValues(alpha: 0.1),
                              )
                            : null,
                      ),
                      child: Text(
                        'Réserver',
                        style: GoogleFonts.manrope(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: isAvailable == false
                              ? Colors.white.withValues(alpha: 0.3)
                              : Colors.white,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
            // "Notify when available" CTA — only for unavailable talents with a date
            if (isAvailable == false && eventDate != null)
              _NotifyAvailabilityButton(
                talent: talent,
                attrs: attrs,
                eventDate: eventDate!,
              ),
          ],
        ),
      ),
    );
  }
}

// ── Notify availability button ────────────────────────────────────
class _NotifyAvailabilityButton extends StatefulWidget {
  const _NotifyAvailabilityButton({
    required this.talent,
    required this.attrs,
    required this.eventDate,
  });

  final Map<String, dynamic> talent;
  final Map<String, dynamic> attrs;
  final DateTime eventDate;

  @override
  State<_NotifyAvailabilityButton> createState() =>
      _NotifyAvailabilityButtonState();
}

class _NotifyAvailabilityButtonState
    extends State<_NotifyAvailabilityButton> {
  bool _requested = false;
  bool _loading = false;

  Future<void> _onNotify() async {
    if (_requested || _loading) return;
    setState(() => _loading = true);

    final talentId = widget.talent['id'] as int? ??
        (widget.attrs['id'] as int? ?? 0);
    final dateStr =
        '${widget.eventDate.year}-${widget.eventDate.month.toString().padLeft(2, '0')}-${widget.eventDate.day.toString().padLeft(2, '0')}';

    final repo = context.read<DiscoveryRepository>();
    final result = await repo.notifyWhenAvailable(
      talentId: talentId,
      eventDate: dateStr,
    );

    if (!mounted) return;
    setState(() {
      _loading = false;
      if (result is ApiSuccess) _requested = true;
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          result is ApiSuccess
              ? 'Vous serez notifié quand ce talent sera disponible'
              : 'Une erreur est survenue. Réessayez.',
        ),
        backgroundColor: result is ApiSuccess
            ? const Color(0xFF00C853)
            : const Color(0xFFEF4444),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: GestureDetector(
        onTap: _onNotify,
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: _requested
                ? const Color(0xFF00C853).withValues(alpha: 0.1)
                : Colors.white.withValues(alpha: 0.05),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: _requested
                  ? const Color(0xFF00C853).withValues(alpha: 0.35)
                  : Colors.white.withValues(alpha: 0.1),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (_loading)
                const SizedBox(
                  width: 12,
                  height: 12,
                  child: CircularProgressIndicator(
                    strokeWidth: 1.5,
                    color: Color(0xFF64B5F6),
                  ),
                )
              else
                Icon(
                  _requested
                      ? Icons.notifications_active_outlined
                      : Icons.notifications_none_outlined,
                  size: 13,
                  color: _requested
                      ? const Color(0xFF00C853)
                      : Colors.white.withValues(alpha: 0.55),
                ),
              const SizedBox(width: 6),
              Text(
                _requested
                    ? 'Notification activée'
                    : 'Me notifier quand disponible',
                style: GoogleFonts.nunito(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: _requested
                      ? const Color(0xFF00C853)
                      : Colors.white.withValues(alpha: 0.55),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
