import 'package:bookmi_app/features/messaging/bloc/messaging_cubit.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_state.dart';
import 'package:bookmi_app/features/messaging/data/models/conversation_model.dart';
import 'package:bookmi_app/features/messaging/presentation/pages/admin_broadcast_page.dart';
import 'package:bookmi_app/features/messaging/presentation/pages/chat_page.dart';
import 'package:bookmi_app/features/notifications/data/models/push_notification_model.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

// ── Design tokens (dark) ─────────────────────────────────────────
const _primary = Color(0xFF2196F3);
const _secondary = Colors.white;
const _muted = Color(0xFF0D1421);
const _mutedFg = Color(0xFF94A3B8);
const _border = Color(0x1AFFFFFF);
const _admin = Color(0xFF7C3AED); // purple for admin messages

class ConversationListPage extends StatefulWidget {
  const ConversationListPage({super.key});

  @override
  State<ConversationListPage> createState() => _ConversationListPageState();
}

class _ConversationListPageState extends State<ConversationListPage> {
  @override
  void initState() {
    super.initState();
    context.read<MessagingCubit>().loadConversations();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: Column(
        children: [
          const _MessagesHeader(),
          Expanded(child: _ConversationsList()),
        ],
      ),
    );
  }
}

// ── Header ────────────────────────────────────────────────────────
class _MessagesHeader extends StatelessWidget {
  const _MessagesHeader();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.transparent,
      padding: const EdgeInsets.only(
        top: 16,
        left: 20,
        right: 20,
        bottom: 0,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                'Messages',
                style: GoogleFonts.nunito(
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                  color: Colors.white,
                  letterSpacing: -0.5,
                ),
              ),
              const Spacer(),
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.08),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  Icons.add,
                  color: Colors.white.withValues(alpha: 0.7),
                  size: 18,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Glass search bar
          Container(
            height: 42,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.06),
              border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                const SizedBox(width: 12),
                Icon(
                  Icons.search,
                  color: Colors.white.withValues(alpha: 0.35),
                  size: 15,
                ),
                const SizedBox(width: 8),
                Text(
                  'Rechercher une conversation…',
                  style: GoogleFonts.nunito(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: Colors.white.withValues(alpha: 0.25),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }
}

class _TabToggle extends StatelessWidget {
  const _TabToggle({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          margin: const EdgeInsets.all(4),
          decoration: BoxDecoration(
            color: isSelected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Center(
            child: Text(
              label,
              style: GoogleFonts.manrope(
                fontSize: 13,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                color: isSelected
                    ? _secondary
                    : Colors.white.withValues(alpha: 0.7),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ── List (conversations + admin broadcasts merged) ─────────────────
class _ConversationsList extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocBuilder<MessagingCubit, MessagingState>(
      builder: (context, state) {
        return switch (state) {
          MessagingLoading() => _buildLoading(),
          MessagingError(:final message) => _buildError(context, message),
          ConversationsLoaded(:final conversations, :final broadcasts) =>
            _buildList(context, conversations, broadcasts),
          _ => _buildEmpty(),
        };
      },
    );
  }

  // ── Merged entry type (conversations + broadcasts sorted by date) ─

  Widget _buildList(
    BuildContext context,
    List<ConversationModel> conversations,
    List<PushNotificationModel> broadcasts,
  ) {
    if (conversations.isEmpty && broadcasts.isEmpty) return _buildEmpty();

    // Build tagged entries: (DateTime, Widget)
    final entries = <(DateTime, Widget)>[];

    for (final c in conversations) {
      entries.add((
        c.lastMessageAt ?? DateTime(2000),
        _DismissibleTile(
          key: ValueKey('conv_${c.id}'),
          onDismissed: () =>
              context.read<MessagingCubit>().deleteConversation(c.id),
          child: _ConversationTile(conversation: c),
        ),
      ));
    }
    for (final b in broadcasts) {
      entries.add((
        b.createdAt,
        _DismissibleTile(
          key: ValueKey('bcast_${b.id}'),
          onDismissed: () =>
              context.read<MessagingCubit>().removeBroadcast(b.id),
          child: _AdminBroadcastTile(broadcast: b),
        ),
      ));
    }

    entries.sort((a, b) => b.$1.compareTo(a.$1)); // newest first

    return RefreshIndicator(
      onRefresh: () => context.read<MessagingCubit>().loadConversations(),
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 20),
        itemCount: entries.length,
        itemBuilder: (_, i) => entries[i].$2,
      ),
    );
  }

  Widget _buildLoading() {
    return ListView.separated(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      itemCount: 5,
      separatorBuilder: (_, __) =>
          const Divider(color: Color(0x0DFFFFFF), height: 1),
      itemBuilder: (_, __) => Container(
        height: 82,
        margin: const EdgeInsets.symmetric(vertical: 0),
        decoration: const BoxDecoration(
          border: Border(bottom: BorderSide(color: Color(0x0DFFFFFF))),
        ),
        child: Row(
          children: [
            const SizedBox(width: 12),
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: _border,
                borderRadius: BorderRadius.circular(14),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(height: 12, width: 130, color: _border),
                  const SizedBox(height: 6),
                  Container(height: 10, width: 180, color: _border),
                  const SizedBox(height: 6),
                  Container(height: 10, width: 110, color: _border),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildError(BuildContext context, String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 48, color: _mutedFg),
            const SizedBox(height: 12),
            Text(
              message,
              style: GoogleFonts.manrope(color: _mutedFg),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: () =>
                  context.read<MessagingCubit>().loadConversations(),
              child: const Text('Réessayer'),
            ),
          ],
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
              color: _primary.withValues(alpha: 0.08),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.chat_bubble_outline,
              size: 32,
              color: _primary,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'Aucun message',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: _secondary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Vos échanges avec les talents\napparaîtront ici',
            style: GoogleFonts.manrope(fontSize: 13, color: _mutedFg),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

// ── Swipe-to-delete wrapper ────────────────────────────────────────
class _DismissibleTile extends StatelessWidget {
  const _DismissibleTile({
    super.key,
    required this.child,
    required this.onDismissed,
  });

  final Widget child;
  final VoidCallback onDismissed;

  @override
  Widget build(BuildContext context) {
    return Dismissible(
      key: key!,
      direction: DismissDirection.endToStart,
      onDismissed: (_) => onDismissed(),
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        decoration: BoxDecoration(
          color: const Color(0xFFEF4444),
          borderRadius: BorderRadius.circular(14),
        ),
        child: const Icon(Icons.delete_outline, color: Colors.white, size: 26),
      ),
      child: child,
    );
  }
}

// ── Booking conversation tile ──────────────────────────────────────
class _ConversationTile extends StatelessWidget {
  const _ConversationTile({required this.conversation});
  final ConversationModel conversation;

  String get _talentName =>
      conversation.talentName ?? conversation.clientName ?? 'Inconnu';

  String get _preview {
    final msg = conversation.latestMessage;
    if (msg == null) return 'Appuyez pour voir les échanges';
    if (msg.type == 'image') return '📷 Photo';
    if (msg.type == 'video') return '🎥 Vidéo';
    return msg.content.isNotEmpty
        ? msg.content
        : 'Appuyez pour voir les échanges';
  }

  String get _timeLabel {
    final dt = conversation.lastMessageAt;
    if (dt == null) return '';
    final now = DateTime.now();
    final diff = now.difference(dt);
    if (diff.inMinutes < 1) return 'À l\'instant';
    if (diff.inHours < 1) return '${diff.inMinutes}m';
    if (diff.inDays < 1) return '${diff.inHours}h';
    return '${diff.inDays}j';
  }

  String get _initials {
    final parts = _talentName.split(' ');
    if (parts.length >= 2) return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    return _talentName.isNotEmpty ? _talentName[0].toUpperCase() : '?';
  }

  @override
  Widget build(BuildContext context) {
    final booking = conversation.booking;
    final unread = conversation.unreadCount;
    final isClosed = conversation.isClosed;
    final avatarUrl = conversation.talentAvatarUrl;

    return GestureDetector(
      onTap: () {
        final cubit = context.read<MessagingCubit>();
        Navigator.of(context)
            .push(
              MaterialPageRoute<void>(
                builder: (_) => BlocProvider.value(
                  value: cubit,
                  child: ChatPage(
                    conversationId: conversation.id,
                    otherPartyName: _talentName,
                    talentAvatarUrl: avatarUrl,
                    booking: booking,
                  ),
                ),
              ),
            )
            .then((_) => cubit.restoreConversationsIfNeeded());
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: const BoxDecoration(
          border: Border(
            bottom: BorderSide(
              color: Color(0x0DFFFFFF),
            ), // rgba(255,255,255,0.05)
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Avatar ─────────────────────────────────────
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(14),
                    color: _border,
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: avatarUrl != null && avatarUrl.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: avatarUrl,
                          fit: BoxFit.cover,
                          errorWidget: (_, __, ___) => _AvatarInitials(
                            initials: _initials,
                          ),
                        )
                      : _AvatarInitials(initials: _initials),
                ),
                if (isClosed)
                  Positioned(
                    bottom: -2,
                    right: -2,
                    child: Container(
                      width: 16,
                      height: 16,
                      decoration: BoxDecoration(
                        color: _mutedFg,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 2),
                      ),
                      child: const Icon(
                        Icons.lock,
                        size: 8,
                        color: Colors.white,
                      ),
                    ),
                  )
                else if (unread > 0)
                  Positioned(
                    top: -4,
                    right: -4,
                    child: Container(
                      width: 18,
                      height: 18,
                      decoration: const BoxDecoration(
                        color: Color(0xFFEF4444),
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          '$unread',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(width: 12),
            // ── Content ────────────────────────────────────
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          _talentName,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: _secondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        _timeLabel,
                        style: GoogleFonts.manrope(
                          fontSize: 11,
                          color: _mutedFg,
                        ),
                      ),
                    ],
                  ),
                  // Booking details (package name, date, location)
                  if (booking != null) ...[
                    const SizedBox(height: 3),
                    if (booking.title != null)
                      Text(
                        booking.title!,
                        style: GoogleFonts.manrope(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: _secondary.withValues(alpha: 0.8),
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        if (booking.eventDate != null) ...[
                          const Icon(
                            Icons.calendar_today_outlined,
                            size: 11,
                            color: _mutedFg,
                          ),
                          const SizedBox(width: 3),
                          Text(
                            _formatDate(booking.eventDate!),
                            style: GoogleFonts.manrope(
                              fontSize: 11,
                              color: _mutedFg,
                            ),
                          ),
                        ],
                        if (booking.eventDate != null &&
                            booking.eventLocation != null)
                          Text(
                            ' · ',
                            style: GoogleFonts.manrope(
                              fontSize: 11,
                              color: _mutedFg,
                            ),
                          ),
                        if (booking.eventLocation != null)
                          Expanded(
                            child: Row(
                              children: [
                                const Icon(
                                  Icons.location_on_outlined,
                                  size: 11,
                                  color: _mutedFg,
                                ),
                                const SizedBox(width: 2),
                                Expanded(
                                  child: Text(
                                    booking.eventLocation!,
                                    style: GoogleFonts.manrope(
                                      fontSize: 11,
                                      color: _mutedFg,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ],
                  const SizedBox(height: 4),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Expanded(
                        child: Text(
                          _preview,
                          style: GoogleFonts.manrope(
                            fontSize: 13,
                            color: unread > 0
                                ? _secondary
                                : const Color(0xFF475569),
                            fontWeight: unread > 0
                                ? FontWeight.w600
                                : FontWeight.normal,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (isClosed)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 6,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: _mutedFg.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            'Clos',
                            style: GoogleFonts.manrope(
                              fontSize: 10,
                              color: _mutedFg,
                              fontWeight: FontWeight.w600,
                            ),
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

  String _formatDate(String isoDate) {
    final dt = DateTime.tryParse(isoDate);
    if (dt == null) return isoDate;
    const months = [
      'Jan',
      'Fév',
      'Mar',
      'Avr',
      'Mai',
      'Jun',
      'Jul',
      'Aoû',
      'Sep',
      'Oct',
      'Nov',
      'Déc',
    ];
    return '${dt.day} ${months[dt.month - 1]} ${dt.year}';
  }
}

class _AvatarInitials extends StatelessWidget {
  const _AvatarInitials({required this.initials});
  final String initials;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(colors: [_primary, Color(0xFF1565C0)]),
      ),
      child: Center(
        child: Text(
          initials,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

// ── Admin broadcast tile ───────────────────────────────────────────
class _AdminBroadcastTile extends StatelessWidget {
  const _AdminBroadcastTile({required this.broadcast});
  final PushNotificationModel broadcast;

  String get _timeLabel {
    final now = DateTime.now();
    final diff = now.difference(broadcast.createdAt);
    if (diff.inMinutes < 1) return 'À l\'instant';
    if (diff.inHours < 1) return '${diff.inMinutes}m';
    if (diff.inDays < 1) return '${diff.inHours}h';
    return '${diff.inDays}j';
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => AdminBroadcastPage(broadcast: broadcast),
          ),
        );
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: const BoxDecoration(
          border: Border(
            bottom: BorderSide(color: Color(0x0DFFFFFF)),
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Admin icon
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    _admin.withValues(alpha: 0.8),
                    _admin,
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(
                Icons.campaign_outlined,
                color: Colors.white,
                size: 24,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Row(
                          children: [
                            Text(
                              'BookMi',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 14,
                                fontWeight: FontWeight.w700,
                                color: _secondary,
                              ),
                            ),
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 6,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: _admin.withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                'Admin',
                                style: GoogleFonts.manrope(
                                  fontSize: 9,
                                  fontWeight: FontWeight.w700,
                                  color: _admin,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        _timeLabel,
                        style: GoogleFonts.manrope(
                          fontSize: 11,
                          color: _mutedFg,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 3),
                  Text(
                    broadcast.title,
                    style: GoogleFonts.manrope(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: _secondary.withValues(alpha: 0.8),
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    broadcast.body,
                    style: GoogleFonts.manrope(
                      fontSize: 13,
                      color: const Color(0xFF475569),
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            if (broadcast.isUnread)
              Container(
                width: 8,
                height: 8,
                margin: const EdgeInsets.only(top: 4, left: 8),
                decoration: const BoxDecoration(
                  color: _admin,
                  shape: BoxShape.circle,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
