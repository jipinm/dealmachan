import { apiClient } from '@/api/client'

export interface ReferralStats {
  total: number
  completed: number
  pending: number
  total_rewards: number
}

export interface ReferralHistoryItem {
  id: number
  status: 'pending' | 'completed' | 'rewarded'
  reward_amount: string | null
  reward_given: number
  created_at: string
  completed_at: string | null
  referee_name: string | null
}

export interface ReferralData {
  referral_code: string
  stats: ReferralStats
  history: ReferralHistoryItem[]
}

export const referralsApi = {
  get: (): Promise<ReferralData> =>
    apiClient.get('/customers/referral').then(r => r.data.data),
}
