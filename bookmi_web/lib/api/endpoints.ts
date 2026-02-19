import { apiClient } from "./client";

// ─── AUTH ───────────────────────────────────────────────────────────────────

export const authApi = {
  login: (data: { email: string; password: string }) =>
    apiClient.post("/auth/login", data),
  logout: () => apiClient.post("/auth/logout"),
  me: () => apiClient.get("/me"),
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
  getSlots: (talentId: number) =>
    apiClient.get(`/talents/${talentId}/calendar`),
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
  accept: (id: number) => apiClient.post(`/booking_requests/${id}/accept`),
  reject: (id: number, reason: string) =>
    apiClient.post(`/booking_requests/${id}/reject`, { reason }),
  cancel: (id: number) => apiClient.post(`/booking_requests/${id}/cancel`),
  getContract: (id: number) =>
    apiClient.get(`/booking_requests/${id}/contract`, {
      responseType: "blob",
    }),
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
