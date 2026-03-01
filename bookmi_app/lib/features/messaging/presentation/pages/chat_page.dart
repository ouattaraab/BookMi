import 'dart:async';
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
import 'package:just_audio/just_audio.dart';
import 'package:path_provider/path_provider.dart';
import 'package:record/record.dart';

// ── Design tokens ─────────────────────────────────────────────────
const _primary = Color(0xFF3B9DF2);
const _card = Color(0xFF0F1C3A);
const _muted = Color(0xFF112044);
const _mutedFg = Color(0xFF8FA3C0);
const _border = Color(0x1AFFFFFF);
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

  // Voice recording state
  AudioRecorder? _audioRecorder;
  bool _isRecording = false;
  Duration _recordingDuration = Duration.zero;
  Timer? _recordingTimer;

  @override
  void initState() {
    super.initState();
    context.read<MessagingCubit>().loadMessages(widget.conversationId);
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    _recordingTimer?.cancel();
    _audioRecorder?.cancel();
    _audioRecorder = null;
    super.dispose();
  }

  // ── Voice recording methods ─────────────────────────────────────

  Future<void> _startRecording() async {
    final recorder = AudioRecorder();
    final hasPermission = await recorder.hasPermission();
    if (!hasPermission) {
      await recorder.dispose();
      return;
    }

    final dir = await getTemporaryDirectory();
    final path =
        '${dir.path}/voice_${DateTime.now().millisecondsSinceEpoch}.m4a';

    await recorder.start(
      const RecordConfig(encoder: AudioEncoder.aacLc),
      path: path,
    );
    _audioRecorder = recorder;
    setState(() {
      _isRecording = true;
      _recordingDuration = Duration.zero;
    });

    _recordingTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) {
        setState(() => _recordingDuration += const Duration(seconds: 1));
      }
    });
  }

  Future<void> _stopAndSendRecording() async {
    _recordingTimer?.cancel();
    _recordingTimer = null;
    final path = await _audioRecorder?.stop();
    _audioRecorder = null;

    if (mounted) setState(() => _isRecording = false);

    if (path == null) return;
    final file = File(path);
    if (!await file.exists()) return;

    await _sendVoiceMessage(file);
  }

  Future<void> _cancelRecording() async {
    _recordingTimer?.cancel();
    _recordingTimer = null;
    await _audioRecorder?.cancel();
    _audioRecorder = null;
    if (mounted) setState(() => _isRecording = false);
  }

  Future<void> _sendVoiceMessage(File file) async {
    setState(() => _sending = true);
    await context.read<MessagingCubit>().sendMediaMessage(
      widget.conversationId,
      file: file,
      type: 'audio',
    );
    if (mounted) setState(() => _sending = false);
    _scrollToBottom();
  }

  // ── Existing methods ────────────────────────────────────────────

  void _showDeleteMessageSheet(BuildContext context, MessageModel msg) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: _card,
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
              leading: const Icon(
                Icons.delete_outline,
                color: Color(0xFFEF4444),
              ),
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
              leading: const Icon(Icons.close, color: _mutedFg),
              title: Text(
                'Annuler',
                style: GoogleFonts.manrope(color: Colors.white70),
              ),
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
    await context.read<MessagingCubit>().sendMessage(
      widget.conversationId,
      text,
    );
    if (mounted) setState(() => _sending = false);
    _scrollToBottom();
  }

  Future<void> _pickMedia() async {
    final picker = ImagePicker();
    final choice = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: _card,
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
              leading: const Icon(
                Icons.photo_library_outlined,
                color: _mutedFg,
              ),
              title: Text(
                'Photo depuis la galerie',
                style: GoogleFonts.manrope(color: Colors.white),
              ),
              onTap: () => Navigator.pop(context, 'photo_gallery'),
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined, color: _mutedFg),
              title: Text(
                'Prendre une photo',
                style: GoogleFonts.manrope(color: Colors.white),
              ),
              onTap: () => Navigator.pop(context, 'photo_camera'),
            ),
            ListTile(
              leading: const Icon(Icons.videocam_outlined, color: _mutedFg),
              title: Text(
                'Vidéo depuis la galerie',
                style: GoogleFonts.manrope(color: Colors.white),
              ),
              onTap: () => Navigator.pop(context, 'video_gallery'),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );

    if (choice == null || !mounted) return;

    XFile? file;
    String type = 'image';

    if (choice == 'photo_gallery') {
      file = await picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 80,
      );
    } else if (choice == 'photo_camera') {
      file = await picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 80,
      );
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
    final currentUserId = authState is AuthAuthenticated
        ? authState.user.id
        : -1;
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
                if (state is MessagesLoaded ||
                    state is MessageSending ||
                    state is ContactSharingBlocked) {
                  _scrollToBottom();
                }
              },
              builder: (context, state) {
                final messages = switch (state) {
                  MessagesLoaded(:final messages) => messages,
                  MessageSending(:final messages) => messages,
                  ContactSharingBlocked(:final messages) => messages,
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

                final listView = ListView.builder(
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

                if (state is ContactSharingBlocked) {
                  return Column(
                    children: [
                      Expanded(child: listView),
                      const _ContactBlockedBanner(),
                    ],
                  );
                }

                return listView;
              },
            ),
          ),
          // Input bar — hidden when conversation is closed
          if (!isClosed)
            _isRecording
                ? _RecordingBar(
                    duration: _recordingDuration,
                    onCancel: _cancelRecording,
                    onSend: _stopAndSendRecording,
                  )
                : _InputBar(
                    controller: _controller,
                    sending: _sending,
                    onSend: _send,
                    onPickMedia: _pickMedia,
                    onStartRecording: _startRecording,
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
        parts.add('${dt.day} ${months[dt.month - 1]}');
      }
    }
    return parts.join(' · ');
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: _card,
        border: Border(bottom: BorderSide(color: _border)),
      ),
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
                      color: _mutedFg,
                    ),
                  ),
              ],
            ),
          ),
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: _border,
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(
              Icons.info_outline,
              color: Colors.white70,
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
      decoration: const BoxDecoration(
        color: _muted,
        border: Border(bottom: BorderSide(color: _border)),
      ),
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

class _ClosedBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: _muted,
        border: Border(bottom: BorderSide(color: _border)),
      ),
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

// ── Contact sharing blocked banner ────────────────────────────────
class _ContactBlockedBanner extends StatelessWidget {
  const _ContactBlockedBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: _warning.withValues(alpha: 0.10),
        border: Border(
          top: BorderSide(color: _warning.withValues(alpha: 0.35)),
        ),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 1),
            child: Icon(
              Icons.warning_amber_rounded,
              size: 18,
              color: _warning,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Message non envoyé — coordonnées détectées',
                  style: GoogleFonts.manrope(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: _warning,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  'BookMi ne permet pas l\'échange de coordonnées directes. Les paiements et communications doivent rester sur la plateforme.',
                  style: GoogleFonts.manrope(
                    fontSize: 12,
                    color: const Color(0xFFF59E0B),
                    height: 1.4,
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
    if (message.type == 'audio') {
      return _AudioMessage(message: message, isMine: isMine);
    }

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Align(
        alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
        child: Column(
          crossAxisAlignment: isMine
              ? CrossAxisAlignment.end
              : CrossAxisAlignment.start,
          children: [
            Container(
              constraints: BoxConstraints(
                maxWidth: MediaQuery.of(context).size.width * 0.72,
              ),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: isMine ? _primary : _card,
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(16),
                  topRight: const Radius.circular(16),
                  bottomLeft: Radius.circular(isMine ? 16 : 0),
                  bottomRight: Radius.circular(isMine ? 0 : 16),
                ),
                border: isMine ? null : Border.all(color: _border),
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
                      color: Colors.white,
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
          crossAxisAlignment: isMine
              ? CrossAxisAlignment.end
              : CrossAxisAlignment.start,
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
                        child: const Icon(
                          Icons.broken_image_outlined,
                          color: _mutedFg,
                        ),
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
            color: _card,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: _border),
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
                  padding: const EdgeInsets.symmetric(
                    horizontal: 6,
                    vertical: 2,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.black54,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.videocam_outlined,
                        size: 12,
                        color: Colors.white,
                      ),
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

// ── Audio message ─────────────────────────────────────────────────
class _AudioMessage extends StatelessWidget {
  const _AudioMessage({required this.message, required this.isMine});
  final MessageModel message;
  final bool isMine;

  String _formatTime(DateTime? dt) {
    if (dt == null) return '';
    return '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Align(
        alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
        child: Column(
          crossAxisAlignment: isMine
              ? CrossAxisAlignment.end
              : CrossAxisAlignment.start,
          children: [
            Container(
              constraints: BoxConstraints(
                maxWidth: MediaQuery.of(context).size.width * 0.72,
                minWidth: 180,
              ),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: isMine ? _primary : _card,
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(16),
                  topRight: const Radius.circular(16),
                  bottomLeft: Radius.circular(isMine ? 16 : 0),
                  bottomRight: Radius.circular(isMine ? 0 : 16),
                ),
                border: isMine ? null : Border.all(color: _border),
              ),
              child: _AudioPlayerBubble(
                audioUrl: message.mediaUrl ?? '',
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

// ── Audio player bubble ───────────────────────────────────────────
class _AudioPlayerBubble extends StatefulWidget {
  const _AudioPlayerBubble({required this.audioUrl});
  final String audioUrl;

  @override
  State<_AudioPlayerBubble> createState() => _AudioPlayerBubbleState();
}

class _AudioPlayerBubbleState extends State<_AudioPlayerBubble> {
  late final AudioPlayer _player;
  bool _isPlaying = false;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _player = AudioPlayer();
    _player.playerStateStream.listen((state) {
      if (mounted) {
        setState(() {
          _isPlaying = state.playing;
          _isLoading =
              state.processingState == ProcessingState.loading ||
              state.processingState == ProcessingState.buffering;
        });
      }
    });
    _player.playbackEventStream.listen(
      (_) {},
      onError: (Object e) {
        // Silently swallow playback errors — UI will handle via state
      },
    );
  }

  @override
  void dispose() {
    _player.dispose();
    super.dispose();
  }

  Future<void> _togglePlayback() async {
    if (_isPlaying) {
      await _player.pause();
    } else {
      if (_player.audioSource == null && widget.audioUrl.isNotEmpty) {
        await _player.setUrl(widget.audioUrl);
      }
      await _player.play();
    }
  }

  String _formatDuration(Duration d) {
    final m = d.inMinutes.toString().padLeft(2, '0');
    final s = (d.inSeconds % 60).toString().padLeft(2, '0');
    return '$m:$s';
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Play/pause button
        SizedBox(
          width: 36,
          height: 36,
          child: _isLoading
              ? const Center(
                  child: SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white70,
                    ),
                  ),
                )
              : IconButton(
                  padding: EdgeInsets.zero,
                  icon: Icon(
                    _isPlaying ? Icons.pause : Icons.play_arrow,
                    color: Colors.white,
                    size: 24,
                  ),
                  onPressed: widget.audioUrl.isNotEmpty
                      ? _togglePlayback
                      : null,
                ),
        ),
        const SizedBox(width: 4),
        // Waveform placeholder + duration
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              // Waveform placeholder (static bars)
              Row(
                children: List.generate(18, (i) {
                  final heights = [
                    3.0,
                    6.0,
                    10.0,
                    14.0,
                    8.0,
                    12.0,
                    16.0,
                    10.0,
                    6.0,
                    12.0,
                    16.0,
                    8.0,
                    14.0,
                    10.0,
                    6.0,
                    12.0,
                    8.0,
                    4.0,
                  ];
                  return Expanded(
                    child: Container(
                      margin: const EdgeInsets.symmetric(horizontal: 1),
                      height: heights[i],
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.5),
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  );
                }),
              ),
              const SizedBox(height: 4),
              // Duration
              StreamBuilder<Duration?>(
                stream: _player.durationStream,
                builder: (context, snapshot) {
                  final duration = snapshot.data ?? Duration.zero;
                  return StreamBuilder<Duration>(
                    stream: _player.positionStream,
                    builder: (context, posSnapshot) {
                      final position = posSnapshot.data ?? Duration.zero;
                      final display = _isPlaying || position > Duration.zero
                          ? position
                          : duration;
                      return Text(
                        _formatDuration(display),
                        style: GoogleFonts.manrope(
                          color: Colors.white70,
                          fontSize: 11,
                        ),
                      );
                    },
                  );
                },
              ),
            ],
          ),
        ),
      ],
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
    required this.onStartRecording,
  });

  final TextEditingController controller;
  final bool sending;
  final VoidCallback onSend;
  final VoidCallback onPickMedia;
  final VoidCallback onStartRecording;

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
        color: const Color(0xFF0D1B38),
        border: const Border(top: BorderSide(color: _border)),
        boxShadow: [
          BoxShadow(
            color: const Color(0x08FFFFFF),
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
                      cursorColor: Colors.white,
                      style: GoogleFonts.manrope(
                        fontSize: 14,
                        color: Colors.white,
                      ),
                      maxLines: 4,
                      minLines: 1,
                      textCapitalization: TextCapitalization.sentences,
                      decoration: InputDecoration(
                        hintText: 'Écrire un message…',
                        hintStyle: GoogleFonts.manrope(
                          fontSize: 14,
                          color: _mutedFg,
                        ),
                        // Override theme's filled:true to prevent light fillColor
                        filled: true,
                        fillColor: Colors.transparent,
                        border: InputBorder.none,
                        enabledBorder: InputBorder.none,
                        focusedBorder: InputBorder.none,
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(
                          vertical: 10,
                        ),
                      ),
                      onSubmitted: (_) => onSend(),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          // Send or mic button
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
                      if (hasText) {
                        // Show send button when typing
                        return GestureDetector(
                          key: const ValueKey('send'),
                          onTap: onSend,
                          child: Container(
                            width: 42,
                            height: 42,
                            decoration: BoxDecoration(
                              color: _primary,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(
                              Icons.send_rounded,
                              color: Colors.white,
                              size: 20,
                            ),
                          ),
                        );
                      }
                      // Show mic button when text field is empty
                      return GestureDetector(
                        key: const ValueKey('mic'),
                        onTap: onStartRecording,
                        child: Container(
                          width: 42,
                          height: 42,
                          decoration: BoxDecoration(
                            color: _muted,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: _border),
                          ),
                          child: const Icon(
                            Icons.mic_none_outlined,
                            color: Colors.white70,
                            size: 22,
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

// ── Recording bar ─────────────────────────────────────────────────
class _RecordingBar extends StatefulWidget {
  const _RecordingBar({
    required this.duration,
    required this.onCancel,
    required this.onSend,
  });

  final Duration duration;
  final VoidCallback onCancel;
  final VoidCallback onSend;

  @override
  State<_RecordingBar> createState() => _RecordingBarState();
}

class _RecordingBarState extends State<_RecordingBar>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;
  late final Animation<double> _pulseAnim;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    )..repeat(reverse: true);
    _pulseAnim = Tween<double>(begin: 0.4, end: 1.0).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  String _formatDuration(Duration d) {
    final m = d.inMinutes.toString().padLeft(2, '0');
    final s = (d.inSeconds % 60).toString().padLeft(2, '0');
    return '$m:$s';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        left: 16,
        right: 12,
        top: 10,
        bottom: MediaQuery.of(context).padding.bottom + 10,
      ),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        border: const Border(top: BorderSide(color: _border)),
        boxShadow: [
          BoxShadow(
            color: const Color(0x08FFFFFF),
            blurRadius: 8,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Pulsing red dot
          FadeTransition(
            opacity: _pulseAnim,
            child: Container(
              width: 12,
              height: 12,
              decoration: const BoxDecoration(
                color: Colors.red,
                shape: BoxShape.circle,
              ),
            ),
          ),
          const SizedBox(width: 10),
          // Duration counter
          Text(
            _formatDuration(widget.duration),
            style: GoogleFonts.manrope(
              color: Colors.white,
              fontWeight: FontWeight.w600,
              fontSize: 15,
            ),
          ),
          const Spacer(),
          // Cancel button
          TextButton(
            onPressed: widget.onCancel,
            child: Text(
              'Annuler',
              style: GoogleFonts.manrope(color: Colors.white54),
            ),
          ),
          const SizedBox(width: 8),
          // Send button
          GestureDetector(
            onTap: widget.onSend,
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: const BoxDecoration(
                color: Color(0xFF2196F3),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.send, color: Colors.white, size: 20),
            ),
          ),
        ],
      ),
    );
  }
}
