import 'package:bookmi_app/features/messaging/data/models/message_model.dart';

class BookingInfo {
  const BookingInfo({
    required this.id,
    required this.status,
    required this.isClosed,
    this.title,
    this.eventDate,
    this.eventLocation,
  });

  final int id;
  final String status;
  final bool isClosed;
  final String? title;
  final String? eventDate; // "YYYY-MM-DD"
  final String? eventLocation;

  factory BookingInfo.fromJson(Map<String, dynamic> json) {
    return BookingInfo(
      id: json['id'] as int,
      status: json['status'] as String? ?? '',
      isClosed: json['is_closed'] as bool? ?? false,
      title: json['title'] as String?,
      eventDate: json['event_date'] as String?,
      eventLocation: json['event_location'] as String?,
    );
  }
}

class ConversationModel {
  const ConversationModel({
    required this.id,
    required this.clientId,
    required this.talentProfileId,
    this.bookingRequestId,
    this.lastMessageAt,
    this.clientName,
    this.clientAvatarUrl,
    this.talentName,
    this.talentAvatarUrl,
    this.latestMessage,
    this.booking,
    this.unreadCount = 0,
  });

  factory ConversationModel.fromJson(Map<String, dynamic> json) {
    final client = json['client'] as Map<String, dynamic>?;
    final talent = json['talent'] as Map<String, dynamic>?;
    final latestMsg = json['latest_message'] as Map<String, dynamic>?;
    final bookingJson = json['booking'] as Map<String, dynamic>?;

    return ConversationModel(
      id: json['id'] as int,
      clientId: json['client_id'] as int,
      talentProfileId: json['talent_profile_id'] as int,
      bookingRequestId: json['booking_request_id'] as int?,
      lastMessageAt: json['last_message_at'] != null
          ? DateTime.tryParse(json['last_message_at'] as String)
          : null,
      clientName: client?['name'] as String?,
      clientAvatarUrl: client?['avatar_url'] as String?,
      talentName: talent?['name'] as String?,
      talentAvatarUrl: talent?['avatar_url'] as String?,
      latestMessage:
          latestMsg != null ? MessageModel.fromJson(latestMsg) : null,
      booking: bookingJson != null ? BookingInfo.fromJson(bookingJson) : null,
      unreadCount: json['unread_count'] as int? ?? 0,
    );
  }

  final int id;
  final int clientId;
  final int talentProfileId;
  final int? bookingRequestId;
  final DateTime? lastMessageAt;
  final String? clientName;
  final String? clientAvatarUrl;
  final String? talentName;
  final String? talentAvatarUrl;
  final MessageModel? latestMessage;
  final BookingInfo? booking;
  final int unreadCount;

  bool get isClosed => booking?.isClosed ?? false;
}
