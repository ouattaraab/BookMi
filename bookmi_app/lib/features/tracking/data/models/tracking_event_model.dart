class TrackingEventModel {
  const TrackingEventModel({
    required this.id,
    required this.bookingRequestId,
    required this.status,
    required this.statusLabel,
    this.latitude,
    this.longitude,
    this.occurredAt,
  });

  factory TrackingEventModel.fromJson(Map<String, dynamic> json) {
    return TrackingEventModel(
      id: json['id'] as int,
      bookingRequestId: json['booking_request_id'] as int,
      status: json['status'] as String,
      statusLabel: json['status_label'] as String? ?? json['status'] as String,
      latitude: (json['latitude'] as num?)?.toDouble(),
      longitude: (json['longitude'] as num?)?.toDouble(),
      occurredAt: json['occurred_at'] != null
          ? DateTime.tryParse(json['occurred_at'] as String)
          : null,
    );
  }

  final int id;
  final int bookingRequestId;
  final String status;
  final String statusLabel;
  final double? latitude;
  final double? longitude;
  final DateTime? occurredAt;

  bool get isCompleted => status == 'completed';
  bool get isArrived => status == 'arrived';
}
