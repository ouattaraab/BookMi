import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);

class PaymentMethodsPage extends StatelessWidget {
  const PaymentMethodsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _muted,
      appBar: AppBar(
        backgroundColor: _secondary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          'Moyens de paiement',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: const Color(0xFF3B9DF2).withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.payment_outlined,
                  size: 36,
                  color: Color(0xFF3B9DF2),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                'Fonctionnalité à venir',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: _secondary,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                'La gestion des moyens de paiement sera '
                'disponible prochainement.\n\n'
                'Les paiements sont actuellement gérés via '
                'Mobile Money (Orange Money, Wave, MTN) '
                'directement lors de la réservation.',
                style: GoogleFonts.manrope(
                  fontSize: 14,
                  color: _mutedFg,
                  height: 1.6,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  _PaymentBadge(
                    label: 'Orange Money',
                    color: const Color(0xFFFF6B35),
                  ),
                  const SizedBox(width: 10),
                  _PaymentBadge(
                    label: 'Wave',
                    color: const Color(0xFF1A9AE5),
                  ),
                  const SizedBox(width: 10),
                  _PaymentBadge(
                    label: 'MTN MoMo',
                    color: const Color(0xFFFFCC00),
                    dark: true,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PaymentBadge extends StatelessWidget {
  const _PaymentBadge({
    required this.label,
    required this.color,
    this.dark = false,
  });

  final String label;
  final Color color;
  final bool dark;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Text(
        label,
        style: GoogleFonts.manrope(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: dark ? Colors.amber.shade800 : color,
        ),
      ),
    );
  }
}
