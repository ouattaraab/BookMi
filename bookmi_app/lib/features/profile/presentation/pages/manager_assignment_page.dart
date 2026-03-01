import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/manager_assignment_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

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
  List<ManagerInfo> _managers = [];

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
    final result = await _repo.getManagers();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _managers = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  Future<void> _assign() async {
    final email = _emailController.text.trim();
    if (email.isEmpty) return;
    setState(() => _submitting = true);
    final result = await _repo.assignManager(email);
    if (!mounted) return;
    setState(() => _submitting = false);
    switch (result) {
      case ApiSuccess():
        _emailController.clear();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Manager assigné avec succès'),
            backgroundColor: Color(0xFF4CAF50),
          ),
        );
        _load();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: Colors.red),
        );
    }
  }

  Future<void> _remove(ManagerInfo manager) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Retirer ce manager ?',
          style: GoogleFonts.manrope(fontWeight: FontWeight.w700),
        ),
        content: Text(
          '${manager.name} (${manager.email}) ne pourra plus gérer vos réservations.',
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

    final result = await _repo.removeManager(manager.email);
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
      body: ListView(
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
              'Un manager peut voir vos demandes de réservation, les accepter ou refuser, et répondre aux clients en votre nom. Il ne voit pas vos montants financiers.',
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: const Color(0xFF6C5ECF),
              ),
            ),
          ),
          const SizedBox(height: 20),

          // Current managers
          Text(
            'MANAGERS ACTUELS',
            style: GoogleFonts.manrope(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Colors.grey,
              letterSpacing: 0.8,
            ),
          ),
          const SizedBox(height: 8),
          if (_loading)
            const Center(child: CircularProgressIndicator())
          else if (_error != null)
            Text(_error!, style: GoogleFonts.manrope(color: Colors.red))
          else if (_managers.isEmpty)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                'Aucun manager assigné',
                style: GoogleFonts.manrope(color: Colors.grey, fontSize: 13),
                textAlign: TextAlign.center,
              ),
            )
          else
            ..._managers.map(
              (m) => Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.manage_accounts_outlined,
                      color: Color(0xFF6C5ECF),
                      size: 22,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            m.name,
                            style: GoogleFonts.manrope(
                              fontWeight: FontWeight.w600,
                              fontSize: 14,
                              color: const Color(0xFF1A1A2E),
                            ),
                          ),
                          Text(
                            m.email,
                            style: GoogleFonts.manrope(
                              fontSize: 12,
                              color: Colors.grey,
                            ),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      onPressed: () => _remove(m),
                      icon: const Icon(
                        Icons.remove_circle_outline,
                        color: Colors.red,
                        size: 20,
                      ),
                      tooltip: 'Retirer',
                    ),
                  ],
                ),
              ),
            ),

          const SizedBox(height: 24),

          // Assign new manager
          Text(
            'ASSIGNER UN MANAGER',
            style: GoogleFonts.manrope(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Colors.grey,
              letterSpacing: 0.8,
            ),
          ),
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
                    onPressed: _submitting ? null : _assign,
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
                            'Assigner',
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
        ],
      ),
    );
  }
}
