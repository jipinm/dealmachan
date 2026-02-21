import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Tag, Clock, Star, MapPin, ArrowLeft, ChevronRight } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import { useAuthStore } from '@/store/authStore'

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
  if (!path) return 'https://via.placeholder.com/800x400?text=Deal'
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL?.replace('/api', '') ?? 'http://localhost:8000'}${path}`
}

export default function DealDetailPage() {
  const { id } = useParams<{ id: string }>()
  const { isAuthenticated } = useAuthStore()

  const { data, isLoading, error } = useQuery({
    queryKey: ['deal-detail', id],
    queryFn: () => publicApi.getCouponDetail(Number(id)).then((r) => r.data.data),
    enabled: !!id,
  })

  if (isLoading) {
    return (
      <div className="site-container py-10">
        <div className="max-w-2xl mx-auto space-y-4">
          <div className="h-64 skeleton rounded-3xl" />
          <div className="h-8 skeleton rounded-xl w-3/4" />
          <div className="h-5 skeleton rounded-xl w-1/2" />
          <div className="h-20 skeleton rounded-2xl" />
        </div>
      </div>
    )
  }

  if (error || !data) {
    return (
      <div className="site-container py-20 text-center text-slate-400">
        <Tag size={48} className="mx-auto mb-4 opacity-30" />
        <p className="font-semibold">Deal not found</p>
        <Link to="/deals" className="mt-4 btn-primary !px-6 !py-2.5 !text-sm !rounded-xl inline-flex items-center gap-2">
          <ArrowLeft size={16} /> Back to Deals
        </Link>
      </div>
    )
  }

  const { coupon, merchant, stores } = data
  const discountLabel = coupon.discount_type === 'percentage'
    ? `${coupon.discount_value}% OFF`
    : `₹${coupon.discount_value} OFF`

  return (
    <div>
      {/* Breadcrumb */}
      <div className="bg-white border-b border-slate-100">
        <div className="site-container py-3">
          <nav className="flex items-center gap-1 text-sm text-slate-500">
            <Link to="/" className="hover:text-brand-600">Home</Link>
            <ChevronRight size={13} />
            <Link to="/deals" className="hover:text-brand-600">Deals</Link>
            <ChevronRight size={13} />
            <span className="text-slate-800 font-medium truncate max-w-[200px]">{coupon.title}</span>
          </nav>
        </div>
      </div>

      <div className="site-container py-8">
        <div className="max-w-3xl mx-auto">
          {/* Hero image */}
          <div className="relative h-64 md:h-80 rounded-3xl overflow-hidden mb-6 bg-slate-100">
            <img
              src={imgSrc(coupon.banner_image)}
              alt={coupon.title}
              className="w-full h-full object-cover"
              onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/800x400?text=Deal' }}
            />
            <span className="absolute top-4 right-4 badge-discount text-lg px-4 py-1.5">{discountLabel}</span>
          </div>

          {/* Merchant row */}
          <Link
            to={`/stores/${merchant.id}`}
            className="flex items-center gap-3 p-4 bg-white rounded-2xl mb-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow"
          >
            <img
              src={imgSrc(merchant.business_logo)}
              alt={merchant.business_name}
              className="w-12 h-12 rounded-xl object-cover bg-slate-100"
            />
            <div className="flex-1 min-w-0">
              <p className="font-semibold text-slate-800 text-sm">{merchant.business_name}</p>
              <div className="flex items-center gap-1 text-xs text-slate-500">
                <Star size={11} className="text-yellow-400 fill-yellow-400" />
                <span>{merchant.avg_rating?.toFixed(1)} ({merchant.total_reviews} reviews)</span>
              </div>
            </div>
            <ChevronRight size={16} className="text-slate-400" />
          </Link>

          {/* Deal title & meta */}
          <h1 className="font-heading font-bold text-2xl text-slate-800 mb-2">{coupon.title}</h1>
          {coupon.valid_until && (
            <div className="flex items-center gap-1.5 text-sm text-slate-500 mb-6">
              <Clock size={14} />
              <span>Expires: {new Date(coupon.valid_until).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })}</span>
              <span className="text-cta-500 font-semibold">({timeLeft(coupon.valid_until)})</span>
            </div>
          )}

          {/* Coupon code box */}
          <div className="bg-gradient-to-r from-brand-50 to-purple-50 border-2 border-dashed border-brand-300 rounded-2xl p-6 text-center mb-6">
            <p className="text-sm text-slate-500 mb-2">Your Coupon Code</p>
            {isAuthenticated ? (
              <>
                <div className="font-mono font-black text-3xl text-brand-700 tracking-widest mb-3">
                  {coupon.coupon_code}
                </div>
                <button
                  onClick={() => navigator.clipboard.writeText(coupon.coupon_code)}
                  className="btn-primary !px-8 !py-3 !rounded-xl"
                >
                  Copy Code
                </button>
              </>
            ) : (
              <>
                <div className="font-mono font-black text-3xl text-slate-300 tracking-widest mb-3 blur-sm select-none">
                  XXXXXX
                </div>
                <Link to="/login" className="btn-primary !px-8 !py-3 !rounded-xl inline-flex">
                  Sign In to Reveal Code
                </Link>
                <p className="text-xs text-slate-400 mt-2">
                  Free account required. <Link to="/register" className="text-brand-600 underline">Register here</Link>
                </p>
              </>
            )}
          </div>

          {/* Terms */}
          {(coupon as any).terms_and_conditions && (
            <div className="bg-white rounded-2xl p-5 border border-slate-100 mb-6">
              <h3 className="font-semibold text-slate-700 mb-2 text-sm">Terms & Conditions</h3>
              <p className="text-sm text-slate-500 leading-relaxed">{(coupon as any).terms_and_conditions}</p>
            </div>
          )}

          {/* Stores */}
          {(stores as any[]).length > 0 && (
            <div>
              <h3 className="font-semibold text-slate-700 mb-3">Available at</h3>
              <div className="space-y-3">
                {(stores as any[]).map((store: any, i: number) => (
                  <div key={i} className="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-100">
                    <MapPin size={16} className="text-cta-500 flex-shrink-0 mt-0.5" />
                    <div>
                      <p className="font-medium text-slate-800 text-sm">{store.store_name}</p>
                      <p className="text-xs text-slate-500">{store.address}{store.area_name ? `, ${store.area_name}` : ''}</p>
                      {store.phone && <p className="text-xs text-brand-600 mt-0.5">{store.phone}</p>}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
