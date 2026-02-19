import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/evaluation/bloc/evaluation_state.dart';
import 'package:bookmi_app/features/evaluation/data/repositories/review_repository.dart';

class EvaluationCubit extends Cubit<EvaluationState> {
  EvaluationCubit({required ReviewRepository repository})
    : _repository = repository,
      super(const EvaluationInitial());

  final ReviewRepository _repository;

  Future<void> loadReviews(int bookingId) async {
    emit(const EvaluationLoading());
    switch (await _repository.getReviews(bookingId)) {
      case ApiFailure(:final message):
        emit(EvaluationError(message));
      case ApiSuccess(:final data):
        emit(EvaluationLoaded(reviews: data, bookingId: bookingId));
    }
  }

  Future<void> submitReview(
    int bookingId, {
    required String type,
    required int rating,
    String? comment,
  }) async {
    emit(const EvaluationSubmitting());

    final submitResult = await _repository.submitReview(
      bookingId,
      type: type,
      rating: rating,
      comment: comment,
    );

    switch (submitResult) {
      case ApiFailure(:final message):
        emit(EvaluationError(message));
      case ApiSuccess(:final data):
        // Best-effort reload to include the new review in the full list.
        final reloadResult = await _repository.getReviews(bookingId);
        final allReviews = switch (reloadResult) {
          ApiSuccess(:final data) => data,
          ApiFailure() => [data],
        };
        emit(
          EvaluationSubmitted(
            review: data,
            reviews: allReviews,
            bookingId: bookingId,
          ),
        );
    }
  }
}
