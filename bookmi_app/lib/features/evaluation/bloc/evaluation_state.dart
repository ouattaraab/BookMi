import 'package:bookmi_app/features/evaluation/data/models/review_model.dart';
import 'package:flutter/foundation.dart';

sealed class EvaluationState {
  const EvaluationState();
}

final class EvaluationInitial extends EvaluationState {
  const EvaluationInitial();
}

final class EvaluationLoading extends EvaluationState {
  const EvaluationLoading();
}

@immutable
final class EvaluationLoaded extends EvaluationState {
  const EvaluationLoaded({
    required this.reviews,
    required this.bookingId,
  });

  final List<ReviewModel> reviews;
  final int bookingId;

  bool get hasReviewedAsClient =>
      reviews.any((r) => r.type == 'client_to_talent');
  bool get hasReviewedAsTalent =>
      reviews.any((r) => r.type == 'talent_to_client');

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EvaluationLoaded &&
          bookingId == other.bookingId &&
          listEquals(reviews, other.reviews);

  @override
  int get hashCode => Object.hash(bookingId, Object.hashAll(reviews));
}

final class EvaluationSubmitting extends EvaluationState {
  const EvaluationSubmitting();
}

@immutable
final class EvaluationSubmitted extends EvaluationState {
  const EvaluationSubmitted({
    required this.review,
    required this.reviews,
    required this.bookingId,
  });

  final ReviewModel review;
  final List<ReviewModel> reviews;
  final int bookingId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EvaluationSubmitted &&
          review.id == other.review.id &&
          bookingId == other.bookingId;

  @override
  int get hashCode => Object.hash(review.id, bookingId);
}

@immutable
final class EvaluationError extends EvaluationState {
  const EvaluationError(this.message);

  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EvaluationError && message == other.message;

  @override
  int get hashCode => message.hashCode;
}
