import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('GlassCard', () {
    tearDown(GpuTierProvider.resetForTesting);

    Widget buildTestCard({
      bool selected = false,
      bool disabled = false,
      VoidCallback? onTap,
    }) {
      return MaterialApp(
        home: Scaffold(
          body: GlassCard(
            selected: selected,
            disabled: disabled,
            onTap: onTap,
            child: const Text('Test'),
          ),
        ),
      );
    }

    testWidgets('renders child widget', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestCard());
      expect(find.text('Test'), findsOneWidget);
    });

    testWidgets('applies BackdropFilter on high tier', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.high;
      await tester.pumpWidget(buildTestCard());

      expect(find.byType(BackdropFilter), findsOneWidget);
      expect(find.byType(ClipRRect), findsOneWidget);
    });

    testWidgets('applies BackdropFilter on medium tier', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.medium;
      await tester.pumpWidget(buildTestCard());

      expect(find.byType(BackdropFilter), findsOneWidget);
    });

    testWidgets('does NOT apply BackdropFilter on low tier', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestCard());

      expect(find.byType(BackdropFilter), findsNothing);
      expect(find.byType(ClipRRect), findsNothing);
    });

    testWidgets('applies opacity when disabled', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestCard(disabled: true));

      final opacity = tester.widget<Opacity>(find.byType(Opacity));
      expect(opacity.opacity, 0.4);
    });

    testWidgets('wraps with GestureDetector when onTap provided', (
      tester,
    ) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      var tapped = false;
      await tester.pumpWidget(buildTestCard(onTap: () => tapped = true));

      await tester.tap(find.byType(GestureDetector));
      await tester.pumpAndSettle();
      expect(tapped, isTrue);
    });

    testWidgets('applies pressed state animation (scale + opacity)', (
      tester,
    ) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestCard(onTap: () {}));

      expect(find.byType(AnimatedScale), findsOneWidget);
      expect(find.byType(AnimatedOpacity), findsOneWidget);
    });

    testWidgets('uses brand blue border when selected', (tester) async {
      GpuTierProvider.tierForTesting = GpuTier.low;
      await tester.pumpWidget(buildTestCard(selected: true));

      final container = tester.widget<Container>(find.byType(Container).last);
      final decoration = container.decoration! as BoxDecoration;
      final border = decoration.border! as Border;
      expect(border.top.color, BookmiColors.brandBlue);
      expect(border.top.width, 2.0);
    });
  });
}
