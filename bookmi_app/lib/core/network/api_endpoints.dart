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
  static const meFinancialDashboard = '/me/financial_dashboard';
  static const mePayouts = '/me/payouts';
  static String talentDetail(String slug) => '/talents/$slug';
  static String talentCalendar(String talentId) =>
      '/talents/$talentId/calendar';
  static const categories = '/categories';
  static String talentFavorite(int talentId) => '/talents/$talentId/favorite';
}
