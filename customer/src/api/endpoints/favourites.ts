import { apiClient } from '@/api/client'

export interface FavouriteMerchant {
  id: number
  business_name: string
  business_logo: string | null
  business_category: string | null
  subscription_status: string | null
  saved_at: string
  active_coupon_count: number
  avg_rating: number
  total_reviews: number
}

export const favouritesApi = {
  list: (): Promise<FavouriteMerchant[]> =>
    apiClient.get('/customers/favourites').then(r => r.data.data ?? []),

  add: (merchantId: number): Promise<void> =>
    apiClient.post(`/customers/favourites/${merchantId}`).then(r => r.data),

  remove: (merchantId: number): Promise<void> =>
    apiClient.delete(`/customers/favourites/${merchantId}`).then(r => r.data),

  check: (merchantId: number): Promise<{ is_favourite: boolean }> =>
    apiClient.get(`/customers/favourites/check/${merchantId}`).then(r => r.data.data ?? { is_favourite: false }),
}
