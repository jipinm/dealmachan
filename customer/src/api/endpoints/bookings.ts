import { apiClient } from '@/api/client'

export type BookingStatus = 'pending' | 'confirmed' | 'rejected' | 'cancelled' | 'completed'

export interface Booking {
  id: number
  store_id: number
  store_name: string
  store_address: string | null
  city_name: string | null
  area_name: string | null
  store_image: string | null
  booking_date: string
  booking_time: string | null
  num_attendees: number
  customer_notes: string | null
  merchant_notes: string | null
  status: BookingStatus
  created_at: string
  updated_at: string | null
}

export interface CreateBookingBody {
  booking_date: string
  booking_time?: string
  num_attendees?: number
  customer_notes?: string
}

export const bookingsApi = {
  /** Create a booking for a store */
  create: (storeId: number, body: CreateBookingBody) =>
    apiClient.post<{ data: { id: number; status: BookingStatus; auto_confirmed: boolean }; message: string }>(
      `/customers/stores/${storeId}/bookings`,
      body
    ),

  /** List current customer's bookings */
  list: () =>
    apiClient.get<{ data: Booking[] }>('/customers/bookings'),

  /** Cancel a booking */
  cancel: (bookingId: number) =>
    apiClient.patch(`/customers/bookings/${bookingId}/cancel`),
}
