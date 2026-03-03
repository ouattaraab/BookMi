class AppVersionModel {
  const AppVersionModel({
    required this.maintenance,
    this.maintenanceMessage,
    this.maintenanceEndAt,
    required this.versionRequired,
    required this.updateType,
    this.updateMessage,
    required this.features,
    this.androidStoreUrl,
    this.iosStoreUrl,
  });

  final bool maintenance;
  final String? maintenanceMessage;
  final DateTime? maintenanceEndAt;
  final String versionRequired;

  /// 'none' | 'optional' | 'forced'
  final String updateType;
  final String? updateMessage;
  final List<String> features;
  final String? androidStoreUrl;
  final String? iosStoreUrl;

  factory AppVersionModel.fromJson(Map<String, dynamic> json) {
    final endAtRaw = json['maintenance_end_at'] as String?;
    final storeUrls = json['store_urls'] as Map<String, dynamic>? ?? {};

    return AppVersionModel(
      maintenance: json['maintenance'] as bool? ?? false,
      maintenanceMessage: json['maintenance_message'] as String?,
      maintenanceEndAt: endAtRaw != null ? DateTime.tryParse(endAtRaw) : null,
      versionRequired: json['version_required'] as String? ?? '1.0.0',
      updateType: json['update_type'] as String? ?? 'none',
      updateMessage: json['update_message'] as String?,
      features: (json['features'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          [],
      androidStoreUrl: storeUrls['android'] as String?,
      iosStoreUrl: storeUrls['ios'] as String?,
    );
  }
}
