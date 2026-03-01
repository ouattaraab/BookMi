import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

// ── Design tokens (dark theme) ────────────────────────────────────
const _bg = Color(0xFF112044);
const _cardBg = Color(0xFF0D1B38);
const _inputBg = Color(0xFF0A1628);
const _primary = Color(0xFF2196F3);
const _muted = Color(0xFF94A3B8);
const _border = Color(0x1AFFFFFF);
const _secondary = Color(0xFFE8F0FF);
const _destructive = Color(0xFFEF4444);

class TalentProfileInfoPage extends StatefulWidget {
  const TalentProfileInfoPage({required this.repository, super.key});
  final ProfileRepository repository;

  @override
  State<TalentProfileInfoPage> createState() => _TalentProfileInfoPageState();
}

class _TalentProfileInfoPageState extends State<TalentProfileInfoPage> {
  bool _loading = true;
  bool _saving = false;
  String? _error;

  final _formKey = GlobalKey<FormState>();
  final _bioCtrl = TextEditingController();
  final _instagramCtrl = TextEditingController();
  final _facebookCtrl = TextEditingController();
  final _youtubeCtrl = TextEditingController();
  final _tiktokCtrl = TextEditingController();
  final _twitterCtrl = TextEditingController();

  bool _isGroup = false;
  int _groupSize = 1;
  final _collectiveNameCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  @override
  void dispose() {
    _bioCtrl.dispose();
    _instagramCtrl.dispose();
    _facebookCtrl.dispose();
    _youtubeCtrl.dispose();
    _tiktokCtrl.dispose();
    _twitterCtrl.dispose();
    _collectiveNameCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await widget.repository.getTalentProfile();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        final attrs = data['attributes'] as Map<String, dynamic>? ?? {};
        final social = attrs['social_links'] as Map<String, dynamic>? ?? {};
        _bioCtrl.text = attrs['bio'] as String? ?? '';
        _instagramCtrl.text = social['instagram'] as String? ?? '';
        _facebookCtrl.text = social['facebook'] as String? ?? '';
        _youtubeCtrl.text = social['youtube'] as String? ?? '';
        _tiktokCtrl.text = social['tiktok'] as String? ?? '';
        _twitterCtrl.text = social['twitter'] as String? ?? '';
        _isGroup = (attrs['is_group'] as bool?) ?? false;
        _groupSize = (attrs['group_size'] as int?) ?? 1;
        _collectiveNameCtrl.text = attrs['collective_name'] as String? ?? '';
        setState(() => _loading = false);
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    final socialLinks = <String, String?>{
      'instagram': _instagramCtrl.text.trim().isEmpty
          ? null
          : _instagramCtrl.text.trim(),
      'facebook': _facebookCtrl.text.trim().isEmpty
          ? null
          : _facebookCtrl.text.trim(),
      'youtube': _youtubeCtrl.text.trim().isEmpty
          ? null
          : _youtubeCtrl.text.trim(),
      'tiktok': _tiktokCtrl.text.trim().isEmpty
          ? null
          : _tiktokCtrl.text.trim(),
      'twitter': _twitterCtrl.text.trim().isEmpty
          ? null
          : _twitterCtrl.text.trim(),
    };

    final result = await widget.repository.updateTalentProfileInfo(
      bio: _bioCtrl.text.trim().isEmpty ? null : _bioCtrl.text.trim(),
      socialLinks: socialLinks,
      isGroup: _isGroup,
      groupSize: _isGroup ? _groupSize : null,
      collectiveName: _isGroup && _collectiveNameCtrl.text.trim().isNotEmpty
          ? _collectiveNameCtrl.text.trim()
          : null,
    );
    if (!mounted) return;
    setState(() => _saving = false);
    switch (result) {
      case ApiSuccess():
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Profil mis à jour avec succès')),
        );
        Navigator.of(context).pop();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: _destructive),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        backgroundColor: _cardBg,
        elevation: 0,
        title: Text(
          'Description & Réseaux sociaux',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white70),
          onPressed: () => Navigator.of(context).pop(),
        ),
        actions: [
          if (!_loading && _error == null)
            TextButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        color: _primary,
                        strokeWidth: 2,
                      ),
                    )
                  : Text(
                      'Enregistrer',
                      style: GoogleFonts.manrope(
                        color: _primary,
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
            ),
        ],
      ),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(color: _primary),
            )
          : _error != null
          ? _buildError()
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // ── Description ──────────────────────────────────
                  _SectionHeader(
                    icon: Icons.description_outlined,
                    title: 'Description',
                  ),
                  const SizedBox(height: 10),
                  _FormField(
                    controller: _bioCtrl,
                    label: 'Bio / Description',
                    hint:
                        'Décrivez votre style musical, votre expérience, ce qui vous rend unique...',
                    maxLines: 5,
                    maxLength: 1000,
                    validator: (v) {
                      if (v != null && v.length > 1000) {
                        return 'Maximum 1000 caractères';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 24),

                  // ── Réseaux sociaux ──────────────────────────────
                  _SectionHeader(
                    icon: Icons.share_outlined,
                    title: 'Réseaux sociaux',
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Laissez vide les champs non renseignés',
                    style: GoogleFonts.manrope(
                      fontSize: 12,
                      color: _muted,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _SocialField(
                    controller: _instagramCtrl,
                    label: 'Instagram',
                    hint: 'https://instagram.com/votre_compte',
                    icon: Icons.camera_alt_outlined,
                    color: const Color(0xFFE1306C),
                  ),
                  const SizedBox(height: 10),
                  _SocialField(
                    controller: _facebookCtrl,
                    label: 'Facebook',
                    hint: 'https://facebook.com/votre_page',
                    icon: Icons.facebook_outlined,
                    color: const Color(0xFF1877F2),
                  ),
                  const SizedBox(height: 10),
                  _SocialField(
                    controller: _youtubeCtrl,
                    label: 'YouTube',
                    hint: 'https://youtube.com/@votre_chaine',
                    icon: Icons.play_circle_outline,
                    color: const Color(0xFFFF0000),
                  ),
                  const SizedBox(height: 10),
                  _SocialField(
                    controller: _tiktokCtrl,
                    label: 'TikTok',
                    hint: 'https://tiktok.com/@votre_compte',
                    icon: Icons.music_video_outlined,
                    color: Colors.white,
                  ),
                  const SizedBox(height: 10),
                  _SocialField(
                    controller: _twitterCtrl,
                    label: 'X (Twitter)',
                    hint: 'https://x.com/votre_compte',
                    icon: Icons.alternate_email,
                    color: const Color(0xFF1DA1F2),
                  ),
                  const SizedBox(height: 24),

                  // ── Groupe / Collectif ───────────────────────────
                  _SectionHeader(
                    icon: Icons.group_outlined,
                    title: 'Groupe / Collectif',
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Activez si vous représentez un groupe ou collectif',
                    style: GoogleFonts.manrope(fontSize: 12, color: _muted),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 10,
                    ),
                    decoration: BoxDecoration(
                      color: _inputBg,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: _border),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Artiste / groupe',
                          style: GoogleFonts.manrope(
                            fontSize: 14,
                            color: _secondary,
                          ),
                        ),
                        Switch(
                          value: _isGroup,
                          onChanged: (v) => setState(() => _isGroup = v),
                          activeColor: _primary,
                        ),
                      ],
                    ),
                  ),
                  if (_isGroup) ...[
                    const SizedBox(height: 10),
                    _FormField(
                      controller: _collectiveNameCtrl,
                      label: 'Nom du collectif',
                      hint: 'Ex: Les Étoiles, Jazz Quartet...',
                      validator: (v) {
                        if (_isGroup && (v == null || v.trim().isEmpty)) {
                          return 'Nom du collectif requis';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 10),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Nombre de membres',
                          style: GoogleFonts.manrope(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: _muted,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            IconButton(
                              onPressed: () => setState(() {
                                if (_groupSize > 1) _groupSize--;
                              }),
                              icon: const Icon(
                                Icons.remove_circle_outline,
                                color: _primary,
                              ),
                            ),
                            Expanded(
                              child: Container(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 10),
                                decoration: BoxDecoration(
                                  color: _inputBg,
                                  borderRadius: BorderRadius.circular(10),
                                  border: Border.all(color: _border),
                                ),
                                child: Text(
                                  '$_groupSize',
                                  textAlign: TextAlign.center,
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w700,
                                    color: _secondary,
                                  ),
                                ),
                              ),
                            ),
                            IconButton(
                              onPressed: () => setState(() {
                                if (_groupSize < 100) _groupSize++;
                              }),
                              icon: const Icon(
                                Icons.add_circle_outline,
                                color: _primary,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ],
                  const SizedBox(height: 24),

                  // ── Save button ──────────────────────────────────
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _saving ? null : _save,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: _saving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                color: Colors.white,
                                strokeWidth: 2,
                              ),
                            )
                          : Text(
                              'Enregistrer les modifications',
                              style: GoogleFonts.manrope(
                                fontWeight: FontWeight.w600,
                                fontSize: 15,
                              ),
                            ),
                    ),
                  ),
                  const SizedBox(height: 24),
                ],
              ),
            ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, color: Colors.white38, size: 48),
            const SizedBox(height: 12),
            Text(
              _error!,
              style: const TextStyle(color: Colors.white54),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            TextButton(
              onPressed: _loadProfile,
              child: const Text(
                'Réessayer',
                style: TextStyle(color: _primary),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.icon, required this.title});
  final IconData icon;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 16, color: _primary),
        const SizedBox(width: 8),
        Text(
          title,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
        ),
      ],
    );
  }
}

class _FormField extends StatelessWidget {
  const _FormField({
    required this.controller,
    required this.label,
    required this.hint,
    this.maxLines = 1,
    this.maxLength,
    this.validator,
  });

  final TextEditingController controller;
  final String label;
  final String hint;
  final int maxLines;
  final int? maxLength;
  final String? Function(String?)? validator;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.manrope(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: _muted,
          ),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          maxLines: maxLines,
          maxLength: maxLength,
          validator: validator,
          style: GoogleFonts.manrope(fontSize: 14, color: _secondary),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.manrope(fontSize: 13, color: _muted),
            filled: true,
            fillColor: _inputBg,
            counterStyle: GoogleFonts.manrope(fontSize: 11, color: _muted),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _primary),
            ),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 12,
            ),
          ),
        ),
      ],
    );
  }
}

class _SocialField extends StatelessWidget {
  const _SocialField({
    required this.controller,
    required this.label,
    required this.hint,
    required this.icon,
    required this.color,
  });

  final TextEditingController controller;
  final String label;
  final String hint;
  final IconData icon;
  final Color color;

  String? _validateUrl(String? v) {
    if (v == null || v.trim().isEmpty) return null;
    final uri = Uri.tryParse(v.trim());
    if (uri == null || !uri.hasScheme || !uri.hasAuthority) {
      return 'URL invalide (ex: https://...)';
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 14, color: color),
            const SizedBox(width: 6),
            Text(
              label,
              style: GoogleFonts.manrope(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: _muted,
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          keyboardType: TextInputType.url,
          validator: _validateUrl,
          style: GoogleFonts.manrope(fontSize: 13, color: _secondary),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.manrope(fontSize: 12, color: _muted),
            filled: true,
            fillColor: _inputBg,
            prefixIcon: Icon(
              icon,
              size: 16,
              color: color.withValues(alpha: 0.6),
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _primary),
            ),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 10,
            ),
          ),
        ),
      ],
    );
  }
}
