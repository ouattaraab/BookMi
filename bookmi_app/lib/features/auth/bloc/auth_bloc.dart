import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
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
}
