import { apiClient } from '@/api/client'

export type FlashDiscountStatus = 'active' | 'inactive' | 'expired'

export interface FlashDiscount {
  id: number
  merchant_id: number
  store_id: number | null
  store_name: string | null
  title: string
  description: string | null
  discount_percentage: string
  valid_from: string | null
  valid_until: string | null
  max_redemptions: number | null
  current_redemptions: number
  status: FlashDiscountStatus
  created_at: string
  updated_at: string | null
}

export interface CreateFlashDiscountPayload {
  title: string
  description?: string
  discount_percentage: number
  store_id?: number
  valid_from?: string
  valid_until?: string
  max_redemptions?: number
}

type R<T> = { success: boolean; message: string; data: T }

export const flashDiscountApi = {
  list: (params?: { status?: FlashDiscountStatus }) =>
    apiClient.get<R<FlashDiscount[]>>('/merchants/flash-discounts', { params }),

  create: (data: CreateFlashDiscountPayload) =>
    apiClient.post<R<FlashDiscount>>('/merchants/flash-discounts', data),

  update: (id: number, data: Partial<CreateFlashDiscountPayload> & { status?: FlashDiscountStatus }) =>
    apiClient.put<R<FlashDiscount>>(`/merchants/flash-discounts/${id}`, data),

  remove: (id: number) =>
    apiClient.delete<R<{ id: number }>>(`/merchants/flash-discounts/${id}`),
}
