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
  static String bookingContractUrl(int id) =>
      '/booking_requests/$id/contract-url';
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
  static String talentFollow(int talentId) => '/talents/$talentId/follow';
  static String bookingDispute(int id) => '/booking_requests/$id/dispute';

  // Reschedule
  static String bookingReschedule(int id) => '/booking_requests/$id/reschedule';
  static String rescheduleAccept(int id) => '/reschedule_requests/$id/accept';
  static String rescheduleReject(int id) => '/reschedule_requests/$id/reject';

  // Calendar management (talent)
  static const calendarSlots = '/calendar_slots';
  static String calendarSlot(int id) => '/calendar_slots/$id';

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
  // Review reply (talent)
  static String reviewReply(int reviewId) => '/reviews/$reviewId/reply';

  // Booking actions (talent)
  static String bookingAccept(int id) => '/booking_requests/$id/accept';
  static String bookingReject(int id) => '/booking_requests/$id/reject';
  // Booking actions (client)
  static String bookingConfirmDelivery(int id) =>
      '/booking_requests/$id/confirm_delivery';

  static String bookingTalentConfirm(int id) =>
      '/booking_requests/$id/talent_confirm';

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

  // Talent profile (own)
  static const meTalentProfile = '/talent_profiles/me';
  static const meTalentProfileInfo = '/talent_profiles/me/info';

  // Payout method (talent)
  static const mePayoutMethod = '/talent_profiles/me/payout_method';

  // Withdrawal requests (talent)
  static const meWithdrawalRequests = '/me/withdrawal_requests';

  // 2FA
  static const auth2faStatus = '/auth/2fa/status';
  static const auth2faSetupTotp = '/auth/2fa/setup/totp';
  static const auth2faEnableTotp = '/auth/2fa/enable/totp';
  static const auth2faSetupEmail = '/auth/2fa/setup/email';
  static const auth2faEnableEmail = '/auth/2fa/enable/email';
  static const auth2faDisable = '/auth/2fa/disable';
}
