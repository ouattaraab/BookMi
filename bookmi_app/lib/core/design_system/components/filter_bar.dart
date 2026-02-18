import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

class FilterItem {
  const FilterItem({
    required this.key,
    required this.label,
  });

  final String key;
  final String label;
}

class FilterBar extends StatelessWidget {
  const FilterBar({
    required this.filters,
    required this.activeFilters,
    required this.onFilterChanged,
    required this.onClearAll,
    super.key,
  });

  final List<FilterItem> filters;
  final Set<String> activeFilters;
  final ValueChanged<String> onFilterChanged;
  final VoidCallback onClearAll;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 48,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(
          horizontal: BookmiSpacing.spaceBase,
          vertical: BookmiSpacing.spaceXs,
        ),
        itemCount: filters.length + (activeFilters.isNotEmpty ? 1 : 0),
        separatorBuilder: (_, _) =>
            const SizedBox(width: BookmiSpacing.spaceSm),
        itemBuilder: (context, index) {
          if (index == filters.length && activeFilters.isNotEmpty) {
            return _buildClearButton();
          }
          final filter = filters[index];
          final isActive = activeFilters.contains(filter.key);
          return _buildChip(filter, isActive: isActive);
        },
      ),
    );
  }

  Widget _buildChip(FilterItem filter, {required bool isActive}) {
    return GestureDetector(
      onTap: () => onFilterChanged(filter.key),
      child: Container(
        padding: const EdgeInsets.symmetric(
          horizontal: BookmiSpacing.spaceMd,
          vertical: BookmiSpacing.spaceXs,
        ),
        decoration: BoxDecoration(
          color: isActive ? BookmiColors.brandBlue : Colors.transparent,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(
            color: isActive ? BookmiColors.brandBlue : BookmiColors.glassBorder,
          ),
        ),
        alignment: Alignment.center,
        child: Text(
          filter.label,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: isActive
                ? Colors.white
                : Colors.white.withValues(alpha: 0.6),
          ),
        ),
      ),
    );
  }

  Widget _buildClearButton() {
    return GestureDetector(
      onTap: onClearAll,
      child: Container(
        padding: const EdgeInsets.symmetric(
          horizontal: BookmiSpacing.spaceMd,
          vertical: BookmiSpacing.spaceXs,
        ),
        alignment: Alignment.center,
        child: const Text(
          'Effacer',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: BookmiColors.brandBlue,
          ),
        ),
      ),
    );
  }
}
