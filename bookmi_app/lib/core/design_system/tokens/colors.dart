import 'package:flutter/material.dart';

abstract final class BookmiColors {
  // ── Brand Colors ──────────────────────────────────────────────
  static const brandNavy = Color(0xFF1A2744);
  static const brandBlue = Color(0xFF2196F3);
  static const brandBlueDark = Color(0xFF1976D2);
  static const brandBlueLight = Color(0xFF64B5F6);
  static const brandBlue50 = Color(0xFFE3F2FD);
  static const ctaOrange = Color(0xFFFF6B35);
  static const ctaOrangeDark = Color(0xFFE55A2B);
  static const ctaOrangeLight = Color(0xFFFF8C5E);

  // ── Semantic Colors ───────────────────────────────────────────
  static const success = Color(0xFF00C853);
  static const warning = Color(0xFFFFB300);
  static const error = Color(0xFFFF1744);
  static const errorLight = Color(0xFFFFEBEE);
  static const info = Color(0xFF2196F3);

  // ── Glass Colors ──────────────────────────────────────────────
  static const glassWhite = Color.fromRGBO(255, 255, 255, 0.15);
  static const glassWhiteMedium = Color.fromRGBO(255, 255, 255, 0.25);
  static const glassWhiteStrong = Color.fromRGBO(255, 255, 255, 0.40);
  static const glassDark = Color.fromRGBO(26, 39, 68, 0.80);
  static const glassDarkMedium = Color.fromRGBO(26, 39, 68, 0.60);
  static const glassBorder = Color.fromRGBO(255, 255, 255, 0.25);
  static const glassBorderBlue = Color.fromRGBO(33, 150, 243, 0.30);

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
    colors: [Color(0xFFFF6B35), Color(0xFFFF8C5E)],
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
}
