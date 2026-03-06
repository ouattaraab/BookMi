import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/features/consent/bloc/consent_cubit.dart';
import 'package:bookmi_app/features/consent/bloc/consent_state.dart';
import 'package:bookmi_app/features/consent/data/repositories/consent_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

/// Page de gestion des consentements opt-in (/profile/consents).
class ConsentSettingsPage extends StatelessWidget {
  const ConsentSettingsPage({super.key});

  static Widget wrapped(BuildContext context) {
    return BlocProvider(
      create: (_) => ConsentCubit(
        repository: ConsentRepository(apiClient: ApiClient.instance),
      )..fetchConsents(),
      child: const ConsentSettingsPage(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BookmiColors.backgroundDeep,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        title: Text(
          'Mes consentements',
          style: GoogleFonts.manrope(
            color: Colors.white,
            fontWeight: FontWeight.w700,
            fontSize: 17,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: BlocConsumer<ConsentCubit, ConsentState>(
        listener: (context, state) {
          if (state is ConsentSuccess) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(
                  state.message,
                  style: GoogleFonts.manrope(color: Colors.white),
                ),
                backgroundColor: BookmiColors.success,
              ),
            );
            // Refresh
            context.read<ConsentCubit>().fetchConsents();
          } else if (state is ConsentFailure) {
            if (state.code == 'CGU_REQUIRED') {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                    'Veuillez accepter les nouvelles CGU pour modifier vos préférences.',
                    style: GoogleFonts.manrope(color: Colors.white),
                  ),
                  backgroundColor: BookmiColors.warning,
                ),
              );
              context.go(RoutePaths.consentUpdate);
            } else {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                    state.message,
                    style: GoogleFonts.manrope(color: Colors.white),
                  ),
                  backgroundColor: BookmiColors.error,
                ),
              );
            }
          }
        },
        builder: (context, state) {
          if (state is ConsentLoading || state is ConsentInitial) {
            return const Center(
              child: CircularProgressIndicator(color: BookmiColors.brandBlue),
            );
          }

          if (state is ConsentFailure && state.code != 'CGU_REQUIRED') {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    state.message,
                    style: GoogleFonts.manrope(color: Colors.white70),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: BookmiSpacing.spaceBase),
                  ElevatedButton(
                    onPressed: () =>
                        context.read<ConsentCubit>().fetchConsents(),
                    child: Text(
                      'Réessayer',
                      style: GoogleFonts.manrope(),
                    ),
                  ),
                ],
              ),
            );
          }

          final loaded = state is ConsentLoaded ? state : null;
          final allConsents = loaded?.consents ?? [];

          // Build a map of latest consent per type
          final consentMap = <String, Map<String, dynamic>>{};
          for (final c in allConsents) {
            final type = c['type'] as String? ?? '';
            consentMap[type] = c;
          }

          const optIns = [
            _OptInItem(
              key: 'marketing',
              label: 'Communications marketing',
              subtitle: 'Recevoir des offres et promotions BookMi.',
              icon: Icons.campaign_outlined,
            ),
            _OptInItem(
              key: 'geolocation',
              label: 'Géolocalisation',
              subtitle: 'Permettre la recherche de talents autour de vous.',
              icon: Icons.location_on_outlined,
            ),
            _OptInItem(
              key: 'image_rights',
              label: "Droit à l'image",
              subtitle:
                  'Autoriser BookMi à utiliser vos photos pour promouvoir la plateforme.',
              icon: Icons.photo_camera_outlined,
            ),
            _OptInItem(
              key: 'satisfaction_surveys',
              label: 'Enquêtes de satisfaction',
              subtitle: 'Participer aux sondages pour améliorer BookMi.',
              icon: Icons.poll_outlined,
            ),
          ];

          return ListView(
            padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
            children: [
              // CGU version info
              _InfoCard(
                cguVersionAccepted: loaded?.cguVersionAccepted,
                currentCguVersion: loaded?.currentCguVersion ?? '1.0',
              ),
              const SizedBox(height: BookmiSpacing.spaceBase),
              Text(
                'Préférences opt-in',
                style: GoogleFonts.manrope(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: BookmiSpacing.spaceSm),
              ...optIns.map(
                (item) => _OptInTile(
                  item: item,
                  currentValue:
                      consentMap[item.key]?['status'] as bool? ?? false,
                  isLoading: state is ConsentUpdating,
                  onChanged: (value) {
                    context.read<ConsentCubit>().updateOptIns({
                      item.key: value,
                    });
                  },
                ),
              ),
              const SizedBox(height: BookmiSpacing.spaceLg),
              // Obligatory consents — read only
              if (allConsents.isNotEmpty) ...[
                Text(
                  'Consentements obligatoires',
                  style: GoogleFonts.manrope(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceSm),
                ...allConsents
                    .where(
                      (c) => ![
                        'marketing',
                        'geolocation',
                        'image_rights',
                        'satisfaction_surveys',
                        'push_notifications',
                      ].contains(c['type']),
                    )
                    .map((c) => _ReadOnlyConsentTile(consent: c)),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _OptInItem {
  const _OptInItem({
    required this.key,
    required this.label,
    required this.subtitle,
    required this.icon,
  });

  final String key;
  final String label;
  final String subtitle;
  final IconData icon;
}

class _OptInTile extends StatelessWidget {
  const _OptInTile({
    required this.item,
    required this.currentValue,
    required this.isLoading,
    required this.onChanged,
  });

  final _OptInItem item;
  final bool currentValue;
  final bool isLoading;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: BookmiColors.backgroundCard,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: BookmiColors.glassBorder),
      ),
      child: Row(
        children: [
          Icon(item.icon, color: BookmiColors.brandBlueLight, size: 22),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.label,
                  style: GoogleFonts.manrope(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  item.subtitle,
                  style: GoogleFonts.manrope(
                    color: Colors.white54,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          Switch.adaptive(
            value: currentValue,
            onChanged: isLoading ? null : onChanged,
            activeThumbColor: BookmiColors.brandBlue,
            activeTrackColor: BookmiColors.brandBlue.withValues(alpha: 0.5),
          ),
        ],
      ),
    );
  }
}

class _ReadOnlyConsentTile extends StatelessWidget {
  const _ReadOnlyConsentTile({required this.consent});

  final Map<String, dynamic> consent;

  @override
  Widget build(BuildContext context) {
    final status = consent['status'] as bool? ?? false;
    final label =
        consent['label'] as String? ?? consent['type'] as String? ?? '';
    final date = consent['consented_at'] as String?;

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(
        horizontal: BookmiSpacing.spaceBase,
        vertical: 10,
      ),
      decoration: BoxDecoration(
        color: BookmiColors.backgroundCard,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: BookmiColors.glassBorder),
      ),
      child: Row(
        children: [
          Icon(
            status ? Icons.check_circle : Icons.cancel,
            color: status ? BookmiColors.success : BookmiColors.error,
            size: 18,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: GoogleFonts.manrope(
                    color: Colors.white,
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                if (date != null)
                  Text(
                    _formatDate(date),
                    style: GoogleFonts.manrope(
                      color: Colors.white38,
                      fontSize: 11,
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(String iso) {
    try {
      final dt = DateTime.parse(iso).toLocal();
      return '${dt.day.toString().padLeft(2, '0')}/'
          '${dt.month.toString().padLeft(2, '0')}/'
          '${dt.year} ${dt.hour.toString().padLeft(2, '0')}:'
          '${dt.minute.toString().padLeft(2, '0')}';
    } on FormatException {
      return iso;
    }
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({
    required this.cguVersionAccepted,
    required this.currentCguVersion,
  });

  final String? cguVersionAccepted;
  final String currentCguVersion;

  @override
  Widget build(BuildContext context) {
    final upToDate = cguVersionAccepted == currentCguVersion;
    return Container(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      decoration: BoxDecoration(
        color: (upToDate ? BookmiColors.success : BookmiColors.error)
            .withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: (upToDate ? BookmiColors.success : BookmiColors.error)
              .withValues(alpha: 0.3),
        ),
      ),
      child: Row(
        children: [
          Icon(
            upToDate ? Icons.verified_outlined : Icons.warning_amber_outlined,
            color: upToDate ? BookmiColors.success : BookmiColors.error,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  upToDate ? 'CGU à jour' : 'CGU non acceptées',
                  style: GoogleFonts.manrope(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  'Version acceptée : ${cguVersionAccepted ?? 'aucune'} · Actuelle : $currentCguVersion',
                  style: GoogleFonts.manrope(
                    color: Colors.white60,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
