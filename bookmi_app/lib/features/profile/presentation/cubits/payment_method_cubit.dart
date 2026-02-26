import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/models/payout_method_model.dart';
import 'package:bookmi_app/features/profile/data/models/withdrawal_request_model.dart';
import 'package:bookmi_app/features/profile/data/repositories/payout_method_repository.dart';

// ─── State ───────────────────────────────────────────────────────────────────

sealed class PaymentMethodState {
  const PaymentMethodState();
}

final class PaymentMethodInitial extends PaymentMethodState {
  const PaymentMethodInitial();
}

final class PaymentMethodLoading extends PaymentMethodState {
  const PaymentMethodLoading();
}

final class PaymentMethodLoaded extends PaymentMethodState {
  const PaymentMethodLoaded({
    required this.payoutMethod,
    required this.withdrawalRequests,
  });

  final PayoutMethodModel payoutMethod;
  final List<WithdrawalRequestModel> withdrawalRequests;

  bool get hasActiveRequest => withdrawalRequests.any((r) => r.isActive);
}

final class PaymentMethodSaving extends PaymentMethodState {
  const PaymentMethodSaving();
}

final class PaymentMethodSaved extends PaymentMethodState {
  const PaymentMethodSaved({required this.payoutMethod});
  final PayoutMethodModel payoutMethod;
}

final class PaymentMethodDeleting extends PaymentMethodState {
  const PaymentMethodDeleting();
}

final class PaymentMethodDeleted extends PaymentMethodState {
  const PaymentMethodDeleted();
}

final class PaymentMethodWithdrawalCreating extends PaymentMethodState {
  const PaymentMethodWithdrawalCreating();
}

final class PaymentMethodWithdrawalCreated extends PaymentMethodState {
  const PaymentMethodWithdrawalCreated({required this.request});
  final WithdrawalRequestModel request;
}

final class PaymentMethodError extends PaymentMethodState {
  const PaymentMethodError({required this.code, required this.message});
  final String code;
  final String message;
}

// ─── Cubit ───────────────────────────────────────────────────────────────────

class PaymentMethodCubit extends Cubit<PaymentMethodState> {
  PaymentMethodCubit({required PayoutMethodRepository repository})
    : _repository = repository,
      super(const PaymentMethodInitial());

  final PayoutMethodRepository _repository;

  Future<void> load() async {
    emit(const PaymentMethodLoading());

    final methodResult = await _repository.getPayoutMethod();
    if (methodResult is ApiFailure<PayoutMethodModel>) {
      emit(
        PaymentMethodError(
          code: methodResult.code,
          message: methodResult.message,
        ),
      );
      return;
    }

    final requestsResult = await _repository.getWithdrawalRequests();
    if (requestsResult is ApiFailure<List<WithdrawalRequestModel>>) {
      emit(
        PaymentMethodError(
          code: requestsResult.code,
          message: requestsResult.message,
        ),
      );
      return;
    }

    emit(
      PaymentMethodLoaded(
        payoutMethod: (methodResult as ApiSuccess<PayoutMethodModel>).data,
        withdrawalRequests:
            (requestsResult as ApiSuccess<List<WithdrawalRequestModel>>).data,
      ),
    );
  }

  Future<void> updatePayoutMethod({
    required String payoutMethod,
    required Map<String, dynamic> payoutDetails,
  }) async {
    emit(const PaymentMethodSaving());

    final result = await _repository.updatePayoutMethod(
      payoutMethod: payoutMethod,
      payoutDetails: payoutDetails,
    );

    switch (result) {
      case ApiSuccess(:final data):
        emit(PaymentMethodSaved(payoutMethod: data));
      case ApiFailure(:final code, :final message):
        emit(PaymentMethodError(code: code, message: message));
    }
  }

  Future<void> deletePayoutMethod() async {
    emit(const PaymentMethodDeleting());

    final result = await _repository.deletePayoutMethod();

    switch (result) {
      case ApiSuccess():
        emit(const PaymentMethodDeleted());
      case ApiFailure(:final code, :final message):
        emit(PaymentMethodError(code: code, message: message));
    }
  }

  Future<void> createWithdrawalRequest({required int amount}) async {
    emit(const PaymentMethodWithdrawalCreating());

    final result = await _repository.createWithdrawalRequest(amount: amount);

    switch (result) {
      case ApiSuccess(:final data):
        emit(PaymentMethodWithdrawalCreated(request: data));
      case ApiFailure(:final code, :final message):
        emit(PaymentMethodError(code: code, message: message));
    }
  }
}
