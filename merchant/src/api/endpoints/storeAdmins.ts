import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface StoreAdmin {
  id: number           // merchant_store_users.id
  user_id: number
  store_id: number
  store_name: string | null
  name: string
  email: string
  phone: string | null
  access_scope: 'store'
  status: 'active' | 'inactive'
  created_at: string
  last_login: string | null
}

export interface CreateStoreAdminPayload {
  name: string
  email: string
  phone?: string
  password: string
  store_id: number
}

export interface UpdateStoreAdminPayload {
  name?: string
  phone?: string
  status?: 'active' | 'inactive'
}

type R<T> = { success: boolean; message: string; data: T }

// ── API Calls ─────────────────────────────────────────────────────────────────

export const storeAdminApi = {
  list: () =>
    apiClient.get<R<StoreAdmin[]>>('/merchants/store-admins'),

  create: (data: CreateStoreAdminPayload) =>
    apiClient.post<R<StoreAdmin>>('/merchants/store-admins', data),

  update: (id: number, data: UpdateStoreAdminPayload) =>
    apiClient.put<R<StoreAdmin>>(`/merchants/store-admins/${id}`, data),

  deactivate: (id: number) =>
    apiClient.put<R<{ id: number }>>(`/merchants/store-admins/${id}/deactivate`, {}),

  resetPassword: (id: number, new_password: string) =>
    apiClient.put<R<{ id: number }>>(`/merchants/store-admins/${id}/reset-password`, { new_password }),
}
