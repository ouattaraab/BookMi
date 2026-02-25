import 'package:flutter/material.dart';

abstract final class BookmiColors {
  // ── Brand Colors ──────────────────────────────────────────────
  static const brandNavy = Color(0xFF1A2744);
  static const brandBlue = Color(0xFF2196F3);
  static const brandBlueDark = Color(0xFF1976D2);
  static const brandBlueLight = Color(0xFF64B5F6);
  static const brandElectricBlue = Color(0xFF38BDF8);
  static const brandBlue50 = Color(0xFFE3F2FD);
  static const ctaOrange = Color(0xFFFF6B35);
  static const ctaOrangeDark = Color(0xFFE55A2B);
  static const ctaOrangeLight = Color(0xFFFF8C5E);

  // ── Background Colors ─────────────────────────────────────────
  static const backgroundDeep = Color(0xFF112044);
  static const backgroundCard = Color(0xFF0F1C3A);

  // ── Semantic Colors ───────────────────────────────────────────
  static const success = Color(0xFF00C853);
  static const warning = Color(0xFFFFB300);
  static const error = Color(0xFFFF1744);
  static const errorLight = Color(0xFFFFEBEE);
  static const info = Color(0xFF2196F3);

  // ── Glass Colors ──────────────────────────────────────────────
  static const glassWhite = Color.fromRGBO(255, 255, 255, 0.06);
  static const glassWhiteMedium = Color.fromRGBO(255, 255, 255, 0.08);
  static const glassWhiteStrong = Color.fromRGBO(255, 255, 255, 0.12);
  static const glassDark = Color.fromRGBO(10, 15, 30, 0.85);
  static const glassDarkMedium = Color.fromRGBO(13, 20, 33, 0.75);
  static const glassBorder = Color.fromRGBO(255, 255, 255, 0.10);
  static const glassBorderBlue = Color.fromRGBO(56, 189, 248, 0.30);

  // ── Gradients ─────────────────────────────────────────────────
  static const gradientHero = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF1A2744), Color(0xFF0F3460)],
  );

  static const gradientBrand = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF1A2744), Color(0xFF2196F3)],
  );

  static const gradientCta = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF2196F3), Color(0xFF64B5F6)],
  );

  static const gradientCard = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [
      Color.fromRGBO(255, 255, 255, 0.20),
      Color.fromRGBO(255, 255, 255, 0.05),
    ],
  );

  static const gradientShield = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF00C853), Color(0xFF2196F3)],
  );

  // ── Category Accent Colors ────────────────────────────────────
  static const categoryDjMusique = Color(0xFF7C4DFF);
  static const categoryGroupeMusical = Color(0xFF1565C0);
  static const categoryHumoriste = Color(0xFFFF4081);
  static const categoryDanseur = Color(0xFF00BFA5);
  static const categoryMcAnimateur = Color(0xFFFFB300);
  static const categoryPhotographe = Color(0xFF536DFE);
  static const categoryDecorateur = Color(0xFFFF6E40);

  /// Returns the accent color for a talent category slug.
  static Color categoryColor(String? slug) {
    return switch (slug) {
      'dj' => categoryDjMusique,
      'groupe-musical' => categoryGroupeMusical,
      'humoriste' => categoryHumoriste,
      'danseur' => categoryDanseur,
      'mc-animateur' => categoryMcAnimateur,
      'photographe' => categoryPhotographe,
      'decorateur' => categoryDecorateur,
      _ => brandBlueLight,
    };
  }
}
