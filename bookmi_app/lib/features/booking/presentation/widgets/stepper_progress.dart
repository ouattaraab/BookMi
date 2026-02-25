import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

/// Horizontal 4-step progress indicator for the booking flow.
class StepperProgress extends StatelessWidget {
  const StepperProgress({
    required this.currentStep,
    required this.totalSteps,
    super.key,
  });

  final int currentStep;
  final int totalSteps;

  static const _stepLabels = ['Package', 'Date', 'Récap', 'Paiement'];

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: BookmiSpacing.spaceBase,
        vertical: BookmiSpacing.spaceSm,
      ),
      child: Row(
        children: List.generate(totalSteps, (index) {
          final isCompleted = index < currentStep;
          final isActive = index == currentStep;
          final isLast = index == totalSteps - 1;

          return Expanded(
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      _StepDot(
                        isCompleted: isCompleted,
                        isActive: isActive,
                        stepNumber: index + 1,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        _stepLabels[index],
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: isActive
                              ? FontWeight.w600
                              : FontWeight.w400,
                          color: isActive || isCompleted
                              ? BookmiColors.brandBlueLight
                              : Colors.white.withValues(alpha: 0.4),
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
                if (!isLast)
                  Expanded(
                    child: Container(
                      height: 2,
                      margin: const EdgeInsets.only(
                        bottom: BookmiSpacing.spaceBase,
                      ),
                      decoration: BoxDecoration(
                        color: isCompleted
                            ? BookmiColors.brandBlue
                            : Colors.white.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(1),
                      ),
                    ),
                  ),
              ],
            ),
          );
        }),
      ),
    );
  }
}

class _StepDot extends StatelessWidget {
  const _StepDot({
    required this.isCompleted,
    required this.isActive,
    required this.stepNumber,
  });

  final bool isCompleted;
  final bool isActive;
  final int stepNumber;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      width: isActive ? 28 : 22,
      height: isActive ? 28 : 22,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: isCompleted
            ? BookmiColors.brandBlue
            : isActive
            ? BookmiColors.brandBlueLight
            : Colors.white.withValues(alpha: 0.1),
        border: Border.all(
          color: isActive
              ? BookmiColors.brandBlueLight
              : isCompleted
              ? BookmiColors.brandBlue
              : Colors.white.withValues(alpha: 0.25),
          width: isActive ? 2 : 1,
        ),
      ),
      child: Center(
        child: isCompleted
            ? const Icon(Icons.check, size: 14, color: Colors.white)
            : Text(
                '$stepNumber',
                style: TextStyle(
                  fontSize: isActive ? 12 : 10,
                  fontWeight: FontWeight.w600,
                  color: isActive
                      ? Colors.white
                      : Colors.white.withValues(alpha: 0.5),
                ),
              ),
      ),
    );
  }
}
