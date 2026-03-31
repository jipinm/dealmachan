import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  CalendarCheck, Clock, CheckCircle, XCircle, Ban,
  User, Phone, Calendar, Loader2, AlertTriangle,
  Check, X, CalendarCheck2,
} from 'lucide-react'
import { merchantBookingsApi, type MerchantBooking, type BookingStatus, type BookingFilters } from '@/api/endpoints/bookings'
import toast from 'react-hot-toast'

// ── Status config ──────────────────────────────────────────────────────────

const STATUS_CONFIG: Record<BookingStatus, { label: string; color: string; Icon: React.ElementType }> = {
  pending:   { label: 'Pending',   color: 'bg-yellow-100 text-yellow-700', Icon: Clock         },
  confirmed: { label: 'Confirmed', color: 'bg-green-100  text-green-700',  Icon: CheckCircle   },
  rejected:  { label: 'Rejected',  color: 'bg-red-100    text-red-700',    Icon: XCircle       },
  cancelled: { label: 'Cancelled', color: 'bg-slate-100  text-slate-500',  Icon: Ban           },
  completed: { label: 'Completed', color: 'bg-blue-100   text-blue-700',   Icon: CalendarCheck },
}

type FilterStatus = 'all' | BookingStatus

const STATUS_TABS: { id: FilterStatus; label: string }[] = [
  { id: 'all',       label: 'All'       },
  { id: 'pending',   label: 'Pending'   },
  { id: 'confirmed', label: 'Confirmed' },
  { id: 'completed', label: 'Completed' },
  { id: 'rejected',  label: 'Rejected'  },
  { id: 'cancelled', label: 'Cancelled' },
]

// ── Helpers ────────────────────────────────────────────────────────────────

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtTime(t: string | null | undefined) {
  if (!t) return null
  const [h, m] = t.split(':')
  const d = new Date()
  d.setHours(Number(h), Number(m))
  return d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true })
}

// ── Booking Card ──────────────────────────────────────────────────────────

function BookingCard({ booking }: { booking: MerchantBooking }) {
  const queryClient = useQueryClient()
  const cfg = STATUS_CONFIG[booking.status]

  const [rejectNotes, setRejectNotes] = useState('')
  const [showRejectInput, setShowRejectInput] = useState(false)

  const confirmMutation = useMutation({
    mutationFn: () => merchantBookingsApi.confirm(booking.id),
    onSuccess: () => {
      toast.success('Booking confirmed')
      queryClient.invalidateQueries({ queryKey: ['merchant-bookings'] })
    },
    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Something went wrong'),
  })

  const rejectMutation = useMutation({
    mutationFn: () => merchantBookingsApi.reject(booking.id, rejectNotes || undefined),
    onSuccess: () => {
      toast.success('Booking rejected')
      setShowRejectInput(false)
      queryClient.invalidateQueries({ queryKey: ['merchant-bookings'] })
    },
    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Something went wrong'),
  })

  const completeMutation = useMutation({
    mutationFn: () => merchantBookingsApi.complete(booking.id),
    onSuccess: () => {
      toast.success('Booking marked as completed')
      queryClient.invalidateQueries({ queryKey: ['merchant-bookings'] })
    },
    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Something went wrong'),
  })

  const isPending = confirmMutation.isPending || rejectMutation.isPending || completeMutation.isPending

  return (
    <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
      {/* Header: status + store */}
      <div className="flex items-start justify-between gap-2">
        <div>
          <p className="text-xs text-slate-400 mb-0.5">{booking.store_name}</p>
          <div className="flex items-center gap-2">
            <Calendar size={13} className="text-slate-400" />
            <span className="text-sm font-semibold text-slate-800">{fmtDate(booking.booking_date)}</span>
            {booking.booking_time && (
              <span className="text-sm text-slate-500">{fmtTime(booking.booking_time)}</span>
            )}
          </div>
        </div>
        <span className={`inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full flex-shrink-0 ${cfg.color}`}>
          <cfg.Icon size={11} /> {cfg.label}
        </span>
      </div>

      {/* Customer info */}
      <div className="flex items-center gap-4 text-sm text-slate-600">
        <span className="flex items-center gap-1.5">
          <User size={13} className="text-slate-400" />
          {booking.customer_name}
        </span>
        {booking.customer_phone && (
          <a
            href={`tel:${booking.customer_phone}`}
            className="flex items-center gap-1.5 text-brand-600 hover:underline"
          >
            <Phone size={13} /> {booking.customer_phone}
          </a>
        )}
      </div>

      {/* Details */}
      {(booking.num_attendees || booking.customer_notes) && (
        <div className="bg-slate-50 rounded-xl px-3 py-2 text-xs text-slate-500 space-y-1">
          {booking.num_attendees && booking.num_attendees > 1 && (
            <p>{booking.num_attendees} attendees</p>
          )}
          {booking.customer_notes && <p className="italic">"{booking.customer_notes}"</p>}
        </div>
      )}

      {/* Merchant notes (if rejected) */}
      {booking.merchant_notes && (
        <div className="flex items-start gap-1.5 text-xs text-amber-700">
          <AlertTriangle size={12} className="mt-0.5 flex-shrink-0" />
          <span>{booking.merchant_notes}</span>
        </div>
      )}

      {/* Reject note input */}
      {showRejectInput && (
        <div className="space-y-2">
          <textarea
            value={rejectNotes}
            onChange={e => setRejectNotes(e.target.value)}
            placeholder="Reason for rejection (optional)"
            rows={2}
            className="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"
          />
          <div className="flex gap-2">
            <button
              onClick={() => rejectMutation.mutate()}
              disabled={rejectMutation.isPending}
              className="flex-1 py-2 text-xs font-semibold bg-red-600 hover:bg-red-700 text-white rounded-xl flex items-center justify-center gap-1 transition-colors disabled:opacity-50"
            >
              {rejectMutation.isPending ? <Loader2 size={12} className="animate-spin" /> : <X size={12} />}
              Confirm Reject
            </button>
            <button
              onClick={() => setShowRejectInput(false)}
              className="flex-1 py-2 text-xs font-semibold bg-slate-100 text-slate-600 rounded-xl"
            >
              Cancel
            </button>
          </div>
        </div>
      )}

      {/* Action buttons */}
      {!showRejectInput && (
        <div className="flex gap-2 pt-1">
          {booking.status === 'pending' && (
            <>
              <button
                onClick={() => confirmMutation.mutate()}
                disabled={isPending}
                className="flex-1 py-2 text-xs font-semibold bg-green-600 hover:bg-green-700 text-white rounded-xl flex items-center justify-center gap-1 transition-colors disabled:opacity-50"
              >
                {confirmMutation.isPending ? <Loader2 size={12} className="animate-spin" /> : <Check size={12} />}
                Confirm
              </button>
              <button
                onClick={() => setShowRejectInput(true)}
                disabled={isPending}
                className="flex-1 py-2 text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-600 rounded-xl flex items-center justify-center gap-1 transition-colors disabled:opacity-50"
              >
                <X size={12} /> Reject
              </button>
            </>
          )}
          {booking.status === 'confirmed' && (
            <>
              <button
                onClick={() => completeMutation.mutate()}
                disabled={isPending}
                className="flex-1 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-xl flex items-center justify-center gap-1 transition-colors disabled:opacity-50"
              >
                {completeMutation.isPending ? <Loader2 size={12} className="animate-spin" /> : <CalendarCheck2 size={12} />}
                Complete
              </button>
              <button
                onClick={() => setShowRejectInput(true)}
                disabled={isPending}
                className="flex-1 py-2 text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-600 rounded-xl flex items-center justify-center gap-1 transition-colors disabled:opacity-50"
              >
                <X size={12} /> Reject
              </button>
            </>
          )}
        </div>
      )}
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────

export default function BookingListPage() {
  const [statusFilter, setStatusFilter] = useState<FilterStatus>('all')

  const filters: BookingFilters = {}
  if (statusFilter !== 'all') filters.status = statusFilter as BookingStatus

  const { data, isLoading, isError } = useQuery({
    queryKey: ['merchant-bookings', filters],
    queryFn: () => merchantBookingsApi.list(filters),
    staleTime: 30_000,
  })

  const items: MerchantBooking[] = (data?.data as any) ?? []

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6">
      {/* Header */}
      <div className="mb-5">
        <h1 className="font-heading font-bold text-2xl text-gray-800">Bookings</h1>
        <p className="text-sm text-gray-500 mt-0.5">Manage customer visit bookings</p>
      </div>

      {/* Status filter tabs */}
      <div className="flex items-center gap-2 flex-wrap mb-5">
        {STATUS_TABS.map(t => (
          <button
            key={t.id}
            onClick={() => setStatusFilter(t.id)}
            className={`px-4 py-1.5 rounded-xl text-sm font-medium transition-all ${
              statusFilter === t.id
                ? 'bg-brand-600 text-white'
                : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="flex justify-center py-16">
          <Loader2 size={28} className="animate-spin text-brand-500" />
        </div>
      )}

      {/* Error */}
      {isError && (
        <div className="text-center py-10 text-gray-500">
          <p>Could not load bookings. Please try again.</p>
        </div>
      )}

      {/* Empty */}
      {!isLoading && !isError && items.length === 0 && (
        <div className="text-center py-20 text-gray-400">
          <CalendarCheck size={48} className="mx-auto mb-4 opacity-30" />
          <p className="font-medium text-gray-500">No bookings found</p>
          {statusFilter !== 'all' && (
            <button
              onClick={() => setStatusFilter('all')}
              className="text-sm text-brand-600 hover:underline mt-2"
            >
              View all bookings
            </button>
          )}
        </div>
      )}

      {/* Grid */}
      {!isLoading && items.length > 0 && (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
          {items.map(b => (
            <BookingCard key={b.id} booking={b} />
          ))}
        </div>
      )}
    </div>
  )
}
