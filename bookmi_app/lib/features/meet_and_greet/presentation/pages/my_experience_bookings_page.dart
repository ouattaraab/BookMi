import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/meet_and_greet/data/models/experience_booking_list_item.dart';
import 'package:bookmi_app/features/meet_and_greet/data/models/experience_model.dart';
import 'package:bookmi_app/features/meet_and_greet/data/repositories/experience_repository.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

// ── Aurora palette ────────────────────────────────────────────────
const _blue = Color(0xFF1AB3FF);
const _violet = Color(0xFF8B5CF6);
const _darkBg = Color(0xFF0F1C3A);
const _cardBg = Color(0xFF162040);
const _mutedText = Color(0xFF94A3B8);
const _borderColor = Color(0x1AFFFFFF);

class MyExperienceBookingsPage extends StatefulWidget {
  const MyExperienceBookingsPage({
    required this.repository,
    super.key,
  });

  final ExperienceRepository repository;

  @override
  State<MyExperienceBookingsPage> createState() =>
      _MyExperienceBookingsPageState();
}

class _MyExperienceBookingsPageState extends State<MyExperienceBookingsPage> {
  List<ExperienceBookingListItem>? _bookings;
  String? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadBookings();
  }

  Future<void> _loadBookings() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await widget.repository.getMyExperienceBookings();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _bookings = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _darkBg,
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [_blue, _violet],
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
            ),
          ),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        title: Text(
          'Mes Meet & Greet',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(color: _blue),
      );
    }

    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, color: _mutedText, size: 48),
              const SizedBox(height: 16),
              Text(
                _error!,
                textAlign: TextAlign.center,
                style: GoogleFonts.manrope(color: _mutedText, fontSize: 14),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _loadBookings,
                style: ElevatedButton.styleFrom(
                  backgroundColor: _blue,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(
                  'Réessayer',
                  style: GoogleFonts.manrope(fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
        ),
      );
    }

    final bookings = _bookings ?? [];

    if (bookings.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.star_outline_rounded,
                color: _mutedText.withValues(alpha: 0.5),
                size: 64,
              ),
              const SizedBox(height: 20),
              Text(
                'Aucun Meet & Greet pour le moment',
                textAlign: TextAlign.center,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: _mutedText,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Découvrez les événements disponibles et rejoignez vos artistes préférés.',
                textAlign: TextAlign.center,
                style: GoogleFonts.manrope(fontSize: 13, color: _mutedText),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      color: _blue,
      backgroundColor: _cardBg,
      onRefresh: _loadBookings,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: bookings.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) =>
            _ExperienceBookingCard(item: bookings[index]),
      ),
    );
  }
}

// ── Booking card ─────────────────────────────────────────────────
class _ExperienceBookingCard extends StatelessWidget {
  const _ExperienceBookingCard({required this.item});

  final ExperienceBookingListItem item;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => context.pushNamed(
        RouteNames.experienceDetail,
        pathParameters: {'id': item.experienceId.toString()},
        extra: ExperienceModel(
          id: item.experienceId,
          title: item.title,
          description: '',
          eventDate: item.eventDate,
          eventTime: item.eventTime,
          pricePerSeat: item.pricePerSeat,
          maxSeats: 0,
          seatsAvailable: 0,
          status: 'published',
          venueAddress: item.venueAddress,
          coverImageUrl: item.coverImageUrl,
          talentProfile: item.talentId != null
              ? ExperienceTalentInfo(
                  id: item.talentId!,
                  stageName: item.talentStageName ?? '',
                  slug: '',
                  profilePhoto: item.talentPhoto,
                )
              : null,
          myBooking: ExperienceBookingInfo(
            id: item.bookingId,
            seatsCount: item.seatsCount,
            totalAmount: item.totalAmount,
            status: item.status,
          ),
        ),
      ),
      child: Container(
        decoration: BoxDecoration(
          color: _cardBg,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: _borderColor),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Cover image header
            _CoverHeader(
              coverImageUrl: item.coverImageUrl,
              talentStageName: item.talentStageName,
            ),
            // Content
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title
                  Text(
                    item.title,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 10),
                  // Date row
                  _InfoRow(
                    icon: Icons.calendar_today_outlined,
                    text: _formatDate(item.eventDate, item.eventTime),
                  ),
                  // Venue row (only if available)
                  if (item.venueAddress != null &&
                      item.venueAddress!.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    _InfoRow(
                      icon: Icons.location_on_outlined,
                      text: item.venueAddress!,
                      color: _blue,
                    ),
                  ],
                  const SizedBox(height: 6),
                  // Price row
                  _InfoRow(
                    icon: Icons.confirmation_num_outlined,
                    text: _formatPrice(item.seatsCount, item.totalAmount),
                  ),
                  const SizedBox(height: 12),
                  // Bottom row: status badge + receipt button
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      _StatusBadge(
                        status: item.status,
                        label: item.statusLabel,
                      ),
                      TextButton.icon(
                        onPressed: () => _showReceiptSheet(context),
                        icon: const Icon(Icons.receipt_long_outlined, size: 16),
                        label: Text(
                          'Voir le reçu',
                          style: GoogleFonts.manrope(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        style: TextButton.styleFrom(
                          foregroundColor: _blue,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 6,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                            side: const BorderSide(color: _blue),
                          ),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showReceiptSheet(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) => _ReceiptSheet(item: item),
    );
  }

  static String _formatDate(String eventDate, String eventTime) {
    try {
      final date = DateTime.parse(eventDate);
      final formatted = DateFormat('d MMM yyyy', 'fr_FR').format(date);
      return '$formatted à $eventTime';
    } on FormatException {
      return '$eventDate à $eventTime';
    } on Exception {
      return '$eventDate à $eventTime';
    }
  }

  static String _formatPrice(int seatsCount, int totalAmount) {
    final formatted = NumberFormat('#,###', 'fr_FR').format(totalAmount);
    final seatLabel = seatsCount > 1 ? 'places' : 'place';
    return '$seatsCount $seatLabel · $formatted FCFA';
  }
}

// ── Cover header ─────────────────────────────────────────────────
class _CoverHeader extends StatelessWidget {
  const _CoverHeader({
    this.coverImageUrl,
    this.talentStageName,
  });

  final String? coverImageUrl;
  final String? talentStageName;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 120,
      width: double.infinity,
      child: Stack(
        fit: StackFit.expand,
        children: [
          // Background image or fallback gradient
          if (coverImageUrl != null)
            CachedNetworkImage(
              imageUrl: coverImageUrl!,
              fit: BoxFit.cover,
              errorWidget: (_, __, ___) => _gradientFallback(),
            )
          else
            _gradientFallback(),
          // Dark overlay
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.black.withValues(alpha: 0.3),
                  Colors.black.withValues(alpha: 0.65),
                ],
              ),
            ),
          ),
          // Talent name
          if (talentStageName != null)
            Positioned(
              left: 12,
              bottom: 10,
              right: 12,
              child: Text(
                talentStageName!,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Colors.white,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
        ],
      ),
    );
  }

  Widget _gradientFallback() {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [_blue, _violet],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
    );
  }
}

// ── Info row ─────────────────────────────────────────────────────
class _InfoRow extends StatelessWidget {
  const _InfoRow({
    required this.icon,
    required this.text,
    this.color = _mutedText,
  });

  final IconData icon;
  final String text;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 6),
        Expanded(
          child: Text(
            text,
            style: GoogleFonts.manrope(fontSize: 12, color: color),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}

// ── Status badge ─────────────────────────────────────────────────
class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status, required this.label});

  final String status;
  final String label;

  @override
  Widget build(BuildContext context) {
    final Color bg;
    final Color fg;

    switch (status) {
      case 'confirmed':
        bg = const Color(0xFF14B8A6).withValues(alpha: 0.15);
        fg = const Color(0xFF14B8A6);
      case 'cancelled':
        bg = const Color(0xFFEF4444).withValues(alpha: 0.12);
        fg = const Color(0xFFEF4444);
      default: // pending
        bg = const Color(0xFFF59E0B).withValues(alpha: 0.15);
        fg = const Color(0xFFF59E0B);
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: fg.withValues(alpha: 0.4)),
      ),
      child: Text(
        label,
        style: GoogleFonts.manrope(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: fg,
        ),
      ),
    );
  }
}

// ── Receipt bottom sheet ──────────────────────────────────────────
class _ReceiptSheet extends StatelessWidget {
  const _ReceiptSheet({required this.item});

  final ExperienceBookingListItem item;

  @override
  Widget build(BuildContext context) {
    final totalFormatted = NumberFormat(
      '#,###',
      'fr_FR',
    ).format(item.totalAmount);
    final pricePerSeatFormatted = NumberFormat(
      '#,###',
      'fr_FR',
    ).format(item.pricePerSeat);

    return Container(
      decoration: const BoxDecoration(
        color: _cardBg,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Handle
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: _mutedText.withValues(alpha: 0.3),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 20),
          // Title
          Text(
            'Reçu M&G',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Référence #${item.bookingId}',
            style: GoogleFonts.manrope(fontSize: 12, color: _mutedText),
          ),
          const SizedBox(height: 20),
          const Divider(color: _borderColor),
          const SizedBox(height: 12),
          _ReceiptRow(label: 'Événement', value: item.title),
          if (item.talentStageName != null)
            _ReceiptRow(label: 'Artiste', value: item.talentStageName!),
          _ReceiptRow(
            label: 'Date',
            value: _formatDateFull(item.eventDate, item.eventTime),
          ),
          if (item.venueAddress != null && item.venueAddress!.isNotEmpty)
            _ReceiptRow(label: 'Lieu', value: item.venueAddress!),
          _ReceiptRow(
            label: 'Nombre de places',
            value: '${item.seatsCount}',
          ),
          _ReceiptRow(
            label: 'Prix par place',
            value: '$pricePerSeatFormatted FCFA',
          ),
          const SizedBox(height: 8),
          const Divider(color: _borderColor),
          const SizedBox(height: 8),
          // Total
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Total',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
              Text(
                '$totalFormatted FCFA',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: _blue,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Center(
            child: _StatusBadge(
              status: item.status,
              label: item.statusLabel,
            ),
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  static String _formatDateFull(String eventDate, String eventTime) {
    try {
      final date = DateTime.parse(eventDate);
      final formatted = DateFormat('d MMM yyyy', 'fr_FR').format(date);
      return '$formatted à $eventTime';
    } on FormatException {
      return '$eventDate à $eventTime';
    } on Exception {
      return '$eventDate à $eventTime';
    }
  }
}

class _ReceiptRow extends StatelessWidget {
  const _ReceiptRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: GoogleFonts.manrope(fontSize: 13, color: _mutedText),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: GoogleFonts.manrope(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: Colors.white,
              ),
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}
