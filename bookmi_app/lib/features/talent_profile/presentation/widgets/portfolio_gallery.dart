import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

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
        final mediaType = item['media_type'] as String? ?? 'image';
        return GestureDetector(
          onTap: () => _handleTap(context, item, index),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(BookmiRadius.image),
            child: switch (mediaType) {
              'link' => _buildLinkTile(item),
              'video' => _buildVideoTile(item),
              _ => _buildImageTile(item),
            },
          ),
        );
      },
    );
  }

  Widget _buildImageTile(Map<String, dynamic> item) {
    final url = item['url'] as String? ?? '';
    return CachedNetworkImage(
      imageUrl: url,
      fit: BoxFit.cover,
      placeholder: (_, _) => const ColoredBox(
        color: BookmiColors.glassDarkMedium,
        child: Center(
          child: Icon(Icons.image, color: Colors.white24, size: 24),
        ),
      ),
      errorWidget: (_, _, _) => const ColoredBox(
        color: BookmiColors.glassDarkMedium,
        child: Center(
          child: Icon(Icons.broken_image, color: Colors.white24, size: 24),
        ),
      ),
    );
  }

  Widget _buildLinkTile(Map<String, dynamic> item) {
    final platform = item['link_platform'] as String? ?? '';
    final icon = switch (platform) {
      'youtube' => Icons.play_circle_filled,
      'instagram' => Icons.camera_alt,
      'facebook' => Icons.facebook,
      'tiktok' => Icons.music_note,
      _ => Icons.link,
    };
    final color = switch (platform) {
      'youtube' => const Color(0xFFFF0000),
      'instagram' => const Color(0xFFE1306C),
      'facebook' => const Color(0xFF1877F2),
      'tiktok' => const Color(0xFF010101),
      _ => BookmiColors.ctaOrange,
    };
    return ColoredBox(
      color: BookmiColors.glassDarkMedium,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 28),
          const SizedBox(height: 4),
          Text(
            platform.isNotEmpty ? platform : 'Lien',
            style: TextStyle(
              fontSize: 10,
              color: Colors.white.withValues(alpha: 0.7),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVideoTile(Map<String, dynamic> item) {
    final url = item['url'] as String? ?? '';
    if (url.isNotEmpty) {
      return Stack(
        fit: StackFit.expand,
        children: [
          CachedNetworkImage(
            imageUrl: url,
            fit: BoxFit.cover,
            errorWidget: (_, _, _) => const ColoredBox(
              color: BookmiColors.glassDarkMedium,
            ),
          ),
          const ColoredBox(
            color: Color(0x66000000),
            child: Center(
              child: Icon(
                Icons.play_circle_filled,
                color: Colors.white70,
                size: 28,
              ),
            ),
          ),
        ],
      );
    }
    return const ColoredBox(
      color: BookmiColors.glassDarkMedium,
      child: Center(
        child: Icon(
          Icons.videocam_outlined,
          color: Colors.white38,
          size: 28,
        ),
      ),
    );
  }

  Future<void> _handleTap(
    BuildContext context,
    Map<String, dynamic> item,
    int index,
  ) async {
    final mediaType = item['media_type'] as String? ?? 'image';
    if (mediaType == 'link') {
      final rawUrl = item['link_url'] as String? ?? '';
      if (rawUrl.isNotEmpty) {
        final uri = Uri.tryParse(rawUrl);
        if (uri != null) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        }
      }
      return;
    }
    final imageItems = items
        .asMap()
        .entries
        .where((e) => (e.value['media_type'] as String? ?? 'image') != 'link')
        .toList();
    final imageIndex = imageItems.indexWhere((e) => e.key == index);
    if (imageIndex >= 0 && context.mounted) {
      await Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => _PortfolioFullscreen(
            items: imageItems.map((e) => e.value).toList(),
            initialIndex: imageIndex,
          ),
        ),
      );
    }
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
