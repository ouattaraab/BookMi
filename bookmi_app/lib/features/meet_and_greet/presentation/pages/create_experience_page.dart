import 'dart:io';

import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/meet_and_greet/data/repositories/experience_repository.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

// ── Aurora palette (dark) ─────────────────────────────────────────
const _blue = Color(0xFF1AB3FF);
const _violet = Color(0xFF8B5CF6);
const _bg = Color(0xFF0F172A);
const _surface = Color(0xFF1E293B);
const _mutedFg = Color(0xFF94A3B8);
const _border = Color(0x33FFFFFF);
const _inputFill = Color(0xFF263148);

class CreateExperiencePage extends StatefulWidget {
  const CreateExperiencePage({required this.repository, super.key});

  final ExperienceRepository repository;

  @override
  State<CreateExperiencePage> createState() => _CreateExperiencePageState();
}

class _CreateExperiencePageState extends State<CreateExperiencePage> {
  final _formKey = GlobalKey<FormState>();

  final _titleCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _venueCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _seatsCtrl = TextEditingController();

  DateTime? _selectedDateTime;
  File? _coverFile;
  bool _coverIsVideo = false;
  bool _loading = false;
  bool _locating = false;

  final _picker = ImagePicker();
  final _venueFocusNode = FocusNode();

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    _venueCtrl.dispose();
    _priceCtrl.dispose();
    _seatsCtrl.dispose();
    _venueFocusNode.dispose();
    super.dispose();
  }

  Future<void> _pickDateTime() async {
    final now = DateTime.now();
    final date = await showDatePicker(
      context: context,
      initialDate: _selectedDateTime ?? now.add(const Duration(days: 7)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 365 * 2)),
      builder: (context, child) => Theme(
        data: ThemeData.dark().copyWith(
          colorScheme: const ColorScheme.dark(
            primary: _blue,
            onPrimary: Colors.white,
            surface: _surface,
            onSurface: Colors.white,
          ),
        ),
        child: child!,
      ),
    );
    if (date == null || !mounted) return;

    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(
        _selectedDateTime ?? DateTime(now.year, now.month, now.day, 18),
      ),
      builder: (context, child) => Theme(
        data: ThemeData.dark().copyWith(
          colorScheme: const ColorScheme.dark(
            primary: _blue,
            onPrimary: Colors.white,
            surface: _surface,
            onSurface: Colors.white,
          ),
        ),
        child: child!,
      ),
    );
    if (time == null || !mounted) return;

    setState(() {
      _selectedDateTime = DateTime(
        date.year,
        date.month,
        date.day,
        time.hour,
        time.minute,
      );
    });
  }

  Future<void> _pickCoverMedia() async {
    final choice = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: _surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            Container(
              width: 36,
              height: 4,
              decoration: BoxDecoration(
                color: _border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 20),
            ListTile(
              leading: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: _blue.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.photo_library_rounded, color: _blue, size: 20),
              ),
              title: Text(
                'Photo depuis la galerie',
                style: GoogleFonts.manrope(color: Colors.white, fontWeight: FontWeight.w600),
              ),
              onTap: () => Navigator.pop(ctx, 'photo_gallery'),
            ),
            ListTile(
              leading: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: _violet.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.videocam_rounded, color: _violet, size: 20),
              ),
              title: Text(
                'Vidéo depuis la galerie',
                style: GoogleFonts.manrope(color: Colors.white, fontWeight: FontWeight.w600),
              ),
              onTap: () => Navigator.pop(ctx, 'video_gallery'),
            ),
            ListTile(
              leading: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.08),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.camera_alt_rounded, color: _mutedFg, size: 20),
              ),
              title: Text(
                'Prendre une photo',
                style: GoogleFonts.manrope(color: Colors.white, fontWeight: FontWeight.w600),
              ),
              onTap: () => Navigator.pop(ctx, 'photo_camera'),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );

    if (choice == null) return;

    XFile? picked;
    switch (choice) {
      case 'photo_gallery':
        picked = await _picker.pickImage(
          source: ImageSource.gallery,
          imageQuality: 85,
          maxWidth: 1200,
        );
        if (picked != null) setState(() { _coverFile = File(picked!.path); _coverIsVideo = false; });
      case 'video_gallery':
        picked = await _picker.pickVideo(source: ImageSource.gallery);
        if (picked != null) setState(() { _coverFile = File(picked!.path); _coverIsVideo = true; });
      case 'photo_camera':
        picked = await _picker.pickImage(
          source: ImageSource.camera,
          imageQuality: 85,
          maxWidth: 1200,
        );
        if (picked != null) setState(() { _coverFile = File(picked!.path); _coverIsVideo = false; });
    }
  }

  Future<void> _showVenuePicker() async {
    final choice = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: _surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            Container(
              width: 36,
              height: 4,
              decoration: BoxDecoration(
                color: _border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 4),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Choisir le lieu',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
            ListTile(
              leading: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: _blue.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.my_location_rounded, color: _blue, size: 20),
              ),
              title: Text(
                'Utiliser ma position actuelle',
                style: GoogleFonts.manrope(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
              subtitle: Text(
                'Localisation GPS automatique',
                style: GoogleFonts.manrope(color: _mutedFg, fontSize: 12),
              ),
              onTap: () => Navigator.pop(ctx, 'gps'),
            ),
            ListTile(
              leading: Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: _violet.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.edit_location_alt_rounded, color: _violet, size: 20),
              ),
              title: Text(
                'Saisir l\'adresse manuellement',
                style: GoogleFonts.manrope(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
              subtitle: Text(
                'Tapez le nom du lieu ou l\'adresse',
                style: GoogleFonts.manrope(color: _mutedFg, fontSize: 12),
              ),
              onTap: () => Navigator.pop(ctx, 'manual'),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );

    if (choice == 'gps') {
      await _pickVenueFromLocation();
    } else if (choice == 'manual' && mounted) {
      _venueFocusNode.requestFocus();
    }
  }

  Future<void> _pickVenueFromLocation() async {
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (!mounted) return;
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            permission == LocationPermission.deniedForever
                ? 'Activez la localisation dans les réglages.'
                : 'Permission de localisation refusée.',
            style: GoogleFonts.manrope(),
          ),
          backgroundColor: const Color(0xFFEF4444),
        ),
      );
      return;
    }

    setState(() => _locating = true);
    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
          timeLimit: Duration(seconds: 10),
        ),
      );

      // Reverse geocode via Nominatim (free, no API key)
      final response = await Dio().get<Map<String, dynamic>>(
        'https://nominatim.openstreetmap.org/reverse',
        queryParameters: {
          'format': 'json',
          'lat': position.latitude.toString(),
          'lon': position.longitude.toString(),
          'accept-language': 'fr',
        },
        options: Options(
          headers: {'User-Agent': 'BookMiApp/1.0'},
          receiveTimeout: const Duration(seconds: 8),
        ),
      );

      final address = response.data?['display_name'] as String?;
      if (address != null && mounted) {
        _venueCtrl.text = address;
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Impossible d\'obtenir la localisation.',
              style: GoogleFonts.manrope(),
            ),
            backgroundColor: const Color(0xFFEF4444),
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedDateTime == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Veuillez sélectionner une date et heure.',
            style: GoogleFonts.manrope(),
          ),
          backgroundColor: const Color(0xFFEF4444),
        ),
      );
      return;
    }

    setState(() => _loading = true);

    final eventDate = DateFormat('yyyy-MM-dd HH:mm:ss').format(_selectedDateTime!);

    final pricePerSeat = int.tryParse(_priceCtrl.text.trim()) ?? 0;
    final maxSeats = int.tryParse(_seatsCtrl.text.trim()) ?? 0;

    final result = await widget.repository.createExperience(
      title: _titleCtrl.text.trim(),
      description: _descCtrl.text.trim(),
      eventDate: eventDate,
      venueAddress: _venueCtrl.text.trim(),
      totalPrice: pricePerSeat * maxSeats,
      maxSeats: maxSeats,
    );

    if (!mounted) return;

    switch (result) {
      case ApiSuccess(:final data):
        // Upload cover if selected
        if (_coverFile != null) {
          await widget.repository.uploadCover(data.id, _coverFile!);
        }
        if (!mounted) return;
        setState(() => _loading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Meet & Greet créé avec succès.',
              style: GoogleFonts.manrope(),
            ),
            backgroundColor: const Color(0xFF14B8A6),
          ),
        );
        context.pop();
      case ApiFailure(:final message):
        setState(() => _loading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message, style: GoogleFonts.manrope()),
            backgroundColor: const Color(0xFFEF4444),
          ),
        );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [_blue, _violet],
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
            ),
          ),
        ),
        elevation: 0,
        title: Text(
          'Créer un Meet & Greet',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 17,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white),
          onPressed: () => context.pop(),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _SectionLabel('Informations générales'),
              const SizedBox(height: 12),
              _buildField(
                controller: _titleCtrl,
                label: 'Titre',
                hint: 'Ex: Backstage VIP après concert',
                maxLength: 255,
                validator: (v) {
                  if (v == null || v.trim().isEmpty) {
                    return 'Le titre est requis.';
                  }
                  if (v.trim().length > 255) return 'Maximum 255 caractères.';
                  return null;
                },
              ),
              const SizedBox(height: 16),
              _buildField(
                controller: _descCtrl,
                label: 'Description (optionnel)',
                hint: 'Décrivez l\'expérience que vous offrez...',
                maxLines: 4,
              ),
              const SizedBox(height: 24),

              // ── Média de couverture ───────────────────────────────
              _SectionLabel('Média de couverture (optionnel)'),
              const SizedBox(height: 8),
              Text(
                'Ajoutez une photo ou une vidéo pour présenter votre événement.',
                style: GoogleFonts.manrope(
                  fontSize: 12,
                  color: _mutedFg.withValues(alpha: 0.7),
                ),
              ),
              const SizedBox(height: 12),
              _CoverPicker(
                file: _coverFile,
                isVideo: _coverIsVideo,
                onTap: _pickCoverMedia,
                onRemove: () => setState(() { _coverFile = null; _coverIsVideo = false; }),
              ),
              const SizedBox(height: 24),

              _SectionLabel('Date et lieu'),
              const SizedBox(height: 12),
              _DateTimeField(
                selectedDateTime: _selectedDateTime,
                onTap: _pickDateTime,
              ),
              const SizedBox(height: 16),
              _VenueFormField(
                controller: _venueCtrl,
                focusNode: _venueFocusNode,
                locating: _locating,
                onLocationTap: _showVenuePicker,
              ),
              const SizedBox(height: 24),
              _SectionLabel('Tarif et capacité'),
              const SizedBox(height: 12),
              _buildField(
                controller: _priceCtrl,
                label: 'Prix par place (FCFA)',
                hint: 'Ex: 5000',
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                validator: (v) {
                  final n = int.tryParse(v ?? '');
                  if (n == null || n < 100) {
                    return 'Minimum 100 FCFA par place.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              _buildField(
                controller: _seatsCtrl,
                label: 'Nombre de places max',
                hint: 'Ex: 10',
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                validator: (v) {
                  final n = int.tryParse(v ?? '');
                  if (n == null || n < 1) {
                    return 'Minimum 1 place.';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 32),
              _SubmitButton(loading: _loading, onPressed: _submit),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildField({
    required TextEditingController controller,
    required String label,
    String? hint,
    int? maxLines,
    int? maxLength,
    TextInputType? keyboardType,
    List<TextInputFormatter>? inputFormatters,
    String? Function(String?)? validator,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.manrope(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: _mutedFg,
          ),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          maxLines: maxLines ?? 1,
          maxLength: maxLength,
          keyboardType: keyboardType,
          inputFormatters: inputFormatters,
          validator: validator,
          style: GoogleFonts.manrope(color: Colors.white, fontSize: 14),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.manrope(color: _mutedFg.withValues(alpha: 0.5)),
            filled: true,
            fillColor: _inputFill,
            counterStyle: GoogleFonts.manrope(color: _mutedFg, fontSize: 11),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: _border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: _border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: _blue, width: 1.5),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFFEF4444)),
            ),
            focusedErrorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide:
                  const BorderSide(color: Color(0xFFEF4444), width: 1.5),
            ),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 12,
            ),
          ),
        ),
      ],
    );
  }
}

// ── Cover Picker ──────────────────────────────────────────────────

class _CoverPicker extends StatelessWidget {
  const _CoverPicker({
    required this.file,
    required this.isVideo,
    required this.onTap,
    required this.onRemove,
  });

  final File? file;
  final bool isVideo;
  final VoidCallback onTap;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    if (file == null) {
      // Empty picker placeholder
      return GestureDetector(
        onTap: onTap,
        child: Container(
          height: 140,
          decoration: BoxDecoration(
            color: _inputFill,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: _border,
              style: BorderStyle.solid,
            ),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              ShaderMask(
                shaderCallback: (bounds) =>
                    const LinearGradient(colors: [_blue, _violet])
                        .createShader(bounds),
                child: const Icon(
                  Icons.add_photo_alternate_rounded,
                  size: 36,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                'Ajouter une photo ou vidéo',
                style: GoogleFonts.manrope(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: _blue,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'JPG, PNG, MP4, MOV — max 50 Mo',
                style: GoogleFonts.manrope(
                  fontSize: 11,
                  color: _mutedFg.withValues(alpha: 0.6),
                ),
              ),
            ],
          ),
        ),
      );
    }

    // Preview
    return Stack(
      children: [
        GestureDetector(
          onTap: onTap,
          child: Container(
            height: 160,
            decoration: BoxDecoration(
              color: _surface,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: _blue.withValues(alpha: 0.5)),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(13),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (!isVideo)
                    Image.file(file!, fit: BoxFit.cover)
                  else
                    Container(
                      color: _inputFill,
                      child: const Center(
                        child: Icon(
                          Icons.videocam_rounded,
                          size: 48,
                          color: _violet,
                        ),
                      ),
                    ),
                  // Gradient overlay
                  Positioned.fill(
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.transparent,
                            Colors.black.withValues(alpha: 0.45),
                          ],
                        ),
                      ),
                    ),
                  ),
                  // Type badge
                  Positioned(
                    top: 10,
                    left: 10,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.5),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            isVideo ? Icons.videocam_rounded : Icons.image_rounded,
                            size: 13,
                            color: isVideo ? _violet : _blue,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            isVideo ? 'Vidéo' : 'Photo',
                            style: GoogleFonts.manrope(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: Colors.white,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  // Change label
                  Positioned(
                    bottom: 10,
                    left: 0,
                    right: 0,
                    child: Text(
                      'Appuyer pour changer',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.manrope(
                        fontSize: 11,
                        color: Colors.white.withValues(alpha: 0.8),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
        // Remove button
        Positioned(
          top: 8,
          right: 8,
          child: GestureDetector(
            onTap: onRemove,
            child: Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.6),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.close_rounded, size: 16, color: Colors.white),
            ),
          ),
        ),
      ],
    );
  }
}

// ── Section Label ─────────────────────────────────────────────────

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: GoogleFonts.plusJakartaSans(
        fontSize: 13,
        fontWeight: FontWeight.w700,
        color: _mutedFg,
        letterSpacing: 0.5,
      ),
    );
  }
}

// ── DateTime Field ────────────────────────────────────────────────

class _DateTimeField extends StatelessWidget {
  const _DateTimeField({
    required this.selectedDateTime,
    required this.onTap,
  });

  final DateTime? selectedDateTime;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final label = selectedDateTime != null
        ? DateFormat('dd MMM yyyy • HH:mm', 'fr_FR').format(selectedDateTime!)
        : 'Sélectionner une date et heure';

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: _inputFill,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: selectedDateTime != null ? _blue : _border,
            width: selectedDateTime != null ? 1.5 : 1,
          ),
        ),
        child: Row(
          children: [
            Icon(
              Icons.calendar_month_outlined,
              size: 18,
              color: selectedDateTime != null ? _blue : _mutedFg,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                label,
                style: GoogleFonts.manrope(
                  fontSize: 14,
                  color: selectedDateTime != null ? Colors.white : _mutedFg,
                ),
              ),
            ),
            Icon(
              Icons.arrow_drop_down_rounded,
              color: selectedDateTime != null ? _blue : _mutedFg,
            ),
          ],
        ),
      ),
    );
  }
}

// ── Venue Form Field ──────────────────────────────────────────────

class _VenueFormField extends StatelessWidget {
  const _VenueFormField({
    required this.controller,
    required this.focusNode,
    required this.locating,
    required this.onLocationTap,
  });

  final TextEditingController controller;
  final FocusNode focusNode;
  final bool locating;
  final VoidCallback onLocationTap;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Lieu (optionnel)',
          style: GoogleFonts.manrope(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: _mutedFg,
          ),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          focusNode: focusNode,
          style: GoogleFonts.manrope(color: Colors.white, fontSize: 14),
          decoration: InputDecoration(
            hintText: 'Salle, adresse…',
            hintStyle: GoogleFonts.manrope(
              color: _mutedFg.withValues(alpha: 0.5),
            ),
            filled: true,
            fillColor: _inputFill,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: _border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: _border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: _blue, width: 1.5),
            ),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 12,
            ),
            suffixIcon: Padding(
              padding: const EdgeInsets.only(right: 4),
              child: locating
                  ? const Padding(
                      padding: EdgeInsets.all(14),
                      child: SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          color: _blue,
                          strokeWidth: 2,
                        ),
                      ),
                    )
                  : IconButton(
                      icon: const Icon(
                        Icons.my_location_rounded,
                        color: _blue,
                        size: 20,
                      ),
                      tooltip: 'Choisir le lieu',
                      onPressed: onLocationTap,
                    ),
            ),
          ),
        ),
      ],
    );
  }
}

// ── Submit Button ─────────────────────────────────────────────────

class _SubmitButton extends StatelessWidget {
  const _SubmitButton({required this.loading, required this.onPressed});

  final bool loading;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 52,
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [_blue, _violet]),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: loading ? null : onPressed,
          child: Center(
            child: loading
                ? const SizedBox(
                    height: 22,
                    width: 22,
                    child: CircularProgressIndicator(
                      color: Colors.white,
                      strokeWidth: 2.5,
                    ),
                  )
                : Text(
                    'Créer l\'expérience',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
          ),
        ),
      ),
    );
  }
}
