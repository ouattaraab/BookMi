import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';

/// Step 3 of the booking flow — transparent devis recap + optional express/message.
class Step3Recap extends StatefulWidget {
  const Step3Recap({
    required this.packageName,
    required this.cachetAmount,
    required this.commissionAmount,
    required this.totalAmount,
    required this.eventDate,
    required this.eventLocation,
    required this.enableExpress,
    required this.isExpress,
    required this.message,
    required this.onExpressChanged,
    required this.onMessageChanged,
    this.travelCost = 0,
    this.appliedPromoCode,
    this.discountAmount = 0,
    this.promoLoading = false,
    this.onPromoCodeChanged,
    this.onApplyPromo,
    this.onClearPromo,
    super.key,
  });

  final String packageName;
  final int cachetAmount;
  final int commissionAmount;
  final int totalAmount;
  final String eventDate;
  final String eventLocation;
  final bool enableExpress;
  final bool isExpress;
  final String message;
  final ValueChanged<bool> onExpressChanged;
  final ValueChanged<String> onMessageChanged;

  /// Travel/displacement cost in XOF (0 = none).
  final int travelCost;

  // Promo code
  final String? appliedPromoCode;
  final int discountAmount;
  final bool promoLoading;
  final ValueChanged<String>? onPromoCodeChanged;
  final VoidCallback? onApplyPromo;
  final VoidCallback? onClearPromo;

  @override
  State<Step3Recap> createState() => _Step3RecapState();
}

class _Step3RecapState extends State<Step3Recap> {
  late final TextEditingController _messageController;
  late final TextEditingController _promoController;

  @override
  void initState() {
    super.initState();
    _messageController = TextEditingController(text: widget.message);
    _promoController = TextEditingController();
  }

  @override
  void didUpdateWidget(Step3Recap oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.message != widget.message &&
        _messageController.text != widget.message) {
      _messageController.text = widget.message;
    }
    // If promo was cleared externally, clear the text field too
    if (oldWidget.appliedPromoCode != null && widget.appliedPromoCode == null) {
      _promoController.clear();
    }
  }

  @override
  void dispose() {
    _messageController.dispose();
    _promoController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final expressFee = widget.isExpress
        ? (widget.cachetAmount * 0.15).round()
        : 0;
    final displayTotal =
        widget.totalAmount + expressFee - widget.discountAmount;
    // widget.totalAmount already includes travelCost from the sheet state.
    final promoApplied = widget.appliedPromoCode != null;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Devis transparent
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Récapitulatif',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceMd),
                _RecapRow(
                  label: widget.packageName,
                  value: TalentCard.formatCachet(widget.cachetAmount),
                ),
                const Divider(color: Colors.white12, height: 24),
                _RecapRow(
                  label: 'Commission BookMi (15%)',
                  value: TalentCard.formatCachet(widget.commissionAmount),
                  labelColor: Colors.white.withValues(alpha: 0.6),
                  valueColor: Colors.white.withValues(alpha: 0.6),
                  fontSize: 13,
                ),
                if (widget.travelCost > 0) ...[
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  _RecapRow(
                    label: 'Frais de déplacement',
                    value: TalentCard.formatCachet(widget.travelCost),
                    labelColor: Colors.white.withValues(alpha: 0.6),
                    valueColor: Colors.white.withValues(alpha: 0.6),
                    fontSize: 13,
                  ),
                ],
                if (widget.isExpress) ...[
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  _RecapRow(
                    label: 'Frais urgence (express)',
                    value: TalentCard.formatCachet(expressFee),
                    labelColor: Colors.orange,
                    valueColor: Colors.orange,
                    fontSize: 13,
                  ),
                ],
                if (widget.discountAmount > 0) ...[
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  _RecapRow(
                    label: 'Réduction (${widget.appliedPromoCode})',
                    value:
                        '-${TalentCard.formatCachet(widget.discountAmount)}',
                    labelColor: const Color(0xFF4CAF50),
                    valueColor: const Color(0xFF4CAF50),
                    fontSize: 13,
                  ),
                ],
                const SizedBox(height: BookmiSpacing.spaceSm),
                _RecapRow(
                  label: 'Date',
                  value: widget.eventDate,
                  labelColor: Colors.white.withValues(alpha: 0.6),
                  valueColor: Colors.white.withValues(alpha: 0.6),
                  fontSize: 13,
                ),
                const SizedBox(height: 4),
                _RecapRow(
                  label: 'Lieu',
                  value: widget.eventLocation,
                  labelColor: Colors.white.withValues(alpha: 0.6),
                  valueColor: Colors.white.withValues(alpha: 0.6),
                  fontSize: 13,
                ),
                const Divider(color: Colors.white12, height: 24),
                _RecapRow(
                  label: 'Total',
                  value: TalentCard.formatCachet(displayTotal),
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  valueColor: BookmiColors.brandBlueLight,
                ),
              ],
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),

          // Promo code field
          if (widget.onApplyPromo != null) ...[
            GlassCard(
              borderColor: promoApplied
                  ? const Color(0xFF4CAF50)
                  : BookmiColors.glassBorder,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        promoApplied
                            ? Icons.check_circle_outline
                            : Icons.local_offer_outlined,
                        size: 18,
                        color: promoApplied
                            ? const Color(0xFF4CAF50)
                            : Colors.white70,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        promoApplied
                            ? 'Code promo appliqué'
                            : 'Code promo (optionnel)',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: promoApplied
                              ? const Color(0xFF4CAF50)
                              : Colors.white,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _promoController,
                          onChanged: widget.onPromoCodeChanged,
                          enabled: !promoApplied && !widget.promoLoading,
                          textCapitalization: TextCapitalization.characters,
                          style: TextStyle(
                            color: promoApplied
                                ? const Color(0xFF4CAF50)
                                : Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            letterSpacing: 1.2,
                          ),
                          decoration: InputDecoration(
                            hintText: 'BOOKMI2026',
                            hintStyle: TextStyle(
                              color: Colors.white.withValues(alpha: 0.35),
                              fontSize: 14,
                              letterSpacing: 1.2,
                            ),
                            filled: true,
                            fillColor: BookmiColors.glassDarkMedium,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: BorderSide(
                                color: BookmiColors.glassBorder,
                              ),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: BorderSide(
                                color: promoApplied
                                    ? const Color(0xFF4CAF50)
                                    : BookmiColors.glassBorder,
                              ),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                color: BookmiColors.brandBlue,
                              ),
                            ),
                            contentPadding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 10,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      _PromoButton(
                        applied: promoApplied,
                        loading: widget.promoLoading,
                        onApply: widget.onApplyPromo,
                        onClear: () {
                          _promoController.clear();
                          widget.onClearPromo?.call();
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: BookmiSpacing.spaceMd),
          ],

          // Express option
          if (widget.enableExpress) ...[
            GlassCard(
              borderColor: widget.isExpress
                  ? BookmiColors.brandBlueLight
                  : BookmiColors.glassBorder,
              child: Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: BookmiColors.brandBlueLight.withValues(
                        alpha: 0.15,
                      ),
                    ),
                    child: const Icon(
                      Icons.bolt,
                      color: BookmiColors.brandBlueLight,
                      size: 22,
                    ),
                  ),
                  const SizedBox(width: BookmiSpacing.spaceSm),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Réservation express',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                        Text(
                          'Acceptation automatique par le talent',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.white.withValues(alpha: 0.6),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Switch(
                    value: widget.isExpress,
                    onChanged: widget.onExpressChanged,
                    activeColor: BookmiColors.brandBlueLight,
                    activeTrackColor: BookmiColors.brandBlueLight.withValues(
                      alpha: 0.3,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: BookmiSpacing.spaceMd),
          ],

          // Optional message
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Message au talent (optionnel)',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceSm),
                TextField(
                  controller: _messageController,
                  onChanged: widget.onMessageChanged,
                  maxLines: 3,
                  style: const TextStyle(color: Colors.white, fontSize: 14),
                  decoration: InputDecoration(
                    hintText:
                        "Précisez vos attentes, le thème de l'événement…",
                    hintStyle: TextStyle(
                      color: Colors.white.withValues(alpha: 0.4),
                      fontSize: 13,
                    ),
                    filled: true,
                    fillColor: BookmiColors.glassDarkMedium,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: BookmiColors.glassBorder),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: BookmiColors.glassBorder),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(
                        color: BookmiColors.brandBlue,
                      ),
                    ),
                    contentPadding: const EdgeInsets.all(BookmiSpacing.spaceSm),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceMd),

          // Security shield
          Container(
            padding: const EdgeInsets.all(BookmiSpacing.spaceSm),
            decoration: BoxDecoration(
              color: BookmiColors.brandBlue.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: BookmiColors.brandBlue.withValues(alpha: 0.2),
              ),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.shield_outlined,
                  size: 20,
                  color: BookmiColors.brandBlueLight.withValues(alpha: 0.8),
                ),
                const SizedBox(width: BookmiSpacing.spaceSm),
                Expanded(
                  child: Text(
                    'Paiement sécurisé · Contrat généré automatiquement · '
                    "Remboursement garanti en cas d'annulation éligible",
                    style: TextStyle(
                      fontSize: 11,
                      color: Colors.white.withValues(alpha: 0.6),
                      height: 1.4,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: BookmiSpacing.spaceBase),
        ],
      ),
    );
  }
}

// ── Promo Apply / Clear button ────────────────────────────────────────────────

class _PromoButton extends StatelessWidget {
  const _PromoButton({
    required this.applied,
    required this.loading,
    required this.onApply,
    required this.onClear,
  });

  final bool applied;
  final bool loading;
  final VoidCallback? onApply;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return const SizedBox(
        width: 24,
        height: 24,
        child: CircularProgressIndicator(
          strokeWidth: 2,
          color: BookmiColors.brandBlueLight,
        ),
      );
    }

    if (applied) {
      return IconButton(
        onPressed: onClear,
        icon: const Icon(Icons.close, size: 20, color: Colors.white54),
        tooltip: 'Supprimer le code promo',
        padding: EdgeInsets.zero,
        constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
      );
    }

    return TextButton(
      onPressed: onApply,
      style: TextButton.styleFrom(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        backgroundColor: BookmiColors.brandBlue.withValues(alpha: 0.2),
        foregroundColor: BookmiColors.brandBlueLight,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
          side: BorderSide(
            color: BookmiColors.brandBlue.withValues(alpha: 0.4),
          ),
        ),
      ),
      child: const Text(
        'Appliquer',
        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
      ),
    );
  }
}

// ── _RecapRow ─────────────────────────────────────────────────────────────────

class _RecapRow extends StatelessWidget {
  const _RecapRow({
    required this.label,
    required this.value,
    this.labelColor,
    this.valueColor,
    this.fontSize = 14,
    this.fontWeight = FontWeight.w500,
  });

  final String label;
  final String value;
  final Color? labelColor;
  final Color? valueColor;
  final double fontSize;
  final FontWeight fontWeight;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Text(
            label,
            style: TextStyle(
              fontSize: fontSize,
              color: labelColor ?? Colors.white,
              fontWeight: fontWeight,
            ),
          ),
        ),
        const SizedBox(width: BookmiSpacing.spaceSm),
        Text(
          value,
          style: TextStyle(
            fontSize: fontSize,
            color: valueColor ?? Colors.white,
            fontWeight: fontWeight,
          ),
        ),
      ],
    );
  }
}
