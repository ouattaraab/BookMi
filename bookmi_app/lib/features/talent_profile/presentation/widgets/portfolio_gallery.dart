import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

class PortfolioGallery extends StatelessWidget {
  const PortfolioGallery({required this.items, super.key});

  final List<Map<String, dynamic>> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return _buildEmptyState();
    }

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        mainAxisSpacing: 4,
        crossAxisSpacing: 4,
      ),
      itemCount: items.length,
      itemBuilder: (context, index) {
        final item = items[index];
        final url = item['url'] as String? ?? '';
        return GestureDetector(
          onTap: () => _openFullscreen(context, index),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(BookmiRadius.image),
            child: CachedNetworkImage(
              imageUrl: url,
              fit: BoxFit.cover,
              placeholder: (_, _) => const ColoredBox(
                color: BookmiColors.glassDarkMedium,
                child: Center(
                  child: Icon(
                    Icons.image,
                    color: Colors.white24,
                    size: 24,
                  ),
                ),
              ),
              errorWidget: (_, _, _) => const ColoredBox(
                color: BookmiColors.glassDarkMedium,
                child: Center(
                  child: Icon(
                    Icons.broken_image,
                    color: Colors.white24,
                    size: 24,
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  Future<void> _openFullscreen(BuildContext context, int initialIndex) {
    return Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _PortfolioFullscreen(
          items: items,
          initialIndex: initialIndex,
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: BookmiSpacing.spaceLg),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.photo_library_outlined,
            size: 48,
            color: Colors.white.withValues(alpha: 0.3),
          ),
          const SizedBox(height: BookmiSpacing.spaceSm),
          Text(
            'Pas encore de portfolio',
            style: TextStyle(
              fontSize: 14,
              color: Colors.white.withValues(alpha: 0.6),
            ),
          ),
        ],
      ),
    );
  }
}

class _PortfolioFullscreen extends StatelessWidget {
  const _PortfolioFullscreen({
    required this.items,
    required this.initialIndex,
  });

  final List<Map<String, dynamic>> items;
  final int initialIndex;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: PageView.builder(
        controller: PageController(initialPage: initialIndex),
        itemCount: items.length,
        itemBuilder: (context, index) {
          final url = items[index]['url'] as String? ?? '';
          return Center(
            child: CachedNetworkImage(
              imageUrl: url,
              fit: BoxFit.contain,
              placeholder: (_, _) => const CircularProgressIndicator(
                color: Colors.white,
              ),
              errorWidget: (_, _, _) => const Icon(
                Icons.broken_image,
                color: Colors.white38,
                size: 64,
              ),
            ),
          );
        },
      ),
    );
  }
}
