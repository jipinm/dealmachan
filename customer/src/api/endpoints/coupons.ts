import { apiClient } from '@/api/client'

export interface WalletCoupon {
  id: number
  title: string
  description: string | null
  coupon_code: string
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  valid_until: string | null
  banner_image: string | null
  terms_and_conditions: string | null
  merchant_id: number
  merchant_name: string
  merchant_logo: string | null
  saved_at: string
  is_gift: false
}

export interface GiftCoupon {
  gift_id: number
  id: number
  title: string
  coupon_code: string
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  valid_until: string | null
  merchant_name: string
  merchant_logo: string | null
  gifted_at: string
  acceptance_status: 'pending' | 'accepted' | 'rejected'
  is_gift: true
}

export interface RedemptionRecord {
  id: number
  coupon_title: string
  coupon_code: string
  discount_amount: number
  redeemed_at: string
  merchant_name: string
  merchant_logo: string | null
  store_name: string | null
}

export interface RedeemRequest {
  coupon_code: string
  store_id?: number
}

export interface StoreCoupon {
  id: number
  coupon_code: string
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  valid_from: string | null
  valid_until: string | null
  is_redeemed: number
  redeemed_at: string | null
  status: 'active' | 'inactive' | 'expired'
  gifted_at: string
  merchant_name: string
  merchant_logo: string | null
  store_name: string | null
  store_address: string | null
}

export const couponsApi = {
  /** Get all saved + accepted gift coupons in wallet */
  getWallet: () =>
    apiClient.get<{ data: { saved: WalletCoupon[]; gifts: GiftCoupon[] } }>('/customers/coupons/wallet'),

  /** Get redemption history */
  getHistory: (params?: { page?: number; per_page?: number }) =>
    apiClient.get<{
      data: RedemptionRecord[]
      meta: { total: number; page: number; per_page: number }
    }>('/customers/coupons/history', { params }),

  /** Save a coupon to wallet (basic) */
  saveCoupon: (couponId: number) =>
    apiClient.post(`/customers/coupons/${couponId}/save`),

  /** Save a coupon with full 6-rule server-side validation */
  subscribe: (couponId: number) =>
    apiClient.post<{ data: { coupon_id: number; coupon_title: string } }>(`/customers/coupons/${couponId}/subscribe`),

  /** Remove saved coupon from wallet */
  unsaveCoupon: (couponId: number) =>
    apiClient.delete(`/customers/coupons/${couponId}/save`),

  /** Redeem a coupon */
  redeem: (body: RedeemRequest) =>
    apiClient.post<{
      data: {
        redemption_id: number
        discount_amount: number
        message: string
      }
    }>('/customers/coupons/redeem', body),

  /** Get all received gift coupons */
  getGiftCoupons: () =>
    apiClient.get<{ data: GiftCoupon[] }>('/customers/gift-coupons'),

  /** Accept a gift coupon */
  acceptGift: (giftId: number) =>
    apiClient.post(`/customers/gift-coupons/${giftId}/accept`),

  /** Reject a gift coupon */
  rejectGift: (giftId: number) =>
    apiClient.post(`/customers/gift-coupons/${giftId}/reject`),

  /** Get store coupons gifted by a merchant directly to this customer */
  getStoreCoupons: () =>
    apiClient.get<{ data: StoreCoupon[] }>('/customers/store-coupons').then(r => r.data.data ?? []),
}
