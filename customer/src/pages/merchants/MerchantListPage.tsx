import { useState, useEffect } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useInfiniteQuery, useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  Search, Store, Star, MapPin, Tag, X, SlidersHorizontal,
  Building2, Heart, Loader2,
} from 'lucide-react'
import { publicApi, type PublicMerchant, type Tag as CategoryTag } from '@/api/endpoints/public'
import { favouritesApi } from '@/api/endpoints/favourites'
import { useLocationStore } from '@/store/locationStore'
import { useAuthStore } from '@/store/authStore'
import { getImageUrl } from '@/lib/imageUrl'
import { useInfiniteScroll } from '@/lib/useInfiniteScroll'
import toast from 'react-hot-toast'

// ── Merchant Card ─────────────────────────────────────────────────────────

function MerchantCard({ merchant }: { merchant: PublicMerchant }) {
  const { isAuthenticated } = useAuthStore()
  const qc = useQueryClient()

  const { data: isFav } = useQuery({
    queryKey: ['fav-check', merchant.id],
    queryFn: () => favouritesApi.check(merchant.id).then((r) => r.is_favourite ?? false),
    enabled: isAuthenticated,
    staleTime: 60_000,
  })

  const toggleFav = useMutation({
    mutationFn: () => isFav ? favouritesApi.remove(merchant.id) : favouritesApi.add(merchant.id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['fav-check', merchant.id] })
      qc.invalidateQueries({ queryKey: ['wishlist'] })
    },
    onError: () => toast.error('Failed to update favourites.'),
  })

  const firstStore = merchant.stores?.[0]
  const location   = firstStore?.area_name ?? firstStore?.city_name ?? null
  const tags       = merchant.tags?.slice(0, 2) ?? []

  return (
    <div className="group bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 hover:shadow-md transition-all duration-200 flex flex-col">
      {/* Cover / logo */}
      <Link to={`/merchants/${merchant.id}`} className="block">
        <div className="relative h-36 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
          {merchant.business_logo ? (
            <img
              src={getImageUrl(merchant.business_logo)}
              alt={merchant.business_name}
              loading="lazy"
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center">
              <Building2 size={36} className="text-slate-300" />
            </div>
          )}
          {merchant.is_premium && (
            <span className="absolute top-2 left-2 text-[10px] font-bold bg-amber-400 text-white px-2 py-0.5 rounded-full">
              ✨ Premium
            </span>
          )}
          {isAuthenticated && (
            <button
              onClick={(e) => { e.preventDefault(); e.stopPropagation(); toggleFav.mutate() }}
              className="absolute top-2 right-2 w-7 h-7 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center hover:bg-white transition"
            >
              {toggleFav.isPending ? (
                <Loader2 size={13} className="animate-spin text-slate-500" />
              ) : (
                <Heart
                  size={13}
                  className={isFav ? 'text-rose-500 fill-rose-500' : 'text-slate-400'}
                />
              )}
            </button>
          )}
        </div>
      </Link>

      {/* Info */}
      <Link to={`/merchants/${merchant.id}`} className="flex-1 p-3.5 flex flex-col gap-1.5">
        <h3 className="font-semibold text-slate-800 text-sm line-clamp-1 leading-snug">
          {merchant.business_name}
        </h3>

        {/* Rating */}
        <div className="flex items-center gap-1">
          <Star size={11} className="text-yellow-400 fill-yellow-400 flex-shrink-0" />
          <span className="text-xs font-semibold text-slate-700">
            {merchant.avg_rating > 0 ? Number(merchant.avg_rating).toFixed(1) : '–'}
          </span>
          {merchant.total_reviews > 0 && (
            <span className="text-xs text-slate-400">({merchant.total_reviews})</span>
          )}
        </div>

        {/* Location */}
        {location && (
          <div className="flex items-center gap-1 text-xs text-slate-500">
            <MapPin size={11} className="flex-shrink-0" />
            <span className="truncate">{location}</span>
          </div>
        )}

        {/* Tags */}
        {tags.length > 0 && (
          <div className="flex flex-wrap gap-1 mt-0.5">
            {tags.map((t) => (
              <span key={t.id} className="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full">
                {t.tag_name}
              </span>
            ))}
          </div>
        )}

        {/* Coupon count */}
        {merchant.active_coupons_count > 0 && (
          <div className="flex items-center gap-1 text-xs text-brand-600 font-semibold mt-auto pt-1">
            <Tag size={11} />
            {merchant.active_coupons_count} deal{merchant.active_coupons_count > 1 ? 's' : ''}
          </div>
        )}
      </Link>
    </div>
  )
}

// ── Skeleton ──────────────────────────────────────────────────────────────

function MerchantSkeleton() {
  return (
    <div className="bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 animate-pulse">
      <div className="h-36 bg-slate-200" />
      <div className="p-3.5 space-y-2">
        <div className="h-3 bg-slate-200 rounded w-3/4" />
        <div className="h-3 bg-slate-200 rounded w-1/2" />
        <div className="h-3 bg-slate-200 rounded w-2/3" />
      </div>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────

export default function MerchantListPage() {
  const [params, setParams] = useSearchParams()
  const { cityId, cityName, openLocationModal } = useLocationStore()

  const [search,     setSearch]    = useState(params.get('q') ?? '')
  const [searchInput, setInput]    = useState(params.get('q') ?? '')
  const [activeTag,  setActiveTag] = useState<number | null>(
    params.get('tag') ? Number(params.get('tag')) : null,
  )
  const [showFilters, setShowFilters] = useState(false)

  // Auto-open location modal if no city set
  useEffect(() => {
    if (!cityId) openLocationModal()
  }, [cityId, openLocationModal])

  // Sync tag param changes
  useEffect(() => {
    const tagParam = params.get('tag')
    setActiveTag(tagParam ? Number(tagParam) : null)
  }, [params])

  // Tags for filter chips
  const { data: tagsData } = useQuery({
    queryKey: ['tags'],
    queryFn: () => publicApi.getTags().then((r) => r.data.data ?? []),
    staleTime: Infinity,
  })
  const tags: CategoryTag[] = tagsData ?? []

  // Merchant listing
  const { data, isLoading, isFetchingNextPage, fetchNextPage, hasNextPage } = useInfiniteQuery({
    queryKey: ['merchants-inf', cityId, search, activeTag],
    queryFn: ({ pageParam = 1 }) =>
      publicApi.getMerchants({
        city_id: cityId ?? undefined,
        q: search || undefined,
        tag_id: activeTag ?? undefined,
        page: pageParam as number,
        per_page: 12,
      }).then((r) => r.data),
    initialPageParam: 1,
    getNextPageParam: (lastPage: any, allPages) => {
      const total = (lastPage as any)?.pagination?.pages ?? (lastPage as any)?.meta?.pages ?? 1
      return allPages.length < total ? allPages.length + 1 : undefined
    },
    staleTime: 2 * 60 * 1000,
  })

  const merchants: PublicMerchant[] = data?.pages.flatMap((p: any) => p?.data ?? []) ?? []
  const total = data?.pages[0] ? ((data.pages[0] as any)?.pagination?.total ?? (data.pages[0] as any)?.meta?.total ?? 0) : 0

  const sentinelRef = useInfiniteScroll(
    () => { if (hasNextPage && !isFetchingNextPage) fetchNextPage() },
    !!hasNextPage && !isFetchingNextPage,
  )

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    const next = new URLSearchParams(params)
    if (searchInput.trim()) next.set('q', searchInput.trim())
    else next.delete('q')
    next.delete('page')
    setParams(next)
    setSearch(searchInput.trim())
  }

  function clearSearch() {
    setInput('')
    setSearch('')
    const next = new URLSearchParams(params)
    next.delete('q')
    next.delete('page')
    setParams(next)
  }

  function selectTag(tagId: number | null) {
    const next = new URLSearchParams(params)
    if (tagId) next.set('tag', String(tagId))
    else next.delete('tag')
    next.delete('page')
    setParams(next)
    setActiveTag(tagId)
  }

  const isFiltered = !!search || !!activeTag

  return (
    <div className="min-h-screen bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-100">
        <div className="site-container py-8">
          <h1 className="section-heading mb-1">Browse Merchants</h1>
          <p className="text-slate-500 text-sm mb-5">
            {cityName ? (
              <>Showing merchants in <span className="font-semibold text-slate-700">{cityName}</span></>
            ) : (
              <>
                <button onClick={openLocationModal} className="text-brand-600 hover:underline font-medium">
                  Set your location
                </button>{' '}
                to filter by city
              </>
            )}
          </p>

          {/* Search bar */}
          <form onSubmit={handleSearch} className="flex gap-2 max-w-xl">
            <div className="flex flex-1 items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden focus-within:border-brand-400 focus-within:bg-white transition-colors">
              <Search size={16} className="ml-3 text-slate-400 flex-shrink-0" />
              <input
                type="text"
                value={searchInput}
                onChange={(e) => setInput(e.target.value)}
                placeholder="Search merchants by name…"
                className="flex-1 px-3 py-3 bg-transparent text-sm outline-none placeholder:text-slate-400"
              />
              {searchInput && (
                <button type="button" onClick={clearSearch} className="mr-3">
                  <X size={15} className="text-slate-400" />
                </button>
              )}
            </div>
            <button type="submit" className="btn-primary !py-3 !px-5 !rounded-xl !text-sm flex-shrink-0">
              Search
            </button>
            <button
              type="button"
              onClick={() => setShowFilters(!showFilters)}
              className={`hidden sm:flex items-center gap-1.5 border rounded-xl px-4 py-3 text-sm font-medium transition-colors ${
                showFilters ? 'border-brand-400 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'
              }`}
            >
              <SlidersHorizontal size={16} />
              <span>Filters</span>
              {isFiltered && <span className="w-2 h-2 rounded-full bg-brand-500" />}
            </button>
          </form>
        </div>

        {/* Tag filter chips */}
        {tags.length > 0 && (
          <div className="site-container pb-4">
            <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
              <button
                onClick={() => selectTag(null)}
                className={`flex-shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors ${
                  !activeTag
                    ? 'bg-brand-600 text-white'
                    : 'bg-white border border-slate-200 text-slate-600 hover:border-brand-300'
                }`}
              >
                All
              </button>
              {tags.map((t) => (
                <button
                  key={t.id}
                  onClick={() => selectTag(t.id)}
                  className={`flex-shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors whitespace-nowrap ${
                    activeTag === t.id
                      ? 'bg-brand-600 text-white'
                      : 'bg-white border border-slate-200 text-slate-600 hover:border-brand-300'
                  }`}
                >
                  {t.icon && <span className="mr-1">{t.icon}</span>}
                  {t.tag_name}
                </button>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Content */}
      <div className="site-container py-6">
        {/* Results header */}
        <div className="flex items-center justify-between mb-4">
          <p className="text-sm text-slate-500">
            {isLoading ? (
              <span className="inline-block w-24 h-3 bg-slate-200 rounded animate-pulse" />
            ) : (
              <>
                <span className="font-semibold text-slate-700">{total}</span>
                {' '}merchant{total !== 1 ? 's' : ''} found
                {isFiltered && ' (filtered)'}
              </>
            )}
          </p>
          {isFiltered && (
            <button
              onClick={() => {
                clearSearch()
                selectTag(null)
              }}
              className="flex items-center gap-1 text-xs text-brand-600 hover:text-brand-700 font-medium"
            >
              <X size={12} /> Clear filters
            </button>
          )}
          {isFetchingNextPage && !isLoading && (
            <Loader2 size={14} className="animate-spin text-slate-400" />
          )}
        </div>

        {/* Grid */}
        {isLoading ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {Array.from({ length: 8 }, (_, i) => <MerchantSkeleton key={i} />)}
          </div>
        ) : merchants.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {merchants.map((m) => (
              <MerchantCard key={m.id} merchant={m} />
            ))}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center text-center py-20 gap-4">
            <div className="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center">
              <Store size={32} className="text-slate-300" />
            </div>
            <div>
              <p className="font-semibold text-slate-700 text-lg">No merchants found</p>
              <p className="text-sm text-slate-400 mt-1">
                {isFiltered
                  ? 'Try clearing your filters or broadening your search.'
                  : cityId
                  ? 'No merchants available in this area yet.'
                  : 'Set your location to discover merchants near you.'}
              </p>
            </div>
            {isFiltered ? (
              <button
                onClick={() => { clearSearch(); selectTag(null) }}
                className="btn-ghost !rounded-xl !text-sm !px-6 !py-2.5"
              >
                Clear filters
              </button>
            ) : !cityId ? (
              <button
                onClick={openLocationModal}
                className="btn-primary !rounded-xl !text-sm !px-6 !py-2.5"
              >
                Set Location
              </button>
            ) : null}
          </div>
        )}

        {/* Pagination — replaced by infinite scroll sentinel */}
        {merchants.length > 0 && (
          <>
            <div ref={sentinelRef} className="h-4 mt-4" />
            {isFetchingNextPage && (
              <div className="flex justify-center py-6">
                <Loader2 size={22} className="animate-spin text-brand-500" />
              </div>
            )}
            {!hasNextPage && merchants.length > 0 && (
              <p className="text-center text-xs text-slate-400 py-6">
                {total > 0 ? `All ${total} merchants loaded` : 'All merchants loaded'}
              </p>
            )}
          </>
        )}
      </div>
    </div>
  )
}
