import 'dart:io';

import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_cubit.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_state.dart';
import 'package:bookmi_app/features/messaging/data/models/conversation_model.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';

// ── Design tokens ─────────────────────────────────────────────────
const _primary = Color(0xFF3B9DF2);
const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _border = Color(0xFFE2E8F0);
const _warning = Color(0xFFFBBF24);

class ChatPage extends StatefulWidget {
  const ChatPage({
    super.key,
    required this.conversationId,
    required this.otherPartyName,
    this.talentAvatarUrl,
    this.booking,
  });

  final int conversationId;
  final String otherPartyName;
  final String? talentAvatarUrl;
  final BookingInfo? booking;

  @override
  State<ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends State<ChatPage> {
  final _controller = TextEditingController();
  final _scrollController = ScrollController();
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    context.read<MessagingCubit>().loadMessages(widget.conversationId);
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _showDeleteMessageSheet(BuildContext context, MessageModel msg) {
    showModalBottomSheet<void>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              margin: const EdgeInsets.only(top: 12, bottom: 8),
              decoration: BoxDecoration(
                color: _border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline, color: Color(0xFFEF4444)),
              title: Text(
                'Supprimer ce message',
                style: GoogleFonts.manrope(
                  color: const Color(0xFFEF4444),
                  fontWeight: FontWeight.w600,
                ),
              ),
              onTap: () {
                Navigator.of(context).pop();
                context.read<MessagingCubit>().deleteMessage(
                  widget.conversationId,
                  msg.id,
                );
              },
            ),
            ListTile(
              leading: const Icon(Icons.close, color: Colors.grey),
              title: Text('Annuler', style: GoogleFonts.manrope()),
              onTap: () => Navigator.of(context).pop(),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _send() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    _controller.clear();
    setState(() => _sending = true);
    await context.read<MessagingCubit>().sendMessage(widget.conversationId, text);
    if (mounted) setState(() => _sending = false);
    _scrollToBottom();
  }

  Future<void> _pickMedia() async {
    final picker = ImagePicker();
    final choice = await showModalBottomSheet<String>(
      context: context,
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text('Photo depuis la galerie',
                  style: GoogleFonts.manrope()),
              onTap: () => Navigator.pop(context, 'photo_gallery'),
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined),
              title: Text('Prendre une photo',
                  style: GoogleFonts.manrope()),
              onTap: () => Navigator.pop(context, 'photo_camera'),
            ),
            ListTile(
              leading: const Icon(Icons.videocam_outlined),
              title: Text('Vidéo depuis la galerie',
                  style: GoogleFonts.manrope()),
              onTap: () => Navigator.pop(context, 'video_gallery'),
            ),
          ],
        ),
      ),
    );

    if (choice == null || !mounted) return;

    XFile? file;
    String type = 'image';

    if (choice == 'photo_gallery') {
      file = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
    } else if (choice == 'photo_camera') {
      file = await picker.pickImage(source: ImageSource.camera, imageQuality: 80);
    } else if (choice == 'video_gallery') {
      file = await picker.pickVideo(source: ImageSource.gallery);
      type = 'video';
    }

    if (file == null || !mounted) return;

    setState(() => _sending = true);
    await context.read<MessagingCubit>().sendMediaMessage(
      widget.conversationId,
      file: File(file.path),
      type: type,
    );
    if (mounted) setState(() => _sending = false);
    _scrollToBottom();
  }

  String get _initials {
    final parts = widget.otherPartyName.split(' ');
    if (parts.length >= 2) return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    return widget.otherPartyName.isNotEmpty
        ? widget.otherPartyName[0].toUpperCase()
        : '?';
  }

  @override
  Widget build(BuildContext context) {
    final authState = context.read<AuthBloc>().state;
    final currentUserId =
        authState is AuthAuthenticated ? authState.user.id : -1;
    final isClosed = widget.booking?.isClosed ?? false;

    return Scaffold(
      backgroundColor: _muted,
      body: Column(
        children: [
          _ChatHeader(
            otherPartyName: widget.otherPartyName,
            initials: _initials,
            avatarUrl: widget.talentAvatarUrl,
            booking: widget.booking,
          ),
          if (!isClosed) _SafetyBanner(),
          if (isClosed) _ClosedBanner(),
          Expanded(
            child: BlocConsumer<MessagingCubit, MessagingState>(
              listener: (context, state) {
                if (state is MessagesLoaded || state is MessageSending) {
                  _scrollToBottom();
                }
              },
              builder: (context, state) {
                final messages = switch (state) {
                  MessagesLoaded(:final messages) => messages,
                  MessageSending(:final messages) => messages,
                  _ => null,
                };

                if (state is MessagingLoading) {
                  return const Center(
                    child: CircularProgressIndicator(color: _primary),
                  );
                }

                if (state is MessagingError) {
                  return Center(
                    child: Text(
                      state.message,
                      style: GoogleFonts.manrope(color: _mutedFg),
                    ),
                  );
                }

                if (messages == null || messages.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.chat_bubble_outline,
                          size: 48,
                          color: _border,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          'Envoyez votre premier message',
                          style: GoogleFonts.manrope(color: _mutedFg),
                        ),
                      ],
                    ),
                  );
                }

                return ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 8,
                  ),
                  itemCount: messages.length,
                  itemBuilder: (context, i) {
                    final msg = messages[i];
                    final isMine = msg.senderId == currentUserId;
                    return Column(
                      children: [
                        if (i == 0) _DateSeparator(label: "Aujourd'hui"),
                        GestureDetector(
                          onLongPress: isMine
                              ? () => _showDeleteMessageSheet(context, msg)
                              : null,
                          child: _ChatBubble(message: msg, isMine: isMine),
                        ),
                      ],
                    );
                  },
                );
              },
            ),
          ),
          // Input bar — hidden when conversation is closed
          if (!isClosed)
            _InputBar(
              controller: _controller,
              sending: _sending,
              onSend: _send,
              onPickMedia: _pickMedia,
            ),
        ],
      ),
    );
  }
}

// ── Chat header ───────────────────────────────────────────────────
class _ChatHeader extends StatelessWidget {
  const _ChatHeader({
    required this.otherPartyName,
    required this.initials,
    this.avatarUrl,
    this.booking,
  });

  final String otherPartyName;
  final String initials;
  final String? avatarUrl;
  final BookingInfo? booking;

  String get _subtitle {
    if (booking == null) return '';
    final parts = <String>[];
    if (booking!.title != null) parts.add(booking!.title!);
    if (booking!.eventDate != null) {
      final dt = DateTime.tryParse(booking!.eventDate!);
      if (dt != null) {
        const months = [
          'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun',
          'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc',
        ];
        parts.add('${dt.day} ${months[dt.month - 1]}');
      }
    }
    return parts.join(' · ');
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: _secondary,
      padding: EdgeInsets.only(
        top: MediaQuery.of(context).padding.top + 8,
        left: 8,
        right: 16,
        bottom: 12,
      ),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(
              Icons.arrow_back_ios_new,
              color: Colors.white,
              size: 18,
            ),
            onPressed: () => Navigator.of(context).pop(),
          ),
          // Avatar
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              color: _border,
            ),
            clipBehavior: Clip.antiAlias,
            child: avatarUrl != null && avatarUrl!.isNotEmpty
                ? CachedNetworkImage(
                    imageUrl: avatarUrl!,
                    fit: BoxFit.cover,
                    errorWidget: (_, __, ___) =>
                        _HeaderInitials(initials: initials),
                  )
                : _HeaderInitials(initials: initials),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  otherPartyName,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
                if (_subtitle.isNotEmpty)
                  Text(
                    _subtitle,
                    style: GoogleFonts.manrope(
                      fontSize: 11,
                      color: Colors.white.withValues(alpha: 0.6),
                    ),
                  ),
              ],
            ),
          ),
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.info_outline,
              color: Colors.white,
              size: 18,
            ),
          ),
        ],
      ),
    );
  }
}

class _HeaderInitials extends StatelessWidget {
  const _HeaderInitials({required this.initials});
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
          style: GoogleFonts.manrope(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

// ── Banners ───────────────────────────────────────────────────────
class _SafetyBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFFF0F7FF),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              color: _primary.withValues(alpha: 0.15),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.shield_outlined, size: 15, color: _primary),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'BookMi protège votre paiement jusqu\'à la prestation',
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: _primary,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ClosedBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFFF1F5F9),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              color: _mutedFg.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.lock_outline, size: 15, color: _mutedFg),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Cette conversation est terminée. Les messages sont en lecture seule.',
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: _mutedFg,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Date separator ────────────────────────────────────────────────
class _DateSeparator extends StatelessWidget {
  const _DateSeparator({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Row(
        children: [
          const Expanded(child: Divider(color: _border)),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Text(
              label,
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: _mutedFg,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          const Expanded(child: Divider(color: _border)),
        ],
      ),
    );
  }
}

// ── Chat bubble ───────────────────────────────────────────────────
class _ChatBubble extends StatelessWidget {
  const _ChatBubble({required this.message, required this.isMine});

  final MessageModel message;
  final bool isMine;

  String _formatTime(DateTime? dt) {
    if (dt == null) return '';
    return '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    if (message.isFlagged) {
      return _FlaggedMessage(message: message);
    }
    if (message.type == 'image') {
      return _ImageMessage(message: message, isMine: isMine);
    }
    if (message.type == 'video') {
      return _VideoMessage(message: message, isMine: isMine);
    }

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Align(
        alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
        child: Column(
          crossAxisAlignment:
              isMine ? CrossAxisAlignment.end : CrossAxisAlignment.start,
          children: [
            Container(
              constraints: BoxConstraints(
                maxWidth: MediaQuery.of(context).size.width * 0.72,
              ),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: isMine ? _primary : Colors.white,
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(16),
                  topRight: const Radius.circular(16),
                  bottomLeft: Radius.circular(isMine ? 16 : 0),
                  bottomRight: Radius.circular(isMine ? 0 : 16),
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.06),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (message.isAutoReply)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 4),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.smart_toy_outlined,
                            size: 11,
                            color: isMine ? Colors.white70 : _mutedFg,
                          ),
                          const SizedBox(width: 3),
                          Text(
                            'Réponse automatique',
                            style: GoogleFonts.manrope(
                              fontSize: 10,
                              fontStyle: FontStyle.italic,
                              color: isMine ? Colors.white70 : _mutedFg,
                            ),
                          ),
                        ],
                      ),
                    ),
                  Text(
                    message.content,
                    style: GoogleFonts.manrope(
                      fontSize: 14,
                      color: isMine ? Colors.white : _secondary,
                      height: 1.4,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 3),
            Text(
              _formatTime(message.createdAt),
              style: GoogleFonts.manrope(fontSize: 10, color: _mutedFg),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Image message ─────────────────────────────────────────────────
class _ImageMessage extends StatelessWidget {
  const _ImageMessage({required this.message, required this.isMine});
  final MessageModel message;
  final bool isMine;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Align(
        alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
        child: Column(
          crossAxisAlignment:
              isMine ? CrossAxisAlignment.end : CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: message.mediaUrl != null
                  ? CachedNetworkImage(
                      imageUrl: message.mediaUrl!,
                      width: 220,
                      height: 180,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => Container(
                        width: 220,
                        height: 180,
                        color: _border,
                        child: const Center(
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: _primary,
                          ),
                        ),
                      ),
                      errorWidget: (_, __, ___) => Container(
                        width: 220,
                        height: 180,
                        color: _border,
                        child: const Icon(Icons.broken_image_outlined, color: _mutedFg),
                      ),
                    )
                  : Container(
                      width: 220,
                      height: 180,
                      color: _border,
                      child: const Icon(Icons.image_outlined, color: _mutedFg),
                    ),
            ),
            if (message.content.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  message.content,
                  style: GoogleFonts.manrope(fontSize: 12, color: _mutedFg),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// ── Video message ─────────────────────────────────────────────────
class _VideoMessage extends StatelessWidget {
  const _VideoMessage({required this.message, required this.isMine});
  final MessageModel message;
  final bool isMine;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Align(
        alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
        child: Container(
          width: 220,
          height: 140,
          decoration: BoxDecoration(
            color: _secondary,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Stack(
            alignment: Alignment.center,
            children: [
              const Icon(
                Icons.play_circle_filled_rounded,
                color: Colors.white,
                size: 48,
              ),
              Positioned(
                bottom: 8,
                left: 8,
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.black54,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.videocam_outlined,
                          size: 12, color: Colors.white),
                      const SizedBox(width: 4),
                      Text(
                        'Vidéo',
                        style: GoogleFonts.manrope(
                          fontSize: 10,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Flagged message warning ───────────────────────────────────────
class _FlaggedMessage extends StatelessWidget {
  const _FlaggedMessage({required this.message});
  final MessageModel message;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: _warning.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: _warning.withValues(alpha: 0.3)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.warning_amber_rounded, size: 16, color: _warning),
                const SizedBox(width: 8),
                Text(
                  'Message signalé - Coordonnées détectées',
                  style: GoogleFonts.manrope(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFFB45309),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              message.content,
              style: GoogleFonts.manrope(
                fontSize: 13,
                color: const Color(0xFF92400E),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Input bar ─────────────────────────────────────────────────────
class _InputBar extends StatelessWidget {
  const _InputBar({
    required this.controller,
    required this.sending,
    required this.onSend,
    required this.onPickMedia,
  });

  final TextEditingController controller;
  final bool sending;
  final VoidCallback onSend;
  final VoidCallback onPickMedia;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        left: 12,
        right: 12,
        top: 10,
        bottom: MediaQuery.of(context).padding.bottom + 10,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        border: const Border(top: BorderSide(color: _border)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Media picker button
          GestureDetector(
            onTap: onPickMedia,
            child: Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: _muted,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: _border),
              ),
              child: const Icon(Icons.add, color: _mutedFg, size: 20),
            ),
          ),
          const SizedBox(width: 8),
          // Text input
          Expanded(
            child: Container(
              constraints: const BoxConstraints(maxHeight: 100),
              decoration: BoxDecoration(
                color: _muted,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: _border),
              ),
              child: Row(
                children: [
                  const SizedBox(width: 14),
                  Expanded(
                    child: TextField(
                      controller: controller,
                      style: GoogleFonts.manrope(fontSize: 14, color: _secondary),
                      maxLines: 4,
                      minLines: 1,
                      textCapitalization: TextCapitalization.sentences,
                      decoration: InputDecoration(
                        hintText: 'Écrire un message…',
                        hintStyle: GoogleFonts.manrope(
                          fontSize: 14,
                          color: _mutedFg,
                        ),
                        border: InputBorder.none,
                        isDense: true,
                        contentPadding:
                            const EdgeInsets.symmetric(vertical: 10),
                      ),
                      onSubmitted: (_) => onSend(),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          // Send button
          AnimatedSwitcher(
            duration: const Duration(milliseconds: 200),
            child: sending
                ? const SizedBox(
                    key: ValueKey('loading'),
                    width: 42,
                    height: 42,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: _primary,
                    ),
                  )
                : ValueListenableBuilder<TextEditingValue>(
                    valueListenable: controller,
                    builder: (context, value, _) {
                      final hasText = value.text.isNotEmpty;
                      return GestureDetector(
                        key: const ValueKey('send'),
                        onTap: hasText ? onSend : null,
                        child: Container(
                          width: 42,
                          height: 42,
                          decoration: BoxDecoration(
                            color: hasText ? _primary : _border,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(
                            Icons.send_rounded,
                            color: hasText ? Colors.white : _mutedFg,
                            size: 20,
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
