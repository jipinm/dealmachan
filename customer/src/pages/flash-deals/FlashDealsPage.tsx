import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Zap, Clock, ChevronRight } from 'lucide-react'
import { publicApi, type FlashDiscount } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'
import { slugify } from '@/lib/slugify'

function timeLeft(until: string): string {
  const diff = new Date(until).getTime() - Date.now()
  if (diff <= 0) return 'Expired'
  const h = Math.floor(diff / 3_600_000)
  const m = Math.floor((diff % 3_600_000) / 60_000)
  if (h >= 24) return `${Math.floor(h / 24)}d ${h % 24}h left`
  return h > 0 ? `${h}h ${m}m left` : `${m}m left`
}

import { getImageUrl } from '@/lib/imageUrl'
import { Helmet } from 'react-helmet-async'

function imgSrc(path: string | null): string {
  return getImageUrl(path)
}

function FlashDealCard({ deal }: { deal: FlashDiscount }) {
  return (
    <Link to={`/flash-deals/${deal.id}/${slugify(deal.title)}`} className="card card-hover group overflow-hidden block">
      {/* Banner image */}
      <div className="relative h-44 bg-slate-100 overflow-hidden">
        <img
          src={deal.banner_image ?? imgSrc(null)}
          alt={deal.title}
          loading="lazy"
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/400x200?text=Flash+Deal' }}
        />
        {/* Gradient overlay for readability */}
        <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent" />
        {/* Discount badge */}
        <div className="absolute top-3 right-3 w-14 h-14 rounded-full bg-cta-500/90 backdrop-blur flex flex-col items-center justify-center shadow-lg">
          <span className="font-black text-lg text-white leading-none">{deal.discount_percentage}%</span>
          <span className="text-white/80 text-[10px] font-semibold">OFF</span>
        </div>
        {/* Flash badge */}
        <span className="absolute top-3 left-3 inline-flex items-center gap-1 bg-black/40 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
          <Zap size={9} className="fill-yellow-300 text-yellow-300" /> FLASH
        </span>
      </div>
      <div className="p-4">
        <div className="flex items-center gap-2 mb-1.5">
          {deal.merchant_logo ? (
            <img
              src={imgSrc(deal.merchant_logo)}
              alt={deal.merchant_name}
              loading="lazy"
              className="w-5 h-5 rounded-full object-cover bg-slate-100 border border-slate-200"
              onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/40?text=M' }}
            />
          ) : (
            <Zap size={14} className="text-cta-500" />
          )}
          <span className="text-xs text-slate-500 truncate">{deal.merchant_name}</span>
        </div>
        <h3 className="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 mb-2">{deal.title}</h3>
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
      <Helmet>
        <title>Flash Deals – Limited Time Discounts | Deal Machan</title>
        <meta name="description" content="Grab time-sensitive flash deals and massive discounts from local merchants before they expire on Deal Machan." />
      </Helmet>
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
