part of 'profile_bloc.dart';

sealed class ProfileEvent {
  const ProfileEvent();
}

final class ProfileStatsFetched extends ProfileEvent {
  const ProfileStatsFetched({required this.isTalent});
  final bool isTalent;
}
