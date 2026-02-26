import 'package:flutter/foundation.dart';

/// The talent's payout account info returned by GET /api/v1/talent_profiles/me/payout_method
@immutable
class PayoutMethodModel {
  const PayoutMethodModel({
    required this.payoutMethod,
    required this.payoutDetails,
    required this.availableBalance,
    this.payoutMethodVerifiedAt,
  });

  /// e.g. 'orange_money', 'wave', 'mtn_momo', 'moov_money', 'bank_transfer'
  final String? payoutMethod;

  /// e.g. {'phone': '+225...'} or {'account_number': '...', 'bank_code': '...'}
  final Map<String, dynamic>? payoutDetails;

  /// ISO8601 timestamp when account was verified by admin (null = not verified yet)
  final String? payoutMethodVerifiedAt;

  /// Available balance in XOF
  final int availableBalance;

  bool get isVerified => payoutMethodVerifiedAt != null;

  String get phone => (payoutDetails?['phone'] as String?) ?? '';

  String get accountNumber =>
      (payoutDetails?['account_number'] as String?) ?? '';

  factory PayoutMethodModel.fromJson(Map<String, dynamic> json) {
    final data = (json['data'] as Map<String, dynamic>?) ?? json;
    return PayoutMethodModel(
      payoutMethod: data['payout_method'] as String?,
      payoutDetails: data['payout_details'] as Map<String, dynamic>?,
      payoutMethodVerifiedAt: data['payout_method_verified_at'] as String?,
      availableBalance: (data['available_balance'] as num?)?.toInt() ?? 0,
    );
  }

  PayoutMethodModel copyWith({
    String? payoutMethod,
    Map<String, dynamic>? payoutDetails,
    String? payoutMethodVerifiedAt,
    int? availableBalance,
  }) => PayoutMethodModel(
    payoutMethod: payoutMethod ?? this.payoutMethod,
    payoutDetails: payoutDetails ?? this.payoutDetails,
    payoutMethodVerifiedAt:
        payoutMethodVerifiedAt ?? this.payoutMethodVerifiedAt,
    availableBalance: availableBalance ?? this.availableBalance,
  );
}
