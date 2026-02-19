import 'package:flutter/foundation.dart';

/// A single payout record from GET /api/v1/me/payouts.
@immutable
class PayoutModel {
  const PayoutModel({
    required this.id,
    required this.amount,
    required this.status,
    this.processedAt,
  });

  final int id;

  /// Amount in XOF cents.
  final int amount;

  /// 'pending', 'succeeded', 'failed'.
  final String status;

  /// ISO8601 timestamp when payout was processed (null if still pending).
  final String? processedAt;

  factory PayoutModel.fromJson(Map<String, dynamic> json) => PayoutModel(
    id: json['id'] as int,
    amount: json['amount'] as int,
    status: json['status'] as String,
    processedAt: json['processed_at'] as String?,
  );
}
