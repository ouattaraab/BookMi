import 'dart:io';

import 'package:bookmi_app/features/app_update/presentation/cubit/app_version_state.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class ForceUpdatePage extends StatelessWidget {
  const ForceUpdatePage({super.key, required this.data});

  final AppVersionUpdateRequired data;

  Future<void> _openStore(BuildContext context) async {
    final url = Platform.isIOS ? data.iosStoreUrl : data.androidStoreUrl;
    if (url == null || url.isEmpty) return;
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isForced = data.updateType == 'forced';

    return PopScope(
      canPop: !isForced,
      child: Scaffold(
        backgroundColor: const Color(0xFF0D1B35),
        body: Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: const BoxDecoration(
                  gradient: RadialGradient(
                    center: Alignment.center,
                    radius: 1.4,
                    colors: [Color(0xFF1E2D4E), Color(0xFF080E1E)],
                  ),
                ),
              ),
            ),
            SafeArea(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 28,
                    vertical: 24,
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 72,
                          height: 72,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: const Color(0xFFFF6B35).withOpacity(0.12),
                            border: Border.all(
                              color: const Color(0xFFFF6B35).withOpacity(0.3),
                            ),
                          ),
                          child: const Icon(
                            Icons.system_update_outlined,
                            color: Color(0xFFFF6B35),
                            size: 34,
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),

                      Center(
                        child: Text(
                          isForced
                              ? 'Mise à jour requise'
                              : 'Mise à jour disponible',
                          style: const TextStyle(
                            color: Color(0xFFF1F5F9),
                            fontSize: 22,
                            fontWeight: FontWeight.w700,
                            letterSpacing: -0.4,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      const SizedBox(height: 8),

                      Center(
                        child: Text(
                          'Version ${data.version}',
                          style: const TextStyle(
                            color: Color(0xFF64B5F6),
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),

                      if (data.message != null && data.message!.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        Text(
                          data.message!,
                          style: const TextStyle(
                            color: Color(0xFF94A3B8),
                            fontSize: 14,
                            height: 1.5,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ],

                      if (data.features.isNotEmpty) ...[
                        const SizedBox(height: 24),
                        const Text(
                          'Nouveautés',
                          style: TextStyle(
                            color: Color(0xFFF1F5F9),
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 12),
                        ...data.features.map(
                          (f) => Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Icon(
                                  Icons.check_circle_outline,
                                  color: Color(0xFF4CAF50),
                                  size: 18,
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    f,
                                    style: const TextStyle(
                                      color: Color(0xFF94A3B8),
                                      fontSize: 14,
                                      height: 1.4,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],

                      const SizedBox(height: 32),

                      // Update button
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: () => _openStore(context),
                          style: FilledButton.styleFrom(
                            backgroundColor: const Color(0xFFFF6B35),
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          icon: const Icon(Icons.download_outlined),
                          label: const Text(
                            'Mettre à jour',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),

                      if (!isForced) ...[
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: TextButton(
                            onPressed: () => Navigator.of(context).pop(),
                            child: const Text(
                              'Plus tard',
                              style: TextStyle(
                                color: Color(0xFF64748B),
                                fontSize: 14,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
