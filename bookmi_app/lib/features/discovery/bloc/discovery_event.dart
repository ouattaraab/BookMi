import 'package:flutter/foundation.dart';

sealed class DiscoveryEvent {
  const DiscoveryEvent();
}

final class DiscoveryFetched extends DiscoveryEvent {
  const DiscoveryFetched();
}

final class DiscoveryNextPageFetched extends DiscoveryEvent {
  const DiscoveryNextPageFetched();
}

@immutable
final class DiscoveryFiltersChanged extends DiscoveryEvent {
  const DiscoveryFiltersChanged({required this.filters});
  final Map<String, dynamic> filters;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiscoveryFiltersChanged && mapEquals(filters, other.filters);

  @override
  int get hashCode => Object.hashAll(
    filters.entries.map((e) => Object.hash(e.key, e.value)),
  );
}

final class DiscoveryFilterCleared extends DiscoveryEvent {
  const DiscoveryFilterCleared();
}

@immutable
final class DiscoverySearchChanged extends DiscoveryEvent {
  const DiscoverySearchChanged({required this.query});
  final String query;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiscoverySearchChanged && query == other.query;

  @override
  int get hashCode => query.hashCode;
}
