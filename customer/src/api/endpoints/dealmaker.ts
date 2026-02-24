import { apiClient } from '../client'

// ── Types ────────────────────────────────────────────────────────────────────

export interface DealmakerApplication {
  id: number
  status: 'pending' | 'approved' | 'rejected'
  applied_at: string
  reviewed_at: string | null
  rejection_reason: string | null
}

export interface TaskSummary {
  total: number
  assigned: number
  in_progress: number
  completed: number
  total_earned: number
  pending_earnings: number
}

export interface DealmakerStatus {
  is_dealmaker: boolean
  approved_at: string | null
  customer_type: 'standard' | 'premium' | 'dealmaker'
  application: DealmakerApplication | null
  task_summary: TaskSummary | null
}

export interface DealmakerTask {
  id: number
  task_type: 'customer_assistance' | 'merchant_visit' | 'survey' | 'promotion' | 'other'
  task_description: string
  status: 'assigned' | 'in_progress' | 'completed' | 'verified'
  assigned_at: string
  completed_at: string | null
  completion_notes: string | null
  reward_amount: string
  reward_status: 'pending' | 'paid'
}

export interface EarningsRecord {
  id: number
  task_type: string
  task_description: string
  reward_amount: string
  reward_status: 'pending' | 'paid'
  completed_at: string | null
  status: string
}

export interface DealmakerEarnings {
  total_earned: number
  total_pending: number
  records: EarningsRecord[]
}

export interface ApplyBody {
  motivation: string
  city?: string
  experience?: string
}

// ── API ──────────────────────────────────────────────────────────────────────

export const dealmakerApi = {
  getStatus: (): Promise<DealmakerStatus> =>
    apiClient.get('/customers/dealmaker/status').then(r => r.data.data ?? r.data),

  apply: (data: ApplyBody): Promise<{ message: string }> =>
    apiClient.post('/customers/dealmaker/apply', data).then(r => r.data),

  getTasks: (): Promise<DealmakerTask[]> =>
    apiClient.get('/customers/dealmaker/tasks').then(r => r.data.data ?? r.data),

  completeTask: (taskId: number, notes?: string): Promise<{ task_id: number; new_status: string }> =>
    apiClient
      .post(`/customers/dealmaker/tasks/${taskId}/complete`, { completion_notes: notes ?? '' })
      .then(r => r.data.data ?? r.data),

  getEarnings: (): Promise<DealmakerEarnings> =>
    apiClient.get('/customers/dealmaker/earnings').then(r => r.data.data ?? r.data),
}
