import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Search, Tag, Clock, X } from 'lucide-react'
import { publicApi, type TopCoupon } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'

function timeLeft(until: string | null): string {
  if (!until) return 'Limited time'
  const diff = new Date(until).getTime() - Date.now()
  if (diff <= 0) return 'Expired'
  const h = Math.floor(diff / 3_600_000)
  const m = Math.floor((diff % 3_600_000) / 60_000)
  if (h >= 24) return `${Math.floor(h / 24)}d left`
  return h > 0 ? `${h}h ${m}m left` : `${m}m left`
}

function imgSrc(path: string | null): string {
  if (!path) return 'https://via.placeholder.com/400x200?text=Deal'
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL?.replace('/api', '') ?? 'http://localhost:8000'}${path}`
}

function CouponGridCard({ coupon }: { coupon: TopCoupon }) {
  const discountLabel = coupon.discount_type === 'percentage'
    ? `${coupon.discount_value}% OFF`
    : `₹${coupon.discount_value} OFF`

  return (
    <Link to={`/deals/${coupon.id}`} className="card card-hover group block overflow-hidden">
      <div className="relative h-44 bg-slate-100 overflow-hidden">
        <img
          src={imgSrc(coupon.banner_image)}
          alt={coupon.title}
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/400x200?text=Deal' }}
        />
        <span className="absolute top-3 right-3 badge-discount text-base px-3 py-1">{discountLabel}</span>
      </div>
      <div className="p-4">
        <div className="flex items-center gap-2 mb-1.5">
          <img
            src={imgSrc(coupon.merchant_logo)}
            alt={coupon.merchant_name}
            className="w-6 h-6 rounded-full object-cover bg-slate-100"
            onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/40?text=M' }}
          />
          <span className="text-xs text-slate-500 font-medium truncate">{coupon.merchant_name}</span>
        </div>
        <h3 className="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 mb-3">{coupon.title}</h3>
        <div className="flex items-center justify-between">
          <span className="inline-flex items-center gap-1 text-xs bg-slate-50 text-slate-600 px-2 py-1 rounded-lg font-mono font-bold border border-dashed border-slate-300">
            <Tag size={10} /> {coupon.coupon_code}
          </span>
          {coupon.valid_until && (
            <span className="flex items-center gap-1 text-xs text-slate-400">
              <Clock size={11} /> {timeLeft(coupon.valid_until)}
            </span>
          )}
        </div>
      </div>
    </Link>
  )
}

export default function DealsPage() {
  const [params, setParams] = useSearchParams()
  const { cityId } = useLocationStore()
  const [search, setSearch] = useState(params.get('q') ?? '')

  const q        = params.get('q') ?? undefined
  const tagId    = params.get('tag') ?? undefined
  const page     = Number(params.get('page') ?? '1')

  const { data, isLoading } = useQuery({
    queryKey: ['deals', cityId, q, tagId, page],
    queryFn: () =>
      publicApi.getCoupons({ city_id: cityId ?? undefined, q, tag_id: tagId, page, per_page: 12 })
        .then((r) => r.data),
    staleTime: 2 * 60 * 1000,
  })

  const { data: tagsData } = useQuery({
    queryKey: ['tags'],
    queryFn: () => publicApi.getTags().then((r) => r.data.data ?? []),
    staleTime: Infinity,
  })

  const coupons = data?.data ?? []
  const total   = data?.pagination?.total ?? 0

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    const next = new URLSearchParams(params)
    if (search.trim()) next.set('q', search.trim())
    else next.delete('q')
    next.delete('page')
    setParams(next)
  }

  function setTag(id: string | undefined) {
    const next = new URLSearchParams(params)
    if (id) next.set('tag', id)
    else next.delete('tag')
    next.delete('page')
    setParams(next)
  }

  return (
    <div>
      {/* Page header */}
      <div className="bg-white border-b border-slate-100 py-8">
        <div className="site-container">
          <h1 className="section-heading mb-2">Browse Deals</h1>
          <p className="text-slate-500 text-sm mb-5">
            {total > 0 ? `${total} deals available` : 'Find the best coupons and discounts near you'}
          </p>
          {/* Search */}
          <form onSubmit={handleSearch} className="flex gap-2 max-w-lg">
            <div className="flex flex-1 items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-100">
              <Search size={16} className="ml-3 text-slate-400" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search deals by name, store…"
                className="flex-1 px-3 py-3 bg-transparent text-sm outline-none"
              />
              {search && (
                <button type="button" onClick={() => { setSearch(''); const n = new URLSearchParams(params); n.delete('q'); setParams(n) }}>
                  <X size={15} className="mr-3 text-slate-400 hover:text-slate-600" />
                </button>
              )}
            </div>
            <button type="submit" className="btn-primary !py-3 !px-5 !rounded-xl !text-sm">Search</button>
          </form>
        </div>
      </div>

      <div className="site-container py-8">
        {/* Category filter chips */}
        {(tagsData ?? []).length > 0 && (
          <div className="scroll-x flex gap-2 mb-6 pb-1">
            <button
              onClick={() => setTag(undefined)}
              className={`flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium border transition-colors ${!tagId ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-600 border-slate-200 hover:border-brand-300'}`}
            >
              All
            </button>
            {(tagsData ?? []).slice(0, 20).map((tag) => (
              <button
                key={tag.id}
                onClick={() => setTag(String(tag.id))}
                className={`flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium border transition-colors ${tagId === String(tag.id) ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate-600 border-slate-200 hover:border-brand-300'}`}
              >
                {tag.tag_name}
              </button>
            ))}
          </div>
        )}

        {/* Grid */}
        {isLoading ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => <div key={i} className="h-72 skeleton rounded-2xl" />)}
          </div>
        ) : coupons.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {coupons.map((c) => <CouponGridCard key={c.id} coupon={c} />)}
          </div>
        ) : (
          <div className="text-center py-24 text-slate-400">
            <Tag size={48} className="mx-auto mb-4 opacity-30" />
            <p className="font-semibold text-lg">No deals found</p>
            <p className="text-sm mt-1">Try changing your filters or search query</p>
            <button
              onClick={() => { setSearch(''); setParams({}) }}
              className="mt-4 btn-ghost !px-6 !py-2.5 !text-sm !rounded-xl"
            >
              Clear filters
            </button>
          </div>
        )}

        {/* Pagination */}
        {(data?.pagination?.pages ?? 0) > 1 && (
          <div className="flex justify-center gap-2 mt-10">
            {Array.from({ length: data!.pagination!.pages }, (_, i) => i + 1).map((p) => (
              <button
                key={p}
                onClick={() => { const n = new URLSearchParams(params); n.set('page', String(p)); setParams(n) }}
                className={`w-9 h-9 rounded-lg text-sm font-semibold transition-colors ${p === page ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-brand-300'}`}
              >
                {p}
              </button>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
