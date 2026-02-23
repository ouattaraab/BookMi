import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';

const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _success = Color(0xFF14B8A6);
const _primary = Color(0xFF3B9DF2);

class IdentityVerificationPage extends StatelessWidget {
  const IdentityVerificationPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        final user =
            state is AuthAuthenticated ? state.user : null;
        final isVerified =
            user?.phoneVerifiedAt != null;

        return Scaffold(
          backgroundColor: _muted,
          appBar: AppBar(
            backgroundColor: _secondary,
            foregroundColor: Colors.white,
            elevation: 0,
            title: Text(
              "Vérification d'identité",
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w700,
                color: Colors.white,
                fontSize: 16,
              ),
            ),
          ),
          body: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              // Status card
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 10,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Column(
                  children: [
                    Container(
                      width: 64,
                      height: 64,
                      decoration: BoxDecoration(
                        color: (isVerified ? _success : Colors.orange)
                            .withValues(alpha: 0.1),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        isVerified
                            ? Icons.verified_user_outlined
                            : Icons.gpp_maybe_outlined,
                        size: 32,
                        color: isVerified ? _success : Colors.orange,
                      ),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      isVerified
                          ? 'Téléphone vérifié'
                          : 'Vérification en attente',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: _secondary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      isVerified
                          ? 'Votre numéro de téléphone a été vérifié '
                              'avec succès. Votre compte est sécurisé.'
                          : 'Votre numéro de téléphone n\'a pas encore '
                              'été vérifié. Vérifiez votre SMS.',
                      style: GoogleFonts.manrope(
                        fontSize: 13,
                        color: _mutedFg,
                        height: 1.5,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    if (user?.phoneVerifiedAt != null) ...[
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: _success.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: _success.withValues(alpha: 0.25),
                          ),
                        ),
                        child: Text(
                          'Vérifié',
                          style: GoogleFonts.manrope(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: _success,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 16),
              // Steps
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 10,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Niveaux de vérification',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: _secondary,
                      ),
                    ),
                    const SizedBox(height: 14),
                    _VerificationStep(
                      icon: Icons.phone_outlined,
                      title: 'Numéro de téléphone',
                      description: 'Vérification par SMS OTP',
                      isDone: isVerified,
                    ),
                    const SizedBox(height: 10),
                    _VerificationStep(
                      icon: Icons.badge_outlined,
                      title: "Pièce d'identité",
                      description:
                          "CNI, passeport ou permis de conduire",
                      isDone: false,
                      isPending: true,
                    ),
                    const SizedBox(height: 10),
                    _VerificationStep(
                      icon: Icons.face_outlined,
                      title: 'Selfie de vérification',
                      description: 'Photo en temps réel avec document',
                      isDone: false,
                      isPending: true,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: _primary.withValues(alpha: 0.06),
                  borderRadius: BorderRadius.circular(12),
                  border:
                      Border.all(color: _primary.withValues(alpha: 0.2)),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.lock_outline,
                      size: 16,
                      color: _primary,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'La vérification complète de l\'identité '
                        'sera disponible prochainement.',
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          color: _primary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _VerificationStep extends StatelessWidget {
  const _VerificationStep({
    required this.icon,
    required this.title,
    required this.description,
    required this.isDone,
    this.isPending = false,
  });

  final IconData icon;
  final String title;
  final String description;
  final bool isDone;
  final bool isPending;

  @override
  Widget build(BuildContext context) {
    final color = isDone
        ? _success
        : isPending
            ? _mutedFg
            : const Color(0xFFFF6B35);

    return Row(
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 18, color: color),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: _secondary,
                ),
              ),
              Text(
                description,
                style: GoogleFonts.manrope(
                  fontSize: 11,
                  color: _mutedFg,
                ),
              ),
            ],
          ),
        ),
        if (isDone)
          const Icon(Icons.check_circle, size: 18, color: _success)
        else
          Icon(
            isPending
                ? Icons.radio_button_unchecked
                : Icons.chevron_right,
            size: 18,
            color: _mutedFg,
          ),
      ],
    );
  }
}
