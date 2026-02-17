import 'package:bookmi_app/core/design_system/components/glass_bottom_nav.dart';
import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('GlassBottomNav', () {
    tearDown(GpuTierProvider.resetForTesting);

    Widget buildTestNav({int currentIndex = 0, ValueChanged<int>? onTap}) {
      return MaterialApp(
        home: Scaffold(
          bottomNavigationBar: GlassBottomNav(
            currentIndex: currentIndex,
            onTap: onTap ?? (_) {},
          ),
        ),
      );
    }

    testWidgets('renders 5 tab labels', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestNav());

      expect(find.text('Accueil'), findsOneWidget);
      expect(find.text('Recherche'), findsOneWidget);
      expect(find.text('Réservations'), findsOneWidget);
      expect(find.text('Messages'), findsOneWidget);
      expect(find.text('Profil'), findsOneWidget);
    });

    testWidgets('renders 5 tab icons', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestNav());

      expect(find.byIcon(Icons.home_rounded), findsOneWidget);
      expect(find.byIcon(Icons.search_rounded), findsOneWidget);
      expect(find.byIcon(Icons.calendar_today_rounded), findsOneWidget);
      expect(find.byIcon(Icons.chat_bubble_rounded), findsOneWidget);
      expect(find.byIcon(Icons.person_rounded), findsOneWidget);
    });

    testWidgets('calls onTap with correct index', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      var tappedIndex = -1;
      await tester.pumpWidget(
        buildTestNav(onTap: (index) => tappedIndex = index),
      );

      // Tap on the third tab (Réservations)
      await tester.tap(find.text('Réservations'));
      expect(tappedIndex, 2);
    });

    testWidgets('applies BackdropFilter on high tier', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.high;
      await tester.pumpWidget(buildTestNav());

      expect(find.byType(BackdropFilter), findsOneWidget);
      expect(find.byType(ClipRRect), findsOneWidget);
    });

    testWidgets('does NOT apply BackdropFilter on low tier', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestNav());

      expect(find.byType(BackdropFilter), findsNothing);
    });
  });
}
