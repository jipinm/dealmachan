import { apiClient } from '@/api/client'
import type { CustomerProfile } from '@/store/authStore'

export interface UpdateProfileRequest {
  name?: string
  last_name?: string | null
  date_of_birth?: string
  gender?: 'male' | 'female' | 'other'
  bio?: string | null
  occupation?: string | null
  full_address?: string | null
  pincode?: string | null
  city_id?: number | null
  area_id?: number | null
  profession_id?: number | null
}

export interface Subscription {
  status: 'active' | 'expired' | 'none'
  plan_type: string | null
  start_date: string | null
  expiry_date: string | null
  auto_renew: boolean
  days_remaining: number
}

export interface CardPartner {
  id: number
  partner_type: 'premium' | 'normal'
  partner_image: string | null
  url: string | null
}

export interface CustomerCard {
  id: number
  card_variant: string
  card_number: string
  card_image: string | null
  status: 'active' | 'inactive' | 'blocked'
  generated_at: string
  // Card configuration fields (populated by Phase C API)
  card_configuration_id: number | null
  expiry_date: string | null
  expiry_status: 'active' | 'expiring_soon' | 'expired' | null
  days_remaining: number | null
  config_name: string | null
  classification: 'silver' | 'gold' | 'platinum' | 'diamond' | null
  features_html: string | null
  max_live_coupons: number | null
  coupon_authorization: number | null
  partners: CardPartner[]
}

export interface CustomerStats {
  saved: number
  redeemed: number
  referrals: number
}

export const profileApi = {
  /** Get full customer profile */
  getProfile: () =>
    apiClient.get<{ data: CustomerProfile & {
      date_of_birth: string | null
      gender: string | null
      last_name: string | null
      bio: string | null
      occupation: string | null
      full_address: string | null
      pincode: string | null
      city_id: number | null
      area_id: number | null
      city_name: string | null
      area_name: string | null
      profession_id: number | null
    } }>('/customers/profile'),

  /** Update profile fields */
  updateProfile: (body: UpdateProfileRequest) =>
    apiClient.put<{ data: CustomerProfile }>('/customers/profile', body),

  /** Upload profile photo */
  uploadPhoto: (file: File) => {
    const fd = new FormData()
    fd.append('image', file)
    return apiClient.post<{ data: { image_url: string } }>(
      '/customers/profile/image',
      fd,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
  },

  /** Get subscription status */
  getSubscription: () =>
    apiClient.get<{ data: Subscription }>('/customers/subscription'),

  /** Renew subscription */
  renewSubscription: (body: { payment_token?: string }) =>
    apiClient.post<{ data: Subscription }>('/customers/subscription/renew', body),

  /** Get assigned card */
  getCard: () =>
    apiClient.get<{ data: CustomerCard | null }>('/customers/card'),

  /** Activate a preprinted card by card number (auth_code required for pre-printed cards) */
  activateCard: (card_number: string, auth_code?: string) =>
    apiClient.post<{ data: CustomerCard }>('/customers/card/activate', { card_number, ...(auth_code ? { auth_code } : {}) }),

  /** Request an auth code for a pre-printed card (code sent to card issuer/merchant) */
  requestCardAuthCode: (card_number: string) =>
    apiClient.post<{ data: { message: string } }>('/customers/card/request-auth-code', { card_number }),

  /** Select a card configuration (generates a new card for the customer) */
  selectCard: (configuration_id: number) =>
    apiClient.post<{ data: CustomerCard }>('/customers/card/select', { configuration_id }),

  /** Change password */
  changePassword: (body: { current_password: string; new_password: string }) =>
    apiClient.put('/customers/profile/password', body),

  /** Set new password (mandatory reset — no current password required) */
  setNewPassword: (body: { new_password: string; confirm_password: string }) =>
    apiClient.post('/customers/password/set-new', body),

  /** Get activity stats: saved coupons, redeemed, referrals */
  getStats: (): Promise<CustomerStats> =>
    apiClient.get('/customers/stats').then(r => r.data.data ?? r.data),
}
