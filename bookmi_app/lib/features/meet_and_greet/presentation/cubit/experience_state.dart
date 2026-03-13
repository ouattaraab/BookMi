import 'package:bookmi_app/features/meet_and_greet/data/models/experience_model.dart';
import 'package:flutter/foundation.dart';

sealed class ExperienceState {
  const ExperienceState();
}

final class ExperienceInitial extends ExperienceState {
  const ExperienceInitial();
}

final class ExperienceLoading extends ExperienceState {
  const ExperienceLoading();
}

@immutable
final class ExperienceLoaded extends ExperienceState {
  const ExperienceLoaded({
    required this.experiences,
    required this.currentPage,
    required this.lastPage,
  });

  final List<ExperienceModel> experiences;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExperienceLoaded &&
          listEquals(experiences, other.experiences) &&
          currentPage == other.currentPage &&
          lastPage == other.lastPage;

  @override
  int get hashCode => Object.hash(
        Object.hashAll(experiences),
        currentPage,
        lastPage,
      );
}

final class ExperienceLoadingMore extends ExperienceLoaded {
  const ExperienceLoadingMore({
    required super.experiences,
    required super.currentPage,
    required super.lastPage,
  });
}

@immutable
final class ExperienceFailure extends ExperienceState {
  const ExperienceFailure({required this.code, required this.message});

  final String code;
  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExperienceFailure &&
          code == other.code &&
          message == other.message;

  @override
  int get hashCode => Object.hash(code, message);
}
