import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/features/consent/bloc/consent_cubit.dart';
import 'package:bookmi_app/features/consent/bloc/consent_state.dart';
import 'package:bookmi_app/features/consent/data/repositories/consent_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Page de gestion des consentements opt-in (/profile/consents).
class ConsentSettingsPage extends StatelessWidget {
  const ConsentSettingsPage({super.key});

  static Widget wrapped(BuildContext context) {
    return BlocProvider(
      create: (_) => ConsentCubit(
        repository: ConsentRepository(apiClient: context.read<ApiClient>()),
      )..fetchConsents(),
      child: const ConsentSettingsPage(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0D1117),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0D1117),
        title: const Text(
          'Mes consentements',
          style: TextStyle(color: Colors.white),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: BlocConsumer<ConsentCubit, ConsentState>(
        listener: (context, state) {
          if (state is ConsentSuccess) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(state.message),
                backgroundColor: BookmiColors.success,
              ),
            );
            // Refresh
            context.read<ConsentCubit>().fetchConsents();
          } else if (state is ConsentFailure) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(state.message),
                backgroundColor: BookmiColors.error,
              ),
            );
          }
        },
        builder: (context, state) {
          if (state is ConsentLoading || state is ConsentInitial) {
            return const Center(
              child: CircularProgressIndicator(color: BookmiColors.brandBlue),
            );
          }

          if (state is ConsentFailure) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    state.message,
                    style: const TextStyle(color: Colors.white70),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: BookmiSpacing.spaceBase),
                  ElevatedButton(
                    onPressed: () =>
                        context.read<ConsentCubit>().fetchConsents(),
                    child: const Text('Réessayer'),
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

          final optIns = [
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
              label: 'Droit à l\'image',
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
              const Text(
                'Préférences opt-in',
                style: TextStyle(
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
                    context
                        .read<ConsentCubit>()
                        .updateOptIns({item.key: value});
                  },
                ),
              ),
              const SizedBox(height: BookmiSpacing.spaceLg),
              // Obligatory consents — read only
              if (allConsents.isNotEmpty) ...[
                const Text(
                  'Consentements obligatoires',
                  style: TextStyle(
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
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
      ),
      child: SwitchListTile(
        secondary: Icon(item.icon, color: BookmiColors.brandBlueLight),
        title: Text(
          item.label,
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w500,
          ),
        ),
        subtitle: Text(
          item.subtitle,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.6),
            fontSize: 12,
          ),
        ),
        value: currentValue,
        onChanged: isLoading ? null : onChanged,
        activeColor: BookmiColors.brandBlue,
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
    final label = consent['label'] as String? ?? consent['type'] as String? ?? '';
    final date = consent['consented_at'] as String?;

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(
        horizontal: BookmiSpacing.spaceBase,
        vertical: 10,
      ),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.03),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.white.withValues(alpha: 0.06)),
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
                  style: const TextStyle(color: Colors.white, fontSize: 13),
                ),
                if (date != null)
                  Text(
                    _formatDate(date),
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.4),
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
    } catch (_) {
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
        color: (upToDate
                ? BookmiColors.success
                : BookmiColors.error)
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
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  'Version acceptée : ${cguVersionAccepted ?? 'aucune'} · Actuelle : $currentCguVersion',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.6),
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
