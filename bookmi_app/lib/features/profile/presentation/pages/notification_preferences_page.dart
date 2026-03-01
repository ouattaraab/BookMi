import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/notification_preferences_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class NotificationPreferencesPage extends StatefulWidget {
  const NotificationPreferencesPage({super.key});

  @override
  State<NotificationPreferencesPage> createState() =>
      _NotificationPreferencesPageState();
}

class _NotificationPreferencesPageState
    extends State<NotificationPreferencesPage> {
  late final NotificationPreferencesRepository _repo;

  bool _loading = true;
  String? _error;
  NotificationPreferences? _prefs;

  @override
  void initState() {
    super.initState();
    _repo = NotificationPreferencesRepository(apiClient: ApiClient.instance);
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await _repo.getPreferences();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _prefs = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  Future<void> _toggle(String key, bool value) async {
    // Optimistic update
    setState(() {
      _prefs = switch (key) {
        'new_message' => _prefs?.copyWith(newMessage: value),
        'booking_updates' => _prefs?.copyWith(bookingUpdates: value),
        'new_review' => _prefs?.copyWith(newReview: value),
        'follow_update' => _prefs?.copyWith(followUpdate: value),
        'admin_broadcast' => _prefs?.copyWith(adminBroadcast: value),
        _ => _prefs,
      };
    });

    final result = await _repo.updatePreferences({key: value});
    if (!mounted) return;
    if (result case ApiFailure(:final message)) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message), backgroundColor: Colors.red),
      );
      // Revert on error
      _load();
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
          'Notifications',
          style: GoogleFonts.manrope(
            color: const Color(0xFF1A1A2E),
            fontWeight: FontWeight.w700,
            fontSize: 17,
          ),
        ),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null || _prefs == null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              _error ?? 'Erreur chargement',
              style: GoogleFonts.manrope(color: Colors.red),
            ),
            const SizedBox(height: 12),
            TextButton(onPressed: _load, child: const Text('Réessayer')),
          ],
        ),
      );
    }

    final prefs = _prefs!;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _SectionHeader(title: 'Réservations'),
        _PrefTile(
          icon: Icons.calendar_today_outlined,
          title: 'Mises à jour de réservation',
          subtitle:
              'Acceptation, refus, paiement, rappels J-7/J-2, litiges, replanifications',
          value: prefs.bookingUpdates,
          onChanged: (v) => _toggle('booking_updates', v),
        ),
        const SizedBox(height: 8),
        _SectionHeader(title: 'Messagerie'),
        _PrefTile(
          icon: Icons.chat_bubble_outline,
          title: 'Nouveaux messages',
          subtitle: 'Notifications quand vous recevez un message',
          value: prefs.newMessage,
          onChanged: (v) => _toggle('new_message', v),
        ),
        const SizedBox(height: 8),
        _SectionHeader(title: 'Avis'),
        _PrefTile(
          icon: Icons.star_outline,
          title: 'Nouvel avis reçu',
          subtitle: 'Quand un client ou talent vous laisse un avis',
          value: prefs.newReview,
          onChanged: (v) => _toggle('new_review', v),
        ),
        const SizedBox(height: 8),
        _SectionHeader(title: 'Social'),
        _PrefTile(
          icon: Icons.people_outline,
          title: 'Mises à jour des talents suivis',
          subtitle:
              'Nouvelles prestations, disponibilités de vos artistes favoris',
          value: prefs.followUpdate,
          onChanged: (v) => _toggle('follow_update', v),
        ),
        const SizedBox(height: 8),
        _SectionHeader(title: 'Plateforme'),
        _PrefTile(
          icon: Icons.campaign_outlined,
          title: 'Annonces BookMi',
          subtitle: 'Messages importants et actualités de la plateforme',
          value: prefs.adminBroadcast,
          onChanged: (v) => _toggle('admin_broadcast', v),
        ),
        const SizedBox(height: 24),
        Text(
          'Certaines notifications critiques (sécurité, authentification) '
          'ne peuvent pas être désactivées.',
          style: GoogleFonts.manrope(
            fontSize: 11,
            color: Colors.grey,
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6, top: 4),
      child: Text(
        title.toUpperCase(),
        style: GoogleFonts.manrope(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: Colors.grey,
          letterSpacing: 0.8,
        ),
      ),
    );
  }
}

class _PrefTile extends StatelessWidget {
  const _PrefTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: SwitchListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        secondary: Icon(icon, color: const Color(0xFF6C5ECF), size: 22),
        title: Text(
          title,
          style: GoogleFonts.manrope(
            fontWeight: FontWeight.w600,
            fontSize: 14,
            color: const Color(0xFF1A1A2E),
          ),
        ),
        subtitle: Text(
          subtitle,
          style: GoogleFonts.manrope(
            fontSize: 12,
            color: Colors.grey,
          ),
        ),
        value: value,
        onChanged: onChanged,
        activeColor: const Color(0xFF6C5ECF),
      ),
    );
  }
}
