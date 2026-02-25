import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/finance/bloc/financial_dashboard_cubit.dart';
import 'package:bookmi_app/features/finance/bloc/financial_dashboard_state.dart';
import 'package:bookmi_app/features/finance/data/models/financial_dashboard_model.dart';
import 'package:bookmi_app/features/finance/data/models/payout_model.dart';
import 'package:bookmi_app/features/finance/data/repositories/financial_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class _MockFinancialRepository extends Mock implements FinancialRepository {}

void main() {
  late _MockFinancialRepository repository;

  final fakeDashboard = FinancialDashboardModel(
    revenusTotal: 5_000_000,
    revenusMoisCourant: 1_000_000,
    revenusMoisPrecedent: 800_000,
    comparaisonPourcentage: 25.0,
    nombrePrestations: 5,
    cachetMoyen: 1_000_000,
    devise: 'XOF',
    mensuels: List.generate(
      6,
      (i) => MonthlyRevenue(
        month: '2025-${(i + 7).toString().padLeft(2, '0')}',
        revenue: i * 100_000,
      ),
    ),
  );

  const fakePayout = PayoutModel(
    id: 1,
    amount: 1_000_000,
    status: 'succeeded',
    processedAt: '2026-02-15T10:00:00Z',
  );

  setUp(() {
    repository = _MockFinancialRepository();
  });

  group('FinancialDashboardCubit', () {
    test('initial state is FinancialDashboardInitial', () {
      expect(
        FinancialDashboardCubit(repository: repository).state,
        isA<FinancialDashboardInitial>(),
      );
    });

    blocTest<FinancialDashboardCubit, FinancialDashboardState>(
      'emits [Loading, Loaded] on successful load',
      build: () {
        when(() => repository.getDashboard()).thenAnswer(
          (_) async => ApiSuccess(fakeDashboard),
        );
        when(() => repository.getPayouts(page: any(named: 'page'))).thenAnswer(
          (_) async => const ApiSuccess([fakePayout]),
        );
        return FinancialDashboardCubit(repository: repository);
      },
      act: (cubit) => cubit.load(),
      expect: () => [
        isA<FinancialDashboardLoading>(),
        isA<FinancialDashboardLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as FinancialDashboardLoaded;
        expect(loaded.dashboard.revenusTotal, equals(5_000_000));
        expect(loaded.payouts, hasLength(1));
        expect(loaded.payouts.first.id, equals(1));
      },
    );

    blocTest<FinancialDashboardCubit, FinancialDashboardState>(
      'emits [Loading, Error] when getDashboard fails',
      build: () {
        when(() => repository.getDashboard()).thenAnswer(
          (_) async => const ApiFailure(
            code: 'NETWORK_ERROR',
            message: 'Erreur réseau',
          ),
        );
        return FinancialDashboardCubit(repository: repository);
      },
      act: (cubit) => cubit.load(),
      expect: () => [
        isA<FinancialDashboardLoading>(),
        isA<FinancialDashboardError>(),
      ],
      verify: (cubit) {
        expect(
          (cubit.state as FinancialDashboardError).message,
          equals('Erreur réseau'),
        );
      },
    );

    blocTest<FinancialDashboardCubit, FinancialDashboardState>(
      'emits [Loading, Error] when getPayouts fails',
      build: () {
        when(() => repository.getDashboard()).thenAnswer(
          (_) async => ApiSuccess(fakeDashboard),
        );
        when(() => repository.getPayouts(page: any(named: 'page'))).thenAnswer(
          (_) async => const ApiFailure(
            code: 'SERVER_ERROR',
            message: 'Erreur serveur',
          ),
        );
        return FinancialDashboardCubit(repository: repository);
      },
      act: (cubit) => cubit.load(),
      expect: () => [
        isA<FinancialDashboardLoading>(),
        isA<FinancialDashboardError>(),
      ],
    );

    blocTest<FinancialDashboardCubit, FinancialDashboardState>(
      'loaded state contains 0 payouts when history is empty',
      build: () {
        when(() => repository.getDashboard()).thenAnswer(
          (_) async => ApiSuccess(fakeDashboard),
        );
        when(() => repository.getPayouts(page: any(named: 'page'))).thenAnswer(
          (_) async => const ApiSuccess(<PayoutModel>[]),
        );
        return FinancialDashboardCubit(repository: repository);
      },
      act: (cubit) => cubit.load(),
      expect: () => [
        isA<FinancialDashboardLoading>(),
        isA<FinancialDashboardLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as FinancialDashboardLoaded;
        expect(loaded.payouts, isEmpty);
      },
    );
  });
}
