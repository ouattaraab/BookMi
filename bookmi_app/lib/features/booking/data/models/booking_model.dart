import 'package:flutter/foundation.dart';

/// Pending reschedule request on a booking.
@immutable
class RescheduleInfo {
  const RescheduleInfo({
    required this.id,
    required this.proposedDate,
    required this.requestedById,
    required this.status,
    this.message,
  });

  final int id;
  final String proposedDate;
  final String? message;
  final int requestedById;
  final String status;

  factory RescheduleInfo.fromJson(Map<String, dynamic> json) {
    return RescheduleInfo(
      id: json['id'] as int,
      proposedDate: json['proposed_date'] as String,
      message: json['message'] as String?,
      requestedById: json['requested_by_id'] as int,
      status: json['status'] as String,
    );
  }
}

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
    required this.travelCost,
    required this.commissionAmount,
    required this.totalAmount,
    required this.expressFeee,
    required this.discountAmount,
    required this.isExpress,
    required this.contractAvailable,
    required this.hasClientReview,
    required this.hasTalentReview,
    this.clientReviewId,
    this.clientReviewReply,
    this.talentAvatarUrl,
    this.startTime,
    this.message,
    this.rejectReason,
    this.refundAmount,
    this.cancellationPolicyApplied,
    this.devisMessage,
    this.appliedPromoCode,
    this.statusLogs,
    this.pendingReschedule,
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
  final int travelCost;
  final int commissionAmount;
  final int totalAmount;
  final int expressFeee;
  final int discountAmount;
  final bool isExpress;
  final bool contractAvailable;
  final bool hasClientReview;
  final bool hasTalentReview;

  /// ID of the client_to_talent review, null if not yet submitted.
  final int? clientReviewId;

  /// Text of the talent's reply to the client review, null if not yet replied.
  final String? clientReviewReply;
  final String? message;
  final String? rejectReason;
  final int? refundAmount;
  final String? cancellationPolicyApplied;
  final String? devisMessage;
  final String? appliedPromoCode;
  final List<BookingStatusLog>? statusLogs;
  final RescheduleInfo? pendingReschedule;

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
      travelCost: (devis?['travel_cost'] as int?) ?? 0,
      commissionAmount: (devis?['commission_amount'] as int?) ?? 0,
      totalAmount: (devis?['total_amount'] as int?) ?? 0,
      expressFeee: (devis?['express_fee'] as int?) ?? 0,
      discountAmount: (devis?['discount_amount'] as int?) ?? 0,
      isExpress: (json['is_express'] as bool?) ?? false,
      contractAvailable: (json['contract_available'] as bool?) ?? false,
      hasClientReview: (json['has_client_review'] as bool?) ?? false,
      hasTalentReview: (json['has_talent_review'] as bool?) ?? false,
      clientReviewId: json['client_review_id'] as int?,
      clientReviewReply: json['client_review_reply'] as String?,
      message: json['message'] as String?,
      rejectReason: json['reject_reason'] as String?,
      refundAmount: json['refund_amount'] as int?,
      cancellationPolicyApplied: json['cancellation_policy_applied'] as String?,
      devisMessage: devis?['message'] as String?,
      appliedPromoCode: devis?['promo_code'] as String?,
      statusLogs: historyJson
          ?.map((e) => BookingStatusLog.fromJson(e as Map<String, dynamic>))
          .toList(),
      pendingReschedule: json['pending_reschedule'] != null
          ? RescheduleInfo.fromJson(
              json['pending_reschedule'] as Map<String, dynamic>,
            )
          : null,
    );
  }

  BookingModel copyWith({
    int? id,
    String? status,
    String? clientName,
    String? talentStageName,
    int? talentProfileId,
    String? talentAvatarUrl,
    String? packageName,
    String? packageType,
    String? eventDate,
    String? startTime,
    String? eventLocation,
    int? cachetAmount,
    int? travelCost,
    int? commissionAmount,
    int? totalAmount,
    int? expressFeee,
    int? discountAmount,
    bool? isExpress,
    bool? contractAvailable,
    bool? hasClientReview,
    bool? hasTalentReview,
    int? clientReviewId,
    String? clientReviewReply,
    String? message,
    String? rejectReason,
    int? refundAmount,
    String? cancellationPolicyApplied,
    String? devisMessage,
    String? appliedPromoCode,
    List<BookingStatusLog>? statusLogs,
    RescheduleInfo? pendingReschedule,
  }) {
    return BookingModel(
      id: id ?? this.id,
      status: status ?? this.status,
      clientName: clientName ?? this.clientName,
      talentStageName: talentStageName ?? this.talentStageName,
      talentProfileId: talentProfileId ?? this.talentProfileId,
      talentAvatarUrl: talentAvatarUrl ?? this.talentAvatarUrl,
      packageName: packageName ?? this.packageName,
      packageType: packageType ?? this.packageType,
      eventDate: eventDate ?? this.eventDate,
      startTime: startTime ?? this.startTime,
      eventLocation: eventLocation ?? this.eventLocation,
      cachetAmount: cachetAmount ?? this.cachetAmount,
      travelCost: travelCost ?? this.travelCost,
      commissionAmount: commissionAmount ?? this.commissionAmount,
      totalAmount: totalAmount ?? this.totalAmount,
      expressFeee: expressFeee ?? this.expressFeee,
      discountAmount: discountAmount ?? this.discountAmount,
      isExpress: isExpress ?? this.isExpress,
      contractAvailable: contractAvailable ?? this.contractAvailable,
      hasClientReview: hasClientReview ?? this.hasClientReview,
      hasTalentReview: hasTalentReview ?? this.hasTalentReview,
      clientReviewId: clientReviewId ?? this.clientReviewId,
      clientReviewReply: clientReviewReply ?? this.clientReviewReply,
      message: message ?? this.message,
      rejectReason: rejectReason ?? this.rejectReason,
      refundAmount: refundAmount ?? this.refundAmount,
      cancellationPolicyApplied:
          cancellationPolicyApplied ?? this.cancellationPolicyApplied,
      devisMessage: devisMessage ?? this.devisMessage,
      appliedPromoCode: appliedPromoCode ?? this.appliedPromoCode,
      statusLogs: statusLogs ?? this.statusLogs,
      pendingReschedule: pendingReschedule ?? this.pendingReschedule,
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
