import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query'
import { ChevronLeft, Plus, Ticket, Gift, CheckCircle, Clock, XCircle } from 'lucide-react'
import toast from 'react-hot-toast'
import {
  storeCouponApi,
  type StoreCoupon,
  type StoreCouponStatus,
} from '@/api/endpoints/storeCoupons'

const TABS: Array<{ key: StoreCouponStatus | 'all'; label: string }> = [
  { key: 'all',      label: 'All' },
  { key: 'active',   label: 'Active' },
  { key: 'inactive', label: 'Inactive' },
  { key: 'expired',  label: 'Expired' },
]

const STATUS_ICON: Record<string, React.ReactNode> = {
  active:   <CheckCircle size={10} className="text-emerald-500" />,
  inactive: <XCircle size={10} className="text-gray-400" />,
  expired:  <Clock size={10} className="text-red-400" />,
}

const STATUS_BG: Record<string, string> = {
  active:   'bg-emerald-50 text-emerald-700',
  inactive: 'bg-gray-100 text-gray-500',
  expired:  'bg-red-50 text-red-600',
}

function CouponCard({ coupon }: { coupon: StoreCoupon }) {
  const navigate = useNavigate()
  const qc = useQueryClient()

  const deleteMutation = useMutation({
    mutationFn: () => storeCouponApi.remove(coupon.id),
    onSuccess: () => { toast.success('Deleted'); qc.invalidateQueries({ queryKey: ['store-coupons'] }) },
    onError: () => toast.error('Failed to delete'),
  })

  const discountLabel = coupon.discount_type === 'percentage'
    ? `${parseFloat(coupon.discount_value).toFixed(0)}% OFF`
    : `₹${parseFloat(coupon.discount_value).toFixed(0)} OFF`

  return (
    <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div className="bg-gradient-to-r from-violet-500 to-purple-600 px-4 py-3 flex items-center justify-between">
        <span className="text-white font-bold text-lg">{discountLabel}</span>
        <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${STATUS_BG[coupon.status]}`}>
          {coupon.status.toUpperCase()}
        </span>
      </div>
      <div className="px-4 py-3">
        <p className="font-semibold text-gray-800 text-sm">{coupon.title}</p>
        {coupon.description && <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{coupon.description}</p>}

        <div className="flex items-center gap-3 mt-2 text-xs text-gray-400">
          {coupon.store_name && (
            <span className="flex items-center gap-1"><Ticket size={10} />{coupon.store_name}</span>
          )}
          {coupon.is_gifted ? (
            <span className="flex items-center gap-1 text-purple-500">
              <Gift size={10} />Gifted to {coupon.gifted_to_customer_name ?? 'customer'}
            </span>
          ) : null}
          {coupon.is_redeemed ? (
            <span className="flex items-center gap-1 text-emerald-500">
              <CheckCircle size={10} />Redeemed
            </span>
          ) : null}
        </div>

        <div className="flex gap-2 mt-3">
          <button
            onClick={() => navigate(`/store-coupons/${coupon.id}`)}
            className="flex-1 py-2 border border-gray-200 rounded-xl text-xs font-medium text-gray-600 hover:bg-gray-50 text-center"
          >
            View
          </button>
          {coupon.status === 'active' && !coupon.is_gifted && (
            <button
              onClick={() => navigate(`/store-coupons/${coupon.id}/assign`)}
              className="flex-1 py-2 bg-purple-50 border border-purple-200 rounded-xl text-xs font-medium text-purple-600 hover:bg-purple-100 text-center flex items-center justify-center gap-1"
            >
              <Gift size={12} /> Assign
            </button>
          )}
          <button
            onClick={() => window.confirm('Delete this store coupon?') && deleteMutation.mutate()}
            disabled={deleteMutation.isPending}
            className="py-2 px-3 border border-red-100 rounded-xl text-xs font-medium text-red-500 hover:bg-red-50"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  )
}

export default function StoreCouponListPage() {
  const navigate = useNavigate()
  const [tab, setTab]   = useState<StoreCouponStatus | 'all'>('all')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['store-coupons', tab, page],
    queryFn:  () => storeCouponApi.list({
      status: tab === 'all' ? undefined : tab,
      page,
      limit: 20,
    }).then(r => ({ items: r.data.data, meta: r.data.meta })),
    placeholderData: keepPreviousData,
  })

  const coupons = data?.items ?? []
  const meta    = data?.meta  ?? { total: 0, page: 1, limit: 20, pages: 1 }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-xl">Store Coupons</h1>
            <p className="text-white/70 text-xs mt-0.5">{meta.total} coupon{meta.total !== 1 ? 's' : ''}</p>
          </div>
          <button onClick={() => navigate('/store-coupons/new')} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30">
            <Plus size={20} className="text-white" />
          </button>
        </div>

        {/* Tabs */}
        <div className="flex gap-2 mt-4 overflow-x-auto no-scrollbar">
          {TABS.map(t => (
            <button
              key={t.key}
              onClick={() => { setTab(t.key); setPage(1) }}
              className={`px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-colors ${
                tab === t.key
                  ? 'bg-white text-brand-700'
                  : 'bg-white/20 text-white/80 hover:bg-white/30'
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>
      </div>

      <div className="px-4 py-4 space-y-3">
        {isLoading ? (
          Array.from({ length: 3 }).map((_, i) => <div key={i} className="bg-white rounded-2xl h-28 animate-pulse" />)
        ) : coupons.length === 0 ? (
          <div className="py-20 text-center">
            <Ticket size={40} className="text-gray-300 mx-auto mb-3" />
            <p className="text-gray-500 font-medium">No store coupons yet</p>
            <p className="text-gray-400 text-sm mt-1">Create store-specific coupons to gift to customers</p>
            <button onClick={() => navigate('/store-coupons/new')} className="btn-primary mt-4 inline-flex items-center gap-2">
              <Plus size={14} /> Create Store Coupon
            </button>
          </div>
        ) : (
          <>
            {coupons.map(c => <CouponCard key={c.id} coupon={c} />)}

            {meta.pages > 1 && (
              <div className="flex items-center justify-between py-3 bg-white rounded-2xl px-4 shadow-sm">
                <button
                  onClick={() => setPage(p => Math.max(1, p - 1))}
                  disabled={page <= 1}
                  className="text-sm text-brand-600 font-medium disabled:text-gray-300"
                >← Prev</button>
                <span className="text-xs text-gray-500">Page {meta.page} of {meta.pages}</span>
                <button
                  onClick={() => setPage(p => Math.min(meta.pages, p + 1))}
                  disabled={page >= meta.pages}
                  className="text-sm text-brand-600 font-medium disabled:text-gray-300"
                >Next →</button>
              </div>
            )}
          </>
        )}
        <div className="h-4" />
      </div>
    </div>
  )
}
