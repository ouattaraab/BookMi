import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/foundation.dart';

/// GPU tier for glassmorphism degradation strategy.
///
/// - [high]: Full BackdropFilter blur(20).
/// - [medium]: Reduced blur(10) + increased opacity.
/// - [low]: No blur, solid semi-transparent fallback.
enum GpuTier { high, medium, low }

class GpuTierProvider {
  GpuTierProvider._();

  static GpuTier? _cachedTier;

  /// Detects the GPU tier based on device capabilities.
  ///
  /// Uses physical screen pixels and platform heuristics.
  /// Result is cached after first call.
  static GpuTier detect() {
    if (_cachedTier != null) return _cachedTier!;
    _cachedTier = _detectTier();
    return _cachedTier!;
  }

  /// Allow overriding for testing.
  @visibleForTesting
  static GpuTier? get tierForTesting => _cachedTier;

  @visibleForTesting
  static set tierForTesting(GpuTier tier) {
    _cachedTier = tier;
  }

  /// Reset cached tier (for testing).
  @visibleForTesting
  static void resetForTesting() {
    _cachedTier = null;
  }

  static GpuTier _detectTier() {
    // On web, always use medium for broad compatibility
    if (kIsWeb) return GpuTier.medium;

    try {
      final display = ui.PlatformDispatcher.instance.displays.firstOrNull;
      if (display == null) return GpuTier.medium;

      final physicalWidth = display.size.width;
      final devicePixelRatio = display.devicePixelRatio;

      // iOS devices with 3x display are typically high-end
      if (Platform.isIOS && devicePixelRatio >= 3) {
        return GpuTier.high;
      }

      // Large resolution Android devices are typically high-end
      if (Platform.isAndroid) {
        final totalPixels = physicalWidth * display.size.height;
        // > 2M physical pixels = likely high-end
        if (totalPixels > 2000000) return GpuTier.high;
        // > 1M physical pixels = likely mid-range
        if (totalPixels > 1000000) return GpuTier.medium;
        return GpuTier.low;
      }

      // Desktop platforms: high tier
      return GpuTier.high;
    } on Exception {
      return GpuTier.medium;
    }
  }
}
