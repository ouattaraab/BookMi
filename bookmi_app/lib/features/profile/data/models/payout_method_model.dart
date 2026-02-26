import 'package:flutter/foundation.dart';

/// The talent's payout account info returned by GET /api/v1/talent_profiles/me/payout_method
@immutable
class PayoutMethodModel {
  const PayoutMethodModel({
    required this.payoutMethod,
    required this.payoutDetails,
    required this.availableBalance,
    this.payoutMethodVerifiedAt,
    this.payoutMethodStatus,
    this.rejectionReason,
  });

  /// e.g. 'orange_money', 'wave', 'mtn_momo', 'moov_money', 'bank_transfer'
  final String? payoutMethod;

  /// e.g. {'phone': '+225...'} or {'account_number': '...', 'bank_code': '...'}
  final Map<String, dynamic>? payoutDetails;

  /// ISO8601 timestamp when account was verified by admin (null = not verified yet)
  final String? payoutMethodVerifiedAt;

  /// 'pending' | 'verified' | 'rejected' | null (not yet submitted)
  final String? payoutMethodStatus;

  /// Admin rejection reason (only set when payoutMethodStatus == 'rejected')
  final String? rejectionReason;

  /// Available balance in XOF
  final int availableBalance;

  bool get hasAccount => payoutMethod != null;

  bool get isVerified =>
      payoutMethodStatus == 'verified' ||
      (payoutMethodStatus == null && payoutMethodVerifiedAt != null);

  bool get isPending => payoutMethodStatus == 'pending';

  bool get isRejected => payoutMethodStatus == 'rejected';

  String get phone => (payoutDetails?['phone'] as String?) ?? '';

  String get accountNumber =>
      (payoutDetails?['account_number'] as String?) ?? '';

  factory PayoutMethodModel.fromJson(Map<String, dynamic> json) {
    final data = (json['data'] as Map<String, dynamic>?) ?? json;
    return PayoutMethodModel(
      payoutMethod: data['payout_method'] as String?,
      payoutDetails: data['payout_details'] as Map<String, dynamic>?,
      payoutMethodVerifiedAt: data['payout_method_verified_at'] as String?,
      payoutMethodStatus: data['payout_method_status'] as String?,
      rejectionReason: data['payout_method_rejection_reason'] as String?,
      availableBalance: (data['available_balance'] as num?)?.toInt() ?? 0,
    );
  }

  PayoutMethodModel copyWith({
    String? payoutMethod,
    Map<String, dynamic>? payoutDetails,
    String? payoutMethodVerifiedAt,
    String? payoutMethodStatus,
    String? rejectionReason,
    int? availableBalance,
  }) => PayoutMethodModel(
    payoutMethod: payoutMethod ?? this.payoutMethod,
    payoutDetails: payoutDetails ?? this.payoutDetails,
    payoutMethodVerifiedAt:
        payoutMethodVerifiedAt ?? this.payoutMethodVerifiedAt,
    payoutMethodStatus: payoutMethodStatus ?? this.payoutMethodStatus,
    rejectionReason: rejectionReason ?? this.rejectionReason,
    availableBalance: availableBalance ?? this.availableBalance,
  );
}
