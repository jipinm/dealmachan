import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  CalendarCheck, CalendarX, Clock, CheckCircle, XCircle,
  AlertTriangle, Ban, Store, Loader2, Calendar,
} from 'lucide-react'
import { bookingsApi, type Booking, type BookingStatus } from '@/api/endpoints/bookings'
import { getApiError } from '@/api/client'
import { getImageUrl } from '@/lib/imageUrl'
import toast from 'react-hot-toast'

// ── Status config ─────────────────────────────────────────────────────────

const STATUS_CONFIG: Record<BookingStatus, { label: string; color: string; Icon: React.ElementType }> = {
  pending:   { label: 'Pending',   color: 'bg-yellow-100 text-yellow-700', Icon: Clock       },
  confirmed: { label: 'Confirmed', color: 'bg-green-100  text-green-700',  Icon: CheckCircle },
  rejected:  { label: 'Rejected',  color: 'bg-red-100    text-red-700',    Icon: XCircle     },
  cancelled: { label: 'Cancelled', color: 'bg-slate-100  text-slate-500',  Icon: Ban         },
  completed: { label: 'Completed', color: 'bg-blue-100   text-blue-700',   Icon: CalendarCheck },
}

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

function BookingCard({ booking }: { booking: Booking }) {
  const queryClient = useQueryClient()
  const cfg = STATUS_CONFIG[booking.status]

  const cancel = useMutation({
    mutationFn: () => bookingsApi.cancel(booking.id),
    onSuccess: () => {
      toast.success('Booking cancelled')
      queryClient.invalidateQueries({ queryKey: ['bookings'] })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const canCancel = booking.status === 'pending' || booking.status === 'confirmed'

  return (
    <div className="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
      {/* Store info */}
      <div className="flex items-start gap-3 mb-3">
        {booking.store_image ? (
          <img
            src={getImageUrl(booking.store_image)}
            alt={booking.store_name}
            className="w-10 h-10 rounded-xl object-cover bg-slate-100 flex-shrink-0"
          />
        ) : (
          <div className="w-10 h-10 rounded-xl gradient-brand flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
            <Store size={16} />
          </div>
        )}
        <div className="flex-1 min-w-0">
          <p className="font-semibold text-slate-800 text-sm truncate">{booking.store_name}</p>
          {(booking.area_name || booking.city_name) && (
            <p className="text-xs text-slate-500 mt-0.5 truncate">
              {[booking.area_name, booking.city_name].filter(Boolean).join(', ')}
            </p>
          )}
        </div>
        {/* Status badge */}
        <span className={`inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full flex-shrink-0 ${cfg.color}`}>
          <cfg.Icon size={11} /> {cfg.label}
        </span>
      </div>

      {/* Booking details */}
      <div className="bg-slate-50 rounded-xl px-3 py-2.5 text-xs text-slate-600 space-y-1 mb-3">
        <div className="flex items-center gap-2">
          <Calendar size={12} className="text-slate-400" />
          <span>{fmtDate(booking.booking_date)}</span>
          {booking.booking_time && <span className="text-slate-400">·</span>}
          {booking.booking_time && <span>{fmtTime(booking.booking_time)}</span>}
        </div>
        {booking.num_attendees && booking.num_attendees > 1 && (
          <div className="text-slate-500">{booking.num_attendees} people</div>
        )}
        {booking.customer_notes && (
          <div className="text-slate-500 italic">"{booking.customer_notes}"</div>
        )}
        {booking.merchant_notes && (
          <div className="flex items-start gap-1.5">
            <AlertTriangle size={11} className="text-amber-500 mt-0.5 flex-shrink-0" />
            <span className="text-amber-700">{booking.merchant_notes}</span>
          </div>
        )}
      </div>

      {/* Actions */}
      {canCancel && (
        <button
          onClick={() => cancel.mutate()}
          disabled={cancel.isPending}
          className="text-xs font-medium text-red-600 hover:text-red-700 flex items-center gap-1 disabled:opacity-50"
        >
          {cancel.isPending ? (
            <Loader2 size={12} className="animate-spin" />
          ) : (
            <CalendarX size={12} />
          )}
          Cancel Booking
        </button>
      )}
    </div>
  )
}

// ── Page ─────────────────────────────────────────────────────────────────

export default function BookingListPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['bookings'],
    queryFn: () => bookingsApi.list(),
    staleTime: 30_000,
  })

  const items: Booking[] = (data?.data as any) ?? []

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6">
      {/* Header */}
      <div className="mb-5">
        <h1 className="font-heading font-bold text-2xl text-slate-800">My Bookings</h1>
        <p className="text-sm text-slate-500 mt-0.5">Track your store visit bookings</p>
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="flex justify-center py-16">
          <Loader2 size={28} className="animate-spin text-brand-500" />
        </div>
      )}

      {/* Error */}
      {isError && (
        <div className="text-center py-10 text-slate-500">
          <p>Could not load bookings. Please try again.</p>
        </div>
      )}

      {/* Empty */}
      {!isLoading && !isError && items.length === 0 && (
        <div className="text-center py-20 text-slate-400">
          <CalendarCheck size={48} className="mx-auto mb-4 opacity-30" />
          <p className="font-medium text-slate-500">No bookings yet</p>
          <p className="text-sm mt-1">Find a store and book a visit</p>
          <Link
            to="/stores"
            className="btn-primary !py-2 !px-5 !rounded-xl !text-sm mt-5 inline-block"
          >
            Browse Stores
          </Link>
        </div>
      )}

      {/* List */}
      {!isLoading && items.length > 0 && (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {items.map(b => (
            <BookingCard key={b.id} booking={b} />
          ))}
        </div>
      )}
    </div>
  )
}
