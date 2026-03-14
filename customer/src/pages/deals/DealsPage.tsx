import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useInfiniteQuery, useQuery } from '@tanstack/react-query'
import { Search, Tag, Clock, X, Loader2, Lock } from 'lucide-react'
import { publicApi, type TopCoupon } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'
import { useInfiniteScroll } from '@/lib/useInfiniteScroll'
import { slugify } from '@/lib/slugify'

function timeLeft(until: string | null): string {
  if (!until) return 'Limited time'
  const diff = new Date(until).getTime() - Date.now()
  if (diff <= 0) return 'Expired'
  const h = Math.floor(diff / 3_600_000)
  const m = Math.floor((diff % 3_600_000) / 60_000)
  if (h >= 24) return `${Math.floor(h / 24)}d left`
  return h > 0 ? `${h}h ${m}m left` : `${m}m left`
}

import { getImageUrl } from '@/lib/imageUrl'
import { Helmet } from 'react-helmet-async'

function imgSrc(path: string | null): string {
  return getImageUrl(path, 'https://via.placeholder.com/400x200?text=Deal')
}

function CouponGridCard({ coupon }: { coupon: TopCoupon }) {
  const discountLabel =
    coupon.discount_type === 'percentage' ? `${coupon.discount_value}% OFF`
    : coupon.discount_type === 'flat'       ? `₹${coupon.discount_value} OFF`
    : coupon.discount_type === 'free_item'  ? 'FREE ITEM'
    : coupon.discount_type === 'bogo'       ? 'BOGO'
    : coupon.discount_value != null         ? `₹${coupon.discount_value} OFF`
    : 'DEAL'
  const displayName = coupon.store_name  || coupon.merchant_name
  const displayLogo = coupon.store_image || coupon.merchant_logo

  return (
    <Link to={`/deals/${coupon.id}/${slugify(coupon.title)}`} className="card card-hover group block overflow-hidden">
      <div className="relative h-44 bg-slate-100 overflow-hidden">
        <img
          src={imgSrc(coupon.banner_image || displayLogo)}
          alt={coupon.title}
          loading="lazy"
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/400x200?text=Deal' }}
        />
        <span className="absolute top-3 right-3 badge-discount text-base px-3 py-1">{discountLabel}</span>
      </div>
      <div className="p-4">
        <div className="flex items-center gap-2 mb-1.5">
          <img
            src={imgSrc(displayLogo)}
            alt={displayName}
            loading="lazy"
            className="w-6 h-6 rounded-full object-cover bg-slate-100"
            onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/40?text=S' }}
          />
          <span className="text-xs text-slate-500 font-medium truncate">{displayName}</span>
        </div>
        <h3 className="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 mb-3">{coupon.title}</h3>
        <div className="flex items-center justify-between">
          {coupon.coupon_code ? (
            <span className="inline-flex items-center gap-1 text-xs bg-slate-50 text-slate-600 px-2 py-1 rounded-lg font-mono font-bold border border-dashed border-slate-300">
              <Tag size={10} /> {coupon.coupon_code}
            </span>
          ) : (
            <Link
              to="/login"
              onClick={(e) => e.stopPropagation()}
              className="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-400 px-2 py-1 rounded-lg border border-dashed border-slate-200 hover:border-brand-300 hover:text-brand-500 transition-colors"
            >
              <Lock size={10} /> Login to see code
            </Link>
          )}
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

  const q     = params.get('q') ?? undefined
  const tagId = params.get('tag') ?? undefined

  const { data, isLoading, isFetchingNextPage, fetchNextPage, hasNextPage } = useInfiniteQuery({
    queryKey: ['deals-inf', cityId, q, tagId],
    queryFn: ({ pageParam = 1 }) =>
      publicApi.getCoupons({ city_id: cityId ?? undefined, q, tag_id: tagId, page: pageParam as number, per_page: 12 })
        .then((r) => r.data),
    initialPageParam: 1,
    getNextPageParam: (lastPage: any, allPages) => {
      const total = lastPage?.data?.pagination?.pages ?? 1
      return allPages.length < total ? allPages.length + 1 : undefined
    },
    staleTime: 2 * 60 * 1000,
  })

  const { data: tagsData } = useQuery({
    queryKey: ['tags'],
    queryFn: () => publicApi.getTags().then((r) => r.data.data ?? []),
    staleTime: Infinity,
  })

  const coupons: TopCoupon[] = data?.pages.flatMap((p: any) => p?.data?.data ?? []) ?? []
  const total = data?.pages[0]?.data?.pagination?.total ?? 0

  const sentinelRef = useInfiniteScroll(
    () => { if (hasNextPage && !isFetchingNextPage) fetchNextPage() },
    !!hasNextPage && !isFetchingNextPage,
  )

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
      <Helmet>
        <title>Best Deals &amp; Coupons | Deal Machan</title>
        <meta name="description" content="Browse hundreds of exclusive coupons and discount deals from local merchants near you. Filter by category, location, and more." />
      </Helmet>
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
          <>
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              {coupons.map((c) => <CouponGridCard key={c.id} coupon={c} />)}
            </div>

            {/* Infinite scroll sentinel */}
            <div ref={sentinelRef} className="h-4 mt-4" />
            {isFetchingNextPage && (
              <div className="flex justify-center py-6">
                <Loader2 size={22} className="animate-spin text-brand-500" />
              </div>
            )}
            {!hasNextPage && coupons.length > 0 && (
              <p className="text-center text-xs text-slate-400 py-6">All {total} deals loaded</p>
            )}
          </>
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
      </div>
    </div>
  )
}
