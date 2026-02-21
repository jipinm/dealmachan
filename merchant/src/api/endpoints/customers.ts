import { apiClient } from '@/api/client'

export interface MerchantCustomer {
  id: number
  user_id: number
  name: string
  phone: string | null
  email: string | null
  customer_type: 'standard' | 'premium' | 'dealmaker'
  subscription_status: 'active' | 'expired' | 'none'
  user_status: 'active' | 'inactive' | 'blocked' | 'pending'
  created_at: string
}

export interface CreateCustomerPayload {
  name: string
  phone?: string
  email?: string
}

export type UpdateCustomerPayload = Partial<CreateCustomerPayload>

export interface CustomerAnalytics {
  total_coupon_assignments: number
  total_transactions: number
  total_transaction_value: number
  highest_transaction: number
  last_visit_date: string | null
  average_frequency_days: number | null
  response_rate: number // percentage: redeemed / assigned
}

type R<T>    = { success: boolean; message: string; data: T }
type Paged<T> = R<T> & { meta: { total: number; page: number; limit: number; pages: number } }

export const merchantCustomerApi = {
  list: (params?: { page?: number; limit?: number; search?: string }) =>
    apiClient.get<Paged<MerchantCustomer[]>>('/merchants/customers', { params }),

  create: (data: CreateCustomerPayload) =>
    apiClient.post<R<MerchantCustomer>>('/merchants/customers', data),

  update: (id: number, data: UpdateCustomerPayload) =>
    apiClient.put<R<MerchantCustomer>>(`/merchants/customers/${id}`, data),

  get: (id: number) =>
    apiClient.get<R<MerchantCustomer>>(`/merchants/customers/${id}`),

  getAnalytics: (id: number, params?: { from?: string; to?: string }) =>
    apiClient.get<R<CustomerAnalytics>>(`/merchants/customers/${id}/analytics`, { params }),
}
