class MessageModel {
  const MessageModel({
    required this.id,
    required this.conversationId,
    required this.senderId,
    required this.content,
    required this.type,
    required this.isFlagged,
    required this.isAutoReply,
    this.senderName,
    this.readAt,
    this.createdAt,
  });

  factory MessageModel.fromJson(Map<String, dynamic> json) {
    return MessageModel(
      id: json['id'] as int,
      conversationId: json['conversation_id'] as int,
      senderId: json['sender_id'] as int,
      content: json['content'] as String,
      type: json['type'] as String? ?? 'text',
      isFlagged: (json['is_flagged'] as bool?) ?? false,
      isAutoReply: (json['is_auto_reply'] as bool?) ?? false,
      senderName: json['sender_name'] as String?,
      readAt: json['read_at'] != null
          ? DateTime.tryParse(json['read_at'] as String)
          : null,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
    );
  }

  final int id;
  final int conversationId;
  final int senderId;
  final String content;
  final String type;
  final bool isFlagged;
  final bool isAutoReply;
  final String? senderName;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isRead => readAt != null;
}
