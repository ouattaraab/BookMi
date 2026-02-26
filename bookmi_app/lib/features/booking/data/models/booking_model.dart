import 'package:flutter/foundation.dart';

/// One entry in the booking status audit trail.
@immutable
class BookingStatusLog {
  const BookingStatusLog({
    required this.id,
    required this.toStatus,
    required this.createdAt,
    this.fromStatus,
    this.performedByName,
  });

  final int id;
  final String? fromStatus;
  final String toStatus;
  final String? performedByName;
  final DateTime createdAt;

  factory BookingStatusLog.fromJson(Map<String, dynamic> json) {
    final performer = json['performed_by'] as Map<String, dynamic>?;
    return BookingStatusLog(
      id: json['id'] as int,
      fromStatus: json['from_status'] as String?,
      toStatus: json['to_status'] as String,
      performedByName: performer?['name'] as String?,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}

/// Immutable model representing a BookingRequest from the API.
@immutable
class BookingModel {
  const BookingModel({
    required this.id,
    required this.status,
    required this.clientName,
    required this.talentStageName,
    required this.talentProfileId,
    required this.packageName,
    required this.packageType,
    required this.eventDate,
    required this.eventLocation,
    required this.cachetAmount,
    required this.commissionAmount,
    required this.totalAmount,
    required this.isExpress,
    required this.contractAvailable,
    this.talentAvatarUrl,
    this.startTime,
    this.message,
    this.rejectReason,
    this.refundAmount,
    this.cancellationPolicyApplied,
    this.devisMessage,
    this.statusLogs,
  });

  final int id;
  final String status;
  final String clientName;
  final String talentStageName;

  /// Null if the backend didn't return a talent_profile object.
  final int? talentProfileId;
  final String? talentAvatarUrl;
  final String packageName;
  final String packageType;
  final String eventDate;
  final String? startTime;
  final String eventLocation;
  final int cachetAmount;
  final int commissionAmount;
  final int totalAmount;
  final bool isExpress;
  final bool contractAvailable;
  final String? message;
  final String? rejectReason;
  final int? refundAmount;
  final String? cancellationPolicyApplied;
  final String? devisMessage;
  final List<BookingStatusLog>? statusLogs;

  factory BookingModel.fromJson(Map<String, dynamic> json) {
    final client = json['client'] as Map<String, dynamic>?;
    final talent = json['talent_profile'] as Map<String, dynamic>?;
    final pkg = json['service_package'] as Map<String, dynamic>?;
    final devis = json['devis'] as Map<String, dynamic>?;
    final historyJson = json['history'] as List<dynamic>?;

    return BookingModel(
      id: json['id'] as int,
      status: json['status'] as String,
      clientName: client?['name'] as String? ?? 'Client',
      talentStageName: talent?['stage_name'] as String? ?? 'Talent',
      talentProfileId: talent?['id'] as int?,
      talentAvatarUrl: talent?['avatar_url'] as String?,
      packageName: pkg?['name'] as String? ?? 'Forfait',
      packageType: pkg?['type'] as String? ?? 'standard',
      eventDate: json['event_date'] as String,
      startTime: json['start_time'] as String?,
      eventLocation: json['event_location'] as String,
      cachetAmount: (devis?['cachet_amount'] as int?) ?? 0,
      commissionAmount: (devis?['commission_amount'] as int?) ?? 0,
      totalAmount: (devis?['total_amount'] as int?) ?? 0,
      isExpress: (json['is_express'] as bool?) ?? false,
      contractAvailable: (json['contract_available'] as bool?) ?? false,
      message: json['message'] as String?,
      rejectReason: json['reject_reason'] as String?,
      refundAmount: json['refund_amount'] as int?,
      cancellationPolicyApplied: json['cancellation_policy_applied'] as String?,
      devisMessage: devis?['message'] as String?,
      statusLogs: historyJson
          ?.map((e) => BookingStatusLog.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BookingModel &&
          runtimeType == other.runtimeType &&
          id == other.id &&
          status == other.status;

  @override
  int get hashCode => Object.hash(id, status);
}
