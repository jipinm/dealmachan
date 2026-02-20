import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { QRCodeSVG } from 'qrcode.react'
import { ChevronLeft, Pencil, Trash2, Users, Ticket } from 'lucide-react'
import toast from 'react-hot-toast'
import { couponApi, type CouponRedemption } from '@/api/endpoints/coupons'

const APPROVAL_COLORS: Record<string, string> = {
  approved: 'bg-green-100 text-green-700',
  pending:  'bg-amber-100 text-amber-700',
  rejected: 'bg-red-100 text-red-600',
}
const STATUS_COLORS: Record<string, string> = {
  active:   'bg-emerald-100 text-emerald-700',
  inactive: 'bg-gray-100 text-gray-500',
  expired:  'bg-red-100 text-red-600',
}

function Pill({ label, color }: { label: string; color: string }) {
  return (
    <span className={`text-[11px] font-semibold px-2.5 py-0.5 rounded-full ${color}`}>
      {label}
    </span>
  )
}

function Row({ label, value }: { label: string; value: string | null }) {
  if (!value) return null
  return (
    <div className="flex justify-between py-2 border-b border-gray-50 last:border-0">
      <span className="text-xs text-gray-400">{label}</span>
      <span className="text-xs font-semibold text-gray-700 text-right max-w-[60%]">{value}</span>
    </div>
  )
}

function RedemptionRow({ r }: { r: CouponRedemption }) {
  return (
    <div className="flex items-center justify-between py-2.5 border-b border-gray-50 last:border-0">
      <div>
        <p className="text-sm font-semibold text-gray-800">{r.customer_name}</p>
        <p className="text-xs text-gray-400">
          {new Date(r.redeemed_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
          {r.store_name ? ` · ${r.store_name}` : ''}
        </p>
      </div>
      <span className="text-sm font-bold text-emerald-600">₹{parseFloat(r.discount_amount).toFixed(2)}</span>
    </div>
  )
}

export default function CouponDetailPage() {
  const navigate    = useNavigate()
  const { id }      = useParams<{ id: string }>()
  const qc          = useQueryClient()
  const [showConfirmDelete, setShowConfirmDelete] = useState(false)
  const [redPage, setRedPage] = useState(1)

  const { data: couponData, isLoading } = useQuery({
    queryKey: ['coupon', id],
    queryFn: async () => (await couponApi.get(Number(id))).data.data,
    enabled: Boolean(id),
  })

  const { data: redData } = useQuery({
    queryKey: ['coupon-redemptions', id, redPage],
    queryFn: async () => (await couponApi.getRedemptions(Number(id), { page: redPage, limit: 20 })).data,
    enabled: Boolean(id),
  })

  const deleteMutation = useMutation({
    mutationFn: () => couponApi.remove(Number(id)),
    onSuccess: () => {
      toast.success('Coupon deactivated')
      qc.invalidateQueries({ queryKey: ['coupons'] })
      navigate('/coupons', { replace: true })
    },
    onError: () => toast.error('Failed to deactivate'),
  })

  if (isLoading) {
    return (
      <div className="min-h-full bg-gray-50">
        <div className="gradient-brand h-32 animate-pulse" />
        <div className="px-4 mt-4 space-y-3">
          {Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="h-16 bg-white rounded-2xl animate-pulse" />
          ))}
        </div>
      </div>
    )
  }

  const c = couponData
  if (!c) return null

  const discountLabel =
    c.discount_type === 'percentage'
      ? `${c.discount_value}% OFF`
      : `₹${parseFloat(c.discount_value).toFixed(0)} OFF`

  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-xl leading-tight truncate">{c.title}</h1>
            <p className="text-white/70 text-sm">{c.store_name ?? 'All stores'}</p>
          </div>
          <button
            onClick={() => navigate(`/coupons/${id}/edit`)}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center text-white hover:bg-white/30"
          >
            <Pencil size={16} />
          </button>
        </div>
      </div>

      <div className="px-4 -mt-2 space-y-3 pb-8">
        {/* QR + code */}
        <div className="bg-white rounded-2xl p-5 shadow-sm flex flex-col items-center gap-3">
          <div className="p-3 bg-gray-50 rounded-2xl">
            <QRCodeSVG value={c.coupon_code} size={140} />
          </div>
          <div className="text-center">
            <p className="font-mono font-bold text-2xl text-brand-700 tracking-widest">{c.coupon_code}</p>
            <p className="text-3xl font-extrabold text-gray-900 mt-1">{discountLabel}</p>
          </div>
          <div className="flex gap-2">
            <Pill label={c.approval_status} color={APPROVAL_COLORS[c.approval_status]} />
            <Pill label={c.status} color={STATUS_COLORS[c.status]} />
          </div>
          {c.approval_status === 'pending' && (
            <p className="text-xs text-amber-600 bg-amber-50 px-3 py-2 rounded-xl text-center">
              This coupon is awaiting admin approval before it can be used.
            </p>
          )}
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 gap-3">
          <div className="bg-white rounded-2xl p-4 shadow-sm text-center">
            <div className="w-9 h-9 bg-brand-100 rounded-xl flex items-center justify-center mx-auto mb-2">
              <Ticket size={18} className="text-brand-600" />
            </div>
            <p className="text-2xl font-extrabold text-gray-900">{c.usage_count}</p>
            <p className="text-xs text-gray-400">Total Redeemed</p>
          </div>
          <div className="bg-white rounded-2xl p-4 shadow-sm text-center">
            <div className="w-9 h-9 bg-indigo-100 rounded-xl flex items-center justify-center mx-auto mb-2">
              <Users size={18} className="text-indigo-500" />
            </div>
            <p className="text-2xl font-extrabold text-gray-900">
              {c.usage_limit ?? '∞'}
            </p>
            <p className="text-xs text-gray-400">Usage Limit</p>
          </div>
        </div>

        {/* Details */}
        <div className="bg-white rounded-2xl p-4 shadow-sm">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Details</p>
          <Row label="Type" value={c.discount_type === 'percentage' ? 'Percentage' : 'Fixed Amount'} />
          <Row label="Value" value={discountLabel} />
          <Row label="Min Purchase" value={c.min_purchase_amount ? `₹${c.min_purchase_amount}` : null} />
          <Row label="Max Discount" value={c.max_discount_amount ? `₹${c.max_discount_amount}` : null} />
          <Row label="Valid From" value={c.valid_from ? new Date(c.valid_from).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : null} />
          <Row label="Valid Until" value={c.valid_until ? new Date(c.valid_until).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : null} />
          {c.description && <Row label="Description" value={c.description} />}
          {c.terms_conditions && <Row label="Terms" value={c.terms_conditions} />}
        </div>

        {/* Redemption history */}
        <div className="bg-white rounded-2xl p-4 shadow-sm">
          <div className="flex items-center justify-between mb-2">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-widest">Redemption History</p>
            {redData?.meta && (
              <p className="text-xs text-gray-400">{redData.meta.total} total</p>
            )}
          </div>
          {(redData?.data ?? []).length === 0 ? (
            <p className="text-sm text-gray-400 py-4 text-center">No redemptions yet</p>
          ) : (
            <>
              {(redData?.data ?? []).map((r) => <RedemptionRow key={r.id} r={r} />)}
              {redData?.meta && redPage < Math.ceil(redData.meta.total / 20) && (
                <button
                  onClick={() => setRedPage((p) => p + 1)}
                  className="w-full mt-2 py-2 text-sm text-brand-600 font-semibold"
                >
                  Load more
                </button>
              )}
            </>
          )}
        </div>

        {/* Danger zone */}
        {!showConfirmDelete ? (
          <button
            onClick={() => setShowConfirmDelete(true)}
            className="w-full flex items-center justify-center gap-2 bg-white rounded-2xl shadow-sm py-4 text-sm font-semibold text-rose-500 hover:bg-rose-50 transition-colors"
          >
            <Trash2 size={16} /> Deactivate Coupon
          </button>
        ) : (
          <div className="bg-white rounded-2xl shadow-sm p-4 border border-rose-200 space-y-3">
            <p className="text-sm font-bold text-rose-600">Deactivate this coupon?</p>
            <p className="text-xs text-gray-500">The coupon will be disabled but history is preserved.</p>
            <div className="flex gap-2">
              <button
                onClick={() => setShowConfirmDelete(false)}
                className="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600"
              >
                Cancel
              </button>
              <button
                onClick={() => deleteMutation.mutate()}
                disabled={deleteMutation.isPending}
                className="flex-1 py-2.5 rounded-xl bg-rose-500 text-white text-sm font-semibold disabled:opacity-50"
              >
                {deleteMutation.isPending ? 'Deactivating…' : 'Deactivate'}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
