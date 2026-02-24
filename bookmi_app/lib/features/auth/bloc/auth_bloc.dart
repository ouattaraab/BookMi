import 'dart:async';

import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/core/services/notification_service.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/repositories/auth_repository.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  AuthBloc({
    required AuthRepository authRepository,
    required SecureStorage secureStorage,
  }) : _authRepository = authRepository,
       _secureStorage = secureStorage,
       super(const AuthInitial()) {
    on<AuthCheckRequested>(_onCheckRequested);
    on<AuthLoginSubmitted>(_onLoginSubmitted);
    on<AuthRegisterSubmitted>(_onRegisterSubmitted);
    on<AuthOtpSubmitted>(_onOtpSubmitted);
    on<AuthOtpResendRequested>(_onOtpResendRequested);
    on<AuthForgotPasswordSubmitted>(_onForgotPasswordSubmitted);
    on<AuthLogoutRequested>(_onLogoutRequested);
    on<AuthSessionExpired>(_onSessionExpired);
    on<AuthProfileUpdated>(_onProfileUpdated);
  }

  final AuthRepository _authRepository;
  final SecureStorage _secureStorage;

  Future<void> _onCheckRequested(
    AuthCheckRequested event,
    Emitter<AuthState> emit,
  ) async {
    final token = await _secureStorage.getToken();
    if (token == null) {
      emit(const AuthUnauthenticated());
      return;
    }

    final result = await _authRepository.getProfile();

    switch (result) {
      case ApiSuccess(:final data):
        emit(AuthAuthenticated(user: data.user, roles: data.roles));
        unawaited(_registerFcmToken());
      case ApiFailure():
        await _secureStorage.deleteToken();
        emit(const AuthUnauthenticated());
    }
  }

  Future<void> _onLoginSubmitted(
    AuthLoginSubmitted event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final result = await _authRepository.login(event.email, event.password);

    switch (result) {
      case ApiSuccess(:final data):
        await _secureStorage.saveToken(data.token);
        emit(AuthAuthenticated(user: data.user, roles: data.roles));
        unawaited(_registerFcmToken());
      case ApiFailure(:final code, :final message, :final details):
        emit(AuthFailure(code: code, message: message, details: details));
    }
  }

  Future<void> _onRegisterSubmitted(
    AuthRegisterSubmitted event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final result = await _authRepository.register(event.data);

    switch (result) {
      case ApiSuccess():
        final phone = event.data['phone'] as String;
        emit(AuthRegistrationSuccess(phone: phone));
      case ApiFailure(:final code, :final message, :final details):
        emit(AuthFailure(code: code, message: message, details: details));
    }
  }

  Future<void> _onOtpSubmitted(
    AuthOtpSubmitted event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final result = await _authRepository.verifyOtp(event.phone, event.code);

    switch (result) {
      case ApiSuccess(:final data):
        await _secureStorage.saveToken(data.token);
        emit(AuthAuthenticated(user: data.user, roles: data.roles));
        unawaited(_registerFcmToken());
      case ApiFailure(:final code, :final message, :final details):
        emit(AuthFailure(code: code, message: message, details: details));
    }
  }

  Future<void> _onOtpResendRequested(
    AuthOtpResendRequested event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final result = await _authRepository.resendOtp(event.phone);

    switch (result) {
      case ApiSuccess():
        emit(const AuthOtpResent());
      case ApiFailure(:final code, :final message, :final details):
        emit(AuthFailure(code: code, message: message, details: details));
    }
  }

  Future<void> _onForgotPasswordSubmitted(
    AuthForgotPasswordSubmitted event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthLoading());

    final result = await _authRepository.forgotPassword(event.email);

    switch (result) {
      case ApiSuccess():
        emit(const AuthForgotPasswordSuccess());
      case ApiFailure(:final code, :final message, :final details):
        emit(AuthFailure(code: code, message: message, details: details));
    }
  }

  Future<void> _onLogoutRequested(
    AuthLogoutRequested event,
    Emitter<AuthState> emit,
  ) async {
    await _authRepository.logout();
    emit(const AuthUnauthenticated());
  }

  Future<void> _onSessionExpired(
    AuthSessionExpired event,
    Emitter<AuthState> emit,
  ) async {
    await _secureStorage.deleteToken();
    emit(const AuthUnauthenticated());
  }

  void _onProfileUpdated(
    AuthProfileUpdated event,
    Emitter<AuthState> emit,
  ) {
    final current = state;
    if (current is AuthAuthenticated) {
      emit(AuthAuthenticated(user: event.user, roles: current.roles));
    }
  }

  Future<void> _registerFcmToken() async {
    try {
      final token = await NotificationService.instance.getFcmToken();
      if (token != null) {
        await _authRepository.updateFcmToken(token);
      }
      // Keep token fresh: when Firebase rotates it, push the new one immediately.
      NotificationService.instance.setTokenRefreshCallback((newToken) {
        _authRepository.updateFcmToken(newToken);
      });
    } catch (_) {
      // Non-critical — ignore FCM token registration failures
    }
  }
}
