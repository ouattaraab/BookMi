class PushNotificationModel {
  const PushNotificationModel({
    required this.id,
    required this.title,
    required this.body,
    required this.createdAt,
    this.data,
    this.readAt,
  });

  final int id;
  final String title;
  final String body;
  final Map<String, dynamic>? data;
  final DateTime? readAt;
  final DateTime createdAt;

  bool get isUnread => readAt == null;

  factory PushNotificationModel.fromJson(Map<String, dynamic> json) {
    final attrs = json['attributes'] as Map<String, dynamic>? ?? json;
    return PushNotificationModel(
      id: json['id'] as int? ?? 0,
      title: attrs['title'] as String? ?? '',
      body: attrs['body'] as String? ?? '',
      data: attrs['data'] as Map<String, dynamic>?,
      readAt: attrs['read_at'] != null
          ? DateTime.tryParse(attrs['read_at'] as String)
          : null,
      createdAt:
          DateTime.tryParse(attrs['created_at'] as String? ?? '') ??
          DateTime.now(),
    );
  }
}
