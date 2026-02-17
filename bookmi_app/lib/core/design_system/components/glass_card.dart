import 'dart:ui';

import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/glass.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

class GlassCard extends StatefulWidget {
  const GlassCard({
    required this.child,
    super.key,
    this.backgroundColor,
    this.borderRadius,
    this.borderColor,
    this.blurSigma,
    this.padding,
    this.onTap,
    this.selected = false,
    this.disabled = false,
  });

  final Widget child;
  final Color? backgroundColor;
  final BorderRadius? borderRadius;
  final Color? borderColor;
  final double? blurSigma;
  final EdgeInsetsGeometry? padding;
  final VoidCallback? onTap;
  final bool selected;
  final bool disabled;

  @override
  State<GlassCard> createState() => _GlassCardState();
}

class _GlassCardState extends State<GlassCard> {
  bool _isPressed = false;

  @override
  Widget build(BuildContext context) {
    final tier = GpuTierProvider.detect();
    final effectiveBorderRadius =
        widget.borderRadius ?? BookmiRadius.cardBorder;
    final effectivePadding =
        widget.padding ?? const EdgeInsets.all(BookmiSpacing.spaceBase);

    final effectiveBorderColor = widget.selected
        ? BookmiColors.brandBlue
        : (widget.borderColor ?? BookmiColors.glassBorder);

    final borderWidth = widget.selected ? 2.0 : 1.0;

    final decoration = BoxDecoration(
      borderRadius: effectiveBorderRadius,
      border: Border.all(color: effectiveBorderColor, width: borderWidth),
      color: _getBackgroundColor(tier),
    );

    Widget content = Container(
      decoration: decoration,
      padding: effectivePadding,
      child: widget.child,
    );

    // Apply backdrop blur for tier 2 and 3
    if (tier != GpuTier.low) {
      final sigma = widget.blurSigma ?? _getBlurSigma(tier);
      content = ClipRRect(
        borderRadius: effectiveBorderRadius,
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: sigma, sigmaY: sigma),
          child: content,
        ),
      );
    }

    if (widget.disabled) {
      return Opacity(opacity: 0.4, child: content);
    }

    if (widget.onTap != null) {
      return GestureDetector(
        onTapDown: (_) => setState(() => _isPressed = true),
        onTapUp: (_) {
          setState(() => _isPressed = false);
          widget.onTap!();
        },
        onTapCancel: () => setState(() => _isPressed = false),
        child: AnimatedScale(
          scale: _isPressed ? 0.98 : 1.0,
          duration: const Duration(milliseconds: 100),
          child: AnimatedOpacity(
            opacity: _isPressed ? 0.8 : 1.0,
            duration: const Duration(milliseconds: 100),
            child: content,
          ),
        ),
      );
    }

    return content;
  }

  Color _getBackgroundColor(GpuTier tier) {
    if (widget.backgroundColor != null) return widget.backgroundColor!;
    return switch (tier) {
      GpuTier.high => BookmiColors.glassWhite,
      GpuTier.medium => BookmiColors.glassWhiteMedium,
      GpuTier.low => BookmiColors.brandNavy.withValues(
        alpha: BookmiGlass.opacityTier1,
      ),
    };
  }

  double _getBlurSigma(GpuTier tier) {
    return switch (tier) {
      GpuTier.high => BookmiGlass.blurFull,
      GpuTier.medium => BookmiGlass.blurLight,
      GpuTier.low => BookmiGlass.blurNone,
    };
  }
}
