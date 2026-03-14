import { useState, useEffect, useRef } from 'react'
import { useSearchParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  Search, Store, Tag, MapPin, Star, ChevronRight,
  Loader2, SearchX,
} from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'

// ── Helpers ───────────────────────────────────────────────────────────────

function discountLabel(type: string, value: number): string {
  switch (type) {
    case 'percentage': return `${value}% OFF`
    case 'flat':
    case 'fixed':      return `₹${value} OFF`
    case 'free_item':  return 'Free Item'
    case 'bogo':       return 'BOGO'
    default:           return `${value} OFF`
  }
}

function logo(
  src: string | null,
  alt: string,
  size = 'w-10 h-10',
  placeholder = '?',
) {
  if (src) {
    return (
      <img
        src={getImageUrl(src)}
        alt={alt}
        className={`${size} rounded-xl object-cover bg-slate-100 flex-shrink-0`}
      />
    )
  }
  return (
    <div
      className={`${size} rounded-xl gradient-brand flex items-center justify-center text-white font-bold text-sm flex-shrink-0`}
    >
      {placeholder}
    </div>
  )
}

type FilterTab = 'all' | 'merchants' | 'coupons' | 'stores'

// ── Main Page ─────────────────────────────────────────────────────────────

export default function SearchPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const urlQ       = searchParams.get('q') ?? ''
  const [input, setInput] = useState(urlQ)
  const [query, setQuery] = useState(urlQ)
  const [tab, setTab] = useState<FilterTab>('all')
  const inputRef = useRef<HTMLInputElement>(null)

  // Sync URL → input when navigating back/forward
  useEffect(() => {
    setInput(urlQ)
    setQuery(urlQ)
  }, [urlQ])

  // Focus input on mount
  useEffect(() => { inputRef.current?.focus() }, [])

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const trimmed = input.trim()
    if (!trimmed) return
    setQuery(trimmed)
    setSearchParams({ q: trimmed }, { replace: true })
    setTab('all')
  }

  const { data, isFetching, isError } = useQuery({
    queryKey: ['search', query],
    queryFn: () => publicApi.search(query),
    enabled: query.length >= 2,
    staleTime: 60_000,
  })

  const results      = data?.data?.data
  const merchants    = results?.merchants  ?? []
  const coupons      = results?.coupons    ?? []
  const stores       = results?.stores     ?? []
  const total        = merchants.length + coupons.length + stores.length
  const hasResults   = total > 0
  const hasQuery     = query.length >= 2

  const TABS: { id: FilterTab; label: string; count: number }[] = [
    { id: 'all',       label: 'All',       count: total           },
    { id: 'coupons',   label: 'Coupons',   count: coupons.length  },
    { id: 'merchants', label: 'Merchants', count: merchants.length },
    { id: 'stores',    label: 'Stores',    count: stores.length   },
  ]

  const showMerchants = (tab === 'all' || tab === 'merchants') && merchants.length > 0
  const showCoupons   = (tab === 'all' || tab === 'coupons')   && coupons.length   > 0
  const showStores    = (tab === 'all' || tab === 'stores')    && stores.length    > 0

  return (
    <div className="min-h-screen bg-slate-50">
      {/* ── Hero / search bar ─────────────────────────────────────────── */}
      <div className="gradient-brand py-10">
        <div className="site-container">
          <h1 className="font-heading font-black text-3xl text-white mb-6 text-center">
            Search DealMachan
          </h1>
          <form onSubmit={handleSubmit} className="max-w-2xl mx-auto relative">
            <Search
              size={20}
              className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
            />
            <input
              ref={inputRef}
              type="search"
              value={input}
              onChange={e => setInput(e.target.value)}
              placeholder="Search coupons, stores, merchants…"
              className="w-full pl-12 pr-28 py-4 rounded-2xl text-slate-800 bg-white shadow-lg text-base outline-none focus:ring-2 focus:ring-brand-400"
            />
            <button
              type="submit"
              className="absolute right-2 top-1/2 -translate-y-1/2 btn-primary !py-2 !px-5 !rounded-xl !text-sm"
            >
              Search
            </button>
          </form>
        </div>
      </div>

      <div className="site-container py-8">
        {/* ── Loading ───────────────────────────────────────────────── */}
        {isFetching && (
          <div className="flex items-center justify-center gap-3 py-16 text-slate-400">
            <Loader2 size={28} className="animate-spin text-brand-500" />
            <span className="text-lg">Searching…</span>
          </div>
        )}

        {/* ── Error ─────────────────────────────────────────────────── */}
        {isError && !isFetching && (
          <div className="text-center py-16 text-slate-500">
            <p className="font-medium">Something went wrong. Please try again.</p>
          </div>
        )}

        {/* ── No query ──────────────────────────────────────────────── */}
        {!isFetching && !hasQuery && (
          <div className="max-w-sm mx-auto text-center py-20 text-slate-400">
            <Search size={48} className="mx-auto mb-4 opacity-30" />
            <p className="text-lg font-medium text-slate-500">Start typing to search</p>
            <p className="text-sm mt-1">Find coupons, stores, and merchants near you</p>
          </div>
        )}

        {/* ── No results ────────────────────────────────────────────── */}
        {!isFetching && hasQuery && !hasResults && !isError && (
          <div className="max-w-sm mx-auto text-center py-20 text-slate-400">
            <SearchX size={48} className="mx-auto mb-4 opacity-30" />
            <p className="text-lg font-medium text-slate-500">No results for "{query}"</p>
            <p className="text-sm mt-1">Try a different keyword or browse categories</p>
            <Link to="/categories" className="btn-primary !py-2 !px-5 !rounded-xl !text-sm mt-5 inline-block">
              Browse Categories
            </Link>
          </div>
        )}

        {/* ── Results ───────────────────────────────────────────────── */}
        {!isFetching && hasQuery && hasResults && (
          <>
            {/* Filter tabs */}
            <div className="flex items-center gap-2 flex-wrap mb-6">
              {TABS.map(t => (
                <button
                  key={t.id}
                  onClick={() => setTab(t.id)}
                  className={`px-4 py-2 rounded-xl text-sm font-medium transition-all ${
                    tab === t.id
                      ? 'bg-brand-600 text-white shadow-sm'
                      : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                  }`}
                >
                  {t.label}
                  {t.count > 0 && (
                    <span
                      className={`ml-1.5 text-xs rounded-full px-1.5 py-0.5 ${
                        tab === t.id
                          ? 'bg-white/20 text-white'
                          : 'bg-slate-100 text-slate-500'
                      }`}
                    >
                      {t.count}
                    </span>
                  )}
                </button>
              ))}
              <p className="ml-auto text-sm text-slate-400">
                {total} result{total !== 1 ? 's' : ''} for "{query}"
              </p>
            </div>

            {/* ── Coupons section ─────────────────────────────────── */}
            {showCoupons && (
              <section className="mb-8">
                <h2 className="font-heading font-bold text-lg text-slate-800 mb-3 flex items-center gap-2">
                  <Tag size={18} className="text-brand-500" /> Coupons
                </h2>
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                  {coupons.map(c => (
                    <Link
                      key={c.id}
                      to={`/deals/${c.id}/${slugify(c.title)}`}
                      className="bg-white rounded-2xl border border-slate-100 p-4 flex items-start gap-3 hover:shadow-md transition-shadow"
                    >
                      {logo(c.merchant_logo, c.merchant_name)}
                      <div className="flex-1 min-w-0">
                        <p className="font-semibold text-sm text-slate-800 line-clamp-1">{c.title}</p>
                        <p className="text-xs text-slate-500 mt-0.5">{c.merchant_name}</p>
                        {c.description && (
                          <p className="text-xs text-slate-400 mt-1 line-clamp-1">{c.description}</p>
                        )}
                      </div>
                      <span className="text-xs font-bold text-brand-600 bg-brand-50 px-2 py-1 rounded-lg flex-shrink-0 whitespace-nowrap">
                        {discountLabel(c.discount_type, c.discount_value)}
                      </span>
                    </Link>
                  ))}
                </div>
              </section>
            )}

            {/* ── Merchants section ───────────────────────────────── */}
            {showMerchants && (
              <section className="mb-8">
                <h2 className="font-heading font-bold text-lg text-slate-800 mb-3 flex items-center gap-2">
                  <Store size={18} className="text-cta-500" /> Merchants
                </h2>
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                  {merchants.map(m => (
                    <Link
                      key={m.id}
                      to={`/merchants/${m.id}`}
                      className="bg-white rounded-2xl border border-slate-100 p-4 flex items-center gap-3 hover:shadow-md transition-shadow"
                    >
                      {logo(m.business_logo, m.business_name)}
                      <div className="flex-1 min-w-0">
                        <p className="font-semibold text-sm text-slate-800 line-clamp-1">{m.business_name}</p>
                        <div className="flex items-center gap-1 text-xs text-slate-500 mt-0.5">
                          {(m.avg_rating ?? 0) > 0 && (
                            <>
                              <Star size={11} className="text-yellow-400 fill-yellow-400" />
                              <span>{Number(m.avg_rating).toFixed(1)}</span>
                              <span className="text-slate-300">·</span>
                            </>
                          )}
                          <span>{m.coupon_count ?? m.active_coupons_count ?? 0} coupon{(m.coupon_count ?? m.active_coupons_count ?? 0) !== 1 ? 's' : ''}</span>
                        </div>
                      </div>
                      <ChevronRight size={16} className="text-slate-300 flex-shrink-0" />
                    </Link>
                  ))}
                </div>
              </section>
            )}

            {/* ── Stores section ──────────────────────────────────── */}
            {showStores && (
              <section className="mb-8">
                <h2 className="font-heading font-bold text-lg text-slate-800 mb-3 flex items-center gap-2">
                  <MapPin size={18} className="text-rose-500" /> Stores
                </h2>
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                  {stores.map(s => (
                    <Link
                      key={s.id}
                      to={`/stores/${s.id}/${slugify(s.name)}`}
                      className="bg-white rounded-2xl border border-slate-100 p-4 flex items-start gap-3 hover:shadow-md transition-shadow"
                    >
                      {logo(s.business_logo, s.business_name)}
                      <div className="flex-1 min-w-0">
                        <p className="font-semibold text-sm text-slate-800 line-clamp-1">{s.name}</p>
                        <p className="text-xs text-slate-500 mt-0.5">{s.business_name}</p>
                        <div className="flex items-center gap-1 text-xs text-slate-400 mt-1">
                          <MapPin size={11} className="flex-shrink-0" />
                          <span className="line-clamp-1">
                            {s.address}{s.area_name ? `, ${s.area_name}` : ''}{s.city_name ? `, ${s.city_name}` : ''}
                          </span>
                        </div>
                      </div>
                      <ChevronRight size={16} className="text-slate-300 flex-shrink-0" />
                    </Link>
                  ))}
                </div>
              </section>
            )}
          </>
        )}
      </div>
    </div>
  )
}
