import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Routes that do not require authentication.
const publicRoutes = <String>{
  RoutePaths.splash,
  RoutePaths.onboarding,
  RoutePaths.login,
  RoutePaths.register,
  RoutePaths.otp,
  RoutePaths.forgotPassword,
};

/// Auth guard for GoRouter redirect.
///
/// Returns the path to redirect to, or `null` to allow the current route.
String? authGuard(BuildContext context, String location) {
  final authState = context.read<AuthBloc>().state;

  final isPublicRoute = publicRoutes.any((route) => location.startsWith(route));

  // Don't redirect on splash (auth check in progress)
  if (location.startsWith(RoutePaths.splash)) return null;

  switch (authState) {
    case AuthAuthenticated():
      // Authenticated user trying to access auth pages → redirect to home
      if (isPublicRoute) return RoutePaths.home;
      return null;
    case AuthUnauthenticated():
      // Unauthenticated user trying to access protected pages
      // → redirect to login
      if (!isPublicRoute) return RoutePaths.login;
      return null;
    case AuthInitial():
    case AuthLoading():
    case AuthRegistrationSuccess():
    case AuthOtpResent():
    case AuthForgotPasswordSuccess():
    case AuthFailure():
      return null;
  }
}
