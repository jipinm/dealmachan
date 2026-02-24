import { apiClient } from '@/api/client'

// ── Types ────────────────────────────────────────────────────────────────────

export interface SurveyListItem {
  id: number
  title: string
  description: string | null
  active_from: string | null
  active_until: string | null
  status: 'active' | 'closed'
  total_responses: number
  question_count: number
  already_submitted: boolean
}

export interface SurveyQuestion {
  id: number
  type: 'rating' | 'radio' | 'checkbox' | 'textarea' | 'text' | 'select'
  question: string
  required: boolean
  options?: string[]
  scale?: number
}

export interface SurveyDetail {
  id: number
  title: string
  description: string | null
  status: 'active' | 'closed'
  active_from: string | null
  active_until: string | null
  questions: SurveyQuestion[]
  already_submitted: boolean
  submission: { id: number; submitted_at: string } | null
}

export interface CompletedSurvey {
  id: number
  title: string
  description: string | null
  response_id: number
  submitted_at: string
}

export type SurveyResponses = Record<number, string | number | string[]>

// ── API ──────────────────────────────────────────────────────────────────────

export const surveysApi = {
  list: (): Promise<SurveyListItem[]> =>
    apiClient.get('/customers/surveys').then(r => r.data.data ?? []),

  get: (id: number): Promise<SurveyDetail> =>
    apiClient.get(`/customers/surveys/${id}`).then(r => r.data.data),

  submit: (id: number, responses: SurveyResponses): Promise<{ message: string }> =>
    apiClient.post(`/customers/surveys/${id}/submit`, { responses }).then(r => r.data),

  completed: (): Promise<CompletedSurvey[]> =>
    apiClient.get('/customers/surveys/completed').then(r => r.data.data ?? []),
}
