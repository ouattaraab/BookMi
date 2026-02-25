import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

const _secondary = Color(0xFFE8F0FF);
const _muted = Color(0xFF112044);
const _mutedFg = Color(0xFF8FA3C0);
const _primary = Color(0xFF3B9DF2);
const _success = Color(0xFF14B8A6);
const _border = Color(0x1AFFFFFF);
const _destructive = Color(0xFFEF4444);

class PackageManagerPage extends StatefulWidget {
  const PackageManagerPage({required this.repository, super.key});
  final ProfileRepository repository;

  @override
  State<PackageManagerPage> createState() => _PackageManagerPageState();
}

class _PackageManagerPageState extends State<PackageManagerPage> {
  List<Map<String, dynamic>> _packages = [];
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
    final result = await widget.repository.getServicePackages();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _packages = data;
          _loading = false;
        });
      case ApiFailure(:final message):
        setState(() {
          _error = message;
          _loading = false;
        });
    }
  }

  Future<void> _openForm({Map<String, dynamic>? existing}) async {
    final updated = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => _PackageFormSheet(
        repository: widget.repository,
        existing: existing,
      ),
    );
    if (updated == true) await _load();
  }

  Future<void> _delete(int id, String name) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Supprimer "$name" ?',
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
            child: Text('Supprimer',
                style: GoogleFonts.manrope(
                    color: _destructive, fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    final result = await widget.repository.deleteServicePackage(id);
    if (!mounted) return;
    switch (result) {
      case ApiSuccess():
        setState(() => _packages.removeWhere(
              (p) => _getId(p) == id,
            ));
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Package "$name" supprimé')),
        );
      case ApiFailure(:final message):
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: _destructive),
        );
    }
  }

  static int _getId(Map<String, dynamic> p) {
    return (p['id'] as int?) ??
        ((p['attributes'] as Map<String, dynamic>?)?['id'] as int?) ??
        0;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _muted,
      appBar: AppBar(
        backgroundColor: const Color(0xFF0D1B38),
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          'Gestion packages',
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
        onPressed: () => _openForm(),
        backgroundColor: _primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: Text(
          'Nouveau package',
          style: GoogleFonts.manrope(
              color: const Color(0xFF0D1B38), fontWeight: FontWeight.w600),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? _buildError()
              : _packages.isEmpty
                  ? _buildEmpty()
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                        itemCount: _packages.length,
                        separatorBuilder: (_, __) =>
                            const SizedBox(height: 10),
                        itemBuilder: (context, index) {
                          final pkg = _packages[index];
                          final attrs =
                              pkg['attributes'] as Map<String, dynamic>? ??
                                  pkg;
                          final id = _getId(pkg);
                          final name =
                              attrs['name'] as String? ?? 'Package';
                          final desc =
                              attrs['description'] as String? ?? '';
                          final cachet =
                              (attrs['cachet_amount'] as num?)?.toInt() ?? 0;
                          final duration =
                              (attrs['duration_minutes'] as int?) ?? 0;
                          final type =
                              attrs['type'] as String? ?? 'standard';

                          return _PackageCard(
                            name: name,
                            description: desc,
                            cachetAmount: cachet,
                            durationMinutes: duration,
                            type: type,
                            onEdit: () => _openForm(existing: pkg),
                            onDelete: () => _delete(id, name),
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
          Icon(Icons.inventory_2_outlined,
              size: 56, color: _mutedFg.withValues(alpha: 0.4)),
          const SizedBox(height: 12),
          Text(
            'Aucun package',
            style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: _secondary),
          ),
          const SizedBox(height: 6),
          Text(
            'Créez vos offres de service pour les clients.',
            style: GoogleFonts.manrope(fontSize: 13, color: _mutedFg),
          ),
        ],
      ),
    );
  }
}

class _PackageCard extends StatelessWidget {
  const _PackageCard({
    required this.name,
    required this.description,
    required this.cachetAmount,
    required this.durationMinutes,
    required this.type,
    required this.onEdit,
    required this.onDelete,
  });

  final String name;
  final String description;
  final int cachetAmount;
  final int durationMinutes;
  final String type;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat('#,###', 'fr_FR');
    final hours = durationMinutes ~/ 60;
    final mins = durationMinutes % 60;
    final durationStr = hours > 0
        ? '${hours}h${mins > 0 ? '${mins}min' : ''}'
        : '${mins}min';

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _border),
        boxShadow: [
          BoxShadow(
            color: const Color(0x08FFFFFF),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    name,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: _secondary,
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: _primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    type,
                    style: GoogleFonts.manrope(
                        fontSize: 10, color: _primary,
                        fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
            if (description.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(
                description,
                style: GoogleFonts.manrope(
                    fontSize: 12, color: _mutedFg),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
            const SizedBox(height: 10),
            Row(
              children: [
                Icon(Icons.attach_money_outlined,
                    size: 14, color: _success),
                const SizedBox(width: 4),
                Text(
                  '${fmt.format(cachetAmount)} FCFA',
                  style: GoogleFonts.manrope(
                      fontSize: 13,
                      color: _success,
                      fontWeight: FontWeight.w700),
                ),
                const SizedBox(width: 16),
                Icon(Icons.schedule_outlined, size: 14, color: _mutedFg),
                const SizedBox(width: 4),
                Text(
                  durationStr,
                  style: GoogleFonts.manrope(
                      fontSize: 12, color: _mutedFg),
                ),
                const Spacer(),
                IconButton(
                  onPressed: onEdit,
                  icon: const Icon(Icons.edit_outlined,
                      size: 18, color: _primary),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
                const SizedBox(width: 12),
                IconButton(
                  onPressed: onDelete,
                  icon: const Icon(Icons.delete_outline,
                      size: 18, color: _destructive),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ── Package form (bottom sheet) ───────────────────────────────────
class _PackageFormSheet extends StatefulWidget {
  const _PackageFormSheet({
    required this.repository,
    this.existing,
  });

  final ProfileRepository repository;
  final Map<String, dynamic>? existing;

  @override
  State<_PackageFormSheet> createState() => _PackageFormSheetState();
}

class _PackageFormSheetState extends State<_PackageFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameCtrl;
  late final TextEditingController _descCtrl;
  late final TextEditingController _cachetCtrl;
  late final TextEditingController _durationCtrl;
  String _type = 'standard';
  bool _saving = false;

  static const _types = ['standard', 'premium', 'express'];

  @override
  void initState() {
    super.initState();
    final attrs = widget.existing?['attributes'] as Map<String, dynamic>? ??
        widget.existing ??
        {};
    _nameCtrl = TextEditingController(
        text: attrs['name'] as String? ?? '');
    _descCtrl = TextEditingController(
        text: attrs['description'] as String? ?? '');
    _cachetCtrl = TextEditingController(
        text: attrs['cachet_amount']?.toString() ?? '');
    _durationCtrl = TextEditingController(
        text: attrs['duration_minutes']?.toString() ?? '');
    _type = attrs['type'] as String? ?? 'standard';
    if (!_types.contains(_type)) _type = 'standard';
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descCtrl.dispose();
    _cachetCtrl.dispose();
    _durationCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    final data = {
      'name': _nameCtrl.text.trim(),
      'description': _descCtrl.text.trim(),
      'cachet_amount': int.parse(_cachetCtrl.text.trim()),
      'duration_minutes': int.parse(_durationCtrl.text.trim()),
      'type': _type,
    };

    ApiResult result;
    if (widget.existing != null) {
      final id = (widget.existing!['id'] as int?) ??
          ((widget.existing!['attributes'] as Map<String, dynamic>?)?['id'] as int?) ??
          0;
      result = await widget.repository.updateServicePackage(id, data);
    } else {
      result = await widget.repository.createServicePackage(data);
    }

    if (!mounted) return;
    setState(() => _saving = false);

    switch (result) {
      case ApiSuccess():
        Navigator.of(context).pop(true);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              widget.existing != null
                  ? 'Package mis à jour'
                  : 'Package créé avec succès',
            ),
          ),
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

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.existing != null;
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                children: [
                  Text(
                    isEdit ? 'Modifier le package' : 'Nouveau package',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: _secondary,
                    ),
                  ),
                  const Spacer(),
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(false),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _Field(
                controller: _nameCtrl,
                label: 'Nom du package *',
                hint: 'Ex: DJ Set 3h Premium',
                validator: (v) =>
                    v == null || v.trim().isEmpty ? 'Requis' : null,
              ),
              const SizedBox(height: 12),
              _Field(
                controller: _descCtrl,
                label: 'Description',
                hint: 'Décrivez votre offre...',
                maxLines: 3,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _Field(
                      controller: _cachetCtrl,
                      label: 'Cachet (FCFA) *',
                      hint: '50000',
                      keyboardType: TextInputType.number,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                      ],
                      validator: (v) =>
                          v == null || int.tryParse(v.trim()) == null
                              ? 'Nombre valide requis'
                              : null,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _Field(
                      controller: _durationCtrl,
                      label: 'Durée (minutes) *',
                      hint: '180',
                      keyboardType: TextInputType.number,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                      ],
                      validator: (v) =>
                          v == null || int.tryParse(v.trim()) == null
                              ? 'Nombre valide requis'
                              : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                'Type de package',
                style: GoogleFonts.manrope(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: _secondary),
              ),
              const SizedBox(height: 8),
              Row(
                children: _types.map((t) {
                  final isSelected = _type == t;
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: GestureDetector(
                      onTap: () => setState(() => _type = t),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 150),
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 8),
                        decoration: BoxDecoration(
                          color: isSelected ? _primary : Colors.transparent,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: isSelected
                                ? _primary
                                : _border,
                          ),
                        ),
                        child: Text(
                          t,
                          style: GoogleFonts.manrope(
                            fontSize: 13,
                            fontWeight: isSelected
                                ? FontWeight.w600
                                : FontWeight.normal,
                            color: isSelected ? Colors.white : _mutedFg,
                          ),
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _saving ? null : _save,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: _saving
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            color: const Color(0xFF0D1B38),
                            strokeWidth: 2,
                          ),
                        )
                      : Text(
                          isEdit ? 'Enregistrer' : 'Créer le package',
                          style: GoogleFonts.manrope(
                              fontWeight: FontWeight.w600, fontSize: 15),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    required this.hint,
    this.maxLines = 1,
    this.keyboardType,
    this.inputFormatters,
    this.validator,
  });

  final TextEditingController controller;
  final String label;
  final String hint;
  final int maxLines;
  final TextInputType? keyboardType;
  final List<TextInputFormatter>? inputFormatters;
  final String? Function(String?)? validator;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: GoogleFonts.manrope(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: _secondary),
        ),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          maxLines: maxLines,
          keyboardType: keyboardType,
          inputFormatters: inputFormatters,
          validator: validator,
          style: GoogleFonts.manrope(fontSize: 14, color: _secondary),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: GoogleFonts.manrope(fontSize: 13, color: _mutedFg),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: _primary),
            ),
            contentPadding: const EdgeInsets.symmetric(
                horizontal: 14, vertical: 12),
          ),
        ),
      ],
    );
  }
}
