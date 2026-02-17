import 'package:bookmi_app/core/design_system/components/glass_app_bar.dart';
import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('GlassAppBar', () {
    tearDown(GpuTierProvider.resetForTesting);

    Widget buildTestAppBar({double scrollOffset = 0}) {
      return MaterialApp(
        home: Scaffold(
          appBar: GlassAppBar(
            title: const Text('Test Title'),
            scrollOffset: scrollOffset,
          ),
        ),
      );
    }

    testWidgets('implements PreferredSizeWidget', (tester) async {
      const appBar = GlassAppBar();
      expect(appBar, isA<PreferredSizeWidget>());
      expect(appBar.preferredSize.height, kToolbarHeight);
    });

    testWidgets('renders title', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestAppBar());
      expect(find.text('Test Title'), findsOneWidget);
    });

    testWidgets('is transparent at scrollOffset 0', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestAppBar());

      // No BackdropFilter when not scrolled (progress = 0)
      expect(find.byType(BackdropFilter), findsNothing);
    });

    testWidgets('applies BackdropFilter when scrolled on high tier', (
      tester,
    ) async {
      GpuTierProvider.tierForTesting = GpuTier.high;
      await tester.pumpWidget(buildTestAppBar(scrollOffset: 100));

      expect(find.byType(BackdropFilter), findsOneWidget);
    });

    testWidgets('no BackdropFilter on low tier even when scrolled', (
      tester,
    ) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestAppBar(scrollOffset: 100));

      expect(find.byType(BackdropFilter), findsNothing);
    });

    testWidgets('renders actions when provided', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            appBar: GlassAppBar(
              actions: [
                IconButton(
                  icon: const Icon(Icons.search),
                  onPressed: () {},
                ),
              ],
            ),
          ),
        ),
      );
      expect(find.byIcon(Icons.search), findsOneWidget);
    });
  });
}
