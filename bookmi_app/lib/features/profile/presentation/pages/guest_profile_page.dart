import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

/// Page affichée dans l'onglet Profil pour les utilisateurs non connectés.
/// Présente les bénéfices de rejoindre BookMi.
class GuestProfilePage extends StatelessWidget {
  const GuestProfilePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Column(
            children: [
              const SizedBox(height: 48),

              // Logo BookMi
              ShaderMask(
                shaderCallback: (bounds) => const LinearGradient(
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                  colors: [Color(0xFFFFFFFF), Color(0xFFD0E8FF)],
                  stops: [0.50, 1.0],
                ).createShader(bounds),
                blendMode: BlendMode.srcIn,
                child: Image.asset(
                  'assets/images/bookmi_logo.png',
                  width: 180,
                  fit: BoxFit.contain,
                ),
              ),

              const SizedBox(height: 32),

              // Titre
              Text(
                'Rejoignez BookMi',
                style: GoogleFonts.nunito(
                  fontSize: 26,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                ),
                textAlign: TextAlign.center,
              ),

              const SizedBox(height: 8),

              Text(
                'Créez un compte pour profiter\nde toutes les fonctionnalités.',
                style: GoogleFonts.manrope(
                  fontSize: 14,
                  color: Colors.white.withValues(alpha: 0.65),
                ),
                textAlign: TextAlign.center,
              ),

              const SizedBox(height: 40),

              // Liste des avantages
              const _BenefitItem(
                icon: Icons.bolt_outlined,
                text: 'Réservez les meilleurs talents en quelques clics',
              ),
              const SizedBox(height: 16),
              const _BenefitItem(
                icon: Icons.track_changes_outlined,
                text: 'Suivez vos réservations en temps réel',
              ),
              const SizedBox(height: 16),
              const _BenefitItem(
                icon: Icons.chat_bubble_outline,
                text: 'Messagerie directe avec les talents',
              ),
              const SizedBox(height: 16),
              const _BenefitItem(
                icon: Icons.shield_outlined,
                text: 'Paiement sécurisé',
              ),

              const SizedBox(height: 48),

              // Bouton primaire — Créer un compte
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFF6B35),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    elevation: 0,
                  ),
                  onPressed: () => context.go(RoutePaths.register),
                  child: Text(
                    'Créer un compte',
                    style: GoogleFonts.nunito(
                      fontWeight: FontWeight.w800,
                      fontSize: 16,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 12),

              // Bouton secondaire — Déjà un compte
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: BorderSide(
                      color: Colors.white.withValues(alpha: 0.3),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  onPressed: () => context.go(RoutePaths.login),
                  child: Text(
                    "J'ai déjà un compte",
                    style: GoogleFonts.manrope(
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Widget avantage ──────────────────────────────────────────────────────────

class _BenefitItem extends StatelessWidget {
  const _BenefitItem({
    required this.icon,
    required this.text,
  });

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: const Color(0xFFFF6B35).withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: const Color(0xFFFF6B35).withValues(alpha: 0.25),
            ),
          ),
          child: Icon(
            icon,
            color: const Color(0xFFFF6B35),
            size: 22,
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Text(
            text,
            style: GoogleFonts.manrope(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: Colors.white.withValues(alpha: 0.9),
            ),
          ),
        ),
      ],
    );
  }
}
