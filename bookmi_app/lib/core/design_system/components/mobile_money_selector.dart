import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

/// Operator descriptor for Mobile Money selection.
class MobileMoneyOperator {
  const MobileMoneyOperator({
    required this.method,
    required this.label,
    required this.color,
    required this.icon,
  });

  final String method; // API value: 'orange_money', 'wave', 'mtn_momo', 'moov_money'
  final String label;
  final Color color;
  final IconData icon;
}

/// Grid of operator selector buttons for Mobile Money payment.
///
/// Used in Step4Payment to let the user pick their preferred operator.
class MobileMoneySelector extends StatelessWidget {
  MobileMoneySelector({
    required this.selectedMethod,
    required this.onMethodSelected,
    super.key,
  });

  final String? selectedMethod;
  final ValueChanged<String> onMethodSelected;

  final _operators = const [
    MobileMoneyOperator(
      method: 'orange_money',
      label: 'Orange Money',
      color: Color(0xFFFF6B00),
      icon: Icons.signal_cellular_alt,
    ),
    MobileMoneyOperator(
      method: 'wave',
      label: 'Wave',
      color: Color(0xFF0075FF),
      icon: Icons.waves,
    ),
    MobileMoneyOperator(
      method: 'mtn_momo',
      label: 'MTN MoMo',
      color: Color(0xFFFFCC00),
      icon: Icons.phone_android,
    ),
    MobileMoneyOperator(
      method: 'moov_money',
      label: 'Moov Money',
      color: Color(0xFF00A859),
      icon: Icons.mobile_friendly,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      mainAxisSpacing: BookmiSpacing.spaceSm,
      crossAxisSpacing: BookmiSpacing.spaceSm,
      childAspectRatio: 2.2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      children: _operators.map((op) => _OperatorTile(
        operator: op,
        isSelected: selectedMethod == op.method,
        onTap: () => onMethodSelected(op.method),
      )).toList(),
    );
  }
}

class _OperatorTile extends StatelessWidget {
  const _OperatorTile({
    required this.operator,
    required this.isSelected,
    required this.onTap,
  });

  final MobileMoneyOperator operator;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        decoration: BoxDecoration(
          color: isSelected
              ? operator.color.withValues(alpha: 0.18)
              : Colors.white.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected
                ? operator.color.withValues(alpha: 0.7)
                : Colors.white.withValues(alpha: 0.15),
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              operator.icon,
              size: 20,
              color: isSelected ? operator.color : Colors.white60,
            ),
            const SizedBox(width: BookmiSpacing.spaceXs),
            Flexible(
              child: Text(
                operator.label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: isSelected ? FontWeight.w700 : FontWeight.w400,
                  color: isSelected ? Colors.white : Colors.white60,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
