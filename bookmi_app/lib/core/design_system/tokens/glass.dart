abstract final class BookmiGlass {
  // ── Blur values per GPU tier ──────────────────────────────────
  /// Tier 3 (GPU puissant): Full glassmorphism
  static const double blurFull = 20;

  /// Tier 2 (GPU moyen): Blur réduit
  static const double blurLight = 10;

  /// Tier 1 (GPU faible): Pas de blur
  static const double blurNone = 0;

  // ── Opacity values per GPU tier ───────────────────────────────
  /// Tier 3: Standard glass opacity
  static const double opacityTier3 = 0.15;

  /// Tier 2: Increased opacity to compensate for reduced blur
  static const double opacityTier2 = 0.25;

  /// Tier 1: Solid fallback opacity
  static const double opacityTier1 = 0.85;

  // ── Animation durations ───────────────────────────────────────
  static const Duration scrollTransition = Duration(milliseconds: 200);
}
