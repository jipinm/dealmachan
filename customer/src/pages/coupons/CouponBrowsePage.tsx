import { useState, useCallback, useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useInfiniteQuery, useQuery } from '@tanstack/react-query'
import { Search, SlidersHorizontal, X, Loader2 } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import CouponCard from '@/components/ui/CouponCard'
import SkeletonCard from '@/components/ui/SkeletonCard'
import { useDebounce } from '@/lib/useDebounce'
import { useInfiniteScroll } from '@/lib/useInfiniteScroll'

const DISCOUNT_TYPES = [
  { value: '', label: 'All Types' },
  { value: 'percentage', label: '% Off' },
  { value: 'flat', label: '₹ Flat' },
  { value: 'free_item', label: 'Free Item' },
  { value: 'bogo', label: 'BOGO' },
]

export default function CouponBrowsePage() {
  const [params, setParams] = useSearchParams()
  const [showFilters, setShowFilters] = useState(false)

  // URL-driven filter params
  const urlQ         = params.get('q') ?? ''
  const discountType = params.get('type') ?? ''
  const tagId        = params.get('tag') ?? ''

  // Local input state — decoupled from URL so keystrokes feel instant
  const [inputQ, setInputQ] = useState(urlQ)

  // Keep local state in sync when URL changes externally (browser back/forward)
  useEffect(() => setInputQ(urlQ), [urlQ])

  // Debounce the search term: only fire API after user stops typing for 350 ms
  const debouncedQ = useDebounce(inputQ, 350)
  // Alias for use in filter badge display
  const q = debouncedQ

  // Sync debounced value back into URL params (so shareable links work)
  useEffect(() => {
    const next = new URLSearchParams(params)
    if (debouncedQ) next.set('q', debouncedQ); else next.delete('q')
    setParams(next, { replace: true })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debouncedQ])

  const setParam = useCallback(
    (key: string, value: string) => {
      const next = new URLSearchParams(params)
      if (value) next.set(key, value); else next.delete(key)
      setParams(next, { replace: true })
    },
    [params, setParams]
  )

  const { data: tagsData } = useQuery({
    queryKey: ['tags'],
    queryFn: () => publicApi.getTags().then((r) => r.data.data ?? []),
    staleTime: 300_000,
  })
  const tags = (tagsData as any[]) ?? []

  const {
    data,
    isLoading,
    isFetchingNextPage,
    fetchNextPage,
    hasNextPage,
  } = useInfiniteQuery({
    queryKey: ['browse-coupons-inf', debouncedQ, discountType, tagId],
    queryFn: ({ pageParam = 1 }) =>
      publicApi.getCoupons({ q: debouncedQ, discount_type: discountType, tag_id: tagId || undefined, page: pageParam as number, per_page: 20 }).then((r) => r.data),
    initialPageParam: 1,
    getNextPageParam: (lastPage: any, allPages) => {
      const total = lastPage?.data?.pagination?.pages ?? 1
      return allPages.length < total ? allPages.length + 1 : undefined
    },
    staleTime: 60_000,
  })

  const coupons: any[] = data?.pages.flatMap((p: any) => p?.data?.data ?? []) ?? []

  const sentinelRef = useInfiniteScroll(
    () => { if (hasNextPage && !isFetchingNextPage) fetchNextPage() },
    !!hasNextPage && !isFetchingNextPage,
  )

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-5 pb-8">
      {/* Desktop two-column layout: sidebar + main */}
      <div className="lg:flex lg:gap-6">

        {/* ── Left sidebar (desktop permanent / mobile hidden) ─────────── */}
        <aside className="hidden lg:block w-60 flex-shrink-0">
          <div className="card p-4 space-y-4 sticky top-24">
            <h2 className="font-heading font-semibold text-sm text-gray-800 flex items-center gap-2">
              <SlidersHorizontal size={14} /> Filters
            </h2>

            {/* Discount type */}
            <div>
              <p className="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Type</p>
              <div className="flex flex-col gap-1.5">
                {DISCOUNT_TYPES.map((dt) => (
                  <button
                    key={dt.value}
                    onClick={() => setParam('type', dt.value)}
                    className={`px-3 py-1.5 rounded-lg text-xs font-medium text-left border transition-colors ${
                      discountType === dt.value
                        ? 'border-brand-500 bg-brand-50 text-brand-700'
                        : 'border-gray-200 text-gray-500 hover:border-gray-300'
                    }`}
                  >
                    {dt.label}
                  </button>
                ))}
              </div>
            </div>

            {/* Tags */}
            {tags.length > 0 && (
              <div>
                <p className="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Category</p>
                <div className="flex flex-col gap-1.5 max-h-80 overflow-y-auto pr-1">
                  <button
                    onClick={() => setParam('tag', '')}
                    className={`px-3 py-1.5 rounded-lg text-xs font-medium text-left border transition-colors ${
                      !tagId ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'
                    }`}
                  >
                    All Categories
                  </button>
                  {tags.slice(0, 20).map((t: any) => (
                    <button
                      key={t.id}
                      onClick={() => setParam('tag', String(t.id))}
                      className={`px-3 py-1.5 rounded-lg text-xs font-medium text-left border transition-colors ${
                        tagId === String(t.id)
                          ? 'border-brand-500 bg-brand-50 text-brand-700'
                          : 'border-gray-200 text-gray-500 hover:border-gray-300'
                      }`}
                    >
                      {t.name}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {(discountType || tagId) && (
              <button
                onClick={() => { setParam('type', ''); setParam('tag', '') }}
                className="text-xs text-brand-600 hover:underline w-full text-left"
              >
                × Clear filters
              </button>
            )}
          </div>
        </aside>

        {/* ── Main content ───────────────────────────────────────────────── */}
        <div className="flex-1 min-w-0">
          {/* Header */}
          <div className="flex items-center justify-between mb-4">
            <h1 className="font-heading font-bold text-xl text-gray-900">All Deals</h1>
            {/* Filter toggle only visible on mobile */}
            <button
              onClick={() => setShowFilters((v) => !v)}
              className={`lg:hidden flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-lg transition-colors ${
                showFilters ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600'
              }`}
            >
              <SlidersHorizontal size={13} />
              Filters
            </button>
          </div>

          {/* Search */}
          <div className="relative mb-3">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true" />
            <input
              type="text"
              placeholder="Search deals…"
              value={inputQ}
              onChange={(e) => setInputQ(e.target.value)}
              aria-label="Search coupons and deals"
              className="input pl-9 w-full"
            />
            {inputQ && (
              <button
                onClick={() => { setInputQ(''); setParam('q', '') }}
                aria-label="Clear search"
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"
              >
                <X size={14} />
              </button>
            )}
          </div>

          {/* Mobile filter panel (hidden on desktop) */}
          {showFilters && (
            <div className="lg:hidden card p-4 mb-4 space-y-3">
              {/* Discount type */}
              <div>
                <p className="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Type</p>
                <div className="flex flex-wrap gap-2">
                  {DISCOUNT_TYPES.map((dt) => (
                    <button
                      key={dt.value}
                      onClick={() => setParam('type', dt.value)}
                      className={`px-3 py-1 rounded-full text-xs font-medium border transition-colors ${
                        discountType === dt.value
                          ? 'border-brand-500 bg-brand-50 text-brand-700'
                          : 'border-gray-200 text-gray-500 hover:border-gray-300'
                      }`}
                    >
                      {dt.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Tags */}
              {tags.length > 0 && (
                <div>
                  <p className="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Category</p>
                  <div className="flex flex-wrap gap-2">
                    <button
                      onClick={() => setParam('tag', '')}
                      className={`px-3 py-1 rounded-full text-xs font-medium border transition-colors ${
                        !tagId ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'
                      }`}
                    >
                      All
                    </button>
                    {tags.slice(0, 14).map((t: any) => (
                      <button
                        key={t.id}
                        onClick={() => setParam('tag', String(t.id))}
                        className={`px-3 py-1 rounded-full text-xs font-medium border transition-colors ${
                          tagId === String(t.id)
                            ? 'border-brand-500 bg-brand-50 text-brand-700'
                            : 'border-gray-200 text-gray-500 hover:border-gray-300'
                        }`}
                      >
                        {t.name}
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Active filters summary */}
          {(q || discountType || tagId) && (
            <div className="flex flex-wrap gap-2 mb-3">
              {q && (
                <span className="flex items-center gap-1 bg-gray-100 text-gray-600 text-xs px-2.5 py-1 rounded-full">
                  "{q}" <button onClick={() => setParam('q', '')} aria-label="Remove search filter"><X size={11} /></button>
                </span>
              )}
              {discountType && (
                <span className="flex items-center gap-1 bg-gray-100 text-gray-600 text-xs px-2.5 py-1 rounded-full">
                  {DISCOUNT_TYPES.find((d) => d.value === discountType)?.label}
                  <button onClick={() => setParam('type', '')} aria-label="Remove type filter"><X size={11} /></button>
                </span>
              )}
            </div>
          )}

          {/* Results */}
          {isLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, i) => <SkeletonCard key={i} />)}
            </div>
          ) : coupons.length > 0 ? (
            <>
              <div className="space-y-3">
                {coupons.map((c: any) => <CouponCard key={c.id} coupon={c} />)}
              </div>

              {/* Infinite scroll sentinel */}
              <div ref={sentinelRef} className="h-4" />
              {isFetchingNextPage && (
                <div className="flex justify-center py-4">
                  <Loader2 size={20} className="animate-spin text-brand-500" />
                </div>
              )}
              {!hasNextPage && coupons.length > 0 && (
                <p className="text-center text-xs text-gray-400 py-4">All deals loaded</p>
              )}
            </>
          ) : (
            <div className="text-center py-16">
              <p className="text-4xl mb-2">🏷️</p>
              <p className="text-gray-500 text-sm">No deals found.</p>
              {(q || discountType || tagId) && (
                <button
                  onClick={() => { setParam('q', ''); setParam('type', ''); setParam('tag', '') }}
                  className="mt-3 text-brand-600 text-sm hover:underline"
                >
                  Clear filters
                </button>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
