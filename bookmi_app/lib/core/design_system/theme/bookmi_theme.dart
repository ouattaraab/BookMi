import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/typography.dart';
import 'package:flutter/material.dart';

abstract final class BookmiTheme {
  static ThemeData get light => ThemeData(
    useMaterial3: true,
    colorScheme: const ColorScheme.light(
      primary: BookmiColors.brandBlue,
      secondary: BookmiColors.brandBlueLight,
      onSecondary: Colors.white,
      onSurface: BookmiColors.brandNavy,
      error: BookmiColors.error,
    ),
    textTheme: BookmiTypography.textTheme.apply(
      bodyColor: BookmiColors.brandNavy,
      displayColor: BookmiColors.brandNavy,
    ),
    scaffoldBackgroundColor: const Color(0xFFF5F5F5),
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: BookmiColors.brandNavy,
      elevation: 0,
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: BookmiColors.brandBlueLight,
        foregroundColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BookmiRadius.buttonBorder,
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      border: OutlineInputBorder(
        borderRadius: BookmiRadius.inputBorder,
      ),
      filled: true,
    ),
    cardTheme: CardThemeData(
      shape: RoundedRectangleBorder(
        borderRadius: BookmiRadius.cardBorder,
      ),
    ),
    chipTheme: ChipThemeData(
      shape: RoundedRectangleBorder(
        borderRadius: BookmiRadius.chipBorder,
      ),
    ),
  );

  static ThemeData get dark => ThemeData(
    useMaterial3: true,
    colorScheme: const ColorScheme.dark(
      primary: BookmiColors.brandBlue,
      secondary: BookmiColors.brandBlueLight,
      surface: BookmiColors.brandNavy,
      error: BookmiColors.error,
    ),
    textTheme: BookmiTypography.textTheme.apply(
      bodyColor: Colors.white,
      displayColor: Colors.white,
    ),
    scaffoldBackgroundColor: BookmiColors.backgroundDeep,
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.transparent,
      foregroundColor: Colors.white,
      elevation: 0,
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: BookmiColors.brandBlueLight,
        foregroundColor: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BookmiRadius.buttonBorder,
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      border: OutlineInputBorder(
        borderRadius: BookmiRadius.inputBorder,
      ),
      filled: true,
    ),
    cardTheme: CardThemeData(
      shape: RoundedRectangleBorder(
        borderRadius: BookmiRadius.cardBorder,
      ),
    ),
    chipTheme: ChipThemeData(
      shape: RoundedRectangleBorder(
        borderRadius: BookmiRadius.chipBorder,
      ),
    ),
  );
}
