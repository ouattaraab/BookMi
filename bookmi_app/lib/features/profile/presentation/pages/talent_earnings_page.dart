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
const _border = Color(0xFFE2E8F0);

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
        final items = (data['data'] as List?)
                ?.cast<Map<String, dynamic>>() ??
            [];
        final meta = data['meta'] as Map<String, dynamic>? ?? {};
        setState(() {
          if (_page == 1) {
            _earnings = items;
          } else {
            _earnings = [..._earnings, ...items];
          }
          _meta = meta;
          _hasMore = (meta['current_page'] as int? ?? 1) <
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
    final result =
        await widget.repository.getEarnings(page: _page);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        final items = (data['data'] as List?)
                ?.cast<Map<String, dynamic>>() ??
            [];
        final meta = data['meta'] as Map<String, dynamic>? ?? {};
        setState(() {
          _earnings = [..._earnings, ...items];
          _meta = meta;
          _hasMore = (meta['current_page'] as int? ?? 1) <
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
    final fmt = NumberFormat('#,###', 'fr_FR');
    final totalCachet =
        (_meta['total_cachet'] as int?) ?? 0;
    final totalCommission =
        (_meta['total_commission'] as int?) ?? 0;
    final commissionRate =
        (_meta['commission_rate'] as num?)?.toDouble() ?? 15.0;

    return Scaffold(
      backgroundColor: _muted,
      appBar: AppBar(
        backgroundColor: _secondary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          'Mes revenus',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? _buildError()
              : Column(
                  children: [
                    // Summary header
                    if (_meta.isNotEmpty)
                      Container(
                        color: _secondary,
                        padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
                        child: Row(
                          children: [
                            Expanded(
                              child: _SummaryChip(
                                label: 'Cachet net total',
                                value: '${fmt.format(totalCachet)} FCFA',
                                color: _success,
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: _SummaryChip(
                                label: 'Commission (${commissionRate.toStringAsFixed(0)}%)',
                                value:
                                    '${fmt.format(totalCommission)} FCFA',
                                color: Colors.orange,
                              ),
                            ),
                          ],
                        ),
                      ),
                    Expanded(
                      child: _earnings.isEmpty
                          ? _buildEmpty()
                          : RefreshIndicator(
                              onRefresh: () => _load(refresh: true),
                              child: NotificationListener<ScrollNotification>(
                                onNotification: (notification) {
                                  if (notification
                                          is ScrollEndNotification &&
                                      notification.metrics.pixels >=
                                          notification
                                                  .metrics.maxScrollExtent -
                                              200) {
                                    _loadMore();
                                  }
                                  return false;
                                },
                                child: ListView.separated(
                                  padding: const EdgeInsets.all(16),
                                  itemCount: _earnings.length +
                                      (_loadingMore ? 1 : 0),
                                  separatorBuilder: (_, __) =>
                                      const SizedBox(height: 10),
                                  itemBuilder: (context, index) {
                                    if (index >= _earnings.length) {
                                      return const Center(
                                        child: Padding(
                                          padding: EdgeInsets.all(16),
                                          child:
                                              CircularProgressIndicator(),
                                        ),
                                      );
                                    }
                                    return _EarningCard(
                                      item: _earnings[index],
                                    );
                                  },
                                ),
                              ),
                            ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(_error!,
              style: GoogleFonts.manrope(color: _mutedFg),
              textAlign: TextAlign.center),
          const SizedBox(height: 12),
          TextButton(
            onPressed: _load,
            child: Text('Réessayer',
                style: GoogleFonts.manrope(color: _primary)),
          ),
        ],
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.account_balance_wallet_outlined,
              size: 56,
              color: _mutedFg.withValues(alpha: 0.4)),
          const SizedBox(height: 12),
          Text(
            'Aucun revenu enregistré',
            style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: _secondary),
          ),
          const SizedBox(height: 6),
          Text(
            'Vos revenus apparaîtront ici après vos prestations.',
            style: GoogleFonts.manrope(fontSize: 13, color: _mutedFg),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class _SummaryChip extends StatelessWidget {
  const _SummaryChip({
    required this.label,
    required this.value,
    required this.color,
  });
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(value,
              style: GoogleFonts.plusJakartaSans(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: Colors.white)),
          const SizedBox(height: 2),
          Text(label,
              style: GoogleFonts.manrope(fontSize: 11, color: Colors.white70)),
        ],
      ),
    );
  }
}

class _EarningCard extends StatelessWidget {
  const _EarningCard({required this.item});
  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat('#,###', 'fr_FR');
    final bookingId = item['booking_id'] as int? ?? 0;
    final clientName = item['client_name'] as String? ?? 'Client';
    final packageName =
        item['package_name'] as String? ?? 'Package';
    final eventDate = item['event_date'] as String? ?? '';
    final cachetAmount = (item['cachet_amount'] as num?)?.toInt() ?? 0;
    final commissionAmount =
        (item['commission_amount'] as num?)?.toInt() ?? 0;
    final totalAmount = (item['total_amount'] as num?)?.toInt() ?? 0;

    return Container(
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
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        clientName,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: _secondary,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        packageName,
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          color: _mutedFg,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _success.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    '#$bookingId',
                    style: GoogleFonts.manrope(
                      fontSize: 11,
                      color: _success,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
            if (eventDate.isNotEmpty) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.calendar_today_outlined,
                      size: 12, color: _mutedFg),
                  const SizedBox(width: 4),
                  Text(
                    eventDate,
                    style:
                        GoogleFonts.manrope(fontSize: 12, color: _mutedFg),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 10),
            const Divider(color: _border, height: 1),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: _AmountRow(
                    label: 'Cachet net',
                    value: '${fmt.format(cachetAmount)} FCFA',
                    color: _success,
                    bold: true,
                  ),
                ),
                Expanded(
                  child: _AmountRow(
                    label: 'Commission',
                    value: '- ${fmt.format(commissionAmount)} FCFA',
                    color: Colors.orange,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            _AmountRow(
              label: 'Total client',
              value: '${fmt.format(totalAmount)} FCFA',
              color: _primary,
            ),
          ],
        ),
      ),
    );
  }
}

class _AmountRow extends StatelessWidget {
  const _AmountRow({
    required this.label,
    required this.value,
    required this.color,
    this.bold = false,
  });
  final String label;
  final String value;
  final Color color;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          '$label : ',
          style: GoogleFonts.manrope(fontSize: 12, color: _mutedFg),
        ),
        Text(
          value,
          style: GoogleFonts.manrope(
            fontSize: 12,
            fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
            color: color,
          ),
        ),
      ],
    );
  }
}
