import { useState, useCallback } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Search, SlidersHorizontal, X } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import CouponCard from '@/components/ui/CouponCard'
import SkeletonCard from '@/components/ui/SkeletonCard'

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
  const [page, setPage] = useState(1)

  const q            = params.get('q') ?? ''
  const discountType = params.get('type') ?? ''
  const tagId        = params.get('tag') ?? ''

  const setParam = useCallback(
    (key: string, value: string) => {
      const next = new URLSearchParams(params)
      if (value) next.set(key, value); else next.delete(key)
      setParams(next, { replace: true })
      setPage(1)
    },
    [params, setParams]
  )

  const { data: tagsData } = useQuery({
    queryKey: ['tags'],
    queryFn: () => publicApi.getTags().then((r) => r.data.data ?? []),
    staleTime: 300_000,
  })

  const { data, isLoading, isFetching } = useQuery({
    queryKey: ['browse-coupons', q, discountType, tagId, page],
    queryFn: () =>
      publicApi.getCoupons({ q, discount_type: discountType, tag_id: tagId || undefined, page, per_page: 20 }).then((r) => r.data),
    staleTime: 60_000,
  })

  const coupons: any[] = (data as any)?.data ?? []
  const pages: number  = (data as any)?.pagination?.pages ?? 1

  return (
    <div className="max-w-2xl mx-auto px-4 py-5 pb-8">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="font-heading font-bold text-xl text-gray-900">All Deals</h1>
        <button
          onClick={() => setShowFilters((v) => !v)}
          className={`flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-lg transition-colors ${
            showFilters ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600'
          }`}
        >
          <SlidersHorizontal size={13} />
          Filters
        </button>
      </div>

      {/* Search */}
      <div className="relative mb-3">
        <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
          type="text"
          placeholder="Search deals…"
          value={q}
          onChange={(e) => setParam('q', e.target.value)}
          className="input pl-9 w-full"
        />
        {q && (
          <button onClick={() => setParam('q', '')} className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
            <X size={14} />
          </button>
        )}
      </div>

      {/* Filter panel */}
      {showFilters && (
        <div className="card p-4 mb-4 space-y-3">
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

          {/* Tags*/}
          {(tagsData as any[])?.length > 0 && (
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
                {(tagsData as any[]).slice(0, 14).map((t: any) => (
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
              "{q}" <button onClick={() => setParam('q', '')}><X size={11} /></button>
            </span>
          )}
          {discountType && (
            <span className="flex items-center gap-1 bg-gray-100 text-gray-600 text-xs px-2.5 py-1 rounded-full">
              {DISCOUNT_TYPES.find((d) => d.value === discountType)?.label}
              <button onClick={() => setParam('type', '')}><X size={11} /></button>
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
        <div className="space-y-3">
          {coupons.map((c: any) => <CouponCard key={c.id} coupon={c} />)}
        </div>
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

      {/* Pagination */}
      {pages > 1 && (
        <div className="flex items-center justify-center gap-4 mt-6">
          <button
            disabled={page === 1 || isFetching}
            onClick={() => setPage(page - 1)}
            className="btn-outline text-sm px-4 py-2 disabled:opacity-40"
          >
            ← Prev
          </button>
          <span className="text-sm text-gray-500">
            {page} / {pages}
          </span>
          <button
            disabled={page >= pages || isFetching}
            onClick={() => setPage(page + 1)}
            className="btn-outline text-sm px-4 py-2 disabled:opacity-40"
          >
            Next →
          </button>
        </div>
      )}
    </div>
  )
}
