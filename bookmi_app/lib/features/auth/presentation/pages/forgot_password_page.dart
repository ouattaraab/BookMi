import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_button.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_text_field.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

class ForgotPasswordPage extends StatefulWidget {
  const ForgotPasswordPage({super.key});

  @override
  State<ForgotPasswordPage> createState() => _ForgotPasswordPageState();
}

class _ForgotPasswordPageState extends State<ForgotPasswordPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  bool _submitted = false;

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  void _onSubmit() {
    if (!_formKey.currentState!.validate()) return;
    context.read<AuthBloc>().add(
      AuthForgotPasswordSubmitted(
        email: _emailController.text.trim(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        if (state is AuthForgotPasswordSuccess) {
          setState(() => _submitted = true);
        } else if (state is AuthFailure) {
          // Always show success message (anti-enumeration)
          setState(() => _submitted = true);
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
                    const Icon(
                      Icons.lock_reset_rounded,
                      size: 60,
                      color: BookmiColors.brandBlueLight,
                    ),
                    const SizedBox(height: BookmiSpacing.spaceBase),
                    Text(
                      'Mot de passe oublié',
                      style: Theme.of(context).textTheme.headlineLarge
                          ?.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceXl),
                    if (_submitted)
                      GlassCard(
                        child: Column(
                          children: [
                            const Icon(
                              Icons.check_circle_outline,
                              size: 48,
                              color: BookmiColors.success,
                            ),
                            const SizedBox(height: BookmiSpacing.spaceBase),
                            Text(
                              'Si un compte existe avec cet email, un '
                              'lien de réinitialisation a été envoyé.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.9),
                                fontSize: 16,
                              ),
                            ),
                            const SizedBox(height: BookmiSpacing.spaceLg),
                            AuthButton(
                              label: 'Retour à la connexion',
                              onPressed: () => context.go(RoutePaths.login),
                            ),
                          ],
                        ),
                      )
                    else
                      GlassCard(
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                'Entrez votre adresse email pour '
                                'recevoir un lien de réinitialisation.',
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.8),
                                ),
                              ),
                              const SizedBox(
                                height: BookmiSpacing.spaceLg,
                              ),
                              AuthTextField(
                                label: 'Email',
                                controller: _emailController,
                                keyboardType: TextInputType.emailAddress,
                                prefixIcon: Icons.email_outlined,
                                textInputAction: TextInputAction.done,
                                onFieldSubmitted: (_) => _onSubmit(),
                                validator: (value) {
                                  if (value == null || value.trim().isEmpty) {
                                    return "L'email est requis.";
                                  }
                                  final emailRegex = RegExp(
                                    r'^[^@\s]+@[^@\s]+\.[^@\s]+$',
                                  );
                                  if (!emailRegex.hasMatch(value.trim())) {
                                    return 'Veuillez entrer une adresse '
                                        'e-mail valide.';
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(
                                height: BookmiSpacing.spaceLg,
                              ),
                              BlocBuilder<AuthBloc, AuthState>(
                                builder: (context, state) {
                                  return AuthButton(
                                    label: 'Envoyer le lien',
                                    isLoading: state is AuthLoading,
                                    onPressed: _onSubmit,
                                  );
                                },
                              ),
                            ],
                          ),
                        ),
                      ),
                    const SizedBox(height: BookmiSpacing.spaceBase),
                    if (!_submitted)
                      TextButton(
                        onPressed: () => context.go(RoutePaths.login),
                        child: Text(
                          'Retour à la connexion',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.7),
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
