import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Zap, ArrowLeft, Clock, Store, MapPin, Phone, AlertCircle, Info } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'
import { Helmet } from 'react-helmet-async'

// ── Helpers ───────────────────────────────────────────────────────────────

function timeLeft(until: string): string {
  const diff = new Date(until).getTime() - Date.now()
  if (diff <= 0) return 'Expired'
  const h = Math.floor(diff / 3_600_000)
  const m = Math.floor((diff % 3_600_000) / 60_000)
  if (h >= 24) return `${Math.floor(h / 24)}d ${h % 24}h left`
  return h > 0 ? `${h}h ${m}m left` : `${m}m left`
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
}

// ── Loading skeleton ──────────────────────────────────────────────────────

function Skeleton() {
  return (
    <div>
      {/* Hero skeleton */}
      <div className="py-12 animate-pulse" style={{ background: 'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)' }}>
        <div className="site-container text-center space-y-4">
          <div className="w-24 h-24 rounded-full bg-white/30 mx-auto" />
          <div className="h-8 w-64 bg-white/30 rounded-xl mx-auto" />
          <div className="h-4 w-40 bg-white/20 rounded-lg mx-auto" />
        </div>
      </div>
      {/* Body skeleton */}
      <div className="site-container py-10 space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="h-24 skeleton rounded-2xl" />
        ))}
      </div>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────

export default function FlashDealDetailPage() {
  const { id } = useParams<{ id: string; slug?: string }>()

  const { data: deal, isLoading, isError } = useQuery({
    queryKey: ['flash-deal-detail', id],
    queryFn: () => publicApi.getFlashDiscountDetail(Number(id)).then((r) => r.data.data),
    enabled: !!id,
    staleTime: 60 * 1000,
    refetchInterval: 60 * 1000, // refresh timer every minute
  })

  // ── Loading ──────────────────────────────────────────────────────────────
  if (isLoading) return <Skeleton />

  // ── Error / not found ────────────────────────────────────────────────────
  if (isError || !deal) {
    return (
      <div className="site-container py-20 flex flex-col items-center text-center gap-4">
        <div className="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
          <AlertCircle size={28} className="text-red-500" />
        </div>
        <h2 className="text-xl font-bold text-slate-800">Flash Deal Not Found</h2>
        <p className="text-slate-500 max-w-sm">
          This deal may have expired or been removed. Check active flash deals for the latest offers.
        </p>
        <Link
          to="/flash-deals"
          className="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white"
          style={{ background: 'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)' }}
        >
          <Zap size={16} className="fill-white" /> View Flash Deals
        </Link>
      </div>
    )
  }

  const expired = new Date(deal.valid_until).getTime() <= Date.now()
  const logoSrc = getImageUrl(deal.merchant_logo)

  return (
    <div>
      <Helmet>
        <title>{`${deal.title} – ${deal.discount_percentage}% Flash Deal | Deal Machan`}</title>
        <meta name="description" content={`${deal.discount_percentage}% OFF at ${deal.merchant_name}. Limited time flash deal on Deal Machan.`} />
      </Helmet>
      {/* ── Hero / gradient header ─────────────────────────────────────── */}
      <div className="py-12 relative overflow-hidden">
        {/* Banner photo — behind everything */}
        {deal.banner_image && (
          <img
            src={deal.banner_image}
            alt=""
            aria-hidden
            className="absolute inset-0 w-full h-full object-cover"
          />
        )}
        {/* Gradient overlay — sits over the photo */}
        <div
          className="absolute inset-0"
          style={{ background: 'linear-gradient(135deg, rgba(220,62,62,0.88) 0%, rgba(200,60,10,0.88) 100%)' }}
        />
        <div className="site-container relative z-10">
          {/* Back nav */}
          <Link
            to="/flash-deals"
            className="inline-flex items-center gap-2 text-white/80 hover:text-white text-sm mb-6 transition-colors"
          >
            <ArrowLeft size={15} /> All Flash Deals
          </Link>

          <div className="flex flex-col sm:flex-row items-center sm:items-start gap-6">
            {/* Merchant logo */}
            <div className="w-24 h-24 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center flex-shrink-0 overflow-hidden border-2 border-white/30">
              {deal.merchant_logo ? (
                <img src={logoSrc} alt={deal.merchant_name} loading="lazy" className="w-full h-full object-cover" />
              ) : (
                <Zap size={36} className="text-white fill-white" />
              )}
            </div>

            {/* Title + meta */}
            <div className="text-center sm:text-left flex-1">
              {/* Flash badge */}
              <div className="inline-flex items-center gap-2 px-3 py-1 bg-white/20 rounded-full text-white text-xs font-medium mb-3">
                <Zap size={12} className="fill-white" /> Flash Deal
              </div>
              <h1 className="font-heading font-black text-2xl sm:text-3xl text-white mb-2 leading-snug">
                {deal.title}
              </h1>
              <p className="text-white/80 text-sm mb-4">{deal.merchant_name}</p>

              {/* Discount + timer row */}
              <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                {/* Big % badge */}
                <div className="flex items-center gap-2 bg-white/20 backdrop-blur rounded-2xl px-4 py-2">
                  <span className="font-black text-4xl text-white leading-none">{deal.discount_percentage}%</span>
                  <span className="text-white/80 text-sm font-semibold leading-tight">
                    OFF
                  </span>
                </div>

                {/* Timer */}
                <div
                  className={`flex items-center gap-2 rounded-2xl px-4 py-2 ${
                    expired ? 'bg-white/30' : 'bg-white/20 backdrop-blur'
                  }`}
                >
                  <Clock size={16} className="text-white" />
                  <span className={`text-sm font-bold ${expired ? 'text-red-100' : 'text-white'}`}>
                    {expired ? 'Expired' : timeLeft(deal.valid_until)}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ── Body ──────────────────────────────────────────────────────────── */}
      <div className="site-container py-10 space-y-6 max-w-3xl">

        {/* ── In-store notice ──────────────────────────────────────────── */}
        <div className="card p-4 flex gap-3 items-start border-l-4 border-orange-400 bg-orange-50">
          <Info size={18} className="text-orange-500 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm font-semibold text-orange-700 mb-0.5">Redeemed In-Store</p>
            <p className="text-xs text-orange-600">
              Show this deal at the merchant&apos;s store at the time of purchase. No code needed — the discount is applied directly.
            </p>
          </div>
        </div>

        {/* ── Description ──────────────────────────────────────────────── */}
        {deal.description && (
          <div className="card p-6">
            <h2 className="font-semibold text-slate-800 mb-2">About This Deal</h2>
            <p className="text-slate-600 text-sm leading-relaxed whitespace-pre-line">{deal.description}</p>
          </div>
        )}

        {/* ── Validity ─────────────────────────────────────────────────── */}
        <div className="card p-6">
          <h2 className="font-semibold text-slate-800 mb-4">Validity</h2>
          <div className="grid grid-cols-2 gap-4 text-sm">
            {deal.valid_from && (
              <div>
                <span className="text-slate-400 text-xs uppercase tracking-wide">From</span>
                <p className="font-medium text-slate-700 mt-1">{formatDate(deal.valid_from)}</p>
              </div>
            )}
            <div>
              <span className="text-slate-400 text-xs uppercase tracking-wide">Until</span>
              <p className={`font-medium mt-1 ${expired ? 'text-red-500' : 'text-slate-700'}`}>
                {formatDate(deal.valid_until)}
              </p>
            </div>
            {deal.max_redemptions != null && (
              <div>
                <span className="text-slate-400 text-xs uppercase tracking-wide">Redemptions</span>
                <p className="font-medium text-slate-700 mt-1">
                  {deal.current_redemptions} / {deal.max_redemptions}
                </p>
              </div>
            )}
          </div>
        </div>

        {/* ── Merchant ─────────────────────────────────────────────────── */}
        <div className="card p-6">
          <h2 className="font-semibold text-slate-800 mb-4">Merchant</h2>
          <Link to={`/stores/${deal.merchant_id}/${slugify(deal.merchant_name)}`} className="flex items-center gap-4 group">
            <div className="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden flex-shrink-0">
              {deal.merchant_logo ? (
                <img src={logoSrc} alt={deal.merchant_name} loading="lazy" className="w-full h-full object-cover" />
              ) : (
                <Store size={22} className="text-slate-400" />
              )}
            </div>
            <div className="flex-1 min-w-0">
              <p className="font-semibold text-slate-800 group-hover:text-brand-600 transition-colors truncate">
                {deal.merchant_name}
              </p>
              {deal.merchant_category && (
                <span className="badge badge-brand text-xs mt-1">{deal.merchant_category}</span>
              )}
              {deal.merchant_description && (
                <p className="text-xs text-slate-500 mt-1 line-clamp-2">{deal.merchant_description}</p>
              )}
            </div>
            <ArrowLeft size={16} className="text-slate-300 rotate-180 group-hover:text-brand-500 transition-colors flex-shrink-0" />
          </Link>
        </div>

        {/* ── Store location ───────────────────────────────────────────── */}
        {deal.store_id && (
          <div className="card p-6">
            <h2 className="font-semibold text-slate-800 mb-4">Store Location</h2>
            <div className="flex items-start gap-3">
              <div className="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0">
                <MapPin size={18} className="text-orange-500" />
              </div>
              <div className="space-y-1 text-sm">
                {deal.store_name && (
                  <p className="font-semibold text-slate-800">{deal.store_name}</p>
                )}
                {deal.store_address && (
                  <p className="text-slate-600">{deal.store_address}</p>
                )}
                {(deal.area_name || deal.city_name) && (
                  <p className="text-slate-500">
                    {[deal.area_name, deal.city_name].filter(Boolean).join(', ')}
                  </p>
                )}
                {deal.store_phone && (
                  <a
                    href={`tel:${deal.store_phone}`}
                    className="inline-flex items-center gap-1.5 text-brand-600 hover:text-brand-700 font-medium mt-2"
                  >
                    <Phone size={13} /> {deal.store_phone}
                  </a>
                )}
              </div>
            </div>
          </div>
        )}

        {/* ── Back link ────────────────────────────────────────────────── */}
        <div className="pt-2 pb-6">
          <Link
            to="/flash-deals"
            className="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700 text-sm transition-colors"
          >
            <ArrowLeft size={14} /> Back to all flash deals
          </Link>
        </div>
      </div>
    </div>
  )
}
