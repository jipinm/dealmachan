import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, CalendarPlus, Loader2 } from 'lucide-react'
import { bookingsApi, type CreateBookingBody } from '@/api/endpoints/bookings'
import { publicApi } from '@/api/endpoints/public'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

// ── Helpers ────────────────────────────────────────────────────────────────

function todayStr() {
  return new Date().toISOString().split('T')[0]
}

// ── Page ──────────────────────────────────────────────────────────────────

export default function BookingFormPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const storeId = Number(id)

  // Load store info for display
  const { data: storeData } = useQuery({
    queryKey: ['store', storeId],
    queryFn: () => publicApi.getStore(storeId),
    staleTime: 60_000,
  })
  const store = storeData?.data

  // Form state
  const [bookingDate, setBookingDate] = useState('')
  const [bookingTime, setBookingTime] = useState('')
  const [numAttendees, setNumAttendees] = useState(1)
  const [notes, setNotes] = useState('')

  const mutation = useMutation({
    mutationFn: (body: CreateBookingBody) => bookingsApi.create(storeId, body),
    onSuccess: (data: any) => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
      const status = data?.data?.status ?? data?.status
      if (status === 'confirmed') {
        toast.success('Booking confirmed!')
      } else {
        toast.success('Booking requested — awaiting confirmation')
      }
      navigate('/bookings')
    },
    onError: (err) => {
      toast.error(getApiError(err))
    },
  })

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!bookingDate) {
      toast.error('Please select a booking date')
      return
    }
    const body: CreateBookingBody = {
      booking_date: bookingDate,
    }
    if (bookingTime) body.booking_time = bookingTime
    if (numAttendees > 1) body.num_attendees = numAttendees
    if (notes.trim()) body.customer_notes = notes.trim()
    mutation.mutate(body)
  }

  return (
    <div className="max-w-[600px] mx-auto px-4 py-6">
      {/* Back */}
      <button
        onClick={() => navigate(-1)}
        className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 mb-5 transition-colors"
      >
        <ArrowLeft size={16} /> Back
      </button>

      {/* Header */}
      <div className="flex items-center gap-3 mb-6">
        <div className="w-10 h-10 rounded-xl bg-brand-100 flex items-center justify-center">
          <CalendarPlus size={20} className="text-brand-600" />
        </div>
        <div>
          <h1 className="font-heading font-bold text-2xl text-slate-800">Book a Visit</h1>
          {store?.data?.store && (
            <p className="text-sm text-slate-500 mt-0.5">{store.data.store.store_name}</p>
          )}
        </div>
      </div>

      {/* Form */}
      <form onSubmit={handleSubmit} className="bg-white rounded-2xl border border-slate-100 p-6 space-y-5 shadow-sm">
        {/* Date */}
        <div>
          <label className="block text-sm font-semibold text-slate-700 mb-1.5">
            Date <span className="text-red-500">*</span>
          </label>
          <input
            type="date"
            value={bookingDate}
            min={todayStr()}
            onChange={e => setBookingDate(e.target.value)}
            required
            className="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent"
          />
        </div>

        {/* Time */}
        <div>
          <label className="block text-sm font-semibold text-slate-700 mb-1.5">
            Preferred Time <span className="text-slate-400 font-normal">(optional)</span>
          </label>
          <input
            type="time"
            value={bookingTime}
            onChange={e => setBookingTime(e.target.value)}
            className="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent"
          />
        </div>

        {/* Attendees */}
        <div>
          <label className="block text-sm font-semibold text-slate-700 mb-1.5">
            Number of People
          </label>
          <input
            type="number"
            value={numAttendees}
            min={1}
            max={50}
            onChange={e => setNumAttendees(Math.max(1, Number(e.target.value)))}
            className="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent"
          />
        </div>

        {/* Notes */}
        <div>
          <label className="block text-sm font-semibold text-slate-700 mb-1.5">
            Notes <span className="text-slate-400 font-normal">(optional)</span>
          </label>
          <textarea
            value={notes}
            onChange={e => setNotes(e.target.value)}
            rows={3}
            placeholder="Any special requests or information for the merchant..."
            className="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent resize-none"
          />
        </div>

        {/* Submit */}
        <button
          type="submit"
          disabled={mutation.isPending}
          className="w-full btn-primary !py-3 !rounded-xl !text-sm font-semibold flex items-center justify-center gap-2"
        >
          {mutation.isPending ? (
            <><Loader2 size={16} className="animate-spin" /> Submitting…</>
          ) : (
            <><CalendarPlus size={16} /> Request Booking</>
          )}
        </button>
      </form>
    </div>
  )
}
