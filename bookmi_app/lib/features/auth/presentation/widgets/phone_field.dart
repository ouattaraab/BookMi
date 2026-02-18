import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/radius.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class PhoneField extends StatefulWidget {
  const PhoneField({
    required this.controller,
    super.key,
    this.validator,
    this.enabled = true,
    this.textInputAction,
  });

  final TextEditingController controller;
  final String? Function(String?)? validator;
  final bool enabled;
  final TextInputAction? textInputAction;

  @override
  State<PhoneField> createState() => _PhoneFieldState();
}

class _PhoneFieldState extends State<PhoneField> {
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
      keyboardType: TextInputType.phone,
      enabled: widget.enabled,
      textInputAction: widget.textInputAction,
      style: TextStyle(color: textColor),
      autovalidateMode: _hasInteracted
          ? AutovalidateMode.onUserInteraction
          : AutovalidateMode.disabled,
      onChanged: (_) {
        if (!_hasInteracted) {
          setState(() => _hasInteracted = true);
        }
      },
      inputFormatters: [
        FilteringTextInputFormatter.digitsOnly,
        LengthLimitingTextInputFormatter(10),
        _PhoneMaskFormatter(),
      ],
      validator: widget.validator,
      decoration: InputDecoration(
        labelText: 'Téléphone',
        labelStyle: TextStyle(color: hintColor),
        hintText: 'XX XX XX XX XX',
        hintStyle: TextStyle(color: hintColor),
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
        prefixIcon: Container(
          padding: const EdgeInsets.symmetric(
            horizontal: BookmiSpacing.spaceMd,
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                '+225',
                style: TextStyle(
                  color: textColor,
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(width: BookmiSpacing.spaceSm),
              Container(
                width: 1,
                height: 24,
                color: BookmiColors.glassBorder,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PhoneMaskFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    final digits = newValue.text.replaceAll(' ', '');
    final buffer = StringBuffer();

    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && i.isEven) {
        buffer.write(' ');
      }
      buffer.write(digits[i]);
    }

    final formatted = buffer.toString();
    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }
}
