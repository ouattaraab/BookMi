import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/components/glass_shield.dart';
import 'package:bookmi_app/core/design_system/components/mobile_money_selector.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Step 4 of the booking flow — Mobile Money payment selection.
///
/// Lets the user pick their operator and enter their phone number.
/// The GlassShield visually signals the secure payment zone.
class Step4Payment extends StatefulWidget {
  const Step4Payment({
    required this.totalAmount,
    required this.selectedMethod,
    required this.phoneNumber,
    required this.onMethodSelected,
    required this.onPhoneChanged,
    super.key,
  });

  final int totalAmount;
  final String? selectedMethod;
  final String phoneNumber;
  final ValueChanged<String> onMethodSelected;
  final ValueChanged<String> onPhoneChanged;

  @override
  State<Step4Payment> createState() => _Step4PaymentState();
}

class _Step4PaymentState extends State<Step4Payment> {
  late final TextEditingController _phoneController;

  @override
  void initState() {
    super.initState();
    _phoneController = TextEditingController(text: widget.phoneNumber);
  }

  @override
  void didUpdateWidget(Step4Payment oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.phoneNumber != widget.phoneNumber &&
        _phoneController.text != widget.phoneNumber) {
      _phoneController.text = widget.phoneNumber;
    }
  }

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Amount summary
          GlassCard(
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Montant à payer',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.white70,
                  ),
                ),
                Text(
                  TalentCard.formatCachet(widget.totalAmount),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),

          // GlassShield wraps the payment form
          GlassShield(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Operator selection
                const Text(
                  'Choisir votre opérateur',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceSm),
                MobileMoneySelector(
                  selectedMethod: widget.selectedMethod,
                  onMethodSelected: (method) {
                    // Haptic feedback on operator selection (UX-FEEDBACK-1)
                    HapticFeedback.selectionClick(); // ignore: discarded_futures
                    widget.onMethodSelected(method);
                  },
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),

                // Phone number input
                const Text(
                  'Numéro Mobile Money',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceSm),
                TextField(
                  controller: _phoneController,
                  onChanged: widget.onPhoneChanged,
                  keyboardType: TextInputType.phone,
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'[0-9+]')),
                  ],
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                  ),
                  decoration: InputDecoration(
                    hintText: '+225 07 00 00 00 00',
                    hintStyle: TextStyle(
                      color: Colors.white.withValues(alpha: 0.35),
                      fontSize: 14,
                    ),
                    prefixIcon: Icon(
                      Icons.phone_outlined,
                      color: Colors.white.withValues(alpha: 0.5),
                      size: 20,
                    ),
                    filled: true,
                    fillColor: BookmiColors.glassDarkMedium,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          BorderSide(color: BookmiColors.glassBorder),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide:
                          BorderSide(color: BookmiColors.glassBorder),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
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
          const SizedBox(height: BookmiSpacing.spaceMd),

          // Info note
          Text(
            'Vous recevrez une notification de confirmation sur votre '
            'téléphone. Approuvez-la dans les 15 secondes.',
            style: TextStyle(
              fontSize: 12,
              color: Colors.white.withValues(alpha: 0.5),
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }
}
