import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useInfiniteQuery } from '@tanstack/react-query'
import { Search, Store, Star, MapPin, Tag, X, Loader2 } from 'lucide-react'
import { publicApi, type PublicMerchant } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'
import { useInfiniteScroll } from '@/lib/useInfiniteScroll'

import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'
import { Helmet } from 'react-helmet-async'

function imgSrc(path: string | null): string {
  return getImageUrl(path)
}

function MerchantCard({ merchant }: { merchant: PublicMerchant }) {
  const firstStore = merchant.stores?.[0]
  return (
    <Link to={`/stores/${merchant.id}/${slugify(merchant.business_name)}`} className="card card-hover group block overflow-hidden">
      <div className="relative h-36 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
        {merchant.business_logo ? (
          <img
            src={imgSrc(merchant.business_logo)}
            alt={merchant.business_name}
            loading="lazy"
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <Store size={40} className="text-slate-300" />
          </div>
        )}
        {merchant.is_premium && (
          <span className="absolute top-2 left-2 badge-new">✨ Premium</span>
        )}
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-slate-800 text-sm mb-1.5 line-clamp-1">{merchant.business_name}</h3>
        <div className="flex items-center gap-1 mb-2">
          <Star size={12} className="text-yellow-400 fill-yellow-400" />
          <span className="text-xs font-semibold text-slate-700">{merchant.avg_rating != null ? Number(merchant.avg_rating).toFixed(1) : '–'}</span>
          <span className="text-xs text-slate-400">({merchant.total_reviews ?? 0})</span>
        </div>
        {firstStore && (firstStore.area_name || firstStore.city_name) && (
          <div className="flex items-center gap-1 text-xs text-slate-500 mb-2">
            <MapPin size={11} />
            <span className="truncate">{firstStore.area_name ?? firstStore.city_name}</span>
          </div>
        )}
        {merchant.active_coupons_count > 0 && (
          <div className="flex items-center gap-1 text-xs text-brand-600 font-semibold">
            <Tag size={11} />
            {merchant.active_coupons_count} deal{merchant.active_coupons_count > 1 ? 's' : ''}
          </div>
        )}
      </div>
    </Link>
  )
}

export default function StoresPage() {
  const [params, setParams] = useSearchParams()
  const { cityId } = useLocationStore()
  const [search, setSearch] = useState(params.get('q') ?? '')
  const q = params.get('q') ?? undefined

  const { data, isLoading, isFetchingNextPage, fetchNextPage, hasNextPage } = useInfiniteQuery({
    queryKey: ['stores-inf', cityId, q],
    queryFn: ({ pageParam = 1 }) =>
      publicApi.getMerchants({ city_id: cityId ?? undefined, q, search: q, page: pageParam as number, per_page: 12, has_coupons: false })
        .then((r) => r.data),
    initialPageParam: 1,
    getNextPageParam: (lastPage: any, allPages) => {
      const total = lastPage?.data?.pagination?.pages ?? 1
      return allPages.length < total ? allPages.length + 1 : undefined
    },
    staleTime: 2 * 60 * 1000,
  })

  const merchants: PublicMerchant[] = data?.pages.flatMap((p: any) => p?.data?.data ?? []) ?? []

  const sentinelRef = useInfiniteScroll(
    () => { if (hasNextPage && !isFetchingNextPage) fetchNextPage() },
    !!hasNextPage && !isFetchingNextPage,
  )

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    const next = new URLSearchParams()
    if (search.trim()) next.set('q', search.trim())
    setParams(next)
  }

  return (
    <div>
      <Helmet>
        <title>Merchant Directory | Deal Machan</title>
        <meta name="description" content="Explore local merchant stores and find exclusive deals, discounts, and coupons near you on Deal Machan." />
      </Helmet>
      <div className="bg-white border-b border-slate-100 py-8">
        <div className="site-container">
          <h1 className="section-heading mb-2">Browse Stores</h1>
          <p className="text-slate-500 text-sm mb-5">Discover merchant stores and their exclusive offers</p>
          <form onSubmit={handleSearch} className="flex gap-2 max-w-lg">
            <div className="flex flex-1 items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden focus-within:border-brand-400">
              <Search size={16} className="ml-3 text-slate-400" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search stores by name…"
                className="flex-1 px-3 py-3 bg-transparent text-sm outline-none"
              />
              {search && (
                <button type="button" onClick={() => { setSearch(''); setParams({}) }}>
                  <X size={15} className="mr-3 text-slate-400" />
                </button>
              )}
            </div>
            <button type="submit" className="btn-primary !py-3 !px-5 !rounded-xl !text-sm">Search</button>
          </form>
        </div>
      </div>

      <div className="site-container py-8">
        {isLoading ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {[1, 2, 3, 4, 5, 6].map((i) => <div key={i} className="h-60 skeleton rounded-2xl" />)}
          </div>
        ) : merchants.length > 0 ? (
          <>
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              {merchants.map((m) => <MerchantCard key={m.id} merchant={m} />)}
            </div>

            {/* Infinite scroll sentinel */}
            <div ref={sentinelRef} className="h-4 mt-4" />
            {isFetchingNextPage && (
              <div className="flex justify-center py-6">
                <Loader2 size={22} className="animate-spin text-brand-500" />
              </div>
            )}
            {!hasNextPage && merchants.length > 0 && (
              <p className="text-center text-xs text-slate-400 py-6">All stores loaded</p>
            )}
          </>
        ) : (
          <div className="text-center py-24 text-slate-400">
            <Store size={48} className="mx-auto mb-4 opacity-30" />
            <p className="font-semibold text-lg">No stores found</p>
            <p className="text-sm mt-1">Try a different search or clear filters</p>
            <button onClick={() => { setSearch(''); setParams({}) }} className="mt-4 btn-ghost !px-6 !py-2.5 !text-sm !rounded-xl">
              Clear search
            </button>
          </div>
        )}
      </div>
    </div>
  )
}
