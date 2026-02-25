import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_bloc.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_event.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_state.dart';
import 'package:bookmi_app/features/discovery/presentation/widgets/talent_grid.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_bloc.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_event.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

// ── Design tokens ─────────────────────────────────────────────────
const _primary = Color(0xFF2196F3);

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
      context.read<DiscoveryBloc>().add(
        DiscoverySearchChanged(query: _searchController.text.trim()),
      );
    });
  }

  void _onCategoryTap(String key) {
    setState(() => _selectedCategory = key);
    final bloc = context.read<DiscoveryBloc>();
    if (key.isEmpty) {
      if (_searchController.text.isNotEmpty) {
        bloc.add(DiscoveryFiltersChanged(
          filters: {'q': _searchController.text.trim()},
        ));
      } else {
        bloc.add(const DiscoveryFilterCleared());
      }
    } else {
      bloc.add(DiscoveryFiltersChanged(filters: {
        'category_id': int.tryParse(key) ?? key,
        if (_searchController.text.isNotEmpty)
          'q': _searchController.text.trim(),
      }));
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
                Text(
                  'Recherche',
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                    color: Colors.white,
                    letterSpacing: -0.5,
                  ),
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
                            context.read<DiscoveryBloc>().add(
                              const DiscoveryFilterCleared(),
                            );
                            setState(() => _selectedCategory = '');
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
                    (c['id'] as int?)?.toString() ??
                        c['slug'] as String? ??
                        '',
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
          // ── Results grid ─────────────────────────────────────────
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
                    DiscoveryInitial() ||
                    DiscoveryLoading() => TalentGrid.skeleton(),
                    DiscoveryLoaded() => state.talents.isEmpty
                        ? _buildEmpty()
                        : TalentGrid(
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
              setState(() => _selectedCategory = '');
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
