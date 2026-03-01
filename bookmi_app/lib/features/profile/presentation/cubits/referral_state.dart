part of 'referral_cubit.dart';

@immutable
sealed class ReferralState {
  const ReferralState();
}

final class ReferralInitial extends ReferralState {
  const ReferralInitial();
}

final class ReferralLoading extends ReferralState {
  const ReferralLoading();
}

final class ReferralLoaded extends ReferralState {
  const ReferralLoaded({required this.info});
  final ReferralInfo info;
}

final class ReferralError extends ReferralState {
  const ReferralError({required this.message});
  final String message;
}

final class ReferralApplying extends ReferralState {
  const ReferralApplying();
}

final class ReferralApplyError extends ReferralState {
  const ReferralApplyError({required this.message});
  final String message;
}
