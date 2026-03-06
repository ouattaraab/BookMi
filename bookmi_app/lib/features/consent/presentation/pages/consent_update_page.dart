import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/consent/bloc/consent_cubit.dart';
import 'package:bookmi_app/features/consent/bloc/consent_state.dart';
import 'package:bookmi_app/features/consent/data/repositories/consent_repository.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

/// Page de re-consentement forcé (CGU mise à jour).
class ConsentUpdatePage extends StatelessWidget {
  const ConsentUpdatePage({super.key});

  static Widget wrapped(BuildContext context) {
    return BlocProvider(
      create: (_) => ConsentCubit(
        repository: ConsentRepository(apiClient: context.read<ApiClient>()),
      ),
      child: const ConsentUpdatePage(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return const _ConsentUpdateView();
  }
}

class _ConsentUpdateView extends StatefulWidget {
  const _ConsentUpdateView();

  @override
  State<_ConsentUpdateView> createState() => _ConsentUpdateViewState();
}

class _ConsentUpdateViewState extends State<_ConsentUpdateView> {
  bool _cguAccepted = false;
  bool _dataProcessingAccepted = false;

  bool get _canSubmit => _cguAccepted && _dataProcessingAccepted;

  Future<void> _submit() async {
    await context.read<ConsentCubit>().reconsent({
      'cgu_update': true,
      'data_processing': true,
    });
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<ConsentCubit, ConsentState>(
      listener: (context, state) {
        if (state is ConsentSuccess) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: BookmiColors.success,
            ),
          );
          // Refresh auth profile so requiresReconsent becomes false
          context.read<AuthBloc>().add(const AuthCheckRequested());
          context.go('/home');
        } else if (state is ConsentFailure) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: BookmiColors.error,
            ),
          );
        }
      },
      child: Scaffold(
        body: DecoratedBox(
          decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(BookmiSpacing.spaceBase),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: BookmiSpacing.spaceLg),
                  const Icon(
                    Icons.gavel_outlined,
                    color: Colors.white,
                    size: 48,
                  ),
                  const SizedBox(height: BookmiSpacing.spaceBase),
                  const Text(
                    'Mise à jour des conditions',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  Text(
                    'Nos conditions générales d\'utilisation ont été mises à jour.'
                    ' Veuillez les accepter pour continuer à utiliser BookMi.',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.8),
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: BookmiSpacing.spaceLg),
                  _ConsentCheckbox(
                    value: _cguAccepted,
                    onChanged: (v) => setState(() => _cguAccepted = v ?? false),
                    label: RichText(
                      text: TextSpan(
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                        ),
                        children: [
                          const TextSpan(text: "J'accepte les nouvelles "),
                          TextSpan(
                            text: 'CGU et politique de confidentialité',
                            style: const TextStyle(
                              color: BookmiColors.brandBlueLight,
                              decoration: TextDecoration.underline,
                            ),
                            recognizer: TapGestureRecognizer()
                              ..onTap = () => launchUrl(
                                Uri.parse('https://bookmi.click/terms'),
                              ),
                          ),
                          const TextSpan(text: '.'),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceSm),
                  _ConsentCheckbox(
                    value: _dataProcessingAccepted,
                    onChanged: (v) =>
                        setState(() => _dataProcessingAccepted = v ?? false),
                    label: const Text(
                      'J\'accepte le traitement de mes données personnelles '
                      'conformément à la loi n°2013-450.',
                      style: TextStyle(color: Colors.white, fontSize: 14),
                    ),
                  ),
                  const Spacer(),
                  BlocBuilder<ConsentCubit, ConsentState>(
                    builder: (context, state) {
                      final isLoading = state is ConsentUpdating;
                      return ElevatedButton(
                        onPressed: (_canSubmit && !isLoading) ? _submit : null,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: BookmiColors.brandBlue,
                          disabledBackgroundColor: Colors.white.withValues(
                            alpha: 0.15,
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: isLoading
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(
                                  color: Colors.white,
                                  strokeWidth: 2,
                                ),
                              )
                            : const Text(
                                'Accepter et continuer',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                      );
                    },
                  ),
                  const SizedBox(height: BookmiSpacing.spaceBase),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _ConsentCheckbox extends StatelessWidget {
  const _ConsentCheckbox({
    required this.value,
    required this.onChanged,
    required this.label,
  });

  final bool value;
  final ValueChanged<bool?> onChanged;
  final Widget label;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Checkbox(
          value: value,
          onChanged: onChanged,
          checkColor: Colors.white,
          fillColor: WidgetStateProperty.resolveWith((states) {
            if (states.contains(WidgetState.selected)) {
              return BookmiColors.brandBlue;
            }
            return Colors.white.withValues(alpha: 0.1);
          }),
          side: BorderSide(
            color: Colors.white.withValues(alpha: 0.5),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Padding(padding: const EdgeInsets.only(top: 12), child: label),
        ),
      ],
    );
  }
}
