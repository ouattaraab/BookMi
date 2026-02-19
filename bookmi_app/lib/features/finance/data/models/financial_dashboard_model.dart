import 'package:flutter/foundation.dart';

/// One month entry in the 6-month revenue breakdown.
@immutable
class MonthlyRevenue {
  const MonthlyRevenue({required this.month, required this.revenue});

  /// ISO month string: 'YYYY-MM' (e.g. '2026-02').
  final String month;

  /// Revenue in XOF cents for this month.
  final int revenue;

  factory MonthlyRevenue.fromJson(Map<String, dynamic> json) => MonthlyRevenue(
    month: json['mois'] as String,
    revenue: json['revenus'] as int,
  );
}

/// Dashboard data returned by GET /api/v1/me/financial_dashboard.
@immutable
class FinancialDashboardModel {
  const FinancialDashboardModel({
    required this.revenusTotal,
    required this.revenusMoisCourant,
    required this.revenusMoisPrecedent,
    required this.comparaisonPourcentage,
    required this.nombrePrestations,
    required this.cachetMoyen,
    required this.devise,
    required this.mensuels,
  });

  final int revenusTotal;
  final int revenusMoisCourant;
  final int revenusMoisPrecedent;

  /// Percentage change vs. previous month. Positive = growth, negative = decline.
  final double comparaisonPourcentage;

  final int nombrePrestations;
  final int cachetMoyen;
  final String devise;

  /// Last 6 months breakdown, ordered oldest → newest.
  final List<MonthlyRevenue> mensuels;

  factory FinancialDashboardModel.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    return FinancialDashboardModel(
      revenusTotal: data['revenus_total'] as int,
      revenusMoisCourant: data['revenus_mois_courant'] as int,
      revenusMoisPrecedent: data['revenus_mois_precedent'] as int,
      comparaisonPourcentage:
          (data['comparaison_pourcentage'] as num).toDouble(),
      nombrePrestations: data['nombre_prestations'] as int,
      cachetMoyen: data['cachet_moyen'] as int,
      devise: data['devise'] as String,
      mensuels: (data['mensuels'] as List<dynamic>)
          .cast<Map<String, dynamic>>()
          .map(MonthlyRevenue.fromJson)
          .toList(),
    );
  }
}
