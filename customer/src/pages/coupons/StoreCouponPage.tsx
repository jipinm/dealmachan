import { useQuery } from '@tanstack/react-query'
import { Store, MapPin, Tag, Clock, CheckCircle2, QrCode } from 'lucide-react'
import { useState, useEffect } from 'react'
import { QRCodeSVG } from 'qrcode.react'
import { couponsApi, StoreCoupon } from '@/api/endpoints/coupons'
import { getImageUrl } from '@/lib/imageUrl'
import PageLoader from '@/components/ui/PageLoader'

// ── Helpers ───────────────────────────────────────────────────────────────────

function fmtDate(dateStr: string | null) {
  if (!dateStr) return null
  return new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }).format(
    new Date(dateStr)
  )
}

function isExpired(validUntil: string | null) {
  if (!validUntil) return false
  return new Date(validUntil) < new Date()
}

// ── QR Modal ─────────────────────────────────────────────────────────────────

function QrModal({ coupon, onClose }: { coupon: StoreCoupon; onClose: () => void }) {
  useEffect(() => {
    const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose() }
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [onClose])

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Coupon QR code"
      className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4"
      onClick={onClose}
    >
      <div
        className="bg-white rounded-3xl p-6 max-w-xs w-full shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <p className="font-heading font-bold text-lg text-slate-800 text-center mb-1">
          {coupon.merchant_name}
        </p>
        {coupon.store_name && (
          <p className="text-xs text-slate-400 text-center mb-4">{coupon.store_name}</p>
        )}
        <div className="flex justify-center mb-4">
          <QRCodeSVG value={coupon.coupon_code} size={180} level="M" />
        </div>
        <div className="text-center mb-4">
          <p className="text-xs text-slate-400 mb-1">Coupon Code</p>
          <span className="font-mono font-bold text-lg text-slate-800 tracking-widest bg-slate-50 px-3 py-1.5 rounded-xl">
            {coupon.coupon_code}
          </span>
        </div>
        <div className="text-center">
          <span className="text-2xl font-extrabold text-brand-600">
            {coupon.discount_type === 'percentage'
              ? `${coupon.discount_value}% OFF`
              : `₹${coupon.discount_value} OFF`}
          </span>
        </div>
        <button onClick={onClose} className="mt-4 w-full btn-ghost !rounded-xl !py-2.5 !text-sm">
          Close
        </button>
      </div>
    </div>
  )
}

// ── Coupon Card ───────────────────────────────────────────────────────────────

function StoreCouponCard({ coupon }: { coupon: StoreCoupon }) {
  const [showQr, setShowQr] = useState(false)
  const expired = isExpired(coupon.valid_until) || coupon.status === 'expired'
  const redeemed = coupon.is_redeemed === 1

  const statusBadge = redeemed
    ? <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Redeemed</span>
    : expired
      ? <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600">Expired</span>
      : <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Active</span>

  return (
    <>
      <div className={`card p-4 transition-all ${redeemed || expired ? 'opacity-60' : 'card-hover'}`}>
        {/* Header */}
        <div className="flex items-start gap-3 mb-3">
          <div className="w-11 h-11 rounded-xl bg-slate-100 overflow-hidden flex-none flex items-center justify-center">
            {coupon.merchant_logo ? (
              <img src={getImageUrl(coupon.merchant_logo)} alt={coupon.merchant_name} loading="lazy" className="w-full h-full object-cover" />
            ) : (
              <Store size={20} className="text-slate-400" />
            )}
          </div>
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 flex-wrap">
              <span className="font-semibold text-slate-800 text-sm leading-tight truncate">{coupon.merchant_name}</span>
              {statusBadge}
            </div>
            {coupon.store_name && (
              <span className="flex items-center gap-1 text-xs text-slate-400 mt-0.5">
                <MapPin size={10} /> {coupon.store_name}
              </span>
            )}
          </div>
        </div>

        {/* Discount */}
        <div className="bg-gradient-to-r from-brand-50 to-purple-50 rounded-2xl px-4 py-3 mb-3 text-center">
          <p className="text-2xl font-extrabold text-brand-600">
            {coupon.discount_type === 'percentage'
              ? `${coupon.discount_value}% OFF`
              : `₹${coupon.discount_value} OFF`}
          </p>
          <p className="font-mono text-sm text-slate-500 mt-0.5 tracking-widest">{coupon.coupon_code}</p>
        </div>

        {/* Meta row */}
        <div className="flex items-center justify-between text-xs text-slate-400 mb-3">
          <span className="flex items-center gap-1">
            <Tag size={11} /> Gifted {fmtDate(coupon.gifted_at) ?? '—'}
          </span>
          {coupon.valid_until && (
            <span className="flex items-center gap-1">
              <Clock size={11} />
              {expired ? 'Expired' : `Valid until ${fmtDate(coupon.valid_until)}`}
            </span>
          )}
        </div>

        {/* Action */}
        {redeemed ? (
          <div className="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-amber-50 text-amber-700 text-sm font-medium">
            <CheckCircle2 size={15} /> Redeemed {fmtDate(coupon.redeemed_at) ?? ''}
          </div>
        ) : !expired ? (
          <button
            onClick={() => setShowQr(true)}
            className="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl
                       bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold
                       transition-colors active:scale-95"
          >
            <QrCode size={15} /> Show QR to Redeem
          </button>
        ) : null}
      </div>

      {showQr && <QrModal coupon={coupon} onClose={() => setShowQr(false)} />}
    </>
  )
}

// ── Main Page ─────────────────────────────────────────────────────────────────

export default function StoreCouponPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['store-coupons'],
    queryFn: couponsApi.getStoreCoupons,
    staleTime: 60_000,
  })

  const coupons = data ?? []
  const active  = coupons.filter(c => c.is_redeemed === 0 && !isExpired(c.valid_until) && c.status === 'active')
  const others  = coupons.filter(c => c.is_redeemed !== 0 || isExpired(c.valid_until) || c.status !== 'active')

  if (isLoading) return <PageLoader />

  if (isError) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <p className="text-slate-400 text-sm">Could not load store coupons.</p>
      </div>
    )
  }

  return (
    <div className="max-w-[1200px] mx-auto pb-10">

      {/* Header */}
      <div className="px-4 pt-6 pb-4">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-2xl gradient-brand flex items-center justify-center">
            <Store size={18} className="text-white" />
          </div>
          <div>
            <h1 className="font-heading font-bold text-xl text-slate-800 leading-tight">Store Coupons</h1>
            <p className="text-xs text-slate-400">Exclusive coupons gifted to you by merchants</p>
          </div>
        </div>
      </div>

      {coupons.length === 0 ? (
        <div className="mx-4 mt-4 rounded-3xl border border-dashed border-slate-200 bg-slate-50 py-16 text-center">
          <div className="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4">
            <Store size={26} className="text-slate-300" />
          </div>
          <p className="font-semibold text-slate-600 mb-1">No Store Coupons Yet</p>
          <p className="text-sm text-slate-400 max-w-xs mx-auto leading-relaxed">
            Merchants can send exclusive coupons directly to you. Check back soon!
          </p>
        </div>
      ) : (
        <div className="px-4 space-y-6">

          {active.length > 0 && (
            <section>
              <h2 className="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
                Active ({active.length})
              </h2>
              <div className="space-y-3">
                {active.map(c => <StoreCouponCard key={c.id} coupon={c} />)}
              </div>
            </section>
          )}

          {others.length > 0 && (
            <section>
              <h2 className="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
                Used &amp; Expired ({others.length})
              </h2>
              <div className="space-y-3">
                {others.map(c => <StoreCouponCard key={c.id} coupon={c} />)}
              </div>
            </section>
          )}

        </div>
      )}
    </div>
  )
}
