import 'package:bookmi_app/features/talent_profile/presentation/widgets/reviews_section.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ReviewsSection', () {
    Widget buildSection({
      List<Map<String, dynamic>> reviews = const [],
      int reviewsCount = 0,
      double averageRating = 0,
    }) {
      return MaterialApp(
        home: Scaffold(
          body: ReviewsSection(
            reviews: reviews,
            reviewsCount: reviewsCount,
            averageRating: averageRating,
          ),
        ),
      );
    }

    testWidgets('shows empty state when reviews list is empty', (tester) async {
      await tester.pumpWidget(buildSection());

      expect(
        find.text("Pas encore d'avis — Soyez le premier !"),
        findsOneWidget,
      );
    });

    testWidgets('displays average rating and reviews count in header', (
      tester,
    ) async {
      await tester.pumpWidget(
        buildSection(
          reviewsCount: 12,
          averageRating: 4.5,
        ),
      );

      expect(find.text('4.5'), findsOneWidget);
      expect(find.text('(12 avis)'), findsOneWidget);
    });

    testWidgets('displays review cards when reviews are provided', (
      tester,
    ) async {
      await tester.pumpWidget(
        buildSection(
          reviews: const [
            {
              'reviewer_name': 'Jean',
              'rating': 5,
              'comment': 'Excellent DJ !',
              'created_at': '2026-02-01',
            },
          ],
          reviewsCount: 1,
          averageRating: 5,
        ),
      );

      expect(find.text('Jean'), findsOneWidget);
      expect(find.text('Excellent DJ !'), findsOneWidget);
    });
  });
}
