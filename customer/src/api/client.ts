import axios from 'axios'
import { useAuthStore } from '@/store/authStore'

// Development: Vite proxies /api → dealmachan-api.local
// Production:  VITE_API_URL set to full API base
const BASE_URL = import.meta.env.VITE_API_URL || '/api'

export const apiClient = axios.create({
  baseURL: BASE_URL,
  headers: { 'Content-Type': 'application/json' },
  timeout: 15000,
})

// ── Request interceptor: inject access token ──────────────────────────────
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().accessToken
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ── Response interceptor: handle 401 → refresh → retry ───────────────────
let isRefreshing = false
let refreshQueue: Array<(token: string) => void> = []

apiClient.interceptors.response.use(
  (res) => res,
  async (error) => {
    const original = error.config

    if (error.response?.status === 401 && !original._retry) {
      original._retry = true

      if (isRefreshing) {
        return new Promise((resolve) => {
          refreshQueue.push((newToken) => {
            original.headers.Authorization = `Bearer ${newToken}`
            resolve(apiClient(original))
          })
        })
      }

      isRefreshing = true

      try {
        const refreshToken = useAuthStore.getState().refreshToken
        if (!refreshToken) throw new Error('No refresh token')

        // Customer-specific refresh endpoint
        const resp = await axios.post(`${BASE_URL}/auth/customer/refresh`, {
          refresh_token: refreshToken,
        })

        const { access_token, refresh_token: newRefresh } = resp.data.data
        useAuthStore.getState().setTokens(access_token, newRefresh)

        refreshQueue.forEach((cb) => cb(access_token))
        refreshQueue = []

        original.headers.Authorization = `Bearer ${access_token}`
        return apiClient(original)
      } catch {
        useAuthStore.getState().logout()
        refreshQueue = []
        return Promise.reject(error)
      } finally {
        isRefreshing = false
      }
    }

    return Promise.reject(error)
  },
)

// ── Typed error helper ────────────────────────────────────────────────────
export interface ApiError {
  message: string
  error: string
  errors?: Record<string, string[]>
}

export function getApiError(err: unknown): string {
  if (axios.isAxiosError(err)) {
    const data = err.response?.data as { message?: string } | undefined
    return data?.message ?? err.message ?? 'Something went wrong.'
  }
  return 'An unexpected error occurred.'
}
