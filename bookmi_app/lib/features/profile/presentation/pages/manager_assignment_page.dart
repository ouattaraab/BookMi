import 'dart:async';

import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/manager_assignment_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

class ManagerAssignmentPage extends StatefulWidget {
  const ManagerAssignmentPage({super.key});

  @override
  State<ManagerAssignmentPage> createState() => _ManagerAssignmentPageState();
}

class _ManagerAssignmentPageState extends State<ManagerAssignmentPage> {
  late final ManagerAssignmentRepository _repo;
  final _emailController = TextEditingController();
  bool _loading = true;
  bool _submitting = false;
  String? _error;
  List<TalentManagerInvitation> _invitations = [];

  List<TalentManagerInvitation> get _accepted =>
      _invitations.where((i) => i.status == 'accepted').toList();
  List<TalentManagerInvitation> get _pending =>
      _invitations.where((i) => i.status == 'pending').toList();
  List<TalentManagerInvitation> get _rejected =>
      _invitations.where((i) => i.status == 'rejected').toList();

  @override
  void initState() {
    super.initState();
    _repo = ManagerAssignmentRepository(apiClient: ApiClient.instance);
    _load();
  }

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await _repo.getTalentInvitations();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _invitations = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  Future<void> _invite() async {
    final email = _emailController.text.trim();
    if (email.isEmpty) return;
    setState(() => _submitting = true);
    final result = await _repo.inviteManager(email);
    if (!mounted) return;
    setState(() => _submitting = false);
    switch (result) {
      case ApiSuccess():
        _emailController.clear();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Invitation envoyée ! Le manager recevra un email.'),
            backgroundColor: Color(0xFF4CAF50),
          ),
        );
        unawaited(_load());
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: Colors.red),
        );
    }
  }

  Future<void> _cancel(TalentManagerInvitation inv) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Annuler l\'invitation ?',
          style: GoogleFonts.manrope(fontWeight: FontWeight.w700),
        ),
        content: Text(
          '${inv.managerEmail} ne recevra plus d\'invitation de votre part.',
          style: GoogleFonts.manrope(),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Non'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Annuler', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    final result = await _repo.cancelInvitation(inv.id);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess():
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Invitation annulée'),
            backgroundColor: Colors.orange,
          ),
        );
        _load();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: Colors.red),
        );
    }
  }

  Future<void> _remove(TalentManagerInvitation inv) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Retirer ce manager ?',
          style: GoogleFonts.manrope(fontWeight: FontWeight.w700),
        ),
        content: Text(
          '${inv.displayName} ne pourra plus gérer vos réservations.',
          style: GoogleFonts.manrope(),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Retirer', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    final result = await _repo.removeManager(inv.managerEmail);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess():
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Manager retiré'),
            backgroundColor: Colors.orange,
          ),
        );
        _load();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: Colors.red),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.white,
        leading: const BackButton(color: Color(0xFF1A1A2E)),
        title: Text(
          'Mes managers',
          style: GoogleFonts.manrope(
            color: const Color(0xFF1A1A2E),
            fontWeight: FontWeight.w700,
            fontSize: 17,
          ),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(_error!, style: GoogleFonts.manrope(color: Colors.red)),
                      const SizedBox(height: 12),
                      TextButton(onPressed: _load, child: const Text('Réessayer')),
                    ],
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    // Info card
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFF6C5ECF).withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: const Color(0xFF6C5ECF).withValues(alpha: 0.2),
                        ),
                      ),
                      child: Text(
                        'Un manager peut voir vos demandes de réservation, les accepter ou refuser, et répondre aux clients en votre nom.',
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          color: const Color(0xFF6C5ECF),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Invite form
                    _SectionTitle('INVITER UN MANAGER'),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        children: [
                          TextField(
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            decoration: InputDecoration(
                              hintText: 'Email du manager',
                              hintStyle: GoogleFonts.manrope(color: Colors.grey),
                              prefixIcon: const Icon(Icons.email_outlined, size: 20),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.grey.shade300),
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(8),
                                borderSide: BorderSide(color: Colors.grey.shade300),
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 10,
                              ),
                            ),
                          ),
                          const SizedBox(height: 12),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton(
                              onPressed: _submitting ? null : _invite,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF6C5ECF),
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                padding: const EdgeInsets.symmetric(vertical: 12),
                              ),
                              child: _submitting
                                  ? const SizedBox(
                                      height: 18,
                                      width: 18,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : Text(
                                      'Envoyer l\'invitation',
                                      style: GoogleFonts.manrope(
                                        fontWeight: FontWeight.w700,
                                        fontSize: 14,
                                      ),
                                    ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 24),

                    // Accepted managers
                    _SectionTitle('MANAGERS ACTIFS'),
                    const SizedBox(height: 8),
                    if (_accepted.isEmpty)
                      _EmptyCard('Aucun manager actif pour l\'instant.')
                    else
                      ..._accepted.map(
                        (inv) => _InvitationCard(
                          invitation: inv,
                          trailing: _ActionButton(
                            label: 'Retirer',
                            color: Colors.red,
                            onTap: () => _remove(inv),
                          ),
                          statusColor: const Color(0xFF22c55e),
                          statusBg: const Color(0xFFdcfce7),
                          statusLabel: 'Actif',
                          avatarColor: const Color(0xFF22c55e),
                        ),
                      ),

                    // Pending invitations
                    if (_pending.isNotEmpty) ...[
                      const SizedBox(height: 24),
                      _SectionTitle('EN ATTENTE DE RÉPONSE'),
                      const SizedBox(height: 8),
                      ..._pending.map(
                        (inv) => _InvitationCard(
                          invitation: inv,
                          trailing: _ActionButton(
                            label: 'Annuler',
                            color: Colors.grey.shade600,
                            onTap: () => _cancel(inv),
                          ),
                          statusColor: const Color(0xFFd97706),
                          statusBg: const Color(0xFFfef9c3),
                          statusLabel: 'En attente',
                          avatarColor: const Color(0xFFd97706),
                        ),
                      ),
                    ],

                    // Rejected invitations
                    if (_rejected.isNotEmpty) ...[
                      const SizedBox(height: 24),
                      _SectionTitle('INVITATIONS REFUSÉES'),
                      const SizedBox(height: 8),
                      ..._rejected.map(
                        (inv) => _InvitationCard(
                          invitation: inv,
                          trailing: null,
                          statusColor: const Color(0xFFef4444),
                          statusBg: const Color(0xFFfee2e2),
                          statusLabel: 'Refusée',
                          avatarColor: const Color(0xFFef4444),
                          showComment: true,
                          opacity: 0.65,
                        ),
                      ),
                    ],

                    const SizedBox(height: 24),
                  ],
                ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.label);
  final String label;

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: GoogleFonts.manrope(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        color: Colors.grey,
        letterSpacing: 0.8,
      ),
    );
  }
}

class _EmptyCard extends StatelessWidget {
  const _EmptyCard(this.message);
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        message,
        style: GoogleFonts.manrope(color: Colors.grey, fontSize: 13),
        textAlign: TextAlign.center,
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.label,
    required this.color,
    required this.onTap,
  });
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return TextButton(
      onPressed: onTap,
      style: TextButton.styleFrom(
        foregroundColor: color,
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        minimumSize: Size.zero,
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
      ),
      child: Text(label, style: GoogleFonts.manrope(fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}

class _InvitationCard extends StatelessWidget {
  const _InvitationCard({
    required this.invitation,
    required this.trailing,
    required this.statusColor,
    required this.statusBg,
    required this.statusLabel,
    required this.avatarColor,
    this.showComment = false,
    this.opacity = 1.0,
  });

  final TalentManagerInvitation invitation;
  final Widget? trailing;
  final Color statusColor;
  final Color statusBg;
  final String statusLabel;
  final Color avatarColor;
  final bool showComment;
  final double opacity;

  @override
  Widget build(BuildContext context) {
    final initial = invitation.displayName.isNotEmpty
        ? invitation.displayName[0].toUpperCase()
        : '?';
    final dateStr = DateFormat('dd/MM/yyyy').format(invitation.invitedAt);

    return Opacity(
      opacity: opacity,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: statusBg),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              radius: 20,
              backgroundColor: avatarColor.withValues(alpha: 0.15),
              child: Text(
                initial,
                style: TextStyle(
                  color: avatarColor,
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    invitation.displayName,
                    style: GoogleFonts.manrope(
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                      color: const Color(0xFF1A1A2E),
                    ),
                  ),
                  if (invitation.displayName != invitation.managerEmail)
                    Text(
                      invitation.managerEmail,
                      style: GoogleFonts.manrope(fontSize: 11, color: Colors.grey),
                    ),
                  Text(
                    'Invité le $dateStr',
                    style: GoogleFonts.manrope(fontSize: 11, color: Colors.grey),
                  ),
                  const SizedBox(height: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: statusBg,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      statusLabel,
                      style: GoogleFonts.manrope(
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: statusColor,
                      ),
                    ),
                  ),
                  if (showComment && invitation.managerComment != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      '"${invitation.managerComment}"',
                      style: GoogleFonts.manrope(
                        fontSize: 11,
                        color: Colors.grey.shade600,
                        fontStyle: FontStyle.italic,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            if (trailing != null) trailing!,
          ],
        ),
      ),
    );
  }
}
