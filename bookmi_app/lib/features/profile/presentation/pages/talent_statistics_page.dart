import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _primary = Color(0xFF3B9DF2);
const _success = Color(0xFF14B8A6);
const _warning = Color(0xFFFBBF24);
const _border = Color(0xFFE2E8F0);

class TalentStatisticsPage extends StatefulWidget {
  const TalentStatisticsPage({required this.repository, super.key});
  final ProfileRepository repository;

  @override
  State<TalentStatisticsPage> createState() =>
      _TalentStatisticsPageState();
}

class _TalentStatisticsPageState extends State<TalentStatisticsPage> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await widget.repository.getFinancialDashboard();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _data = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _muted,
      appBar: AppBar(
        backgroundColor: _secondary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          'Statistiques talent',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, size: 20),
            onPressed: _load,
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              _error!,
              style: GoogleFonts.manrope(color: _mutedFg),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: _load,
              child: Text(
                'Réessayer',
                style: GoogleFonts.manrope(color: _primary),
              ),
            ),
          ],
        ),
      );
    }

    final d = _data ?? {};
    final revenusTotal = (d['revenus_total'] as int?) ?? 0;
    final revenusMoisCourant = (d['revenus_mois_courant'] as int?) ?? 0;
    final revenusMoisPrecedent =
        (d['revenus_mois_precedent'] as int?) ?? 0;
    final comparaison =
        (d['comparaison_pourcentage'] as num?)?.toDouble() ?? 0;
    final nombrePrestations = (d['nombre_prestations'] as int?) ?? 0;
    final cachetMoyen = (d['cachet_moyen'] as int?) ?? 0;
    final mensuels = ((d['mensuels'] as List?)
            ?.cast<Map<String, dynamic>>()) ??
        [];

    final fmt = NumberFormat('#,###', 'fr_FR');

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Revenue this month
          _StatCard(
            label: 'Revenus ce mois',
            value: '${fmt.format(revenusMoisCourant)} FCFA',
            icon: Icons.account_balance_wallet_outlined,
            color: _primary,
            subtitle: comparaison != 0
                ? '${comparaison > 0 ? '+' : ''}${comparaison.toStringAsFixed(1)}% vs mois précédent'
                : null,
            subtitlePositive: comparaison >= 0,
          ),
          const SizedBox(height: 12),
          // Grid: total + prestations + moyen + mois précédent
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: 'Revenus totaux',
                  value: '${fmt.format(revenusTotal)} FCFA',
                  icon: Icons.savings_outlined,
                  color: _success,
                  compact: true,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _StatCard(
                  label: 'Prestations',
                  value: '$nombrePrestations',
                  icon: Icons.star_outline,
                  color: _warning,
                  compact: true,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: 'Cachet moyen',
                  value: '${fmt.format(cachetMoyen)} FCFA',
                  icon: Icons.paid_outlined,
                  color: const Color(0xFF8B5CF6),
                  compact: true,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _StatCard(
                  label: 'Mois précédent',
                  value: '${fmt.format(revenusMoisPrecedent)} FCFA',
                  icon: Icons.history,
                  color: _mutedFg,
                  compact: true,
                ),
              ),
            ],
          ),
          if (mensuels.isNotEmpty) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: _border),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Revenus des 6 derniers mois',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: _secondary,
                    ),
                  ),
                  const SizedBox(height: 16),
                  _RevenueBarChart(mensuels: mensuels),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
    this.subtitle,
    this.subtitlePositive = true,
    this.compact = false,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final String? subtitle;
  final bool subtitlePositive;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(compact ? 12 : 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: color),
          SizedBox(height: compact ? 6 : 10),
          Text(
            value,
            style: GoogleFonts.plusJakartaSans(
              fontSize: compact ? 14 : 18,
              fontWeight: FontWeight.w700,
              color: _secondary,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: 11,
              color: _mutedFg,
            ),
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 4),
            Text(
              subtitle!,
              style: GoogleFonts.manrope(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: subtitlePositive ? _success : Colors.red,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _RevenueBarChart extends StatelessWidget {
  const _RevenueBarChart({required this.mensuels});
  final List<Map<String, dynamic>> mensuels;

  @override
  Widget build(BuildContext context) {
    final maxRevenue = mensuels
        .map((m) => (m['revenus'] as int?) ?? 0)
        .fold(0, (a, b) => a > b ? a : b);

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: mensuels.map((m) {
        final rev = (m['revenus'] as int?) ?? 0;
        final ratio = maxRevenue > 0 ? rev / maxRevenue : 0.0;
        final moisStr = m['mois'] as String? ?? '';
        final label = _shortMonth(moisStr);
        final isLast = m == mensuels.last;

        return Expanded(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 2),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                SizedBox(
                  height: 60,
                  child: Align(
                    alignment: Alignment.bottomCenter,
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 400),
                      height: (60 * ratio).clamp(4.0, 60.0),
                      decoration: BoxDecoration(
                        color: isLast
                            ? _primary
                            : _primary.withValues(alpha: 0.25),
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  label,
                  style: GoogleFonts.manrope(
                    fontSize: 10,
                    color: isLast ? _secondary : _mutedFg,
                    fontWeight: isLast
                        ? FontWeight.w600
                        : FontWeight.normal,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  static String _shortMonth(String yyyyMm) {
    if (yyyyMm.length < 7) return yyyyMm;
    final parts = yyyyMm.split('-');
    if (parts.length < 2) return yyyyMm;
    const names = [
      '',
      'Jan',
      'Fév',
      'Mar',
      'Avr',
      'Mai',
      'Jun',
      'Jul',
      'Aoû',
      'Sep',
      'Oct',
      'Nov',
      'Déc',
    ];
    final month = int.tryParse(parts[1]) ?? 0;
    return month >= 1 && month <= 12 ? names[month] : yyyyMm;
  }
}
