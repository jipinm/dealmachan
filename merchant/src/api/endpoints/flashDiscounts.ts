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
  banner_image: string | null
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

export interface RedeemFlashDiscountPayload {
  customer_phone: string
  transaction_amount?: number
  store_id?: number
}

export interface FlashDiscountRedeemResult {
  flash_discount_id: number
  title: string
  discount_percentage: string
  discount_amount: number
  transaction_amount: number | null
}

type R<T> = { success: boolean; message: string; data: T }

export const flashDiscountApi = {
  list: (params?: { status?: FlashDiscountStatus; store_id?: number }) =>
    apiClient.get<R<FlashDiscount[]>>('/merchants/flash-discounts', { params }),

  create: (data: CreateFlashDiscountPayload) =>
    apiClient.post<R<FlashDiscount>>('/merchants/flash-discounts', data),

  update: (id: number, data: Partial<CreateFlashDiscountPayload> & { status?: FlashDiscountStatus }) =>
    apiClient.put<R<FlashDiscount>>(`/merchants/flash-discounts/${id}`, data),

  remove: (id: number) =>
    apiClient.delete<R<{ id: number }>>(`/merchants/flash-discounts/${id}`),

  redeem: (id: number, data: RedeemFlashDiscountPayload) =>
    apiClient.post<R<FlashDiscountRedeemResult>>(`/merchants/flash-discounts/${id}/redeem`, data),

  uploadImage: (id: number, file: File) => {
    const form = new FormData()
    form.append('image', file)
    return apiClient.post<{ success: boolean; data: { banner_image: string }; message: string }>(
      `/merchants/flash-discounts/${id}/image`, form,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
  },

  deleteImage: (id: number) =>
    apiClient.delete<{ success: boolean; message: string }>(
      `/merchants/flash-discounts/${id}/image`
    ),
}
