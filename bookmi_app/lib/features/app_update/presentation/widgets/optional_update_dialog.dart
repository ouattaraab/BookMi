import 'dart:io';

import 'package:bookmi_app/features/app_update/presentation/cubit/app_version_state.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

class OptionalUpdateDialog extends StatelessWidget {
  const OptionalUpdateDialog({super.key, required this.data});

  final AppVersionUpdateRequired data;

  Future<void> _openStore() async {
    final url = Platform.isIOS ? data.iosStoreUrl : data.androidStoreUrl;
    if (url == null || url.isEmpty) return;
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  static Future<void> show(
    BuildContext context,
    AppVersionUpdateRequired data,
  ) {
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => OptionalUpdateDialog(data: data),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: const Color(0xFF1A2744),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFFFF6B35).withOpacity(0.15),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(
              Icons.system_update_outlined,
              color: Color(0xFFFF6B35),
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          const Expanded(
            child: Text(
              'Mise à jour disponible',
              style: TextStyle(
                color: Color(0xFFF1F5F9),
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (data.message != null && data.message!.isNotEmpty)
            Text(
              data.message!,
              style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
            ),
          if (data.features.isNotEmpty) ...[
            const SizedBox(height: 12),
            ...data.features.map(
              (f) => Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(
                      Icons.check_circle_outline,
                      color: Color(0xFF4CAF50),
                      size: 15,
                    ),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        f,
                        style: const TextStyle(
                          color: Color(0xFF94A3B8),
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text(
            'Plus tard',
            style: TextStyle(color: Color(0xFF64748B)),
          ),
        ),
        FilledButton(
          onPressed: () async {
            Navigator.of(context).pop();
            await _openStore();
          },
          style: FilledButton.styleFrom(
            backgroundColor: const Color(0xFFFF6B35),
          ),
          child: const Text('Mettre à jour'),
        ),
      ],
    );
  }
}
