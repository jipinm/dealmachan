import { apiClient } from '@/api/client'

export type BookingStatus = 'pending' | 'confirmed' | 'rejected' | 'cancelled' | 'completed'

export interface MerchantBooking {
  id: number
  store_id: number
  store_name: string
  customer_id: number
  customer_name: string
  customer_phone: string | null
  customer_email: string | null
  booking_date: string
  booking_time: string | null
  num_attendees: number
  customer_notes: string | null
  merchant_notes: string | null
  status: BookingStatus
  created_at: string
}

export interface BookingFilters {
  status?: BookingStatus
  store_id?: number
  date?: string
}

export const merchantBookingsApi = {
  /** List all bookings for this merchant */
  list: (filters?: BookingFilters) =>
    apiClient.get<{ data: MerchantBooking[] }>('/merchants/bookings', { params: filters }),

  /** Confirm a pending booking */
  confirm: (id: number) =>
    apiClient.patch(`/merchants/bookings/${id}/confirm`),

  /** Reject a booking */
  reject: (id: number, merchant_notes?: string) =>
    apiClient.patch(`/merchants/bookings/${id}/reject`, { merchant_notes }),

  /** Mark a booking as completed */
  complete: (id: number) =>
    apiClient.patch(`/merchants/bookings/${id}/complete`),
}
