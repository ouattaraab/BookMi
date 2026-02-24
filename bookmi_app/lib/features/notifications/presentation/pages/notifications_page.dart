import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/notifications/data/models/push_notification_model.dart';
import 'package:bookmi_app/features/notifications/data/repositories/notification_repository.dart';

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({required this.repository, super.key});
  final NotificationRepository repository;

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  List<PushNotificationModel> _items = [];
  bool _loading = true;
  String? _error;

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
    final result = await widget.repository.getNotifications();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        // Mark all as read after loading
        unawaited(widget.repository.markAllRead());
        setState(() {
          _items = data;
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
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: const Color(0xFF00274D),
        foregroundColor: Colors.white,
        title: Text(
          'Notifications',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
        actions: [
          if (_items.isNotEmpty)
            TextButton(
              onPressed: () async {
                await widget.repository.markAllRead();
                setState(() {
                  _items = _items
                      .map(
                        (n) => PushNotificationModel(
                          id: n.id,
                          title: n.title,
                          body: n.body,
                          data: n.data,
                          readAt: DateTime.now(),
                          createdAt: n.createdAt,
                        ),
                      )
                      .toList();
                });
              },
              child: Text(
                'Tout lire',
                style:
                    GoogleFonts.manrope(color: Colors.white70, fontSize: 12),
              ),
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        _error!,
                        style: GoogleFonts.manrope(color: Colors.grey),
                      ),
                      TextButton(
                        onPressed: _load,
                        child: const Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : _items.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.notifications_none_outlined,
                            size: 56,
                            color: Colors.grey.shade300,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'Aucune notification',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                              color: const Color(0xFF00274D),
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Vous serez notifié des mises à jour de vos réservations.',
                            style: GoogleFonts.manrope(
                              fontSize: 13,
                              color: Colors.grey,
                            ),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        itemCount: _items.length,
                        separatorBuilder: (_, __) =>
                            const Divider(height: 1, indent: 72),
                        itemBuilder: (context, index) {
                          final n = _items[index];
                          return _NotificationTile(
                            notification: n,
                            onTap: () async {
                              if (n.isUnread) {
                                await widget.repository.markRead(n.id);
                                setState(() {
                                  _items[index] = PushNotificationModel(
                                    id: n.id,
                                    title: n.title,
                                    body: n.body,
                                    data: n.data,
                                    readAt: DateTime.now(),
                                    createdAt: n.createdAt,
                                  );
                                });
                              }
                            },
                          );
                        },
                      ),
                    ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({
    required this.notification,
    required this.onTap,
  });
  final PushNotificationModel notification;
  final VoidCallback onTap;

  IconData _iconFor(String? type) {
    switch (type) {
      case 'booking_requested':
        return Icons.calendar_today_outlined;
      case 'booking_accepted':
        return Icons.check_circle_outline;
      case 'booking_rejected':
        return Icons.cancel_outlined;
      case 'booking_cancelled':
        return Icons.close_outlined;
      case 'payment_received':
      case 'payment_confirmed':
        return Icons.payment_outlined;
      case 'booking_completed':
        return Icons.star_outline;
      case 'escrow_released':
        return Icons.account_balance_wallet_outlined;
      case 'admin_message':
        return Icons.campaign_outlined;
      default:
        return Icons.notifications_outlined;
    }
  }

  Color _colorFor(String? type) {
    switch (type) {
      case 'booking_accepted':
        return Colors.green;
      case 'booking_rejected':
      case 'booking_cancelled':
        return Colors.red;
      case 'payment_received':
      case 'payment_confirmed':
      case 'escrow_released':
        return Colors.teal;
      case 'booking_completed':
        return Colors.amber;
      default:
        return const Color(0xFF3B9DF2);
    }
  }

  @override
  Widget build(BuildContext context) {
    final type = notification.data?['type'] as String?;
    final color = _colorFor(type);
    final isUnread = notification.isUnread;

    return InkWell(
      onTap: onTap,
      child: Container(
        color: isUnread ? color.withValues(alpha: 0.04) : Colors.transparent,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(_iconFor(type), color: color, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    notification.title,
                    style: GoogleFonts.plusJakartaSans(
                      fontWeight:
                          isUnread ? FontWeight.w700 : FontWeight.w600,
                      fontSize: 13,
                      color: const Color(0xFF0F172A),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    notification.body,
                    style: GoogleFonts.manrope(
                      fontSize: 12,
                      color: const Color(0xFF64748B),
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _formatDate(notification.createdAt),
                    style: GoogleFonts.manrope(
                      fontSize: 10,
                      color: const Color(0xFF94A3B8),
                    ),
                  ),
                ],
              ),
            ),
            if (isUnread)
              Container(
                width: 8,
                height: 8,
                margin: const EdgeInsets.only(top: 4, left: 8),
                decoration: BoxDecoration(
                  color: color,
                  shape: BoxShape.circle,
                ),
              ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime dt) {
    final now = DateTime.now();
    final diff = now.difference(dt);
    if (diff.inMinutes < 60) return 'Il y a ${diff.inMinutes} min';
    if (diff.inHours < 24) return 'Il y a ${diff.inHours}h';
    if (diff.inDays == 1) return 'Hier';
    return '${dt.day}/${dt.month}/${dt.year}';
  }
}
