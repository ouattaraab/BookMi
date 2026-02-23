import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _primary = Color(0xFF3B9DF2);
const _border = Color(0xFFE2E8F0);

class FavoritesPage extends StatefulWidget {
  const FavoritesPage({required this.repository, super.key});
  final ProfileRepository repository;

  @override
  State<FavoritesPage> createState() => _FavoritesPageState();
}

class _FavoritesPageState extends State<FavoritesPage> {
  List<Map<String, dynamic>> _favorites = [];
  bool _loading = true;
  String? _error;

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
    final result = await widget.repository.getFavorites();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _favorites = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
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
          'Mes talents favoris',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              _error!,
              style: GoogleFonts.manrope(color: _mutedFg),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: _load,
              child: Text(
                'Réessayer',
                style: GoogleFonts.manrope(color: _primary),
              ),
            ),
          ],
        ),
      );
    }
    if (_favorites.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.favorite_border,
              size: 56,
              color: _mutedFg.withValues(alpha: 0.4),
            ),
            const SizedBox(height: 12),
            Text(
              'Aucun talent en favori',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: _secondary,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Explorez les talents et ajoutez vos favoris.',
              style: GoogleFonts.manrope(
                fontSize: 13,
                color: _mutedFg,
              ),
            ),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _favorites.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final item = _favorites[index];
          final attrs =
              item['attributes'] as Map<String, dynamic>? ?? item;
          final talent =
              attrs['talent'] as Map<String, dynamic>? ?? {};
          final slug = talent['slug'] as String? ?? '';
          final stageName =
              talent['stage_name'] as String? ?? 'Talent';
          final category =
              talent['category'] as String? ?? '';
          final photoUrl = talent['profile_photo_url'] as String?;
          final rating =
              (talent['average_rating'] as num?)?.toDouble() ?? 0;
          final city = talent['city'] as String? ?? '';

          return GestureDetector(
            onTap: slug.isNotEmpty
                ? () => context.pushNamed(
                      RouteNames.talentDetail,
                      pathParameters: {'slug': slug},
                      extra: talent,
                    )
                : null,
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: _border),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    // Avatar
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: photoUrl != null && photoUrl.isNotEmpty
                          ? CachedNetworkImage(
                              imageUrl: photoUrl,
                              width: 56,
                              height: 56,
                              fit: BoxFit.cover,
                              errorWidget: (_, __, ___) =>
                                  _InitialAvatar(name: stageName),
                            )
                          : _InitialAvatar(name: stageName),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            stageName,
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                              color: _secondary,
                            ),
                          ),
                          if (category.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Text(
                              category,
                              style: GoogleFonts.manrope(
                                fontSize: 12,
                                color: _mutedFg,
                              ),
                            ),
                          ],
                          if (city.isNotEmpty) ...[
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                const Icon(
                                  Icons.location_on_outlined,
                                  size: 12,
                                  color: _mutedFg,
                                ),
                                const SizedBox(width: 2),
                                Text(
                                  city,
                                  style: GoogleFonts.manrope(
                                    fontSize: 12,
                                    color: _mutedFg,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                    if (rating > 0) ...[
                      Row(
                        children: [
                          const Icon(
                            Icons.star,
                            size: 14,
                            color: Color(0xFFFBBF24),
                          ),
                          const SizedBox(width: 3),
                          Text(
                            rating.toStringAsFixed(1),
                            style: GoogleFonts.manrope(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: _secondary,
                            ),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(width: 4),
                    const Icon(
                      Icons.chevron_right,
                      size: 18,
                      color: _mutedFg,
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _InitialAvatar extends StatelessWidget {
  const _InitialAvatar({required this.name});
  final String name;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 56,
      height: 56,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFFFF6B35), Color(0xFFC85A20)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : 'T',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}
