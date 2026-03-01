import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/calendar_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

class CalendarManagementPage extends StatefulWidget {
  const CalendarManagementPage({super.key});

  @override
  State<CalendarManagementPage> createState() => _CalendarManagementPageState();
}

class _CalendarManagementPageState extends State<CalendarManagementPage> {
  late final CalendarRepository _repo;
  int? _talentProfileId;
  late DateTime _month;
  Map<String, CalendarDayModel> _calendar = {};
  bool _initLoading = true;
  bool _monthLoading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _repo = CalendarRepository(apiClient: ApiClient.instance);
    final now = DateTime.now();
    _month = DateTime(now.year, now.month);
    _init();
  }

  Future<void> _init() async {
    final result = await _repo.getMyTalentProfileId();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _talentProfileId = data;
          _initLoading = false;
        });
        await _loadCalendar();
      case ApiFailure(:final message):
        setState(() {
          _initLoading = false;
          _error = message;
        });
    }
  }

  Future<void> _loadCalendar() async {
    final profileId = _talentProfileId;
    if (profileId == null) return;
    setState(() => _monthLoading = true);
    final month = DateFormat('yyyy-MM').format(_month);
    final result = await _repo.getCalendar(profileId, month);
    if (!mounted) return;
    setState(() => _monthLoading = false);
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _calendar = {for (final d in data) d.date: d};
        });
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: Colors.red.shade700,
          ),
        );
    }
  }

  void _goPrev() {
    setState(() {
      _month = DateTime(_month.year, _month.month - 1);
      _calendar = {};
    });
    _loadCalendar();
  }

  void _goNext() {
    setState(() {
      _month = DateTime(_month.year, _month.month + 1);
      _calendar = {};
    });
    _loadCalendar();
  }

  Future<void> _onDayTap(DateTime date) async {
    final dateStr = DateFormat('yyyy-MM-dd').format(date);
    final existing = _calendar[dateStr];

    if (existing?.status == 'confirmed') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Ce jour a une réservation confirmée — non modifiable'),
          duration: Duration(seconds: 2),
        ),
      );
      return;
    }

    final choice = await showModalBottomSheet<String?>(
      context: context,
      backgroundColor: const Color(0xFF1A2233),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => _StatusSheet(
        date: DateFormat('d MMMM yyyy', 'fr').format(date),
        currentStatus: existing?.status,
        hasSlot: existing?.slotId != null,
      ),
    );

    if (choice == null || !mounted) return;

    if (choice == 'delete') {
      final slotId = existing?.slotId;
      if (slotId == null) return;
      final res = await _repo.deleteSlot(slotId);
      if (!mounted) return;
      if (res is ApiSuccess) {
        setState(() => _calendar.remove(dateStr));
      }
    } else if (existing?.slotId != null) {
      final res = await _repo.updateSlot(existing!.slotId!, choice);
      if (!mounted) return;
      switch (res) {
        case ApiSuccess(:final data):
          setState(() => _calendar[dateStr] = data);
        case ApiFailure(:final message):
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(message),
              backgroundColor: Colors.red.shade700,
            ),
          );
      }
    } else {
      final res = await _repo.createSlot(dateStr, choice);
      if (!mounted) return;
      switch (res) {
        case ApiSuccess(:final data):
          setState(() => _calendar[dateStr] = data);
        case ApiFailure(:final message):
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(message),
              backgroundColor: Colors.red.shade700,
            ),
          );
      }
    }
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
          foregroundColor: Colors.white,
          title: Text(
            'Mon calendrier',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: Colors.white,
            ),
          ),
        ),
        body: _initLoading
            ? const Center(
                child: CircularProgressIndicator(
                  color: BookmiColors.brandBlueLight,
                ),
              )
            : _error != null
            ? Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.error_outline,
                      color: Colors.redAccent,
                      size: 40,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _error!,
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 14,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    TextButton(
                      onPressed: _init,
                      child: const Text(
                        'Réessayer',
                        style: TextStyle(color: BookmiColors.brandBlueLight),
                      ),
                    ),
                  ],
                ),
              )
            : Column(
                children: [
                  _MonthNav(
                    month: _month,
                    loading: _monthLoading,
                    onPrev: _goPrev,
                    onNext: _goNext,
                  ),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: _CalendarGrid(
                        month: _month,
                        calendar: _calendar,
                        onDayTap: _onDayTap,
                      ),
                    ),
                  ),
                  const _Legend(),
                  const SizedBox(height: BookmiSpacing.spaceMd),
                ],
              ),
      ),
    );
  }
}

// ── Month nav header ──────────────────────────────────────────────────────────

class _MonthNav extends StatelessWidget {
  const _MonthNav({
    required this.month,
    required this.loading,
    required this.onPrev,
    required this.onNext,
  });

  final DateTime month;
  final bool loading;
  final VoidCallback onPrev;
  final VoidCallback onNext;

  @override
  Widget build(BuildContext context) {
    final label = DateFormat('MMMM yyyy', 'fr').format(month);
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.chevron_left, color: Colors.white70),
            onPressed: onPrev,
          ),
          Expanded(
            child: Center(
              child: loading
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: BookmiColors.brandBlueLight,
                      ),
                    )
                  : Text(
                      label.substring(0, 1).toUpperCase() + label.substring(1),
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.chevron_right, color: Colors.white70),
            onPressed: onNext,
          ),
        ],
      ),
    );
  }
}

// ── Calendar grid ─────────────────────────────────────────────────────────────

class _CalendarGrid extends StatelessWidget {
  const _CalendarGrid({
    required this.month,
    required this.calendar,
    required this.onDayTap,
  });

  final DateTime month;
  final Map<String, CalendarDayModel> calendar;
  final void Function(DateTime) onDayTap;

  static const _weekDays = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

  @override
  Widget build(BuildContext context) {
    final firstDay = DateTime(month.year, month.month, 1);
    final daysInMonth = DateUtils.getDaysInMonth(month.year, month.month);
    // Monday-first offset: weekday 1=Mon→0, 7=Sun→6
    final offset = (firstDay.weekday - 1) % 7;
    final totalCells = offset + daysInMonth;
    final weeks = (totalCells / 7).ceil();
    final today = DateTime.now();

    return Column(
      children: [
        // Day-of-week header
        Row(
          children: _weekDays.map((d) {
            return Expanded(
              child: Center(
                child: Text(
                  d,
                  style: GoogleFonts.manrope(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: Colors.white38,
                  ),
                ),
              ),
            );
          }).toList(),
        ),
        const SizedBox(height: 6),
        // Weeks
        ...List.generate(weeks, (week) {
          return Expanded(
            child: Row(
              children: List.generate(7, (col) {
                final idx = week * 7 + col;
                final day = idx - offset + 1;

                if (day < 1 || day > daysInMonth) {
                  return const Expanded(child: SizedBox());
                }

                final date = DateTime(month.year, month.month, day);
                final dateStr = DateFormat('yyyy-MM-dd').format(date);
                final slot = calendar[dateStr];
                final isPast = date.isBefore(
                  DateTime(today.year, today.month, today.day),
                );
                final isToday =
                    date.year == today.year &&
                    date.month == today.month &&
                    date.day == today.day;

                return Expanded(
                  child: GestureDetector(
                    onTap: isPast ? null : () => onDayTap(date),
                    child: Container(
                      margin: const EdgeInsets.all(2),
                      decoration: BoxDecoration(
                        color: isPast
                            ? Colors.transparent
                            : _bgColor(slot?.status),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(
                          color: isToday
                              ? BookmiColors.brandBlueLight
                              : slot != null && !isPast
                              ? _borderColor(slot.status)
                              : Colors.white.withValues(alpha: 0.06),
                          width: isToday ? 1.5 : 1,
                        ),
                      ),
                      child: Center(
                        child: Text(
                          '$day',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: isToday
                                ? FontWeight.w700
                                : FontWeight.w400,
                            color: isPast
                                ? Colors.white.withValues(alpha: 0.2)
                                : slot != null
                                ? Colors.white
                                : Colors.white.withValues(alpha: 0.75),
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              }),
            ),
          );
        }),
      ],
    );
  }

  static Color _bgColor(String? status) => switch (status) {
    'available' => const Color(0xFF4CAF50).withValues(alpha: 0.20),
    'blocked' => const Color(0xFFF44336).withValues(alpha: 0.20),
    'rest' => const Color(0xFFFF9800).withValues(alpha: 0.20),
    'confirmed' => const Color(0xFF2196F3).withValues(alpha: 0.20),
    _ => Colors.white.withValues(alpha: 0.05),
  };

  static Color _borderColor(String status) => switch (status) {
    'available' => const Color(0xFF4CAF50).withValues(alpha: 0.40),
    'blocked' => const Color(0xFFF44336).withValues(alpha: 0.40),
    'rest' => const Color(0xFFFF9800).withValues(alpha: 0.40),
    'confirmed' => const Color(0xFF2196F3).withValues(alpha: 0.40),
    _ => Colors.white.withValues(alpha: 0.10),
  };
}

// ── Status selection sheet ────────────────────────────────────────────────────

class _StatusSheet extends StatelessWidget {
  const _StatusSheet({
    required this.date,
    this.currentStatus,
    required this.hasSlot,
  });

  final String date;
  final String? currentStatus;
  final bool hasSlot;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24, 20, 24, 16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              date,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Choisir le statut de ce jour',
              style: GoogleFonts.manrope(fontSize: 12, color: Colors.white54),
            ),
            const SizedBox(height: 12),
            _StatusOption(
              label: 'Disponible',
              sub: 'Marquer explicitement comme libre',
              icon: Icons.check_circle_outline,
              color: const Color(0xFF4CAF50),
              value: 'available',
              current: currentStatus,
            ),
            _StatusOption(
              label: 'Bloquer ce jour',
              sub: 'Aucune réservation acceptée',
              icon: Icons.block_outlined,
              color: const Color(0xFFF44336),
              value: 'blocked',
              current: currentStatus,
            ),
            _StatusOption(
              label: 'Jour de repos',
              sub: 'Repos personnel — non disponible',
              icon: Icons.self_improvement_outlined,
              color: const Color(0xFFFF9800),
              value: 'rest',
              current: currentStatus,
            ),
            if (hasSlot) ...[
              Divider(
                color: Colors.white.withValues(alpha: 0.12),
                height: 20,
              ),
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(
                  Icons.delete_outline,
                  color: Colors.white38,
                  size: 22,
                ),
                title: Text(
                  'Retirer le statut',
                  style: GoogleFonts.manrope(
                    fontSize: 14,
                    color: Colors.white54,
                  ),
                ),
                onTap: () => Navigator.of(context).pop('delete'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _StatusOption extends StatelessWidget {
  const _StatusOption({
    required this.label,
    required this.sub,
    required this.icon,
    required this.color,
    required this.value,
    this.current,
  });

  final String label;
  final String sub;
  final IconData icon;
  final Color color;
  final String value;
  final String? current;

  @override
  Widget build(BuildContext context) {
    final isSelected = current == value;
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          color: color.withValues(alpha: isSelected ? 0.20 : 0.08),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Icon(
          icon,
          size: 18,
          color: isSelected ? color : color.withValues(alpha: 0.5),
        ),
      ),
      title: Text(
        label,
        style: GoogleFonts.manrope(
          fontSize: 14,
          fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
          color: isSelected ? Colors.white : Colors.white70,
        ),
      ),
      subtitle: Text(
        sub,
        style: GoogleFonts.manrope(fontSize: 11, color: Colors.white38),
      ),
      trailing: isSelected
          ? Icon(Icons.check_circle, color: color, size: 18)
          : null,
      onTap: () => Navigator.of(context).pop(value),
    );
  }
}

// ── Legend ────────────────────────────────────────────────────────────────────

class _Legend extends StatelessWidget {
  const _Legend();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: GlassCard(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: const [
            _LegendItem(color: Color(0xFF4CAF50), label: 'Dispo'),
            _LegendItem(color: Color(0xFFF44336), label: 'Bloqué'),
            _LegendItem(color: Color(0xFFFF9800), label: 'Repos'),
            _LegendItem(color: Color(0xFF2196F3), label: 'Réservé'),
          ],
        ),
      ),
    );
  }
}

class _LegendItem extends StatelessWidget {
  const _LegendItem({required this.color, required this.label});
  final Color color;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.7),
            borderRadius: BorderRadius.circular(3),
          ),
        ),
        const SizedBox(width: 4),
        Text(
          label,
          style: GoogleFonts.manrope(fontSize: 11, color: Colors.white60),
        ),
      ],
    );
  }
}
