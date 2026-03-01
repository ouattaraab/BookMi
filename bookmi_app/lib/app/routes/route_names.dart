abstract final class RouteNames {
  static const splash = 'splash';
  static const onboarding = 'onboarding';
  static const login = 'login';
  static const register = 'register';
  static const otp = 'otp';
  static const forgotPassword = 'forgotPassword';
  static const home = 'home';
  static const search = 'search';
  static const bookings = 'bookings';
  static const messages = 'messages';
  static const profile = 'profile';
  static const talentDetail = 'talentDetail';
  static const bookingDetail = 'bookingDetail';
  static const tracking = 'tracking';
  static const evaluation = 'evaluation';
  static const talentOnboarding = 'talentOnboarding';
  // Profile sub-routes
  static const profilePersonalInfo = 'profilePersonalInfo';
  static const profileFavorites = 'profileFavorites';
  static const profilePaymentMethods = 'profilePaymentMethods';
  static const profileIdentityVerification = 'profileIdentityVerification';
  static const profileTalentStatistics = 'profileTalentStatistics';
  static const profileSupport = 'profileSupport';
  static const profileTalentEarnings = 'profileTalentEarnings';
  static const profilePortfolioManager = 'profilePortfolioManager';
  static const profilePackageManager = 'profilePackageManager';
  static const profileTalentInfo = 'profileTalentInfo';
  static const notifications = 'notifications';
  static const talentDeepLink = 'talentDeepLink';
  static const clientReviews = 'clientReviews';
  static const profileCalendar = 'profileCalendar';
  static const profileManagerAssignment = 'profileManagerAssignment';
  static const profileTwoFactor = 'profileTwoFactor';
  static const profileNotifications = 'profileNotifications';
  static const profileAutoReply = 'profileAutoReply';
  // Manager routes
  static const managerDashboard = 'managerDashboard';
}

abstract final class RoutePaths {
  static const splash = '/splash';
  static const onboarding = '/onboarding';
  static const login = '/login';
  static const register = '/register';
  static const otp = '/otp';
  static const forgotPassword = '/forgot-password';
  static const home = '/home';
  static const search = '/search';
  static const bookings = '/bookings';
  static const bookingDetail = 'booking/:id';
  static const tracking = 'tracking';
  static const evaluation = 'evaluation';
  static const clientReviews = 'client-reviews';
  static const messages = '/messages';
  static const profile = '/profile';
  static const talentDetail = 'talent/:slug';
  static const talentOnboarding = '/talent-onboarding';
  // Profile sub-routes (relative — children of /profile)
  static const profilePersonalInfo = 'personal-info';
  static const profileFavorites = 'favorites';
  static const profilePaymentMethods = 'payment-methods';
  static const profileIdentityVerification = 'identity-verification';
  static const profileTalentStatistics = 'talent-statistics';
  static const profileSupport = 'support';
  static const profileTalentEarnings = 'earnings';
  static const profilePortfolioManager = 'portfolio-manager';
  static const profilePackageManager = 'package-manager';
  static const profileTalentInfo = 'talent-info';
  static const profileTwoFactor = 'two-factor';
  static const profileNotifications = 'notifications-prefs';
  static const profileAutoReply = 'auto-reply';
  static const profileCalendar = 'calendar';
  static const profileManagerAssignment = 'manager-assignment';
  static const notifications = '/notifications';
  // Manager routes
  static const managerDashboard = '/manager-dashboard';
}
