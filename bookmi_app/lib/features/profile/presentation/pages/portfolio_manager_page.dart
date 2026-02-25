import 'dart:io';

import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';

const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _primary = Color(0xFF3B9DF2);
const _border = Color(0xFFE2E8F0);
const _destructive = Color(0xFFEF4444);

class PortfolioManagerPage extends StatefulWidget {
  const PortfolioManagerPage({required this.repository, super.key});
  final ProfileRepository repository;

  @override
  State<PortfolioManagerPage> createState() =>
      _PortfolioManagerPageState();
}

class _PortfolioManagerPageState extends State<PortfolioManagerPage> {
  List<Map<String, dynamic>> _items = [];
  bool _loading = true;
  String? _error;
  bool _uploading = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await widget.repository.getPortfolio();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _items = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  Future<void> _pickAndUpload() async {
    if (!mounted) return;

    // Step 1 — choose source
    final source = await showModalBottomSheet<_MediaSource?>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 36,
              height: 4,
              margin: const EdgeInsets.only(top: 12, bottom: 8),
              decoration: BoxDecoration(
                color: _border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 4),
              child: Text(
                'Ajouter au portfolio',
                style: GoogleFonts.plusJakartaSans(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                    color: _secondary),
              ),
            ),
            const Divider(height: 16),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined,
                  color: _primary),
              title: Text('Photo depuis la galerie',
                  style: GoogleFonts.manrope()),
              onTap: () =>
                  Navigator.of(ctx).pop(_MediaSource.imageGallery),
            ),
            ListTile(
              leading: const Icon(Icons.videocam_outlined,
                  color: _primary),
              title: Text('Vidéo depuis la galerie',
                  style: GoogleFonts.manrope()),
              onTap: () =>
                  Navigator.of(ctx).pop(_MediaSource.videoGallery),
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined,
                  color: _primary),
              title: Text('Prendre une photo',
                  style: GoogleFonts.manrope()),
              onTap: () =>
                  Navigator.of(ctx).pop(_MediaSource.imageCamera),
            ),
            ListTile(
              leading: const Icon(Icons.videocam, color: _primary),
              title: Text('Enregistrer une vidéo',
                  style: GoogleFonts.manrope()),
              onTap: () =>
                  Navigator.of(ctx).pop(_MediaSource.videoCamera),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );

    if (source == null || !mounted) return;

    final picker = ImagePicker();
    XFile? picked;

    switch (source) {
      case _MediaSource.imageGallery:
        picked = await picker.pickImage(
            source: ImageSource.gallery, imageQuality: 85);
      case _MediaSource.imageCamera:
        picked = await picker.pickImage(
            source: ImageSource.camera, imageQuality: 85);
      case _MediaSource.videoGallery:
        picked = await picker.pickVideo(source: ImageSource.gallery);
      case _MediaSource.videoCamera:
        picked = await picker.pickVideo(source: ImageSource.camera);
    }

    if (picked == null || !mounted) return;

    setState(() => _uploading = true);

    final captionController = TextEditingController();
    final caption = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Légende (optionnel)',
          style: GoogleFonts.plusJakartaSans(
              fontWeight: FontWeight.w700, color: _secondary),
        ),
        content: TextField(
          controller: captionController,
          decoration: InputDecoration(
            hintText: 'Ex: Performance DJ à Abidjan...',
            hintStyle: GoogleFonts.manrope(color: _mutedFg),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
            ),
          ),
          maxLength: 120,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(null),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () =>
                Navigator.of(ctx).pop(captionController.text.trim()),
            child: Text(
              'Ajouter',
              style: GoogleFonts.manrope(
                  color: _primary, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );

    if (!mounted) {
      setState(() => _uploading = false);
      return;
    }

    final result = await widget.repository.addPortfolioItem(
      file: File(picked.path),
      caption: caption?.isNotEmpty == true ? caption : null,
    );

    if (!mounted) return;
    setState(() => _uploading = false);

    switch (result) {
      case ApiSuccess():
        await _load();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Média ajouté au portfolio')),
          );
        }
      case ApiFailure(:final message):
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(message),
              backgroundColor: _destructive,
            ),
          );
        }
    }
  }

  Future<void> _delete(int itemId) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Supprimer cette photo ?',
          style: GoogleFonts.plusJakartaSans(
              fontWeight: FontWeight.w700, color: _secondary),
        ),
        content: Text(
          'Cette action est irréversible.',
          style: GoogleFonts.manrope(color: _mutedFg),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(
              'Supprimer',
              style: GoogleFonts.manrope(
                  color: _destructive, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    final result = await widget.repository.deletePortfolioItem(itemId);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess():
        setState(() => _items.removeWhere(
              (i) => (i['id'] as int?) == itemId,
            ));
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Photo supprimée')),
        );
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: _destructive,
          ),
        );
    }
  }

  Future<void> _openItem({
    required String mediaUrl,
    required String mediaType,
    required String caption,
  }) async {
    if (mediaUrl.isEmpty) return;

    if (mediaType == 'image') {
      // Full-screen image viewer
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        barrierColor: Colors.black87,
        builder: (ctx) => _FullScreenImageViewer(
          imageUrl: mediaUrl,
          caption: caption,
        ),
      );
      return;
    }

    // Video or link — open with external app/browser
    final uri = Uri.tryParse(mediaUrl);
    if (uri == null) return;
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text("Impossible d'ouvrir ce lien."),
          backgroundColor: _destructive,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _muted,
      appBar: AppBar(
        backgroundColor: _secondary,
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          'Gestion portfolio',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, size: 20),
            onPressed: _load,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _uploading ? null : _pickAndUpload,
        backgroundColor: _primary,
        icon: _uploading
            ? const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 2,
                ),
              )
            : const Icon(Icons.add_photo_alternate, color: Colors.white),
        label: Text(
          _uploading ? 'Envoi...' : 'Ajouter un média',
          style: GoogleFonts.manrope(
              color: Colors.white, fontWeight: FontWeight.w600),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? _buildError()
              : _items.isEmpty
                  ? _buildEmpty()
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: GridView.builder(
                        padding: const EdgeInsets.all(16),
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                          childAspectRatio: 1,
                        ),
                        itemCount: _items.length,
                        itemBuilder: (context, index) {
                          final item = _items[index];
                          final id = item['id'] as int? ?? 0;
                          final attrs = item['attributes'] as Map<String, dynamic>? ?? item;
                          final mediaUrl = attrs['media_url'] as String? ??
                              attrs['url'] as String? ?? '';
                          final caption = attrs['caption'] as String? ?? '';
                          final isApproved = attrs['is_approved'] as bool? ?? true;
                          final status = isApproved ? 'approved' : 'pending';
                          final mediaType = attrs['media_type'] as String? ?? 'image';

                          return _PortfolioItem(
                            mediaUrl: mediaUrl,
                            caption: caption,
                            status: status,
                            mediaType: mediaType,
                            onDelete: () => _delete(id),
                            onTap: () => _openItem(
                              mediaUrl: mediaUrl,
                              mediaType: mediaType,
                              caption: caption,
                            ),
                          );
                        },
                      ),
                    ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(_error!,
              style: GoogleFonts.manrope(color: _mutedFg),
              textAlign: TextAlign.center),
          const SizedBox(height: 12),
          TextButton(
            onPressed: _load,
            child: Text('Réessayer',
                style: GoogleFonts.manrope(color: _primary)),
          ),
        ],
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.photo_library_outlined,
              size: 56, color: _mutedFg.withValues(alpha: 0.4)),
          const SizedBox(height: 12),
          Text(
            'Portfolio vide',
            style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: _secondary),
          ),
          const SizedBox(height: 6),
          Text(
            'Ajoutez des photos de vos prestations.',
            style: GoogleFonts.manrope(fontSize: 13, color: _mutedFg),
          ),
        ],
      ),
    );
  }
}

enum _MediaSource {
  imageGallery,
  imageCamera,
  videoGallery,
  videoCamera,
}

class _PortfolioItem extends StatelessWidget {
  const _PortfolioItem({
    required this.mediaUrl,
    required this.caption,
    required this.status,
    required this.mediaType,
    required this.onDelete,
    required this.onTap,
  });

  final String mediaUrl;
  final String caption;
  final String status;
  final String mediaType;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isLink = mediaType == 'link';
    final isVideo = mediaType == 'video';

    return GestureDetector(
      onTap: onTap,
      child: Stack(
      fit: StackFit.expand,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: isLink
              ? _buildLinkTile()
              : mediaUrl.isNotEmpty
                  ? CachedNetworkImage(
                      imageUrl: mediaUrl,
                      fit: BoxFit.cover,
                      placeholder: (_, __) =>
                          Container(color: _border),
                      errorWidget: (_, __, ___) => Container(
                        color: _border,
                        child: const Icon(Icons.image_not_supported,
                            color: _mutedFg),
                      ),
                    )
                  : Container(
                      color: _border,
                      child: const Icon(Icons.image, color: _mutedFg, size: 40),
                    ),
        ),
        // Video badge
        if (isVideo)
          Positioned(
            top: 8,
            left: 8,
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.65),
                borderRadius: BorderRadius.circular(6),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.play_circle_outline,
                      size: 12, color: Colors.white),
                  SizedBox(width: 3),
                  Text('Vidéo',
                      style: TextStyle(
                          fontSize: 9,
                          color: Colors.white,
                          fontWeight: FontWeight.w600)),
                ],
              ),
            ),
          ),
        // Status chip
        if (status != 'approved')
          Positioned(
            top: 8,
            left: 8,
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: status == 'pending'
                    ? Colors.orange
                    : _destructive,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                status == 'pending' ? 'En attente' : 'Rejeté',
                style: GoogleFonts.manrope(
                    fontSize: 9,
                    color: Colors.white,
                    fontWeight: FontWeight.w600),
              ),
            ),
          ),
        // Caption overlay
        if (caption.isNotEmpty)
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: ClipRRect(
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(12),
                bottomRight: Radius.circular(12),
              ),
              child: Container(
                color: Colors.black.withValues(alpha: 0.5),
                padding: const EdgeInsets.all(6),
                child: Text(
                  caption,
                  style: GoogleFonts.manrope(
                      fontSize: 10, color: Colors.white),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ),
          ),
        // Delete button — stops propagation to onTap
        Positioned(
          top: 6,
          right: 6,
          child: GestureDetector(
            onTap: onDelete,
            behavior: HitTestBehavior.opaque,
            child: Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.55),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.delete_outline,
                  size: 16, color: Colors.white),
            ),
          ),
        ),
        // Tap hint overlay (play icon for video/link)
        if (mediaType == 'video' || mediaType == 'link')
          Center(
            child: Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.45),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.play_arrow_rounded,
                  size: 30, color: Colors.white),
            ),
          ),
      ],
      ),
    );
  }

  Widget _buildLinkTile() {
    final isYoutube = mediaUrl.contains('youtube') ||
        mediaUrl.contains('youtu.be');
    final isSoundcloud = mediaUrl.contains('soundcloud');
    final isDeezer = mediaUrl.contains('deezer');

    Color bg;
    IconData icon;
    String label;

    if (isYoutube) {
      bg = const Color(0xFFFF0000);
      icon = Icons.play_circle_filled;
      label = 'YouTube';
    } else if (isSoundcloud) {
      bg = const Color(0xFFFF7700);
      icon = Icons.audiotrack;
      label = 'SoundCloud';
    } else if (isDeezer) {
      bg = const Color(0xFF9B59B6);
      icon = Icons.music_note;
      label = 'Deezer';
    } else {
      bg = _secondary;
      icon = Icons.link;
      label = 'Lien';
    }

    return Container(
      color: bg.withValues(alpha: 0.12),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 40, color: bg),
          const SizedBox(height: 6),
          Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: 11,
              color: bg,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Full-screen image viewer ──────────────────────────────────────────────

class _FullScreenImageViewer extends StatelessWidget {
  const _FullScreenImageViewer({
    required this.imageUrl,
    required this.caption,
  });

  final String imageUrl;
  final String caption;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.black,
      insetPadding: EdgeInsets.zero,
      child: Stack(
        fit: StackFit.expand,
        children: [
          // Zoomable image
          InteractiveViewer(
            minScale: 0.5,
            maxScale: 5.0,
            child: Center(
              child: CachedNetworkImage(
                imageUrl: imageUrl,
                fit: BoxFit.contain,
                placeholder: (_, __) => const Center(
                  child: CircularProgressIndicator(color: Colors.white54),
                ),
                errorWidget: (_, __, ___) => const Center(
                  child: Icon(
                    Icons.image_not_supported_outlined,
                    color: Colors.white54,
                    size: 56,
                  ),
                ),
              ),
            ),
          ),
          // Caption bottom gradient
          if (caption.isNotEmpty)
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: Container(
                padding: const EdgeInsets.fromLTRB(20, 12, 64, 20),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.bottomCenter,
                    end: Alignment.topCenter,
                    colors: [
                      Colors.black.withValues(alpha: 0.75),
                      Colors.transparent,
                    ],
                  ),
                ),
                child: Text(
                  caption,
                  style: GoogleFonts.manrope(
                    color: Colors.white,
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                  ),
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ),
          // Close button — uses SafeArea so it clears the status bar
          Positioned(
            top: 0,
            right: 0,
            child: SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(8),
                child: GestureDetector(
                  onTap: () => Navigator.of(context).pop(),
                  behavior: HitTestBehavior.opaque,
                  child: Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.65),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                        Icons.close, color: Colors.white, size: 22),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
