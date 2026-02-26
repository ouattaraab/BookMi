import 'package:flutter/foundation.dart';

@immutable
class WithdrawalRequestModel {
  const WithdrawalRequestModel({
    required this.id,
    required this.amount,
    required this.status,
    required this.statusLabel,
    required this.payoutMethod,
    required this.payoutDetails,
    this.note,
    this.processedAt,
    required this.createdAt,
  });

  final int id;
  final int amount;

  /// 'pending', 'approved', 'processing', 'completed', 'rejected'
  final String status;
  final String statusLabel;
  final String? payoutMethod;
  final Map<String, dynamic>? payoutDetails;
  final String? note;
  final String? processedAt;
  final String createdAt;

  bool get isPending => status == 'pending';
  bool get isApproved => status == 'approved';
  bool get isProcessing => status == 'processing';
  bool get isCompleted => status == 'completed';
  bool get isRejected => status == 'rejected';
  bool get isActive =>
      status == 'pending' || status == 'approved' || status == 'processing';

  factory WithdrawalRequestModel.fromJson(Map<String, dynamic> json) =>
      WithdrawalRequestModel(
        id: json['id'] as int,
        amount: (json['amount'] as num).toInt(),
        status: json['status'] as String,
        statusLabel:
            json['status_label'] as String? ?? json['status'] as String,
        payoutMethod: json['payout_method'] as String?,
        payoutDetails: json['payout_details'] as Map<String, dynamic>?,
        note: json['note'] as String?,
        processedAt: json['processed_at'] as String?,
        createdAt: json['created_at'] as String,
      );
}
