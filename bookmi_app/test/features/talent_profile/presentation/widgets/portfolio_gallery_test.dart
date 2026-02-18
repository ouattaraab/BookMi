import 'package:bookmi_app/features/talent_profile/presentation/widgets/portfolio_gallery.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('PortfolioGallery', () {
    Widget buildGallery({
      List<Map<String, dynamic>> items = const [],
    }) {
      return MaterialApp(
        home: Scaffold(
          body: PortfolioGallery(items: items),
        ),
      );
    }

    testWidgets('shows empty state when items list is empty', (tester) async {
      await tester.pumpWidget(buildGallery());

      expect(find.text('Pas encore de portfolio'), findsOneWidget);
    });

    testWidgets('shows grid when items are provided', (tester) async {
      await tester.pumpWidget(
        buildGallery(
          items: const [
            {'url': 'https://example.com/photo1.jpg'},
            {'url': 'https://example.com/photo2.jpg'},
          ],
        ),
      );

      expect(find.text('Pas encore de portfolio'), findsNothing);
    });
  });
}
