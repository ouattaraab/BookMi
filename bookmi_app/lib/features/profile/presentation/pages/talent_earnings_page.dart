import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

class TalentEarningsPage extends StatefulWidget {
  const TalentEarningsPage({required this.repository, super.key});
  final ProfileRepository repository;

  @override
  State<TalentEarningsPage> createState() => _TalentEarningsPageState();
}

class _TalentEarningsPageState extends State<TalentEarningsPage> {
  List<Map<String, dynamic>> _earnings = [];
  Map<String, dynamic> _meta = {};
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  int _page = 1;
  bool _hasMore = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _page = 1;
        _earnings = [];
        _loading = true;
        _error = null;
      });
    } else {
      setState(() {
        _loading = true;
        _error = null;
      });
    }

    final result = await widget.repository.getEarnings(page: _page);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        final items =
            (data['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
        final meta = data['meta'] as Map<String, dynamic>? ?? {};
        setState(() {
          if (_page == 1) {
            _earnings = items;
          } else {
            _earnings = [..._earnings, ...items];
          }
          _meta = meta;
          _hasMore =
              (meta['current_page'] as int? ?? 1) <
              (meta['last_page'] as int? ?? 1);
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  Future<void> _loadMore() async {
    if (_loadingMore || !_hasMore) return;
    setState(() {
      _loadingMore = true;
      _page++;
    });
    final result = await widget.repository.getEarnings(page: _page);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        final items =
            (data['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
        final meta = data['meta'] as Map<String, dynamic>? ?? {};
        setState(() {
          _earnings = [..._earnings, ...items];
          _meta = meta;
          _hasMore =
              (meta['current_page'] as int? ?? 1) <
              (meta['last_page'] as int? ?? 1);
          _loadingMore = false;
        });
      case ApiFailure():
        setState(() {
          _page--;
          _loadingMore = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BookmiColors.backgroundDeep,
      appBar: AppBar(
        backgroundColor: BookmiColors.backgroundDeep,
        elevation: 0,
        title: Text(
          'Mes revenus',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white70),
          onPressed: () => Navigator.of(context).pop(),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white70, size: 20),
            onPressed: () => _load(refresh: true),
          ),
        ],
      ),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(color: BookmiColors.brandBlue),
            )
          : _error != null
          ? _buildError()
          : RefreshIndicator(
              color: BookmiColors.brandBlue,
              backgroundColor: const Color(0xFF0D1B38),
              onRefresh: () => _load(refresh: true),
              child: NotificationListener<ScrollNotification>(
                onNotification: (n) {
                  if (n is ScrollEndNotification &&
                      n.metrics.pixels >= n.metrics.maxScrollExtent - 200) {
                    _loadMore();
                  }
                  return false;
                },
                child: ListView(
                  padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
                  children: [
                    if (_meta.isNotEmpty) _SummaryGrid(meta: _meta),
                    const SizedBox(height: BookmiSpacing.spaceMd),
                    if (_earnings.isNotEmpty) ...[
                      Text(
                        'Historique des prestations',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: Colors.white70,
                          letterSpacing: 0.3,
                        ),
                      ),
                      const SizedBox(height: BookmiSpacing.spaceSm),
                      ..._earnings.map((item) => _EarningCard(item: item)),
                      if (_loadingMore)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 16),
                          child: Center(
                            child: CircularProgressIndicator(
                              color: BookmiColors.brandBlue,
                              strokeWidth: 2,
                            ),
                          ),
                        ),
                    ] else
                      _buildEmpty(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, color: Colors.white38, size: 48),
            const SizedBox(height: 12),
            Text(
              _error!,
              style: const TextStyle(color: Colors.white54),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            TextButton(
              onPressed: _load,
              child: const Text(
                'Réessayer',
                style: TextStyle(color: BookmiColors.brandBlue),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmpty() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 32),
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.account_balance_wallet_outlined,
              size: 56,
              color: Colors.white24,
            ),
            const SizedBox(height: 12),
            Text(
              'Aucune prestation terminée',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 15,
                fontWeight: FontWeight.w600,
                color: Colors.white70,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Vos revenus apparaîtront ici\naprès vos prestations.',
              style: const TextStyle(fontSize: 13, color: Colors.white38),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

// ── Summary grid (4 cards) ───────────────────────────────────────────────────

class _SummaryGrid extends StatelessWidget {
  const _SummaryGrid({required this.meta});
  final Map<String, dynamic> meta;

  @override
  Widget build(BuildContext context) {
    final totalCachetsActifs = (meta['total_cachets_actifs'] as int?) ?? 0;
    final revenusLiberes = (meta['revenus_liberes'] as int?) ?? 0;
    final revenusGlobaux = (meta['revenus_globaux'] as int?) ?? 0;
    final soldeCompte = (meta['solde_compte'] as int?) ?? 0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Aperçu financier',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: Colors.white70,
            letterSpacing: 0.3,
          ),
        ),
        const SizedBox(height: BookmiSpacing.spaceSm),
        // Solde compte — highlight card
        _HighlightCard(
          icon: Icons.account_balance_wallet_rounded,
          label: 'Solde disponible',
          subtitle: 'Montant retirable',
          amount: soldeCompte,
          color: BookmiColors.success,
        ),
        const SizedBox(height: BookmiSpacing.spaceSm),
        Row(
          children: [
            Expanded(
              child: _MetricCard(
                icon: Icons.schedule_rounded,
                label: 'Cachets à venir',
                subtitle: 'Réservations actives',
                amount: totalCachetsActifs,
                color: BookmiColors.brandBlue,
              ),
            ),
            const SizedBox(width: BookmiSpacing.spaceSm),
            Expanded(
              child: _MetricCard(
                icon: Icons.check_circle_rounded,
                label: 'Revenus libérés',
                subtitle: 'Prestations terminées',
                amount: revenusLiberes,
                color: BookmiColors.warning,
              ),
            ),
          ],
        ),
        const SizedBox(height: BookmiSpacing.spaceSm),
        _MetricCard(
          icon: Icons.bar_chart_rounded,
          label: 'Revenus globaux',
          subtitle: 'Total de toutes les réservations',
          amount: revenusGlobaux,
          color: BookmiColors.brandBlueLight,
          wide: true,
        ),
      ],
    );
  }
}

class _HighlightCard extends StatelessWidget {
  const _HighlightCard({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.amount,
    required this.color,
  });

  final IconData icon;
  final String label;
  final String subtitle;
  final int amount;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat('#,###', 'fr_FR');
    return GlassCard(
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: color.withValues(alpha: 0.15),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(width: BookmiSpacing.spaceSm),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                Text(
                  subtitle,
                  style: const TextStyle(fontSize: 11, color: Colors.white38),
                ),
              ],
            ),
          ),
          Text(
            '${fmt.format(amount)} XOF',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.amount,
    required this.color,
    this.wide = false,
  });

  final IconData icon;
  final String label;
  final String subtitle;
  final int amount;
  final Color color;
  final bool wide;

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat('#,###', 'fr_FR');
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: color),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: const TextStyle(fontSize: 12, color: Colors.white70),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            '${fmt.format(amount)} XOF',
            style: TextStyle(
              fontSize: wide ? 16 : 14,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
          Text(
            subtitle,
            style: const TextStyle(fontSize: 10, color: Colors.white38),
          ),
        ],
      ),
    );
  }
}

// ── Earning card ─────────────────────────────────────────────────────────────

class _EarningCard extends StatelessWidget {
  const _EarningCard({required this.item});
  final Map<String, dynamic> item;

  static String _formatDate(String raw) {
    try {
      final dt = DateTime.parse(raw);
      const months = [
        'jan',
        'fév',
        'mar',
        'avr',
        'mai',
        'jun',
        'jul',
        'aoû',
        'sep',
        'oct',
        'nov',
        'déc',
      ];
      return '${dt.day} ${months[dt.month - 1]}. ${dt.year}';
    } on FormatException catch (_) {
      return raw;
    }
  }

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat('#,###', 'fr_FR');
    final clientName = item['client_name'] as String? ?? 'Client';
    final packageName = item['package_name'] as String? ?? 'Prestation';
    final eventDate = item['event_date'] as String? ?? '';
    final cachetAmount = (item['cachet_amount'] as num?)?.toInt() ?? 0;

    return Padding(
      padding: const EdgeInsets.only(bottom: BookmiSpacing.spaceSm),
      child: GlassCard(
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: BookmiColors.success.withValues(alpha: 0.15),
              ),
              child: const Icon(
                Icons.music_note_rounded,
                size: 18,
                color: BookmiColors.success,
              ),
            ),
            const SizedBox(width: BookmiSpacing.spaceSm),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    clientName,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  Text(
                    packageName,
                    style: const TextStyle(
                      fontSize: 11,
                      color: Colors.white54,
                    ),
                  ),
                  if (eventDate.isNotEmpty)
                    Text(
                      _formatDate(eventDate),
                      style: const TextStyle(
                        fontSize: 10,
                        color: Colors.white38,
                      ),
                    ),
                ],
              ),
            ),
            Text(
              '${fmt.format(cachetAmount)} XOF',
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w700,
                color: BookmiColors.success,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
