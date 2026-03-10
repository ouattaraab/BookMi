import 'dart:async';

import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/manager/data/repositories/manager_repository.dart';
import 'package:bookmi_app/features/manager/presentation/pages/manager_chat_page.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

class ManagerConversationsPage extends StatefulWidget {
  const ManagerConversationsPage({required this.repo, super.key});

  final ManagerRepository repo;

  @override
  State<ManagerConversationsPage> createState() =>
      _ManagerConversationsPageState();
}

class _ManagerConversationsPageState extends State<ManagerConversationsPage> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _conversations = [];

  @override
  void initState() {
    super.initState();
    unawaited(_load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await widget.repo.getManagerConversations();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _conversations = data;
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
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.white,
        leading: const BackButton(color: Color(0xFF1A1A2E)),
        title: Text(
          'Messages',
          style: GoogleFonts.manrope(
            color: const Color(0xFF1A1A2E),
            fontWeight: FontWeight.w700,
            fontSize: 17,
          ),
        ),
        actions: [
          IconButton(
            onPressed: _load,
            icon: const Icon(Icons.refresh, color: Color(0xFF6C5ECF)),
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF6C5ECF)),
      );
    }
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
    if (_conversations.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.chat_bubble_outline,
              size: 56,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 12),
            Text(
              'Aucune conversation',
              style: GoogleFonts.manrope(
                fontSize: 15,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: _load,
      color: const Color(0xFF6C5ECF),
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _conversations.length,
        separatorBuilder: (context, index) => const SizedBox(height: 10),
        itemBuilder: (context, i) {
          final conv = _conversations[i];
          return _ConversationCard(
            conversation: conv,
            onTap: () async {
              await Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => ManagerChatPage(
                    conversation: conv,
                    repo: widget.repo,
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class _ConversationCard extends StatelessWidget {
  const _ConversationCard({
    required this.conversation,
    required this.onTap,
  });

  final Map<String, dynamic> conversation;
  final VoidCallback onTap;

  String _formatTime(String? rawDate) {
    if (rawDate == null) return '';
    final dt = DateTime.tryParse(rawDate);
    if (dt == null) return '';
    final now = DateTime.now();
    final diff = now.difference(dt);
    if (diff.inDays >= 1) {
      return DateFormat('dd/MM/yy').format(dt);
    }
    return DateFormat('HH:mm').format(dt);
  }

  @override
  Widget build(BuildContext context) {
    final client = conversation['client'] as Map<String, dynamic>? ?? {};
    final talent = conversation['talent'] as Map<String, dynamic>? ?? {};
    final latestMessage =
        conversation['latest_message'] as Map<String, dynamic>?;
    final unreadCount = (conversation['unread_count'] as int?) ?? 0;

    final clientFirstName = (client['first_name'] as String?) ?? '';
    final clientLastName = (client['last_name'] as String?) ?? '';
    final clientName = '$clientFirstName $clientLastName'.trim();
    final clientInitial =
        clientName.isNotEmpty ? clientName[0].toUpperCase() : '?';

    final talentStageName =
        (talent['stage_name'] as String?) ??
        ((talent['user'] as Map<String, dynamic>?)?['first_name'] as String? ??
            '');

    final lastMessageContent =
        (latestMessage?['content'] as String?) ?? '';
    final lastMessageAt =
        conversation['last_message_at'] as String?;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            // Client avatar
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: const Color(0xFF1A2744),
                borderRadius: BorderRadius.circular(24),
              ),
              child: Center(
                child: Text(
                  clientInitial,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    fontSize: 18,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 14),
            // Text content
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          clientName.isEmpty ? 'Client' : clientName,
                          style: GoogleFonts.manrope(
                            fontWeight: FontWeight.w700,
                            fontSize: 15,
                            color: const Color(0xFF1A1A2E),
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        _formatTime(lastMessageAt),
                        style: GoogleFonts.manrope(
                          fontSize: 11,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  if (talentStageName.isNotEmpty)
                    Text(
                      'Talent : $talentStageName',
                      style: GoogleFonts.manrope(
                        fontSize: 12,
                        color: const Color(0xFF6C5ECF),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          lastMessageContent.isEmpty
                              ? 'Aucun message'
                              : lastMessageContent,
                          style: GoogleFonts.manrope(
                            fontSize: 13,
                            color: Colors.grey.shade600,
                            fontWeight: unreadCount > 0
                                ? FontWeight.w600
                                : FontWeight.normal,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (unreadCount > 0) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 7,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.red,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '$unreadCount',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            const Icon(Icons.chevron_right, color: Colors.grey),
          ],
        ),
      ),
    );
  }
}
