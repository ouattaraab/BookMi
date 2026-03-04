import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/validators/form_validators.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/repositories/auth_repository.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_button.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_text_field.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/phone_field.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  String _role = 'client';
  bool _termsAccepted = false;
  final List<int> _selectedCategoryIds = [];
  List<Map<String, dynamic>> _categories = [];
  @override
  void initState() {
    super.initState();
    unawaited(_loadCategories());
  }

  Future<void> _loadCategories() async {
    final result = await context.read<AuthRepository>().getCategories();
    if (!mounted) return;
    switch (result) {
      case ApiSuccess(:final data):
        setState(() {
          _categories = data;
        });
      case ApiFailure():
        break; // Categories loading is non-blocking
    }
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
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

    final phone = '+225${_phoneController.text.replaceAll(' ', '')}';

    final data = <String, dynamic>{
      'first_name': _firstNameController.text.trim(),
      'last_name': _lastNameController.text.trim(),
      'email': _emailController.text.trim(),
      'phone': phone,
      'password': _passwordController.text,
      'password_confirmation': _confirmPasswordController.text,
      'role': _role,
    };

    if (_role == 'talent') {
      if (_selectedCategoryIds.isEmpty) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(
            const SnackBar(
              content: Text('Veuillez sélectionner au moins une catégorie.'),
              backgroundColor: Color(0xFFE53E3E),
            ),
          );
        return;
      }
      data['category_ids'] = _selectedCategoryIds;
    }

    context.read<AuthBloc>().add(AuthRegisterSubmitted(data: data));
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        switch (state) {
          // New backend: auto-login → router handles navigation.
          case AuthAuthenticated():
            break;
          // Old backend fallback: no token returned → redirect to login.
          case AuthRegistrationSuccess():
            ScaffoldMessenger.of(context)
              ..hideCurrentSnackBar()
              ..showSnackBar(
                const SnackBar(
                  content: Text('Compte créé ! Connectez-vous.'),
                  backgroundColor: Color(0xFF4CAF50),
                  duration: Duration(seconds: 3),
                ),
              );
            context.go(RoutePaths.login);
          case AuthFailure(code: 'VALIDATION_FAILED', message: final msg):
            ScaffoldMessenger.of(context)
              ..hideCurrentSnackBar()
              ..showSnackBar(
                SnackBar(
                  content: Text(msg),
                  backgroundColor: BookmiColors.error,
                ),
              );
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
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(BookmiSpacing.spaceLg),
              child: Column(
                children: [
                  const SizedBox(height: BookmiSpacing.spaceBase),
                  Text(
                    'Créer un compte',
                    style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: BookmiSpacing.spaceLg),
                  GlassCard(
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          AuthTextField(
                            label: 'Prénom',
                            controller: _firstNameController,
                            prefixIcon: Icons.person_outline,
                            textInputAction: TextInputAction.next,
                            validator: (v) => v == null || v.trim().isEmpty
                                ? 'Le prénom est requis.'
                                : null,
                          ),
                          const SizedBox(height: BookmiSpacing.spaceBase),
                          AuthTextField(
                            label: 'Nom',
                            controller: _lastNameController,
                            prefixIcon: Icons.person_outline,
                            textInputAction: TextInputAction.next,
                            validator: (v) => v == null || v.trim().isEmpty
                                ? 'Le nom est requis.'
                                : null,
                          ),
                          const SizedBox(height: BookmiSpacing.spaceBase),
                          AuthTextField(
                            label: 'Email',
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            prefixIcon: Icons.email_outlined,
                            textInputAction: TextInputAction.next,
                            validator: validateEmail,
                          ),
                          const SizedBox(height: BookmiSpacing.spaceBase),
                          PhoneField(
                            controller: _phoneController,
                            textInputAction: TextInputAction.next,
                            validator: (value) {
                              final digits = value?.replaceAll(' ', '') ?? '';
                              if (digits.isEmpty) {
                                return 'Le numéro de téléphone est requis.';
                              }
                              if (digits.length != 10) {
                                return 'Le numéro de téléphone doit contenir '
                                    '10 chiffres.';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: BookmiSpacing.spaceBase),
                          AuthTextField(
                            label: 'Mot de passe',
                            controller: _passwordController,
                            obscureText: true,
                            prefixIcon: Icons.lock_outline,
                            textInputAction: TextInputAction.next,
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Le mot de passe est requis.';
                              }
                              if (value.length < 8) {
                                return 'Le mot de passe doit contenir au '
                                    'moins 8 caractères.';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: BookmiSpacing.spaceBase),
                          AuthTextField(
                            label: 'Confirmer le mot de passe',
                            controller: _confirmPasswordController,
                            obscureText: true,
                            prefixIcon: Icons.lock_outline,
                            textInputAction: TextInputAction.next,
                            validator: (value) {
                              if (value != _passwordController.text) {
                                return 'Les mots de passe ne correspondent '
                                    'pas.';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: BookmiSpacing.spaceLg),
                          // Role selector
                          Text(
                            'Vous êtes :',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.9),
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: BookmiSpacing.spaceSm),
                          Row(
                            children: [
                              _RoleChip(
                                label: 'Client',
                                selected: _role == 'client',
                                onTap: () => setState(() => _role = 'client'),
                              ),
                              const SizedBox(
                                width: BookmiSpacing.spaceMd,
                              ),
                              _RoleChip(
                                label: 'Talent',
                                selected: _role == 'talent',
                                onTap: () => setState(() => _role = 'talent'),
                              ),
                              const SizedBox(
                                width: BookmiSpacing.spaceMd,
                              ),
                              _RoleChip(
                                label: 'Manager',
                                selected: _role == 'manager',
                                onTap: () => setState(() => _role = 'manager'),
                              ),
                            ],
                          ),
                          // Category multi-select (talent only)
                          if (_role == 'talent') ...[
                            const SizedBox(height: BookmiSpacing.spaceBase),
                            _buildCategoryMultiSelect(isDark),
                          ],
                          const SizedBox(height: BookmiSpacing.spaceLg),
                          _TermsCheckbox(
                            value: _termsAccepted,
                            onChanged: (v) =>
                                setState(() => _termsAccepted = v ?? false),
                          ),
                          const SizedBox(height: BookmiSpacing.spaceBase),
                          BlocBuilder<AuthBloc, AuthState>(
                            builder: (context, state) {
                              return AuthButton(
                                label: "S'inscrire",
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
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Déjà un compte ? ',
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.7),
                        ),
                      ),
                      TextButton(
                        onPressed: () => context.go(RoutePaths.login),
                        child: const Text(
                          'Se connecter',
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
    );
  }

  Widget _buildCategoryMultiSelect(bool isDark) {
    final containerColor = isDark
        ? BookmiColors.glassWhite
        : Colors.white.withValues(alpha: 0.15);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(
              Icons.category_outlined,
              size: 16,
              color: Colors.white70,
            ),
            const SizedBox(width: 6),
            Text(
              'Catégories artistiques',
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.9),
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
            const SizedBox(width: 4),
            Text(
              '(min. 1)',
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.5),
                fontSize: 12,
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        if (_categories.isEmpty)
          Text(
            'Chargement des catégories...',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.5),
              fontSize: 13,
            ),
          )
        else
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: containerColor,
              borderRadius: BookmiRadius.inputBorder,
              border: Border.all(color: BookmiColors.glassBorder),
            ),
            child: Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _categories.map((cat) {
                final id = cat['id'] as int;
                final name = cat['name'] as String;
                final isSelected = _selectedCategoryIds.contains(id);

                return GestureDetector(
                  onTap: () {
                    setState(() {
                      if (isSelected) {
                        _selectedCategoryIds.remove(id);
                      } else {
                        _selectedCategoryIds.add(id);
                      }
                    });
                  },
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 150),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: isSelected
                          ? BookmiColors.brandBlue
                          : Colors.white.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: isSelected
                            ? BookmiColors.brandBlue
                            : BookmiColors.glassBorder,
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (isSelected) ...[
                          const Icon(
                            Icons.check,
                            size: 14,
                            color: Colors.white,
                          ),
                          const SizedBox(width: 4),
                        ],
                        Text(
                          name,
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: isSelected
                                ? FontWeight.w600
                                : FontWeight.w400,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
        if (_selectedCategoryIds.isEmpty && _categories.isNotEmpty)
          Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Text(
              'Sélectionnez au moins une catégorie.',
              style: TextStyle(
                color: BookmiColors.error,
                fontSize: 12,
              ),
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

class _RoleChip extends StatelessWidget {
  const _RoleChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(
          horizontal: BookmiSpacing.spaceBase,
          vertical: BookmiSpacing.spaceMd,
        ),
        decoration: BoxDecoration(
          color: selected ? BookmiColors.brandBlue : Colors.transparent,
          borderRadius: BookmiRadius.chipBorder,
          border: Border.all(
            color: selected ? BookmiColors.brandBlue : BookmiColors.glassBorder,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: Colors.white,
            fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
          ),
        ),
      ),
    );
  }
}
