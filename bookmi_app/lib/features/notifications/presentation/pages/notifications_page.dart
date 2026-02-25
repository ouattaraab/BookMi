import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/services/notification_service.dart';
import 'package:bookmi_app/features/notifications/data/models/push_notification_model.dart';
import 'package:bookmi_app/features/notifications/data/repositories/notification_repository.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

// ── Dark design tokens ────────────────────────────────────────────
const _bg      = Color(0xFF0A0F1E);
const _cardBg  = Color(0xFF0D1421);
const _primary = Color(0xFF2196F3);
const _accent  = Color(0xFF64B5F6);
const _muted   = Color(0xFF94A3B8);
const _border  = Color(0x1AFFFFFF);
const _divider = Color(0x0DFFFFFF);

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({required this.repository, super.key});
  final NotificationRepository repository;

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  List<PushNotificationModel> _items = [];
  bool    _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  static const _bookingTypes = {
    'booking_requested',
    'booking_accepted',
    'booking_rejected',
    'booking_cancelled',
    'booking_completed',
    'payment_received',
    'payment_confirmed',
    'escrow_released',
  };

  void _navigate(PushNotificationModel n) {
    final type      = n.data?['type'] as String?;
    final bookingId = n.data?['booking_id']?.toString();

    if (bookingId != null && bookingId.isNotEmpty) {
      context.push('/bookings/booking/$bookingId');
    } else if (type != null && _bookingTypes.contains(type)) {
      context.go(RoutePaths.bookings);
    } else if (type == 'new_message' || type == 'admin_broadcast') {
      context.go(RoutePaths.messages);
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error   = null;
    });
    final result = await widget.repository.getNotifications();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        unawaited(
          widget.repository.markAllRead().then((_) {
            NotificationService.instance.notifyNotificationsRead();
          }),
        );
        setState(() {
          _items   = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error   = message;
          _loading = false;
        });
    }
  }

  Future<void> _markAllRead() async {
    await widget.repository.markAllRead();
    NotificationService.instance.notifyNotificationsRead();
    setState(() {
      _items = _items.map((n) => PushNotificationModel(
        id:        n.id,
        title:     n.title,
        body:      n.body,
        data:      n.data,
        readAt:    DateTime.now(),
        createdAt: n.createdAt,
      )).toList();
    });
  }

  // ── AppBar ──────────────────────────────────────────────────────
  PreferredSizeWidget _buildAppBar() {
    final hasUnread = _items.any((n) => n.isUnread);
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
                    // Back
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
                    const SizedBox(width: 8),
                    // Title
                    Expanded(
                      child: Text(
                        'Notifications',
                        style: GoogleFonts.nunito(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                          letterSpacing: -0.3,
                        ),
                      ),
                    ),
                    // "Tout lire" chip
                    if (hasUnread)
                      Padding(
                        padding: const EdgeInsets.only(right: 16),
                        child: GestureDetector(
                          onTap: _markAllRead,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 7,
                            ),
                            decoration: BoxDecoration(
                              color: _primary.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(9),
                              border: Border.all(
                                color: _primary.withValues(alpha: 0.25),
                              ),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(
                                  Icons.done_all_rounded,
                                  size: 13,
                                  color: _accent,
                                ),
                                const SizedBox(width: 5),
                                Text(
                                  'Tout lire',
                                  style: GoogleFonts.manrope(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: _accent,
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
              Container(height: 0.5, color: _divider),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      appBar: _buildAppBar(),
      body: Stack(
        children: [
          // Atmospheric glow
          Positioned(
            top: 0, left: 0, right: 0,
            height: 200,
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: RadialGradient(
                  center: Alignment.topCenter,
                  radius: 1.4,
                  colors: [
                    _primary.withValues(alpha: 0.07),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),
          // Content
          if (_loading)
            _buildSkeleton()
          else if (_error != null)
            _buildError()
          else if (_items.isEmpty)
            _buildEmpty()
          else
            RefreshIndicator(
              onRefresh: _load,
              color: _primary,
              backgroundColor: _cardBg,
              child: ListView.builder(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
                itemCount: _items.length,
                itemBuilder: (context, index) {
                  final n = _items[index];
                  return _NotificationCard(
                    notification: n,
                    onTap: () async {
                      if (n.isUnread) {
                        await widget.repository.markRead(n.id);
                        if (!mounted) return;
                        NotificationService.instance.notifyNotificationsRead();
                        setState(() {
                          _items[index] = PushNotificationModel(
                            id:        n.id,
                            title:     n.title,
                            body:      n.body,
                            data:      n.data,
                            readAt:    DateTime.now(),
                            createdAt: n.createdAt,
                          );
                        });
                      }
                      if (!mounted) return;
                      _navigate(n);
                    },
                  );
                },
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildSkeleton() {
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
      itemCount: 6,
      itemBuilder: (_, __) => Container(
        margin: const EdgeInsets.only(bottom: 10),
        height: 80,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.04),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: _border),
        ),
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              color: _primary.withValues(alpha: 0.1),
              shape: BoxShape.circle,
              border: Border.all(color: _primary.withValues(alpha: 0.2)),
              boxShadow: [
                BoxShadow(
                  color: _primary.withValues(alpha: 0.2),
                  blurRadius: 20,
                  spreadRadius: 2,
                ),
              ],
            ),
            child: const Icon(
              Icons.notifications_none_rounded,
              size: 32,
              color: _accent,
            ),
          ),
          const SizedBox(height: 18),
          Text(
            'Aucune notification',
            style: GoogleFonts.nunito(
              fontSize: 17,
              fontWeight: FontWeight.w800,
              color: Colors.white,
              letterSpacing: -0.3,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Vous serez notifié des mises à jour\nde vos réservations.',
            textAlign: TextAlign.center,
            style: GoogleFonts.manrope(
              fontSize: 13,
              color: _muted,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.error_outline_rounded,
            size: 44,
            color: Colors.white.withValues(alpha: 0.2),
          ),
          const SizedBox(height: 12),
          Text(
            _error!,
            textAlign: TextAlign.center,
            style: GoogleFonts.manrope(
              color: _muted,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 16),
          GestureDetector(
            onTap: _load,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              decoration: BoxDecoration(
                color: _primary.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: _primary.withValues(alpha: 0.25)),
              ),
              child: Text(
                'Réessayer',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: _accent,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Notification card ─────────────────────────────────────────────
class _NotificationCard extends StatelessWidget {
  const _NotificationCard({
    required this.notification,
    required this.onTap,
  });

  final PushNotificationModel notification;
  final VoidCallback          onTap;

  IconData _iconFor(String? type) => switch (type) {
    'booking_requested'                       => Icons.calendar_today_rounded,
    'booking_accepted'                        => Icons.check_circle_rounded,
    'booking_rejected' || 'booking_cancelled' => Icons.cancel_rounded,
    'payment_received' || 'payment_confirmed' => Icons.payments_rounded,
    'booking_completed'                       => Icons.star_rounded,
    'escrow_released'                         => Icons.account_balance_wallet_rounded,
    'admin_message' || 'admin_broadcast'      => Icons.campaign_rounded,
    _                                         => Icons.notifications_rounded,
  };

  Color _colorFor(String? type) => switch (type) {
    'booking_accepted'                        => const Color(0xFF00C853),
    'booking_rejected' || 'booking_cancelled' => const Color(0xFFFF4444),
    'payment_received' || 'payment_confirmed' ||
    'escrow_released'                         => const Color(0xFF00BFA5),
    'booking_completed'                       => const Color(0xFFFFB300),
    'admin_message' || 'admin_broadcast'      => const Color(0xFF7C4DFF),
    _                                         => _primary,
  };

  @override
  Widget build(BuildContext context) {
    final type     = notification.data?['type'] as String?;
    final color    = _colorFor(type);
    final isUnread = notification.isUnread;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        decoration: BoxDecoration(
          color: isUnread
              ? Colors.white.withValues(alpha: 0.06)
              : Colors.white.withValues(alpha: 0.03),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isUnread
                ? color.withValues(alpha: 0.2)
                : Colors.white.withValues(alpha: 0.06),
          ),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: Stack(
            children: [
              // Left accent strip — unread only
              if (isUnread)
                Positioned(
                  left: 0, top: 0, bottom: 0,
                  child: Container(
                    width: 3,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          color,
                          color.withValues(alpha: 0.4),
                        ],
                      ),
                    ),
                  ),
                ),
              // Content
              Padding(
                padding: EdgeInsets.fromLTRB(
                  isUnread ? 19 : 14,
                  12, 12, 12,
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Icon container
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(13),
                        border: Border.all(
                          color: color.withValues(alpha: 0.2),
                        ),
                      ),
                      child: Icon(
                        _iconFor(type),
                        color: color,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 12),
                    // Text
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Title
                          Text(
                            notification.title,
                            style: GoogleFonts.nunito(
                              fontWeight: isUnread
                                  ? FontWeight.w800
                                  : FontWeight.w600,
                              fontSize: 13,
                              color: isUnread
                                  ? Colors.white
                                  : Colors.white.withValues(alpha: 0.75),
                              letterSpacing: -0.1,
                            ),
                          ),
                          const SizedBox(height: 3),
                          // Body
                          Text(
                            notification.body,
                            style: GoogleFonts.manrope(
                              fontSize: 12,
                              color: _muted,
                              height: 1.4,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 6),
                          // Timestamp
                          Row(
                            children: [
                              Icon(
                                Icons.access_time_rounded,
                                size: 10,
                                color: _muted.withValues(alpha: 0.5),
                              ),
                              const SizedBox(width: 3),
                              Text(
                                _formatDate(notification.createdAt),
                                style: GoogleFonts.manrope(
                                  fontSize: 10,
                                  color: _muted.withValues(alpha: 0.6),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    // Unread dot
                    if (isUnread)
                      Padding(
                        padding: const EdgeInsets.only(top: 2, left: 6),
                        child: Container(
                          width: 8,
                          height: 8,
                          decoration: BoxDecoration(
                            color: color,
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: color.withValues(alpha: 0.6),
                                blurRadius: 6,
                              ),
                            ],
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatDate(DateTime dt) {
    final now  = DateTime.now();
    final diff = now.difference(dt);
    if (diff.inMinutes < 60) return 'Il y a ${diff.inMinutes} min';
    if (diff.inHours < 24)   return 'Il y a ${diff.inHours}h';
    if (diff.inDays == 1)    return 'Hier';
    return '${dt.day}/${dt.month}/${dt.year}';
  }
}
