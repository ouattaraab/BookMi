import 'package:bookmi_app/app/routes/app_router.dart';
import 'package:bookmi_app/core/design_system/theme/bookmi_theme.dart';
import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/core/storage/local_storage.dart';
import 'package:bookmi_app/core/storage/secure_storage.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/data/repositories/auth_repository.dart';
import 'package:bookmi_app/features/discovery/bloc/discovery_bloc.dart';
import 'package:bookmi_app/features/discovery/data/repositories/discovery_repository.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_bloc.dart';
import 'package:bookmi_app/features/favorites/data/local/favorites_local_source.dart';
import 'package:bookmi_app/features/favorites/data/repositories/favorites_repository.dart';
import 'package:bookmi_app/features/booking/booking.dart';
import 'package:bookmi_app/features/evaluation/data/repositories/review_repository.dart';
import 'package:bookmi_app/features/talent_profile/data/repositories/talent_profile_repository.dart';
import 'package:bookmi_app/features/tracking/data/repositories/tracking_repository.dart';
import 'package:bookmi_app/l10n/l10n.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:hive_ce/hive.dart';

class App extends StatelessWidget {
  const App({super.key});

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<_AppDependencies>(
      future: _AppDependencies.initialize(),
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const MaterialApp(
            home: Scaffold(
              body: Center(child: CircularProgressIndicator()),
            ),
          );
        }

        final deps = snapshot.data!;

        return RepositoryProvider<AuthRepository>.value(
          value: deps.authRepo,
          child: MultiBlocProvider(
            providers: [
              BlocProvider<AuthBloc>.value(value: deps.authBloc),
              BlocProvider<FavoritesBloc>(
                create: (_) => FavoritesBloc(repository: deps.favoritesRepo),
              ),
              BlocProvider<DiscoveryBloc>(
                create: (_) => DiscoveryBloc(repository: deps.discoveryRepo),
              ),
            ],
            child: MaterialApp.router(
              routerConfig: deps.router,
              theme: BookmiTheme.light,
              darkTheme: BookmiTheme.dark,
              localizationsDelegates: AppLocalizations.localizationsDelegates,
              supportedLocales: AppLocalizations.supportedLocales,
            ),
          ),
        );
      },
    );
  }
}

class _AppDependencies {
  _AppDependencies({
    required this.authBloc,
    required this.authRepo,
    required this.favoritesRepo,
    required this.discoveryRepo,
    required this.talentProfileRepo,
    required this.bookingRepo,
    required this.trackingRepo,
    required this.reviewRepo,
    required this.router,
  });

  final AuthBloc authBloc;
  final AuthRepository authRepo;
  final FavoritesRepository favoritesRepo;
  final DiscoveryRepository discoveryRepo;
  final TalentProfileRepository talentProfileRepo;
  final BookingRepository bookingRepo;
  final TrackingRepository trackingRepo;
  final ReviewRepository reviewRepo;
  final GoRouter router;

  static Future<_AppDependencies> initialize() async {
    final favoritesBox = await Hive.openBox<dynamic>('favorites');
    final discoveryBox = await Hive.openBox<dynamic>('discovery');
    final talentProfileBox = await Hive.openBox<dynamic>('talent_profile');
    // Ensure settings box is open for onboarding flag
    await Hive.openBox<dynamic>('settings');

    final apiClient = ApiClient.instance;
    final secureStorage = SecureStorage();

    // Auth
    final authRepo = AuthRepository(
      apiClient: apiClient,
      secureStorage: secureStorage,
    );
    final authBloc = AuthBloc(
      authRepository: authRepo,
      secureStorage: secureStorage,
    );

    // Wire up session expiration: 401 → AuthBloc
    apiClient.onSessionExpired = () {
      authBloc.add(const AuthSessionExpired());
    };

    final favoritesLocalSource = FavoritesLocalSource(box: favoritesBox);
    final favoritesRepo = FavoritesRepository(
      apiClient: apiClient,
      localSource: favoritesLocalSource,
    );

    final discoveryLocalStorage = LocalStorage(box: discoveryBox);
    final discoveryRepo = DiscoveryRepository(
      apiClient: apiClient,
      localStorage: discoveryLocalStorage,
    );

    final talentProfileLocalStorage = LocalStorage(box: talentProfileBox);
    final talentProfileRepo = TalentProfileRepository(
      apiClient: apiClient,
      localStorage: talentProfileLocalStorage,
    );

    final bookingsBox = await Hive.openBox<dynamic>('bookings');
    final bookingLocalStorage = LocalStorage(box: bookingsBox);
    final bookingRepo = BookingRepository(
      apiClient: apiClient,
      localStorage: bookingLocalStorage,
    );

    final trackingRepo = TrackingRepository(apiClient: apiClient);
    final reviewRepo = ReviewRepository(apiClient: apiClient);

    final router = buildAppRouter(
      talentProfileRepo,
      authBloc,
      bookingRepo,
      trackingRepo,
      reviewRepo,
    );

    return _AppDependencies(
      authBloc: authBloc,
      authRepo: authRepo,
      favoritesRepo: favoritesRepo,
      discoveryRepo: discoveryRepo,
      talentProfileRepo: talentProfileRepo,
      bookingRepo: bookingRepo,
      trackingRepo: trackingRepo,
      reviewRepo: reviewRepo,
      router: router,
    );
  }
}
