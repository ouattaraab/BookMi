import 'package:bookmi_app/features/meet_and_greet/data/models/experience_model.dart';
import 'package:flutter/foundation.dart';

sealed class ExperienceDetailState {
  const ExperienceDetailState();
}

final class ExperienceDetailInitial extends ExperienceDetailState {
  const ExperienceDetailInitial();
}

final class ExperienceDetailLoading extends ExperienceDetailState {
  const ExperienceDetailLoading();
}

@immutable
final class ExperienceDetailLoaded extends ExperienceDetailState {
  const ExperienceDetailLoaded({required this.experience});

  final ExperienceModel experience;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExperienceDetailLoaded && experience == other.experience;

  @override
  int get hashCode => experience.hashCode;
}

@immutable
final class ExperienceDetailFailure extends ExperienceDetailState {
  const ExperienceDetailFailure({required this.code, required this.message});

  final String code;
  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExperienceDetailFailure &&
          code == other.code &&
          message == other.message;

  @override
  int get hashCode => Object.hash(code, message);
}

/// Emitted while a booking or cancellation request is in-flight.
@immutable
final class ExperienceDetailBooking extends ExperienceDetailLoaded {
  const ExperienceDetailBooking({required super.experience});
}

/// Emitted when a booking action (book or cancel) completes successfully.
@immutable
final class ExperienceDetailBookingSuccess extends ExperienceDetailLoaded {
  const ExperienceDetailBookingSuccess({
    required super.experience,
    required this.message,
  });

  final String message;
}

/// Emitted when a booking action fails.
@immutable
final class ExperienceDetailBookingFailure extends ExperienceDetailLoaded {
  const ExperienceDetailBookingFailure({
    required super.experience,
    required this.errorMessage,
  });

  final String errorMessage;
}
