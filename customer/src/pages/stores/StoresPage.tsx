import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Search, Store, Star, MapPin, Tag, X } from 'lucide-react'
import { publicApi, type PublicMerchant } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'

function imgSrc(path: string | null): string {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL?.replace('/api', '') ?? 'http://localhost:8000'}${path}`
}

function MerchantCard({ merchant }: { merchant: PublicMerchant }) {
  const firstStore = merchant.stores?.[0]
  return (
    <Link to={`/stores/${merchant.id}`} className="card card-hover group block overflow-hidden">
      <div className="relative h-36 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
        {merchant.business_logo ? (
          <img
            src={imgSrc(merchant.business_logo)}
            alt={merchant.business_name}
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
          <span className="text-xs font-semibold text-slate-700">{merchant.avg_rating?.toFixed(1) ?? '–'}</span>
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
  const page = Number(params.get('page') ?? '1')
  const q    = params.get('q') ?? undefined

  const { data, isLoading } = useQuery({
    queryKey: ['stores', cityId, q, page],
    queryFn: () =>
      publicApi.getMerchants({ city_id: cityId ?? undefined, q, search: q, page, per_page: 12, has_coupons: false })
        .then((r) => r.data),
    staleTime: 2 * 60 * 1000,
  })

  const merchants: PublicMerchant[] = data?.data ?? []
  const totalPages = data?.pagination?.pages ?? 1

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    const next = new URLSearchParams()
    if (search.trim()) next.set('q', search.trim())
    setParams(next)
  }

  return (
    <div>
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
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {merchants.map((m) => <MerchantCard key={m.id} merchant={m} />)}
          </div>
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

        {totalPages > 1 && (
          <div className="flex justify-center gap-2 mt-10">
            {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
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
