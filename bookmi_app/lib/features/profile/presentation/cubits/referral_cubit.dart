import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/referral_repository.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

part 'referral_state.dart';

class ReferralCubit extends Cubit<ReferralState> {
  ReferralCubit({required ReferralRepository repository})
    : _repository = repository,
      super(const ReferralInitial());

  final ReferralRepository _repository;

  Future<void> load() async {
    emit(const ReferralLoading());
    final result = await _repository.getReferralInfo();
    switch (result) {
      case ApiSuccess(:final data):
        emit(ReferralLoaded(info: data));
      case ApiFailure(:final message):
        emit(ReferralError(message: message));
    }
  }

  Future<void> applyCode(String code) async {
    final currentInfo = state is ReferralLoaded
        ? (state as ReferralLoaded).info
        : null;

    emit(const ReferralApplying());
    final result = await _repository.applyCode(code);
    switch (result) {
      case ApiSuccess():
        // Reload to get updated stats
        await load();
      case ApiFailure(:final message):
        // Restore previous loaded state on error
        if (currentInfo != null) {
          emit(ReferralLoaded(info: currentInfo));
        }
        emit(ReferralApplyError(message: message));
    }
  }
}
