abstract final class ApiEndpoints {
  static const health = '/health';
  static const authLogin = '/auth/login';
  static const authRegister = '/auth/register';
  static const authLogout = '/auth/logout';
  static const authVerifyOtp = '/auth/verify-otp';
  static const authResendOtp = '/auth/resend-otp';
  static const authForgotPassword = '/auth/forgot-password';
  static const authResetPassword = '/auth/reset-password';
  static const me = '/me';
  static const talents = '/talents';
  static const bookingRequests = '/booking_requests';
  static String bookingRequest(int id) => '/booking_requests/$id';
  static String bookingContract(int id) => '/booking_requests/$id/contract';
  static String bookingReceipt(int id) => '/booking_requests/$id/receipt';
  static const payments = '/payments';
  static const conversations = '/conversations';
  static String conversationMessages(int id) => '/conversations/$id/messages';
  static String conversationRead(int id) => '/conversations/$id/read';
  static const meUpdateFcmToken = '/me/fcm_token';
  static const notifications = '/notifications';
  static const notificationsReadAll = '/notifications/read-all';
  static String notificationRead(int id) => '/notifications/$id/read';
  static const myFavorites = '/me/favorites';
  static const paymentsInitiate = '/payments/initiate';
  static const paymentsCancel = '/payments/cancel';
  static const meFinancialDashboard = '/me/financial_dashboard';
  static const mePayouts = '/me/payouts';
  static String talentDetail(String slug) => '/talents/$slug';
  static String talentCalendar(String talentId) =>
      '/talents/$talentId/calendar';
  static const categories = '/categories';
  static String talentFavorite(int talentId) => '/talents/$talentId/favorite';

  // Profile
  static const meStats = '/me/stats';
  static const meAvatar = '/me/avatar';
  static const meIdentityStatus = '/me/identity/status';
  static const meIdentityDocument = '/me/identity/document';
  static const meIdentitySelfie = '/me/identity/selfie';

  // Story 6.1 — Tracking
  static String bookingTracking(int bookingId) =>
      '/booking_requests/$bookingId/tracking';

  // Story 6.2 — Check-in
  static String bookingCheckin(int bookingId) =>
      '/booking_requests/$bookingId/checkin';

  // Stories 6.4 & 6.5 — Reviews
  static String bookingReviews(int bookingId) =>
      '/booking_requests/$bookingId/reviews';

  // Story 6.6 — Reports
  static String bookingReports(int bookingId) =>
      '/booking_requests/$bookingId/reports';

  // Story 6.7 — Portfolio
  static String talentPortfolio(int profileId) =>
      '/talent_profiles/$profileId/portfolio';
  static const mePortfolio = '/talent_profiles/me/portfolio';
  static String mePortfolioItem(int itemId) =>
      '/talent_profiles/me/portfolio/$itemId';
  // Booking actions (talent)
  static String bookingAccept(int id) => '/booking_requests/$id/accept';
  static String bookingReject(int id) => '/booking_requests/$id/reject';

  // Service packages (talent own)
  static const servicePackages = '/service_packages';
  static String servicePackage(int id) => '/service_packages/$id';

  // Earnings (talent)
  static const meEarnings = '/me/earnings';

  // Admin broadcasts (push notifications of type admin_broadcast)
  static const meBroadcasts = '/me/broadcasts';

  // Messaging — delete
  static String conversationDelete(int id) => '/conversations/$id';
  static String messageDelete(int convId, int msgId) =>
      '/conversations/$convId/messages/$msgId';

  // Availability notification
  static String talentNotifyAvailability(int talentId) =>
      '/talents/$talentId/notify-availability';
}
