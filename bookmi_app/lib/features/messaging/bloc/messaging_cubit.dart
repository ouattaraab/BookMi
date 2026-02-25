import 'dart:io';

import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_state.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';
import 'package:bookmi_app/features/messaging/data/repositories/messaging_repository.dart';
import 'package:bookmi_app/features/notifications/data/models/push_notification_model.dart';

class MessagingCubit extends Cubit<MessagingState> {
  MessagingCubit({required MessagingRepository repository})
    : _repository = repository,
      super(const MessagingInitial());

  final MessagingRepository _repository;

  /// Cache the last successful conversations load so we can restore it
  /// instantly when returning from ChatPage (which emits MessagesLoaded).
  ConversationsLoaded? _conversationsCache;

  /// Load conversations + admin broadcasts in parallel, merge and emit.
  Future<void> loadConversations() async {
    emit(const MessagingLoading());

    final convFuture = _repository.getConversations();
    final broadFuture = _repository.getAdminBroadcasts();

    final convResult = await convFuture;
    final broadResult = await broadFuture;

    switch (convResult) {
      case ApiFailure(:final message):
        emit(MessagingError(message));
        return;
      case ApiSuccess(:final data):
        final broadcasts =
            broadResult is ApiSuccess<List<PushNotificationModel>>
            ? broadResult.data
            : <PushNotificationModel>[];
        final loaded = ConversationsLoaded(
          conversations: data,
          broadcasts: broadcasts,
        );
        _conversationsCache = loaded;
        emit(loaded);
    }
  }

  /// Load messages for a conversation and mark them as read.
  Future<void> loadMessages(int conversationId) async {
    emit(const MessagingLoading());
    switch (await _repository.getMessages(conversationId)) {
      case ApiFailure(:final message):
        emit(MessagingError(message));
      case ApiSuccess(:final data):
        // Reverse to show oldest first (API returns newest first)
        final ordered = data.reversed.toList();
        emit(MessagesLoaded(conversationId: conversationId, messages: ordered));
        // Mark as read in background
        unawaited(_repository.markAsRead(conversationId));
    }
  }

  /// Send a text message in a conversation.
  Future<void> sendMessage(int conversationId, String content) async {
    final currentMessages = _currentMessages();
    emit(
      MessageSending(conversationId: conversationId, messages: currentMessages),
    );

    switch (await _repository.sendMessage(conversationId, content: content)) {
      case ApiFailure(:final message):
        emit(
          MessagesLoaded(
            conversationId: conversationId,
            messages: currentMessages,
          ),
        );
        addError(Exception(message));
      case ApiSuccess(:final data):
        emit(
          MessagesLoaded(
            conversationId: conversationId,
            messages: [...currentMessages, data],
          ),
        );
    }
  }

  /// Send a media file (image or video) in a conversation.
  Future<void> sendMediaMessage(
    int conversationId, {
    required File file,
    required String type, // 'image' or 'video'
    String caption = '',
  }) async {
    final currentMessages = _currentMessages();
    emit(
      MessageSending(conversationId: conversationId, messages: currentMessages),
    );

    switch (await _repository.sendMediaMessage(
      conversationId,
      file: file,
      type: type,
      caption: caption,
    )) {
      case ApiFailure(:final message):
        emit(
          MessagesLoaded(
            conversationId: conversationId,
            messages: currentMessages,
          ),
        );
        addError(Exception(message));
      case ApiSuccess(:final data):
        emit(
          MessagesLoaded(
            conversationId: conversationId,
            messages: [...currentMessages, data],
          ),
        );
    }
  }

  /// Restore the last ConversationsLoaded from cache (no network call).
  /// Called when returning from ChatPage.
  void restoreConversationsIfNeeded() {
    if (state is ConversationsLoaded) return;
    final cache = _conversationsCache;
    if (cache != null) emit(cache);
  }

  /// Delete an entire conversation optimistically then call the API.
  Future<void> deleteConversation(int conversationId) async {
    final s = _conversationsCache ?? state;
    if (s is! ConversationsLoaded) return;
    final updated = ConversationsLoaded(
      conversations: s.conversations
          .where((c) => c.id != conversationId)
          .toList(),
      broadcasts: s.broadcasts,
    );
    _conversationsCache = updated;
    emit(updated);
    unawaited(_repository.deleteConversation(conversationId));
  }

  /// Remove an admin broadcast from the local list (no backend persistence needed).
  void removeBroadcast(int broadcastId) {
    final s = _conversationsCache ?? state;
    if (s is! ConversationsLoaded) return;
    final updated = ConversationsLoaded(
      conversations: s.conversations,
      broadcasts: s.broadcasts.where((b) => b.id != broadcastId).toList(),
    );
    _conversationsCache = updated;
    emit(updated);
  }

  /// Delete a single message optimistically then call the API.
  Future<void> deleteMessage(int conversationId, int messageId) async {
    final updated = _currentMessages().where((m) => m.id != messageId).toList();
    emit(MessagesLoaded(conversationId: conversationId, messages: updated));
    unawaited(_repository.deleteMessage(conversationId, messageId));
  }

  List<MessageModel> _currentMessages() {
    return switch (state) {
      MessagesLoaded(:final messages) => messages,
      MessageSending(:final messages) => messages,
      _ => <MessageModel>[],
    };
  }
}

/// Suppress lint for fire-and-forget async calls.
void unawaited(Future<void> future) {}
