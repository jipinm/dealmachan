import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────

export type GrievanceStatus = 'open' | 'in_progress' | 'resolved' | 'closed'
export type GrievancePriority = 'low' | 'medium' | 'high' | 'urgent'

export interface GrievanceSummary {
  id: number
  subject: string
  status: GrievanceStatus
  priority: GrievancePriority
  created_at: string
  resolved_at: string | null
  resolution_notes: string | null
  merchant_id: number
  business_name: string
  business_logo: string | null
  store_id: number | null
  store_name: string | null
}

export interface GrievanceDetail extends GrievanceSummary {
  description: string
  store_address: string | null
}

export interface CreateGrievanceBody {
  merchant_id: number
  store_id?: number | null
  subject: string
  description: string
}

// ── API ────────────────────────────────────────────────────────────────────

export const grievancesApi = {
  /** List the authed customer's grievances */
  list: (params?: { status?: GrievanceStatus; page?: number; per_page?: number }) =>
    apiClient
      .get<{ data: { data: GrievanceSummary[]; pagination: { total: number; page: number; per_page: number; pages: number } } }>('/customers/grievances', { params })
      .then(r => r.data.data),   // → { data: GrievanceSummary[], pagination: {...} }

  /** Single grievance detail */
  get: (id: number) =>
    apiClient
      .get<{ data: { data: GrievanceDetail } }>(`/customers/grievances/${id}`)
      .then(r => r.data.data.data),  // → GrievanceDetail

  /** Submit a new grievance */
  create: (body: CreateGrievanceBody) =>
    apiClient
      .post<{ data: { data: GrievanceSummary } }>('/customers/grievances', body)
      .then(r => r.data.data.data),  // → GrievanceSummary

  /** Archive (close) a grievance */
  archive: (id: number) =>
    apiClient.put(`/customers/grievances/${id}/archive`),
}
