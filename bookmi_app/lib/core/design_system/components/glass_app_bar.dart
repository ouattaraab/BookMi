import 'dart:ui';

import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/glass.dart';
import 'package:flutter/material.dart';

class GlassAppBar extends StatelessWidget implements PreferredSizeWidget {
  const GlassAppBar({
    super.key,
    this.title,
    this.actions,
    this.leading,
    this.scrollOffset = 0,
  });

  final Widget? title;
  final List<Widget>? actions;
  final Widget? leading;

  /// Scroll offset to control glass transition.
  /// 0 = transparent, >=100 = full glass.
  final double scrollOffset;

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);

  @override
  Widget build(BuildContext context) {
    final tier = GpuTierProvider.detect();
    final progress = (scrollOffset / 100).clamp(0.0, 1.0);

    final backgroundColor = Color.lerp(
      Colors.transparent,
      BookmiColors.glassDark,
      progress,
    )!;

    Widget appBar = AppBar(
      title: title,
      leading: leading,
      actions: actions,
      backgroundColor: backgroundColor,
      elevation: 0,
      foregroundColor: Colors.white,
    );

    // Apply blur only when scrolled and GPU supports it
    if (progress > 0 && tier != GpuTier.low) {
      final sigma = switch (tier) {
        GpuTier.high => BookmiGlass.blurFull * progress,
        GpuTier.medium => BookmiGlass.blurLight * progress,
        GpuTier.low => BookmiGlass.blurNone,
      };

      appBar = ClipRect(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: sigma, sigmaY: sigma),
          child: appBar,
        ),
      );
    }

    return appBar;
  }
}
