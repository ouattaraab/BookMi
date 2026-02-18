import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/filter_bar.dart';
import 'package:bookmi_app/core/design_system/components/glass_app_bar.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_bloc.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_event.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_state.dart';
import 'package:bookmi_app/features/discovery/presentation/widgets/talent_grid.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_bloc.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_event.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

class DiscoveryPage extends StatefulWidget {
  const DiscoveryPage({super.key});

  @override
  State<DiscoveryPage> createState() => _DiscoveryPageState();
}

class _DiscoveryPageState extends State<DiscoveryPage> {
  final _scrollController = ScrollController();
  final _scrollOffset = ValueNotifier<double>(0);

  static const _categoryFilters = [
    FilterItem(key: 'dj', label: 'DJ'),
    FilterItem(key: 'groupe-musical', label: 'Groupe'),
    FilterItem(key: 'humoriste', label: 'Humoriste'),
    FilterItem(key: 'danseur', label: 'Danseur'),
    FilterItem(key: 'mc-animateur', label: 'MC'),
    FilterItem(key: 'photographe', label: 'Photo'),
    FilterItem(key: 'decorateur', label: 'Déco'),
  ];

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    context.read<DiscoveryBloc>().add(const DiscoveryFetched());
    context.read<FavoritesBloc>().add(const FavoritesFetched());
  }

  @override
  void dispose() {
    _scrollController
      ..removeListener(_onScroll)
      ..dispose();
    _scrollOffset.dispose();
    super.dispose();
  }

  void _onScroll() {
    _scrollOffset.value = _scrollController.offset;

    final maxScroll = _scrollController.position.maxScrollExtent;
    final currentScroll = _scrollController.position.pixels;
    if (currentScroll >= maxScroll - 200) {
      context.read<DiscoveryBloc>().add(const DiscoveryNextPageFetched());
    }
  }

  void _onFilterChanged(String filterKey) {
    final bloc = context.read<DiscoveryBloc>();
    final currentState = bloc.state;
    final currentFilters = currentState is DiscoveryLoaded
        ? Map<String, dynamic>.from(currentState.activeFilters)
        : <String, dynamic>{};

    if (currentFilters['category'] == filterKey) {
      currentFilters.remove('category');
    } else {
      currentFilters['category'] = filterKey;
    }

    if (currentFilters.isEmpty) {
      bloc.add(const DiscoveryFilterCleared());
    } else {
      bloc.add(DiscoveryFiltersChanged(filters: currentFilters));
    }
  }

  void _onClearAll() {
    context.read<DiscoveryBloc>().add(const DiscoveryFilterCleared());
  }

  Future<void> _onTalentTap(Map<String, dynamic> talent) async {
    final attributes = talent['attributes'] as Map<String, dynamic>;
    final slug = attributes['slug'] as String? ?? '';
    await context.pushNamed(
      RouteNames.talentDetail,
      pathParameters: {'slug': slug},
      extra: {...attributes, 'id': talent['id']},
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
        appBar: PreferredSize(
          preferredSize: const Size.fromHeight(kToolbarHeight),
          child: ValueListenableBuilder<double>(
            valueListenable: _scrollOffset,
            builder: (context, offset, _) => GlassAppBar(
              title: const Text('Recherche'),
              scrollOffset: offset,
            ),
          ),
        ),
        body: Column(
          children: [
            BlocBuilder<DiscoveryBloc, DiscoveryState>(
              buildWhen: (prev, curr) {
                final prevFilters = prev is DiscoveryLoaded
                    ? prev.activeFilters
                    : const <String, dynamic>{};
                final currFilters = curr is DiscoveryLoaded
                    ? curr.activeFilters
                    : const <String, dynamic>{};
                return prevFilters != currFilters;
              },
              builder: (context, state) {
                final activeFilters = state is DiscoveryLoaded
                    ? state.activeFilters
                    : const <String, dynamic>{};
                final activeCategory = activeFilters['category'] as String?;

                return FilterBar(
                  filters: _categoryFilters,
                  activeFilters: activeCategory != null
                      ? {activeCategory}
                      : const {},
                  onFilterChanged: _onFilterChanged,
                  onClearAll: _onClearAll,
                );
              },
            ),
            Expanded(
              child: RefreshIndicator(
                color: BookmiColors.brandBlue,
                onRefresh: () async {
                  context.read<DiscoveryBloc>().add(const DiscoveryFetched());
                  // Wait for state change
                  await context.read<DiscoveryBloc>().stream.firstWhere(
                    (state) =>
                        state is DiscoveryLoaded || state is DiscoveryFailure,
                  );
                },
                child: BlocBuilder<DiscoveryBloc, DiscoveryState>(
                  builder: (context, state) {
                    return switch (state) {
                      DiscoveryInitial() ||
                      DiscoveryLoading() => TalentGrid.skeleton(),
                      DiscoveryLoaded() =>
                        state.talents.isEmpty
                            ? _buildEmptyState()
                            : TalentGrid(
                                talents: state.talents,
                                hasMore: state.hasMore,
                                isLoadingMore: state is DiscoveryLoadingMore,
                                scrollController: _scrollController,
                                onTalentTap: _onTalentTap,
                              ),
                      DiscoveryFailure(:final message) => _buildErrorState(
                        message,
                      ),
                    };
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return CustomScrollView(
      controller: _scrollController,
      physics: const AlwaysScrollableScrollPhysics(),
      slivers: [
        SliverFillRemaining(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(BookmiSpacing.spaceXl),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.search_off,
                    size: 64,
                    color: Colors.white.withValues(alpha: 0.3),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceBase),
                  const Text(
                    'Aucun talent trouvé',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  Text(
                    "Essayez 'DJ' ou 'Musicien'",
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withValues(alpha: 0.6),
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceLg),
                  OutlinedButton(
                    onPressed: _onClearAll,
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: BookmiColors.brandBlue),
                      foregroundColor: BookmiColors.brandBlue,
                    ),
                    child: const Text('Élargir la recherche'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildErrorState(String message) {
    return CustomScrollView(
      controller: _scrollController,
      physics: const AlwaysScrollableScrollPhysics(),
      slivers: [
        SliverFillRemaining(
          child: Center(
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
                    onPressed: () => context.read<DiscoveryBloc>().add(
                      const DiscoveryFetched(),
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
          ),
        ),
      ],
    );
  }
}
