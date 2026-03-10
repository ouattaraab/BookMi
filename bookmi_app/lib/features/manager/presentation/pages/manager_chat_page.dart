import 'dart:async';

import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/manager/data/repositories/manager_repository.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

class ManagerChatPage extends StatefulWidget {
  const ManagerChatPage({
    required this.conversation,
    required this.repo,
    super.key,
  });

  final Map<String, dynamic> conversation;
  final ManagerRepository repo;

  @override
  State<ManagerChatPage> createState() => _ManagerChatPageState();
}

class _ManagerChatPageState extends State<ManagerChatPage> {
  final _textController = TextEditingController();
  final _scrollController = ScrollController();

  bool _loadingMessages = true;
  String? _messagesError;
  List<MessageModel> _messages = [];
  bool _sending = false;

  late final int _conversationId;
  late final Map<String, dynamic> _client;
  late final Map<String, dynamic> _talent;

  @override
  void initState() {
    super.initState();
    _conversationId = widget.conversation['id'] as int;
    _client = widget.conversation['client'] as Map<String, dynamic>? ?? {};
    _talent = widget.conversation['talent'] as Map<String, dynamic>? ?? {};
    unawaited(_loadMessages());
  }

  @override
  void dispose() {
    _textController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  String get _clientName {
    final first = (_client['first_name'] as String?) ?? '';
    final last = (_client['last_name'] as String?) ?? '';
    return '$first $last'.trim();
  }

  String get _talentName {
    return (_talent['stage_name'] as String?) ??
        ((_talent['user'] as Map<String, dynamic>?)?['first_name'] as String? ??
            'Talent');
  }

  int? get _clientId => _client['id'] as int?;

  Future<void> _loadMessages() async {
    setState(() {
      _loadingMessages = true;
      _messagesError = null;
    });

    try {
      final response = await ApiClient.instance.dio
          .get<Map<String, dynamic>>('/conversations/$_conversationId/messages');
      if (!mounted) return;
      final items = ((response.data?['data'] as List<dynamic>?) ?? [])
          .cast<Map<String, dynamic>>();
      setState(() {
        _messages = items.map(MessageModel.fromJson).toList();
        _loadingMessages = false;
      });
      _scrollToBottom();
    } on DioException catch (e) {
      if (!mounted) return;
      final errorData = e.response?.data as Map<String, dynamic>?;
      final error = errorData?['error'] as Map<String, dynamic>?;
      setState(() {
        _messagesError =
            (error?['message'] as String?) ?? e.message ?? 'Erreur réseau';
        _loadingMessages = false;
      });
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        unawaited(
          _scrollController.animateTo(
            _scrollController.position.maxScrollExtent,
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeOut,
          ),
        );
      }
    });
  }

  Future<void> _sendMessage() async {
    final content = _textController.text.trim();
    if (content.isEmpty || _sending) return;

    setState(() => _sending = true);
    _textController.clear();

    final result = await widget.repo.sendManagerMessage(
      _conversationId,
      content,
    );
    if (!mounted) return;

    switch (result) {
      case ApiSuccess():
        await _loadMessages();
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: Colors.red,
          ),
        );
    }

    if (mounted) setState(() => _sending = false);
  }

  bool _isMe(MessageModel message) {
    final cid = _clientId;
    if (cid == null) return false;
    return message.senderId != cid;
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
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Conversation – ${_clientName.isEmpty ? "Client" : _clientName}',
              style: GoogleFonts.manrope(
                color: const Color(0xFF1A1A2E),
                fontWeight: FontWeight.w700,
                fontSize: 15,
              ),
            ),
            Text(
              _talentName,
              style: GoogleFonts.manrope(
                color: const Color(0xFF6C5ECF),
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            onPressed: _loadMessages,
            icon: const Icon(Icons.refresh, color: Color(0xFF6C5ECF)),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(child: _buildMessageList()),
          _buildInputBar(),
        ],
      ),
    );
  }

  Widget _buildMessageList() {
    if (_loadingMessages) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF6C5ECF)),
      );
    }
    if (_messagesError != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              _messagesError!,
              style: GoogleFonts.manrope(color: Colors.red),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: _loadMessages,
              child: const Text('Réessayer'),
            ),
          ],
        ),
      );
    }
    if (_messages.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.chat_bubble_outline,
              size: 48,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 8),
            Text(
              'Aucun message',
              style: GoogleFonts.manrope(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      itemCount: _messages.length,
      itemBuilder: (context, i) {
        final msg = _messages[i];
        final isMe = _isMe(msg);
        return _ChatBubble(message: msg, isMe: isMe);
      },
    );
  }

  Widget _buildInputBar() {
    return Container(
      color: Colors.white,
      padding: EdgeInsets.only(
        left: 12,
        right: 8,
        top: 10,
        bottom: MediaQuery.of(context).viewInsets.bottom + 10,
      ),
      child: SafeArea(
        top: false,
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _textController,
                maxLines: 4,
                minLines: 1,
                textCapitalization: TextCapitalization.sentences,
                style: GoogleFonts.manrope(fontSize: 14),
                decoration: InputDecoration(
                  hintText: 'Envoyer un message au nom du talent…',
                  hintStyle: GoogleFonts.manrope(
                    fontSize: 13,
                    color: Colors.grey.shade400,
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  filled: true,
                  fillColor: const Color(0xFFF5F5F5),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(20),
                    borderSide: BorderSide.none,
                  ),
                ),
                onSubmitted: (_) => _sendMessage(),
              ),
            ),
            const SizedBox(width: 8),
            if (_sending)
              const SizedBox(
                width: 40,
                height: 40,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Color(0xFF6C5ECF),
                ),
              )
            else
              IconButton(
                onPressed: _sendMessage,
                icon: const Icon(
                  Icons.send_rounded,
                  color: Color(0xFF6C5ECF),
                ),
                tooltip: 'Envoyer',
              ),
          ],
        ),
      ),
    );
  }
}

class _ChatBubble extends StatelessWidget {
  const _ChatBubble({required this.message, required this.isMe});

  final MessageModel message;
  final bool isMe;

  @override
  Widget build(BuildContext context) {
    final time = message.createdAt != null
        ? DateFormat('HH:mm').format(message.createdAt!)
        : '';

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment:
            isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          if (!isMe) ...[
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: const Color(0xFF1A2744),
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Center(
                child: Icon(Icons.person, color: Colors.white, size: 16),
              ),
            ),
            const SizedBox(width: 8),
          ],
          Flexible(
            child: Column(
              crossAxisAlignment:
                  isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  constraints: BoxConstraints(
                    maxWidth: MediaQuery.of(context).size.width * 0.70,
                  ),
                  decoration: BoxDecoration(
                    color: isMe
                        ? const Color(0xFF6C5ECF)
                        : Colors.white,
                    borderRadius: BorderRadius.only(
                      topLeft: const Radius.circular(16),
                      topRight: const Radius.circular(16),
                      bottomLeft: Radius.circular(isMe ? 16 : 4),
                      bottomRight: Radius.circular(isMe ? 4 : 16),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.05),
                        blurRadius: 4,
                        offset: const Offset(0, 1),
                      ),
                    ],
                  ),
                  child: Text(
                    message.content,
                    style: GoogleFonts.manrope(
                      fontSize: 14,
                      color: isMe ? Colors.white : const Color(0xFF1A1A2E),
                    ),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  time,
                  style: GoogleFonts.manrope(
                    fontSize: 10,
                    color: Colors.grey.shade500,
                  ),
                ),
              ],
            ),
          ),
          if (isMe) const SizedBox(width: 36),
        ],
      ),
    );
  }
}
