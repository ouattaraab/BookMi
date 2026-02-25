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

const _secondary = Color(0xFF00274D);
const _primary = Color(0xFF3B9DF2);
const _orange = Color(0xFFFF6B35);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _border = Color(0xFFE2E8F0);
const _success = Color(0xFF14B8A6);

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
    if (picked != null) {
      setState(() {
        _pendingAvatarFile = File(picked.path);
      });
    }
  }

  Future<void> _deleteAvatar() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Supprimer la photo',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: _secondary,
          ),
        ),
        content: Text(
          'Voulez-vous supprimer votre photo de profil ?',
          style: GoogleFonts.manrope(color: _mutedFg),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(
              'Annuler',
              style: GoogleFonts.manrope(color: _mutedFg),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(
              'Supprimer',
              style: GoogleFonts.manrope(
                color: Colors.red,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
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
        // Refresh user profile in AuthBloc
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
      _showSnack('Le prénom et le nom ne peuvent pas être vides.', isError: true);
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
        // Build updated user from response
        final authState = context.read<AuthBloc>().state;
        if (authState is AuthAuthenticated) {
          final updatedUser = authState.user.copyWith(
            firstName: (data['user'] as Map<String, dynamic>?)?['first_name'] as String? ?? firstName,
            lastName: (data['user'] as Map<String, dynamic>?)?['last_name'] as String? ?? lastName,
            avatarUrl: (data['user'] as Map<String, dynamic>?)?['avatar_url'] as String?,
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
        content: Text(message, style: GoogleFonts.manrope()),
        backgroundColor: isError ? Colors.red.shade700 : _success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        final user = state is AuthAuthenticated ? state.user : null;

        return Scaffold(
          backgroundColor: _muted,
          appBar: AppBar(
            backgroundColor: _secondary,
            foregroundColor: Colors.white,
            elevation: 0,
            title: Text(
              'Informations personnelles',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w700,
                color: Colors.white,
                fontSize: 16,
              ),
            ),
            actions: [
              if (!_isEditing)
                IconButton(
                  icon: const Icon(Icons.edit_outlined),
                  tooltip: 'Modifier',
                  onPressed: () => setState(() => _isEditing = true),
                )
              else ...[
                TextButton(
                  onPressed: _cancelEdit,
                  child: Text(
                    'Annuler',
                    style: GoogleFonts.manrope(
                      color: Colors.white.withValues(alpha: 0.8),
                      fontSize: 13,
                    ),
                  ),
                ),
              ],
            ],
          ),
          body: user == null
              ? const Center(child: CircularProgressIndicator())
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    // ── Avatar section ──────────────────────────────
                    _AvatarSection(
                      user: user,
                      pendingAvatarFile: _pendingAvatarFile,
                      isEditing: _isEditing,
                      deletingAvatar: _deletingAvatar,
                      onPickAvatar: _pickAvatar,
                      onDeleteAvatar: _deleteAvatar,
                    ),
                    const SizedBox(height: 20),

                    // ── Name fields ─────────────────────────────────
                    Container(
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
                          _isEditing
                              ? _EditRow(
                                  label: 'Prénom',
                                  controller: _firstNameCtrl,
                                )
                              : _InfoRow(
                                  label: 'Prénom',
                                  value: user.firstName,
                                ),
                          const Divider(
                            color: _border,
                            height: 1,
                            indent: 16,
                            endIndent: 16,
                          ),
                          _isEditing
                              ? _EditRow(
                                  label: 'Nom',
                                  controller: _lastNameCtrl,
                                )
                              : _InfoRow(
                                  label: 'Nom',
                                  value: user.lastName,
                                ),
                          const Divider(
                            color: _border,
                            height: 1,
                            indent: 16,
                            endIndent: 16,
                          ),
                          _InfoRow(
                            label: 'Email',
                            value: user.email,
                            isReadOnly: true,
                          ),
                          const Divider(
                            color: _border,
                            height: 1,
                            indent: 16,
                            endIndent: 16,
                          ),
                          _InfoRow(
                            label: 'Téléphone',
                            value: user.phone,
                            isReadOnly: true,
                            trailing: user.phoneVerifiedAt != null
                                ? _VerifiedChip()
                                : _UnverifiedChip(),
                          ),
                          const Divider(
                            color: _border,
                            height: 1,
                            indent: 16,
                            endIndent: 16,
                          ),
                          _InfoRow(
                            label: 'Compte actif',
                            value: user.isActive ? 'Oui' : 'Non',
                            isLast: true,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // ── Save button (edit mode only) ────────────────
                    if (_isEditing)
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _isSaving ? null : _save,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: _orange,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 0,
                          ),
                          child: _isSaving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  'Enregistrer les modifications',
                                  style: GoogleFonts.manrope(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 15,
                                  ),
                                ),
                        ),
                      ),
                  ],
                ),
        );
      },
    );
  }
}

// ── Avatar section widget ──────────────────────────────────────────
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
    return Container(
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
          // Avatar circle
          Stack(
            alignment: Alignment.bottomRight,
            children: [
              Container(
                width: 90,
                height: 90,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: _orange.withValues(alpha: 0.4),
                    width: 2.5,
                  ),
                ),
                child: ClipOval(
                  child: _buildAvatarContent(),
                ),
              ),
              if (isEditing)
                GestureDetector(
                  onTap: onPickAvatar,
                  child: Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      color: _orange,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                    ),
                    child: const Icon(
                      Icons.camera_alt,
                      size: 14,
                      color: Colors.white,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            '${user.firstName} ${user.lastName}',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 17,
              fontWeight: FontWeight.w700,
              color: _secondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            user.email,
            style: GoogleFonts.manrope(fontSize: 13, color: _mutedFg),
          ),
          // Buttons (edit mode)
          if (isEditing) ...[
            const SizedBox(height: 14),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                OutlinedButton.icon(
                  onPressed: onPickAvatar,
                  icon: const Icon(Icons.photo_library_outlined, size: 15),
                  label: Text(
                    'Modifier la photo',
                    style: GoogleFonts.manrope(fontSize: 12),
                  ),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: _primary,
                    side: const BorderSide(color: _primary),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 8,
                    ),
                  ),
                ),
                if (user.avatarUrl != null || pendingAvatarFile != null) ...[
                  const SizedBox(width: 8),
                  OutlinedButton.icon(
                    onPressed: deletingAvatar ? null : onDeleteAvatar,
                    icon: deletingAvatar
                        ? const SizedBox(
                            width: 12,
                            height: 12,
                            child: CircularProgressIndicator(strokeWidth: 1.5),
                          )
                        : const Icon(Icons.delete_outline, size: 15),
                    label: Text(
                      'Supprimer',
                      style: GoogleFonts.manrope(fontSize: 12),
                    ),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red,
                      side: const BorderSide(color: Colors.red),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 8,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildAvatarContent() {
    // 1. Pending local file (selected but not yet saved)
    if (pendingAvatarFile != null) {
      return Image.file(
        pendingAvatarFile!,
        width: 90,
        height: 90,
        fit: BoxFit.cover,
      );
    }
    // 2. Remote avatar URL
    if (user.avatarUrl != null) {
      return CachedNetworkImage(
        imageUrl: user.avatarUrl!,
        width: 90,
        height: 90,
        fit: BoxFit.cover,
        errorWidget: (_, __, ___) => _buildInitials(),
      );
    }
    // 3. Fallback: initials
    return _buildInitials();
  }

  Widget _buildInitials() {
    return Container(
      color: _primary,
      child: Center(
        child: Text(
          _initials,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 30,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

// ── Editable row ──────────────────────────────────────────────────
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
            width: 90,
            child: Text(
              label,
              style: GoogleFonts.manrope(fontSize: 13, color: _mutedFg),
            ),
          ),
          Expanded(
            child: TextField(
              controller: controller,
              style: GoogleFonts.manrope(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: _secondary,
              ),
              decoration: InputDecoration(
                isDense: true,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 8,
                ),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: const BorderSide(color: _border),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: const BorderSide(color: _orange),
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
  });

  final String label;
  final String value;
  final Widget? trailing;
  final bool isLast;
  final bool isReadOnly;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 14,
          ),
          child: Row(
            children: [
              Expanded(
                flex: 2,
                child: Text(
                  label,
                  style: GoogleFonts.manrope(
                    fontSize: 13,
                    color: isReadOnly ? _mutedFg.withValues(alpha: 0.7) : _mutedFg,
                  ),
                ),
              ),
              Expanded(
                flex: 3,
                child: Text(
                  value.isNotEmpty ? value : '—',
                  style: GoogleFonts.manrope(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: isReadOnly ? _secondary.withValues(alpha: 0.6) : _secondary,
                  ),
                  textAlign: TextAlign.end,
                ),
              ),
              if (trailing != null) ...[
                const SizedBox(width: 8),
                trailing!,
              ],
            ],
          ),
        ),
        if (!isLast)
          const Divider(
            color: _border,
            height: 1,
            indent: 16,
            endIndent: 16,
          ),
      ],
    );
  }
}

class _VerifiedChip extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: _success.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _success.withValues(alpha: 0.3)),
      ),
      child: Text(
        'Vérifié',
        style: GoogleFonts.manrope(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: _success,
        ),
      ),
    );
  }
}

class _UnverifiedChip extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: Colors.orange.shade50,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.orange.shade200),
      ),
      child: Text(
        'Non vérifié',
        style: GoogleFonts.manrope(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: Colors.orange.shade700,
        ),
      ),
    );
  }
}
