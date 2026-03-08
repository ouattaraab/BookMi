import 'dart:async';

import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';

/// Model for a Nominatim geocoding result.
class LocationSuggestion {
  const LocationSuggestion({
    required this.displayName,
    required this.latitude,
    required this.longitude,
  });

  final String displayName;
  final double latitude;
  final double longitude;
}

/// Text field with Nominatim (OpenStreetMap) autocomplete and optional
/// "use current location" button. Calls [onLocationChanged] when text
/// changes and [onCoordinatesSelected] when the user picks a suggestion
/// or uses GPS.
class LocationPickerField extends StatefulWidget {
  const LocationPickerField({
    required this.initialValue,
    required this.onLocationChanged,
    required this.onCoordinatesSelected,
    super.key,
  });

  final String initialValue;
  final ValueChanged<String> onLocationChanged;
  final void Function(double lat, double lng) onCoordinatesSelected;

  @override
  State<LocationPickerField> createState() => _LocationPickerFieldState();
}

class _LocationPickerFieldState extends State<LocationPickerField> {
  late final TextEditingController _controller;
  late final Dio _dio;

  List<LocationSuggestion> _suggestions = [];
  bool _loadingSuggestions = false;
  bool _loadingGps = false;
  Timer? _debounce;
  bool _showSuggestions = false;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.initialValue);
    _dio = Dio(
      BaseOptions(
        connectTimeout: const Duration(seconds: 5),
        receiveTimeout: const Duration(seconds: 5),
        headers: {
          'User-Agent': 'BookMiApp/1.0 (contact@bookmi.click)',
        },
      ),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    _debounce?.cancel();
    _dio.close();
    super.dispose();
  }

  Future<void> _searchNominatim(String query) async {
    if (query.trim().length < 3) {
      setState(() {
        _suggestions = [];
        _showSuggestions = false;
      });
      return;
    }

    setState(() => _loadingSuggestions = true);

    try {
      final response = await _dio.get<List<dynamic>>(
        'https://nominatim.openstreetmap.org/search',
        queryParameters: {
          'q': query,
          'format': 'json',
          'limit': '5',
          'addressdetails': '0',
        },
      );

      if (!mounted) return;

      final results = (response.data ?? [])
          .map((e) {
            final m = e as Map<String, dynamic>;
            return LocationSuggestion(
              displayName: m['display_name'] as String,
              latitude: double.parse(m['lat'] as String),
              longitude: double.parse(m['lon'] as String),
            );
          })
          .toList();

      setState(() {
        _suggestions = results;
        _showSuggestions = results.isNotEmpty;
        _loadingSuggestions = false;
      });
    } catch (_) {
      if (mounted) {
        setState(() {
          _loadingSuggestions = false;
          _showSuggestions = false;
        });
      }
    }
  }

  Future<void> _useCurrentLocation() async {
    setState(() => _loadingGps = true);

    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        if (mounted) _showLocationError("Le GPS est désactivé.");
        return;
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        if (mounted) _showLocationError("Permission GPS refusée.");
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 10),
        ),
      );

      // Reverse geocode with Nominatim
      final response = await _dio.get<Map<String, dynamic>>(
        'https://nominatim.openstreetmap.org/reverse',
        queryParameters: {
          'lat': position.latitude,
          'lon': position.longitude,
          'format': 'json',
        },
      );

      if (!mounted) return;

      final displayName = response.data?['display_name'] as String? ??
          '${position.latitude.toStringAsFixed(5)}, '
              '${position.longitude.toStringAsFixed(5)}';

      _controller.text = displayName;
      widget.onLocationChanged(displayName);
      widget.onCoordinatesSelected(position.latitude, position.longitude);

      setState(() {
        _suggestions = [];
        _showSuggestions = false;
      });
    } catch (_) {
      if (mounted) _showLocationError("Impossible d'obtenir la localisation.");
    } finally {
      if (mounted) setState(() => _loadingGps = false);
    }
  }

  void _showLocationError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: BookmiColors.error,
      ),
    );
    setState(() => _loadingGps = false);
  }

  void _onSuggestionSelected(LocationSuggestion suggestion) {
    _controller.text = suggestion.displayName;
    widget.onLocationChanged(suggestion.displayName);
    widget.onCoordinatesSelected(suggestion.latitude, suggestion.longitude);
    setState(() {
      _suggestions = [];
      _showSuggestions = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _controller,
                style: const TextStyle(color: Colors.white, fontSize: 14),
                onChanged: (value) {
                  widget.onLocationChanged(value);
                  _debounce?.cancel();
                  _debounce = Timer(const Duration(milliseconds: 500), () {
                    _searchNominatim(value);
                  });
                },
                decoration: InputDecoration(
                  hintText: 'Ex: Sofitel Abidjan Hôtel Ivoire',
                  hintStyle: TextStyle(
                    color: Colors.white.withValues(alpha: 0.4),
                    fontSize: 14,
                  ),
                  prefixIcon: Icon(
                    Icons.location_on_outlined,
                    color: Colors.white.withValues(alpha: 0.5),
                    size: 20,
                  ),
                  suffixIcon: _loadingSuggestions
                      ? const Padding(
                          padding: EdgeInsets.all(12),
                          child: SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: BookmiColors.brandBlue,
                            ),
                          ),
                        )
                      : null,
                  filled: true,
                  fillColor: BookmiColors.glassDarkMedium,
                  border: OutlineInputBorder(
                    borderRadius: BookmiRadius.inputBorder,
                    borderSide: BorderSide(color: BookmiColors.glassBorder),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BookmiRadius.inputBorder,
                    borderSide: BorderSide(color: BookmiColors.glassBorder),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BookmiRadius.inputBorder,
                    borderSide: const BorderSide(color: BookmiColors.brandBlue),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: BookmiSpacing.spaceBase,
                    vertical: BookmiSpacing.spaceSm,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            // GPS button
            SizedBox(
              width: 44,
              height: 44,
              child: Material(
                color: BookmiColors.glassDarkMedium,
                borderRadius: BookmiRadius.inputBorder,
                child: InkWell(
                  borderRadius: BookmiRadius.inputBorder,
                  onTap: _loadingGps ? null : _useCurrentLocation,
                  child: Center(
                    child: _loadingGps
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: BookmiColors.brandBlue,
                            ),
                          )
                        : Icon(
                            Icons.my_location_rounded,
                            size: 20,
                            color: Colors.white.withValues(alpha: 0.7),
                          ),
                  ),
                ),
              ),
            ),
          ],
        ),
        // Suggestions dropdown
        if (_showSuggestions && _suggestions.isNotEmpty) ...[
          const SizedBox(height: 4),
          Container(
            constraints: const BoxConstraints(maxHeight: 200),
            decoration: BoxDecoration(
              color: const Color(0xFF0D1B38),
              borderRadius: BookmiRadius.inputBorder,
              border: Border.all(color: BookmiColors.glassBorder),
            ),
            child: ListView.separated(
              shrinkWrap: true,
              padding: EdgeInsets.zero,
              itemCount: _suggestions.length,
              separatorBuilder: (_, __) => Divider(
                height: 1,
                color: Colors.white.withValues(alpha: 0.08),
              ),
              itemBuilder: (context, index) {
                final s = _suggestions[index];
                return InkWell(
                  onTap: () => _onSuggestionSelected(s),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: BookmiSpacing.spaceBase,
                      vertical: 10,
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.place_outlined,
                          size: 16,
                          color: BookmiColors.brandBlue,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            s.displayName,
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.85),
                              fontSize: 12,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ],
    );
  }
}
