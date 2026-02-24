import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface CustomerProfile {
  id: number
  user_id: number
  email: string
  phone: string
  name: string
  profile_image: string | null
  subscription_status: 'active' | 'expired' | 'none'
  subscription_expiry: string | null
  is_dealmaker: boolean
  referral_code: string
  temp_password?: number   // 1 = must reset before proceeding
  is_new_user?: boolean
}

interface AuthState {
  accessToken: string | null
  refreshToken: string | null
  customer: CustomerProfile | null
  isAuthenticated: boolean
}

interface AuthActions {
  setAuth(customer: CustomerProfile, accessToken: string, refreshToken: string): void
  setTokens(access: string, refresh: string): void
  setCustomer(c: CustomerProfile): void
  logout(): void
}

export const useAuthStore = create<AuthState & AuthActions>()(
  persist(
    (set, get) => ({
      // ── State ──────────────────────────────────────────────────────────────
      accessToken: null,
      refreshToken: null,
      customer: null,
      isAuthenticated: false,

      // ── Actions ────────────────────────────────────────────────────────────
      setAuth: (customer, accessToken, refreshToken) => {
        set({ customer, accessToken, refreshToken, isAuthenticated: true })
      },

      setTokens: (access, refresh) => {
        set({ accessToken: access, refreshToken: refresh, isAuthenticated: true })
      },

      setCustomer: (c) => {
        set({ customer: c })
      },

      logout: () => {
        // Fire-and-forget — invalidate refresh token on server
        const { refreshToken } = get()
        if (refreshToken) {
          // Imported lazily to avoid circular dep
          import('@/api/endpoints/auth').then(({ authApi }) => {
            authApi.logout(refreshToken).catch(() => {/* silent */})
          })
        }
        set({
          accessToken: null,
          refreshToken: null,
          customer: null,
          isAuthenticated: false,
        })
      },
    }),
    {
      name: 'dm-customer-auth',
      // Only persist tokens + customer, NOT loading states
      partialize: (state) => ({
        accessToken: state.accessToken,
        refreshToken: state.refreshToken,
        customer: state.customer,
        isAuthenticated: state.isAuthenticated,
      }),
    },
  ),
)
