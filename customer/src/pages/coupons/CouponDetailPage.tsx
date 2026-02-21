import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import { Bookmark, BookmarkCheck, ChevronLeft, Clock, Copy, Share2, Store, Loader2 } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import { couponsApi } from '@/api/endpoints/coupons'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import SkeletonCard, { SkeletonRow } from '@/components/ui/SkeletonCard'
import toast from 'react-hot-toast'

// Discount display helpers
function discountLabel(type: string, value: number): string {
  switch (type) {
    case 'percentage': return `${value}% OFF`
    case 'flat':       return `₹${value} OFF`
    case 'free_item':  return 'FREE ITEM'
    case 'bogo':       return 'BOGO'
    default:           return `${value} OFF`
  }
}

function discountGradient(type: string): string {
  switch (type) {
    case 'percentage': return 'from-brand-500 to-purple-600'
    case 'flat':       return 'from-emerald-500 to-teal-600'
    case 'free_item':  return 'from-orange-400 to-pink-500'
    case 'bogo':       return 'from-rose-500 to-red-600'
    default:           return 'from-brand-500 to-brand-700'
  }
}

function daysLeft(expiry: string | null): { text: string; urgent: boolean } | null {
  if (!expiry) return null
  const diff = Math.ceil((new Date(expiry).getTime() - Date.now()) / 86_400_000)
  if (diff < 0) return null
  if (diff === 0) return { text: 'Expires today!', urgent: true }
  if (diff <= 3) return { text: `${diff}d left`, urgent: true }
  return { text: `${diff} days left`, urgent: false }
}

export default function CouponDetailPage() {
  const { id } = useParams<{ id: string }>()
  const couponId = Number(id)
  const { isAuthenticated } = useAuthStore()
  const [saved, setSaved] = useState(false)
  const [codeCopied, setCodeCopied] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['coupon-detail', couponId],
    queryFn: () => publicApi.getCouponDetail(couponId).then((r) => r.data.data),
    enabled: !!couponId,
    staleTime: 60_000,
  })

  const saveMutation = useMutation({
    mutationFn: async () => {
      if (!isAuthenticated) throw new Error('Please log in to save coupons to your wallet')
      return couponsApi.saveCoupon(couponId)
    },
    onSuccess: () => {
      setSaved(true)
      toast.success('Coupon saved to your wallet!')
    },
    onError: (err) => {
      const msg = getApiError(err)
      if (msg.includes('login') || msg.includes('auth')) {
        toast.error('Please log in to save coupons')
      } else {
        toast.error(msg)
      }
    },
  })

  const coupon: any   = (data as any)?.coupon
  const merchant: any = (data as any)?.merchant
  const stores: any[] = (data as any)?.stores ?? []

  const handleCopyCode = () => {
    if (!coupon?.coupon_code) return
    navigator.clipboard.writeText(coupon.coupon_code).then(() => {
      setCodeCopied(true)
      setTimeout(() => setCodeCopied(false), 2000)
    })
  }

  const handleShare = async () => {
    const url  = window.location.href
    const text = coupon?.title ?? 'Check out this deal!'
    if (navigator.share) {
      await navigator.share({ title: text, url })
    } else {
      navigator.clipboard.writeText(url)
      toast.success('Link copied!')
    }
  }

  if (isLoading) {
    return (
      <div className="max-w-lg mx-auto px-4 py-5 space-y-4">
        <div className="skeleton h-44 rounded-2xl" />
        <SkeletonCard />
        <SkeletonRow />
        <SkeletonRow />
      </div>
    )
  }

  if (!coupon || !merchant) {
    return (
      <div className="text-center py-20 px-4">
        <p className="text-4xl mb-3">😕</p>
        <p className="text-gray-500">Coupon not found.</p>
        <Link to="/coupons" className="mt-4 inline-block text-brand-600 text-sm font-semibold hover:underline">Back to Deals</Link>
      </div>
    )
  }

  const expiry = daysLeft(coupon.expiry_date)
  const gradient = discountGradient(coupon.discount_type)

  return (
    <div className="max-w-lg mx-auto pb-10">
      {/* ── Back button ─────────────────────────────────────────────────── */}
      <div className="px-4 pt-5 mb-4 flex items-center justify-between">
        <Link to="/coupons" className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800">
          <ChevronLeft size={16} /> Back
        </Link>
        <button onClick={handleShare} className="text-gray-500 hover:text-gray-800">
          <Share2 size={18} />
        </button>
      </div>

      {/* ── Coupon hero card ─────────────────────────────────────────────── */}
      <div className="mx-4">
        <div className={`rounded-2xl bg-gradient-to-br ${gradient} p-px shadow-brand`}>
          <div className="rounded-2xl bg-white overflow-hidden">
            {/* Top gradient stripe */}
            <div className={`bg-gradient-to-r ${gradient} px-5 pt-6 pb-5 text-white`}>
              <p className="text-xs font-semibold uppercase tracking-widest opacity-80 mb-1">{merchant.business_name}</p>
              <p className="font-heading font-bold text-3xl leading-tight">{discountLabel(coupon.discount_type, coupon.discount_value)}</p>
              <p className="text-sm mt-1 opacity-90">{coupon.title}</p>
            </div>

            {/* Dashed separator */}
            <div className="flex items-center px-4 py-0">
              <div className="w-5 h-5 -ml-7 rounded-full bg-white border-2 border-gray-200 shrink-0" />
              <div className="flex-1 border-t-2 border-dashed border-gray-200 mx-2" />
              <div className="w-5 h-5 -mr-7 rounded-full bg-white border-2 border-gray-200 shrink-0" />
            </div>

            {/* Code + expiry row */}
            <div className="px-5 py-4 flex items-center justify-between gap-3">
              <div>
                {coupon.coupon_code ? (
                  <button
                    onClick={handleCopyCode}
                    className="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 hover:bg-brand-50 transition-colors"
                  >
                    <span className="font-mono font-bold text-gray-900 tracking-widest">{coupon.coupon_code}</span>
                    {codeCopied
                      ? <span className="text-xs text-emerald-600 font-semibold">Copied!</span>
                      : <Copy size={13} className="text-gray-400" />
                    }
                  </button>
                ) : (
                  <span className="text-xs text-gray-400 italic">No code needed, just show at store</span>
                )}
                {expiry && (
                  <p className={`text-xs mt-1.5 font-medium ${expiry.urgent ? 'text-red-500' : 'text-gray-400'}`}>
                    <Clock size={10} className="inline mr-0.5" /> {expiry.text}
                  </p>
                )}
              </div>

              {/* Save / wallet button */}
              <button
                onClick={() => saveMutation.mutate()}
                disabled={saved || saveMutation.isPending}
                className={`flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl transition-all ${
                  saved
                    ? 'bg-brand-50 text-brand-600'
                    : 'bg-gray-100 text-gray-500 hover:bg-brand-50 hover:text-brand-600'
                }`}
              >
                {saveMutation.isPending
                  ? <Loader2 size={16} className="animate-spin" />
                  : saved ? <BookmarkCheck size={16} /> : <Bookmark size={16} />
                }
                <span className="text-[10px] font-semibold">{saved ? 'Saved' : 'Save'}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* ── Details ─────────────────────────────────────────────────────── */}
      <div className="px-4 mt-6 space-y-4">
        {/* Merchant info */}
        <Link to={`/merchants/${merchant.id}`} className="card p-4 flex items-center gap-3 hover:shadow-md transition-shadow block">
          {merchant.business_logo ? (
            <img src={merchant.business_logo} alt={merchant.business_name} className="w-12 h-12 rounded-xl object-cover" />
          ) : (
            <div className="w-12 h-12 rounded-xl gradient-brand flex items-center justify-center text-white font-bold text-lg">
              {merchant.business_name.charAt(0)}
            </div>
          )}
          <div className="min-w-0">
            <p className="font-semibold text-gray-900 text-sm truncate">{merchant.business_name}</p>
            {merchant.business_category && <p className="text-xs text-gray-400">{merchant.business_category}</p>}
          </div>
          <ChevronLeft size={14} className="ml-auto rotate-180 text-gray-300" />
        </Link>

        {/* Terms & conditions */}
        {coupon.terms_and_conditions && (
          <div className="card p-4">
            <h3 className="font-semibold text-gray-900 text-sm mb-2">Terms & Conditions</h3>
            <p className="text-xs text-gray-500 leading-relaxed whitespace-pre-line">{coupon.terms_and_conditions}</p>
          </div>
        )}

        {/* Min purchase */}
        {coupon.min_purchase_amount > 0 && (
          <div className="flex items-center gap-2 text-sm text-gray-600 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
            <span>💰</span>
            <span>Minimum purchase: <span className="font-semibold text-amber-700">₹{coupon.min_purchase_amount}</span></span>
          </div>
        )}

        {/* Max discount cap */}
        {coupon.max_discount_amount > 0 && (
          <div className="flex items-center gap-2 text-sm text-gray-600 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
            <span>🎯</span>
            <span>Max discount: <span className="font-semibold text-blue-700">₹{coupon.max_discount_amount}</span></span>
          </div>
        )}

        {/* Valid stores */}
        {stores.length > 0 && (
          <div className="card p-4">
            <h3 className="font-semibold text-gray-900 text-sm mb-2 flex items-center gap-1.5">
              <Store size={14} className="text-brand-400" /> Valid Stores
            </h3>
            <div className="space-y-2">
              {stores.map((s: any) => (
                <div key={s.id} className="text-xs text-gray-500">
                  <p className="font-medium text-gray-700">{s.name}</p>
                  {s.address && <p>{s.address}{s.area_name ? `, ${s.area_name}` : ''}</p>}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
