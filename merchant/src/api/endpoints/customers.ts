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

type R<T>    = { success: boolean; message: string; data: T }
type Paged<T> = R<T> & { meta: { total: number; page: number; limit: number; pages: number } }

export const merchantCustomerApi = {
  list: (params?: { page?: number; limit?: number; search?: string }) =>
    apiClient.get<Paged<MerchantCustomer[]>>('/merchants/customers', { params }),

  create: (data: CreateCustomerPayload) =>
    apiClient.post<R<MerchantCustomer>>('/merchants/customers', data),
}
