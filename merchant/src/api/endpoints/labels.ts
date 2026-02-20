import { apiClient } from '@/api/client'

export interface Label {
  id: number
  label_name: string
  label_icon: string | null
  description: string | null
  priority_weight: number
  assigned?: boolean
  assigned_at?: string
}

export interface LabelsData {
  assigned:  Label[]
  available: Label[]
}

type R<T> = { success: boolean; message: string; data: T }

export const labelApi = {
  list: () =>
    apiClient.get<R<LabelsData>>('/merchants/labels'),

  request: (label_id: number, note?: string) =>
    apiClient.post<R<{ label_id: number; label_name: string }>>('/merchants/labels/request', { label_id, note }),
}
