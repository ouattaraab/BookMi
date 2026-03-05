import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/services/biometric_service.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:bookmi_app/core/validators/form_validators.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_button.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_text_field.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _termsAccepted = false;
  bool _biometricAvailable = false;
  final _biometricService = BiometricService();
  final _secureStorage = SecureStorage();

  @override
  void initState() {
    super.initState();
    _checkBiometricAvailability();
  }

  Future<void> _checkBiometricAvailability() async {
    final enabled = await _secureStorage.isBiometricEnabled();
    if (!enabled) return;
    final available = await _biometricService.isAvailable();
    if (mounted) setState(() => _biometricAvailable = available);
  }

  Future<void> _onBiometricLogin() async {
    final authenticated = await _biometricService.authenticate(
      reason: 'Connectez-vous à BookMi avec votre empreinte digitale',
    );
    if (!authenticated || !mounted) return;
    final creds = await _secureStorage.getBiometricCredentials();
    if (creds == null || !mounted) return;
    context.read<AuthBloc>().add(
      AuthLoginSubmitted(email: creds.email, password: creds.password),
    );
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _onSubmit() {
    if (!_formKey.currentState!.validate()) return;
    if (!_termsAccepted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(
          const SnackBar(
            content: Text(
              "Veuillez accepter les conditions d'utilisation pour continuer.",
            ),
            backgroundColor: Color(0xFFE53E3E),
          ),
        );
      return;
    }
    context.read<AuthBloc>().add(
      AuthLoginSubmitted(
        email: _emailController.text.trim(),
        password: _passwordController.text,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        switch (state) {
          case AuthAuthenticated():
            context.go(RoutePaths.home);
          case AuthFailure(
            code: 'AUTH_PHONE_NOT_VERIFIED',
            details: final d,
          ):
            final phone = d?['phone'] as String? ?? '';
            context.go(RoutePaths.otp, extra: phone);
          case AuthFailure(:final message):
            ScaffoldMessenger.of(context)
              ..hideCurrentSnackBar()
              ..showSnackBar(
                SnackBar(
                  content: Text(message),
                  backgroundColor: BookmiColors.error,
                ),
              );
          default:
            break;
        }
      },
      child: Scaffold(
        body: DecoratedBox(
          decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
          child: SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(BookmiSpacing.spaceLg),
                child: Column(
                  children: [
                    // Logo
                    const Icon(
                      Icons.music_note_rounded,
                      size: 60,
                      color: BookmiColors.brandBlueLight,
                    ),
                    const SizedBox(height: BookmiSpacing.spaceSm),
                    Text(
                      'BookMi',
                      style: Theme.of(context).textTheme.displayMedium
                          ?.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceXl),
                    // Form
                    GlassCard(
                      child: Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Text(
                              'Connexion',
                              style: Theme.of(context).textTheme.titleLarge
                                  ?.copyWith(color: Colors.white),
                            ),
                            const SizedBox(height: BookmiSpacing.spaceLg),
                            AuthTextField(
                              label: 'Email',
                              controller: _emailController,
                              keyboardType: TextInputType.emailAddress,
                              prefixIcon: Icons.email_outlined,
                              autofillHints: const [AutofillHints.email],
                              textInputAction: TextInputAction.next,
                              validator: validateEmail,
                            ),
                            const SizedBox(height: BookmiSpacing.spaceBase),
                            AuthTextField(
                              label: 'Mot de passe',
                              controller: _passwordController,
                              obscureText: true,
                              prefixIcon: Icons.lock_outline,
                              autofillHints: const [AutofillHints.password],
                              textInputAction: TextInputAction.done,
                              onFieldSubmitted: (_) => _onSubmit(),
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Le mot de passe est requis.';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: BookmiSpacing.spaceSm),
                            // Forgot password link
                            Align(
                              alignment: Alignment.centerRight,
                              child: TextButton(
                                onPressed: () =>
                                    context.go(RoutePaths.forgotPassword),
                                child: const Text(
                                  'Mot de passe oublié ?',
                                  style: TextStyle(
                                    color: BookmiColors.brandBlueLight,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: BookmiSpacing.spaceBase),
                            _TermsCheckbox(
                              value: _termsAccepted,
                              onChanged: (v) =>
                                  setState(() => _termsAccepted = v ?? false),
                            ),
                            const SizedBox(height: BookmiSpacing.spaceBase),
                            BlocBuilder<AuthBloc, AuthState>(
                              builder: (context, state) {
                                return AuthButton(
                                  label: 'Se connecter',
                                  isLoading: state is AuthLoading,
                                  onPressed: _onSubmit,
                                );
                              },
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceLg),
                    // Biometric login
                    if (_biometricAvailable) ...[
                      const _OrDivider(),
                      const SizedBox(height: BookmiSpacing.spaceBase),
                      OutlinedButton.icon(
                        onPressed: _onBiometricLogin,
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.white,
                          side: const BorderSide(
                            color: BookmiColors.glassBorder,
                          ),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          minimumSize: const Size(double.infinity, 48),
                        ),
                        icon: const Icon(
                          Icons.fingerprint,
                          color: BookmiColors.brandBlueLight,
                        ),
                        label: const Text('Connexion biométrique'),
                      ),
                      const SizedBox(height: BookmiSpacing.spaceLg),
                    ],
                    // Register link
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'Pas encore de compte ? ',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.7),
                          ),
                        ),
                        TextButton(
                          onPressed: () => context.go(RoutePaths.register),
                          child: const Text(
                            "S'inscrire",
                            style: TextStyle(
                              color: BookmiColors.brandBlueLight,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                    TextButton.icon(
                      onPressed: () => context.go(RoutePaths.home),
                      icon: Icon(
                        Icons.home_outlined,
                        size: 16,
                        color: Colors.white.withValues(alpha: 0.45),
                      ),
                      label: Text(
                        "Retour à l'accueil",
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.45),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _OrDivider extends StatelessWidget {
  const _OrDivider();

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Divider(
            color: Colors.white.withValues(alpha: 0.2),
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          child: Text(
            'ou',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.45),
              fontSize: 12,
            ),
          ),
        ),
        Expanded(
          child: Divider(
            color: Colors.white.withValues(alpha: 0.2),
          ),
        ),
      ],
    );
  }
}

class _TermsCheckbox extends StatelessWidget {
  const _TermsCheckbox({required this.value, required this.onChanged});

  final bool value;
  final ValueChanged<bool?> onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Checkbox(
          value: value,
          onChanged: onChanged,
          activeColor: BookmiColors.brandBlue,
          side: const BorderSide(color: BookmiColors.glassBorder, width: 1.5),
          materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
          visualDensity: VisualDensity.compact,
        ),
        const SizedBox(width: 4),
        Expanded(
          child: GestureDetector(
            onTap: () => onChanged(!value),
            child: Padding(
              padding: const EdgeInsets.only(top: 10),
              child: RichText(
                text: TextSpan(
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.6),
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                    height: 1.5,
                  ),
                  children: [
                    const TextSpan(text: "J'accepte les "),
                    TextSpan(
                      text: "Conditions d'utilisation",
                      style: const TextStyle(
                        color: BookmiColors.brandBlueLight,
                        fontWeight: FontWeight.w700,
                      ),
                      recognizer: TapGestureRecognizer()
                        ..onTap = () => launchUrl(
                          Uri.parse(
                            'https://bookmi.click/conditions-utilisation',
                          ),
                        ),
                    ),
                    const TextSpan(text: ' et la '),
                    TextSpan(
                      text: 'Politique de confidentialité',
                      style: const TextStyle(
                        color: BookmiColors.brandBlueLight,
                        fontWeight: FontWeight.w700,
                      ),
                      recognizer: TapGestureRecognizer()
                        ..onTap = () => launchUrl(
                          Uri.parse(
                            'https://bookmi.click/politique-confidentialite',
                          ),
                        ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }
}
