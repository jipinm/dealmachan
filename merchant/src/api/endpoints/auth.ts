import { apiClient } from '@/api/client'

export interface LoginRequest {
  email: string
  password: string
}

export interface AuthTokens {
  access_token: string
  refresh_token: string
  expires_in: number
}

export interface MerchantProfile {
  id: number
  user_id: number
  business_name: string
  email: string
  phone: string | null
  logo_url: string | null
  profile_status: 'pending' | 'approved' | 'rejected'
  subscription_status: 'active' | 'expired' | 'trial'
  account_type_id?: number | null  // 1=Basic, 2=Standard, 3=Premium, 4=Enterprise
}

export interface LoginResponse {
  merchant: MerchantProfile
  tokens: AuthTokens
}

export const authApi = {
  login: (data: LoginRequest) =>
    apiClient.post<{ success: boolean; data: LoginResponse }>('/auth/merchant/login', data),

  refresh: (refresh_token: string) =>
    apiClient.post<{ success: boolean; data: AuthTokens }>('/auth/merchant/refresh', {
      refresh_token,
    }),

  logout: (refresh_token: string) =>
    apiClient.post('/auth/merchant/logout', { refresh_token }),

  forgotPassword: (email: string) =>
    apiClient.post('/auth/merchant/forgot-password', { email }),

  resetPassword: (data: { token: string; password: string; password_confirmation: string }) =>
    apiClient.post('/auth/merchant/reset-password', data),
}
