import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

/// Step 2 of the booking flow — pick event date, time, and location.
class Step2DateLocation extends StatefulWidget {
  const Step2DateLocation({
    required this.selectedDate,
    required this.selectedTime,
    required this.location,
    required this.onDateSelected,
    required this.onTimeSelected,
    required this.onLocationChanged,
    this.blockedDates = const [],
    super.key,
  });

  final DateTime? selectedDate;
  final TimeOfDay? selectedTime;
  final String location;
  final ValueChanged<DateTime> onDateSelected;
  final ValueChanged<TimeOfDay> onTimeSelected;
  final ValueChanged<String> onLocationChanged;
  final List<DateTime> blockedDates;

  @override
  State<Step2DateLocation> createState() => _Step2DateLocationState();
}

class _Step2DateLocationState extends State<Step2DateLocation> {
  late final TextEditingController _locationController;
  late DateTime _focusedMonth;

  @override
  void initState() {
    super.initState();
    _locationController = TextEditingController(text: widget.location);
    _focusedMonth = DateTime.now();
  }

  @override
  void dispose() {
    _locationController.dispose();
    super.dispose();
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: widget.selectedTime ?? const TimeOfDay(hour: 18, minute: 0),
      builder: (ctx, child) => Theme(
        data: ThemeData.dark().copyWith(
          colorScheme: const ColorScheme.dark(
            primary: BookmiColors.brandBlue,
            surface: Color(0xFF0D1B38),
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) widget.onTimeSelected(picked);
  }

  bool _isBlocked(DateTime date) {
    return widget.blockedDates.any(
      (d) => d.year == date.year && d.month == date.month && d.day == date.day,
    );
  }

  bool _isSelected(DateTime date) {
    final sel = widget.selectedDate;
    if (sel == null) return false;
    return sel.year == date.year &&
        sel.month == date.month &&
        sel.day == date.day;
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Calendar
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Date de l\'événement',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),
                _CalendarPicker(
                  focusedMonth: _focusedMonth,
                  selectedDate: widget.selectedDate,
                  onMonthChanged: (m) => setState(() => _focusedMonth = m),
                  onDateSelected: widget.onDateSelected,
                  isBlocked: _isBlocked,
                  isSelected: _isSelected,
                ),
              ],
            ),
          ),
          // Time picker — shown once a date is selected
          if (widget.selectedDate != null) ...[
            const SizedBox(height: BookmiSpacing.spaceMd),
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Heure de l\'événement',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  GestureDetector(
                    onTap: _pickTime,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: BookmiSpacing.spaceBase,
                        vertical: BookmiSpacing.spaceSm,
                      ),
                      decoration: BoxDecoration(
                        color: BookmiColors.glassDarkMedium,
                        borderRadius: BookmiRadius.inputBorder,
                        border: Border.all(
                          color: widget.selectedTime != null
                              ? BookmiColors.brandBlue
                              : BookmiColors.glassBorder,
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.access_time_outlined,
                            color: Colors.white.withValues(alpha: 0.5),
                            size: 20,
                          ),
                          const SizedBox(width: 12),
                          Text(
                            widget.selectedTime != null
                                ? '${widget.selectedTime!.hour.toString().padLeft(2, '0')}:${widget.selectedTime!.minute.toString().padLeft(2, '0')}'
                                : 'Sélectionner une heure',
                            style: TextStyle(
                              color: widget.selectedTime != null
                                  ? Colors.white
                                  : Colors.white.withValues(alpha: 0.4),
                              fontSize: 14,
                            ),
                          ),
                          const Spacer(),
                          Icon(
                            Icons.chevron_right,
                            color: Colors.white.withValues(alpha: 0.3),
                            size: 20,
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: BookmiSpacing.spaceMd),
          // Location
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Lieu de l\'événement',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceSm),
                TextField(
                  controller: _locationController,
                  onChanged: widget.onLocationChanged,
                  style: const TextStyle(color: Colors.white, fontSize: 14),
                  decoration: InputDecoration(
                    hintText: 'Ex: Salle des fêtes, Abidjan',
                    hintStyle: TextStyle(
                      color: Colors.white.withValues(alpha: 0.4),
                      fontSize: 14,
                    ),
                    prefixIcon: Icon(
                      Icons.location_on_outlined,
                      color: Colors.white.withValues(alpha: 0.5),
                      size: 20,
                    ),
                    filled: true,
                    fillColor: BookmiColors.glassDarkMedium,
                    border: OutlineInputBorder(
                      borderRadius: BookmiRadius.inputBorder,
                      borderSide: BorderSide(color: BookmiColors.glassBorder),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BookmiRadius.inputBorder,
                      borderSide: BorderSide(color: BookmiColors.glassBorder),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BookmiRadius.inputBorder,
                      borderSide: const BorderSide(
                        color: BookmiColors.brandBlue,
                      ),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: BookmiSpacing.spaceBase,
                      vertical: BookmiSpacing.spaceSm,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _CalendarPicker extends StatelessWidget {
  const _CalendarPicker({
    required this.focusedMonth,
    required this.selectedDate,
    required this.onMonthChanged,
    required this.onDateSelected,
    required this.isBlocked,
    required this.isSelected,
  });

  final DateTime focusedMonth;
  final DateTime? selectedDate;
  final ValueChanged<DateTime> onMonthChanged;
  final ValueChanged<DateTime> onDateSelected;
  final bool Function(DateTime) isBlocked;
  final bool Function(DateTime) isSelected;

  static const _weekDays = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
  static const _maxFutureMonths = 24;

  static bool _canNavigatePrevious(DateTime focused) {
    final now = DateTime.now();
    final currentMonth = DateTime(now.year, now.month);
    final focusedMonth = DateTime(focused.year, focused.month);
    return focusedMonth.isAfter(currentMonth);
  }

  static bool _canNavigateNext(DateTime focused) {
    final now = DateTime.now();
    final limit = DateTime(now.year, now.month + _maxFutureMonths);
    final focusedMonth = DateTime(focused.year, focused.month);
    return focusedMonth.isBefore(limit);
  }

  static const _months = [
    'Janvier',
    'Février',
    'Mars',
    'Avril',
    'Mai',
    'Juin',
    'Juillet',
    'Août',
    'Septembre',
    'Octobre',
    'Novembre',
    'Décembre',
  ];

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final firstDay = DateTime(focusedMonth.year, focusedMonth.month, 1);
    // weekday: 1=Mon..7=Sun, we want Mon=0 offset
    final startOffset = (firstDay.weekday - 1) % 7;
    final daysInMonth = DateTime(
      focusedMonth.year,
      focusedMonth.month + 1,
      0,
    ).day;

    return Column(
      children: [
        // Month navigation
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            IconButton(
              icon: Icon(
                Icons.chevron_left,
                color: _canNavigatePrevious(focusedMonth)
                    ? Colors.white70
                    : Colors.white24,
              ),
              onPressed: _canNavigatePrevious(focusedMonth)
                  ? () => onMonthChanged(
                      DateTime(focusedMonth.year, focusedMonth.month - 1),
                    )
                  : null,
            ),
            Text(
              '${_months[focusedMonth.month - 1]} ${focusedMonth.year}',
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Colors.white,
              ),
            ),
            IconButton(
              icon: const Icon(Icons.chevron_right, color: Colors.white70),
              onPressed: _canNavigateNext(focusedMonth)
                  ? () => onMonthChanged(
                      DateTime(focusedMonth.year, focusedMonth.month + 1),
                    )
                  : null,
            ),
          ],
        ),
        // Weekday headers
        Row(
          children: _weekDays
              .map(
                (d) => Expanded(
                  child: Center(
                    child: Text(
                      d,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: Colors.white.withValues(alpha: 0.5),
                      ),
                    ),
                  ),
                ),
              )
              .toList(),
        ),
        const SizedBox(height: BookmiSpacing.spaceXs),
        // Days grid
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 7,
            childAspectRatio: 1,
          ),
          itemCount: startOffset + daysInMonth,
          itemBuilder: (context, index) {
            if (index < startOffset) return const SizedBox.shrink();
            final day = index - startOffset + 1;
            final date = DateTime(focusedMonth.year, focusedMonth.month, day);
            final isPast = date.isBefore(
              DateTime(now.year, now.month, now.day),
            );
            final blocked = isBlocked(date);
            final selected = isSelected(date);
            final isDisabled = isPast || blocked;

            return GestureDetector(
              onTap: isDisabled ? null : () => onDateSelected(date),
              child: Center(
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: selected
                        ? BookmiColors.brandBlue
                        : blocked
                        ? BookmiColors.error.withValues(alpha: 0.12)
                        : Colors.transparent,
                    border: selected
                        ? null
                        : !isDisabled
                        ? Border.all(color: Colors.transparent)
                        : null,
                  ),
                  child: Center(
                    child: Text(
                      '$day',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: selected
                            ? FontWeight.w700
                            : FontWeight.w400,
                        color: selected
                            ? Colors.white
                            : isDisabled
                            ? Colors.white.withValues(alpha: 0.25)
                            : Colors.white.withValues(alpha: 0.9),
                        decoration: blocked
                            ? TextDecoration.lineThrough
                            : TextDecoration.none,
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        ),
      ],
    );
  }
}
