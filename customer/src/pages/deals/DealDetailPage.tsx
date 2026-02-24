import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import {
  Tag, Clock, Star, MapPin, ChevronRight, Copy, Check,
  Bookmark, BookmarkCheck, Share2, Loader2, Store, AlertCircle, LogIn,
} from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import { couponsApi } from '@/api/endpoints/coupons'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import AuthModal from '@/components/ui/AuthModal'
import toast from 'react-hot-toast'

// ── Helpers ───────────────────────────────────────────────────────────────
function daysLeft(until: string | null): { text: string; urgent: boolean } | null {
  if (!until) return null
  const diff = Math.ceil((new Date(until).getTime() - Date.now()) / 86_400_000)
  if (diff < 0) return null
  if (diff === 0) return { text: 'Expires today!', urgent: true }
  if (diff <= 3) return { text: `${diff}d left`, urgent: true }
  return { text: `${diff} days left`, urgent: false }
}

import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'
import { Helmet } from 'react-helmet-async'

function imgSrc(path: string | null): string {
  return getImageUrl(path, 'https://via.placeholder.com/800x400?text=Deal')
}

function discountLabel(type: string, value: number): string {
  switch (type) {
    case 'percentage': return `${value}% OFF`
    case 'flat':
    case 'fixed':      return `₹${value} OFF`
    case 'free_item':  return 'FREE ITEM'
    case 'bogo':       return 'BUY 1 GET 1'
    default:           return `${value} OFF`
  }
}

function discountGradient(type: string): string {
  switch (type) {
    case 'percentage': return 'from-brand-500 to-purple-600'
    case 'flat':
    case 'fixed':      return 'from-emerald-500 to-teal-600'
    case 'free_item':  return 'from-orange-400 to-pink-500'
    case 'bogo':       return 'from-rose-500 to-red-600'
    default:           return 'from-brand-500 to-brand-700'
  }
}

// ── Main Page ─────────────────────────────────────────────────────────────
export default function DealDetailPage() {
  const { id } = useParams<{ id: string; slug?: string }>()
  const couponId = Number(id)
  const { isAuthenticated } = useAuthStore()
  const [saved, setSaved] = useState(false)
  const [codeCopied, setCodeCopied] = useState(false)
  const [showAuthModal, setShowAuthModal] = useState(false)

  const { data, isLoading, error } = useQuery({
    queryKey: ['deal-detail', couponId],
    queryFn: () => publicApi.getCouponDetail(couponId).then((r) => r.data.data),
    enabled: !!couponId,
  })

  const saveMutation = useMutation({
    mutationFn: () => couponsApi.subscribe(couponId),
    onSuccess: () => {
      setSaved(true)
      toast.success('Coupon saved to your wallet!')
    },
    onError: (err) => {
      toast.error(getApiError(err))
    },
  })

  // ── Loading skeleton ────────────────────────────────────────────────
  if (isLoading) {
    return (
      <div className="site-container py-10">
        <div className="max-w-[1200px] mx-auto space-y-4">
          <div className="h-64 skeleton rounded-3xl" />
          <div className="h-8 skeleton rounded-xl w-3/4" />
          <div className="h-5 skeleton rounded-xl w-1/2" />
          <div className="h-20 skeleton rounded-2xl" />
        </div>
      </div>
    )
  }

  // ── Error / not found ───────────────────────────────────────────────
  if (error || !data) {
    return (
      <div className="site-container py-20 text-center text-slate-400">
        <Tag size={48} className="mx-auto mb-4 opacity-30" />
        <p className="font-semibold text-lg">Deal not found</p>
        <p className="text-sm mt-1">This deal may have expired or been removed.</p>
        <Link to="/deals" className="mt-6 btn-primary !px-6 !py-2.5 !text-sm !rounded-xl inline-flex items-center gap-2">
          Browse All Deals
        </Link>
      </div>
    )
  }

  const { coupon, merchant, stores } = data as any
  const discount = discountLabel(coupon.discount_type, coupon.discount_value)
  const gradient = discountGradient(coupon.discount_type)
  const expiry = daysLeft(coupon.valid_until)

  function handleCopyCode() {
    if (!coupon.coupon_code) return
    navigator.clipboard.writeText(coupon.coupon_code).then(() => {
      setCodeCopied(true)
      toast.success('Code copied!')
      setTimeout(() => setCodeCopied(false), 2000)
    })
  }

  async function handleShare() {
    const url = window.location.href
    const text = `${coupon.title} — ${discount} at ${merchant.business_name}`
    if (navigator.share) {
      await navigator.share({ title: text, url })
    } else {
      navigator.clipboard.writeText(url)
      toast.success('Link copied!')
    }
  }

  function handleSave() {
    if (!isAuthenticated) {
      setShowAuthModal(true)
      return
    }
    saveMutation.mutate()
  }

  return (
    <div>
      <Helmet>
        <title>{coupon.title} | Deal Machan</title>
        <meta name="description" content={`${discountLabel(coupon.discount_type, coupon.discount_value)} at ${coupon.merchant_name ?? 'local merchant'}. Valid until ${coupon.valid_until ?? 'limited time'}. Claim your deal on Deal Machan.`} />
      </Helmet>
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
        <div className="max-w-[1200px] mx-auto">

          {/* ── Coupon Card ─────────────────────────────────────────── */}
          <div className={`rounded-3xl bg-gradient-to-br ${gradient} p-px shadow-lg mb-6`}>
            <div className="rounded-3xl bg-white overflow-hidden">
              {/* Banner image */}
              {coupon.banner_image && (
                <div className="relative h-52 overflow-hidden rounded-t-3xl">
                  <img
                    src={imgSrc(coupon.banner_image)}
                    alt={coupon.title}
                    className="w-full h-full object-cover"
                    onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/800x400?text=Deal' }}
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent" />
                </div>
              )}
              {/* Gradient header */}
              <div className={`bg-gradient-to-r ${gradient} px-6 md:px-8 pt-8 pb-6 text-white relative`}>
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-widest opacity-80 mb-2">
                      {merchant.business_name}
                    </p>
                    <p className="font-heading font-black text-4xl md:text-5xl leading-none mb-2">
                      {discount}
                    </p>
                    <p className="text-white/90 text-sm md:text-base leading-snug">{coupon.title}</p>
                  </div>
                  {/* Action buttons */}
                  <div className="flex items-center gap-2">
                    <button
                      onClick={handleShare}
                      className="w-10 h-10 rounded-xl bg-white/15 hover:bg-white/25 flex items-center justify-center transition-colors"
                      title="Share"
                    >
                      <Share2 size={16} />
                    </button>
                    <button
                      onClick={handleSave}
                      disabled={saved || saveMutation.isPending}
                      className={`w-10 h-10 rounded-xl flex items-center justify-center transition-colors ${
                        saved
                          ? 'bg-white/30'
                          : 'bg-white/15 hover:bg-white/25'
                      }`}
                      title={saved ? 'Saved to wallet' : 'Save to wallet'}
                    >
                      {saveMutation.isPending
                        ? <Loader2 size={16} className="animate-spin" />
                        : saved ? <BookmarkCheck size={16} /> : <Bookmark size={16} />
                      }
                    </button>
                  </div>
                </div>
                {expiry && (
                  <div className={`inline-flex items-center gap-1 mt-4 text-xs font-semibold px-3 py-1 rounded-full ${
                    expiry.urgent ? 'bg-red-500/30 text-white' : 'bg-white/15 text-white/90'
                  }`}>
                    <Clock size={11} /> {expiry.text}
                  </div>
                )}
              </div>

              {/* Dashed separator with punched circles */}
              <div className="flex items-center px-4">
                <div className={`w-5 h-5 -ml-6 rounded-full bg-[#f8f9fc] shrink-0`} />
                <div className="flex-1 border-t-2 border-dashed border-slate-200 mx-2" />
                <div className={`w-5 h-5 -mr-6 rounded-full bg-[#f8f9fc] shrink-0`} />
              </div>

              {/* Code section */}
              <div className="p-6 md:px-8">
                {(coupon.coupon_code || !isAuthenticated) ? (
                  <div className="flex flex-col sm:flex-row items-center gap-4">
                    <div className="flex-1 text-center sm:text-left">
                      <p className="text-xs text-slate-500 mb-1 font-medium uppercase tracking-wide">Coupon Code</p>
                      {isAuthenticated ? (
                        <button
                          onClick={handleCopyCode}
                          className="inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-slate-50 border border-dashed border-slate-300 hover:bg-brand-50 hover:border-brand-300 transition-all"
                        >
                          <span className="font-mono font-black text-2xl tracking-[.2em] text-slate-800">
                            {coupon.coupon_code}
                          </span>
                          {codeCopied
                            ? <Check size={18} className="text-emerald-500" />
                            : <Copy size={16} className="text-slate-400" />
                          }
                        </button>
                      ) : (
                        <div className="text-center sm:text-left">
                          <div className="font-mono font-black text-2xl tracking-[.2em] text-slate-300 blur-sm select-none mb-3">
                            XXXXXXXX
                          </div>
                          <button
                            onClick={() => setShowAuthModal(true)}
                            className="btn-primary !py-2.5 !px-5 !text-sm !rounded-xl inline-flex items-center gap-2"
                          >
                            <LogIn size={15} /> Sign In to Reveal Code
                          </button>
                          <p className="text-xs text-slate-400 mt-2">
                            Don&apos;t have an account?{' '}
                            <button
                              onClick={() => { setShowAuthModal(true) }}
                              className="text-brand-600 font-medium hover:underline"
                            >
                              Register free
                            </button>
                          </p>
                        </div>
                      )}
                    </div>
                    {isAuthenticated && (
                      <button
                        onClick={handleSave}
                        disabled={saved || saveMutation.isPending}
                        className={`flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold transition-all ${
                          saved
                            ? 'bg-brand-50 text-brand-600 border border-brand-200'
                            : 'bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 border border-slate-200'
                        }`}
                      >
                        {saveMutation.isPending
                          ? <Loader2 size={16} className="animate-spin" />
                          : saved ? <BookmarkCheck size={16} /> : <Bookmark size={16} />
                        }
                        {saved ? 'Saved to Wallet' : 'Save to Wallet'}
                      </button>
                    )}
                  </div>
                ) : (
                  <div className="flex items-center gap-2 text-sm text-slate-500 bg-slate-50 px-4 py-3 rounded-xl">
                    <AlertCircle size={15} />
                    <span>No code needed — just show this page at the store to redeem.</span>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* ── Merchant row ────────────────────────────────────────── */}
          <Link
            to={`/stores/${merchant.id}/${slugify(merchant.business_name)}`}
            className="flex items-center gap-3 p-4 bg-white rounded-2xl mb-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow"
          >
            {merchant.business_logo ? (
              <img
                src={imgSrc(merchant.business_logo)}
                alt={merchant.business_name}
                className="w-12 h-12 rounded-xl object-cover bg-slate-100"
              />
            ) : (
              <div className="w-12 h-12 rounded-xl gradient-brand flex items-center justify-center text-white font-bold text-lg">
                {merchant.business_name.charAt(0)}
              </div>
            )}
            <div className="flex-1 min-w-0">
              <p className="font-semibold text-slate-800 text-sm">{merchant.business_name}</p>
              <div className="flex items-center gap-1 text-xs text-slate-500">
                <Star size={11} className="text-yellow-400 fill-yellow-400" />
                <span>{Number(merchant.avg_rating ?? 0).toFixed(1)} ({merchant.total_reviews} reviews)</span>
              </div>
            </div>
            <ChevronRight size={16} className="text-slate-400" />
          </Link>

          {/* ── Deal details grid ──────────────────────────────────── */}
          <div className="grid sm:grid-cols-2 gap-4 mb-6">
            {/* Min purchase */}
            {coupon.min_purchase_amount > 0 && (
              <div className="flex items-center gap-3 text-sm text-slate-700 bg-amber-50 border border-amber-100 rounded-2xl px-5 py-4">
                <span className="text-lg">💰</span>
                <div>
                  <p className="font-semibold">Minimum Purchase</p>
                  <p className="text-amber-700 font-bold">₹{coupon.min_purchase_amount}</p>
                </div>
              </div>
            )}
            {/* Max discount */}
            {coupon.max_discount_amount > 0 && (
              <div className="flex items-center gap-3 text-sm text-slate-700 bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4">
                <span className="text-lg">🎯</span>
                <div>
                  <p className="font-semibold">Max Discount</p>
                  <p className="text-blue-700 font-bold">₹{coupon.max_discount_amount}</p>
                </div>
              </div>
            )}
          </div>

          {/* ── Terms ──────────────────────────────────────────────── */}
          {coupon.terms_and_conditions && (
            <div className="bg-white rounded-2xl p-5 border border-slate-100 mb-6">
              <h3 className="font-semibold text-slate-700 mb-2 text-sm">Terms & Conditions</h3>
              <p className="text-sm text-slate-500 leading-relaxed whitespace-pre-line">{coupon.terms_and_conditions}</p>
            </div>
          )}

          {/* ── Valid Stores ────────────────────────────────────────── */}
          {(stores as any[]).length > 0 && (
            <div className="bg-white rounded-2xl p-5 border border-slate-100">
              <h3 className="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                <Store size={16} className="text-brand-500" /> Available at {(stores as any[]).length} store{(stores as any[]).length > 1 ? 's' : ''}
              </h3>
              <div className="space-y-3">
                {(stores as any[]).map((store: any, i: number) => (
                  <div key={i} className="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                    <MapPin size={16} className="text-cta-500 flex-shrink-0 mt-0.5" />
                    <div>
                      <p className="font-medium text-slate-800 text-sm">{store.store_name ?? store.name}</p>
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

      <AuthModal
        isOpen={showAuthModal}
        onClose={() => setShowAuthModal(false)}
        prompt="Sign in to reveal & save this coupon"
        onSuccess={() => saveMutation.mutate()}
      />
    </div>
  )
}
