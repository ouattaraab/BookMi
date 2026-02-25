import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/components/glass_shield.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

/// Step 4 of the booking flow — Paystack payment.
///
/// Shows the total amount and Paystack branding. The user taps "Payer
/// maintenant" (in the bottom CTA) which triggers the Paystack SDK sheet.
class Step4Payment extends StatelessWidget {
  const Step4Payment({required this.totalAmount, super.key});

  final int totalAmount;

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
                  style: TextStyle(fontSize: 14, color: Colors.white70),
                ),
                Text(
                  TalentCard.formatCachet(totalAmount),
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

          // Paystack branded card
          GlassShield(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Paystack header
                Row(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: const Color(0xFF00C3F7).withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: const Color(0xFF00C3F7).withValues(alpha: 0.25),
                        ),
                      ),
                      child: const Icon(
                        Icons.payment_outlined,
                        color: Color(0xFF00C3F7),
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Paystack',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                          Text(
                            'Paiement sécurisé',
                            style: TextStyle(fontSize: 11, color: Colors.white54),
                          ),
                        ],
                      ),
                    ),
                    Icon(
                      Icons.lock_outline,
                      color: Colors.white.withValues(alpha: 0.35),
                      size: 16,
                    ),
                  ],
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),

                // Info box
                Container(
                  padding: const EdgeInsets.all(BookmiSpacing.spaceSm),
                  decoration: BoxDecoration(
                    color: BookmiColors.brandBlue.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: BookmiColors.brandBlue.withValues(alpha: 0.18),
                    ),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(
                        Icons.info_outline,
                        color: BookmiColors.brandBlue.withValues(alpha: 0.8),
                        size: 16,
                      ),
                      const SizedBox(width: 8),
                      const Expanded(
                        child: Text(
                          'En appuyant sur "Payer maintenant", une fenêtre de paiement sécurisée s\'ouvrira. Vous pourrez payer par carte bancaire ou Mobile Money.',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.white70,
                            height: 1.45,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),

                // Accepted payment icons row
                Row(
                  children: [
                    Text(
                      'Méthodes acceptées :',
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.white.withValues(alpha: 0.45),
                      ),
                    ),
                    const SizedBox(width: 8),
                    _PaymentBadge(label: 'Visa'),
                    const SizedBox(width: 4),
                    _PaymentBadge(label: 'MoMo'),
                    const SizedBox(width: 4),
                    _PaymentBadge(label: 'Orange'),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),

          Text(
            'Vos données de paiement sont chiffrées et sécurisées par Paystack.',
            style: TextStyle(
              fontSize: 11,
              color: Colors.white.withValues(alpha: 0.35),
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }
}

class _PaymentBadge extends StatelessWidget {
  const _PaymentBadge({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: Colors.white70,
        ),
      ),
    );
  }
}
