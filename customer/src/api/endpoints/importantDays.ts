import { apiClient } from '@/api/client'

export type EventType = 'Birthday' | 'Anniversary' | 'Others'

export const MONTH_NAMES = [
  'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
]

export interface ImportantDay {
  id: number
  event_type: EventType
  event_specify: string | null
  event_day: number
  event_month: number
  person_name: string | null
  created_at: string
}

export interface CreateImportantDayBody {
  event_type: EventType
  event_day: number
  event_month: number
  event_specify?: string
  person_name?: string
}

export const importantDaysApi = {
  list: () =>
    apiClient.get<{ data: ImportantDay[] }>('/customers/important-days')
      .then((r) => r.data.data ?? []),

  add: (body: CreateImportantDayBody) =>
    apiClient.post<{ data: ImportantDay }>('/customers/important-days', body),

  remove: (id: number) =>
    apiClient.delete(`/customers/important-days/${id}`),
}
