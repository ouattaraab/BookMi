import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('BookmiSpacing', () {
    test('spaceXs is 4', () {
      expect(BookmiSpacing.spaceXs, 4);
    });

    test('spaceSm is 8', () {
      expect(BookmiSpacing.spaceSm, 8);
    });

    test('spaceMd is 12', () {
      expect(BookmiSpacing.spaceMd, 12);
    });

    test('spaceBase is 16', () {
      expect(BookmiSpacing.spaceBase, 16);
    });

    test('spaceLg is 24', () {
      expect(BookmiSpacing.spaceLg, 24);
    });

    test('spaceXl is 32', () {
      expect(BookmiSpacing.spaceXl, 32);
    });

    test('space2xl is 48', () {
      expect(BookmiSpacing.space2xl, 48);
    });

    test('space3xl is 64', () {
      expect(BookmiSpacing.space3xl, 64);
    });

    test('spacing tokens are in ascending order', () {
      final spacings = [
        BookmiSpacing.spaceXs,
        BookmiSpacing.spaceSm,
        BookmiSpacing.spaceMd,
        BookmiSpacing.spaceBase,
        BookmiSpacing.spaceLg,
        BookmiSpacing.spaceXl,
        BookmiSpacing.space2xl,
        BookmiSpacing.space3xl,
      ];
      for (var i = 0; i < spacings.length - 1; i++) {
        expect(spacings[i] < spacings[i + 1], isTrue);
      }
    });
  });
}
