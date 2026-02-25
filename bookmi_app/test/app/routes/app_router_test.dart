import 'package:bookmi_app/app/routes/app_router.dart';
import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/storage/local_storage.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/data/repositories/auth_repository.dart';
import 'package:bookmi_app/features/booking/data/repositories/booking_repository.dart';
import 'package:bookmi_app/features/evaluation/data/repositories/review_repository.dart';
import 'package:bookmi_app/features/messaging/data/repositories/messaging_repository.dart';
import 'package:bookmi_app/features/notifications/data/repositories/notification_repository.dart';
import 'package:bookmi_app/features/onboarding/data/repositories/onboarding_repository.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:bookmi_app/features/talent_profile/data/repositories/talent_profile_repository.dart';
import 'package:bookmi_app/features/tracking/data/repositories/tracking_repository.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:mocktail/mocktail.dart';

class MockDio extends Mock implements Dio {}

class MockLocalStorage extends Mock implements LocalStorage {}

class MockAuthRepository extends Mock implements AuthRepository {}

class MockSecureStorage extends Mock implements SecureStorage {}

class MockNotificationRepository extends Mock implements NotificationRepository {}

void main() {
  late GoRouter router;
  late AuthBloc authBloc;

  setUp(() {
    final mockDio = MockDio();
    final mockLocalStorage = MockLocalStorage();
    final repo = TalentProfileRepository.forTesting(
      dio: mockDio,
      localStorage: mockLocalStorage,
    );
    final bookingRepo = BookingRepository.forTesting(
      dio: mockDio,
      localStorage: mockLocalStorage,
    );

    final mockAuthRepo = MockAuthRepository();
    final mockSecureStorage = MockSecureStorage();
    authBloc = AuthBloc(
      authRepository: mockAuthRepo,
      secureStorage: mockSecureStorage,
    );

    final trackingRepo = TrackingRepository.forTesting(dio: mockDio);
    final reviewRepo = ReviewRepository.forTesting(dio: mockDio);
    final onboardingRepo = OnboardingRepository.forTesting(dio: mockDio);
    final messagingRepo = MessagingRepository.forTesting(dio: mockDio);
    final profileRepo = ProfileRepository.forTesting(dio: mockDio);

    final notificationRepo = MockNotificationRepository();

    router = buildAppRouter(
      repo,
      authBloc,
      bookingRepo,
      trackingRepo,
      reviewRepo,
      onboardingRepo,
      messagingRepo,
      profileRepo,
      notificationRepo,
    );
  });

  tearDown(() async {
    await authBloc.close();
  });

  group('AppRouter', () {
    test('has initial location set to splash', () {
      expect(
        router.routeInformationProvider.value.uri.path,
        '/splash',
      );
    });

    group('route paths', () {
      test('splash path is defined', () {
        expect(RoutePaths.splash, '/splash');
      });

      test('onboarding path is defined', () {
        expect(RoutePaths.onboarding, '/onboarding');
      });

      test('login path is defined', () {
        expect(RoutePaths.login, '/login');
      });

      test('register path is defined', () {
        expect(RoutePaths.register, '/register');
      });

      test('otp path is defined', () {
        expect(RoutePaths.otp, '/otp');
      });

      test('forgotPassword path is defined', () {
        expect(RoutePaths.forgotPassword, '/forgot-password');
      });

      test('home path is defined', () {
        expect(RoutePaths.home, '/home');
      });

      test('search path is defined', () {
        expect(RoutePaths.search, '/search');
      });

      test('bookings path is defined', () {
        expect(RoutePaths.bookings, '/bookings');
      });

      test('messages path is defined', () {
        expect(RoutePaths.messages, '/messages');
      });

      test('profile path is defined', () {
        expect(RoutePaths.profile, '/profile');
      });

      test('talent detail uses slug parameter', () {
        expect(RoutePaths.talentDetail, 'talent/:slug');
      });
    });

    group('route names', () {
      test('splash name is defined', () {
        expect(RouteNames.splash, 'splash');
      });

      test('onboarding name is defined', () {
        expect(RouteNames.onboarding, 'onboarding');
      });

      test('home name is defined', () {
        expect(RouteNames.home, 'home');
      });

      test('search name is defined', () {
        expect(RouteNames.search, 'search');
      });

      test('bookings name is defined', () {
        expect(RouteNames.bookings, 'bookings');
      });

      test('messages name is defined', () {
        expect(RouteNames.messages, 'messages');
      });

      test('profile name is defined', () {
        expect(RouteNames.profile, 'profile');
      });
    });
  });
}
