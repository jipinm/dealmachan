import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { publicApi } from '@/api/endpoints/public'
import { Star, MapPin, Phone, Tag, Globe, ChevronRight, Store, Clock, ArrowLeft } from 'lucide-react'

function imgSrc(path: string | null): string {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL?.replace('/api', '') ?? 'http://localhost:8000'}${path}`
}

function timeLeft(until: string | null): string {
  if (!until) return 'Limited time'
  const diff = new Date(until).getTime() - Date.now()
  if (diff <= 0) return 'Expired'
  const h = Math.floor(diff / 3_600_000)
  if (h >= 24) return `${Math.floor(h / 24)}d left`
  return `${h}h left`
}

export default function StoreDetailPage() {
  const { id } = useParams<{ id: string }>()

  const { data: merchant, isLoading } = useQuery({
    queryKey: ['merchant', id],
    queryFn: () => publicApi.getMerchant(Number(id)).then((r) => r.data.data),
    enabled: !!id,
  })

  const { data: coupons } = useQuery({
    queryKey: ['merchant-coupons', id],
    queryFn: () => publicApi.getMerchantCoupons(Number(id)).then((r) => r.data.data ?? []),
    enabled: !!id,
  })

  if (isLoading) {
    return (
      <div className="site-container py-10">
        <div className="h-48 skeleton rounded-3xl mb-6" />
        <div className="h-8 skeleton rounded-xl w-1/2 mb-3" />
        <div className="grid grid-cols-2 gap-3">
          {[1, 2, 3, 4].map((i) => <div key={i} className="h-48 skeleton rounded-2xl" />)}
        </div>
      </div>
    )
  }

  if (!merchant) {
    return (
      <div className="site-container py-20 text-center text-slate-400">
        <Store size={48} className="mx-auto mb-4 opacity-30" />
        <p className="font-semibold">Store not found</p>
        <Link to="/stores" className="mt-4 btn-primary !px-6 !py-2.5 !text-sm !rounded-xl inline-flex items-center gap-2">
          <ArrowLeft size={16} /> Back to Stores
        </Link>
      </div>
    )
  }

  return (
    <div>
      {/* Breadcrumb */}
      <div className="bg-white border-b border-slate-100">
        <div className="site-container py-3">
          <nav className="flex items-center gap-1 text-sm text-slate-500">
            <Link to="/" className="hover:text-brand-600">Home</Link>
            <ChevronRight size={13} />
            <Link to="/stores" className="hover:text-brand-600">Stores</Link>
            <ChevronRight size={13} />
            <span className="text-slate-800 font-medium truncate max-w-[200px]">{merchant.business_name}</span>
          </nav>
        </div>
      </div>

      <div className="site-container py-8">
        <div className="lg:grid lg:grid-cols-[300px,1fr] lg:gap-8">
          {/* Sidebar: merchant info */}
          <aside>
            <div className="card p-6 mb-5 sticky top-24">
              <div className="w-24 h-24 rounded-2xl bg-slate-100 overflow-hidden mx-auto mb-4">
                {merchant.business_logo ? (
                  <img src={imgSrc(merchant.business_logo)} alt={merchant.business_name} className="w-full h-full object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <Store size={36} className="text-slate-300" />
                  </div>
                )}
              </div>
              <h1 className="font-heading font-bold text-xl text-slate-800 text-center mb-2">{merchant.business_name}</h1>
              <div className="flex items-center justify-center gap-1 mb-4">
                <Star size={14} className="text-yellow-400 fill-yellow-400" />
                <span className="font-semibold text-slate-700">{merchant.avg_rating?.toFixed(1) ?? '–'}</span>
                <span className="text-sm text-slate-400">({merchant.total_reviews ?? 0} reviews)</span>
              </div>
              {merchant.business_description && (
                <p className="text-sm text-slate-500 leading-relaxed mb-4 text-center">{merchant.business_description}</p>
              )}
              {merchant.website_url && (
                <a
                  href={merchant.website_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-2 text-sm text-brand-600 hover:underline justify-center mb-3"
                >
                  <Globe size={14} /> Website
                </a>
              )}
              {/* Labels */}
              {merchant.labels?.length > 0 && (
                <div className="flex flex-wrap gap-1.5 justify-center mt-3">
                  {merchant.labels.map((l) => (
                    <span key={l.id} className="px-2.5 py-1 bg-brand-50 text-brand-700 text-xs font-medium rounded-full">
                      {l.label_name}
                    </span>
                  ))}
                </div>
              )}
            </div>
          </aside>

          {/* Main: coupons + stores */}
          <main>
            {/* Active deals */}
            <section className="mb-8">
              <h2 className="section-heading mb-4 text-xl">
                Active Deals <span className="text-slate-400 text-base font-normal">({coupons?.length ?? 0})</span>
              </h2>
              {(coupons ?? []).length > 0 ? (
                <div className="grid sm:grid-cols-2 gap-4">
                  {(coupons ?? []).map((c) => (
                    <Link
                      key={c.id}
                      to={`/deals/${c.id}`}
                      className="card card-hover p-4 block"
                    >
                      <div className="flex items-start justify-between mb-2">
                        <h3 className="font-semibold text-slate-800 text-sm line-clamp-2 flex-1 mr-2">{c.title}</h3>
                        <span className="badge-discount flex-shrink-0">
                          {c.discount_type === 'percentage' ? `${c.discount_value}%` : `₹${c.discount_value}`} OFF
                        </span>
                      </div>
                      <div className="flex items-center justify-between mt-2">
                        <span className="font-mono text-xs bg-slate-50 text-slate-600 px-2 py-1 rounded-lg border border-dashed border-slate-300">
                          {c.coupon_code}
                        </span>
                        {c.valid_until && (
                          <span className="flex items-center gap-1 text-xs text-slate-400">
                            <Clock size={11} /> {timeLeft(c.valid_until)}
                          </span>
                        )}
                      </div>
                    </Link>
                  ))}
                </div>
              ) : (
                <div className="text-center py-10 text-slate-400 bg-white rounded-2xl border border-slate-100">
                  <Tag size={32} className="mx-auto mb-2 opacity-30" />
                  <p className="text-sm">No active deals right now</p>
                </div>
              )}
            </section>

            {/* Store locations */}
            {merchant.stores?.length > 0 && (
              <section>
                <h2 className="section-heading mb-4 text-xl">Store Locations</h2>
                <div className="space-y-3">
                  {merchant.stores.map((store) => (
                    <div key={store.id} className="card p-4 flex items-start gap-3">
                      <div className="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center flex-shrink-0">
                        <MapPin size={16} className="text-cta-500" />
                      </div>
                      <div>
                        <p className="font-semibold text-slate-800 text-sm">{store.store_name}</p>
                        <p className="text-xs text-slate-500">{store.address}{store.area_name ? `, ${store.area_name}` : ''}{store.city_name ? `, ${store.city_name}` : ''}</p>
                        {store.phone && (
                          <a href={`tel:${store.phone}`} className="flex items-center gap-1 text-xs text-brand-600 mt-1">
                            <Phone size={11} /> {store.phone}
                          </a>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </section>
            )}
          </main>
        </div>
      </div>
    </div>
  )
}
