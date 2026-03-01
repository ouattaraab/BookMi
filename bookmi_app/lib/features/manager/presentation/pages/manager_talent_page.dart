import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:bookmi_app/features/manager/data/repositories/manager_repository.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

class ManagerTalentPage extends StatefulWidget {
  const ManagerTalentPage({
    super.key,
    required this.talent,
    required this.repo,
  });

  final ManagedTalent talent;
  final ManagerRepository repo;

  @override
  State<ManagerTalentPage> createState() => _ManagerTalentPageState();
}

class _ManagerTalentPageState extends State<ManagerTalentPage> {
  bool _loading = true;
  String? _error;
  List<BookingModel> _bookings = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await widget.repo.getTalentBookings(widget.talent.id);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        // Sort: pending first, then by event_date
        final sorted = List<BookingModel>.from(data)
          ..sort((a, b) {
            if (a.status == 'pending' && b.status != 'pending') return -1;
            if (a.status != 'pending' && b.status == 'pending') return 1;
            return a.eventDate.compareTo(b.eventDate);
          });
        setState(() {
          _bookings = sorted;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  Future<void> _accept(BookingModel booking) async {
    final result = await widget.repo.acceptBooking(
      widget.talent.id,
      booking.id,
    );
    if (!mounted) return;
    switch (result) {
      case ApiSuccess():
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Réservation acceptée'),
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

  Future<void> _reject(BookingModel booking) async {
    final controller = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Refuser la réservation',
          style: GoogleFonts.manrope(fontWeight: FontWeight.w700),
        ),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: const InputDecoration(
            hintText: 'Raison du refus…',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, controller.text.trim()),
            child: const Text('Confirmer', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (reason == null || reason.isEmpty || !mounted) return;

    final result = await widget.repo.rejectBooking(
      widget.talent.id,
      booking.id,
      reason,
    );
    if (!mounted) return;
    switch (result) {
      case ApiSuccess():
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Réservation refusée'),
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
          widget.talent.stageName,
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
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!, style: GoogleFonts.manrope(color: Colors.red)),
            const SizedBox(height: 12),
            TextButton(onPressed: _load, child: const Text('Réessayer')),
          ],
        ),
      );
    }

    final pending = _bookings.where((b) => b.status == 'pending').toList();
    final others = _bookings.where((b) => b.status != 'pending').toList();

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (pending.isNotEmpty) ...[
            _SectionHeader(
              title: 'En attente de validation (${pending.length})',
              color: Colors.orange,
            ),
            const SizedBox(height: 8),
            ...pending.map(
              (b) => _BookingCard(
                booking: b,
                onAccept: () => _accept(b),
                onReject: () => _reject(b),
              ),
            ),
            const SizedBox(height: 16),
          ],
          if (others.isNotEmpty) ...[
            const _SectionHeader(title: 'Autres réservations'),
            const SizedBox(height: 8),
            ...others.map((b) => _BookingCard(booking: b)),
          ],
          if (_bookings.isEmpty)
            Center(
              child: Padding(
                padding: const EdgeInsets.only(top: 48),
                child: Text(
                  'Aucune réservation',
                  style: GoogleFonts.manrope(color: Colors.grey),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title, this.color});
  final String title;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Text(
      title.toUpperCase(),
      style: GoogleFonts.manrope(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        color: color ?? Colors.grey,
        letterSpacing: 0.8,
      ),
    );
  }
}

class _BookingCard extends StatelessWidget {
  const _BookingCard({
    required this.booking,
    this.onAccept,
    this.onReject,
  });

  final BookingModel booking;
  final VoidCallback? onAccept;
  final VoidCallback? onReject;

  Color _statusColor(String status) => switch (status) {
    'pending' => Colors.orange,
    'accepted' => Colors.blue,
    'paid' || 'confirmed' => const Color(0xFF4CAF50),
    'completed' => Colors.teal,
    'cancelled' || 'rejected' => Colors.red,
    'disputed' => Colors.deepOrange,
    _ => Colors.grey,
  };

  String _statusLabel(String status) => switch (status) {
    'pending' => 'En attente',
    'accepted' => 'Acceptée',
    'paid' => 'Payée',
    'confirmed' => 'Confirmée',
    'completed' => 'Terminée',
    'cancelled' => 'Annulée',
    'rejected' => 'Refusée',
    'disputed' => 'Litige',
    _ => status,
  };

  @override
  Widget build(BuildContext context) {
    final eventDate = DateFormat('dd MMM yyyy', 'fr_FR').format(
      DateTime.tryParse(booking.eventDate) ?? DateTime.now(),
    );

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  booking.clientName,
                  style: GoogleFonts.manrope(
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                    color: const Color(0xFF1A1A2E),
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: _statusColor(booking.status).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  _statusLabel(booking.status),
                  style: GoogleFonts.manrope(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: _statusColor(booking.status),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              const Icon(
                Icons.calendar_today_outlined,
                size: 13,
                color: Colors.grey,
              ),
              const SizedBox(width: 4),
              Text(
                eventDate,
                style: GoogleFonts.manrope(fontSize: 12, color: Colors.grey),
              ),
              const SizedBox(width: 10),
              const Icon(
                Icons.location_on_outlined,
                size: 13,
                color: Colors.grey,
              ),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  booking.eventLocation,
                  style: GoogleFonts.manrope(fontSize: 12, color: Colors.grey),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          if (onAccept != null && onReject != null) ...[
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: onReject,
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red,
                      side: const BorderSide(color: Colors.red),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 8),
                    ),
                    child: Text(
                      'Refuser',
                      style: GoogleFonts.manrope(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: ElevatedButton(
                    onPressed: onAccept,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF4CAF50),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 8),
                    ),
                    child: Text(
                      'Accepter',
                      style: GoogleFonts.manrope(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
