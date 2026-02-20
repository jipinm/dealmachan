import axios from 'axios'
import { useAuthStore } from '@/store/authStore'

// Base URL — falls back to /api (proxied by Vite in dev, set VITE_API_URL in production)
const BASE_URL = import.meta.env.VITE_API_URL || '/api'

export const apiClient = axios.create({
  baseURL: BASE_URL,
  headers: { 'Content-Type': 'application/json' },
  timeout: 15000,
})

// ── Request interceptor: attach access token ──────────────────────────────────
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().accessToken
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ── Response interceptor: handle 401 → refresh → retry ───────────────────────
let isRefreshing = false
let refreshQueue: Array<(token: string) => void> = []

apiClient.interceptors.response.use(
  (res) => res,
  async (error) => {
    const original = error.config

    if (error.response?.status === 401 && !original._retry) {
      original._retry = true

      if (isRefreshing) {
        // Queue the retry until refresh completes
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

        const resp = await axios.post(`${BASE_URL}/auth/merchant/refresh`, {
          refresh_token: refreshToken,
        })

        const { access_token, refresh_token: newRefresh } = resp.data.data
        useAuthStore.getState().setTokens(access_token, newRefresh)

        refreshQueue.forEach((cb) => cb(access_token))
        refreshQueue = []

        original.headers.Authorization = `Bearer ${access_token}`
        return apiClient(original)
      } catch {
        // Refresh failed — force logout
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

// ── Typed API error helper ────────────────────────────────────────────────────
export interface ApiError {
  message: string
  error: string
  errors?: Record<string, string[]>
}

export function getApiError(err: unknown): ApiError {
  if (axios.isAxiosError(err) && err.response?.data) {
    return err.response.data as ApiError
  }
  return { message: 'An unexpected error occurred', error: 'UNKNOWN' }
}
