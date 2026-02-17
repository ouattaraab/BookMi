import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:flutter/material.dart';

/// Placeholder auth guard — will be implemented in Story 2.x.
///
/// For now, always allows access (returns null = no redirect).
String? authGuard(BuildContext context) {
  // TODO(auth): Check token in secure storage and redirect to login.
  return null;
}

/// Routes that do not require authentication.
const publicRoutes = <String>{
  RoutePaths.login,
};
