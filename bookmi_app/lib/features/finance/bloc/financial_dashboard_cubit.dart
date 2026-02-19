import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/finance/bloc/financial_dashboard_state.dart';
import 'package:bookmi_app/features/finance/data/repositories/financial_repository.dart';

class FinancialDashboardCubit extends Cubit<FinancialDashboardState> {
  FinancialDashboardCubit({required FinancialRepository repository})
    : _repository = repository,
      super(const FinancialDashboardInitial());

  final FinancialRepository _repository;

  /// Load dashboard + first page of payouts in parallel.
  Future<void> load() async {
    emit(const FinancialDashboardLoading());

    switch (await _repository.getDashboard()) {
      case ApiFailure(:final message):
        emit(FinancialDashboardError(message: message));
        return;
      case ApiSuccess(:final data):
        final dashboard = data;
        switch (await _repository.getPayouts()) {
          case ApiFailure(:final message):
            emit(FinancialDashboardError(message: message));
          case ApiSuccess(:final data):
            emit(FinancialDashboardLoaded(dashboard: dashboard, payouts: data));
        }
    }
  }
}
