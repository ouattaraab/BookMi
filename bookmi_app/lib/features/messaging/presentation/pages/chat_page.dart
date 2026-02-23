import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_cubit.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_state.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';

// ── Design tokens ─────────────────────────────────────────────────
const _primary = Color(0xFF3B9DF2);
const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _border = Color(0xFFE2E8F0);
const _success = Color(0xFF14B8A6);
const _warning = Color(0xFFFBBF24);

class ChatPage extends StatefulWidget {
  const ChatPage({
    super.key,
    required this.conversationId,
    required this.otherPartyName,
  });

  final int conversationId;
  final String otherPartyName;

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

  String get _initials {
    final parts = widget.otherPartyName.split(' ');
    if (parts.length >= 2) return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    return widget.otherPartyName.isNotEmpty ? widget.otherPartyName[0].toUpperCase() : '?';
  }

  @override
  Widget build(BuildContext context) {
    // Get current user id from auth state
    final authState = context.read<AuthBloc>().state;
    final currentUserId = authState is AuthAuthenticated ? authState.user.id : -1;

    return Scaffold(
      backgroundColor: _muted,
      body: Column(
        children: [
          _ChatHeader(
            otherPartyName: widget.otherPartyName,
            initials: _initials,
          ),
          // Safety banner
          _SafetyBanner(),
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
                        const Icon(Icons.chat_bubble_outline, size: 48, color: _border),
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
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  itemCount: messages.length,
                  itemBuilder: (context, i) {
                    final msg = messages[i];
                    final isMine = msg.senderId == currentUserId;

                    // Show date separator at top
                    final showDateSep = i == 0;

                    return Column(
                      children: [
                        if (showDateSep) _DateSeparator(label: "Aujourd'hui"),
                        // Show sequestre banner in middle of messages
                        if (i == messages.length ~/ 2) _SequestreBanner(),
                        _ChatBubble(message: msg, isMine: isMine),
                      ],
                    );
                  },
                );
              },
            ),
          ),
          _InputBar(
            controller: _controller,
            sending: _sending,
            onSend: _send,
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
  });

  final String otherPartyName;
  final String initials;

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
          // Back button
          IconButton(
            icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white, size: 18),
            onPressed: () => Navigator.of(context).pop(),
          ),
          // Avatar
          Stack(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [_primary, Color(0xFF1565C0)],
                  ),
                  borderRadius: BorderRadius.circular(12),
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
              ),
              // Online indicator
              Positioned(
                bottom: 0,
                right: 0,
                child: Container(
                  width: 12,
                  height: 12,
                  decoration: BoxDecoration(
                    color: _success,
                    shape: BoxShape.circle,
                    border: Border.all(color: _secondary, width: 2),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(width: 10),
          // Name & event
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
                Text(
                  'En ligne · Réservation #BM-2025',
                  style: GoogleFonts.manrope(
                    fontSize: 11,
                    color: Colors.white.withValues(alpha: 0.6),
                  ),
                ),
              ],
            ),
          ),
          // Info button
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.info_outline, color: Colors.white, size: 18),
          ),
        ],
      ),
    );
  }
}

// ── Safety banner ─────────────────────────────────────────────────
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

// ── Sequestre banner ──────────────────────────────────────────────
class _SequestreBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(vertical: 8),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: _secondary,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Icon(Icons.lock_outline, color: Colors.white, size: 16),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Paiement Séquestre activé · 50 000 FCFA sécurisé',
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: Colors.white,
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
      return _FlaggedMessage(message: message, isMine: isMine);
    }

    if (message.type == 'audio') {
      return _AudioMessage(message: message, isMine: isMine);
    }

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Align(
        alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
        child: Column(
          crossAxisAlignment: isMine ? CrossAxisAlignment.end : CrossAxisAlignment.start,
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
              style: GoogleFonts.manrope(
                fontSize: 10,
                color: _mutedFg,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Flagged message warning ───────────────────────────────────────
class _FlaggedMessage extends StatelessWidget {
  const _FlaggedMessage({required this.message, required this.isMine});
  final MessageModel message;
  final bool isMine;

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

// ── Audio message ─────────────────────────────────────────────────
class _AudioMessage extends StatelessWidget {
  const _AudioMessage({required this.message, required this.isMine});
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
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            color: isMine ? _primary : Colors.white,
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.06),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Row(
            children: [
              Icon(
                Icons.play_circle_filled,
                color: isMine ? Colors.white : _primary,
                size: 32,
              ),
              const SizedBox(width: 10),
              // Simulated waveform
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: List.generate(16, (i) {
                        final heights = [6.0, 12.0, 8.0, 16.0, 10.0, 14.0, 6.0, 18.0,
                                         10.0, 14.0, 8.0, 12.0, 16.0, 6.0, 10.0, 8.0];
                        return Container(
                          width: 3,
                          height: heights[i % heights.length],
                          decoration: BoxDecoration(
                            color: isMine
                                ? Colors.white.withValues(alpha: 0.8)
                                : _primary.withValues(alpha: 0.6),
                            borderRadius: BorderRadius.circular(2),
                          ),
                        );
                      }),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '0:24',
                      style: GoogleFonts.manrope(
                        fontSize: 10,
                        color: isMine ? Colors.white70 : _mutedFg,
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
}

// ── Input bar ─────────────────────────────────────────────────────
class _InputBar extends StatelessWidget {
  const _InputBar({
    required this.controller,
    required this.sending,
    required this.onSend,
  });

  final TextEditingController controller;
  final bool sending;
  final VoidCallback onSend;

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
          // Attach button
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: _muted,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: _border),
            ),
            child: const Icon(Icons.add, color: _mutedFg, size: 20),
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
                      style: GoogleFonts.manrope(
                        fontSize: 14,
                        color: _secondary,
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
                        border: InputBorder.none,
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(vertical: 10),
                      ),
                      onSubmitted: (_) => onSend(),
                    ),
                  ),
                  // Emoji button
                  IconButton(
                    icon: const Icon(Icons.emoji_emotions_outlined, color: _mutedFg, size: 20),
                    onPressed: () {},
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          // Send/mic button
          AnimatedSwitcher(
            duration: const Duration(milliseconds: 200),
            child: sending
                ? const SizedBox(
                    key: ValueKey('loading'),
                    width: 42,
                    height: 42,
                    child: CircularProgressIndicator(strokeWidth: 2, color: _primary),
                  )
                : ValueListenableBuilder<TextEditingValue>(
                    valueListenable: controller,
                    builder: (context, value, _) {
                      final hasText = value.text.isNotEmpty;
                      return GestureDetector(
                        key: const ValueKey('send_mic'),
                        onTap: hasText ? onSend : null,
                        child: Container(
                          width: 42,
                          height: 42,
                          decoration: BoxDecoration(
                            color: _primary,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(
                            hasText ? Icons.send_rounded : Icons.mic_outlined,
                            color: Colors.white,
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
