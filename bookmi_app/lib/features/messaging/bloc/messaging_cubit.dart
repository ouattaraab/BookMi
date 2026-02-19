import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_state.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';
import 'package:bookmi_app/features/messaging/data/repositories/messaging_repository.dart';

class MessagingCubit extends Cubit<MessagingState> {
  MessagingCubit({required MessagingRepository repository})
    : _repository = repository,
      super(const MessagingInitial());

  final MessagingRepository _repository;

  /// Load all conversations for the current user.
  Future<void> loadConversations() async {
    emit(const MessagingLoading());
    switch (await _repository.getConversations()) {
      case ApiFailure(:final message):
        emit(MessagingError(message));
      case ApiSuccess(:final data):
        emit(ConversationsLoaded(data));
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
    final currentMessages = state is MessagesLoaded
        ? (state as MessagesLoaded).messages
        : state is MessageSending
            ? (state as MessageSending).messages
            : <MessageModel>[];

    emit(MessageSending(conversationId: conversationId, messages: currentMessages));

    switch (await _repository.sendMessage(conversationId, content: content)) {
      case ApiFailure(:final message):
        // Revert to loaded state with existing messages on failure
        emit(MessagesLoaded(conversationId: conversationId, messages: currentMessages));
        addError(Exception(message));
      case ApiSuccess(:final data):
        final updated = [...currentMessages, data];
        emit(MessagesLoaded(conversationId: conversationId, messages: updated));
    }
  }
}

/// Suppress lint for fire-and-forget async calls.
void unawaited(Future<void> future) {}
