import 'dart:io';

import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';

// ── Dark design tokens ────────────────────────────────────────────
const _bg = Color(0xFF112044);
const _cardBg = Color(0xFF0D1B38);
const _primary = Color(0xFF2196F3);
const _accent = Color(0xFF64B5F6);
const _muted = Color(0xFF94A3B8);
const _border = Color(0x1AFFFFFF); // white 10%
const _divider = Color(0x0DFFFFFF); // white 5%
const _success = Color(0xFF00C853);
const _errorRed = Color(0xFFFF1744);

class PersonalInfoPage extends StatefulWidget {
  const PersonalInfoPage({super.key});

  @override
  State<PersonalInfoPage> createState() => _PersonalInfoPageState();
}

class _PersonalInfoPageState extends State<PersonalInfoPage> {
  bool _isEditing = false;
  bool _isSaving = false;

  late TextEditingController _firstNameCtrl;
  late TextEditingController _lastNameCtrl;

  File? _pendingAvatarFile;
  bool _deletingAvatar = false;

  @override
  void initState() {
    super.initState();
    final authState = context.read<AuthBloc>().state;
    final user = authState is AuthAuthenticated ? authState.user : null;
    _firstNameCtrl = TextEditingController(text: user?.firstName ?? '');
    _lastNameCtrl = TextEditingController(text: user?.lastName ?? '');
  }

  @override
  void dispose() {
    _firstNameCtrl.dispose();
    _lastNameCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickAvatar() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 80,
      maxWidth: 800,
      maxHeight: 800,
    );
    if (picked != null) setState(() => _pendingAvatarFile = File(picked.path));
  }

  Future<void> _deleteAvatar() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => Theme(
        data: ThemeData.dark().copyWith(
          colorScheme: const ColorScheme.dark(
            surface: _cardBg,
            primary: _primary,
          ),
          dialogBackgroundColor: _cardBg,
        ),
        child: AlertDialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),
          title: Text(
            'Supprimer la photo',
            style: GoogleFonts.nunito(
              fontWeight: FontWeight.w800,
              color: Colors.white,
              fontSize: 17,
            ),
          ),
          content: Text(
            'Voulez-vous supprimer votre photo de profil ?',
            style: GoogleFonts.manrope(color: _muted, fontSize: 14),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: Text(
                'Annuler',
                style: GoogleFonts.manrope(color: _muted),
              ),
            ),
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(true),
              child: Text(
                'Supprimer',
                style: GoogleFonts.manrope(
                  color: _errorRed,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      ),
    );
    if (confirmed != true) return;

    setState(() => _deletingAvatar = true);
    final repo = context.read<ProfileRepository>();
    final result = await repo.deleteAvatar();
    if (!mounted) return;
    setState(() => _deletingAvatar = false);

    switch (result) {
      case ApiSuccess():
        final authState = context.read<AuthBloc>().state;
        if (authState is AuthAuthenticated) {
          final updatedUser = authState.user.copyWith(avatarUrl: null);
          context.read<AuthBloc>().add(AuthProfileUpdated(updatedUser));
        }
        _showSnack('Photo supprimée.');
      case ApiFailure(:final message):
        _showSnack(message, isError: true);
    }
  }

  Future<void> _save() async {
    if (_isSaving) return;

    final firstName = _firstNameCtrl.text.trim();
    final lastName = _lastNameCtrl.text.trim();

    if (firstName.isEmpty || lastName.isEmpty) {
      _showSnack(
        'Le prénom et le nom ne peuvent pas être vides.',
        isError: true,
      );
      return;
    }

    setState(() => _isSaving = true);

    final repo = context.read<ProfileRepository>();
    final result = await repo.updateProfile(
      firstName: firstName,
      lastName: lastName,
      avatarFile: _pendingAvatarFile,
    );

    if (!mounted) return;
    setState(() {
      _isSaving = false;
      _isEditing = false;
    });

    switch (result) {
      case ApiSuccess(:final data):
        final authState = context.read<AuthBloc>().state;
        if (authState is AuthAuthenticated) {
          final updatedUser = authState.user.copyWith(
            firstName:
                (data['user'] as Map<String, dynamic>?)?['first_name']
                    as String? ??
                firstName,
            lastName:
                (data['user'] as Map<String, dynamic>?)?['last_name']
                    as String? ??
                lastName,
            avatarUrl:
                (data['user'] as Map<String, dynamic>?)?['avatar_url']
                    as String?,
          );
          context.read<AuthBloc>().add(AuthProfileUpdated(updatedUser));
        }
        setState(() => _pendingAvatarFile = null);
        _showSnack('Profil mis à jour avec succès.');
      case ApiFailure(:final message):
        _showSnack(message, isError: true);
    }
  }

  void _cancelEdit() {
    final authState = context.read<AuthBloc>().state;
    final user = authState is AuthAuthenticated ? authState.user : null;
    setState(() {
      _isEditing = false;
      _pendingAvatarFile = null;
      _firstNameCtrl.text = user?.firstName ?? '';
      _lastNameCtrl.text = user?.lastName ?? '';
    });
  }

  void _showSnack(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message, style: GoogleFonts.manrope(color: Colors.white)),
        backgroundColor: isError ? _errorRed : _success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  // ── AppBar ──────────────────────────────────────────────────────
  PreferredSizeWidget _buildAppBar() {
    return PreferredSize(
      preferredSize: const Size.fromHeight(56),
      child: Container(
        color: _cardBg,
        child: SafeArea(
          bottom: false,
          child: Column(
            children: [
              SizedBox(
                height: 55,
                child: Row(
                  children: [
                    // Back button
                    GestureDetector(
                      onTap: () => Navigator.of(context).pop(),
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(8, 0, 4, 0),
                        child: Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.07),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: _border),
                          ),
                          child: const Icon(
                            Icons.arrow_back_ios_new_rounded,
                            color: Colors.white,
                            size: 15,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 4),
                    // Title
                    Expanded(
                      child: Text(
                        'Informations personnelles',
                        style: GoogleFonts.nunito(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                          letterSpacing: -0.3,
                        ),
                      ),
                    ),
                    // Action
                    Padding(
                      padding: const EdgeInsets.only(right: 16),
                      child: _isEditing
                          ? GestureDetector(
                              onTap: _cancelEdit,
                              child: Text(
                                'Annuler',
                                style: GoogleFonts.manrope(
                                  fontSize: 13,
                                  color: Colors.white.withValues(alpha: 0.55),
                                ),
                              ),
                            )
                          : GestureDetector(
                              onTap: () => setState(() => _isEditing = true),
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 7,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.07),
                                  borderRadius: BorderRadius.circular(9),
                                  border: Border.all(color: _border),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(
                                      Icons.edit_outlined,
                                      size: 13,
                                      color: Colors.white,
                                    ),
                                    const SizedBox(width: 5),
                                    Text(
                                      'Modifier',
                                      style: GoogleFonts.manrope(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600,
                                        color: Colors.white,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                    ),
                  ],
                ),
              ),
              // Bottom divider
              Container(height: 0.5, color: _divider),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        final user = state is AuthAuthenticated ? state.user : null;

        return Scaffold(
          backgroundColor: _bg,
          appBar: _buildAppBar(),
          body: user == null
              ? const Center(
                  child: CircularProgressIndicator(color: _primary),
                )
              : Stack(
                  children: [
                    // Glow haut
                    Positioned(
                      top: -40,
                      left: -20,
                      right: -20,
                      height: 280,
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          gradient: RadialGradient(
                            center: const Alignment(0, -0.2),
                            radius: 1.0,
                            colors: [
                              _primary.withValues(alpha: 0.28),
                              const Color(0xFF38BDF8).withValues(alpha: 0.09),
                              Colors.transparent,
                            ],
                            stops: const [0.0, 0.45, 1.0],
                          ),
                        ),
                      ),
                    ),
                    // Glow bas-gauche teal
                    Positioned(
                      bottom: 0,
                      left: -30,
                      width: 220,
                      height: 220,
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          gradient: RadialGradient(
                            center: const Alignment(-0.4, 0.6),
                            radius: 0.9,
                            colors: [
                              const Color(0xFF00BCD4).withValues(alpha: 0.16),
                              Colors.transparent,
                            ],
                          ),
                        ),
                      ),
                    ),
                    // Glow bas-droite bleu
                    Positioned(
                      bottom: 0,
                      right: -30,
                      width: 220,
                      height: 220,
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          gradient: RadialGradient(
                            center: const Alignment(0.4, 0.6),
                            radius: 0.9,
                            colors: [
                              _primary.withValues(alpha: 0.20),
                              Colors.transparent,
                            ],
                          ),
                        ),
                      ),
                    ),
                    // Scrollable content
                    ListView(
                      padding: const EdgeInsets.fromLTRB(16, 20, 16, 40),
                      children: [
                        // ── Avatar ─────────────────────────────────
                        _AvatarSection(
                          user: user,
                          pendingAvatarFile: _pendingAvatarFile,
                          isEditing: _isEditing,
                          deletingAvatar: _deletingAvatar,
                          onPickAvatar: _pickAvatar,
                          onDeleteAvatar: _deleteAvatar,
                        ),
                        const SizedBox(height: 16),

                        // ── Info fields ────────────────────────────
                        _GlassCard(
                          child: Column(
                            children: [
                              _isEditing
                                  ? _EditRow(
                                      label: 'Prénom',
                                      controller: _firstNameCtrl,
                                    )
                                  : _InfoRow(
                                      label: 'Prénom',
                                      value: user.firstName,
                                    ),
                              _Divider(),
                              _isEditing
                                  ? _EditRow(
                                      label: 'Nom',
                                      controller: _lastNameCtrl,
                                    )
                                  : _InfoRow(
                                      label: 'Nom',
                                      value: user.lastName,
                                    ),
                              _Divider(),
                              _InfoRow(
                                label: 'Email',
                                value: user.email,
                                isReadOnly: true,
                                icon: Icons.mail_outline_rounded,
                              ),
                              _Divider(),
                              _InfoRow(
                                label: 'Téléphone',
                                value: user.phone,
                                isReadOnly: true,
                                icon: Icons.phone_outlined,
                                trailing: user.phoneVerifiedAt != null
                                    ? _StatusChip(
                                        label: 'Vérifié',
                                        color: _success,
                                      )
                                    : _StatusChip(
                                        label: 'Non vérifié',
                                        color: const Color(0xFFFFB300),
                                      ),
                              ),
                              _Divider(),
                              _InfoRow(
                                label: 'Compte actif',
                                value: user.isActive ? 'Oui' : 'Non',
                                icon: Icons.verified_user_outlined,
                                isLast: true,
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 24),

                        // ── Save button (edit mode) ─────────────────
                        if (_isEditing)
                          GestureDetector(
                            onTap: _isSaving ? null : _save,
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 150),
                              height: 52,
                              decoration: BoxDecoration(
                                gradient: _isSaving
                                    ? null
                                    : const LinearGradient(
                                        colors: [
                                          Color(0xFF2196F3),
                                          Color(0xFF64B5F6),
                                        ],
                                      ),
                                color: _isSaving
                                    ? Colors.white.withValues(alpha: 0.08)
                                    : null,
                                borderRadius: BorderRadius.circular(14),
                                boxShadow: _isSaving
                                    ? null
                                    : [
                                        BoxShadow(
                                          color: _primary.withValues(
                                            alpha: 0.4,
                                          ),
                                          blurRadius: 18,
                                          offset: const Offset(0, 6),
                                        ),
                                      ],
                              ),
                              child: Center(
                                child: _isSaving
                                    ? const SizedBox(
                                        width: 22,
                                        height: 22,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          color: Colors.white,
                                        ),
                                      )
                                    : Text(
                                        'Enregistrer les modifications',
                                        style: GoogleFonts.nunito(
                                          fontSize: 15,
                                          fontWeight: FontWeight.w800,
                                          color: Colors.white,
                                          letterSpacing: -0.2,
                                        ),
                                      ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
        );
      },
    );
  }
}

// ── Glass card wrapper ────────────────────────────────────────────
class _GlassCard extends StatelessWidget {
  const _GlassCard({required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: _border),
      ),
      child: child,
    );
  }
}

class _Divider extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      height: 0.5,
      margin: const EdgeInsets.symmetric(horizontal: 16),
      color: _divider,
    );
  }
}

// ── Avatar section ────────────────────────────────────────────────
class _AvatarSection extends StatelessWidget {
  const _AvatarSection({
    required this.user,
    required this.pendingAvatarFile,
    required this.isEditing,
    required this.deletingAvatar,
    required this.onPickAvatar,
    required this.onDeleteAvatar,
  });

  final AuthUser user;
  final File? pendingAvatarFile;
  final bool isEditing;
  final bool deletingAvatar;
  final VoidCallback onPickAvatar;
  final VoidCallback onDeleteAvatar;

  String get _initials =>
      '${user.firstName.isNotEmpty ? user.firstName[0] : ''}'
              '${user.lastName.isNotEmpty ? user.lastName[0] : ''}'
          .toUpperCase();

  @override
  Widget build(BuildContext context) {
    return _GlassCard(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 20),
        child: Column(
          children: [
            // Avatar circle with glow
            Stack(
              alignment: Alignment.bottomRight,
              children: [
                AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  width: 96,
                  height: 96,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: isEditing
                          ? _accent
                          : _accent.withValues(alpha: 0.35),
                      width: 2,
                    ),
                    boxShadow: isEditing
                        ? [
                            BoxShadow(
                              color: _primary.withValues(alpha: 0.4),
                              blurRadius: 20,
                              spreadRadius: 2,
                            ),
                          ]
                        : [
                            BoxShadow(
                              color: _primary.withValues(alpha: 0.15),
                              blurRadius: 10,
                            ),
                          ],
                  ),
                  child: ClipOval(child: _buildAvatarContent()),
                ),
                if (isEditing)
                  GestureDetector(
                    onTap: onPickAvatar,
                    child: Container(
                      width: 30,
                      height: 30,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF2196F3), Color(0xFF64B5F6)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: const Color(0xFF0D1421),
                          width: 2,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: _primary.withValues(alpha: 0.5),
                            blurRadius: 8,
                          ),
                        ],
                      ),
                      child: const Icon(
                        Icons.camera_alt_rounded,
                        size: 14,
                        color: Colors.white,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 14),
            // Name
            Text(
              '${user.firstName} ${user.lastName}',
              style: GoogleFonts.nunito(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: Colors.white,
                letterSpacing: -0.3,
              ),
            ),
            const SizedBox(height: 3),
            // Email
            Text(
              user.email,
              style: GoogleFonts.manrope(
                fontSize: 13,
                color: _muted,
              ),
            ),
            // Edit buttons
            if (isEditing) ...[
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  _GhostButton(
                    label: 'Modifier la photo',
                    icon: Icons.photo_library_outlined,
                    onTap: onPickAvatar,
                  ),
                  if (user.avatarUrl != null || pendingAvatarFile != null) ...[
                    const SizedBox(width: 8),
                    _GhostButton(
                      label: deletingAvatar ? '…' : 'Supprimer',
                      icon: Icons.delete_outline_rounded,
                      onTap: deletingAvatar ? () {} : onDeleteAvatar,
                      isDestructive: true,
                    ),
                  ],
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildAvatarContent() {
    if (pendingAvatarFile != null) {
      return Image.file(
        pendingAvatarFile!,
        width: 96,
        height: 96,
        fit: BoxFit.cover,
      );
    }
    if (user.avatarUrl != null) {
      return CachedNetworkImage(
        imageUrl: user.avatarUrl!,
        width: 96,
        height: 96,
        fit: BoxFit.cover,
        errorWidget: (_, __, ___) => _buildInitials(),
      );
    }
    return _buildInitials();
  }

  Widget _buildInitials() {
    return Container(
      color: _primary.withValues(alpha: 0.25),
      child: Center(
        child: Text(
          _initials,
          style: GoogleFonts.nunito(
            fontSize: 32,
            fontWeight: FontWeight.w900,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

// ── Ghost action button ───────────────────────────────────────────
class _GhostButton extends StatelessWidget {
  const _GhostButton({
    required this.label,
    required this.icon,
    required this.onTap,
    this.isDestructive = false,
  });

  final String label;
  final IconData icon;
  final VoidCallback onTap;
  final bool isDestructive;

  @override
  Widget build(BuildContext context) {
    final color = isDestructive ? const Color(0xFFFF4444) : _accent;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 13, color: color),
            const SizedBox(width: 5),
            Text(
              label,
              style: GoogleFonts.manrope(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Editable field row ────────────────────────────────────────────
class _EditRow extends StatelessWidget {
  const _EditRow({required this.label, required this.controller});

  final String label;
  final TextEditingController controller;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          SizedBox(
            width: 86,
            child: Text(
              label,
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: _muted,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: TextField(
              controller: controller,
              style: GoogleFonts.manrope(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Colors.white,
              ),
              decoration: InputDecoration(
                isDense: true,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 10,
                ),
                filled: true,
                fillColor: Colors.white.withValues(alpha: 0.05),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: BorderSide(
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: BorderSide(
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: _primary, width: 1.5),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Read-only info row ────────────────────────────────────────────
class _InfoRow extends StatelessWidget {
  const _InfoRow({
    required this.label,
    required this.value,
    this.trailing,
    this.isLast = false,
    this.isReadOnly = false,
    this.icon,
  });

  final String label;
  final String value;
  final Widget? trailing;
  final bool isLast;
  final bool isReadOnly;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
      child: Row(
        children: [
          if (icon != null) ...[
            Icon(
              icon,
              size: 15,
              color: _muted.withValues(alpha: 0.6),
            ),
            const SizedBox(width: 8),
          ],
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: isReadOnly ? _muted.withValues(alpha: 0.6) : _muted,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(
              value.isNotEmpty ? value : '—',
              style: GoogleFonts.manrope(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: isReadOnly
                    ? Colors.white.withValues(alpha: 0.5)
                    : Colors.white,
              ),
              textAlign: TextAlign.end,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          if (trailing != null) ...[
            const SizedBox(width: 8),
            trailing!,
          ],
        ],
      ),
    );
  }
}

// ── Status chip ───────────────────────────────────────────────────
class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Text(
        label,
        style: GoogleFonts.manrope(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}
