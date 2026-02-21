import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { QRCodeSVG } from 'qrcode.react'
import { Bookmark, Gift, Clock, X, CheckCircle, XCircle, Loader2, Share2 } from 'lucide-react'
import { couponsApi } from '@/api/endpoints/coupons'
import type { WalletCoupon, GiftCoupon } from '@/api/endpoints/coupons'
import { getApiError } from '@/api/client'
import { SkeletonRow } from '@/components/ui/SkeletonCard'
import toast from 'react-hot-toast'
import { Link } from 'react-router-dom'

type WalletTab = 'saved' | 'gifts' | 'history'

// ── Small discount label helper ───────────────────────────────────────────────
function discountText(type: string, value: number) {
  switch (type) {
    case 'percentage': return `${value}% OFF`
    case 'flat':       return `₹${value} OFF`
    case 'free_item':  return 'FREE ITEM'
    case 'bogo':       return 'BOGO'
    default:           return `${value} OFF`
  }
}

// ── QR Redeem Modal ───────────────────────────────────────────────────────────
function QrModal({ coupon, onClose }: { coupon: WalletCoupon | GiftCoupon; onClose: () => void }) {
  const qrValue = (coupon as WalletCoupon).coupon_code ?? `COUPON:${coupon.id}`

  const handleShare = async () => {
    const text = `${coupon.title} — ${discountText(coupon.discount_type, coupon.discount_value)}`
    if (navigator.share) {
      await navigator.share({ title: text, text })
    } else {
      navigator.clipboard.writeText(qrValue)
      toast.success('Code copied!')
    }
  }

  return (
    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-4">
      <div className="bg-white w-full max-w-xs rounded-3xl p-6 relative animate-in slide-in-from-bottom">
        <button onClick={onClose} className="absolute top-4 right-4 p-1 rounded-full hover:bg-gray-100">
          <X size={18} className="text-gray-500" />
        </button>

        <p className="font-heading font-bold text-lg text-gray-900 text-center mb-1">{coupon.title}</p>
        <p className="text-xs text-gray-400 text-center mb-4">
          {coupon.merchant_name} · {discountText(coupon.discount_type, coupon.discount_value)}
        </p>

        {/* QR code */}
        <div className="flex justify-center mb-4">
          <div className="p-3 bg-white border-4 border-brand-100 rounded-2xl shadow-sm">
            <QRCodeSVG value={qrValue} size={180} level="M" />
          </div>
        </div>

        {/* Coupon code */}
        <div className="text-center mb-4">
          <p className="text-xs text-gray-400 mb-1">Coupon Code</p>
          <p className="font-mono font-bold text-2xl tracking-widest text-gray-900">{qrValue}</p>
        </div>

        {/* Validity */}
        {coupon.valid_until && (
          <p className="text-center text-xs text-gray-400 mb-4">
            <Clock size={11} className="inline mr-1" />
            Valid until {new Date(coupon.valid_until).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
          </p>
        )}

        <div className="flex gap-2">
          <button onClick={handleShare} className="flex-1 btn-outline text-sm py-2.5 flex items-center justify-center gap-1.5">
            <Share2 size={14} /> Share
          </button>
          <button onClick={onClose} className="flex-1 btn-primary text-sm py-2.5">
            Done
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Saved coupon card ─────────────────────────────────────────────────────────
function SavedCouponCard({ coupon, onRedeem }: { coupon: WalletCoupon; onRedeem: () => void }) {
  const qc = useQueryClient()
  const removeMutation = useMutation({
    mutationFn: () => couponsApi.unsaveCoupon(coupon.coupon_id ?? (coupon as any).id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['wallet'] })
      toast.success('Removed from wallet')
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  return (
    <div className="card p-4 flex gap-3 items-start">
      <div className="w-12 h-12 rounded-xl gradient-brand flex items-center justify-center shrink-0 text-white font-bold text-sm">
        {coupon.merchant_name?.charAt(0)?.toUpperCase() ?? '?'}
      </div>
      <div className="flex-1 min-w-0">
        <p className="font-semibold text-gray-900 text-sm truncate">{coupon.title}</p>
        <p className="text-xs text-gray-400">{coupon.merchant_name}</p>
        <div className="flex items-center gap-2 mt-1.5">
          <span className="text-xs font-semibold text-brand-700 bg-brand-50 px-2 py-0.5 rounded-full">
            {discountText(coupon.discount_type, coupon.discount_value)}
          </span>
          {coupon.valid_until && (
            <span className="text-[10px] text-gray-400">
              Exp {new Date(coupon.valid_until).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}
            </span>
          )}
        </div>
      </div>
      <div className="flex flex-col gap-1 shrink-0">
        <button onClick={onRedeem} className="btn-primary text-xs px-3 py-1.5 rounded-lg">
          Redeem
        </button>
        <button
          onClick={() => removeMutation.mutate()}
          disabled={removeMutation.isPending}
          className="text-[10px] text-gray-400 text-center hover:text-red-500 transition-colors"
        >
          {removeMutation.isPending ? <Loader2 size={11} className="animate-spin mx-auto" /> : 'Remove'}
        </button>
      </div>
    </div>
  )
}

// ── Gift coupon card ──────────────────────────────────────────────────────────
function GiftCouponCard({ gift }: { gift: GiftCoupon }) {
  const qc = useQueryClient()

  const acceptMutation = useMutation({
    mutationFn: () => couponsApi.acceptGift(gift.gift_id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['wallet'] })
      toast.success('Gift coupon accepted! Check your Saved tab.')
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  const rejectMutation = useMutation({
    mutationFn: () => couponsApi.rejectGift(gift.gift_id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['wallet'] })
      toast('Gift coupon declined.', { icon: '👋' })
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  const isPending = acceptMutation.isPending || rejectMutation.isPending

  return (
    <div className="card p-4 border border-amber-100 bg-amber-50/50">
      <div className="flex items-start gap-3">
        <div className="w-11 h-11 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
          <Gift size={18} className="text-amber-600" />
        </div>
        <div className="flex-1 min-w-0">
          <p className="font-semibold text-gray-900 text-sm">{gift.title}</p>
          <p className="text-xs text-gray-500">{gift.merchant_name}</p>
          <span className="text-xs font-semibold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full mt-1 inline-block">
            {discountText(gift.discount_type, gift.discount_value)}
          </span>
          {gift.gifted_at && (
            <p className="text-[10px] text-gray-400 mt-1">
              Received {new Date(gift.gifted_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
            </p>
          )}
        </div>
      </div>

      {gift.acceptance_status === 'pending' && (
        <div className="flex gap-2 mt-3">
          <button
            onClick={() => acceptMutation.mutate()}
            disabled={isPending}
            className="flex-1 btn-primary text-xs py-2 flex items-center justify-center gap-1"
          >
            {acceptMutation.isPending ? <Loader2 size={12} className="animate-spin" /> : <CheckCircle size={12} />}
            Accept Gift
          </button>
          <button
            onClick={() => rejectMutation.mutate()}
            disabled={isPending}
            className="flex-1 btn-outline text-xs py-2 flex items-center justify-center gap-1 text-gray-500"
          >
            {rejectMutation.isPending ? <Loader2 size={12} className="animate-spin" /> : <XCircle size={12} />}
            Decline
          </button>
        </div>
      )}
      {gift.acceptance_status === 'accepted' && (
        <p className="text-xs text-emerald-600 font-semibold mt-2 flex items-center gap-1">
          <CheckCircle size={12} /> Accepted — check Saved tab
        </p>
      )}
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────────
export default function CouponWalletPage() {
  const [tab, setTab] = useState<WalletTab>('saved')
  const [redeemCoupon, setRedeemCoupon] = useState<WalletCoupon | GiftCoupon | null>(null)
  const [historyPage, setHistoryPage] = useState(1)

  const { data: walletData, isLoading: walletLoading } = useQuery({
    queryKey: ['wallet'],
    queryFn: () => couponsApi.getWallet().then((r) => r.data.data),
    staleTime: 30_000,
  })

  const { data: historyData, isLoading: historyLoading } = useQuery({
    queryKey: ['coupon-history', historyPage],
    queryFn: () => couponsApi.getHistory({ page: historyPage, per_page: 15 }).then((r) => r.data),
    enabled: tab === 'history',
    staleTime: 60_000,
  })

  const saved: WalletCoupon[]   = (walletData as any)?.saved ?? []
  const gifts: GiftCoupon[]     = (walletData as any)?.gifts ?? []
  const history: any[]          = (historyData as any)?.data ?? []
  const historyPages: number    = (historyData as any)?.pagination?.pages ?? 1
  const pendingGifts            = gifts.filter((g) => g.acceptance_status === 'pending').length

  const tabs = [
    { key: 'saved',   label: 'Saved',       icon: Bookmark, count: saved.length },
    { key: 'gifts',   label: 'Gifts',        icon: Gift,     count: pendingGifts, badge: pendingGifts > 0 },
    { key: 'history', label: 'History',      icon: Clock,    count: null },
  ] as const

  return (
    <div className="max-w-2xl mx-auto pb-8">
      {/* Header */}
      <div className="px-4 pt-5 pb-3">
        <h1 className="font-heading font-bold text-xl text-gray-900">My Wallet</h1>
        <p className="text-sm text-gray-400">Your coupons, gifts & redemption history</p>
      </div>

      {/* Tabs */}
      <div className="px-4 mb-4">
        <div className="flex bg-gray-100 rounded-xl p-1 gap-1">
          {tabs.map(({ key, label, icon: Icon, count, badge }) => (
            <button
              key={key}
              onClick={() => setTab(key as WalletTab)}
              className={`flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-semibold transition-all relative ${
                tab === key ? 'bg-white text-brand-700 shadow' : 'text-gray-500'
              }`}
            >
              <Icon size={13} />
              {label}
              {typeof count === 'number' && count > 0 && (
                <span className={`text-[10px] font-bold px-1.5 rounded-full ${
                  badge ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-600'
                }`}>
                  {count}
                </span>
              )}
            </button>
          ))}
        </div>
      </div>

      <div className="px-4 space-y-3">
        {/* ── Saved ── */}
        {tab === 'saved' && (
          walletLoading
            ? Array.from({ length: 4 }).map((_, i) => <SkeletonRow key={i} />)
            : saved.length > 0
              ? saved.map((c) => (
                  <SavedCouponCard key={c.id} coupon={c} onRedeem={() => setRedeemCoupon(c)} />
                ))
              : (
                <div className="text-center py-16">
                  <p className="text-4xl mb-2">🎟️</p>
                  <p className="text-gray-500 text-sm">No saved coupons yet.</p>
                  <Link to="/coupons" className="mt-3 inline-block btn-primary text-sm px-5 py-2">
                    Browse Deals
                  </Link>
                </div>
              )
        )}

        {/* ── Gifts ── */}
        {tab === 'gifts' && (
          walletLoading
            ? Array.from({ length: 3 }).map((_, i) => <SkeletonRow key={i} />)
            : gifts.length > 0
              ? gifts.map((g) => <GiftCouponCard key={g.gift_id} gift={g} />)
              : (
                <div className="text-center py-16">
                  <p className="text-4xl mb-2">🎁</p>
                  <p className="text-gray-500 text-sm">No gift coupons received yet.</p>
                </div>
              )
        )}

        {/* ── History ── */}
        {tab === 'history' && (
          historyLoading
            ? Array.from({ length: 4 }).map((_, i) => <SkeletonRow key={i} />)
            : history.length > 0
              ? (
                <>
                  {history.map((r: any) => (
                    <div key={r.id} className="card p-4 flex items-center gap-3">
                      <div className="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                        <span className="text-emerald-600 font-bold text-xs">✓</span>
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="font-semibold text-gray-900 text-sm truncate">{r.title ?? r.coupon_title}</p>
                        <p className="text-xs text-gray-400">{r.business_name ?? r.merchant_name} · {r.store_name ?? ''}</p>
                        <p className="text-[10px] text-gray-400 mt-0.5">
                          {new Date(r.redeemed_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </p>
                      </div>
                      {r.discount_amount > 0 && (
                        <span className="text-sm font-bold text-emerald-600 shrink-0">
                          −₹{r.discount_amount}
                        </span>
                      )}
                    </div>
                  ))}

                  {historyPages > 1 && (
                    <div className="flex justify-center gap-4 pt-2">
                      <button
                        disabled={historyPage === 1}
                        onClick={() => setHistoryPage(p => p - 1)}
                        className="btn-outline text-xs px-4 py-2 disabled:opacity-40"
                      >
                        ← Prev
                      </button>
                      <span className="text-xs text-gray-500 self-center">{historyPage}/{historyPages}</span>
                      <button
                        disabled={historyPage >= historyPages}
                        onClick={() => setHistoryPage(p => p + 1)}
                        className="btn-outline text-xs px-4 py-2 disabled:opacity-40"
                      >
                        Next →
                      </button>
                    </div>
                  )}
                </>
              )
              : (
                <div className="text-center py-16">
                  <p className="text-4xl mb-2">🧾</p>
                  <p className="text-gray-500 text-sm">No redemptions yet.</p>
                </div>
              )
        )}
      </div>

      {/* QR Modal */}
      {redeemCoupon && (
        <QrModal coupon={redeemCoupon} onClose={() => setRedeemCoupon(null)} />
      )}
    </div>
  )
}
