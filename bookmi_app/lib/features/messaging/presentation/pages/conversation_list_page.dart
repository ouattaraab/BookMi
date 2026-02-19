import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_cubit.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_state.dart';
import 'package:bookmi_app/features/messaging/data/models/conversation_model.dart';
import 'package:bookmi_app/features/messaging/presentation/pages/chat_page.dart';

class ConversationListPage extends StatefulWidget {
  const ConversationListPage({super.key});

  @override
  State<ConversationListPage> createState() => _ConversationListPageState();
}

class _ConversationListPageState extends State<ConversationListPage> {
  @override
  void initState() {
    super.initState();
    // ignore: discarded_futures
    context.read<MessagingCubit>().loadConversations();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BookmiColors.brandNavy,
      appBar: AppBar(
        backgroundColor: BookmiColors.brandNavy,
        title: const Text(
          'Messages',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        centerTitle: true,
        elevation: 0,
      ),
      body: BlocBuilder<MessagingCubit, MessagingState>(
        builder: (context, state) {
          return switch (state) {
            MessagingLoading() => const Center(
                child: CircularProgressIndicator(color: BookmiColors.brandBlue),
              ),
            MessagingError(:final message) => Center(
                child: Text(
                  message,
                  style: const TextStyle(color: Colors.white70),
                  textAlign: TextAlign.center,
                ),
              ),
            ConversationsLoaded(:final conversations) =>
              conversations.isEmpty
                  ? const Center(
                      child: Text(
                        'Aucune conversation',
                        style: TextStyle(color: Colors.white54),
                      ),
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      itemCount: conversations.length,
                      separatorBuilder: (_, __) => const Divider(
                        color: BookmiColors.glassBorder,
                        height: 1,
                        indent: 72,
                      ),
                      itemBuilder: (context, i) =>
                          _ConversationTile(conversation: conversations[i]),
                    ),
            _ => const SizedBox.shrink(),
          };
        },
      ),
    );
  }
}

class _ConversationTile extends StatelessWidget {
  const _ConversationTile({required this.conversation});

  final ConversationModel conversation;

  String get _otherName =>
      conversation.talentName ?? conversation.clientName ?? 'Inconnu';

  String get _preview =>
      conversation.latestMessage?.content ?? 'Commencer la conversation…';

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(
        backgroundColor: BookmiColors.brandBlue.withAlpha(51),
        child: Text(
          _otherName.isNotEmpty ? _otherName[0].toUpperCase() : '?',
          style: const TextStyle(
            color: BookmiColors.brandBlueLight,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      title: Text(
        _otherName,
        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
      ),
      subtitle: Text(
        _preview,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(color: Colors.white54, fontSize: 13),
      ),
      trailing: conversation.latestMessage?.isFlagged == true
          ? const Icon(Icons.warning_amber_rounded, color: BookmiColors.warning, size: 18)
          : null,
      onTap: () {
        Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => BlocProvider.value(
              value: context.read<MessagingCubit>(),
              child: ChatPage(
                conversationId: conversation.id,
                otherPartyName: _otherName,
              ),
            ),
          ),
        );
      },
    );
  }
}
