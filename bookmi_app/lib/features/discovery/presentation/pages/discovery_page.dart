import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:geolocator/geolocator.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_bloc.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_event.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_state.dart';
import 'package:bookmi_app/features/discovery/presentation/widgets/talent_grid.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_bloc.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_event.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

// ── Design tokens ─────────────────────────────────────────────────
const _primary = Color(0xFF2196F3);
const _secondary = Colors.white;
const _mutedFg = Color(0xFF94A3B8);

enum _ViewMode { grid, list }

String _formatCachet(int amount) {
  return NumberFormat(
        '#,###',
        'fr_FR',
      ).format(amount).replaceAll(RegExp(r'[\s\u00A0\u202F,]'), '\u202F') +
      ' FCFA';
}

class DiscoveryPage extends StatefulWidget {
  const DiscoveryPage({super.key});

  @override
  State<DiscoveryPage> createState() => _DiscoveryPageState();
}

class _DiscoveryPageState extends State<DiscoveryPage> {
  final _scrollController = ScrollController();
  final _searchController = TextEditingController();
  Timer? _debounce;
  String _selectedCategory = '';
  _ViewMode _viewMode = _ViewMode.grid;
  Map<String, dynamic> _advancedFilters = {};

  int get _activeFilterCount => _advancedFilters.length;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _searchController.addListener(_onSearchChanged);
    context.read<DiscoveryBloc>().add(const DiscoveryFetched());
    context.read<FavoritesBloc>().add(const FavoritesFetched());
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollController
      ..removeListener(_onScroll)
      ..dispose();
    _searchController
      ..removeListener(_onSearchChanged)
      ..dispose();
    super.dispose();
  }

  void _onScroll() {
    final maxScroll = _scrollController.position.maxScrollExtent;
    if (_scrollController.position.pixels >= maxScroll - 200) {
      context.read<DiscoveryBloc>().add(const DiscoveryNextPageFetched());
    }
  }

  void _onSearchChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      if (!mounted) return;
      if (_advancedFilters.isEmpty) {
        context.read<DiscoveryBloc>().add(
          DiscoverySearchChanged(query: _searchController.text.trim()),
        );
      } else {
        _dispatchCombinedFilters();
      }
    });
  }

  void _dispatchCombinedFilters() {
    final filters = <String, dynamic>{
      if (_selectedCategory.isNotEmpty)
        'category_id': int.tryParse(_selectedCategory) ?? _selectedCategory,
      if (_searchController.text.trim().isNotEmpty)
        'q': _searchController.text.trim(),
      ..._advancedFilters,
    };
    final bloc = context.read<DiscoveryBloc>();
    if (filters.isEmpty) {
      bloc.add(const DiscoveryFilterCleared());
    } else {
      bloc.add(DiscoveryFiltersChanged(filters: filters));
    }
  }

  void _applyAdvancedFilters(Map<String, dynamic> filters) {
    setState(() => _advancedFilters = filters);
    _dispatchCombinedFilters();
  }

  void _onCategoryTap(String key) {
    setState(() => _selectedCategory = key);
    final bloc = context.read<DiscoveryBloc>();
    if (key.isEmpty) {
      if (_searchController.text.isNotEmpty || _advancedFilters.isNotEmpty) {
        final filters = <String, dynamic>{
          if (_searchController.text.isNotEmpty)
            'q': _searchController.text.trim(),
          ..._advancedFilters,
        };
        bloc.add(DiscoveryFiltersChanged(filters: filters));
      } else {
        bloc.add(const DiscoveryFilterCleared());
      }
    } else {
      bloc.add(
        DiscoveryFiltersChanged(
          filters: {
            'category_id': int.tryParse(key) ?? key,
            if (_searchController.text.isNotEmpty)
              'q': _searchController.text.trim(),
            ..._advancedFilters,
          },
        ),
      );
    }
  }

  Future<void> _onTalentTap(Map<String, dynamic> talent) async {
    final attrs = talent['attributes'] as Map<String, dynamic>? ?? talent;
    final slug = attrs['slug'] as String? ?? '';
    if (slug.isEmpty) return;
    await context.pushNamed(
      RouteNames.talentDetail,
      pathParameters: {'slug': slug},
      extra: {...attrs, 'id': talent['id']},
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: Column(
        children: [
          // ── Top bar + search ─────────────────────────────────────
          Padding(
            padding: EdgeInsets.fromLTRB(
              20,
              MediaQuery.of(context).padding.top + 8,
              20,
              12,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Title row + filter button + view toggle
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Text(
                      'Recherche',
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w900,
                        color: Colors.white,
                        letterSpacing: -0.5,
                      ),
                    ),
                    const Spacer(),
                    // Filter button with active-count badge
                    GestureDetector(
                      onTap: () async {
                        final result = await _FilterSheet.show(
                          context,
                          initialFilters: _advancedFilters,
                        );
                        if (result != null && mounted) {
                          _applyAdvancedFilters(result);
                        }
                      },
                      child: Stack(
                        clipBehavior: Clip.none,
                        children: [
                          AnimatedContainer(
                            duration: const Duration(milliseconds: 160),
                            height: 34,
                            width: 34,
                            decoration: BoxDecoration(
                              color: _activeFilterCount > 0
                                  ? _primary
                                  : Colors.white.withValues(alpha: 0.07),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(
                                color: Colors.white.withValues(alpha: 0.10),
                              ),
                            ),
                            child: Icon(
                              Icons.tune_rounded,
                              size: 16,
                              color: _activeFilterCount > 0
                                  ? Colors.white
                                  : Colors.white.withValues(alpha: 0.45),
                            ),
                          ),
                          if (_activeFilterCount > 0)
                            Positioned(
                              top: -4,
                              right: -4,
                              child: Container(
                                width: 15,
                                height: 15,
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFF6B6B),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(
                                    color: const Color(0xFF0F1923),
                                    width: 1.5,
                                  ),
                                ),
                                child: Center(
                                  child: Text(
                                    '$_activeFilterCount',
                                    style: const TextStyle(
                                      fontSize: 8,
                                      color: Colors.white,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    _ViewToggle(
                      current: _viewMode,
                      onChanged: (mode) => setState(() => _viewMode = mode),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                // ── Text search field ─────────────────────────────
                Container(
                  height: 46,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.07),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.12),
                    ),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(
                    children: [
                      const SizedBox(width: 14),
                      Icon(
                        Icons.search,
                        color: Colors.white.withValues(alpha: 0.4),
                        size: 18,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextField(
                          controller: _searchController,
                          style: GoogleFonts.nunito(
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                            color: Colors.white,
                          ),
                          decoration: InputDecoration(
                            hintText: 'Nom, spécialité, ville…',
                            hintStyle: GoogleFonts.nunito(
                              fontSize: 14,
                              fontWeight: FontWeight.w500,
                              color: Colors.white.withValues(alpha: 0.3),
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
                      if (_searchController.text.isNotEmpty)
                        GestureDetector(
                          onTap: () {
                            _searchController.clear();
                            setState(() {
                              _selectedCategory = '';
                              _advancedFilters = {};
                            });
                            context.read<DiscoveryBloc>().add(
                              const DiscoveryFilterCleared(),
                            );
                          },
                          child: Padding(
                            padding: const EdgeInsets.only(right: 12),
                            child: Icon(
                              Icons.close,
                              size: 16,
                              color: Colors.white.withValues(alpha: 0.4),
                            ),
                          ),
                        )
                      else
                        const SizedBox(width: 14),
                    ],
                  ),
                ),
              ],
            ),
          ),
          // ── Category pills ───────────────────────────────────────
          BlocBuilder<DiscoveryBloc, DiscoveryState>(
            buildWhen: (prev, curr) {
              final pCats = prev is DiscoveryLoaded
                  ? prev.categories
                  : const <Map<String, dynamic>>[];
              final cCats = curr is DiscoveryLoaded
                  ? curr.categories
                  : const <Map<String, dynamic>>[];
              return pCats != cCats;
            },
            builder: (context, state) {
              final categories = state is DiscoveryLoaded
                  ? state.categories
                  : const <Map<String, dynamic>>[];
              if (categories.isEmpty) return const SizedBox.shrink();

              final items = <(String, String)>[
                ('', 'Tout'),
                ...categories.map(
                  (c) => (
                    (c['id'] as int?)?.toString() ?? c['slug'] as String? ?? '',
                    c['name'] as String? ?? '',
                  ),
                ),
              ];

              return Container(
                color: Colors.transparent,
                padding: const EdgeInsets.only(bottom: 10),
                child: SizedBox(
                  height: 36,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 8),
                    itemBuilder: (context, i) {
                      final (key, label) = items[i];
                      final isActive = key == _selectedCategory;
                      return GestureDetector(
                        onTap: () => _onCategoryTap(key),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 180),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 7,
                          ),
                          decoration: BoxDecoration(
                            color: isActive
                                ? _primary
                                : Colors.white.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(
                              color: isActive
                                  ? _primary
                                  : Colors.white.withValues(alpha: 0.12),
                            ),
                          ),
                          child: Text(
                            label,
                            style: GoogleFonts.nunito(
                              fontSize: 12,
                              fontWeight: isActive
                                  ? FontWeight.w700
                                  : FontWeight.w500,
                              color: isActive
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
            },
          ),
          // ── Results ───────────────────────────────────────────────
          Expanded(
            child: RefreshIndicator(
              color: _primary,
              onRefresh: () async {
                context.read<DiscoveryBloc>().add(const DiscoveryFetched());
                await context.read<DiscoveryBloc>().stream.firstWhere(
                  (s) => s is DiscoveryLoaded || s is DiscoveryFailure,
                );
              },
              child: BlocBuilder<DiscoveryBloc, DiscoveryState>(
                builder: (context, state) {
                  return switch (state) {
                    DiscoveryInitial() || DiscoveryLoading() =>
                      _viewMode == _ViewMode.grid
                          ? TalentGrid.skeleton()
                          : _TalentList.skeleton(),
                    DiscoveryLoaded() =>
                      state.talents.isEmpty
                          ? _buildEmpty()
                          : _viewMode == _ViewMode.grid
                          ? TalentGrid(
                              talents: state.talents,
                              hasMore: state.hasMore,
                              isLoadingMore: state is DiscoveryLoadingMore,
                              scrollController: _scrollController,
                              onTalentTap: _onTalentTap,
                            )
                          : _TalentList(
                              talents: state.talents,
                              hasMore: state.hasMore,
                              isLoadingMore: state is DiscoveryLoadingMore,
                              scrollController: _scrollController,
                              onTalentTap: _onTalentTap,
                            ),
                    DiscoveryFailure(:final message) => _buildError(message),
                  };
                },
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.search_off_rounded,
            size: 52,
            color: Colors.white.withValues(alpha: 0.2),
          ),
          const SizedBox(height: 12),
          Text(
            'Aucun résultat',
            style: TextStyle(
              fontSize: 16,
              color: Colors.white.withValues(alpha: 0.5),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Essayez avec un autre nom ou catégorie',
            style: TextStyle(
              fontSize: 12,
              color: Colors.white.withValues(alpha: 0.3),
            ),
          ),
          const SizedBox(height: 16),
          GestureDetector(
            onTap: () {
              _searchController.clear();
              setState(() {
                _selectedCategory = '';
                _advancedFilters = {};
              });
              context.read<DiscoveryBloc>().add(const DiscoveryFilterCleared());
            },
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                'Élargir la recherche',
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.white.withValues(alpha: 0.6),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildError(String message) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.error_outline,
            size: 44,
            color: Colors.white.withValues(alpha: 0.25),
          ),
          const SizedBox(height: 10),
          Text(
            message,
            style: const TextStyle(color: Colors.white60),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 14),
          TextButton(
            onPressed: () =>
                context.read<DiscoveryBloc>().add(const DiscoveryFetched()),
            child: const Text(
              'Réessayer',
              style: TextStyle(color: Color(0xFF64B5F6)),
            ),
          ),
        ],
      ),
    );
  }
}

// ── View mode toggle ─────────────────────────────────────────────
class _ViewToggle extends StatelessWidget {
  const _ViewToggle({required this.current, required this.onChanged});

  final _ViewMode current;
  final ValueChanged<_ViewMode> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 34,
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.07),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.white.withValues(alpha: 0.10)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _ToggleBtn(
            icon: Icons.grid_view_rounded,
            isActive: current == _ViewMode.grid,
            onTap: () => onChanged(_ViewMode.grid),
          ),
          const SizedBox(width: 2),
          _ToggleBtn(
            icon: Icons.view_list_rounded,
            isActive: current == _ViewMode.list,
            onTap: () => onChanged(_ViewMode.list),
          ),
        ],
      ),
    );
  }
}

class _ToggleBtn extends StatelessWidget {
  const _ToggleBtn({
    required this.icon,
    required this.isActive,
    required this.onTap,
  });

  final IconData icon;
  final bool isActive;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        width: 28,
        height: 28,
        decoration: BoxDecoration(
          color: isActive ? _primary : Colors.transparent,
          borderRadius: BorderRadius.circular(7),
          boxShadow: isActive
              ? [
                  BoxShadow(
                    color: _primary.withValues(alpha: 0.4),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Icon(
          icon,
          size: 16,
          color: isActive ? Colors.white : Colors.white.withValues(alpha: 0.45),
        ),
      ),
    );
  }
}

// ── List view ────────────────────────────────────────────────────
class _TalentList extends StatelessWidget {
  const _TalentList({
    required this.talents,
    required this.hasMore,
    required this.isLoadingMore,
    required this.scrollController,
    required this.onTalentTap,
  });

  final List<Map<String, dynamic>> talents;
  final bool hasMore;
  final bool isLoadingMore;
  final ScrollController scrollController;
  final ValueChanged<Map<String, dynamic>> onTalentTap;

  static Widget skeleton() {
    return CustomScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      slivers: [
        SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          sliver: SliverList(
            delegate: SliverChildBuilderDelegate(
              (_, __) => Container(
                margin: const EdgeInsets.only(bottom: 10),
                height: 90,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.06),
                  borderRadius: BorderRadius.circular(16),
                ),
              ),
              childCount: 5,
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
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          sliver: SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, index) => _TalentListItem(
                talent: talents[index],
                onTap: () => onTalentTap(talents[index]),
              ),
              childCount: talents.length,
            ),
          ),
        ),
        if (isLoadingMore)
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Center(
                child: const CircularProgressIndicator(color: _primary),
              ),
            ),
          ),
        if (!hasMore && talents.isNotEmpty)
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
              child: Center(
                child: Text(
                  'Fin des résultats',
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.white.withValues(alpha: 0.4),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

// ── List item (style identique à _NearbyTalentCard de home_page) ──
class _TalentListItem extends StatelessWidget {
  const _TalentListItem({required this.talent, required this.onTap});

  final Map<String, dynamic> talent;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final attrs = talent['attributes'] as Map<String, dynamic>? ?? talent;
    final stageName = attrs['stage_name'] as String? ?? 'Talent';
    final photoUrl = attrs['photo_url'] as String? ?? '';
    final cachetAmount = attrs['cachet_amount'] as int? ?? 0;
    final averageRating = double.tryParse('${attrs['average_rating']}') ?? 0.0;
    final isVerified = attrs['is_verified'] as bool? ?? false;
    final category = attrs['category'] as Map<String, dynamic>?;
    final categoryName = category?['name'] as String? ?? '';
    final city = attrs['city'] as String? ?? '';

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withValues(alpha: 0.10)),
        ),
        child: Row(
          children: [
            // Avatar
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: SizedBox(
                width: 64,
                height: 64,
                child: photoUrl.isNotEmpty
                    ? CachedNetworkImage(
                        imageUrl: photoUrl,
                        fit: BoxFit.cover,
                        placeholder: (_, __) => _placeholder(),
                        errorWidget: (_, __, ___) => _placeholder(),
                      )
                    : _placeholder(),
              ),
            ),
            const SizedBox(width: 12),
            // Info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Name + verified
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
                          size: 13,
                          color: _primary,
                        ),
                      ],
                    ],
                  ),
                  if (categoryName.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      categoryName,
                      style: GoogleFonts.manrope(
                        fontSize: 12,
                        color: _mutedFg,
                      ),
                    ),
                  ],
                  if (city.isNotEmpty) ...[
                    const SizedBox(height: 3),
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
            const SizedBox(width: 10),
            // Price + rating (right side)
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  _formatCachet(cachetAmount),
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF64B5F6),
                  ),
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    const Icon(
                      Icons.star_rounded,
                      size: 12,
                      color: Color(0xFF64B5F6),
                    ),
                    const SizedBox(width: 3),
                    Text(
                      averageRating.toStringAsFixed(1),
                      style: GoogleFonts.nunito(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: Colors.white.withValues(alpha: 0.6),
                      ),
                    ),
                  ],
                ),
                // Reserve button
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF2196F3), Color(0xFF64B5F6)],
                    ),
                    borderRadius: BorderRadius.circular(8),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF2196F3).withValues(alpha: 0.3),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Text(
                    'Réserver',
                    style: GoogleFonts.nunito(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
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

  Widget _placeholder() {
    return Container(
      color: const Color(0xFF0D1421),
      child: const Icon(Icons.person, color: _mutedFg, size: 32),
    );
  }
}

// ── Filter / Sort bottom sheet ─────────────────────────────────────────────

enum _SortOption { popularity, rating, priceAsc, priceDesc }

class _FilterSheet extends StatefulWidget {
  const _FilterSheet({required this.initialFilters});

  final Map<String, dynamic> initialFilters;

  static Future<Map<String, dynamic>?> show(
    BuildContext context, {
    required Map<String, dynamic> initialFilters,
  }) {
    return showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _FilterSheet(initialFilters: initialFilters),
    );
  }

  @override
  State<_FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends State<_FilterSheet> {
  late _SortOption _sort;
  late TextEditingController _cityCtrl;
  late TextEditingController _minBudgetCtrl;
  late TextEditingController _maxBudgetCtrl;
  double? _minRating;

  // Geo-radius filter state
  bool _useLocation = false;
  double? _lat;
  double? _lng;
  int _radius = 25;
  bool _fetchingLocation = false;

  @override
  void initState() {
    super.initState();
    final f = widget.initialFilters;
    _cityCtrl = TextEditingController(text: f['city'] as String? ?? '');
    _minBudgetCtrl = TextEditingController(
      text: f['min_cachet']?.toString() ?? '',
    );
    _maxBudgetCtrl = TextEditingController(
      text: f['max_cachet']?.toString() ?? '',
    );
    _minRating = (f['min_rating'] as num?)?.toDouble();

    // Restore geo state from initial filters
    if (f['lat'] != null && f['lng'] != null) {
      _useLocation = true;
      _lat = (f['lat'] as num).toDouble();
      _lng = (f['lng'] as num).toDouble();
      _radius = (f['radius'] as num?)?.toInt() ?? 25;
    }

    final sortBy = f['sort_by'] as String?;
    final sortDir = f['sort_direction'] as String?;
    if (sortBy == 'distance') {
      _sort = _SortOption.popularity; // distance overrides sort when geo active
    } else if (sortBy == 'rating') {
      _sort = _SortOption.rating;
    } else if (sortBy == 'cachet_amount' && sortDir == 'asc') {
      _sort = _SortOption.priceAsc;
    } else if (sortBy == 'cachet_amount' && sortDir == 'desc') {
      _sort = _SortOption.priceDesc;
    } else {
      _sort = _SortOption.popularity;
    }
  }

  @override
  void dispose() {
    _cityCtrl.dispose();
    _minBudgetCtrl.dispose();
    _maxBudgetCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchLocation() async {
    setState(() => _fetchingLocation = true);
    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.deniedForever ||
          permission == LocationPermission.denied) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Permission de localisation refusée.'),
            ),
          );
        }
        setState(() {
          _useLocation = false;
          _fetchingLocation = false;
        });
        return;
      }
      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
        ),
      );
      if (mounted) {
        setState(() {
          _lat = pos.latitude;
          _lng = pos.longitude;
          _fetchingLocation = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _useLocation = false;
          _fetchingLocation = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Impossible d\'obtenir votre position.'),
          ),
        );
      }
    }
  }

  Map<String, dynamic> _buildFilters() {
    final filters = <String, dynamic>{};
    // Geo overrides sort to distance when active
    if (_useLocation && _lat != null && _lng != null) {
      filters['lat'] = _lat;
      filters['lng'] = _lng;
      filters['radius'] = _radius;
      filters['sort_by'] = 'distance';
      filters['sort_direction'] = 'asc';
    } else {
      if (_sort == _SortOption.rating) {
        filters['sort_by'] = 'rating';
        filters['sort_direction'] = 'desc';
      } else if (_sort == _SortOption.priceAsc) {
        filters['sort_by'] = 'cachet_amount';
        filters['sort_direction'] = 'asc';
      } else if (_sort == _SortOption.priceDesc) {
        filters['sort_by'] = 'cachet_amount';
        filters['sort_direction'] = 'desc';
      }
    }
    if (_cityCtrl.text.trim().isNotEmpty) {
      filters['city'] = _cityCtrl.text.trim();
    }
    final minVal = int.tryParse(_minBudgetCtrl.text);
    final maxVal = int.tryParse(_maxBudgetCtrl.text);
    if (minVal != null) filters['min_cachet'] = minVal;
    if (maxVal != null) filters['max_cachet'] = maxVal;
    if (_minRating != null) filters['min_rating'] = _minRating;
    return filters;
  }

  int get _activeCount => _buildFilters().length;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFF0F1923),
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        top: 12,
        left: 20,
        right: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 28,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Drag handle
            Center(
              child: Container(
                width: 36,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 20),
            // Header
            Row(
              children: [
                Text(
                  'Filtres & Tri',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
                const Spacer(),
                GestureDetector(
                  onTap: () => setState(() {
                    _sort = _SortOption.popularity;
                    _cityCtrl.clear();
                    _minBudgetCtrl.clear();
                    _maxBudgetCtrl.clear();
                    _minRating = null;
                    _useLocation = false;
                    _lat = null;
                    _lng = null;
                    _radius = 25;
                  }),
                  child: Text(
                    'Réinitialiser',
                    style: GoogleFonts.manrope(
                      fontSize: 13,
                      color: const Color(0xFF64B5F6),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // ── Sort ────────────────────────────────────────────────
            _SectionLabel(label: 'TRIER PAR'),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _SortChip(
                  label: 'Popularité',
                  icon: Icons.trending_up_rounded,
                  isActive: _sort == _SortOption.popularity,
                  onTap: () => setState(() => _sort = _SortOption.popularity),
                ),
                _SortChip(
                  label: 'Mieux notés',
                  icon: Icons.star_rounded,
                  isActive: _sort == _SortOption.rating,
                  onTap: () => setState(() => _sort = _SortOption.rating),
                ),
                _SortChip(
                  label: 'Prix croissant',
                  icon: Icons.arrow_upward_rounded,
                  isActive: _sort == _SortOption.priceAsc,
                  onTap: () => setState(() => _sort = _SortOption.priceAsc),
                ),
                _SortChip(
                  label: 'Prix décroissant',
                  icon: Icons.arrow_downward_rounded,
                  isActive: _sort == _SortOption.priceDesc,
                  onTap: () => setState(() => _sort = _SortOption.priceDesc),
                ),
              ],
            ),
            const SizedBox(height: 22),

            // ── City ─────────────────────────────────────────────────
            _SectionLabel(label: 'VILLE'),
            const SizedBox(height: 10),
            _FilterTextField(
              controller: _cityCtrl,
              hint: 'Ex : Abidjan, Dakar, Paris…',
            ),
            const SizedBox(height: 22),

            // ── Budget ───────────────────────────────────────────────
            _SectionLabel(label: 'BUDGET (FCFA)'),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: _FilterTextField(
                    controller: _minBudgetCtrl,
                    hint: 'Min',
                    keyboardType: TextInputType.number,
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  child: Text(
                    '—',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.3),
                      fontSize: 16,
                    ),
                  ),
                ),
                Expanded(
                  child: _FilterTextField(
                    controller: _maxBudgetCtrl,
                    hint: 'Max',
                    keyboardType: TextInputType.number,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 22),

            // ── Min rating ───────────────────────────────────────────
            _SectionLabel(label: 'NOTE MINIMALE'),
            const SizedBox(height: 10),
            Row(
              children: [
                _RatingChip(
                  label: 'Toutes',
                  isActive: _minRating == null,
                  onTap: () => setState(() => _minRating = null),
                ),
                const SizedBox(width: 8),
                _RatingChip(
                  label: '3 ★+',
                  isActive: _minRating == 3.0,
                  onTap: () => setState(() => _minRating = 3.0),
                ),
                const SizedBox(width: 8),
                _RatingChip(
                  label: '4 ★+',
                  isActive: _minRating == 4.0,
                  onTap: () => setState(() => _minRating = 4.0),
                ),
                const SizedBox(width: 8),
                _RatingChip(
                  label: '5 ★',
                  isActive: _minRating == 5.0,
                  onTap: () => setState(() => _minRating = 5.0),
                ),
              ],
            ),
            const SizedBox(height: 22),

            // ── Près de moi ──────────────────────────────────────────
            _SectionLabel(label: 'PRÈS DE MOI'),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: Text(
                    _useLocation && _lat != null
                        ? 'Position obtenue ✓'
                        : 'Filtrer par distance',
                    style: GoogleFonts.manrope(
                      fontSize: 13,
                      color: _useLocation && _lat != null
                          ? const Color(0xFF4CAF50)
                          : Colors.white.withValues(alpha: 0.7),
                    ),
                  ),
                ),
                if (_fetchingLocation)
                  const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: _primary,
                    ),
                  )
                else
                  Switch(
                    value: _useLocation,
                    activeColor: _primary,
                    onChanged: (val) {
                      if (val && _lat == null) {
                        setState(() => _useLocation = true);
                        _fetchLocation();
                      } else {
                        setState(() {
                          _useLocation = val;
                          if (!val) {
                            _lat = null;
                            _lng = null;
                          }
                        });
                      }
                    },
                  ),
              ],
            ),
            if (_useLocation) ...[
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                children: [10, 25, 50, 100].map((km) {
                  final isActive = _radius == km;
                  return GestureDetector(
                    onTap: () => setState(() => _radius = km),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 150),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 8,
                      ),
                      decoration: BoxDecoration(
                        color: isActive
                            ? _primary
                            : Colors.white.withValues(alpha: 0.07),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: isActive
                              ? _primary
                              : Colors.white.withValues(alpha: 0.12),
                        ),
                      ),
                      child: Text(
                        '$km km',
                        style: GoogleFonts.nunito(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: isActive
                              ? Colors.white
                              : Colors.white.withValues(alpha: 0.6),
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ],
            const SizedBox(height: 30),

            // ── Apply button ─────────────────────────────────────────
            SizedBox(
              width: double.infinity,
              height: 52,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF2196F3), Color(0xFF1565C0)],
                  ),
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF2196F3).withValues(alpha: 0.35),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: ElevatedButton(
                  onPressed: () => Navigator.of(context).pop(_buildFilters()),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.transparent,
                    shadowColor: Colors.transparent,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: Text(
                    _activeCount > 0
                        ? 'Appliquer ($_activeCount filtre${_activeCount > 1 ? 's' : ''})'
                        : 'Appliquer',
                    style: GoogleFonts.nunito(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Shared sheet helper widgets ────────────────────────────────────────────

class _SectionLabel extends StatelessWidget {
  const _SectionLabel({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: GoogleFonts.manrope(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        color: Colors.white.withValues(alpha: 0.45),
        letterSpacing: 0.8,
      ),
    );
  }
}

class _FilterTextField extends StatelessWidget {
  const _FilterTextField({
    required this.controller,
    required this.hint,
    this.keyboardType,
  });

  final TextEditingController controller;
  final String hint;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 46,
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withValues(alpha: 0.10)),
      ),
      child: TextField(
        controller: controller,
        keyboardType: keyboardType,
        style: GoogleFonts.nunito(fontSize: 14, color: Colors.white),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: GoogleFonts.nunito(
            fontSize: 14,
            color: Colors.white.withValues(alpha: 0.3),
          ),
          border: InputBorder.none,
          enabledBorder: InputBorder.none,
          focusedBorder: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 14,
          ),
          isDense: true,
        ),
      ),
    );
  }
}

class _SortChip extends StatelessWidget {
  const _SortChip({
    required this.label,
    required this.icon,
    required this.isActive,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final bool isActive;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
        decoration: BoxDecoration(
          color: isActive ? _primary : Colors.white.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isActive
                ? _primary
                : Colors.white.withValues(alpha: 0.12),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 13,
              color: isActive
                  ? Colors.white
                  : Colors.white.withValues(alpha: 0.55),
            ),
            const SizedBox(width: 5),
            Text(
              label,
              style: GoogleFonts.nunito(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: isActive
                    ? Colors.white
                    : Colors.white.withValues(alpha: 0.6),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RatingChip extends StatelessWidget {
  const _RatingChip({
    required this.label,
    required this.isActive,
    required this.onTap,
  });

  final String label;
  final bool isActive;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
        decoration: BoxDecoration(
          color: isActive
              ? const Color(0xFFFFB300).withValues(alpha: 0.18)
              : Colors.white.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isActive
                ? const Color(0xFFFFB300).withValues(alpha: 0.6)
                : Colors.white.withValues(alpha: 0.12),
          ),
        ),
        child: Text(
          label,
          style: GoogleFonts.nunito(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: isActive
                ? const Color(0xFFFFB300)
                : Colors.white.withValues(alpha: 0.6),
          ),
        ),
      ),
    );
  }
}
