import 'package:bookmi_app/features/messaging/data/models/conversation_model.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';

sealed class MessagingState {
  const MessagingState();
}

final class MessagingInitial extends MessagingState {
  const MessagingInitial();
}

final class MessagingLoading extends MessagingState {
  const MessagingLoading();
}

final class ConversationsLoaded extends MessagingState {
  const ConversationsLoaded(this.conversations);
  final List<ConversationModel> conversations;
}

final class MessagesLoaded extends MessagingState {
  const MessagesLoaded({
    required this.conversationId,
    required this.messages,
  });
  final int conversationId;
  final List<MessageModel> messages;
}

final class MessageSending extends MessagingState {
  const MessageSending({
    required this.conversationId,
    required this.messages,
  });
  final int conversationId;
  final List<MessageModel> messages;
}

final class MessagingError extends MessagingState {
  const MessagingError(this.message);
  final String message;
}
