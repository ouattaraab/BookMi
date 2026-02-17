import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('BookmiColors', () {
    group('brand colors', () {
      test('brandNavy has correct hex value', () {
        expect(BookmiColors.brandNavy, const Color(0xFF1A2744));
      });

      test('brandBlue has correct hex value', () {
        expect(BookmiColors.brandBlue, const Color(0xFF2196F3));
      });

      test('brandBlueDark has correct hex value', () {
        expect(BookmiColors.brandBlueDark, const Color(0xFF1976D2));
      });

      test('brandBlueLight has correct hex value', () {
        expect(BookmiColors.brandBlueLight, const Color(0xFF64B5F6));
      });

      test('brandBlue50 has correct hex value', () {
        expect(BookmiColors.brandBlue50, const Color(0xFFE3F2FD));
      });

      test('ctaOrange has correct hex value', () {
        expect(BookmiColors.ctaOrange, const Color(0xFFFF6B35));
      });

      test('ctaOrangeDark has correct hex value', () {
        expect(BookmiColors.ctaOrangeDark, const Color(0xFFE55A2B));
      });

      test('ctaOrangeLight has correct hex value', () {
        expect(BookmiColors.ctaOrangeLight, const Color(0xFFFF8C5E));
      });
    });

    group('semantic colors', () {
      test('success has correct hex value', () {
        expect(BookmiColors.success, const Color(0xFF00C853));
      });

      test('warning has correct hex value', () {
        expect(BookmiColors.warning, const Color(0xFFFFB300));
      });

      test('error has correct hex value', () {
        expect(BookmiColors.error, const Color(0xFFFF1744));
      });

      test('errorLight has correct hex value', () {
        expect(BookmiColors.errorLight, const Color(0xFFFFEBEE));
      });

      test('info has correct hex value', () {
        expect(BookmiColors.info, const Color(0xFF2196F3));
      });
    });

    group('glass colors', () {
      test('glassWhite has correct RGBA', () {
        expect(BookmiColors.glassWhite.r, closeTo(1.0, 0.01));
        expect(BookmiColors.glassWhite.g, closeTo(1.0, 0.01));
        expect(BookmiColors.glassWhite.b, closeTo(1.0, 0.01));
        expect(BookmiColors.glassWhite.a, closeTo(0.15, 0.01));
      });

      test('glassDark has correct RGBA', () {
        expect(BookmiColors.glassDark.a, closeTo(0.80, 0.01));
      });

      test('glassBorder has correct RGBA', () {
        expect(BookmiColors.glassBorder.a, closeTo(0.25, 0.01));
      });

      test('glassBorderBlue has correct RGBA', () {
        expect(BookmiColors.glassBorderBlue.a, closeTo(0.30, 0.01));
      });
    });

    group('gradients', () {
      test('gradientHero has 2 colors', () {
        expect(BookmiColors.gradientHero.colors.length, 2);
      });

      test('gradientBrand has 2 colors', () {
        expect(BookmiColors.gradientBrand.colors.length, 2);
      });

      test('gradientCta starts with ctaOrange', () {
        expect(
          BookmiColors.gradientCta.colors.first,
          const Color(0xFFFF6B35),
        );
      });

      test('gradientCard has 2 colors', () {
        expect(BookmiColors.gradientCard.colors.length, 2);
      });

      test('gradientShield has 2 colors', () {
        expect(BookmiColors.gradientShield.colors.length, 2);
      });
    });

    group('category accent colors', () {
      test('all 7 category colors are defined', () {
        final categories = [
          BookmiColors.categoryDjMusique,
          BookmiColors.categoryGroupeMusical,
          BookmiColors.categoryHumoriste,
          BookmiColors.categoryDanseur,
          BookmiColors.categoryMcAnimateur,
          BookmiColors.categoryPhotographe,
          BookmiColors.categoryDecorateur,
        ];
        expect(categories.length, 7);
        for (final color in categories) {
          expect(color, isA<Color>());
          expect(color.a, 1.0);
        }
      });
    });
  });
}
