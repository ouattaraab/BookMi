import 'dart:ui';

import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/glass.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:flutter/material.dart';

class GlassBottomNav extends StatelessWidget {
  const GlassBottomNav({
    required this.currentIndex,
    required this.onTap,
    this.bookingsBadge = 0,
    super.key,
  });

  final int currentIndex;
  final ValueChanged<int> onTap;
  final int bookingsBadge;

  static const _items = <_NavItem>[
    _NavItem(icon: Icons.home_rounded, label: 'Accueil'),
    _NavItem(icon: Icons.search_rounded, label: 'Recherche'),
    _NavItem(icon: Icons.calendar_today_rounded, label: 'Réservations'),
    _NavItem(icon: Icons.chat_bubble_rounded, label: 'Messages'),
    _NavItem(icon: Icons.person_rounded, label: 'Profil'),
  ];

  @override
  Widget build(BuildContext context) {
    final tier = GpuTierProvider.detect();
    final bottomPadding = MediaQuery.of(context).padding.bottom;

    Widget nav = Container(
      height: 64 + bottomPadding,
      padding: EdgeInsets.only(bottom: bottomPadding),
      decoration: BoxDecoration(
        color: _getBackgroundColor(tier),
        borderRadius: BookmiRadius.sheetBorder,
        border: const Border(
          top: BorderSide(color: BookmiColors.glassBorder),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: List.generate(_items.length, (index) {
          final item = _items[index];
          final isActive = index == currentIndex;
          final showBadge = index == 2 && bookingsBadge > 0;
          return Expanded(
            child: GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTap: () => onTap(index),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Stack(
                    clipBehavior: Clip.none,
                    children: [
                      Icon(
                        item.icon,
                        color: isActive
                            ? BookmiColors.brandElectricBlue
                            : Colors.white.withValues(alpha: 0.5),
                        size: 24,
                      ),
                      if (showBadge)
                        Positioned(
                          top: -4,
                          right: -6,
                          child: Container(
                            constraints: const BoxConstraints(
                              minWidth: 16,
                              minHeight: 16,
                            ),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 4,
                            ),
                            decoration: BoxDecoration(
                              color: BookmiColors.brandBlueLight,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              bookingsBadge > 99 ? '99+' : '$bookingsBadge',
                              style: const TextStyle(
                                fontSize: 9,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item.label,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
                      color: isActive
                          ? BookmiColors.brandElectricBlue
                          : Colors.white.withValues(alpha: 0.5),
                    ),
                  ),
                ],
              ),
            ),
          );
        }),
      ),
    );

    if (tier != GpuTier.low) {
      final sigma = switch (tier) {
        GpuTier.high => BookmiGlass.blurFull,
        GpuTier.medium => BookmiGlass.blurLight,
        GpuTier.low => BookmiGlass.blurNone,
      };
      nav = ClipRRect(
        borderRadius: BookmiRadius.sheetBorder,
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: sigma, sigmaY: sigma),
          child: nav,
        ),
      );
    }

    return nav;
  }

  Color _getBackgroundColor(GpuTier tier) {
    return switch (tier) {
      GpuTier.high => BookmiColors.glassDark,
      GpuTier.medium => BookmiColors.glassDark,
      GpuTier.low => BookmiColors.brandNavy.withValues(
        alpha: BookmiGlass.opacityTier1,
      ),
    };
  }
}

class _NavItem {
  const _NavItem({required this.icon, required this.label});
  final IconData icon;
  final String label;
}
