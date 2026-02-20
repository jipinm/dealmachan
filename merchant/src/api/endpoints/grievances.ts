import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────────

export type GrievanceStatus   = 'open' | 'in_progress' | 'resolved' | 'closed'
export type GrievancePriority = 'low' | 'medium' | 'high' | 'urgent'

export interface Grievance {
  id: number
  subject: string
  description: string
  status: GrievanceStatus
  priority: GrievancePriority
  resolution_notes: string | null
  created_at: string
  resolved_at: string | null
  customer_name: string | null
  customer_phone: string | null
  customer_email?: string | null
  store_name: string | null
  merchant_id: number
}

export interface GrievanceCounts {
  open: number
  in_progress: number
  resolved: number
  closed: number
}

type R<T> = { success: boolean; message: string; data: T; meta?: { counts: GrievanceCounts } }

export const grievanceApi = {
  list: (params?: { status?: GrievanceStatus }) =>
    apiClient.get<R<Grievance[]>>('/merchants/grievances', { params }),

  get: (id: number) =>
    apiClient.get<R<Grievance>>(`/merchants/grievances/${id}`),

  respond: (id: number, resolution_notes: string) =>
    apiClient.post<R<Grievance>>(`/merchants/grievances/${id}/respond`, { resolution_notes }),

  resolve: (id: number) =>
    apiClient.put<R<Grievance>>(`/merchants/grievances/${id}/resolve`, {}),
}
