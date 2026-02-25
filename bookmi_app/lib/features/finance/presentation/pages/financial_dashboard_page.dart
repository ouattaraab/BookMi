import 'dart:math' show max;

import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/finance/bloc/financial_dashboard_cubit.dart';
import 'package:bookmi_app/features/finance/bloc/financial_dashboard_state.dart';
import 'package:bookmi_app/features/finance/data/models/financial_dashboard_model.dart';
import 'package:bookmi_app/features/finance/data/models/payout_model.dart';
import 'package:bookmi_app/features/finance/data/repositories/financial_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Talent financial dashboard — revenues, monthly chart, payout history.
class FinancialDashboardPage extends StatelessWidget {
  const FinancialDashboardPage({required this.repository, super.key});

  final FinancialRepository repository;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) {
        final cubit = FinancialDashboardCubit(repository: repository);
        cubit.load(); // ignore: discarded_futures
        return cubit;
      },
      child: const _FinancialDashboardView(),
    );
  }
}

class _FinancialDashboardView extends StatelessWidget {
  const _FinancialDashboardView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BookmiColors.backgroundDeep,
      appBar: AppBar(
        backgroundColor: BookmiColors.backgroundDeep,
        elevation: 0,
        title: const Text(
          'Mes revenus',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white70),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: BlocBuilder<FinancialDashboardCubit, FinancialDashboardState>(
        builder: (context, state) => switch (state) {
          FinancialDashboardInitial() ||
          FinancialDashboardLoading() => const Center(
            child: CircularProgressIndicator(color: BookmiColors.brandBlue),
          ),
          FinancialDashboardError(:final message) => Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.cloud_off,
                  color: Colors.white38,
                  size: 48,
                ),
                const SizedBox(height: BookmiSpacing.spaceSm),
                Text(
                  message,
                  style: const TextStyle(color: Colors.white54),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),
                TextButton(
                  onPressed: () =>
                      context.read<FinancialDashboardCubit>().load(),
                  child: const Text('Réessayer'),
                ),
              ],
            ),
          ),
          FinancialDashboardLoaded(:final dashboard, :final payouts) =>
            _LoadedBody(dashboard: dashboard, payouts: payouts),
        },
      ),
    );
  }
}

class _LoadedBody extends StatelessWidget {
  const _LoadedBody({required this.dashboard, required this.payouts});

  final FinancialDashboardModel dashboard;
  final List<PayoutModel> payouts;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      children: [
        _TotalRevenueCard(dashboard: dashboard),
        const SizedBox(height: BookmiSpacing.spaceMd),
        _MonthlyChartCard(mensuels: dashboard.mensuels),
        const SizedBox(height: BookmiSpacing.spaceMd),
        _StatsRow(dashboard: dashboard),
        const SizedBox(height: BookmiSpacing.spaceMd),
        if (payouts.isNotEmpty) ...[
          const Text(
            'Historique des versements',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceSm),
          ...payouts.map((p) => _PayoutListItem(payout: p)),
        ],
      ],
    );
  }
}

// ── Total Revenue Card ────────────────────────────────────────────────────────

class _TotalRevenueCard extends StatelessWidget {
  const _TotalRevenueCard({required this.dashboard});

  final FinancialDashboardModel dashboard;

  @override
  Widget build(BuildContext context) {
    final pct = dashboard.comparaisonPourcentage;
    final isPositive = pct >= 0;
    final pctLabel =
        '${isPositive ? '+' : ''}${pct.toStringAsFixed(1)} % vs mois préc.';

    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Revenus totaux',
            style: TextStyle(fontSize: 13, color: Colors.white60),
          ),
          const SizedBox(height: BookmiSpacing.spaceXs),
          Text(
            TalentCard.formatCachet(dashboard.revenusTotal),
            style: const TextStyle(
              fontSize: 32,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceSm),
          Row(
            children: [
              // Current month
              Expanded(
                child: _MonthChip(
                  label: 'Ce mois',
                  amount: dashboard.revenusMoisCourant,
                ),
              ),
              const SizedBox(width: BookmiSpacing.spaceSm),
              // Month comparison badge
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: BookmiSpacing.spaceSm,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: isPositive
                      ? BookmiColors.success.withValues(alpha: 0.15)
                      : BookmiColors.error.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: isPositive
                        ? BookmiColors.success.withValues(alpha: 0.4)
                        : BookmiColors.error.withValues(alpha: 0.4),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      isPositive ? Icons.trending_up : Icons.trending_down,
                      size: 14,
                      color:
                          isPositive ? BookmiColors.success : BookmiColors.error,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      pctLabel,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: isPositive
                            ? BookmiColors.success
                            : BookmiColors.error,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MonthChip extends StatelessWidget {
  const _MonthChip({required this.label, required this.amount});

  final String label;
  final int amount;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 11, color: Colors.white54),
        ),
        Text(
          TalentCard.formatCachet(amount),
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
      ],
    );
  }
}

// ── Monthly Bar Chart ─────────────────────────────────────────────────────────

class _MonthlyChartCard extends StatelessWidget {
  const _MonthlyChartCard({required this.mensuels});

  final List<MonthlyRevenue> mensuels;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Revenus (6 derniers mois)',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),
          SizedBox(
            height: 140,
            child: CustomPaint(
              size: const Size(double.infinity, 140),
              painter: _RevenueBarChartPainter(mensuels: mensuels),
            ),
          ),
        ],
      ),
    );
  }
}

/// Draws a simple vertical bar chart for 6-month revenue data.
class _RevenueBarChartPainter extends CustomPainter {
  _RevenueBarChartPainter({required this.mensuels});

  final List<MonthlyRevenue> mensuels;

  static const _monthAbbr = [
    'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun',
    'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc',
  ];

  @override
  void paint(Canvas canvas, Size size) {
    if (mensuels.isEmpty) return;

    final maxRevenue = mensuels.map((m) => m.revenue).fold(0, max);
    final chartHeight = size.height * 0.78;
    final labelHeight = size.height - chartHeight;

    final slotWidth = size.width / mensuels.length;
    final barWidth = slotWidth * 0.5;

    final barPaint = Paint()
      ..shader = const LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [BookmiColors.brandBlue, BookmiColors.brandBlueDark],
      ).createShader(Rect.fromLTWH(0, 0, size.width, chartHeight));

    final zeroPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.08);

    const labelStyle = TextStyle(
      color: Colors.white54,
      fontSize: 10,
      fontWeight: FontWeight.w400,
    );

    for (var i = 0; i < mensuels.length; i++) {
      final m = mensuels[i];
      final x = slotWidth * i + (slotWidth - barWidth) / 2;

      // Background bar (full height)
      canvas.drawRRect(
        RRect.fromRectAndRadius(
          Rect.fromLTWH(x, 0, barWidth, chartHeight),
          const Radius.circular(4),
        ),
        zeroPaint,
      );

      // Filled bar
      if (m.revenue > 0 && maxRevenue > 0) {
        final fraction = m.revenue / maxRevenue;
        final barH = chartHeight * fraction;
        canvas.drawRRect(
          RRect.fromRectAndRadius(
            Rect.fromLTWH(x, chartHeight - barH, barWidth, barH),
            const Radius.circular(4),
          ),
          barPaint,
        );
      }

      // Month label
      final monthIndex = int.tryParse(m.month.substring(5)) ?? 1;
      final label = _monthAbbr[(monthIndex - 1).clamp(0, 11)];

      final tp = TextPainter(
        text: TextSpan(text: label, style: labelStyle),
        textDirection: TextDirection.ltr,
      )..layout();

      tp.paint(
        canvas,
        Offset(
          x + barWidth / 2 - tp.width / 2,
          chartHeight + (labelHeight - tp.height) / 2,
        ),
      );
    }
  }

  @override
  bool shouldRepaint(covariant _RevenueBarChartPainter old) =>
      mensuels != old.mensuels;
}

// ── Stats Row ─────────────────────────────────────────────────────────────────

class _StatsRow extends StatelessWidget {
  const _StatsRow({required this.dashboard});

  final FinancialDashboardModel dashboard;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _StatCard(
            icon: Icons.music_note_rounded,
            label: 'Prestations',
            value: '${dashboard.nombrePrestations}',
          ),
        ),
        const SizedBox(width: BookmiSpacing.spaceSm),
        Expanded(
          child: _StatCard(
            icon: Icons.payments_outlined,
            label: 'Cachet moyen',
            value: TalentCard.formatCachet(dashboard.cachetMoyen),
          ),
        ),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: BookmiColors.brandBlueLight),
          const SizedBox(height: BookmiSpacing.spaceXs),
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: Colors.white54),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Payout List Item ──────────────────────────────────────────────────────────

class _PayoutListItem extends StatelessWidget {
  const _PayoutListItem({required this.payout});

  final PayoutModel payout;

  static const _statusColors = {
    'succeeded': BookmiColors.success,
    'pending': BookmiColors.warning,
    'failed': BookmiColors.error,
  };

  static const _statusLabels = {
    'succeeded': 'Versé',
    'pending': 'En cours',
    'failed': 'Échoué',
  };

  String _formatDate(String? iso) {
    if (iso == null) return '—';
    try {
      final dt = DateTime.parse(iso).toLocal();
      const months = [
        'jan', 'fév', 'mar', 'avr', 'mai', 'jun',
        'jul', 'aoû', 'sep', 'oct', 'nov', 'déc',
      ];
      return '${dt.day} ${months[dt.month - 1]}. ${dt.year}';
    } on FormatException catch (_) {
      return '—';
    }
  }

  @override
  Widget build(BuildContext context) {
    final color = _statusColors[payout.status] ?? Colors.white54;
    final label = _statusLabels[payout.status] ?? payout.status;

    return Padding(
      padding: const EdgeInsets.only(bottom: BookmiSpacing.spaceSm),
      child: GlassCard(
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: color.withValues(alpha: 0.15),
              ),
              child: Icon(Icons.account_balance_wallet_outlined,
                  size: 18, color: color),
            ),
            const SizedBox(width: BookmiSpacing.spaceSm),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Versement #${payout.id}',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  Text(
                    _formatDate(payout.processedAt),
                    style: const TextStyle(fontSize: 11, color: Colors.white54),
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  TalentCard.formatCachet(payout.amount),
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    label,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: color,
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
