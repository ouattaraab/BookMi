import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/evaluation/bloc/evaluation_cubit.dart';
import 'package:bookmi_app/features/evaluation/bloc/evaluation_state.dart';
import 'package:bookmi_app/features/evaluation/data/models/review_model.dart';
import 'package:bookmi_app/features/evaluation/data/repositories/review_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class _MockReviewRepository extends Mock implements ReviewRepository {}

void main() {
  late _MockReviewRepository repository;
  const bookingId = 1;

  const fakeClientReview = ReviewModel(
    id: 1,
    bookingRequestId: bookingId,
    reviewerId: 10,
    revieweeId: 20,
    type: 'client_to_talent',
    rating: 5,
    comment: 'Excellent travail !',
  );

  const fakeTalentReview = ReviewModel(
    id: 2,
    bookingRequestId: bookingId,
    reviewerId: 20,
    revieweeId: 10,
    type: 'talent_to_client',
    rating: 4,
  );

  setUp(() {
    repository = _MockReviewRepository();
  });

  group('EvaluationCubit', () {
    test('initial state is EvaluationInitial', () {
      expect(
        EvaluationCubit(repository: repository).state,
        isA<EvaluationInitial>(),
      );
    });

    // ── loadReviews ────────────────────────────────────────────────────────────

    blocTest<EvaluationCubit, EvaluationState>(
      'emits [Loading, Loaded] on successful loadReviews',
      build: () {
        when(() => repository.getReviews(bookingId)).thenAnswer(
          (_) async => const ApiSuccess([fakeClientReview]),
        );
        return EvaluationCubit(repository: repository);
      },
      act: (cubit) => cubit.loadReviews(bookingId),
      expect: () => [
        isA<EvaluationLoading>(),
        isA<EvaluationLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as EvaluationLoaded;
        expect(loaded.reviews, hasLength(1));
        expect(loaded.hasReviewedAsClient, isTrue);
        expect(loaded.hasReviewedAsTalent, isFalse);
      },
    );

    blocTest<EvaluationCubit, EvaluationState>(
      'emits [Loading, Error] when getReviews fails',
      build: () {
        when(() => repository.getReviews(bookingId)).thenAnswer(
          (_) async => const ApiFailure(
            code: 'FORBIDDEN',
            message: 'Accès refusé',
          ),
        );
        return EvaluationCubit(repository: repository);
      },
      act: (cubit) => cubit.loadReviews(bookingId),
      expect: () => [
        isA<EvaluationLoading>(),
        isA<EvaluationError>(),
      ],
      verify: (cubit) {
        expect(
          (cubit.state as EvaluationError).message,
          equals('Accès refusé'),
        );
      },
    );

    // ── submitReview ───────────────────────────────────────────────────────────

    blocTest<EvaluationCubit, EvaluationState>(
      'emits [Submitting, Submitted] on successful submitReview',
      build: () {
        when(
          () => repository.submitReview(
            bookingId,
            type: 'client_to_talent',
            rating: 5,
            comment: any(named: 'comment'),
          ),
        ).thenAnswer((_) async => const ApiSuccess(fakeClientReview));
        when(() => repository.getReviews(bookingId)).thenAnswer(
          (_) async => const ApiSuccess([fakeClientReview]),
        );
        return EvaluationCubit(repository: repository);
      },
      act: (cubit) => cubit.submitReview(
        bookingId,
        type: 'client_to_talent',
        rating: 5,
        comment: 'Excellent travail !',
      ),
      expect: () => [
        isA<EvaluationSubmitting>(),
        isA<EvaluationSubmitted>(),
      ],
      verify: (cubit) {
        final submitted = cubit.state as EvaluationSubmitted;
        expect(submitted.review.rating, equals(5));
        expect(submitted.review.type, equals('client_to_talent'));
        expect(submitted.reviews, hasLength(1));
      },
    );

    blocTest<EvaluationCubit, EvaluationState>(
      'emits [Submitting, Error] when submitReview fails',
      build: () {
        when(
          () => repository.submitReview(
            bookingId,
            type: 'client_to_talent',
            rating: 4,
            comment: any(named: 'comment'),
          ),
        ).thenAnswer(
          (_) async => const ApiFailure(
            code: 'ALREADY_REVIEWED',
            message: 'Vous avez déjà évalué cette prestation.',
          ),
        );
        return EvaluationCubit(repository: repository);
      },
      act: (cubit) => cubit.submitReview(
        bookingId,
        type: 'client_to_talent',
        rating: 4,
      ),
      expect: () => [
        isA<EvaluationSubmitting>(),
        isA<EvaluationError>(),
      ],
      verify: (cubit) {
        expect(
          (cubit.state as EvaluationError).message,
          equals('Vous avez déjà évalué cette prestation.'),
        );
      },
    );

    blocTest<EvaluationCubit, EvaluationState>(
      'hasReviewedAsTalent is true for talent review',
      build: () {
        when(() => repository.getReviews(bookingId)).thenAnswer(
          (_) async => const ApiSuccess([fakeClientReview, fakeTalentReview]),
        );
        return EvaluationCubit(repository: repository);
      },
      act: (cubit) => cubit.loadReviews(bookingId),
      verify: (cubit) {
        final loaded = cubit.state as EvaluationLoaded;
        expect(loaded.hasReviewedAsTalent, isTrue);
        expect(loaded.hasReviewedAsClient, isTrue);
        expect(loaded.reviews, hasLength(2));
      },
    );

    blocTest<EvaluationCubit, EvaluationState>(
      'Submitted still shows review even if reload fails',
      build: () {
        when(
          () => repository.submitReview(
            bookingId,
            type: 'talent_to_client',
            rating: 4,
            comment: any(named: 'comment'),
          ),
        ).thenAnswer((_) async => const ApiSuccess(fakeTalentReview));
        when(() => repository.getReviews(bookingId)).thenAnswer(
          (_) async => const ApiFailure(code: 'SERVER_ERROR', message: 'Err'),
        );
        return EvaluationCubit(repository: repository);
      },
      act: (cubit) => cubit.submitReview(
        bookingId,
        type: 'talent_to_client',
        rating: 4,
      ),
      expect: () => [
        isA<EvaluationSubmitting>(),
        isA<EvaluationSubmitted>(),
      ],
      verify: (cubit) {
        final submitted = cubit.state as EvaluationSubmitted;
        expect(submitted.review.type, equals('talent_to_client'));
        // Falls back to list containing just the new review
        expect(submitted.reviews, hasLength(1));
      },
    );
  });
}
