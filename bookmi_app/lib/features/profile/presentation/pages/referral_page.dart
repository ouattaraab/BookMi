import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/features/profile/data/repositories/referral_repository.dart';
import 'package:bookmi_app/features/profile/presentation/cubits/referral_cubit.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:share_plus/share_plus.dart';

class ReferralPage extends StatelessWidget {
  const ReferralPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ReferralCubit(
        repository: ReferralRepository(apiClient: ApiClient.instance),
      )..load(),
      child: const _ReferralView(),
    );
  }
}

class _ReferralView extends StatefulWidget {
  const _ReferralView();

  @override
  State<_ReferralView> createState() => _ReferralViewState();
}

class _ReferralViewState extends State<_ReferralView> {
  final _codeController = TextEditingController();

  @override
  void dispose() {
    _codeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BookmiColors.backgroundDeep,
      appBar: AppBar(
        backgroundColor: BookmiColors.backgroundDeep,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new,
              color: Colors.white70, size: 18),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'Parrainage',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
        ),
      ),
      body: BlocConsumer<ReferralCubit, ReferralState>(
        listener: (context, state) {
          if (state is ReferralApplyError) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(state.message),
                backgroundColor: BookmiColors.error,
              ),
            );
          }
          if (state is ReferralLoaded && _codeController.text.isNotEmpty) {
            // Code applied successfully — clear field
            _codeController.clear();
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Code de parrainage appliqué !'),
                backgroundColor: Color(0xFF4CAF50),
              ),
            );
          }
        },
        builder: (context, state) {
          if (state is ReferralLoading || state is ReferralInitial) {
            return const Center(
              child: CircularProgressIndicator(color: BookmiColors.brandBlue),
            );
          }

          if (state is ReferralError) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    state.message,
                    style: const TextStyle(color: Colors.white70),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  TextButton(
                    onPressed: () => context.read<ReferralCubit>().load(),
                    child: const Text('Réessayer'),
                  ),
                ],
              ),
            );
          }

          final info = switch (state) {
            ReferralLoaded(:final info) => info,
            ReferralApplying() =>
              null, // keep showing previous content behind spinner
            _ => null,
          };

          if (info == null) {
            return const Center(
              child: CircularProgressIndicator(color: BookmiColors.brandBlue),
            );
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Hero section
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(BookmiSpacing.spaceLg),
                  decoration: BoxDecoration(
                    gradient: BookmiColors.gradientHero,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Column(
                    children: [
                      const Icon(
                        Icons.card_giftcard_outlined,
                        size: 48,
                        color: Colors.white,
                      ),
                      const SizedBox(height: BookmiSpacing.spaceSm),
                      Text(
                        'Invitez vos amis !',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Partagez votre code unique et suivez\nvos parrainages en temps réel.',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.white.withValues(alpha: 0.7),
                          height: 1.5,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),

                // Code card
                GlassCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Votre code de parrainage',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Colors.white70,
                        ),
                      ),
                      const SizedBox(height: BookmiSpacing.spaceSm),
                      Row(
                        children: [
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 12,
                              ),
                              decoration: BoxDecoration(
                                color: BookmiColors.glassDarkMedium,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: BookmiColors.brandBlue.withValues(
                                    alpha: 0.4,
                                  ),
                                ),
                              ),
                              child: Text(
                                info.code,
                                style: GoogleFonts.spaceMono(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w700,
                                  color: BookmiColors.brandBlueLight,
                                  letterSpacing: 3,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          _IconBtn(
                            icon: Icons.copy_outlined,
                            tooltip: 'Copier',
                            onTap: () {
                              Clipboard.setData(
                                ClipboardData(text: info.code),
                              );
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Code copié !'),
                                  duration: Duration(seconds: 2),
                                ),
                              );
                            },
                          ),
                          const SizedBox(width: 8),
                          _IconBtn(
                            icon: Icons.share_outlined,
                            tooltip: 'Partager',
                            onTap: () => Share.share(
                              'Rejoignez BookMi avec mon code de parrainage : ${info.code}\n'
                              'Téléchargez l\'app sur bookmi.click',
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),

                // Stats
                GlassCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Statistiques',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: BookmiSpacing.spaceSm),
                      Row(
                        children: [
                          _StatChip(
                            label: 'Total',
                            value: info.total,
                            color: BookmiColors.brandBlueLight,
                          ),
                          const SizedBox(width: 12),
                          _StatChip(
                            label: 'Complétés',
                            value: info.completed,
                            color: const Color(0xFF4CAF50),
                          ),
                          const SizedBox(width: 12),
                          _StatChip(
                            label: 'En attente',
                            value: info.pending,
                            color: Colors.orange,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),

                // Apply code section (only if hasn't used one yet)
                GlassCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Utiliser un code de parrainage',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        "Vous avez reçu un code d'un ami ? Entrez-le ici.",
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.white.withValues(alpha: 0.6),
                        ),
                      ),
                      const SizedBox(height: BookmiSpacing.spaceSm),
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _codeController,
                              textCapitalization: TextCapitalization.characters,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                letterSpacing: 1.5,
                              ),
                              decoration: InputDecoration(
                                hintText: 'CODE1234',
                                hintStyle: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.35),
                                  letterSpacing: 1.5,
                                ),
                                filled: true,
                                fillColor: BookmiColors.glassDarkMedium,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: BorderSide(
                                    color: BookmiColors.glassBorder,
                                  ),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: BorderSide(
                                    color: BookmiColors.glassBorder,
                                  ),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: const BorderSide(
                                    color: BookmiColors.brandBlue,
                                  ),
                                ),
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 10,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          BlocBuilder<ReferralCubit, ReferralState>(
                            builder: (context, applyState) {
                              if (applyState is ReferralApplying) {
                                return const SizedBox(
                                  width: 24,
                                  height: 24,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: BookmiColors.brandBlueLight,
                                  ),
                                );
                              }
                              return TextButton(
                                onPressed: () {
                                  final code = _codeController.text.trim();
                                  if (code.isNotEmpty) {
                                    context.read<ReferralCubit>().applyCode(code);
                                  }
                                },
                                style: TextButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                    vertical: 8,
                                  ),
                                  backgroundColor: BookmiColors.brandBlue
                                      .withValues(alpha: 0.2),
                                  foregroundColor: BookmiColors.brandBlueLight,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(10),
                                    side: BorderSide(
                                      color: BookmiColors.brandBlue.withValues(
                                        alpha: 0.4,
                                      ),
                                    ),
                                  ),
                                ),
                                child: const Text(
                                  'Appliquer',
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceBase),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _IconBtn extends StatelessWidget {
  const _IconBtn({
    required this.icon,
    required this.tooltip,
    required this.onTap,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: BookmiColors.brandBlue.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: BookmiColors.brandBlue.withValues(alpha: 0.3),
          ),
        ),
        child: Icon(icon, size: 18, color: BookmiColors.brandBlueLight),
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  const _StatChip({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final int value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Column(
          children: [
            Text(
              '$value',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                color: color,
              ),
            ),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                color: color.withValues(alpha: 0.8),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
