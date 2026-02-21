import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  ChevronLeft, Edit2, Trash2, Gift, CheckCircle, Clock, Ticket,
} from 'lucide-react'
import toast from 'react-hot-toast'
import { storeCouponApi } from '@/api/endpoints/storeCoupons'

const STATUS_STYLE: Record<string, string> = {
  active:   'bg-emerald-50 text-emerald-700',
  inactive: 'bg-gray-100 text-gray-500',
  expired:  'bg-red-50 text-red-600',
}

export default function StoreCouponDetailPage() {
  const { id }   = useParams<{ id: string }>()
  const navigate = useNavigate()
  const qc       = useQueryClient()

  const { data: coupon, isLoading } = useQuery({
    queryKey: ['store-coupon', id],
    queryFn:  () => storeCouponApi.get(Number(id)).then(r => r.data.data),
    enabled:  !!id,
  })

  const deleteMutation = useMutation({
    mutationFn: () => storeCouponApi.remove(Number(id)),
    onSuccess: () => {
      toast.success('Store coupon deleted')
      qc.invalidateQueries({ queryKey: ['store-coupons'] })
      navigate('/store-coupons', { replace: true })
    },
    onError: () => toast.error('Failed to delete'),
  })

  if (isLoading || !coupon) {
    return (
      <div className="min-h-full bg-gray-50">
        <div className="gradient-brand px-5 pt-14 pb-6 flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <h1 className="text-white font-bold text-lg flex-1">Store Coupon</h1>
        </div>
        <div className="px-4 py-8 text-center text-gray-400 text-sm">Loading…</div>
      </div>
    )
  }

  const discountLabel = coupon.discount_type === 'percentage'
    ? `${parseFloat(coupon.discount_value).toFixed(0)}%`
    : `₹${parseFloat(coupon.discount_value).toFixed(0)}`

  const formatDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'

  return (
    <div className="min-h-full bg-gray-50 pb-10">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-lg">{coupon.title}</h1>
            <span className={`mt-1 inline-block text-[10px] font-bold px-2 py-0.5 rounded-full ${STATUS_STYLE[coupon.status]}`}>
              {coupon.status.toUpperCase()}
            </span>
          </div>
        </div>
      </div>

      <div className="px-4 -mt-2 space-y-4">
        {/* Discount banner */}
        <div className="bg-gradient-to-r from-violet-500 to-purple-600 rounded-2xl p-6 text-center shadow-sm">
          <p className="text-4xl font-extrabold text-white">{discountLabel} OFF</p>
          <p className="text-white/80 text-sm mt-1">{coupon.discount_type === 'percentage' ? 'Percentage discount' : 'Fixed amount discount'}</p>
        </div>

        {/* Info card */}
        <div className="bg-white rounded-2xl shadow-sm p-4 space-y-3">
          {coupon.description && (
            <div>
              <p className="text-[10px] font-bold text-gray-400 uppercase">Description</p>
              <p className="text-sm text-gray-700 mt-0.5">{coupon.description}</p>
            </div>
          )}
          {coupon.terms_conditions && (
            <div>
              <p className="text-[10px] font-bold text-gray-400 uppercase">Terms & Conditions</p>
              <p className="text-sm text-gray-700 mt-0.5">{coupon.terms_conditions}</p>
            </div>
          )}

          <div className="grid grid-cols-2 gap-3 pt-1">
            <div>
              <p className="text-[10px] font-bold text-gray-400 uppercase">Store</p>
              <p className="text-sm text-gray-700 mt-0.5 flex items-center gap-1">
                <Ticket size={12} />{coupon.store_name ?? 'All Stores'}
              </p>
            </div>
            <div>
              <p className="text-[10px] font-bold text-gray-400 uppercase">Created</p>
              <p className="text-sm text-gray-700 mt-0.5">{formatDate(coupon.created_at)}</p>
            </div>
            <div>
              <p className="text-[10px] font-bold text-gray-400 uppercase">Valid From</p>
              <p className="text-sm text-gray-700 mt-0.5">{formatDate(coupon.valid_from)}</p>
            </div>
            <div>
              <p className="text-[10px] font-bold text-gray-400 uppercase">Valid Until</p>
              <p className="text-sm text-gray-700 mt-0.5">{formatDate(coupon.valid_until)}</p>
            </div>
          </div>

          {coupon.min_purchase_amount && (
            <div>
              <p className="text-[10px] font-bold text-gray-400 uppercase">Min Purchase</p>
              <p className="text-sm text-gray-700 mt-0.5">₹{parseFloat(coupon.min_purchase_amount).toFixed(2)}</p>
            </div>
          )}
          {coupon.max_discount_amount && (
            <div>
              <p className="text-[10px] font-bold text-gray-400 uppercase">Max Discount</p>
              <p className="text-sm text-gray-700 mt-0.5">₹{parseFloat(coupon.max_discount_amount).toFixed(2)}</p>
            </div>
          )}
        </div>

        {/* Assignment info */}
        <div className="bg-white rounded-2xl shadow-sm p-4 space-y-2">
          <p className="text-[10px] font-bold text-gray-400 uppercase">Assignment Status</p>
          {coupon.is_gifted ? (
            <div className="flex items-center gap-2">
              <Gift size={16} className="text-purple-500" />
              <div>
                <p className="text-sm font-semibold text-gray-800">Gifted to {coupon.gifted_to_customer_name ?? 'Customer'}</p>
                <p className="text-xs text-gray-400">{formatDate(coupon.gifted_at)}</p>
              </div>
            </div>
          ) : (
            <p className="text-sm text-gray-500">Not yet assigned to a customer</p>
          )}
          {coupon.is_redeemed ? (
            <div className="flex items-center gap-2 mt-1">
              <CheckCircle size={16} className="text-emerald-500" />
              <div>
                <p className="text-sm font-semibold text-emerald-700">Redeemed</p>
                <p className="text-xs text-gray-400">{formatDate(coupon.redeemed_at)}</p>
              </div>
            </div>
          ) : (
            <div className="flex items-center gap-2 mt-1">
              <Clock size={16} className="text-amber-500" />
              <p className="text-sm text-amber-700">Not redeemed yet</p>
            </div>
          )}
        </div>

        {/* Actions */}
        <div className="flex gap-3">
          {coupon.status === 'active' && !coupon.is_gifted && (
            <button
              onClick={() => navigate(`/store-coupons/${coupon.id}/assign`)}
              className="flex-1 flex items-center justify-center gap-2 py-3 bg-purple-600 text-white font-bold rounded-2xl hover:bg-purple-700"
            >
              <Gift size={16} /> Assign to Customer
            </button>
          )}
          <button
            onClick={() => navigate(`/store-coupons/${coupon.id}/edit`)}
            className="flex-1 flex items-center justify-center gap-2 py-3 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-600 hover:bg-gray-50"
          >
            <Edit2 size={16} /> Edit
          </button>
          <button
            onClick={() => window.confirm('Delete this store coupon?') && deleteMutation.mutate()}
            disabled={deleteMutation.isPending}
            className="py-3 px-4 border border-red-200 rounded-2xl text-sm font-semibold text-red-500 hover:bg-red-50 disabled:opacity-50"
          >
            <Trash2 size={16} />
          </button>
        </div>
      </div>
    </div>
  )
}
