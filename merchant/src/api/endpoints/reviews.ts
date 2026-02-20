import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────────

export type ReviewStatus = 'pending' | 'approved' | 'rejected'

export interface Review {
  id: number
  rating: number
  review_text: string | null
  status: ReviewStatus
  created_at: string
  customer_name: string | null
  store_name: string | null
  merchant_id: number
  merchant_reply: string | null
  merchant_reply_at: string | null
}

export interface ReviewSummary {
  total: number
  avg_rating: string | null
  five: number
  four: number
  three: number
  two: number
  one: number
}

type R<T> = { success: boolean; message: string; data: T; meta?: { summary: ReviewSummary } }

export const reviewApi = {
  list: (params?: { rating?: number; status?: ReviewStatus }) =>
    apiClient.get<R<Review[]>>('/merchants/reviews', { params }),

  get: (id: number) =>
    apiClient.get<R<Review>>(`/merchants/reviews/${id}`),

  reply: (id: number, reply_text: string) =>
    apiClient.post<R<{ reply_text: string }>>(`/merchants/reviews/${id}/reply`, { reply_text }),
}
