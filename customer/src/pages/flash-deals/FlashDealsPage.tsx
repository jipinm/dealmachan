import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Zap, Clock, ChevronRight } from 'lucide-react'
import { publicApi, type FlashDiscount } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'

function timeLeft(until: string): string {
  const diff = new Date(until).getTime() - Date.now()
  if (diff <= 0) return 'Expired'
  const h = Math.floor(diff / 3_600_000)
  const m = Math.floor((diff % 3_600_000) / 60_000)
  if (h >= 24) return `${Math.floor(h / 24)}d ${h % 24}h left`
  return h > 0 ? `${h}h ${m}m left` : `${m}m left`
}

function imgSrc(path: string | null): string {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL?.replace('/api', '') ?? 'http://localhost:8000'}${path}`
}

function FlashDealCard({ deal }: { deal: FlashDiscount }) {
  return (
    <Link to={`/flash-deals/${deal.id}`} className="card card-hover group overflow-hidden block">
      <div className="relative h-44 bg-gradient-cta overflow-hidden flex items-center justify-center">
        {deal.merchant_logo ? (
          <img
            src={imgSrc(deal.merchant_logo)}
            alt={deal.merchant_name}
            className="w-20 h-20 object-contain rounded-2xl bg-white p-2 shadow"
          />
        ) : (
          <Zap size={48} className="text-white/60" />
        )}
        <div className="absolute top-3 right-3 w-16 h-16 rounded-full bg-white/20 backdrop-blur flex flex-col items-center justify-center">
          <span className="font-black text-2xl text-white leading-none">{deal.discount_percentage}%</span>
          <span className="text-white/70 text-xs">OFF</span>
        </div>
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 mb-1">{deal.title}</h3>
        <p className="text-xs text-slate-500 mb-3">{deal.merchant_name}</p>
        <div className="flex items-center gap-1.5 text-xs text-cta-500 font-bold">
          <Clock size={12} /> {timeLeft(deal.valid_until)}
        </div>
      </div>
    </Link>
  )
}

export default function FlashDealsPage() {
  const { cityId } = useLocationStore()

  const { data, isLoading } = useQuery({
    queryKey: ['flash-deals', cityId],
    queryFn: () => publicApi.getFlashDiscounts({ city_id: cityId ?? undefined }).then((r) => r.data.data ?? []),
    staleTime: 60 * 1000,
    refetchInterval: 60 * 1000, // refresh every minute for timer accuracy
  })

  return (
    <div>
      {/* Page header */}
      <div className="py-12" style={{ background: 'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)' }}>
        <div className="site-container text-center">
          <div className="inline-flex items-center gap-2 px-4 py-1.5 bg-white/20 rounded-full text-white text-sm font-medium mb-4">
            <Zap size={14} className="fill-white" /> Updated every minute
          </div>
          <h1 className="font-heading font-black text-4xl text-white mb-3">⚡ Flash Deals</h1>
          <p className="text-white/80 text-lg max-w-md mx-auto">
            Time-sensitive deals with massive discounts. Act fast before they expire!
          </p>
        </div>
      </div>

      <div className="site-container py-10">
        {isLoading ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {[1, 2, 3, 4, 5, 6].map((i) => <div key={i} className="h-64 skeleton rounded-2xl" />)}
          </div>
        ) : (data ?? []).length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {(data ?? []).map((deal) => <FlashDealCard key={deal.id} deal={deal} />)}
          </div>
        ) : (
          <div className="text-center py-24 text-slate-400">
            <Zap size={48} className="mx-auto mb-4 opacity-30" />
            <p className="font-semibold text-lg">No active flash deals</p>
            <p className="text-sm mt-1">Check back soon — new flash deals launch daily!</p>
            <Link to="/deals" className="mt-6 btn-primary !px-8 !py-3 !rounded-xl inline-flex items-center gap-2">
              Browse All Deals <ChevronRight size={16} />
            </Link>
          </div>
        )}
      </div>
    </div>
  )
}
