import 'package:flutter/material.dart';

abstract final class BookmiRadius {
  static const double card = 24;
  static const double button = 16;
  static const double input = 12;
  static const double chip = 999;
  static const double sheet = 24;
  static const double avatar = 999;
  static const double image = 16;

  static final BorderRadius cardBorder = BorderRadius.circular(card);
  static final BorderRadius buttonBorder = BorderRadius.circular(button);
  static final BorderRadius inputBorder = BorderRadius.circular(input);
  static final BorderRadius chipBorder = BorderRadius.circular(chip);
  static const BorderRadius sheetBorder = BorderRadius.only(
    topLeft: Radius.circular(sheet),
    topRight: Radius.circular(sheet),
  );
  static final BorderRadius imageBorder = BorderRadius.circular(image);
}
