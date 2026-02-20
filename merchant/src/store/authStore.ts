import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'
import { authApi, MerchantProfile } from '@/api/endpoints/auth'
import { apiClient } from '@/api/client'

const REFRESH_KEY = 'dm_refresh_token'

interface AuthState {
  accessToken: string | null
  refreshToken: string | null
  merchant: MerchantProfile | null
  isAuthenticated: boolean
}

interface AuthActions {
  login(email: string, password: string): Promise<void>
  logout(): void
  refresh(): Promise<string>
  setTokens(access: string, refresh: string): void
  setMerchant(m: MerchantProfile): void
}

export const useAuthStore = create<AuthState & AuthActions>()(
  persist(
    (set, get) => ({
      // ── State ──────────────────────────────────────────────────────────
      accessToken: null,
      refreshToken: null,
      merchant: null,
      isAuthenticated: false,

      // ── Actions ────────────────────────────────────────────────────────
      login: async (email, password) => {
        const res = await authApi.login({ email, password })
        const { merchant, tokens } = res.data.data
        set({
          accessToken: tokens.access_token,
          refreshToken: tokens.refresh_token,
          merchant,
          isAuthenticated: true,
        })
      },

      logout: () => {
        const { refreshToken } = get()
        if (refreshToken) {
          authApi.logout(refreshToken).catch(() => {/* silent */})
        }
        set({
          accessToken: null,
          refreshToken: null,
          merchant: null,
          isAuthenticated: false,
        })
      },

      refresh: async () => {
        const { refreshToken } = get()
        if (!refreshToken) throw new Error('No refresh token stored')

        const res = await apiClient.post('/auth/merchant/refresh', {
          refresh_token: refreshToken,
        })
        const tokens = res.data.data
        set({
          accessToken: tokens.access_token,
          refreshToken: tokens.refresh_token,
          isAuthenticated: true,
        })
        return tokens.access_token as string
      },

      setTokens: (access, refresh) => {
        set({ accessToken: access, refreshToken: refresh, isAuthenticated: true })
      },

      setMerchant: (m) => {
        set({ merchant: m })
      },
    }),
    {
      name: REFRESH_KEY,
      storage: createJSONStorage(() => localStorage),
      // Only persist the refresh token and merchant profile — NOT the access token
      partialize: (state) => ({
        refreshToken: state.refreshToken,
        merchant: state.merchant,
        isAuthenticated: state.isAuthenticated,
      }),
    },
  ),
)
