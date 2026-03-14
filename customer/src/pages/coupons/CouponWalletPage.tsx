import React, { useState, useEffect } from 'react'
import { useQuery, useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { QRCodeSVG } from 'qrcode.react'
import { Bookmark, Gift, Clock, X, CheckCircle, XCircle, Loader2, Share2, QrCode } from 'lucide-react'
import { couponsApi } from '@/api/endpoints/coupons'
import type { WalletCoupon, GiftCoupon } from '@/api/endpoints/coupons'
import { getApiError } from '@/api/client'
import { SkeletonRow } from '@/components/ui/SkeletonCard'
import toast from 'react-hot-toast'
import { Link } from 'react-router-dom'
import { Helmet } from 'react-helmet-async'
import { useInfiniteScroll } from '@/lib/useInfiniteScroll'

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

  useEffect(() => {
    const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose() }
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [onClose])

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
    <div role="dialog" aria-modal="true" aria-label="Redeem coupon QR code" className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-4">
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
    mutationFn: () => couponsApi.unsaveCoupon(coupon.id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['wallet'] })
      toast.success('Removed from wallet')
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  return (
    <div className="group relative bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-brand-100 transition-all duration-200 overflow-hidden flex flex-col">
      {/* Top colour band */}
      <div className="h-1.5 w-full gradient-brand" />

      <div className="p-4 flex flex-col flex-1">
        {/* Merchant avatar + discount badge */}
        <div className="flex items-start justify-between gap-2 mb-3">
          <div className="w-10 h-10 rounded-xl gradient-brand flex items-center justify-center shrink-0 text-white font-bold text-base shadow-sm">
            {coupon.merchant_name?.charAt(0)?.toUpperCase() ?? '?'}
          </div>
          <span className="text-xs font-bold text-brand-700 bg-brand-50 border border-brand-100 px-2.5 py-1 rounded-full shrink-0">
            {discountText(coupon.discount_type, coupon.discount_value)}
          </span>
        </div>

        {/* Title + merchant */}
        <p className="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 mb-0.5">{coupon.title}</p>
        <p className="text-xs text-gray-400 truncate mb-3">{coupon.merchant_name}</p>

        {/* Expiry */}
        {coupon.valid_until && (
          <p className="text-[10px] text-gray-400 flex items-center gap-1 mb-3 mt-auto">
            <Clock size={10} />
            Expires {new Date(coupon.valid_until).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
          </p>
        )}

        {/* Dashed divider */}
        <div className="border-t border-dashed border-gray-200 mb-3" />

        {/* Actions */}
        <div className="flex gap-2">
          <button
            onClick={onRedeem}
            className="flex-1 btn-primary text-xs py-2 rounded-xl"
          >
            Redeem
          </button>
          <button
            onClick={() => removeMutation.mutate()}
            disabled={removeMutation.isPending}
            className="w-9 h-8 flex items-center justify-center rounded-xl border border-gray-200 text-gray-400 hover:border-red-200 hover:text-red-500 hover:bg-red-50 transition-colors shrink-0"
            title="Remove"
          >
            {removeMutation.isPending
              ? <Loader2 size={12} className="animate-spin" />
              : <X size={13} />}
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Gift coupon card ──────────────────────────────────────────────────────────
function GiftCouponCard({ gift, onRedeem }: { gift: GiftCoupon; onRedeem?: () => void }) {
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
    <div className="relative bg-white rounded-2xl shadow-sm border border-amber-100 hover:shadow-md transition-all duration-200 overflow-hidden flex flex-col">
      {/* Top accent */}
      <div className="h-1.5 w-full bg-gradient-to-r from-amber-400 to-orange-400" />

      <div className="p-4 flex flex-col flex-1">
        {/* Icon + badge */}
        <div className="flex items-start justify-between gap-2 mb-3">
          <div className="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 shadow-sm">
            <Gift size={18} className="text-amber-600" />
          </div>
          <span className="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full shrink-0">
            {discountText(gift.discount_type, gift.discount_value)}
          </span>
        </div>

        {/* Title + merchant */}
        <p className="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 mb-0.5">{gift.title}</p>
        <p className="text-xs text-gray-400 truncate mb-2">{gift.merchant_name}</p>

        {gift.gifted_at && (
          <p className="text-[10px] text-gray-400 flex items-center gap-1 mb-3 mt-auto">
            <Clock size={10} />
            Received {new Date(gift.gifted_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
          </p>
        )}

        <div className="border-t border-dashed border-amber-100 mb-3" />

        {gift.acceptance_status === 'pending' && (
          <div className="flex gap-2">
            <button
              onClick={() => acceptMutation.mutate()}
              disabled={isPending}
              className="flex-1 btn-primary text-xs py-2 rounded-xl flex items-center justify-center gap-1"
            >
              {acceptMutation.isPending ? <Loader2 size={12} className="animate-spin" /> : <CheckCircle size={12} />}
              Accept
            </button>
            <button
              onClick={() => rejectMutation.mutate()}
              disabled={isPending}
              className="flex-1 text-xs py-2 rounded-xl border border-gray-200 text-gray-500 hover:border-red-200 hover:text-red-500 hover:bg-red-50 transition-colors flex items-center justify-center gap-1"
            >
              {rejectMutation.isPending ? <Loader2 size={12} className="animate-spin" /> : <XCircle size={12} />}
              Decline
            </button>
          </div>
        )}
        {gift.acceptance_status === 'accepted' && (
          <button
            onClick={onRedeem}
            className="w-full btn-primary text-xs py-2 rounded-xl flex items-center justify-center gap-1"
          >
            <QrCode size={12} /> Use Coupon
          </button>
        )}
      </div>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────────
export default function CouponWalletPage() {
  const [tab, setTab] = useState<WalletTab>('saved')
  const [redeemCoupon, setRedeemCoupon] = useState<WalletCoupon | GiftCoupon | null>(null)

  const { data: walletData, isLoading: walletLoading } = useQuery({
    queryKey: ['wallet'],
    queryFn: () => couponsApi.getWallet().then((r) => r.data.data),
    staleTime: 30_000,
  })

  const { data: historyData, isLoading: historyLoading, isFetchingNextPage: historyFetchingMore, fetchNextPage: fetchMoreHistory, hasNextPage: historyHasMore } = useInfiniteQuery({
    queryKey: ['coupon-history-inf'],
    queryFn: ({ pageParam = 1 }) => couponsApi.getHistory({ page: pageParam as number, per_page: 15 }).then((r) => r.data),
    initialPageParam: 1,
    getNextPageParam: (lastPage: any, allPages) => {
      const total = lastPage?.data?.pagination?.pages ?? 1
      return allPages.length < total ? allPages.length + 1 : undefined
    },
    enabled: tab === 'history',
    staleTime: 60_000,
  })

  const saved: WalletCoupon[]   = (walletData as any)?.saved ?? []
  const gifts: GiftCoupon[]     = (walletData as any)?.gifts ?? []
  const history: any[]          = historyData?.pages.flatMap((p: any) => p?.data?.data ?? []) ?? []
  const pendingGifts            = gifts.filter((g) => g.acceptance_status === 'pending').length

  const historySentinelRef = useInfiniteScroll(
    () => { if (historyHasMore && !historyFetchingMore) fetchMoreHistory() },
    !!historyHasMore && !historyFetchingMore && tab === 'history',
  )

  const tabs: Array<{ key: WalletTab; label: string; icon: React.ElementType; count: number | null; badge?: boolean }> = [
    { key: 'saved',   label: 'Saved',   icon: Bookmark, count: saved.length },
    { key: 'gifts',   label: 'Gifts',   icon: Gift,     count: pendingGifts, badge: pendingGifts > 0 },
    { key: 'history', label: 'History', icon: Clock,    count: null },
  ]

  return (
    <div className="max-w-[1200px] mx-auto pb-8">
      <Helmet>
        <title>My Wallet | Deal Machan</title>
      </Helmet>
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

      <div className="px-4">
        {/* ── Saved ── */}
        {tab === 'saved' && (
          walletLoading
            ? <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">{Array.from({ length: 6 }).map((_, i) => <SkeletonRow key={i} />)}</div>
            : saved.length > 0
              ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                  {saved.map((c) => (
                    <SavedCouponCard key={c.id} coupon={c} onRedeem={() => setRedeemCoupon(c)} />
                  ))}
                </div>
              )
              : (
                <div className="text-center py-20">
                  <div className="w-16 h-16 bg-brand-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Bookmark size={28} className="text-brand-300" />
                  </div>
                  <p className="font-semibold text-gray-700 mb-1">No saved coupons yet</p>
                  <p className="text-gray-400 text-sm mb-6">Browse deals and tap Save to add them here.</p>
                  <Link to="/coupons" className="btn-primary text-sm px-6 py-2.5 rounded-xl">
                    Browse Deals
                  </Link>
                </div>
              )
        )}

        {/* ── Gifts ── */}
        {tab === 'gifts' && (
          walletLoading
            ? <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">{Array.from({ length: 3 }).map((_, i) => <SkeletonRow key={i} />)}</div>
            : gifts.length > 0
              ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                  {gifts.map((g) => <GiftCouponCard key={g.gift_id} gift={g} onRedeem={() => setRedeemCoupon(g)} />)}
                </div>
              )
              : (
                <div className="text-center py-20">
                  <div className="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Gift size={28} className="text-amber-300" />
                  </div>
                  <p className="font-semibold text-gray-700 mb-1">No gift coupons</p>
                  <p className="text-gray-400 text-sm">Gift coupons sent to you will appear here.</p>
                </div>
              )
        )}

        {/* ── History ── */}
        {tab === 'history' && (
          historyLoading
            ? <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">{Array.from({ length: 6 }).map((_, i) => <SkeletonRow key={i} />)}</div>
            : history.length > 0
              ? (
                <>
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {history.map((r: any) => (
                      <div key={r.id} className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-emerald-100 transition-all duration-200 overflow-hidden flex flex-col">
                        <div className="h-1.5 w-full bg-gradient-to-r from-emerald-400 to-teal-400" />
                        <div className="p-4 flex flex-col flex-1">
                          <div className="flex items-start justify-between gap-2 mb-3">
                            <div className="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                              <CheckCircle size={18} className="text-emerald-500" />
                            </div>
                            {r.discount_amount > 0 && (
                              <span className="text-sm font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-full shrink-0">
                                −₹{Number(r.discount_amount).toFixed(2)}
                              </span>
                            )}
                          </div>
                          <p className="font-semibold text-gray-900 text-sm leading-snug line-clamp-2 mb-0.5">{r.title ?? r.coupon_title}</p>
                          <p className="text-xs text-gray-400 truncate mb-auto">
                            {r.merchant_name ?? r.business_name}
                            {(r.store_name) ? ` · ${r.store_name}` : ''}
                          </p>
                          <div className="border-t border-dashed border-gray-100 mt-3 pt-2.5">
                            <p className="text-[10px] text-gray-400 flex items-center gap-1">
                              <Clock size={10} />
                              {new Date(r.redeemed_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                            </p>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>

                  <div ref={historySentinelRef} className="h-4" />
                  {historyFetchingMore && (
                    <div className="flex justify-center py-4">
                      <Loader2 size={18} className="animate-spin text-brand-500" />
                    </div>
                  )}
                  {!historyHasMore && history.length > 0 && (
                    <p className="text-center text-xs text-gray-400 py-4">All history loaded</p>
                  )}
                </>
              )
              : (
                <div className="text-center py-20">
                  <div className="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Clock size={28} className="text-emerald-300" />
                  </div>
                  <p className="font-semibold text-gray-700 mb-1">No redemptions yet</p>
                  <p className="text-gray-400 text-sm">Your coupon redemption history will appear here.</p>
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
