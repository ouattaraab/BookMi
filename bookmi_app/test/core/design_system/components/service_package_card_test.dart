import 'package:bookmi_app/core/design_system/components/service_package_card.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ServicePackageCard', () {
    Widget buildCard({
      String name = 'Pack Essentiel',
      String? description,
      int cachetAmount = 30000000,
      int? durationMinutes,
      List<String>? inclusions,
      String type = 'essentiel',
      bool isRecommended = false,
    }) {
      return MaterialApp(
        home: Scaffold(
          body: ServicePackageCard(
            name: name,
            description: description,
            cachetAmount: cachetAmount,
            durationMinutes: durationMinutes,
            inclusions: inclusions,
            type: type,
            isRecommended: isRecommended,
          ),
        ),
      );
    }

    testWidgets('displays name, formatted price, and duration', (tester) async {
      await tester.pumpWidget(
        buildCard(
          name: 'Pack Standard',
          durationMinutes: 120,
        ),
      );

      expect(find.text('Pack Standard'), findsOneWidget);
      expect(find.text('300 000 FCFA'), findsOneWidget);
      expect(find.text('2h'), findsOneWidget);
    });

    testWidgets('displays description when provided', (tester) async {
      await tester.pumpWidget(
        buildCard(description: 'DJ set complet'),
      );

      expect(find.text('DJ set complet'), findsOneWidget);
    });

    testWidgets('displays inclusions as bullet list', (tester) async {
      await tester.pumpWidget(
        buildCard(
          inclusions: ['Sound system', 'DJ set', 'Éclairage'],
        ),
      );

      expect(find.text('Sound system'), findsOneWidget);
      expect(find.text('DJ set'), findsOneWidget);
      expect(find.text('Éclairage'), findsOneWidget);
    });

    testWidgets('shows Recommandé badge when isRecommended is true', (
      tester,
    ) async {
      await tester.pumpWidget(
        buildCard(isRecommended: true),
      );

      expect(find.text('Recommandé'), findsOneWidget);
    });

    testWidgets('shows Populaire badge for standard type', (tester) async {
      await tester.pumpWidget(
        buildCard(type: 'standard'),
      );

      expect(find.text('Populaire'), findsOneWidget);
    });

    testWidgets('shows no badge for essentiel type', (tester) async {
      await tester.pumpWidget(
        buildCard(),
      );

      expect(find.text('Recommandé'), findsNothing);
      expect(find.text('Populaire'), findsNothing);
    });

    group('formatDuration', () {
      test('120 minutes → 2h', () {
        expect(ServicePackageCard.formatDuration(120), '2h');
      });

      test('90 minutes → 1h30', () {
        expect(ServicePackageCard.formatDuration(90), '1h30');
      });

      test('45 minutes → 45min', () {
        expect(ServicePackageCard.formatDuration(45), '45min');
      });

      test('null → empty string', () {
        expect(ServicePackageCard.formatDuration(null), '');
      });

      test('0 → empty string', () {
        expect(ServicePackageCard.formatDuration(0), '');
      });
    });
  });
}
