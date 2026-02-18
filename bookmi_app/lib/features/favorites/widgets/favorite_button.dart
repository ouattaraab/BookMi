import 'dart:async';

import 'package:bookmi_app/features/favorites/bloc/favorites_bloc.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_event.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_state.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class FavoriteButton extends StatefulWidget {
  const FavoriteButton({
    required this.talentId,
    this.size = 24.0,
    this.color,
    super.key,
  });

  final int talentId;
  final double size;
  final Color? color;

  @override
  State<FavoriteButton> createState() => _FavoriteButtonState();
}

class _FavoriteButtonState extends State<FavoriteButton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 200),
      vsync: this,
    );
    _scaleAnimation = TweenSequence<double>([
      TweenSequenceItem(tween: Tween(begin: 1, end: 1.3), weight: 50),
      TweenSequenceItem(tween: Tween(begin: 1.3, end: 1), weight: 50),
    ]).animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOut));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onTap() {
    unawaited(HapticFeedback.lightImpact());
    unawaited(_controller.forward(from: 0));
    context.read<FavoritesBloc>().add(FavoriteToggled(widget.talentId));
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<FavoritesBloc, FavoritesState>(
      builder: (context, state) {
        final isFavorite =
            state is FavoritesLoaded &&
            state.favoriteIds.contains(widget.talentId);

        final activeColor = widget.color ?? Colors.red;

        return GestureDetector(
          onTap: _onTap,
          child: ScaleTransition(
            scale: _scaleAnimation,
            child: Icon(
              isFavorite ? Icons.favorite : Icons.favorite_border,
              size: widget.size,
              color: isFavorite ? activeColor : Colors.grey,
            ),
          ),
        );
      },
    );
  }
}
