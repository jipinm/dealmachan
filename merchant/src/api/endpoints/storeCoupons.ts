import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────────

export type StoreCouponStatus = 'active' | 'inactive' | 'expired'

export interface StoreCoupon {
  id: number
  merchant_id: number
  store_id: number | null
  store_name: string | null
  title: string
  description: string | null
  terms_conditions: string | null
  discount_type: 'percentage' | 'fixed'
  discount_value: string
  min_purchase_amount: string | null
  max_discount_amount: string | null
  valid_from: string | null
  valid_until: string | null
  status: StoreCouponStatus
  is_gifted: number
  gifted_to_customer_id: number | null
  gifted_to_customer_name: string | null
  gifted_at: string | null
  is_redeemed: number
  redeemed_at: string | null
  created_at: string
  updated_at: string | null
}

export interface CreateStoreCouponPayload {
  title: string
  description?: string
  terms_conditions?: string
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  min_purchase_amount?: number
  max_discount_amount?: number
  store_id?: number
  valid_from?: string
  valid_until?: string
}

export type UpdateStoreCouponPayload = Partial<CreateStoreCouponPayload> & {
  status?: StoreCouponStatus
}

export interface AssignStoreCouponPayload {
  customer_id: number
}

export interface BulkAssignStoreCouponPayload {
  customer_ids: number[]
}

export interface RedeemStoreCouponPayload {
  customer_phone: string
  transaction_amount?: number
}

export interface StoreCouponRedeemResult {
  store_coupon_id: number
  title: string
  discount_type: 'percentage' | 'fixed'
  discount_value: string
  discount_amount: number
  transaction_amount: number | null
}

export interface StoreCouponListMeta {
  total: number
  page: number
  limit: number
  pages: number
}

type R<T>     = { success: boolean; message: string; data: T }
type Paged<T> = R<T> & { meta: StoreCouponListMeta }

// ── API ───────────────────────────────────────────────────────────────────────

export const storeCouponApi = {
  list: (params?: { status?: StoreCouponStatus; store_id?: number; page?: number; limit?: number }) =>
    apiClient.get<Paged<StoreCoupon[]>>('/merchants/store-coupons', { params }),

  get: (id: number) =>
    apiClient.get<R<StoreCoupon>>(`/merchants/store-coupons/${id}`),

  create: (data: CreateStoreCouponPayload) =>
    apiClient.post<R<StoreCoupon>>('/merchants/store-coupons', data),

  update: (id: number, data: UpdateStoreCouponPayload) =>
    apiClient.put<R<StoreCoupon>>(`/merchants/store-coupons/${id}`, data),

  remove: (id: number) =>
    apiClient.delete<R<{ id: number }>>(`/merchants/store-coupons/${id}`),

  assign: (id: number, data: AssignStoreCouponPayload) =>
    apiClient.post<R<StoreCoupon>>(`/merchants/store-coupons/${id}/assign`, data),

  bulkAssign: (id: number, data: BulkAssignStoreCouponPayload) =>
    apiClient.post<R<{ assigned_count: number }>>(`/merchants/store-coupons/${id}/bulk-assign`, data),

  redeem: (id: number, data: RedeemStoreCouponPayload) =>
    apiClient.post<R<StoreCouponRedeemResult>>(`/merchants/store-coupons/${id}/redeem`, data),
}
