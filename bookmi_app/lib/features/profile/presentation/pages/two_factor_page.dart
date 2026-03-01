import 'dart:async';

import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/two_factor_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

class TwoFactorPage extends StatefulWidget {
  const TwoFactorPage({super.key});

  @override
  State<TwoFactorPage> createState() => _TwoFactorPageState();
}

class _TwoFactorPageState extends State<TwoFactorPage> {
  late final TwoFactorRepository _repo;
  bool _loadingStatus = true;
  bool _enabled = false;
  String? _method; // 'totp' | 'email' | null
  String? _error;

  @override
  void initState() {
    super.initState();
    _repo = TwoFactorRepository(apiClient: ApiClient.instance);
    _loadStatus();
  }

  Future<void> _loadStatus() async {
    setState(() {
      _loadingStatus = true;
      _error = null;
    });
    final result = await _repo.getStatus();
    if (!mounted) return;
    setState(() {
      _loadingStatus = false;
      switch (result) {
        case ApiSuccess(:final data):
          _enabled = (data['enabled'] as bool?) ?? false;
          _method = data['method'] as String?;
        case ApiFailure(:final message):
          _error = message;
      }
    });
  }

  Future<void> _startSetupTotp() async {
    final result = await _repo.setupTotp();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        await _showTotpEnableSheet(data);
      case ApiFailure(:final message):
        _showError(message);
    }
  }

  Future<void> _startSetupEmail() async {
    setState(() => _error = null);
    final result = await _repo.setupEmail();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess():
        await _showEmailEnableSheet();
      case ApiFailure(:final message):
        _showError(message);
    }
  }

  Future<void> _showTotpEnableSheet(Map<String, dynamic> data) async {
    final secret = data['secret'] as String? ?? '';
    final qrSvg = data['qr_code_svg'] as String? ?? '';
    final codeController = TextEditingController();
    bool loading = false;
    String? sheetError;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF1A2233),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) => Padding(
          padding: EdgeInsets.only(
            left: 24,
            right: 24,
            top: 24,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Activer l\'authentificateur',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Scannez le QR code avec Google Authenticator ou Authy, puis entrez le code à 6 chiffres.',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  color: Colors.white70,
                ),
              ),
              const SizedBox(height: 16),
              // QR placeholder (SVG can't be rendered without plugin — show secret instead)
              if (qrSvg.isNotEmpty)
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.05),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.1),
                    ),
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.qr_code_2,
                        color: Colors.white70,
                        size: 28,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Clé secrète manuelle :',
                              style: GoogleFonts.manrope(
                                fontSize: 11,
                                color: Colors.white54,
                              ),
                            ),
                            const SizedBox(height: 4),
                            SelectableText(
                              secret,
                              style: GoogleFonts.jetBrainsMono(
                                fontSize: 13,
                                color: BookmiColors.brandBlueLight,
                                letterSpacing: 1.5,
                              ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        onPressed: () {
                          Clipboard.setData(ClipboardData(text: secret));
                          ScaffoldMessenger.of(ctx).showSnackBar(
                            const SnackBar(
                              content: Text('Clé copiée'),
                              duration: Duration(seconds: 2),
                            ),
                          );
                        },
                        icon: const Icon(
                          Icons.copy,
                          size: 16,
                          color: Colors.white54,
                        ),
                      ),
                    ],
                  ),
                ),
              const SizedBox(height: 16),
              TextField(
                controller: codeController,
                keyboardType: TextInputType.number,
                maxLength: 6,
                decoration: InputDecoration(
                  labelText: 'Code à 6 chiffres',
                  labelStyle: const TextStyle(color: Colors.white54),
                  counterText: '',
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: BorderSide(
                      color: Colors.white.withValues(alpha: 0.2),
                    ),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: const BorderSide(
                      color: BookmiColors.brandBlueLight,
                    ),
                  ),
                  filled: true,
                  fillColor: Colors.white.withValues(alpha: 0.05),
                ),
                style: const TextStyle(color: Colors.white),
              ),
              if (sheetError != null) ...[
                const SizedBox(height: 8),
                Text(
                  sheetError!,
                  style: const TextStyle(
                    color: Colors.redAccent,
                    fontSize: 12,
                  ),
                ),
              ],
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: BookmiColors.brandBlueLight,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  onPressed: loading
                      ? null
                      : () async {
                          final code = codeController.text.trim();
                          if (code.length != 6) {
                            setSheetState(
                              () => sheetError = 'Entrez un code à 6 chiffres',
                            );
                            return;
                          }
                          setSheetState(() => loading = true);
                          final r = await _repo.enableTotp(code);
                          if (!ctx.mounted) return;
                          switch (r) {
                            case ApiSuccess():
                              Navigator.of(ctx).pop();
                              _loadStatus();
                            case ApiFailure(:final message):
                              setSheetState(() {
                                loading = false;
                                sheetError = message;
                              });
                          }
                        },
                  child: loading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : Text(
                          'Activer',
                          style: GoogleFonts.manrope(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _showEmailEnableSheet() async {
    final codeController = TextEditingController();
    bool loading = false;
    String? sheetError;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF1A2233),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) => Padding(
          padding: EdgeInsets.only(
            left: 24,
            right: 24,
            top: 24,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Activer la 2FA par email',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Un code à 6 chiffres vient d\'être envoyé à votre adresse email. Entrez-le ci-dessous pour activer.',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  color: Colors.white70,
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: codeController,
                keyboardType: TextInputType.number,
                maxLength: 6,
                decoration: InputDecoration(
                  labelText: 'Code à 6 chiffres',
                  labelStyle: const TextStyle(color: Colors.white54),
                  counterText: '',
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: BorderSide(
                      color: Colors.white.withValues(alpha: 0.2),
                    ),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: const BorderSide(
                      color: BookmiColors.brandBlueLight,
                    ),
                  ),
                  filled: true,
                  fillColor: Colors.white.withValues(alpha: 0.05),
                ),
                style: const TextStyle(color: Colors.white),
              ),
              if (sheetError != null) ...[
                const SizedBox(height: 8),
                Text(
                  sheetError!,
                  style: const TextStyle(
                    color: Colors.redAccent,
                    fontSize: 12,
                  ),
                ),
              ],
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: BookmiColors.brandBlueLight,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  onPressed: loading
                      ? null
                      : () async {
                          final code = codeController.text.trim();
                          if (code.length != 6) {
                            setSheetState(
                              () => sheetError = 'Entrez un code à 6 chiffres',
                            );
                            return;
                          }
                          setSheetState(() => loading = true);
                          final r = await _repo.enableEmail(code);
                          if (!ctx.mounted) return;
                          switch (r) {
                            case ApiSuccess():
                              Navigator.of(ctx).pop();
                              _loadStatus();
                            case ApiFailure(:final message):
                              setSheetState(() {
                                loading = false;
                                sheetError = message;
                              });
                          }
                        },
                  child: loading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : Text(
                          'Activer',
                          style: GoogleFonts.manrope(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _disableTwoFactor() async {
    final passwordController = TextEditingController();
    bool loading = false;
    String? sheetError;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF1A2233),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) => Padding(
          padding: EdgeInsets.only(
            left: 24,
            right: 24,
            top: 24,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Désactiver la 2FA',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Entrez votre mot de passe pour désactiver l\'authentification à deux facteurs.',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  color: Colors.white70,
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: passwordController,
                obscureText: true,
                decoration: InputDecoration(
                  labelText: 'Mot de passe',
                  labelStyle: const TextStyle(color: Colors.white54),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: BorderSide(
                      color: Colors.white.withValues(alpha: 0.2),
                    ),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                    borderSide: const BorderSide(
                      color: BookmiColors.brandBlueLight,
                    ),
                  ),
                  filled: true,
                  fillColor: Colors.white.withValues(alpha: 0.05),
                ),
                style: const TextStyle(color: Colors.white),
              ),
              if (sheetError != null) ...[
                const SizedBox(height: 8),
                Text(
                  sheetError!,
                  style: const TextStyle(
                    color: Colors.redAccent,
                    fontSize: 12,
                  ),
                ),
              ],
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red.shade700,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  onPressed: loading
                      ? null
                      : () async {
                          final password = passwordController.text.trim();
                          if (password.isEmpty) {
                            setSheetState(
                              () => sheetError = 'Mot de passe requis',
                            );
                            return;
                          }
                          setSheetState(() => loading = true);
                          final r = await _repo.disable(password);
                          if (!ctx.mounted) return;
                          switch (r) {
                            case ApiSuccess():
                              Navigator.of(ctx).pop();
                              _loadStatus();
                            case ApiFailure(:final message):
                              setSheetState(() {
                                loading = false;
                                sheetError = message;
                              });
                          }
                        },
                  child: loading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : Text(
                          'Désactiver',
                          style: GoogleFonts.manrope(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showError(String msg) {
    if (!mounted) return;
    setState(() => _error = msg);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          foregroundColor: Colors.white,
          title: Text(
            'Double authentification',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: Colors.white,
            ),
          ),
        ),
        body: _loadingStatus
            ? const Center(
                child: CircularProgressIndicator(
                  color: BookmiColors.brandBlueLight,
                ),
              )
            : _error != null
            ? Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.error_outline,
                      color: Colors.redAccent,
                      size: 40,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _error!,
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 14,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    TextButton(
                      onPressed: _loadStatus,
                      child: const Text(
                        'Réessayer',
                        style: TextStyle(color: BookmiColors.brandBlueLight),
                      ),
                    ),
                  ],
                ),
              )
            : ListView(
                padding: const EdgeInsets.all(BookmiSpacing.spaceMd),
                children: [
                  // Status card
                  GlassCard(
                    child: Row(
                      children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color:
                                (_enabled
                                        ? BookmiColors.success
                                        : Colors.white30)
                                    .withValues(alpha: 0.15),
                          ),
                          child: Icon(
                            _enabled
                                ? Icons.shield_outlined
                                : Icons.shield_outlined,
                            size: 24,
                            color: _enabled
                                ? BookmiColors.success
                                : Colors.white38,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _enabled
                                    ? 'Double authentification activée'
                                    : 'Double authentification désactivée',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w600,
                                  color: Colors.white,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                _enabled
                                    ? 'Méthode : ${_method == 'totp' ? 'Authentificateur' : 'Email'}'
                                    : 'Protégez votre compte avec une vérification supplémentaire.',
                                style: GoogleFonts.manrope(
                                  fontSize: 12,
                                  color: Colors.white54,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color:
                                (_enabled
                                        ? BookmiColors.success
                                        : Colors.white24)
                                    .withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            _enabled ? 'Actif' : 'Inactif',
                            style: GoogleFonts.manrope(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: _enabled
                                  ? BookmiColors.success
                                  : Colors.white54,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: BookmiSpacing.spaceMd),

                  if (!_enabled) ...[
                    // Enable options
                    Text(
                      'Choisir une méthode',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Colors.white54,
                        letterSpacing: 0.5,
                      ),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceSm),
                    GlassCard(
                      child: Column(
                        children: [
                          _MethodTile(
                            icon: Icons.phonelink_lock_outlined,
                            title: 'Application authentificateur',
                            subtitle:
                                'Google Authenticator, Authy ou compatible TOTP',
                            onTap: _startSetupTotp,
                          ),
                          Divider(
                            height: 1,
                            color: Colors.white.withValues(alpha: 0.08),
                            indent: 56,
                          ),
                          _MethodTile(
                            icon: Icons.email_outlined,
                            title: 'Code par email',
                            subtitle:
                                'Un code sera envoyé à votre adresse email',
                            onTap: _startSetupEmail,
                          ),
                        ],
                      ),
                    ),
                  ] else ...[
                    // Disable option
                    GlassCard(
                      child: _MethodTile(
                        icon: Icons.lock_open_outlined,
                        title: 'Désactiver la 2FA',
                        subtitle:
                            'Votre compte sera moins protégé sans cette option',
                        iconColor: Colors.redAccent,
                        onTap: _disableTwoFactor,
                      ),
                    ),
                  ],

                  const SizedBox(height: BookmiSpacing.spaceMd),

                  // Info card
                  GlassCard(
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(
                          Icons.info_outline,
                          size: 18,
                          color: BookmiColors.brandBlueLight,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'La double authentification ajoute une couche de sécurité supplémentaire. '
                            'Lors de votre prochaine connexion, un code vous sera demandé en plus de votre mot de passe.',
                            style: GoogleFonts.manrope(
                              fontSize: 12,
                              color: Colors.white60,
                              height: 1.5,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}

class _MethodTile extends StatelessWidget {
  const _MethodTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    this.iconColor,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final Color? iconColor;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 14),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: (iconColor ?? BookmiColors.brandBlueLight).withValues(
                  alpha: 0.1,
                ),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                icon,
                size: 20,
                color: iconColor ?? BookmiColors.brandBlueLight,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: GoogleFonts.manrope(
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      color: Colors.white,
                    ),
                  ),
                  Text(
                    subtitle,
                    style: GoogleFonts.manrope(
                      fontSize: 11,
                      color: Colors.white54,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, size: 18, color: Colors.white38),
          ],
        ),
      ),
    );
  }
}
