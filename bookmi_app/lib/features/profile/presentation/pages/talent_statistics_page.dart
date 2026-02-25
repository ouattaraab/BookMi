import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

const _secondary = Color(0xFFE8F0FF);
const _muted = Color(0xFF112044);
const _mutedFg = Color(0xFF8FA3C0);
const _primary = Color(0xFF3B9DF2);
const _success = Color(0xFF14B8A6);
const _warning = Color(0xFFFBBF24);
const _border = Color(0x1AFFFFFF);

class TalentStatisticsPage extends StatefulWidget {
  const TalentStatisticsPage({required this.repository, super.key});
  final ProfileRepository repository;

  @override
  State<TalentStatisticsPage> createState() => _TalentStatisticsPageState();
}

class _TalentStatisticsPageState extends State<TalentStatisticsPage> {
  Map<String, dynamic>? _data;
  ProfileStats? _stats;
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

    // Fetch both endpoints in parallel
    final results = await Future.wait([
      widget.repository.getFinancialDashboard(),
      widget.repository.getStats(isTalent: true),
    ]);

    if (!mounted) return;

    final finResult = results[0] as ApiResult<Map<String, dynamic>>;
    final statsResult = results[1] as ApiResult<ProfileStats>;

    switch (finResult) {
      case ApiSuccess(:final data):
        setState(() {
          _data = data;
          if (statsResult case ApiSuccess(:final data)) {
            _stats = data;
          }
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
        backgroundColor: const Color(0xFF0D1B38),
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
    final revenusMoisPrecedent = (d['revenus_mois_precedent'] as int?) ?? 0;
    final comparaison = (d['comparaison_pourcentage'] as num?)?.toDouble() ?? 0;
    final nombrePrestations = (d['nombre_prestations'] as int?) ?? 0;
    final cachetMoyen = (d['cachet_moyen'] as int?) ?? 0;
    final mensuels =
        ((d['mensuels'] as List?)?.cast<Map<String, dynamic>>()) ?? [];

    final bookingCount = _stats?.bookingCount ?? 0;
    final viewsToday = _stats?.profileViewsToday ?? 0;
    final viewsWeek = _stats?.profileViewsWeek ?? 0;
    final viewsMonth = _stats?.profileViewsMonth ?? 0;
    final viewsTotal = _stats?.profileViewsTotal ?? 0;

    final fmt = NumberFormat('#,###', 'fr_FR');

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // ── Réservations ────────────────────────────────────────────
          _SectionTitle(title: 'Réservations'),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: 'Réservations totales',
                  value: '$bookingCount',
                  icon: Icons.calendar_today_outlined,
                  color: _primary,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _StatCard(
                  label: 'Prestations réalisées',
                  value: '$nombrePrestations',
                  icon: Icons.star_outline,
                  color: _warning,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // ── Vues du profil ──────────────────────────────────────────
          _SectionTitle(title: 'Vues du profil'),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: "Aujourd'hui",
                  value: '$viewsToday',
                  icon: Icons.visibility_outlined,
                  color: _success,
                  compact: true,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _StatCard(
                  label: 'Cette semaine',
                  value: '$viewsWeek',
                  icon: Icons.bar_chart_outlined,
                  color: const Color(0xFF8B5CF6),
                  compact: true,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: 'Ce mois',
                  value: '$viewsMonth',
                  icon: Icons.calendar_month_outlined,
                  color: _primary,
                  compact: true,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _StatCard(
                  label: 'Total',
                  value: '$viewsTotal',
                  icon: Icons.people_outline,
                  color: _warning,
                  compact: true,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // ── Revenus ─────────────────────────────────────────────────
          _SectionTitle(title: 'Revenus'),
          const SizedBox(height: 8),
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
          const SizedBox(height: 10),
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
                  label: 'Cachet moyen',
                  value: '${fmt.format(cachetMoyen)} FCFA',
                  icon: Icons.paid_outlined,
                  color: const Color(0xFF8B5CF6),
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
                  label: 'Mois précédent',
                  value: '${fmt.format(revenusMoisPrecedent)} FCFA',
                  icon: Icons.history,
                  color: _mutedFg,
                  compact: true,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(child: const SizedBox()),
            ],
          ),
          if (mensuels.isNotEmpty) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF0D1B38),
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
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: GoogleFonts.plusJakartaSans(
        fontSize: 13,
        fontWeight: FontWeight.w700,
        color: _mutedFg,
        letterSpacing: 0.5,
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
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _border),
        boxShadow: [
          BoxShadow(
            color: const Color(0x08FFFFFF),
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
                    fontWeight: isLast ? FontWeight.w600 : FontWeight.normal,
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
