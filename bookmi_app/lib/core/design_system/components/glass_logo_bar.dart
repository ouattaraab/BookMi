import 'dart:ui';

import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/glass.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class GlassLogoBar extends StatelessWidget implements PreferredSizeWidget {
  const GlassLogoBar({super.key});

  static const double _barHeight = 74.0;

  @override
  Size get preferredSize => const Size.fromHeight(_barHeight);

  @override
  Widget build(BuildContext context) {
    final tier = GpuTierProvider.detect();

    Widget bar = Container(
      height: _barHeight,
      padding: const EdgeInsets.only(top: 16),
      decoration: BoxDecoration(
        color: _getBackgroundColor(tier),
        border: const Border(
          bottom: BorderSide(
            color: Color(0x0FFFFFFF), // rgba(255,255,255,0.06)
            width: 1,
          ),
        ),
      ),
      child: Center(
        child: RichText(
          text: TextSpan(
            children: [
              TextSpan(
                text: 'Book',
                style: GoogleFonts.nunito(
                  fontSize: 45,
                  fontWeight: FontWeight.w900,
                  color: Colors.white,
                  letterSpacing: -1.8,
                ),
              ),
              TextSpan(
                text: 'Mi',
                style: GoogleFonts.nunito(
                  fontSize: 45,
                  fontWeight: FontWeight.w900,
                  color: BookmiColors.brandBlueLight,
                  letterSpacing: -1.8,
                ),
              ),
            ],
          ),
        ),
      ),
    );

    if (tier != GpuTier.low) {
      final sigma = switch (tier) {
        GpuTier.high => BookmiGlass.blurFull,
        GpuTier.medium => BookmiGlass.blurLight,
        GpuTier.low => BookmiGlass.blurNone,
      };
      bar = ClipRect(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: sigma, sigmaY: sigma),
          child: bar,
        ),
      );
    }

    return bar;
  }

  Color _getBackgroundColor(GpuTier tier) {
    return switch (tier) {
      GpuTier.high => const Color(0xBF0D1421), // rgba(13,20,33,0.75)
      GpuTier.medium => const Color(0xCC0D1421), // rgba(13,20,33,0.80)
      GpuTier.low => const Color(0xFF0D1421),
    };
  }
}
