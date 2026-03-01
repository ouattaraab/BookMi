import 'dart:io';

import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

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

  Map<String, dynamic>? _alerts;

  @override
  void initState() {
    super.initState();
    _load();
    _loadAlerts();
  }

  Future<void> _loadAlerts() async {
    final result = await widget.repository.getCalendarAlerts();
    if (!mounted) return;
    if (result case ApiSuccess(:final data)) {
      setState(() => _alerts = data);
    }
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
          _ExportEarningsButton(repository: widget.repository),
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white70, size: 20),
            onPressed: () {
              _load(refresh: true);
              _loadAlerts();
            },
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
                    if (_alerts != null) ...[
                      _CalendarAlertBanner(alerts: _alerts!),
                      const SizedBox(height: BookmiSpacing.spaceSm),
                    ],
                    if (_meta.isNotEmpty) _SummaryGrid(meta: _meta),
                    const SizedBox(height: BookmiSpacing.spaceMd),
                    _RevenueCertificateCard(repository: widget.repository),
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

// ── Revenue certificate download card ────────────────────────────────────────

class _RevenueCertificateCard extends StatefulWidget {
  const _RevenueCertificateCard({required this.repository});
  final ProfileRepository repository;

  @override
  State<_RevenueCertificateCard> createState() =>
      _RevenueCertificateCardState();
}

class _RevenueCertificateCardState extends State<_RevenueCertificateCard> {
  final int _currentYear = DateTime.now().year;
  late int _selectedYear;
  bool _downloading = false;

  @override
  void initState() {
    super.initState();
    // Default to previous year (most common use-case for tax certificates)
    _selectedYear = _currentYear - 1;
  }

  Future<void> _download() async {
    setState(() => _downloading = true);
    final result =
        await widget.repository.downloadRevenueCertificate(_selectedYear);
    if (!mounted) return;
    setState(() => _downloading = false);
    switch (result) {
      case ApiSuccess(:final data):
        if (data.isEmpty) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Aucune donnée disponible pour cette année.'),
              backgroundColor: BookmiColors.warning,
            ),
          );
          return;
        }
        final dir = await getTemporaryDirectory();
        final file = File('${dir.path}/attestation_$_selectedYear.pdf');
        await file.writeAsBytes(data);
        if (!mounted) return;
        await OpenFilex.open(file.path);
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: BookmiColors.error,
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    // Offer years from 2024 up to (currentYear - 1)
    final years = List.generate(
      (_currentYear - 1) - 2024 + 1,
      (i) => 2024 + i,
    ).reversed.toList();

    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: BookmiColors.ctaOrange.withValues(alpha: 0.15),
                ),
                child: const Icon(
                  Icons.receipt_long_outlined,
                  size: 18,
                  color: BookmiColors.ctaOrange,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Attestation de revenus',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      'PDF officiel pour votre déclaration fiscale',
                      style: const TextStyle(
                        fontSize: 11,
                        color: Colors.white38,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              // Year selector
              if (years.isNotEmpty) ...[
                Container(
                  height: 38,
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.12),
                    ),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<int>(
                      value: _selectedYear,
                      dropdownColor: const Color(0xFF1A2744),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                      ),
                      items: years
                          .map(
                            (y) => DropdownMenuItem(
                              value: y,
                              child: Text(y.toString()),
                            ),
                          )
                          .toList(),
                      onChanged: (v) {
                        if (v != null) setState(() => _selectedYear = v);
                      },
                    ),
                  ),
                ),
                const SizedBox(width: 10),
              ],
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: (years.isEmpty || _downloading) ? null : _download,
                  icon: _downloading
                      ? const SizedBox(
                          height: 14,
                          width: 14,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Icon(
                          Icons.download_rounded,
                          size: 16,
                          color: Colors.white,
                        ),
                  label: Text(
                    years.isEmpty
                        ? 'Disponible en $_currentYear'
                        : (_downloading ? 'Téléchargement...' : 'Télécharger'),
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: BookmiColors.ctaOrange,
                    disabledBackgroundColor:
                        BookmiColors.ctaOrange.withValues(alpha: 0.3),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
              ),
            ],
          ),
          if (years.isEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                'L\'attestation $_currentYear sera disponible à partir du 1er janvier ${_currentYear + 1}.',
                style: const TextStyle(fontSize: 11, color: Colors.white38),
              ),
            ),
        ],
      ),
    );
  }
}

// ─── Calendar alert banner ────────────────────────────────────────────────

class _CalendarAlertBanner extends StatefulWidget {
  const _CalendarAlertBanner({required this.alerts});
  final Map<String, dynamic> alerts;

  @override
  State<_CalendarAlertBanner> createState() => _CalendarAlertBannerState();
}

class _CalendarAlertBannerState extends State<_CalendarAlertBanner> {
  bool _dismissed = false;

  @override
  Widget build(BuildContext context) {
    if (_dismissed) return const SizedBox.shrink();

    final isOverloaded = widget.alerts['is_overloaded'] as bool? ?? false;
    final hasEmpty = widget.alerts['has_empty_upcoming'] as bool? ?? false;

    if (!isOverloaded && !hasEmpty) return const SizedBox.shrink();

    final isWarning = isOverloaded;
    final color = isWarning ? const Color(0xFFFF6B35) : const Color(0xFF64B5F6);
    final bgColor = isWarning
        ? const Color(0xFFFF6B35).withValues(alpha: 0.12)
        : const Color(0xFF1E3A5F);
    final icon = isWarning
        ? Icons.warning_amber_rounded
        : Icons.calendar_today_outlined;
    final message = isOverloaded
        ? 'Agenda surchargé : vous avez ${widget.alerts['active_booking_count']} réservations actives (seuil : ${widget.alerts['overload_threshold']}). Pensez à bloquer des créneaux.'
        : 'Aucune prestation prévue dans les 30 prochains jours. Mettez à jour votre disponibilité pour attirer des clients.';

    return Container(
      padding: const EdgeInsets.fromLTRB(12, 10, 8, 10),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: Colors.white.withValues(alpha: 0.85),
                height: 1.4,
              ),
            ),
          ),
          GestureDetector(
            onTap: () => setState(() => _dismissed = true),
            child: Padding(
              padding: const EdgeInsets.only(left: 6),
              child: Icon(
                Icons.close,
                size: 16,
                color: Colors.white.withValues(alpha: 0.4),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Export earnings button ───────────────────────────────────────────────

class _ExportEarningsButton extends StatefulWidget {
  const _ExportEarningsButton({required this.repository});
  final ProfileRepository repository;

  @override
  State<_ExportEarningsButton> createState() => _ExportEarningsButtonState();
}

class _ExportEarningsButtonState extends State<_ExportEarningsButton> {
  bool _exporting = false;

  Future<void> _export() async {
    if (_exporting) return;
    setState(() => _exporting = true);
    try {
      final result = await widget.repository.exportEarnings();
      if (!mounted) return;
      switch (result) {
        case ApiSuccess(:final data):
          final dir = await getTemporaryDirectory();
          final filename =
              'revenus_${DateTime.now().year}_${DateTime.now().millisecondsSinceEpoch}.csv';
          final file = File('${dir.path}/$filename');
          await file.writeAsBytes(data);
          await OpenFilex.open(file.path);
        case ApiFailure(:final message):
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(message)),
            );
          }
      }
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return IconButton(
      tooltip: 'Exporter CSV',
      icon: _exporting
          ? const SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: Colors.white70,
              ),
            )
          : const Icon(Icons.download_outlined, color: Colors.white70, size: 20),
      onPressed: _exporting ? null : _export,
    );
  }
}
