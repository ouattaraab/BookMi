import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_bloc.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_event.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_state.dart';
import 'package:bookmi_app/features/discovery/data/repositories/discovery_repository.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

// ── Local tokens ──────────────────────────────────────────────────
const _primary = Color(0xFF2196F3);
const _mutedFg = Color(0xFF94A3B8);
const _border = Color(0x1AFFFFFF);

String _formatCachet(int amount) =>
    NumberFormat('#,###', 'fr_FR')
            .format(amount)
            .replaceAll(RegExp(r'[\s\u00A0\u202F,]'), '\u202F') +
        ' FCFA';

class SearchPlaceholderPage extends StatelessWidget {
  const SearchPlaceholderPage({super.key});

  @override
  Widget build(BuildContext context) {
    // Create an isolated DiscoveryBloc for the search tab
    return BlocProvider(
      create: (ctx) => DiscoveryBloc(
        repository: ctx.read<DiscoveryRepository>(),
      ),
      child: const _SearchView(),
    );
  }
}

class _SearchView extends StatefulWidget {
  const _SearchView();

  @override
  State<_SearchView> createState() => _SearchViewState();
}

class _SearchViewState extends State<_SearchView> {
  final _searchController = TextEditingController();
  Timer? _debounce;
  String _selectedCategory = '';

  @override
  void initState() {
    super.initState();
    // Load all talents immediately
    context.read<DiscoveryBloc>().add(const DiscoveryFetched());
    _searchController.addListener(_onSearchChanged);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController
      ..removeListener(_onSearchChanged)
      ..dispose();
    super.dispose();
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
        bloc.add(DiscoveryFiltersChanged(filters: {'q': _searchController.text.trim()}));
      } else {
        bloc.add(const DiscoveryFilterCleared());
      }
    } else {
      bloc.add(DiscoveryFiltersChanged(filters: {
        'category_id': int.tryParse(key) ?? key,
        if (_searchController.text.isNotEmpty) 'q': _searchController.text.trim(),
      }));
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
      body: Column(
        children: [
          // ── Top bar ──────────────────────────────────────────────
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
                // ── Search field ──────────────────────────────────
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
                          autofocus: false,
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
          // ── Results ──────────────────────────────────────────────
          Expanded(
            child: BlocBuilder<DiscoveryBloc, DiscoveryState>(
              builder: (context, state) {
                if (state is DiscoveryLoading || state is DiscoveryInitial) {
                  return _buildSkeletons();
                }
                if (state is DiscoveryFailure) {
                  return _buildError(context, state.message);
                }
                if (state is DiscoveryLoaded) {
                  if (state.talents.isEmpty) {
                    return _buildEmpty();
                  }
                  return RefreshIndicator(
                    color: _primary,
                    onRefresh: () async {
                      context.read<DiscoveryBloc>().add(const DiscoveryFetched());
                      await context
                          .read<DiscoveryBloc>()
                          .stream
                          .firstWhere(
                            (s) =>
                                s is DiscoveryLoaded ||
                                s is DiscoveryFailure,
                          );
                    },
                    child: NotificationListener<ScrollNotification>(
                      onNotification: (n) {
                        if (n is ScrollEndNotification &&
                            n.metrics.pixels >=
                                n.metrics.maxScrollExtent - 200) {
                          context
                              .read<DiscoveryBloc>()
                              .add(const DiscoveryNextPageFetched());
                        }
                        return false;
                      },
                      child: ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                        itemCount: state.talents.length +
                            (state is DiscoveryLoadingMore ? 1 : 0),
                        separatorBuilder: (_, __) =>
                            const SizedBox(height: 8),
                        itemBuilder: (context, i) {
                          if (i >= state.talents.length) {
                            return const Padding(
                              padding: EdgeInsets.all(16),
                              child: Center(
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: _primary,
                                ),
                              ),
                            );
                          }
                          final talent = state.talents[i];
                          final attrs =
                              talent['attributes']
                                  as Map<String, dynamic>? ??
                              talent;
                          return _SearchTalentCard(
                            talent: talent,
                            attrs: attrs,
                            onTap: () => _onTalentTap(talent),
                          );
                        },
                      ),
                    ),
                  );
                }
                return _buildSkeletons();
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSkeletons() {
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
      itemCount: 6,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (_, __) => Container(
        height: 84,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(16),
        ),
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
        ],
      ),
    );
  }

  Widget _buildError(BuildContext context, String message) {
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

// ── Talent card for search results ───────────────────────────────
class _SearchTalentCard extends StatelessWidget {
  const _SearchTalentCard({
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
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
        ),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: SizedBox(
                width: 60,
                height: 60,
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
                        child:
                            const Icon(Icons.person, color: _mutedFg),
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
                      Expanded(
                        child: Text(
                          stageName,
                          style: GoogleFonts.nunito(
                            fontSize: 14,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
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
                  const SizedBox(height: 2),
                  if (categoryName.isNotEmpty)
                    Text(
                      categoryName,
                      style: GoogleFonts.manrope(
                        fontSize: 11,
                        color: _mutedFg,
                      ),
                    ),
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      const Icon(
                        Icons.star_rounded,
                        size: 11,
                        color: Color(0xFF64B5F6),
                      ),
                      const SizedBox(width: 2),
                      Text(
                        averageRating.toStringAsFixed(1),
                        style: GoogleFonts.nunito(
                          fontSize: 11,
                          color: _mutedFg,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      if (city.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        const Icon(
                          Icons.location_on_outlined,
                          size: 11,
                          color: _mutedFg,
                        ),
                        const SizedBox(width: 2),
                        Expanded(
                          child: Text(
                            city,
                            style: GoogleFonts.manrope(
                              fontSize: 11,
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
                  style: GoogleFonts.nunito(
                    fontSize: 11,
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
                    style: GoogleFonts.nunito(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
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
