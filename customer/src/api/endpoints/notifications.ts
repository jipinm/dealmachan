import { apiClient } from '@/api/client'

export interface Notification {
  id: number
  notification_type: string
  title: string
  message: string
  read_status: 0 | 1
  created_at: string
  link?: string | null
}

export const notificationsApi = {
  /** Get notification list */
  getAll: (params?: { page?: number; type?: string }) =>
    apiClient.get<{
      data: Notification[]
      meta: { total: number; unread: number; page: number }
    }>('/customers/notifications', { params }),

  /** Get unread count only (lightweight, for badge) */
  getUnreadCount: () =>
    apiClient.get<{ data: { count: number } }>('/customers/notifications/unread-count'),

  /** Mark a single notification as read */
  markRead: (id: number) =>
    apiClient.put(`/customers/notifications/${id}/read`),

  /** Mark all as read */
  markAllRead: () =>
    apiClient.put('/customers/notifications/read-all'),

  /** Delete a notification */
  deleteNotification: (id: number) =>
    apiClient.delete(`/customers/notifications/${id}`),
}
