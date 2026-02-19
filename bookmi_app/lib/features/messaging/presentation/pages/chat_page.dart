import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_cubit.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_state.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';

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
    // ignore: discarded_futures
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
    await context
        .read<MessagingCubit>()
        .sendMessage(widget.conversationId, text);
    if (mounted) setState(() => _sending = false);
    _scrollToBottom();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BookmiColors.brandNavy,
      appBar: AppBar(
        backgroundColor: BookmiColors.brandNavy,
        elevation: 0,
        title: Text(
          widget.otherPartyName,
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Column(
        children: [
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
                    child: CircularProgressIndicator(
                      color: BookmiColors.brandBlue,
                    ),
                  );
                }

                if (state is MessagingError) {
                  return Center(
                    child: Text(
                      state.message,
                      style: const TextStyle(color: Colors.white70),
                    ),
                  );
                }

                if (messages == null || messages.isEmpty) {
                  return const Center(
                    child: Text(
                      'Envoyez votre premier message',
                      style: TextStyle(color: Colors.white54),
                    ),
                  );
                }

                return ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  itemCount: messages.length,
                  itemBuilder: (context, i) => _ChatBubble(message: messages[i]),
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

/// Individual chat bubble widget.
class _ChatBubble extends StatelessWidget {
  const _ChatBubble({required this.message});

  final MessageModel message;

  /// Whether this message was sent by the current user.
  /// For simplicity we check is_auto_reply to mark system messages.
  bool get _isAutoReply => message.isAutoReply;
  bool get _isFlagged => message.isFlagged;

  @override
  Widget build(BuildContext context) {
    // Auto-reply messages are always from the talent (other side)
    // In a real app, we'd compare message.senderId with the current user's ID.
    // Here we use isFlagged/isAutoReply as visual cues only.

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (_isAutoReply)
            Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Row(
                children: [
                  const Icon(
                    Icons.smart_toy_outlined,
                    size: 14,
                    color: BookmiColors.brandBlueLight,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'Réponse automatique',
                    style: TextStyle(
                      color: BookmiColors.brandBlueLight,
                      fontSize: 11,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
              ),
            ),
          Container(
            constraints: BoxConstraints(
              maxWidth: MediaQuery.of(context).size.width * 0.75,
            ),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: _isFlagged
                  ? BookmiColors.warning.withAlpha(38)
                  : BookmiColors.glassWhite,
              borderRadius: BorderRadius.circular(16),
              border: _isFlagged
                  ? Border.all(color: BookmiColors.warning.withAlpha(128))
                  : null,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  message.content,
                  style: const TextStyle(color: Colors.white, fontSize: 15),
                ),
                if (_isFlagged)
                  Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Row(
                      children: [
                        Icon(
                          Icons.warning_amber_rounded,
                          size: 14,
                          color: BookmiColors.warning,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          'Coordonnées détectées',
                          style: TextStyle(
                            color: BookmiColors.warning,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          if (message.senderName != null)
            Padding(
              padding: const EdgeInsets.only(top: 2, left: 4),
              child: Text(
                message.senderName!,
                style: const TextStyle(color: Colors.white38, fontSize: 11),
              ),
            ),
        ],
      ),
    );
  }
}

/// Bottom input bar with text field and send button.
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
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 16),
      decoration: const BoxDecoration(
        color: BookmiColors.glassDark,
        border: Border(
          top: BorderSide(color: BookmiColors.glassBorder),
        ),
      ),
      child: SafeArea(
        top: false,
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: controller,
                style: const TextStyle(color: Colors.white),
                maxLines: 4,
                minLines: 1,
                textCapitalization: TextCapitalization.sentences,
                decoration: InputDecoration(
                  hintText: 'Écrire un message…',
                  hintStyle: const TextStyle(color: Colors.white38),
                  filled: true,
                  fillColor: BookmiColors.glassWhite,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 10,
                  ),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: BorderSide.none,
                  ),
                ),
                onSubmitted: (_) => onSend(),
              ),
            ),
            const SizedBox(width: 8),
            AnimatedSwitcher(
              duration: const Duration(milliseconds: 200),
              child: sending
                  ? const SizedBox(
                      key: ValueKey('loading'),
                      width: 44,
                      height: 44,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: BookmiColors.brandBlue,
                      ),
                    )
                  : IconButton(
                      key: const ValueKey('send'),
                      icon: const Icon(Icons.send_rounded),
                      color: BookmiColors.brandBlue,
                      iconSize: 28,
                      onPressed: onSend,
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
