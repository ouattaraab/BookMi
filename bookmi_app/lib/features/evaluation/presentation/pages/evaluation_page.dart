import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/evaluation/bloc/evaluation_cubit.dart';
import 'package:bookmi_app/features/evaluation/bloc/evaluation_state.dart';
import 'package:bookmi_app/features/evaluation/data/repositories/review_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Full-screen evaluation page.
///
/// [type] is `client_to_talent` (client rates talent) or
/// `talent_to_client` (talent rates client).
class EvaluationPage extends StatelessWidget {
  const EvaluationPage({
    required this.bookingId,
    required this.type,
    required this.repository,
    super.key,
  });

  final int bookingId;
  final String type;
  final ReviewRepository repository;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => EvaluationCubit(repository: repository),
      child: _EvaluationView(bookingId: bookingId, type: type),
    );
  }
}

// ── View ─────────────────────────────────────────────────────────────────────

class _EvaluationView extends StatelessWidget {
  const _EvaluationView({required this.bookingId, required this.type});

  final int bookingId;
  final String type;

  String get _title =>
      type == 'client_to_talent' ? 'Évaluer le talent' : 'Évaluer le client';

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          foregroundColor: Colors.white,
          title: Text(
            _title,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        body: BlocConsumer<EvaluationCubit, EvaluationState>(
          listener: (context, state) {
            if (state is EvaluationError) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(state.message),
                  backgroundColor: BookmiColors.error,
                ),
              );
            }
          },
          builder: (context, state) => switch (state) {
            EvaluationSubmitted() => _SuccessScreen(
              onClose: () => Navigator.of(context).pop(),
            ),
            _ => _EvaluationForm(bookingId: bookingId, type: type),
          },
        ),
      ),
    );
  }
}

// ── Form ─────────────────────────────────────────────────────────────────────

class _EvaluationForm extends StatefulWidget {
  const _EvaluationForm({required this.bookingId, required this.type});

  final int bookingId;
  final String type;

  @override
  State<_EvaluationForm> createState() => _EvaluationFormState();
}

class _EvaluationFormState extends State<_EvaluationForm> {
  int _rating = 0;
  int _punctualityScore = 0;
  int _qualityScore = 0;
  int _professionalismScore = 0;
  int _contractRespectScore = 0;
  final _commentController = TextEditingController();

  bool get _isClientToTalent => widget.type == 'client_to_talent';

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  void _submit() {
    if (_rating == 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez sélectionner une note.')),
      );
      return;
    }
    context.read<EvaluationCubit>().submitReview(
      widget.bookingId,
      type: widget.type,
      rating: _rating,
      punctualityScore: _isClientToTalent && _punctualityScore > 0
          ? _punctualityScore
          : null,
      qualityScore: _isClientToTalent && _qualityScore > 0
          ? _qualityScore
          : null,
      professionalismScore: _isClientToTalent && _professionalismScore > 0
          ? _professionalismScore
          : null,
      contractRespectScore: _isClientToTalent && _contractRespectScore > 0
          ? _contractRespectScore
          : null,
      comment: _commentController.text.trim().isEmpty
          ? null
          : _commentController.text.trim(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<EvaluationCubit, EvaluationState>(
      builder: (context, state) {
        final isSubmitting = state is EvaluationSubmitting;
        return ListView(
          padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
          children: [
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Note globale',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceMd),
                  Center(
                    child: _StarRating(
                      value: _rating,
                      onChanged: isSubmitting
                          ? null
                          : (v) => setState(() => _rating = v),
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  Center(
                    child: Text(
                      _ratingLabel(_rating),
                      style: const TextStyle(
                        fontSize: 13,
                        color: Colors.white60,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            if (_isClientToTalent) ...[
              const SizedBox(height: BookmiSpacing.spaceMd),
              GlassCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Critères détaillés (optionnel)',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceMd),
                    _CriteriaRow(
                      label: 'Ponctualité',
                      value: _punctualityScore,
                      enabled: !isSubmitting,
                      onChanged: (v) =>
                          setState(() => _punctualityScore = v),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceSm),
                    _CriteriaRow(
                      label: 'Qualité',
                      value: _qualityScore,
                      enabled: !isSubmitting,
                      onChanged: (v) => setState(() => _qualityScore = v),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceSm),
                    _CriteriaRow(
                      label: 'Professionnalisme',
                      value: _professionalismScore,
                      enabled: !isSubmitting,
                      onChanged: (v) =>
                          setState(() => _professionalismScore = v),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceSm),
                    _CriteriaRow(
                      label: 'Respect du contrat',
                      value: _contractRespectScore,
                      enabled: !isSubmitting,
                      onChanged: (v) =>
                          setState(() => _contractRespectScore = v),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: BookmiSpacing.spaceMd),
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Commentaire (optionnel)',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  TextField(
                    controller: _commentController,
                    enabled: !isSubmitting,
                    maxLines: 4,
                    maxLength: 500,
                    style: const TextStyle(color: Colors.white, fontSize: 14),
                    decoration: InputDecoration(
                      hintText: 'Partagez votre expérience…',
                      hintStyle: const TextStyle(color: Colors.white38),
                      counterStyle: const TextStyle(color: Colors.white38),
                      filled: true,
                      fillColor: Colors.white.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                        borderSide: const BorderSide(color: Colors.white24),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                        borderSide: const BorderSide(color: Colors.white24),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                        borderSide: const BorderSide(
                          color: BookmiColors.brandBlueLight,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: BookmiSpacing.spaceLg),
            SizedBox(
              width: double.infinity,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: _rating > 0 && !isSubmitting
                      ? BookmiColors.gradientBrand
                      : null,
                  color: _rating > 0 && !isSubmitting ? null : Colors.white12,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: TextButton(
                  onPressed: isSubmitting || _rating == 0 ? null : _submit,
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(
                      vertical: BookmiSpacing.spaceMd,
                    ),
                    foregroundColor: Colors.white,
                    disabledForegroundColor: Colors.white38,
                  ),
                  child: isSubmitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white38,
                          ),
                        )
                      : const Text(
                          'Soumettre mon évaluation',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  static String _ratingLabel(int rating) => switch (rating) {
    1 => 'Très décevant',
    2 => 'Décevant',
    3 => 'Bien',
    4 => 'Très bien',
    5 => 'Excellent !',
    _ => 'Sélectionnez une note',
  };
}

// ── Criteria Row ──────────────────────────────────────────────────────────────

class _CriteriaRow extends StatelessWidget {
  const _CriteriaRow({
    required this.label,
    required this.value,
    required this.enabled,
    required this.onChanged,
  });

  final String label;
  final int value;
  final bool enabled;
  final void Function(int) onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        SizedBox(
          width: 140,
          child: Text(
            label,
            style: const TextStyle(fontSize: 13, color: Colors.white70),
          ),
        ),
        Expanded(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: List.generate(5, (i) {
              final starIndex = i + 1;
              final isFilled = starIndex <= value;
              return GestureDetector(
                onTap: enabled ? () => onChanged(starIndex) : null,
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 2),
                  child: Icon(
                    isFilled
                        ? Icons.star_rounded
                        : Icons.star_outline_rounded,
                    size: 24,
                    color: isFilled
                        ? BookmiColors.brandBlueLight
                        : Colors.white24,
                  ),
                ),
              );
            }),
          ),
        ),
      ],
    );
  }
}

// ── Star Rating Widget ────────────────────────────────────────────────────────

class _StarRating extends StatelessWidget {
  const _StarRating({required this.value, required this.onChanged});

  final int value;
  final void Function(int)? onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(5, (i) {
        final starIndex = i + 1;
        final isFilled = starIndex <= value;
        return GestureDetector(
          onTap: onChanged != null ? () => onChanged!(starIndex) : null,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4),
            child: Icon(
              isFilled ? Icons.star_rounded : Icons.star_outline_rounded,
              size: 40,
              color: isFilled ? BookmiColors.brandBlueLight : Colors.white30,
            ),
          ),
        );
      }),
    );
  }
}

// ── Success Screen ────────────────────────────────────────────────────────────

class _SuccessScreen extends StatefulWidget {
  const _SuccessScreen({required this.onClose});

  final VoidCallback onClose;

  @override
  State<_SuccessScreen> createState() => _SuccessScreenState();
}

class _SuccessScreenState extends State<_SuccessScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _scale;
  late final Animation<double> _opacity;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _scale = CurvedAnimation(parent: _controller, curve: Curves.elasticOut);
    _opacity = CurvedAnimation(parent: _controller, curve: Curves.easeIn);
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _opacity,
      child: Padding(
        padding: const EdgeInsets.all(BookmiSpacing.spaceXl),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            ScaleTransition(
              scale: _scale,
              child: Container(
                width: 96,
                height: 96,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      BookmiColors.brandBlueLight.withValues(alpha: 0.3),
                      Colors.transparent,
                    ],
                  ),
                ),
                child: const Icon(
                  Icons.star_rounded,
                  color: BookmiColors.brandBlueLight,
                  size: 64,
                ),
              ),
            ),
            const SizedBox(height: BookmiSpacing.spaceLg),
            const Text(
              'Évaluation envoyée !',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w800,
                color: Colors.white,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: BookmiSpacing.spaceSm),
            const Text(
              'Merci pour votre retour. Votre avis aide la communauté BookMi.',
              style: TextStyle(fontSize: 15, color: Colors.white70),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: BookmiSpacing.spaceXl),
            TextButton(
              onPressed: widget.onClose,
              child: const Text(
                'Fermer',
                style: TextStyle(
                  color: BookmiColors.brandBlueLight,
                  fontSize: 15,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
