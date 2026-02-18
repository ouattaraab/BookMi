import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:flutter/material.dart';

class AuthTextField extends StatefulWidget {
  const AuthTextField({
    required this.label,
    super.key,
    this.controller,
    this.keyboardType,
    this.obscureText = false,
    this.validator,
    this.prefixIcon,
    this.enabled = true,
    this.autofillHints,
    this.textInputAction,
    this.onFieldSubmitted,
  });

  final String label;
  final TextEditingController? controller;
  final TextInputType? keyboardType;
  final bool obscureText;
  final String? Function(String?)? validator;
  final IconData? prefixIcon;
  final bool enabled;
  final Iterable<String>? autofillHints;
  final TextInputAction? textInputAction;
  final ValueChanged<String>? onFieldSubmitted;

  @override
  State<AuthTextField> createState() => _AuthTextFieldState();
}

class _AuthTextFieldState extends State<AuthTextField> {
  bool _obscured = true;
  bool _hasInteracted = false;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final fillColor = isDark
        ? BookmiColors.glassWhite
        : Colors.white.withValues(alpha: 0.9);
    final textColor = isDark ? Colors.white : BookmiColors.brandNavy;
    final hintColor = isDark
        ? Colors.white.withValues(alpha: 0.5)
        : BookmiColors.brandNavy.withValues(alpha: 0.5);

    return TextFormField(
      controller: widget.controller,
      keyboardType: widget.keyboardType,
      obscureText: widget.obscureText && _obscured,
      enabled: widget.enabled,
      autofillHints: widget.autofillHints,
      textInputAction: widget.textInputAction,
      onFieldSubmitted: widget.onFieldSubmitted,
      style: TextStyle(color: textColor),
      autovalidateMode: _hasInteracted
          ? AutovalidateMode.onUserInteraction
          : AutovalidateMode.disabled,
      onChanged: (_) {
        if (!_hasInteracted) {
          setState(() => _hasInteracted = true);
        }
      },
      validator: widget.validator,
      decoration: InputDecoration(
        labelText: widget.label,
        labelStyle: TextStyle(color: hintColor),
        filled: true,
        fillColor: fillColor,
        border: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(color: BookmiColors.glassBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(color: BookmiColors.glassBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(
            color: BookmiColors.brandBlue,
            width: 2,
          ),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(color: BookmiColors.error),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BookmiRadius.inputBorder,
          borderSide: const BorderSide(
            color: BookmiColors.error,
            width: 2,
          ),
        ),
        prefixIcon: widget.prefixIcon != null ? Icon(widget.prefixIcon) : null,
        suffixIcon: widget.obscureText
            ? IconButton(
                icon: Icon(
                  _obscured ? Icons.visibility_off : Icons.visibility,
                  color: hintColor,
                ),
                onPressed: () => setState(() => _obscured = !_obscured),
              )
            : null,
      ),
    );
  }
}
