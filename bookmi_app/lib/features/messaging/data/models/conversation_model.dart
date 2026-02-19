import 'package:bookmi_app/features/messaging/data/models/message_model.dart';

class ConversationModel {
  const ConversationModel({
    required this.id,
    required this.clientId,
    required this.talentProfileId,
    this.bookingRequestId,
    this.lastMessageAt,
    this.clientName,
    this.talentName,
    this.latestMessage,
  });

  factory ConversationModel.fromJson(Map<String, dynamic> json) {
    final client = json['client'] as Map<String, dynamic>?;
    final talent = json['talent'] as Map<String, dynamic>?;
    final latestMsg = json['latest_message'] as Map<String, dynamic>?;

    return ConversationModel(
      id: json['id'] as int,
      clientId: json['client_id'] as int,
      talentProfileId: json['talent_profile_id'] as int,
      bookingRequestId: json['booking_request_id'] as int?,
      lastMessageAt: json['last_message_at'] != null
          ? DateTime.tryParse(json['last_message_at'] as String)
          : null,
      clientName: client?['name'] as String?,
      talentName: talent?['name'] as String?,
      latestMessage:
          latestMsg != null ? MessageModel.fromJson(latestMsg) : null,
    );
  }

  final int id;
  final int clientId;
  final int talentProfileId;
  final int? bookingRequestId;
  final DateTime? lastMessageAt;
  final String? clientName;
  final String? talentName;
  final MessageModel? latestMessage;
}
