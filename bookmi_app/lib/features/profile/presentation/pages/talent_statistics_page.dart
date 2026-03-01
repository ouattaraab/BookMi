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
  Map<String, dynamic>? _analytics;
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

    // Fetch all three endpoints in parallel
    final results = await Future.wait([
      widget.repository.getFinancialDashboard(),
      widget.repository.getStats(isTalent: true),
      widget.repository.getAnalytics(),
    ]);

    if (!mounted) return;

    final finResult = results[0] as ApiResult<Map<String, dynamic>>;
    final statsResult = results[1] as ApiResult<ProfileStats>;
    final analyticsResult = results[2] as ApiResult<Map<String, dynamic>>;

    switch (finResult) {
      case ApiSuccess(:final data):
        setState(() {
          _data = data;
          if (statsResult case ApiSuccess(:final data)) {
            _stats = data;
          }
          if (analyticsResult case ApiSuccess(:final data)) {
            _analytics = data;
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
          // ── Top villes ──────────────────────────────────────────────
          if (_analytics != null) ...[
            const SizedBox(height: 16),
            _TopCitiesCard(analytics: _analytics!),
            const SizedBox(height: 16),
            _BookingStatusCard(analytics: _analytics!),
            const SizedBox(height: 16),
            _RatingHistoryCard(analytics: _analytics!),
          ],
          const SizedBox(height: 16),
          // ── Visibilité ───────────────────────────────────────────────
          _SectionTitle(title: 'Visibilité'),
          const SizedBox(height: 8),
          _VisibilityScoreCard(score: _stats?.visibilityScore ?? 0.0),
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

// ── Top villes ──────────────────────────────────────────────────────────────

class _TopCitiesCard extends StatelessWidget {
  const _TopCitiesCard({required this.analytics});
  final Map<String, dynamic> analytics;

  @override
  Widget build(BuildContext context) {
    final topCities =
        ((analytics['top_cities'] as List?)?.cast<Map<String, dynamic>>()) ??
        [];
    if (topCities.isEmpty) return const SizedBox.shrink();

    final maxCount = topCities
        .map((c) => (c['count'] as int?) ?? 0)
        .fold(0, (a, b) => a > b ? a : b);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.location_on_outlined, size: 16, color: _primary),
              const SizedBox(width: 6),
              Text(
                'Villes des prestations',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: _secondary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ...topCities.map((c) {
            final city = (c['city'] as String?) ?? '—';
            final count = (c['count'] as int?) ?? 0;
            final ratio = maxCount > 0 ? count / maxCount : 0.0;
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        city,
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          color: _secondary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      Text(
                        '$count prestation${count > 1 ? 's' : ''}',
                        style: GoogleFonts.manrope(
                          fontSize: 11,
                          color: _mutedFg,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: ratio,
                      backgroundColor: const Color(0xFF1A2E54),
                      valueColor: const AlwaysStoppedAnimation<Color>(_primary),
                      minHeight: 6,
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }
}

// ── Répartition des réservations ────────────────────────────────────────────

class _BookingStatusCard extends StatelessWidget {
  const _BookingStatusCard({required this.analytics});
  final Map<String, dynamic> analytics;

  @override
  Widget build(BuildContext context) {
    final byStatus =
        (analytics['bookings_by_status'] as Map<String, dynamic>?) ?? {};
    if (byStatus.isEmpty) return const SizedBox.shrink();

    final total = byStatus.values.fold<int>(
      0,
      (sum, v) => sum + ((v as num?)?.toInt() ?? 0),
    );
    if (total == 0) return const SizedBox.shrink();

    const statusLabels = <String, String>{
      'pending': 'En attente',
      'accepted': 'Acceptées',
      'paid': 'Payées',
      'confirmed': 'Confirmées',
      'completed': 'Terminées',
      'cancelled': 'Annulées',
      'rejected': 'Refusées',
      'disputed': 'Litiges',
    };
    const statusColors = <String, Color>{
      'pending': Color(0xFFFBBF24),
      'accepted': Color(0xFF3B9DF2),
      'paid': Color(0xFF8B5CF6),
      'confirmed': Color(0xFF06B6D4),
      'completed': Color(0xFF14B8A6),
      'cancelled': Color(0xFF6B7280),
      'rejected': Color(0xFFEF4444),
      'disputed': Color(0xFFF97316),
    };

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.donut_small_outlined,
                size: 16,
                color: _warning,
              ),
              const SizedBox(width: 6),
              Text(
                'Répartition des réservations',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: _secondary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: byStatus.entries
                .where((e) => ((e.value as num?)?.toInt() ?? 0) > 0)
                .map((e) {
                  final count = (e.value as num?)?.toInt() ?? 0;
                  final label = statusLabels[e.key] ?? e.key;
                  final color = statusColors[e.key] ?? const Color(0xFF8FA3C0);
                  final pct = (count / total * 100).round();
                  return Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: color.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 8,
                          height: 8,
                          decoration: BoxDecoration(
                            color: color,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          '$label · $count ($pct%)',
                          style: GoogleFonts.manrope(
                            fontSize: 11,
                            color: color,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  );
                })
                .toList(),
          ),
        ],
      ),
    );
  }
}

// ── Tendance des notes ───────────────────────────────────────────────────────

class _RatingHistoryCard extends StatelessWidget {
  const _RatingHistoryCard({required this.analytics});
  final Map<String, dynamic> analytics;

  @override
  Widget build(BuildContext context) {
    final ratingHistory =
        ((analytics['rating_history'] as List?)
            ?.cast<Map<String, dynamic>>()) ??
        [];
    final avgRating = (analytics['average_rating'] as num?)?.toDouble() ?? 0.0;

    if (ratingHistory.isEmpty) return const SizedBox.shrink();

    // Take last 10 reviews for the mini chart
    final recent = ratingHistory.take(10).toList().reversed.toList();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.star_outline, size: 16, color: _warning),
                  const SizedBox(width: 6),
                  Text(
                    'Tendance des notes',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: _secondary,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: _warning.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.star, size: 12, color: _warning),
                    const SizedBox(width: 4),
                    Text(
                      avgRating.toStringAsFixed(1),
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: _warning,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 40,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: recent.map((r) {
                final rating = (r['rating'] as int?) ?? 0;
                final height = (rating / 5 * 40).clamp(4.0, 40.0);
                final color = rating >= 4
                    ? _success
                    : rating >= 3
                    ? _warning
                    : const Color(0xFFEF4444);
                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 1),
                    child: Align(
                      alignment: Alignment.bottomCenter,
                      child: Container(
                        height: height,
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: 0.8),
                          borderRadius: BorderRadius.circular(3),
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            '${ratingHistory.length} avis reçus — ${recent.length} derniers affichés',
            style: GoogleFonts.manrope(fontSize: 10, color: _mutedFg),
          ),
        ],
      ),
    );
  }
}

class _VisibilityScoreCard extends StatelessWidget {
  const _VisibilityScoreCard({required this.score});
  final double score;

  @override
  Widget build(BuildContext context) {
    final pct = (score / 100).clamp(0.0, 1.0);
    final color = score >= 70
        ? _success
        : score >= 40
        ? _warning
        : const Color(0xFFEF4444);
    final label = score >= 70
        ? 'Excellent'
        : score >= 40
        ? 'Bon'
        : 'À améliorer';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(Icons.trending_up, size: 18, color: color),
                  const SizedBox(width: 8),
                  Text(
                    'Score de visibilité',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: _secondary,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: color.withValues(alpha: 0.3)),
                ),
                child: Text(
                  label,
                  style: GoogleFonts.manrope(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: color,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: pct,
                    backgroundColor: const Color(0xFF1A2E54),
                    valueColor: AlwaysStoppedAnimation<Color>(color),
                    minHeight: 8,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Text(
                '${score.toStringAsFixed(0)}/100',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: color,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            'Calculé à partir de vos prestations récentes, votre note moyenne et votre statut vérifié.',
            style: GoogleFonts.manrope(fontSize: 11, color: _mutedFg),
          ),
        ],
      ),
    );
  }
}
