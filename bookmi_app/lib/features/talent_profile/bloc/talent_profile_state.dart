import 'package:flutter/foundation.dart';

sealed class TalentProfileState {
  const TalentProfileState();
}

final class TalentProfileInitial extends TalentProfileState {
  const TalentProfileInitial();
}

final class TalentProfileLoading extends TalentProfileState {
  const TalentProfileLoading();
}

@immutable
final class TalentProfileLoaded extends TalentProfileState {
  const TalentProfileLoaded({
    required this.profile,
    required this.similarTalents,
  });

  final Map<String, dynamic> profile;
  final List<Map<String, dynamic>> similarTalents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other.runtimeType == runtimeType &&
          other is TalentProfileLoaded &&
          mapEquals(profile, other.profile) &&
          listEquals(similarTalents, other.similarTalents);

  @override
  int get hashCode => Object.hash(
    Object.hashAll(
      profile.entries.map((e) => Object.hash(e.key, e.value)),
    ),
    Object.hashAll(similarTalents),
  );
}

@immutable
final class TalentProfileFailure extends TalentProfileState {
  const TalentProfileFailure({
    required this.code,
    required this.message,
  });

  final String code;
  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other.runtimeType == runtimeType &&
          other is TalentProfileFailure &&
          code == other.code &&
          message == other.message;

  @override
  int get hashCode => Object.hash(code, message);
}
