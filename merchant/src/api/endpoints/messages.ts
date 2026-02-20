import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────────

export type SenderType = 'admin' | 'merchant' | 'customer'

export interface Message {
  id: number
  sender_id: number
  sender_type: SenderType
  receiver_id: number
  receiver_type: SenderType
  subject: string | null
  message_text: string
  parent_message_id: number | null
  read_status: number
  read_at: string | null
  sent_at: string
  reply_count?: number
  has_unread?: number
}

export interface MessageThread {
  message: Message
  replies: Message[]
}

export interface SendMessagePayload {
  message_text: string
  subject?: string
  parent_message_id?: number
}

type R<T> = { success: boolean; message: string; data: T; meta?: { unread_count: number } }

export const messageApi = {
  list: () =>
    apiClient.get<R<Message[]>>('/merchants/messages'),

  send: (data: SendMessagePayload) =>
    apiClient.post<R<Message>>('/merchants/messages', data),

  getThread: (id: number) =>
    apiClient.get<R<MessageThread>>(`/merchants/messages/${id}`),

  markRead: (id: number) =>
    apiClient.put<R<{ id: number; read_status: number }>>(`/merchants/messages/${id}/read`, {}),
}
