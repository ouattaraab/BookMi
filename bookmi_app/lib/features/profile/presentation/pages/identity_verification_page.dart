import 'dart:io';

import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';

const _secondary = Color(0xFFE8F0FF);
const _muted = Color(0xFF112044);
const _mutedFg = Color(0xFF8FA3C0);
const _border = Color(0x1AFFFFFF);
const _success = Color(0xFF14B8A6);
const _primary = Color(0xFF3B9DF2);
const _orange = Color(0xFFFF6B35);

class IdentityVerificationPage extends StatefulWidget {
  const IdentityVerificationPage({super.key});

  @override
  State<IdentityVerificationPage> createState() =>
      _IdentityVerificationPageState();
}

class _IdentityVerificationPageState extends State<IdentityVerificationPage> {
  // Status loading
  bool _statusLoading = true;
  String? _overallStatus; // not_submitted | pending | approved | rejected
  bool _selfieSubmitted = false;
  String? _rejectionReason;

  // Document form
  final _formKey = GlobalKey<FormState>();
  String? _selectedDocType;
  final _docNumberCtrl = TextEditingController();
  File? _documentFile;
  bool _submittingDoc = false;

  // Selfie
  File? _selfieFile;
  bool _submittingSelfie = false;

  @override
  void initState() {
    super.initState();
    _loadStatus();
  }

  @override
  void dispose() {
    _docNumberCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadStatus() async {
    setState(() => _statusLoading = true);
    final repo = context.read<ProfileRepository>();
    final result = await repo.getIdentityStatus();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _overallStatus = data['overall_status'] as String? ?? 'not_submitted';
          _selfieSubmitted = data['selfie_submitted'] as bool? ?? false;
          _rejectionReason = data['rejection_reason'] as String?;
          _statusLoading = false;
        });
      case ApiFailure():
        setState(() {
          _overallStatus = 'not_submitted';
          _statusLoading = false;
        });
    }
  }

  Future<void> _pickDocument() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 90,
    );
    if (picked != null) {
      setState(() => _documentFile = File(picked.path));
    }
  }

  Future<void> _takeSelfie() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
    );
    if (picked != null) {
      setState(() => _selfieFile = File(picked.path));
    }
  }

  Future<void> _submitDocument() async {
    if (!_formKey.currentState!.validate()) return;
    if (_documentFile == null) {
      _showSnack('Veuillez sélectionner votre document.', isError: true);
      return;
    }
    if (_selectedDocType == null) {
      _showSnack('Veuillez sélectionner le type de pièce.', isError: true);
      return;
    }

    setState(() => _submittingDoc = true);
    final repo = context.read<ProfileRepository>();
    final result = await repo.submitIdentityDocument(
      documentType: _selectedDocType!,
      documentNumber: _docNumberCtrl.text.trim(),
      documentFile: _documentFile!,
    );
    if (!mounted) return;
    setState(() => _submittingDoc = false);

    switch (result) {
      case ApiSuccess():
        _showSnack('Document soumis avec succès. En attente de validation.');
        await _loadStatus();
      case ApiFailure(:final message):
        _showSnack(message, isError: true);
    }
  }

  Future<void> _submitSelfie() async {
    if (_selfieFile == null) {
      _showSnack('Veuillez prendre une photo selfie.', isError: true);
      return;
    }

    setState(() => _submittingSelfie = true);
    final repo = context.read<ProfileRepository>();
    final result = await repo.submitSelfie(selfieFile: _selfieFile!);
    if (!mounted) return;
    setState(() => _submittingSelfie = false);

    switch (result) {
      case ApiSuccess():
        _showSnack(
          'Selfie soumis. Votre identité est en cours de vérification.',
        );
        await _loadStatus();
      case ApiFailure(:final message):
        _showSnack(message, isError: true);
    }
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
    return Scaffold(
      backgroundColor: _muted,
      appBar: AppBar(
        backgroundColor: const Color(0xFF0D1B38),
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
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_outlined),
            tooltip: 'Actualiser',
            onPressed: _loadStatus,
          ),
        ],
      ),
      body: _statusLoading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Status card
                _StatusCard(
                  status: _overallStatus ?? 'not_submitted',
                  selfieSubmitted: _selfieSubmitted,
                  rejectionReason: _rejectionReason,
                ),
                const SizedBox(height: 16),

                // Document form (show unless approved)
                if (_overallStatus != 'approved') ...[
                  _DocumentForm(
                    formKey: _formKey,
                    selectedDocType: _selectedDocType,
                    docNumberCtrl: _docNumberCtrl,
                    documentFile: _documentFile,
                    submitting: _submittingDoc,
                    currentStatus: _overallStatus,
                    onDocTypeChanged: (v) =>
                        setState(() => _selectedDocType = v),
                    onPickDocument: _pickDocument,
                    onSubmit: _submitDocument,
                  ),
                  const SizedBox(height: 16),
                ],

                // Selfie section (show after document submitted)
                if (_overallStatus == 'pending' && !_selfieSubmitted) ...[
                  _SelfieSection(
                    selfieFile: _selfieFile,
                    submitting: _submittingSelfie,
                    onTakeSelfie: _takeSelfie,
                    onSubmit: _submitSelfie,
                  ),
                  const SizedBox(height: 16),
                ],

                // Privacy note
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: _primary.withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: _primary.withValues(alpha: 0.2)),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.lock_outline, size: 16, color: _primary),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Vos documents sont chiffrés et uniquement '
                          'utilisés pour la vérification de votre identité.',
                          style: GoogleFonts.manrope(
                            fontSize: 12,
                            color: _primary,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
              ],
            ),
    );
  }
}

// ── Status card ───────────────────────────────────────────────────
class _StatusCard extends StatelessWidget {
  const _StatusCard({
    required this.status,
    required this.selfieSubmitted,
    this.rejectionReason,
  });

  final String status;
  final bool selfieSubmitted;
  final String? rejectionReason;

  @override
  Widget build(BuildContext context) {
    final (icon, color, title, subtitle) = switch (status) {
      'approved' => (
        Icons.verified_user_outlined,
        _success,
        'Identité vérifiée',
        'Votre identité a été validée avec succès.',
      ),
      'pending' => (
        Icons.hourglass_top_outlined,
        _primary,
        selfieSubmitted
            ? 'Vérification en cours'
            : 'Document soumis — selfie requis',
        selfieSubmitted
            ? 'Vos documents sont en cours de révision par notre équipe.'
            : 'Veuillez soumettre votre selfie pour finaliser la vérification.',
      ),
      'rejected' => (
        Icons.cancel_outlined,
        Colors.red,
        'Vérification refusée',
        rejectionReason ?? 'Veuillez soumettre à nouveau vos documents.',
      ),
      _ => (
        Icons.gpp_maybe_outlined,
        Colors.orange,
        'Identité non vérifiée',
        'Soumettez votre pièce d\'identité pour vérifier votre compte.',
      ),
    };

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0x08FFFFFF),
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
              color: color.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 32, color: color),
          ),
          const SizedBox(height: 14),
          Text(
            title,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: _secondary,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            subtitle,
            style: GoogleFonts.manrope(
              fontSize: 13,
              color: _mutedFg,
              height: 1.5,
            ),
            textAlign: TextAlign.center,
          ),
          if (status == 'approved') ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              decoration: BoxDecoration(
                color: _success.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: _success.withValues(alpha: 0.25)),
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
    );
  }
}

// ── Document form ─────────────────────────────────────────────────
class _DocumentForm extends StatelessWidget {
  const _DocumentForm({
    required this.formKey,
    required this.selectedDocType,
    required this.docNumberCtrl,
    required this.documentFile,
    required this.submitting,
    required this.currentStatus,
    required this.onDocTypeChanged,
    required this.onPickDocument,
    required this.onSubmit,
  });

  final GlobalKey<FormState> formKey;
  final String? selectedDocType;
  final TextEditingController docNumberCtrl;
  final File? documentFile;
  final bool submitting;
  final String? currentStatus;
  final ValueChanged<String?> onDocTypeChanged;
  final VoidCallback onPickDocument;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    final isPending = currentStatus == 'pending';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0x08FFFFFF),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Form(
        key: formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: _orange.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(
                    Icons.badge_outlined,
                    size: 18,
                    color: _orange,
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  "Pièce d'identité",
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: _secondary,
                  ),
                ),
                if (isPending) ...[
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: _primary.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      'Soumis',
                      style: GoogleFonts.manrope(
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: _primary,
                      ),
                    ),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 16),
            if (!isPending) ...[
              // Document type dropdown
              DropdownButtonFormField<String>(
                value: selectedDocType,
                decoration: InputDecoration(
                  labelText: 'Type de pièce',
                  labelStyle: GoogleFonts.manrope(
                    fontSize: 13,
                    color: _mutedFg,
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
                    borderSide: const BorderSide(color: _orange),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                ),
                items: const [
                  DropdownMenuItem(
                    value: 'cni',
                    child: Text('Carte Nationale d\'Identité'),
                  ),
                  DropdownMenuItem(
                    value: 'passeport',
                    child: Text('Passeport'),
                  ),
                  DropdownMenuItem(
                    value: 'permis',
                    child: Text('Permis de conduire'),
                  ),
                ],
                onChanged: onDocTypeChanged,
                validator: (v) =>
                    v == null ? 'Veuillez sélectionner un type' : null,
              ),
              const SizedBox(height: 12),
              // Document number
              TextFormField(
                controller: docNumberCtrl,
                decoration: InputDecoration(
                  labelText: 'Numéro du document',
                  labelStyle: GoogleFonts.manrope(
                    fontSize: 13,
                    color: _mutedFg,
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
                    borderSide: const BorderSide(color: _orange),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                ),
                style: GoogleFonts.manrope(fontSize: 14),
                validator: (v) => (v == null || v.trim().isEmpty)
                    ? 'Veuillez saisir le numéro du document'
                    : null,
              ),
              const SizedBox(height: 14),
              // Document file picker
              GestureDetector(
                onTap: onPickDocument,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 14,
                  ),
                  decoration: BoxDecoration(
                    color: documentFile != null
                        ? _success.withValues(alpha: 0.06)
                        : _muted,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: documentFile != null ? _success : _border,
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        documentFile != null
                            ? Icons.check_circle_outline
                            : Icons.upload_file_outlined,
                        size: 20,
                        color: documentFile != null ? _success : _mutedFg,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          documentFile != null
                              ? documentFile!.path.split('/').last
                              : 'Sélectionner le document (JPG, PNG ou PDF)',
                          style: GoogleFonts.manrope(
                            fontSize: 13,
                            color: documentFile != null ? _success : _mutedFg,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        'Parcourir',
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: _primary,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              // Submit button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: submitting ? null : onSubmit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _orange,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    elevation: 0,
                  ),
                  child: submitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: const Color(0xFF0D1B38),
                          ),
                        )
                      : Text(
                          'Soumettre le document',
                          style: GoogleFonts.manrope(
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                          ),
                        ),
                ),
              ),
            ] else ...[
              // Show that document is submitted
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: _primary.withValues(alpha: 0.06),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: _primary.withValues(alpha: 0.2)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.check_circle, size: 18, color: _primary),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Document soumis et en cours de vérification.',
                        style: GoogleFonts.manrope(
                          fontSize: 13,
                          color: _primary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// ── Selfie section ────────────────────────────────────────────────
class _SelfieSection extends StatelessWidget {
  const _SelfieSection({
    required this.selfieFile,
    required this.submitting,
    required this.onTakeSelfie,
    required this.onSubmit,
  });

  final File? selfieFile;
  final bool submitting;
  final VoidCallback onTakeSelfie;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0x08FFFFFF),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: _secondary.withValues(alpha: 0.07),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.face_outlined,
                  size: 18,
                  color: _secondary,
                ),
              ),
              const SizedBox(width: 10),
              Text(
                'Selfie de vérification',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: _secondary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Instructions
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.amber.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.amber.withValues(alpha: 0.3)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Instructions',
                  style: GoogleFonts.manrope(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: Colors.amber.shade800,
                  ),
                ),
                const SizedBox(height: 6),
                ...[
                  'Tenez votre pièce d\'identité juste en dessous de votre visage',
                  'Assurez-vous que votre visage et le document sont bien visibles',
                  'Prenez la photo dans un endroit bien éclairé',
                ].map(
                  (instruction) => Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '• ',
                          style: GoogleFonts.manrope(
                            fontSize: 12,
                            color: Colors.amber.shade800,
                          ),
                        ),
                        Expanded(
                          child: Text(
                            instruction,
                            style: GoogleFonts.manrope(
                              fontSize: 12,
                              color: Colors.amber.shade800,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          // Selfie preview or camera button
          if (selfieFile != null) ...[
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.file(
                selfieFile!,
                height: 180,
                width: double.infinity,
                fit: BoxFit.cover,
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: onTakeSelfie,
                    icon: const Icon(Icons.camera_alt_outlined, size: 16),
                    label: Text(
                      'Reprendre',
                      style: GoogleFonts.manrope(fontSize: 13),
                    ),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: _mutedFg,
                      side: const BorderSide(color: _border),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: submitting ? null : onSubmit,
                    icon: submitting
                        ? const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(
                              strokeWidth: 1.5,
                              color: const Color(0xFF0D1B38),
                            ),
                          )
                        : const Icon(Icons.check, size: 16),
                    label: Text(
                      'Valider',
                      style: GoogleFonts.manrope(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _success,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              ],
            ),
          ] else
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: onTakeSelfie,
                icon: const Icon(Icons.camera_alt_outlined, size: 18),
                label: Text(
                  'Prendre la photo selfie',
                  style: GoogleFonts.manrope(
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0D1B38),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 13),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  elevation: 0,
                ),
              ),
            ),
        ],
      ),
    );
  }
}
