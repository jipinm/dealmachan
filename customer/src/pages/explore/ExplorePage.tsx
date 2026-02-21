import { useState, useEffect } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Search, SlidersHorizontal, X } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'
import MerchantCard from '@/components/ui/MerchantCard'
import CouponCard from '@/components/ui/CouponCard'
import SkeletonCard, { SkeletonRow } from '@/components/ui/SkeletonCard'

type TabType = 'merchants' | 'coupons'
type DiscountFilter = 'all' | 'percentage' | 'flat' | 'free_item'

// SORT_OPTIONS reserved for future use

export default function ExplorePage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const { cityId, areaId } = useLocationStore()

  const [tab, setTab]     = useState<TabType>((searchParams.get('tab') as TabType) ?? 'merchants')
  const [q, setQ]         = useState(searchParams.get('q') ?? '')
  const [input, setInput] = useState(q)
  const [tagId, setTagId] = useState<number | null>(searchParams.get('tag') ? Number(searchParams.get('tag')) : null)
  const [discountFilter, setDiscountFilter] = useState<DiscountFilter>('all')
  const [showFilters, setShowFilters] = useState(false)
  const [page, setPage] = useState(1)

  // Sync q from URL on mount
  useEffect(() => {
    const urlQ = searchParams.get('q')
    if (urlQ) { setQ(urlQ); setInput(urlQ) }
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    setQ(input.trim())
    setPage(1)
    setSearchParams((p) => {
      if (input.trim()) p.set('q', input.trim()); else p.delete('q')
      return p
    })
  }

  function clearSearch() {
    setInput(''); setQ(''); setPage(1)
    setSearchParams((p) => { p.delete('q'); return p })
  }

  // ── Tags ──────────────────────────────────────────────────────────────────
  const { data: tagsData } = useQuery({
    queryKey: ['tags'],
    queryFn:  () => publicApi.getTags().then((r) => r.data.data ?? []),
    staleTime: Infinity,
  })

  // ── Merchants ─────────────────────────────────────────────────────────────
  const merchantQuery = useQuery({
    queryKey: ['explore-merchants', q, cityId, areaId, tagId, page],
    queryFn: () =>
      publicApi.getMerchants({
        q:        q || undefined,
        city_id:  cityId   ?? undefined,
        area_id:  areaId   ?? undefined,
        tag_id:   tagId    ?? undefined,
        page,
        per_page: 12,
      }).then((r) => r.data.data),
    staleTime: 60_000,
    enabled: tab === 'merchants',
  })

  // ── Coupons ───────────────────────────────────────────────────────────────
  const couponQuery = useQuery({
    queryKey: ['explore-coupons', q, cityId, tagId, discountFilter, page],
    queryFn: () =>
      publicApi.getCoupons({
        q:             q || undefined,
        city_id:       cityId ?? undefined,
        tag_id:        tagId  ?? undefined,
        discount_type: discountFilter !== 'all' ? discountFilter : undefined,
        page,
        per_page: 15,
      }).then((r) => r.data.data),
    staleTime: 60_000,
    enabled: tab === 'coupons',
  })

  const merchants       = (merchantQuery.data as any)?.data ?? []
  const merchantPages   = (merchantQuery.data as any)?.pagination?.pages ?? 1
  const coupons         = (couponQuery.data  as any)?.data ?? []
  const couponPages     = (couponQuery.data  as any)?.pagination?.pages ?? 1
  const isLoadingM      = merchantQuery.isLoading
  const isLoadingC      = couponQuery.isLoading

  function switchTab(t: TabType) {
    setTab(t); setPage(1)
    setSearchParams((p) => { p.set('tab', t); return p })
  }

  return (
    <div className="max-w-3xl mx-auto px-4 py-5 space-y-4">
      {/* ── Header ─────────────────────────────────────────────────────── */}
      <h1 className="font-heading font-bold text-2xl text-gray-900">Explore</h1>

      {/* ── Search bar ──────────────────────────────────────────────────── */}
      <form onSubmit={handleSearch} className="flex gap-2">
        <div className="flex-1 flex items-center bg-white border border-gray-200 rounded-xl px-3 py-2.5 gap-2 shadow-sm focus-within:ring-2 focus-within:ring-brand-400 transition">
          <Search size={16} className="text-gray-400 shrink-0" />
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="Search merchants, deals…"
            className="flex-1 text-sm outline-none bg-transparent text-gray-800 placeholder-gray-400"
          />
          {input && (
            <button type="button" onClick={clearSearch}>
              <X size={14} className="text-gray-400" />
            </button>
          )}
        </div>
        <button
          type="button"
          onClick={() => setShowFilters((v) => !v)}
          className={`px-3 rounded-xl border transition-colors flex items-center gap-1 text-sm font-medium ${
            showFilters || tagId ? 'bg-brand-600 text-white border-brand-600' : 'bg-white border-gray-200 text-gray-600'
          }`}
        >
          <SlidersHorizontal size={15} />
        </button>
      </form>

      {/* ── Filter panel ────────────────────────────────────────────────── */}
      {showFilters && (
        <div className="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm space-y-3">
          {/* Tags */}
          {tagsData && tagsData.length > 0 && (
            <div>
              <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Category</p>
              <div className="flex flex-wrap gap-1.5">
                <button
                  onClick={() => { setTagId(null); setPage(1) }}
                  className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
                    tagId === null ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  }`}
                >All</button>
                {(tagsData as any[]).map((t) => (
                  <button
                    key={t.id}
                    onClick={() => { setTagId(t.id); setPage(1) }}
                    className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
                      tagId === t.id ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    }`}
                  >
                    {t.icon && <span className="mr-1">{t.icon}</span>}{t.tag_name}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Coupon type (only show when coupons tab active) */}
          {tab === 'coupons' && (
            <div>
              <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Discount Type</p>
              <div className="flex flex-wrap gap-1.5">
                {(['all', 'percentage', 'flat', 'free_item'] as DiscountFilter[]).map((type) => (
                  <button
                    key={type}
                    onClick={() => { setDiscountFilter(type); setPage(1) }}
                    className={`px-3 py-1 rounded-full text-xs font-medium transition-colors capitalize ${
                      discountFilter === type ? 'bg-cta-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    }`}
                  >
                    {type === 'all' ? 'All' : type === 'percentage' ? '% Off' : type === 'flat' ? 'Flat ₹' : 'Free Item'}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      {/* ── Tabs ────────────────────────────────────────────────────────── */}
      <div className="flex bg-gray-100 rounded-xl p-1 gap-1">
        {(['merchants', 'coupons'] as TabType[]).map((t) => (
          <button
            key={t}
            onClick={() => switchTab(t)}
            className={`flex-1 py-2 rounded-lg text-sm font-semibold transition-all capitalize ${
              tab === t ? 'bg-white text-brand-700 shadow' : 'text-gray-500 hover:text-gray-700'
            }`}
          >
            {t}
          </button>
        ))}
      </div>

      {/* ── Active search label ──────────────────────────────────────────── */}
      {q && (
        <div className="flex items-center gap-2">
          <p className="text-sm text-gray-500">Results for "<span className="font-semibold text-gray-800">{q}</span>"</p>
          <button onClick={clearSearch} className="text-xs text-brand-600 hover:underline">Clear</button>
        </div>
      )}

      {/* ── Merchants Tab ─────────────────────────────────────────────────── */}
      {tab === 'merchants' && (
        <>
          {isLoadingM ? (
            <div className="grid grid-cols-2 gap-3">
              {Array.from({ length: 6 }).map((_, i) => <SkeletonCard key={i} />)}
            </div>
          ) : merchants.length > 0 ? (
            <>
              <div className="grid grid-cols-2 gap-3">
                {merchants.map((m: any) => (
                  <MerchantCard key={m.id} merchant={m} showFavourite />
                ))}
              </div>
              {/* Pagination */}
              {merchantPages > 1 && (
                <div className="flex justify-center gap-2 pt-2">
                  <button disabled={page <= 1} onClick={() => setPage((p) => p - 1)}
                    className="px-4 py-2 text-sm rounded-xl border border-gray-200 disabled:opacity-40 hover:bg-gray-50">← Prev</button>
                  <span className="px-4 py-2 text-sm text-gray-500">{page} / {merchantPages}</span>
                  <button disabled={page >= merchantPages} onClick={() => setPage((p) => p + 1)}
                    className="px-4 py-2 text-sm rounded-xl border border-gray-200 disabled:opacity-40 hover:bg-gray-50">Next →</button>
                </div>
              )}
            </>
          ) : (
            <div className="text-center py-12">
              <p className="text-4xl mb-3">🔍</p>
              <p className="text-gray-500 text-sm">No merchants found{q ? ` for "${q}"` : ''}.</p>
            </div>
          )}
        </>
      )}

      {/* ── Coupons Tab ───────────────────────────────────────────────────── */}
      {tab === 'coupons' && (
        <>
          {isLoadingC ? (
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, i) => <SkeletonRow key={i} />)}
            </div>
          ) : coupons.length > 0 ? (
            <>
              <div className="space-y-3">
                {coupons.map((c: any) => (
                  <CouponCard key={c.id} coupon={c} />
                ))}
              </div>
              {couponPages > 1 && (
                <div className="flex justify-center gap-2 pt-2">
                  <button disabled={page <= 1} onClick={() => setPage((p) => p - 1)}
                    className="px-4 py-2 text-sm rounded-xl border border-gray-200 disabled:opacity-40 hover:bg-gray-50">← Prev</button>
                  <span className="px-4 py-2 text-sm text-gray-500">{page} / {couponPages}</span>
                  <button disabled={page >= couponPages} onClick={() => setPage((p) => p + 1)}
                    className="px-4 py-2 text-sm rounded-xl border border-gray-200 disabled:opacity-40 hover:bg-gray-50">Next →</button>
                </div>
              )}
            </>
          ) : (
            <div className="text-center py-12">
              <p className="text-4xl mb-3">🏷️</p>
              <p className="text-gray-500 text-sm">No deals found{q ? ` for "${q}"` : ''}.</p>
            </div>
          )}
        </>
      )}
    </div>
  )
}
