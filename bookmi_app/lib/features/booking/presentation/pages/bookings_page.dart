import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_bloc.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_event.dart';
import 'package:bookmi_app/features/booking/bloc/bookings_list/bookings_list_state.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/booking_card.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/booking_card_skeleton.dart';
import 'package:bookmi_app/features/profile/bloc/profile_bloc.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:url_launcher/url_launcher.dart';

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
    _Tab(label: 'Toutes'),
    _Tab(label: 'En attente', status: 'pending'),
    _Tab(label: 'Validée', status: 'accepted'),
    _Tab(label: 'Confirmée', status: 'paid,confirmed'),
    _Tab(label: 'Terminée', status: 'completed'),
    _Tab(label: 'Annulée', status: 'cancelled,rejected'),
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
    final authState = context.watch<AuthBloc>().state;
    final isTalent = authState is AuthAuthenticated &&
        authState.roles.contains('talent');

    // Pending count from ProfileBloc (available at shell level)
    int pendingCount = 0;
    try {
      final profileState = context.watch<ProfileBloc>().state;
      if (profileState is ProfileLoaded) {
        pendingCount = profileState.stats.pendingBookingCount;
      }
    } catch (_) {
      // ProfileBloc may not be available in some test contexts
    }

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Page title
          Padding(
            padding: EdgeInsets.fromLTRB(
              20,
              MediaQuery.of(context).padding.top + 8,
              20,
              16,
            ),
            child: const Text(
              'Mes Réservations',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w900,
                color: Colors.white,
                letterSpacing: -0.5,
              ),
            ),
          ),
          // Segmented glass tab control
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: AnimatedBuilder(
              animation: _tabController,
              builder: (context, _) {
                return Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.05),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.08),
                    ),
                  ),
                  child: SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: _tabs.asMap().entries.map((entry) {
                        final i = entry.key;
                        final t = entry.value;
                        final isActive = i == _tabController.index;
                        return GestureDetector(
                          onTap: () => _tabController.animateTo(i),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 14,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              color: isActive
                                  ? BookmiColors.brandBlueLight
                                  : Colors.transparent,
                              borderRadius: BorderRadius.circular(10),
                              boxShadow: isActive
                                  ? [
                                      BoxShadow(
                                        color: BookmiColors.brandBlueLight
                                            .withValues(alpha: 0.35),
                                        blurRadius: 12,
                                        offset: const Offset(0, 4),
                                      ),
                                    ]
                                  : null,
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  t.label,
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w800,
                                    color: isActive
                                        ? Colors.white
                                        : Colors.white.withValues(alpha: 0.45),
                                  ),
                                ),
                                if (i == 1 && isTalent && pendingCount > 0) ...[
                                  const SizedBox(width: 4),
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 5,
                                      vertical: 1,
                                    ),
                                    decoration: BoxDecoration(
                                      color: isActive
                                          ? Colors.white.withValues(alpha: 0.3)
                                          : BookmiColors.brandBlueLight,
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text(
                                      pendingCount > 99 ? '99+' : '$pendingCount',
                                      style: const TextStyle(
                                        fontSize: 9,
                                        fontWeight: FontWeight.w700,
                                        color: Colors.white,
                                      ),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                );
              },
            ),
          ),
          // Tab content
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: _tabs
                  .map(
                    (t) => _BookingsTab(
                      status: t.status,
                      tabController: _tabController,
                      tabIndex: _tabs.indexOf(t),
                      isTalent: isTalent,
                    ),
                  )
                  .toList(),
            ),
          ),
        ],
      ),
    );
  }
}

class _BookingsTab extends StatelessWidget {
  const _BookingsTab({
    required this.status,
    required this.tabController,
    required this.tabIndex,
    required this.isTalent,
  });

  final String? status;
  final TabController tabController;
  final int tabIndex;
  final bool isTalent;

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
                  final isPending = booking.status == 'pending';
                  final isAccepted = booking.status == 'accepted';
                  return BookingCard(
                    booking: booking,
                    onTap: () => context.pushNamed(
                      RouteNames.bookingDetail,
                      pathParameters: {'id': '${booking.id}'},
                      extra: booking,
                    ),
                    onAccept: isTalent && isPending
                        ? () => _handleAccept(context, booking.id)
                        : null,
                    onReject: isTalent && isPending
                        ? () => _handleReject(context, booking.id)
                        : null,
                    onPay: !isTalent && isAccepted
                        ? () => _handlePay(context, booking.id)
                        : null,
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

  Future<void> _handleAccept(BuildContext context, int bookingId) async {
    final repo = context.read<BookingRepository>();
    final result = await repo.acceptBooking(bookingId);
    if (!context.mounted) return;
    switch (result) {
      case ApiSuccess():
        // Refresh current tab
        context.read<BookingsListBloc>().add(
          BookingsListFetched(status: status),
        );
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Réservation acceptée avec succès'),
            backgroundColor: Color(0xFF14B8A6),
          ),
        );
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: const Color(0xFFEF4444),
          ),
        );
    }
  }

  Future<void> _handleReject(BuildContext context, int bookingId) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text(
          'Refuser la réservation',
          style: TextStyle(
            fontWeight: FontWeight.w700,
            fontSize: 16,
          ),
        ),
        content: const Text(
          'Voulez-vous vraiment refuser cette demande de réservation ?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text(
              'Refuser',
              style: TextStyle(color: Color(0xFFEF4444)),
            ),
          ),
        ],
      ),
    );
    if (confirmed != true || !context.mounted) return;

    final repo = context.read<BookingRepository>();
    final result = await repo.rejectBooking(bookingId);
    if (!context.mounted) return;
    switch (result) {
      case ApiSuccess():
        context.read<BookingsListBloc>().add(
          BookingsListFetched(status: status),
        );
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Réservation refusée'),
          ),
        );
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: const Color(0xFFEF4444),
          ),
        );
    }
  }

  Future<void> _handlePay(BuildContext context, int bookingId) async {
    // Show loading while we create the Paystack transaction on the backend.
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Row(
          children: [
            SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
            ),
            SizedBox(width: 12),
            Text('Initialisation du paiement…'),
          ],
        ),
        duration: Duration(seconds: 20),
      ),
    );

    final repo = context.read<BookingRepository>();
    final result = await repo.initiatePayment(bookingId: bookingId);
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).hideCurrentSnackBar();

    switch (result) {
      case ApiSuccess(:final data):
        // Extract authorization_url — TransactionResource places it directly under data.
        final txData = data['data'] as Map<String, dynamic>?;
        final authUrl = txData?['authorization_url'] as String?
            ?? data['authorization_url'] as String?;

        if (authUrl == null || authUrl.isEmpty) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Erreur : URL de paiement manquante.'),
              backgroundColor: Color(0xFFEF4444),
            ),
          );
          return;
        }

        // Open Paystack checkout in a Chrome Custom Tab.
        // The user pays, then returns to BookMi; we then verify the result.
        final uri = Uri.parse(authUrl);
        final launched = await launchUrl(
          uri,
          mode: LaunchMode.externalApplication,
        );

        if (!context.mounted) return;

        if (!launched) {
          await repo.cancelPayment(bookingId: bookingId);
          if (!context.mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Impossible d\'ouvrir la page de paiement.'),
              backgroundColor: Color(0xFFEF4444),
            ),
          );
          return;
        }

        // Paystack checkout is now open in the browser. Show a dialog when the
        // user returns so we can verify the payment status.
        final confirmed = await showDialog<bool>(
          context: context,
          barrierDismissible: false,
          builder: (ctx) => AlertDialog(
            backgroundColor: const Color(0xFF0D1421),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: const Text(
              'Paiement effectué ?',
              style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
            ),
            content: const Text(
              'Avez-vous complété le paiement sur la page Paystack ?',
              style: TextStyle(color: Colors.white70),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(ctx).pop(false),
                child: const Text(
                  'Non, annuler',
                  style: TextStyle(color: Colors.white54),
                ),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: BookmiColors.brandBlueLight,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                onPressed: () => Navigator.of(ctx).pop(true),
                child: const Text('Oui, vérifier'),
              ),
            ],
          ),
        );

        if (!context.mounted) return;

        if (confirmed != true) {
          // User says they didn't pay — cancel the transaction so retry works.
          await repo.cancelPayment(bookingId: bookingId);
          return;
        }

        // User confirmed payment — refresh bookings list to reflect new status.
        context.read<BookingsListBloc>().add(BookingsListFetched(status: status));
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Vérification du paiement…'),
            duration: Duration(seconds: 3),
          ),
        );

      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: const Color(0xFFEF4444),
          ),
        );
    }
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
  const _Tab({required this.label, this.status});
  final String label;
  final String? status;
}
