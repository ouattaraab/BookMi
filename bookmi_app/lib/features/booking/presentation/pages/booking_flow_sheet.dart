import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/booking/bloc/booking_flow/booking_flow_bloc.dart';
import 'package:bookmi_app/features/booking/bloc/booking_flow/booking_flow_event.dart';
import 'package:bookmi_app/features/booking/bloc/booking_flow/booking_flow_state.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:bookmi_app/core/design_system/components/celebration_overlay.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/step1_package_selection.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/step2_date_location.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/step3_recap.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/step4_payment.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/stepper_progress.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Opens the booking flow as a modal bottom sheet.
///
/// Call [BookingFlowSheet.show] rather than instantiating directly.
class BookingFlowSheet extends StatefulWidget {
  const BookingFlowSheet({
    required this.talentProfileId,
    required this.talentStageName,
    required this.servicePackages,
    required this.enableExpress,
    super.key,
  });

  final int talentProfileId;
  final String talentStageName;
  final List<Map<String, dynamic>> servicePackages;
  final bool enableExpress;

  /// Convenience helper — shows the sheet and returns the created booking or
  /// null if the user dismissed without completing.
  static Future<void> show(
    BuildContext context, {
    required BookingRepository repository,
    required int talentProfileId,
    required String talentStageName,
    required List<Map<String, dynamic>> servicePackages,
    required bool enableExpress,
  }) {
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => BlocProvider(
        create: (_) => BookingFlowBloc(repository: repository),
        child: BookingFlowSheet(
          talentProfileId: talentProfileId,
          talentStageName: talentStageName,
          servicePackages: servicePackages,
          enableExpress: enableExpress,
        ),
      ),
    );
  }

  @override
  State<BookingFlowSheet> createState() => _BookingFlowSheetState();
}

class _BookingFlowSheetState extends State<BookingFlowSheet> {
  int _currentStep = 0;
  static const int _totalSteps = 4;

  // Step 1
  int? _selectedPackageId;
  Map<String, dynamic>? _selectedPackage;

  // Step 2
  DateTime? _selectedDate;
  TimeOfDay? _selectedTime;
  String _location = '';

  // Step 3
  bool _isExpress = false;
  String _message = '';

  // Step 4 — Payment
  int? _createdBookingId;
  String? _selectedPaymentMethod;
  String _paymentPhone = '';

  // Computed devis (set after step 1 selection)
  int get _cachetAmount {
    final attrs =
        _selectedPackage?['attributes'] as Map<String, dynamic>? ??
        _selectedPackage ??
        {};
    return attrs['cachet_amount'] as int? ?? 0;
  }

  int get _commissionAmount => (_cachetAmount * 0.15).round();
  int get _totalAmount => _cachetAmount + _commissionAmount;

  String get _formattedDate {
    if (_selectedDate == null) return '';
    const months = [
      'jan', 'fév', 'mar', 'avr', 'mai', 'juin',
      'juil', 'aoû', 'sep', 'oct', 'nov', 'déc',
    ];
    final datePart = '${_selectedDate!.day} ${months[_selectedDate!.month - 1]}. '
        '${_selectedDate!.year}';
    if (_selectedTime == null) return datePart;
    final h = _selectedTime!.hour.toString().padLeft(2, '0');
    final m = _selectedTime!.minute.toString().padLeft(2, '0');
    return '$datePart à $h:$m';
  }

  bool get _canProceed {
    return switch (_currentStep) {
      0 => _selectedPackageId != null,
      1 => _selectedDate != null && _selectedTime != null && _location.trim().isNotEmpty,
      2 => true,
      3 => _selectedPaymentMethod != null && _paymentPhone.trim().length >= 8,
      _ => false,
    };
  }

  void _onNext() {
    if (!_canProceed) return;
    switch (_currentStep) {
      case 0:
      case 1:
        setState(() => _currentStep++);
      case 2:
        // Submit booking request — listener advances to step 3 on success
        _submit();
      case 3:
        _pay();
    }
  }

  void _onBack() {
    if (_currentStep > 0) {
      setState(() => _currentStep--);
    } else {
      Navigator.of(context).pop();
    }
  }

  void _pay() {
    if (_createdBookingId == null || _selectedPaymentMethod == null) return;
    context.read<BookingFlowBloc>().add(
      BookingFlowPaymentInitiated(
        bookingId: _createdBookingId!,
        paymentMethod: _selectedPaymentMethod!,
        phoneNumber: _paymentPhone.trim(),
      ),
    );
  }

  void _submit() {
    final pkg =
        _selectedPackage?['attributes'] as Map<String, dynamic>? ??
        _selectedPackage ??
        {};
    final pkgId = pkg['id'] as int? ?? _selectedPackageId!;

    context.read<BookingFlowBloc>().add(
      BookingFlowSubmitted(
        talentProfileId: widget.talentProfileId,
        servicePackageId: pkgId,
        eventDate:
            '${_selectedDate!.year}-'
            '${_selectedDate!.month.toString().padLeft(2, '0')}-'
            '${_selectedDate!.day.toString().padLeft(2, '0')}'
            'T${_selectedTime!.hour.toString().padLeft(2, '0')}:'
            '${_selectedTime!.minute.toString().padLeft(2, '0')}:00',
        eventLocation: _location.trim(),
        message: _message.trim().isEmpty ? null : _message.trim(),
        isExpress: _isExpress,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final screenHeight = MediaQuery.of(context).size.height;
    final bottomPadding = MediaQuery.of(context).padding.bottom;

    return BlocListener<BookingFlowBloc, BookingFlowState>(
      listener: (context, state) {
        if (state is BookingFlowSuccess) {
          // Booking created — store ID and advance to payment step
          setState(() {
            _createdBookingId = state.booking.id;
            _currentStep = 3;
          });
        } else if (state is BookingFlowPaymentSuccess) {
          // Payment initiated — celebrate then close
          CelebrationOverlay.show(
            context,
            title: 'Paiement initié !',
            subtitle:
                'Approuvez la notification Mobile Money sur votre téléphone '
                'dans les 15 secondes.',
            // Guard against popping if widget is disposed before 2.5 s (M1-FIX).
            onDismiss: () {
              if (context.mounted) Navigator.of(context).pop();
            },
          );
        } else if (state is BookingFlowFailure) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: BookmiColors.error,
            ),
          );
        }
      },
      child: Container(
        height: screenHeight * 0.92,
        decoration: const BoxDecoration(
          gradient: BookmiColors.gradientHero,
          borderRadius: BookmiRadius.sheetBorder,
        ),
        child: Column(
          children: [
            // Drag handle
            const SizedBox(height: BookmiSpacing.spaceSm),
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.3),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(
                BookmiSpacing.spaceBase,
                BookmiSpacing.spaceSm,
                BookmiSpacing.spaceBase,
                0,
              ),
              child: Row(
                children: [
                  // Hide back button on payment step — booking already created,
                  // going back would risk re-submitting it (H1-FIX).
                  if (_currentStep > 0 && _currentStep < 3)
                    IconButton(
                      icon: const Icon(
                        Icons.arrow_back_ios_new,
                        color: Colors.white70,
                        size: 18,
                      ),
                      onPressed: _onBack,
                    )
                  else
                    const SizedBox(width: 48),
                  Expanded(
                    child: Column(
                      children: [
                        Text(
                          widget.talentStageName,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                          textAlign: TextAlign.center,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          _stepTitle,
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.white.withValues(alpha: 0.6),
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(
                      Icons.close,
                      color: Colors.white54,
                      size: 20,
                    ),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ],
              ),
            ),
            // Stepper
            StepperProgress(
              currentStep: _currentStep,
              totalSteps: _totalSteps,
            ),
            const Divider(color: Colors.white12, height: 1),
            // Step content
            Expanded(child: _buildStep()),
            // Bottom CTA
            _BottomCta(
              currentStep: _currentStep,
              totalSteps: _totalSteps,
              canProceed: _canProceed,
              onNext: _onNext,
              bottomPadding: bottomPadding,
            ),
          ],
        ),
      ),
    );
  }

  String get _stepTitle => switch (_currentStep) {
    0 => 'Choisir un package',
    1 => 'Date & lieu',
    2 => 'Récapitulatif',
    3 => 'Paiement',
    _ => '',
  };

  Widget _buildStep() {
    return switch (_currentStep) {
      0 => Step1PackageSelection(
        packages: widget.servicePackages,
        selectedPackageId: _selectedPackageId,
        onPackageSelected: (id) {
          setState(() {
            _selectedPackageId = id;
            _selectedPackage = widget.servicePackages.firstWhere(
              (p) => (p['id'] as int?) == id,
              orElse: () => {},
            );
          });
        },
      ),
      1 => Step2DateLocation(
        selectedDate: _selectedDate,
        selectedTime: _selectedTime,
        location: _location,
        onDateSelected: (d) => setState(() => _selectedDate = d),
        onTimeSelected: (t) => setState(() => _selectedTime = t),
        onLocationChanged: (v) => setState(() => _location = v),
      ),
      2 => BlocBuilder<BookingFlowBloc, BookingFlowState>(
        builder: (context, state) {
          return Step3Recap(
            packageName: (() {
              final attrs =
                  _selectedPackage?['attributes'] as Map<String, dynamic>? ??
                  _selectedPackage ??
                  {};
              return attrs['name'] as String? ?? '';
            })(),
            cachetAmount: _cachetAmount,
            commissionAmount: _commissionAmount,
            totalAmount: _totalAmount,
            eventDate: _formattedDate,
            eventLocation: _location,
            enableExpress: widget.enableExpress,
            isExpress: _isExpress,
            message: _message,
            onExpressChanged: (v) => setState(() => _isExpress = v),
            onMessageChanged: (v) => setState(() => _message = v),
          );
        },
      ),
      3 => Step4Payment(
        totalAmount: _totalAmount,
        selectedMethod: _selectedPaymentMethod,
        phoneNumber: _paymentPhone,
        onMethodSelected: (method) =>
            setState(() => _selectedPaymentMethod = method),
        onPhoneChanged: (v) => setState(() => _paymentPhone = v),
      ),
      _ => const SizedBox.shrink(),
    };
  }
}

class _BottomCta extends StatelessWidget {
  const _BottomCta({
    required this.currentStep,
    required this.totalSteps,
    required this.canProceed,
    required this.onNext,
    required this.bottomPadding,
  });

  final int currentStep;
  final int totalSteps;
  final bool canProceed;
  final VoidCallback onNext;
  final double bottomPadding;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<BookingFlowBloc, BookingFlowState>(
      builder: (context, state) {
        final isSubmitting = state is BookingFlowSubmitting ||
            state is BookingFlowPaymentSubmitting;
        final isLastStep = currentStep == totalSteps - 1;
        final label = switch (currentStep) {
          2 => 'Confirmer la réservation',
          _ when isLastStep => 'Payer maintenant',
          _ => 'Continuer',
        };

        return Padding(
          padding: EdgeInsets.fromLTRB(
            BookmiSpacing.spaceBase,
            BookmiSpacing.spaceSm,
            BookmiSpacing.spaceBase,
            bottomPadding + BookmiSpacing.spaceSm,
          ),
          child: Container(
            height: 52,
            decoration: BoxDecoration(
              gradient: canProceed && !isSubmitting
                  ? (isLastStep || currentStep == 2
                      ? BookmiColors.gradientCta
                      : BookmiColors.gradientBrand)
                  : null,
              color: canProceed && !isSubmitting
                  ? null
                  : Colors.white.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(BookmiRadius.button),
            ),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                borderRadius: BorderRadius.circular(BookmiRadius.button),
                onTap: canProceed && !isSubmitting ? onNext : null,
                child: Center(
                  child: isSubmitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : Text(
                          label,
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                            color: canProceed
                                ? Colors.white
                                : Colors.white.withValues(alpha: 0.4),
                          ),
                        ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
