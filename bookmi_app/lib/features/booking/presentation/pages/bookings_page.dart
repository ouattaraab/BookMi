import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_bloc.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_event.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_state.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/booking_card.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/booking_card_skeleton.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:bookmi_app/app/routes/route_names.dart';

class BookingsPage extends StatelessWidget {
  const BookingsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => BookingsListBloc(
        repository: context.read<BookingRepository>(),
      )..add(const BookingsListFetched()),
      child: const _BookingsView(),
    );
  }
}

class _BookingsView extends StatefulWidget {
  const _BookingsView();

  @override
  State<_BookingsView> createState() => _BookingsViewState();
}

class _BookingsViewState extends State<_BookingsView>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  static const _tabs = [
    _Tab(label: 'En attente', status: 'pending'),
    _Tab(label: 'Confirmées', status: 'accepted'),
    _Tab(label: 'Passées', status: 'completed'),
    _Tab(label: 'Annulées', status: 'cancelled'),
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _tabs.length, vsync: this);
    _tabController.addListener(_onTabChanged);
  }

  void _onTabChanged() {
    if (_tabController.indexIsChanging) return;
    context.read<BookingsListBloc>().add(
      BookingsListFetched(status: _tabs[_tabController.index].status),
    );
  }

  @override
  void dispose() {
    _tabController
      ..removeListener(_onTabChanged)
      ..dispose();
    super.dispose();
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
          title: const Text(
            'Mes réservations',
            style: TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.w600,
            ),
          ),
          bottom: TabBar(
            controller: _tabController,
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            indicatorColor: BookmiColors.brandBlue,
            indicatorWeight: 2,
            labelColor: Colors.white,
            unselectedLabelColor: Colors.white54,
            labelStyle: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
            unselectedLabelStyle: const TextStyle(fontSize: 13),
            tabs: _tabs.map((t) => Tab(text: t.label)).toList(),
          ),
        ),
        body: TabBarView(
          controller: _tabController,
          children: _tabs
              .map(
                (t) => _BookingsTab(
                  status: t.status,
                  tabController: _tabController,
                  tabIndex: _tabs.indexOf(t),
                ),
              )
              .toList(),
        ),
      ),
    );
  }
}

class _BookingsTab extends StatelessWidget {
  const _BookingsTab({
    required this.status,
    required this.tabController,
    required this.tabIndex,
  });

  final String status;
  final TabController tabController;
  final int tabIndex;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<BookingsListBloc, BookingsListState>(
      builder: (context, state) {
        if (state is BookingsListLoading) {
          return _buildSkeletons();
        }
        if (state is BookingsListFailure) {
          return _buildError(context, state.message);
        }
        if (state is BookingsListLoaded) {
          if (state.bookings.isEmpty) {
            return _buildEmpty();
          }
          return RefreshIndicator(
            color: BookmiColors.brandBlue,
            onRefresh: () async {
              final bloc = context.read<BookingsListBloc>();
              // Skip current state — wait for the NEXT state emitted after
              // the refresh request to avoid completing prematurely.
              final future = bloc.stream.firstWhere(
                (s) => s is BookingsListLoaded || s is BookingsListFailure,
              );
              bloc.add(BookingsListFetched(status: status));
              await future;
            },
            child: NotificationListener<ScrollNotification>(
              onNotification: (notification) {
                if (notification is ScrollEndNotification &&
                    notification.metrics.pixels >=
                        notification.metrics.maxScrollExtent - 200) {
                  context.read<BookingsListBloc>().add(
                    const BookingsListNextPageFetched(),
                  );
                }
                return false;
              },
              child: ListView.separated(
                padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
                itemCount: state.bookings.length +
                    (state is BookingsListLoadingMore ? 1 : 0),
                separatorBuilder: (_, __) =>
                    const SizedBox(height: BookmiSpacing.spaceSm),
                itemBuilder: (context, index) {
                  if (index >= state.bookings.length) {
                    return const Padding(
                      padding: EdgeInsets.all(BookmiSpacing.spaceBase),
                      child: Center(
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: BookmiColors.brandBlue,
                        ),
                      ),
                    );
                  }
                  final booking = state.bookings[index];
                  return BookingCard(
                    booking: booking,
                    onTap: () => context.pushNamed(
                      RouteNames.bookingDetail,
                      pathParameters: {'id': '${booking.id}'},
                      extra: booking,
                    ),
                  );
                },
              ),
            ),
          );
        }
        return _buildSkeletons();
      },
    );
  }

  Widget _buildSkeletons() {
    return ListView.separated(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      itemCount: 4,
      separatorBuilder: (_, __) => const SizedBox(height: BookmiSpacing.spaceSm),
      itemBuilder: (_, __) => const BookingCardSkeleton(),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(BookmiSpacing.spaceXl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.event_busy_outlined,
              size: 64,
              color: Colors.white.withValues(alpha: 0.25),
            ),
            const SizedBox(height: BookmiSpacing.spaceBase),
            Text(
              'Aucune réservation',
              style: TextStyle(
                fontSize: 16,
                color: Colors.white.withValues(alpha: 0.6),
              ),
            ),
          ],
        ),
      ),
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
              size: 48,
              color: Colors.white.withValues(alpha: 0.3),
            ),
            const SizedBox(height: BookmiSpacing.spaceBase),
            Text(
              message,
              style: const TextStyle(color: Colors.white70),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: BookmiSpacing.spaceLg),
            TextButton(
              onPressed: () => context.read<BookingsListBloc>().add(
                BookingsListFetched(status: status),
              ),
              child: const Text(
                'Réessayer',
                style: TextStyle(color: BookmiColors.brandBlueLight),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Tab {
  const _Tab({required this.label, required this.status});
  final String label;
  final String status;
}
