import { apiClient } from "./client";

// ─── AUTH ───────────────────────────────────────────────────────────────────

export const authApi = {
  login: (data: { email: string; password: string }) =>
    apiClient.post("/auth/login", data),
  logout: () => apiClient.post("/auth/logout"),
  me: () => apiClient.get("/me"),
  register: (data: Record<string, unknown>) =>
    apiClient.post("/auth/register", data),
  verifyOtp: (data: { phone: string; otp: string }) =>
    apiClient.post("/auth/verify-otp", { phone: data.phone, code: data.otp }),
  resendOtp: (data: { phone: string }) =>
    apiClient.post("/auth/resend-otp", data),
};

// ─── PUBLIC ──────────────────────────────────────────────────────────────────

export const publicApi = {
  getCategories: () => apiClient.get("/categories"),
  getTalents: (params?: Record<string, unknown>) =>
    apiClient.get("/talents", { params }),
  getTalent: (slug: string) => apiClient.get(`/talents/${slug}`),
};

// ─── TALENT PROFILE ─────────────────────────────────────────────────────────

export const talentApi = {
  getMyProfile: () => apiClient.get("/talent_profiles/me"),
  updateProfile: (data: Record<string, unknown>) =>
    apiClient.patch("/talent_profiles/me", data),
  getPublicProfile: (slug: string) => apiClient.get(`/talents/${slug}`),
};

// ─── CALENDAR ───────────────────────────────────────────────────────────────

export const calendarApi = {
  getSlots: (talentId: number, month?: string) =>
    apiClient.get(`/talents/${talentId}/calendar`, { params: month ? { month } : {} }),
  createSlot: (data: Record<string, unknown>) =>
    apiClient.post("/calendar_slots", data),
  updateSlot: (id: number, data: Record<string, unknown>) =>
    apiClient.put(`/calendar_slots/${id}`, data),
  deleteSlot: (id: number) => apiClient.delete(`/calendar_slots/${id}`),
};

// ─── BOOKINGS ────────────────────────────────────────────────────────────────

export const bookingApi = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get("/booking_requests", { params }),
  get: (id: number) => apiClient.get(`/booking_requests/${id}`),
  create: (data: Record<string, unknown>) =>
    apiClient.post("/booking_requests", data),
  accept: (id: number) => apiClient.post(`/booking_requests/${id}/accept`),
  reject: (id: number, reason: string) =>
    apiClient.post(`/booking_requests/${id}/reject`, { reason }),
  cancel: (id: number) => apiClient.post(`/booking_requests/${id}/cancel`),
  getContract: (id: number) =>
    apiClient.get(`/booking_requests/${id}/contract`, { responseType: "blob" }),
};

// ─── FAVORITES ───────────────────────────────────────────────────────────────

export const favoriteApi = {
  list: (params?: { per_page?: number }) =>
    apiClient.get("/me/favorites", { params }),
  add: (talentId: number) => apiClient.post(`/talents/${talentId}/favorite`),
  remove: (talentId: number) =>
    apiClient.delete(`/talents/${talentId}/favorite`),
  check: (talentId: number) => apiClient.get(`/talents/${talentId}/favorite`),
};

// ─── SERVICE PACKAGES ────────────────────────────────────────────────────────

export const packageApi = {
  list: () => apiClient.get("/service_packages"),
  create: (data: Record<string, unknown>) =>
    apiClient.post("/service_packages", data),
  update: (id: number, data: Record<string, unknown>) =>
    apiClient.patch(`/service_packages/${id}`, data),
  delete: (id: number) => apiClient.delete(`/service_packages/${id}`),
};

// ─── MESSAGES ────────────────────────────────────────────────────────────────

export const messageApi = {
  listConversations: () => apiClient.get("/conversations"),
  getMessages: (conversationId: number) =>
    apiClient.get(`/conversations/${conversationId}/messages`),
  sendMessage: (conversationId: number, content: string) =>
    apiClient.post(`/conversations/${conversationId}/messages`, { content }),
  markRead: (conversationId: number) =>
    apiClient.post(`/conversations/${conversationId}/read`),
};

// ─── ANALYTICS ───────────────────────────────────────────────────────────────

export const analyticsApi = {
  getDashboard: () => apiClient.get("/me/analytics"),
  getFinancialDashboard: () => apiClient.get("/me/financial_dashboard"),
  getPayouts: () => apiClient.get("/me/payouts"),
  downloadCertificate: () =>
    apiClient.get("/me/revenue_certificate", { responseType: "blob" }),
};

// ─── NOTIFICATIONS ────────────────────────────────────────────────────────────

export const notificationApi = {
  list: () => apiClient.get("/notifications"),
  markRead: (id: number) => apiClient.post(`/notifications/${id}/read`),
  markAllRead: () => apiClient.post("/notifications/read-all"),
};

// ─── PAYMENTS ────────────────────────────────────────────────────────────────

export const paymentApi = {
  initiate: (data: {
    booking_id: number;
    payment_method: string;
    phone_number: string;
  }) => apiClient.post("/payments/initiate", data),
  submitOtp: (data: { reference: string; otp: string }) =>
    apiClient.post("/payments/submit_otp", data),
  getStatus: (id: number) => apiClient.get(`/payments/${id}/status`),
};

// ─── REVIEWS ─────────────────────────────────────────────────────────────────

export const reviewApi = {
  submit: (
    bookingId: number,
    data: { type: string; rating: number; comment?: string }
  ) => apiClient.post(`/booking_requests/${bookingId}/reviews`, data),
  list: (bookingId: number) =>
    apiClient.get(`/booking_requests/${bookingId}/reviews`),
};

// ─── ESCROW ───────────────────────────────────────────────────────────────────

export const escrowApi = {
  confirmDelivery: (bookingId: number) =>
    apiClient.post(`/booking_requests/${bookingId}/confirm_delivery`),
};

// ─── TRACKING ─────────────────────────────────────────────────────────────────

export const trackingApi = {
  get: (bookingId: number) =>
    apiClient.get(`/booking_requests/${bookingId}/tracking`),
  update: (
    bookingId: number,
    data: { status: string; latitude?: number; longitude?: number }
  ) => apiClient.post(`/booking_requests/${bookingId}/tracking`, data),
};

// ─── IDENTITY VERIFICATION ────────────────────────────────────────────────────

export const verificationApi = {
  submit: (data: FormData) =>
    apiClient.post("/verifications", data, {
      headers: { "Content-Type": "multipart/form-data" },
    }),
  getMe: () => apiClient.get("/verifications/me"),
};

// ─── TALENT PROFILE (MUTATIONS) ───────────────────────────────────────────────

export const talentProfileApi = {
  update: (id: number, data: Record<string, unknown>) =>
    apiClient.patch(`/talent_profiles/${id}`, data),
  updatePayout: (data: {
    payout_method: string;
    payout_details: Record<string, string>;
  }) => apiClient.patch("/talent_profiles/me/payout_method", data),
  updateAutoReply: (data: {
    auto_reply_message: string;
    auto_reply_is_active: boolean;
  }) => apiClient.put("/talent_profiles/me/auto_reply", data),
};

// ─── MANAGER ─────────────────────────────────────────────────────────────────

export const managerApi = {
  getMyTalents: () => apiClient.get("/manager/talents"),
  getTalentStats: (talentId: number) =>
    apiClient.get(`/manager/talents/${talentId}`),
  getTalentBookings: (talentId: number) =>
    apiClient.get(`/manager/talents/${talentId}/bookings`),
  acceptBooking: (talentId: number, bookingId: number) =>
    apiClient.post(`/manager/talents/${talentId}/bookings/${bookingId}/accept`),
  rejectBooking: (talentId: number, bookingId: number, reason: string) =>
    apiClient.post(
      `/manager/talents/${talentId}/bookings/${bookingId}/reject`,
      { reason }
    ),
  sendMessage: (conversationId: number, content: string) =>
    apiClient.post(`/manager/conversations/${conversationId}/messages`, {
      content,
    }),
};

// ─── PORTFOLIO ────────────────────────────────────────────────────────────────

export const portfolioApi = {
  list: (talentProfileId: number) =>
    apiClient.get(`/talent_profiles/${talentProfileId}/portfolio`),
  upload: (data: FormData) =>
    apiClient.post("/talent_profiles/me/portfolio", data, {
      headers: { "Content-Type": "multipart/form-data" },
    }),
  addLink: (data: {
    link_url: string;
    link_platform: string;
    caption?: string;
  }) => apiClient.post("/talent_profiles/me/portfolio", data),
  delete: (itemId: number) =>
    apiClient.delete(`/talent_profiles/me/portfolio/${itemId}`),
};

// ─── TWO-FACTOR AUTHENTICATION ────────────────────────────────────────────────

export const twoFactorApi = {
  status: () => apiClient.get("/auth/2fa/status"),
  setupTotp: () => apiClient.post("/auth/2fa/setup/totp"),
  enableTotp: (data: { code: string }) =>
    apiClient.post("/auth/2fa/enable/totp", data),
  setupEmail: () => apiClient.post("/auth/2fa/setup/email"),
  enableEmail: (data: { code: string }) =>
    apiClient.post("/auth/2fa/enable/email", data),
  verify: (data: { challenge_token: string; code: string }) =>
    apiClient.post("/auth/2fa/verify", data),
  disable: (data: { password: string }) =>
    apiClient.post("/auth/2fa/disable", data),
};
