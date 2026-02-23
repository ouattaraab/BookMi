import 'package:flutter/foundation.dart';

sealed class DiscoveryState {
  const DiscoveryState();
}

final class DiscoveryInitial extends DiscoveryState {
  const DiscoveryInitial();
}

final class DiscoveryLoading extends DiscoveryState {
  const DiscoveryLoading();
}

@immutable
final class DiscoveryLoaded extends DiscoveryState {
  const DiscoveryLoaded({
    required this.talents,
    required this.hasMore,
    required this.nextCursor,
    required this.activeFilters,
    this.categories = const [],
  });

  final List<Map<String, dynamic>> talents;
  final bool hasMore;
  final String? nextCursor;
  final Map<String, dynamic> activeFilters;

  /// Categories fetched from /categories endpoint.
  final List<Map<String, dynamic>> categories;

  DiscoveryLoaded copyWith({
    List<Map<String, dynamic>>? talents,
    bool? hasMore,
    String? nextCursor,
    Map<String, dynamic>? activeFilters,
    List<Map<String, dynamic>>? categories,
  }) {
    return DiscoveryLoaded(
      talents: talents ?? this.talents,
      hasMore: hasMore ?? this.hasMore,
      nextCursor: nextCursor ?? this.nextCursor,
      activeFilters: activeFilters ?? this.activeFilters,
      categories: categories ?? this.categories,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other.runtimeType == runtimeType &&
          other is DiscoveryLoaded &&
          listEquals(talents, other.talents) &&
          hasMore == other.hasMore &&
          nextCursor == other.nextCursor &&
          mapEquals(activeFilters, other.activeFilters) &&
          listEquals(categories, other.categories);

  @override
  int get hashCode => Object.hash(
    runtimeType,
    Object.hashAll(talents),
    hasMore,
    nextCursor,
    Object.hashAll(
      activeFilters.entries.map((e) => Object.hash(e.key, e.value)),
    ),
    Object.hashAll(categories),
  );
}

final class DiscoveryLoadingMore extends DiscoveryLoaded {
  const DiscoveryLoadingMore({
    required super.talents,
    required super.hasMore,
    required super.nextCursor,
    required super.activeFilters,
    super.categories,
  });
}

@immutable
final class DiscoveryFailure extends DiscoveryState {
  const DiscoveryFailure({
    required this.code,
    required this.message,
  });

  final String code;
  final String message;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiscoveryFailure &&
          code == other.code &&
          message == other.message;

  @override
  int get hashCode => Object.hash(code, message);
}
