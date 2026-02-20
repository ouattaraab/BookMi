import { create } from "zustand";
import { persist } from "zustand/middleware";

export interface AuthUser {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  is_admin: boolean;
  role?: "client" | "talent" | "manager";
  talentProfile?: {
    id: number;
    stage_name: string;
    slug: string;
    talent_level: string;
    is_verified: boolean;
  } | null;
}

interface AuthState {
  token: string | null;
  user: AuthUser | null;
  setAuth: (token: string, user: AuthUser) => void;
  clearAuth: () => void;
  isAuthenticated: () => boolean;
  isTalent: () => boolean;
  isManager: () => boolean;
  isClient: () => boolean;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      user: null,

      setAuth: (token, user) => set({ token, user }),

      clearAuth: () => set({ token: null, user: null }),

      isAuthenticated: () => !!get().token,

      isTalent: () => !!get().user?.talentProfile,

      isManager: () => get().user?.role === "manager",

      isClient: () => {
        const user = get().user;
        return !!user && !user.talentProfile && !user.is_admin && user.role !== "manager";
      },
    }),
    {
      name: "bookmi-auth",
      partialize: (state) => ({ token: state.token, user: state.user }),
    }
  )
);
