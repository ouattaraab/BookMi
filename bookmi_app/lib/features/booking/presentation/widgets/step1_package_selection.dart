import 'package:bookmi_app/core/design_system/components/service_package_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

/// Step 1 of the booking flow — the user selects a service package.
class Step1PackageSelection extends StatelessWidget {
  const Step1PackageSelection({
    required this.packages,
    required this.selectedPackageId,
    required this.onPackageSelected,
    super.key,
  });

  final List<Map<String, dynamic>> packages;
  final int? selectedPackageId;
  final ValueChanged<int> onPackageSelected;

  @override
  Widget build(BuildContext context) {
    if (packages.isEmpty) {
      return Center(
        child: Text(
          'Aucun package disponible',
          style: TextStyle(
            fontSize: 14,
            color: Colors.white.withValues(alpha: 0.5),
          ),
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      itemCount: packages.length,
      separatorBuilder: (_, __) =>
          const SizedBox(height: BookmiSpacing.spaceMd),
      itemBuilder: (context, index) {
        final pkg = packages[index];
        final attrs = pkg['attributes'] as Map<String, dynamic>? ?? pkg;
        final id = attrs['id'] as int? ?? pkg['id'] as int? ?? index;
        final type = attrs['type'] as String? ?? '';
        final isSelected = selectedPackageId == id;

        return GestureDetector(
          onTap: () => onPackageSelected(id),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 150),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(24),
              border: isSelected
                  ? Border.all(color: BookmiColors.brandBlue, width: 2)
                  : null,
              boxShadow: isSelected
                  ? [
                      BoxShadow(
                        color: BookmiColors.brandBlue.withValues(alpha: 0.25),
                        blurRadius: 12,
                        spreadRadius: 1,
                      ),
                    ]
                  : null,
            ),
            child: ServicePackageCard(
              name: attrs['name'] as String? ?? '',
              description: attrs['description'] as String?,
              cachetAmount: attrs['cachet_amount'] as int? ?? 0,
              durationMinutes: attrs['duration_minutes'] as int?,
              inclusions: (attrs['inclusions'] as List<dynamic>?)
                  ?.cast<String>(),
              type: type,
              isRecommended: type == 'premium',
            ),
          ),
        );
      },
    );
  }
}
