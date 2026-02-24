import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────────

export type DiscountType    = 'percentage' | 'fixed'
export type ApprovalStatus  = 'pending' | 'approved' | 'rejected'
export type CouponStatus    = 'active' | 'inactive' | 'expired'
export type CouponTab       = 'all' | 'active' | 'pending' | 'expired' | 'inactive'

export interface Coupon {
  id: number
  title: string
  description: string | null
  coupon_code: string
  discount_type: DiscountType
  discount_value: string        // comes as string from DB decimal
  min_purchase_amount: string | null
  max_discount_amount: string | null
  merchant_id: number
  store_id: number | null
  store_name: string | null
  valid_from: string | null
  valid_until: string | null
  usage_limit: number | null
  usage_count: number
  is_admin_coupon: number
  approval_status: ApprovalStatus
  status: CouponStatus
  terms_conditions: string | null
  banner_image: string | null
  created_by: number
  created_at: string
  updated_at: string | null
  redemption_count: number
}

export interface CouponRedemption {
  id: number
  coupon_id: number
  customer_id: number
  customer_name: string
  customer_phone: string
  store_id: number | null
  store_name: string | null
  redeemed_at: string
  discount_amount: string
  transaction_amount: string | null
  verified_by_merchant: number
}

export interface CreateCouponPayload {
  title: string
  description?: string
  coupon_code?: string
  discount_type: DiscountType
  discount_value: number
  min_purchase_amount?: number | null
  max_discount_amount?: number | null
  store_id?: number | null
  valid_from?: string | null
  valid_until?: string | null
  usage_limit?: number | null
  terms_conditions?: string
}

export type UpdateCouponPayload = Partial<Omit<CreateCouponPayload, 'coupon_code'>> & {
  status?: CouponStatus
}

export interface RedeemPayload {
  coupon_code: string
  customer_phone?: string
  customer_id?: number
  store_id?: number
  transaction_amount?: number
}

export interface RedeemResult {
  coupon_code: string
  coupon_title: string
  discount_type: DiscountType
  discount_value: string
  discount_amount: number
  transaction_amount: number | null
}

// ── Customer Lookup types ─────────────────────────────────────────────────────

export interface RedeemableItem {
  id: number
  type: 'platform_coupon' | 'store_coupon' | 'flash_discount'
  title: string
  description: string | null
  discount_type: string
  discount_value: string
  store_name: string | null
  valid_until: string | null
  coupon_code?: string
}

export interface CustomerLookupResult {
  customer: {
    id: number
    name: string
    phone: string | null
    email: string | null
  }
  redeemable_items: RedeemableItem[]
}

export interface CouponListMeta {
  total: number
  page: number
  limit: number
  pages: number
}

// ── API functions ─────────────────────────────────────────────────────────────

export const couponApi = {
  list: (params?: { tab?: CouponTab; page?: number; limit?: number; store_id?: number }) =>
    apiClient.get<{ success: boolean; data: Coupon[]; meta: CouponListMeta }>(
      '/merchants/coupons', { params }
    ),

  create: (payload: CreateCouponPayload) =>
    apiClient.post<{ success: boolean; data: Coupon; message: string }>(
      '/merchants/coupons', payload
    ),

  get: (id: number) =>
    apiClient.get<{ success: boolean; data: Coupon }>(
      `/merchants/coupons/${id}`
    ),

  update: (id: number, payload: UpdateCouponPayload) =>
    apiClient.put<{ success: boolean; data: Coupon; message: string }>(
      `/merchants/coupons/${id}`, payload
    ),

  remove: (id: number) =>
    apiClient.delete(`/merchants/coupons/${id}`),

  getRedemptions: (id: number, params?: { page?: number; limit?: number }) =>
    apiClient.get<{ success: boolean; data: CouponRedemption[]; meta: { total: number; page: number; limit: number } }>(
      `/merchants/coupons/${id}/redemptions`, { params }
    ),

  scanRedeem: (payload: RedeemPayload) =>
    apiClient.post<{ success: boolean; data: RedeemResult; message: string }>(
      '/merchants/coupons/scan-redeem', payload
    ),

  manualRedeem: (payload: RedeemPayload) =>
    apiClient.post<{ success: boolean; data: RedeemResult; message: string }>(
      '/merchants/coupons/manual-redeem', payload
    ),

  uploadImage: (id: number, file: File) => {
    const form = new FormData()
    form.append('image', file)
    return apiClient.post<{ success: boolean; data: { banner_image: string }; message: string }>(
      `/merchants/coupons/${id}/image`, form,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
  },

  deleteImage: (id: number) =>
    apiClient.delete<{ success: boolean; message: string }>(
      `/merchants/coupons/${id}/image`
    ),

  lookupCustomer: (search: string) =>
    apiClient.get<{ success: boolean; data: CustomerLookupResult }>(
      '/merchants/redemption/customer-lookup', { params: { search } }
    ),
}
