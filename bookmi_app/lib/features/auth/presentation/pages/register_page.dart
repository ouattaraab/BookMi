import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/repositories/auth_repository.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_button.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_text_field.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/phone_field.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

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
  int? _categoryId;
  int? _subcategoryId;
  List<Map<String, dynamic>> _categories = [];
  List<Map<String, dynamic>> _subcategories = [];
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

    if (_role == 'talent' && _categoryId != null) {
      data['category_id'] = _categoryId;
      if (_subcategoryId != null) {
        data['subcategory_id'] = _subcategoryId;
      }
    }

    context.read<AuthBloc>().add(AuthRegisterSubmitted(data: data));
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return BlocListener<AuthBloc, AuthState>(
      listener: (context, state) {
        switch (state) {
          case AuthRegistrationSuccess(:final phone):
            context.go(RoutePaths.otp, extra: phone);
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
                            ],
                          ),
                          // Category dropdown (talent only)
                          if (_role == 'talent') ...[
                            const SizedBox(height: BookmiSpacing.spaceBase),
                            _buildCategoryDropdown(isDark),
                            if (_subcategories.isNotEmpty) ...[
                              const SizedBox(
                                height: BookmiSpacing.spaceBase,
                              ),
                              _buildSubcategoryDropdown(isDark),
                            ],
                          ],
                          const SizedBox(height: BookmiSpacing.spaceLg),
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
                            color: BookmiColors.ctaOrange,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCategoryDropdown(bool isDark) {
    final fillColor = isDark
        ? BookmiColors.glassWhite
        : Colors.white.withValues(alpha: 0.9);
    final textColor = isDark ? Colors.white : BookmiColors.brandNavy;

    return DropdownButtonFormField<int>(
      initialValue: _categoryId,
      decoration: InputDecoration(
        labelText: 'Catégorie',
        labelStyle: TextStyle(
          color: textColor.withValues(alpha: 0.5),
        ),
        filled: true,
        fillColor: fillColor,
        border: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(
            color: BookmiColors.glassBorder,
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(
            color: BookmiColors.glassBorder,
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(
            color: BookmiColors.brandBlue,
            width: 2,
          ),
        ),
        prefixIcon: const Icon(Icons.category_outlined),
      ),
      dropdownColor: isDark ? BookmiColors.brandNavy : Colors.white,
      style: TextStyle(color: textColor),
      items: _categories.map((cat) {
        return DropdownMenuItem<int>(
          value: cat['id'] as int,
          child: Text(cat['name'] as String),
        );
      }).toList(),
      onChanged: (value) {
        setState(() {
          _categoryId = value;
          _subcategoryId = null;
          final selected = _categories.firstWhere(
            (c) => c['id'] == value,
            orElse: () => <String, dynamic>{},
          );
          final subs = selected['subcategories'] as List<dynamic>?;
          _subcategories = subs?.cast<Map<String, dynamic>>() ?? [];
        });
      },
      validator: (value) {
        if (_role == 'talent' && value == null) {
          return 'La catégorie est requise pour un talent.';
        }
        return null;
      },
    );
  }

  Widget _buildSubcategoryDropdown(bool isDark) {
    final fillColor = isDark
        ? BookmiColors.glassWhite
        : Colors.white.withValues(alpha: 0.9);
    final textColor = isDark ? Colors.white : BookmiColors.brandNavy;

    return DropdownButtonFormField<int>(
      initialValue: _subcategoryId,
      decoration: InputDecoration(
        labelText: 'Sous-catégorie (optionnel)',
        labelStyle: TextStyle(
          color: textColor.withValues(alpha: 0.5),
        ),
        filled: true,
        fillColor: fillColor,
        border: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(
            color: BookmiColors.glassBorder,
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(
            color: BookmiColors.glassBorder,
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(
            color: BookmiColors.brandBlue,
            width: 2,
          ),
        ),
        prefixIcon: const Icon(Icons.subdirectory_arrow_right),
      ),
      dropdownColor: isDark ? BookmiColors.brandNavy : Colors.white,
      style: TextStyle(color: textColor),
      items: _subcategories.map((sub) {
        return DropdownMenuItem<int>(
          value: sub['id'] as int,
          child: Text(sub['name'] as String),
        );
      }).toList(),
      onChanged: (value) => setState(() => _subcategoryId = value),
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
