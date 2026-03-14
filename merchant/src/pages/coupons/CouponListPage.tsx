import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Plus, Ticket, Copy, ChevronRight, Store } from 'lucide-react'
import toast from 'react-hot-toast'
import { couponApi, type Coupon, type CouponTab } from '@/api/endpoints/coupons'
import { useAuthStore } from '@/store/authStore'

const TABS: { key: CouponTab; label: string }[] = [
  { key: 'all',     label: 'All' },
  { key: 'active',  label: 'Active' },
  { key: 'pending', label: 'Pending' },
  { key: 'expired', label: 'Expired' },
]

const APPROVAL_COLORS: Record<string, string> = {
  approved: 'bg-green-100 text-green-700',
  pending:  'bg-amber-100 text-amber-700',
  rejected: 'bg-red-100   text-red-700',
}

const STATUS_COLORS: Record<string, string> = {
  active:   'bg-emerald-100 text-emerald-700',
  inactive: 'bg-gray-100   text-gray-500',
  expired:  'bg-red-100    text-red-600',
}

function CouponCard({ coupon }: { coupon: Coupon }) {
  const navigate = useNavigate()

  const copyCode = (e: React.MouseEvent) => {
    e.stopPropagation()
    navigator.clipboard.writeText(coupon.coupon_code)
    toast.success('Code copied!')
  }

  const discountLabel =
    coupon.discount_type === 'percentage'
      ? `${coupon.discount_value}% OFF`
      : `₹${parseFloat(coupon.discount_value).toFixed(0)} OFF`

  return (
    <button
      onClick={() => navigate(`/coupons/${coupon.id}`)}
      className="w-full bg-white rounded-2xl shadow-sm overflow-hidden text-left hover:shadow-md transition-shadow active:scale-[0.99]"
    >
      {/* Top stripe */}
      <div className="bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-3 flex items-center justify-between">
        <span className="text-white font-bold text-lg">{discountLabel}</span>
        <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${APPROVAL_COLORS[coupon.approval_status]}`}>
          {coupon.approval_status.toUpperCase()}
        </span>
      </div>

      <div className="px-4 py-3">
        <div className="flex items-start justify-between gap-2">
          <div className="min-w-0 flex-1">
            <p className="text-sm font-bold text-gray-900 truncate">{coupon.title}</p>
            {coupon.store_name && (
              <p className="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                <Store size={10} />{coupon.store_name}
              </p>
            )}
          </div>
          <ChevronRight size={16} className="text-gray-300 mt-0.5 shrink-0" />
        </div>

        {/* Code chip */}
        <button
          onClick={copyCode}
          className="mt-2 flex items-center gap-1.5 bg-gray-50 rounded-lg px-2.5 py-1.5 hover:bg-gray-100 transition-colors"
        >
          <Ticket size={12} className="text-brand-500" />
          <span className="font-mono text-xs font-bold text-brand-700 tracking-widest">
            {coupon.coupon_code}
          </span>
          <Copy size={11} className="text-gray-400" />
        </button>

        {/* Meta row */}
        <div className="mt-2 flex items-center justify-between">
          <div className="flex gap-1.5">
            <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-md ${STATUS_COLORS[coupon.status]}`}>
              {coupon.status}
            </span>
          </div>
          <p className="text-[10px] text-gray-400">
            {coupon.redemption_count} redemptions
            {coupon.usage_limit ? ` / ${coupon.usage_limit}` : ''}
          </p>
        </div>

        {coupon.valid_until && (
          <p className="mt-1 text-[10px] text-gray-400">
            Expires {new Date(coupon.valid_until).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
          </p>
        )}
      </div>
    </button>
  )
}

export default function CouponListPage() {
  const navigate = useNavigate()
  const [tab, setTab]   = useState<CouponTab>('all')
  const isStoreAdmin  = useAuthStore(s => s.isStoreAdmin())
  const scopedStoreId = useAuthStore(s => s.scopedStoreId())

  const { data, isLoading } = useQuery({
    queryKey: ['coupons', tab, scopedStoreId],
    queryFn: async () => {
      const params: Parameters<typeof couponApi.list>[0] = { tab, limit: 50 }
      if (isStoreAdmin && scopedStoreId) params.store_id = scopedStoreId
      const res = await couponApi.list(params)
      return res.data
    },
  })

  const coupons = data?.data ?? []

  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <h1 className="text-white font-bold text-xl">My Coupons</h1>
        <p className="text-white/70 text-sm mt-0.5">{data?.meta?.total ?? 0} total</p>
      </div>

      {/* Tabs */}
      <div className="bg-white border-b border-gray-100 px-4">
        <div className="flex gap-1 overflow-x-auto">
          {TABS.map((t) => (
            <button
              key={t.key}
              onClick={() => setTab(t.key)}
              className={`shrink-0 px-4 py-3 text-sm font-semibold border-b-2 transition-colors ${
                tab === t.key
                  ? 'border-brand-600 text-brand-600'
                  : 'border-transparent text-gray-400'
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>
      </div>

      {/* List */}
      <div className="px-4 py-4 space-y-3">
        {isLoading ? (
          Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-32 bg-white rounded-2xl animate-pulse" />
          ))
        ) : coupons.length === 0 ? (
          <div className="flex flex-col items-center py-16 text-center">
            <Ticket size={48} className="text-gray-200 mb-3" />
            <p className="text-gray-500 font-medium">No coupons here</p>
            <p className="text-gray-400 text-sm mt-1">Tap + to create your first coupon</p>
          </div>
        ) : (
          coupons.map((c) => <CouponCard key={c.id} coupon={c} />)
        )}
      </div>

      {/* FAB */}
      <button
        onClick={() => navigate('/coupons/new')}
        className="fixed bottom-24 right-5 w-14 h-14 bg-brand-600 rounded-full shadow-lg flex items-center justify-center hover:bg-brand-700 active:scale-95 transition-all"
      >
        <Plus size={24} className="text-white" />
      </button>
    </div>
  )
}
