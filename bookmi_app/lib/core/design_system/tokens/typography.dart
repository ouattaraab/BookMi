import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

abstract final class BookmiTypography {
  static TextTheme get textTheme => TextTheme(
    displayLarge: GoogleFonts.nunito(
      fontSize: 36,
      fontWeight: FontWeight.w700,
      height: 44 / 36,
      letterSpacing: -0.5,
    ),
    displayMedium: GoogleFonts.nunito(
      fontSize: 32,
      fontWeight: FontWeight.w700,
      height: 40 / 32,
      letterSpacing: -0.25,
    ),
    headlineLarge: GoogleFonts.nunito(
      fontSize: 24,
      fontWeight: FontWeight.w600,
      height: 32 / 24,
    ),
    titleLarge: GoogleFonts.nunito(
      fontSize: 20,
      fontWeight: FontWeight.w600,
      height: 28 / 20,
    ),
    titleMedium: GoogleFonts.nunito(
      fontSize: 16,
      fontWeight: FontWeight.w600,
      height: 24 / 16,
      letterSpacing: 0.15,
    ),
    bodyLarge: GoogleFonts.nunito(
      fontSize: 16,
      fontWeight: FontWeight.w400,
      height: 24 / 16,
      letterSpacing: 0.5,
    ),
    bodyMedium: GoogleFonts.nunito(
      fontSize: 14,
      fontWeight: FontWeight.w400,
      height: 20 / 14,
      letterSpacing: 0.25,
    ),
    labelLarge: GoogleFonts.nunito(
      fontSize: 14,
      fontWeight: FontWeight.w500,
      height: 20 / 14,
      letterSpacing: 0.1,
    ),
    labelMedium: GoogleFonts.nunito(
      fontSize: 12,
      fontWeight: FontWeight.w500,
      height: 16 / 12,
      letterSpacing: 0.5,
    ),
    bodySmall: GoogleFonts.nunito(
      fontSize: 12,
      fontWeight: FontWeight.w400,
      height: 16 / 12,
      letterSpacing: 0.4,
    ),
    labelSmall: GoogleFonts.nunito(
      fontSize: 10,
      fontWeight: FontWeight.w600,
      height: 14 / 10,
      letterSpacing: 1.5,
    ),
  );
}
