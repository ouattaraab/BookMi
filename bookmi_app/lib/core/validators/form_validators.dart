final _emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');

String? validateEmail(String? value) {
  if (value == null || value.trim().isEmpty) {
    return "L'email est requis.";
  }
  if (!_emailRegex.hasMatch(value.trim())) {
    return 'Veuillez entrer une adresse e-mail valide.';
  }
  return null;
}
