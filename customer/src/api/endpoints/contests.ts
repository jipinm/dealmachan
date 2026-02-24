import { apiClient } from '@/api/client'

// ── Types ────────────────────────────────────────────────────────────────────

export interface ContestListItem {
  id: number
  title: string
  description: string | null
  start_date: string | null
  end_date: string | null
  status: 'active' | 'completed' | 'cancelled' | 'draft'
  participant_count: number
  has_entered: boolean
}

export interface ContestWinner {
  position: number
  prize_details: string | null
  announced_at: string
  customer_name: string
}

export interface ContestDetail {
  id: number
  title: string
  description: string | null
  rules: string[]
  start_date: string | null
  end_date: string | null
  status: 'active' | 'completed' | 'cancelled' | 'draft'
  participant_count: number
  has_entered: boolean
  my_entry: { id: number; participated_at: string; entry_data_json: string | null } | null
  winners: ContestWinner[]
}

export interface MyContestEntry {
  id: number
  title: string
  description: string | null
  start_date: string | null
  end_date: string | null
  status: string
  participated_at: string
  entry_data_json: string | null
  is_winner: boolean
  won_positions: string | null
}

// ── API ──────────────────────────────────────────────────────────────────────

export const contestsApi = {
  list: (): Promise<ContestListItem[]> =>
    apiClient.get('/customers/contests').then(r => r.data.data ?? []),

  get: (id: number): Promise<ContestDetail> =>
    apiClient.get(`/customers/contests/${id}`).then(r => r.data.data),

  participate: (id: number, entryData?: Record<string, unknown>): Promise<{ message: string }> =>
    apiClient.post(`/customers/contests/${id}/participate`, entryData ?? {}).then(r => r.data),

  myEntries: (): Promise<MyContestEntry[]> =>
    apiClient.get('/customers/contests/my-entries').then(r => r.data.data ?? []),
}
