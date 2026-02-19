import 'package:bookmi_app/features/finance/data/models/financial_dashboard_model.dart';
import 'package:bookmi_app/features/finance/data/models/payout_model.dart';
import 'package:flutter/foundation.dart';

sealed class FinancialDashboardState {
  const FinancialDashboardState();
}

final class FinancialDashboardInitial extends FinancialDashboardState {
  const FinancialDashboardInitial();
}

final class FinancialDashboardLoading extends FinancialDashboardState {
  const FinancialDashboardLoading();
}

@immutable
final class FinancialDashboardLoaded extends FinancialDashboardState {
  const FinancialDashboardLoaded({
    required this.dashboard,
    required this.payouts,
  });

  final FinancialDashboardModel dashboard;
  final List<PayoutModel> payouts;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FinancialDashboardLoaded &&
          dashboard == other.dashboard &&
          payouts == other.payouts;

  @override
  int get hashCode => Object.hash(dashboard, payouts);
}

@immutable
final class FinancialDashboardError extends FinancialDashboardState {
  const FinancialDashboardError({required this.message});
  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FinancialDashboardError && message == other.message;

  @override
  int get hashCode => message.hashCode;
}
