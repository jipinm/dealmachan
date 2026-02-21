import { apiClient } from '@/api/client'
import type { CustomerProfile } from '@/store/authStore'

export interface UpdateProfileRequest {
  name?: string
  date_of_birth?: string
  gender?: 'male' | 'female' | 'other'
  city_id?: number | null
  area_id?: number | null
  bio?: string | null
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

export interface CustomerCard {
  id: number
  card_variant: string
  card_number: string
  card_image: string | null
  status: 'active' | 'inactive' | 'blocked'
  generated_at: string
}

export const profileApi = {
  /** Get full customer profile */
  getProfile: () =>
    apiClient.get<{ data: CustomerProfile & {
      date_of_birth: string | null
      gender: string | null
      city_id: number | null
      area_id: number | null
      bio: string | null
      profession_id: number | null
      city_name: string | null
      area_name: string | null
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

  /** Activate a preprinted card by card number */
  activateCard: (card_number: string) =>
    apiClient.post<{ data: CustomerCard }>('/customers/card/activate', { card_number }),

  /** Change password */
  changePassword: (body: { current_password: string; new_password: string }) =>
    apiClient.put('/customers/profile/password', body),
}

export const favouritesApi = {
  /** Get all favourited merchants */
  list: () =>
    apiClient.get<{ data: any[] }>('/customers/favourites'),

  /** Add a merchant to favourites */
  add: (merchantId: number) =>
    apiClient.post(`/customers/favourites/${merchantId}`),

  /** Remove a merchant from favourites */
  remove: (merchantId: number) =>
    apiClient.delete(`/customers/favourites/${merchantId}`),

  /** Check if a specific merchant is favourited */
  check: (merchantId: number) =>
    apiClient.get<{ data: { is_favourite: boolean } }>(`/customers/favourites/check/${merchantId}`),
}
