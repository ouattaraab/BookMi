import 'package:bookmi_app/features/messaging/data/models/conversation_model.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';
import 'package:bookmi_app/features/notifications/data/models/push_notification_model.dart';

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
  const ConversationsLoaded({
    required this.conversations,
    this.broadcasts = const [],
  });
  final List<ConversationModel> conversations;
  final List<PushNotificationModel> broadcasts;
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
