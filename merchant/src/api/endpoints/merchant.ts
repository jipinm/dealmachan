import { apiClient } from '@/api/client'

// ── Types ────────────────────────────────────────────────────────────────────

export interface MerchantLabel {
  id: number
  label_name: string
  label_icon: string | null
  assigned_at?: string
}

export interface MerchantCategory {
  category_id: number
  category_name: string
  sub_category_id: number | null
  sub_category_name: string | null
}

export interface Subscription {
  id: number
  plan_type: string
  start_date: string
  expiry_date: string
  auto_renew: boolean
  status: 'active' | 'expired' | 'cancelled'
  payment_amount: number | null
}

export interface MerchantProfileDetail {
  id: number
  user_id: number
  business_name: string
  business_logo: string | null
  banner_image: string | null
  registration_number: string | null
  gst_number: string | null
  business_address: string | null
  website_url: string | null
  is_premium: boolean
  subscription_status: 'active' | 'expired' | 'trial'
  subscription_expiry: string | null
  profile_status: 'pending' | 'approved' | 'rejected'
  created_at: string
  email: string
  phone: string | null
  account_status: string
  last_login: string | null
  label_id: number | null
  label_name: string | null
  label_icon: string | null
  categories: MerchantCategory[]
}

export interface ProfileResponse {
  profile: MerchantProfileDetail
  stores: { total_stores: number; active_stores: number }
  labels: MerchantLabel[]
  subscription: Subscription | null
  coupons: { total_coupons: number; active_coupons: number }
}

export interface UpdateProfilePayload {
  business_name?: string
  phone?: string
  gst_number?: string
  registration_number?: string
  business_address?: string
  website_url?: string
  category_ids?: number[]
}

export interface Notification {
  id: number
  notification_type: 'info' | 'success' | 'warning' | 'error'
  title: string
  message: string
  action_url: string | null
  read_status: boolean
  read_at: string | null
  created_at: string
}

export interface NotificationMeta {
  current_page: number
  per_page: number
  total_count: number
  unread_count: number
}

// ── API Calls ─────────────────────────────────────────────────────────────────

export const merchantApi = {
  // Profile
  getProfile: () =>
    apiClient.get<{ success: boolean; data: ProfileResponse }>('/merchants/profile'),

  updateProfile: (data: UpdateProfilePayload) =>
    apiClient.put<{ success: boolean; data: ProfileResponse }>('/merchants/profile', data),

  uploadLogo: (file: File) => {
    const form = new FormData()
    form.append('logo', file)
    return apiClient.post<{ success: boolean; data: { logo_url: string } }>(
      '/merchants/profile/logo',
      form,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
  },

  uploadBanner: (file: File) => {
    const form = new FormData()
    form.append('banner', file)
    return apiClient.post<{ success: boolean; data: { banner_image: string } }>(
      '/merchants/profile/banner',
      form,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
  },

  deleteBanner: () =>
    apiClient.delete<{ success: boolean }>('/merchants/profile/banner'),

  renewSubscription: (data?: { message?: string }) =>
    apiClient.post('/merchants/subscription/renew', data ?? {}),

  upgradeSubscription: (data: { plan_type: string }) =>
    apiClient.post('/merchants/subscription/upgrade', data),

  changePassword: (data: { current_password: string; new_password: string; confirm_password: string }) =>
    apiClient.put<{ success: boolean; message: string }>('/merchants/profile/password', data),

  // Notifications
  getNotifications: (params?: { page?: number; limit?: number; unread?: boolean }) =>
    apiClient.get<{ success: boolean; data: Notification[]; meta: NotificationMeta }>(
      '/merchants/notifications',
      { params },
    ),

  markAllNotificationsRead: () =>
    apiClient.put('/merchants/notifications/read-all', {}),

  markNotificationRead: (id: number) =>
    apiClient.put(`/merchants/notifications/${id}/read`, {}),
}
