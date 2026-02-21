import { apiClient } from '@/api/client'
import type { CustomerProfile } from '@/store/authStore'

// ── Request / Response types ──────────────────────────────────────────────

export interface LoginRequest {
  login: string   // email OR phone
  password: string
}

export interface RegisterRequest {
  name: string
  email: string
  phone: string
  password: string
  referral_code?: string
}

export interface OtpVerifyRequest {
  phone: string
  otp: string
}

export interface AuthResponse {
  data: {
    customer: CustomerProfile
    tokens: {
      access_token: string
      refresh_token: string
      expires_in: number
    }
  }
}

export interface RefreshResponse {
  data: {
    access_token: string
    refresh_token: string
    expires_in: number
  }
}

// ── Auth API ──────────────────────────────────────────────────────────────

export const authApi = {
  /** Register a new customer account */
  register: (body: RegisterRequest) =>
    apiClient.post<AuthResponse>('/auth/customer/register', body),

  /** Login with email/phone + password */
  login: (body: LoginRequest) =>
    apiClient.post<AuthResponse>('/auth/customer/login', body),

  /** Verify OTP sent to phone (first-time or phone-login) */
  verifyOtp: (body: OtpVerifyRequest) =>
    apiClient.post<AuthResponse>('/auth/customer/verify-otp', body),

  /** Resend OTP to phone */
  resendOtp: (phone: string) =>
    apiClient.post('/auth/customer/resend-otp', { phone }),

  /** Request a password-reset OTP */
  forgotPassword: (login: string) =>
    apiClient.post('/auth/customer/forgot-password', { login }),

  /** Reset password using OTP */
  resetPassword: (body: { otp: string; phone: string; new_password: string }) =>
    apiClient.post('/auth/customer/reset-password', body),

  /** Refresh access token — returns new token pair */
  refresh: (refreshToken: string) =>
    apiClient.post<RefreshResponse>('/auth/customer/refresh', {
      refresh_token: refreshToken,
    }),

  /** Logout — invalidates refresh token on server */
  logout: (refreshToken: string) =>
    apiClient.post('/auth/customer/logout', { refresh_token: refreshToken }),
}
