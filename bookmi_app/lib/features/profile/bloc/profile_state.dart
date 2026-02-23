part of 'profile_bloc.dart';

sealed class ProfileState {
  const ProfileState();
}

final class ProfileInitial extends ProfileState {
  const ProfileInitial();
}

final class ProfileLoading extends ProfileState {
  const ProfileLoading();
}

final class ProfileLoaded extends ProfileState {
  const ProfileLoaded({required this.stats});
  final ProfileStats stats;
}

final class ProfileFailure extends ProfileState {
  const ProfileFailure({required this.message});
  final String message;
}
