import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  Gift, Star, Zap, CreditCard, CheckCircle, Lock,
  Tag, BarChart2, Loader2, Clock, Crown, ArrowUpRight,
} from 'lucide-react'
import { profileApi } from '@/api/endpoints/profile'
import { useAuthStore } from '@/store/authStore'

// ── Tier config ──────────────────────────────────────────────────────────

type Tier = 'standard' | 'premium' | 'dealmaker'

const TIER = {
  standard: {
    label: 'Standard',
    gradient: 'from-slate-500 to-slate-700',
    bg: 'bg-slate-50 border-slate-200',
    accent: 'text-slate-600',
    badgeClass: 'bg-slate-100 text-slate-600',
    Icon: CreditCard,
    saveLimit: 10,
    benefits: [
      { label: 'Save up to 10 coupons at a time', included: true  },
      { label: 'Flash deal access',               included: false },
      { label: 'Exclusive member discounts',      included: false },
      { label: 'Gift coupons from merchants',     included: false },
      { label: 'Priority support',                included: false },
      { label: 'Contest participation',           included: false },
    ],
  },
  premium: {
    label: 'Premium',
    gradient: 'from-brand-500 to-brand-700',
    bg: 'bg-brand-50 border-brand-200',
    accent: 'text-brand-600',
    badgeClass: 'bg-brand-100 text-brand-700',
    Icon: Star,
    saveLimit: 50,
    benefits: [
      { label: 'Save up to 50 coupons at a time', included: true },
      { label: 'Flash deal early access',         included: true },
      { label: 'Exclusive member discounts',      included: true },
      { label: 'Gift coupons from merchants',     included: true },
      { label: 'Priority support',                included: true },
      { label: 'Contest participation',           included: true },
    ],
  },
  dealmaker: {
    label: 'Deal Maker',
    gradient: 'from-cta-500 to-orange-600',
    bg: 'bg-orange-50 border-orange-200',
    accent: 'text-orange-600',
    badgeClass: 'bg-orange-100 text-orange-700',
    Icon: Crown,
    saveLimit: 50,
    benefits: [
      { label: 'Save up to 50 coupons at a time', included: true },
      { label: 'Flash deal early access',         included: true },
      { label: 'Exclusive member discounts',      included: true },
      { label: 'Gift coupons from merchants',     included: true },
      { label: 'Priority support',                included: true },
      { label: 'Deal Maker earnings',             included: true },
    ],
  },
}

function daysLeft(expiry: string | null): number {
  if (!expiry) return 0
  return Math.max(0, Math.ceil((new Date(expiry).getTime() - Date.now()) / 86_400_000))
}

// ── Page ──────────────────────────────────────────────────────────────────

export default function LoyaltyCardsPage() {
  const { customer } = useAuthStore()

  const { data: sub, isLoading: subLoading } = useQuery({
    queryKey: ['subscription'],
    queryFn: () => profileApi.getSubscription().then(r => r.data.data),
    staleTime: 120_000,
  })

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['customer-stats'],
    queryFn: profileApi.getStats,
    staleTime: 60_000,
  })

  const isLoading = subLoading || statsLoading

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-24">
        <Loader2 size={28} className="animate-spin text-brand-500" />
      </div>
    )
  }

  const subData = sub as any
  const statsData = stats as any

  const isPremiumSub = subData?.status === 'active'
    || customer?.subscription_status === 'active'
  // Derive tier from profile flags
  const tier: Tier = customer?.is_dealmaker ? 'dealmaker'
                   : isPremiumSub ? 'premium'
                   : 'standard'

  const cfg = TIER[tier]
  const TierIcon = cfg.Icon
  const remaining = daysLeft(
    subData?.expiry_date ?? subData?.subscription_expiry ?? customer?.subscription_expiry ?? null
  )
  const savedCount: number = statsData?.saved ?? 0
  const saveLimit = cfg.saveLimit
  const savePct = Math.min(100, Math.round((savedCount / saveLimit) * 100))

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6 pb-10">
      <h1 className="font-heading font-bold text-2xl text-slate-800 mb-1">Membership</h1>
      <p className="text-sm text-slate-500 mb-6">Your DealMachan loyalty status and benefits</p>

      {/* Desktop 2-column layout */}
      <div className="lg:grid lg:grid-cols-2 lg:gap-8">

        {/* ── Left column: card visual + slot usage ── */}
        <div>
          {/* ── Membership card visual ──────────────────────────────────── */}
          <div className={`relative rounded-3xl overflow-hidden bg-gradient-to-br ${cfg.gradient} p-6 mb-6 shadow-lg`}>
            {/* Decorative circles */}
            <div className="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-white/10" />
            <div className="absolute -bottom-10 -left-10 w-36 h-36 rounded-full bg-white/10" />

            <div className="relative">
              <div className="flex items-center justify-between mb-8">
                <div>
                  <p className="text-white/60 text-xs uppercase tracking-widest font-semibold">Deal Machan</p>
                  <p className="text-white font-bold text-lg mt-0.5">{cfg.label} Member</p>
                </div>
                <TierIcon size={32} className="text-white/80" />
              </div>

              <p className="text-white font-semibold text-base">{customer?.name}</p>
              {isPremiumSub && remaining > 0 ? (
                <div className="flex items-center gap-1.5 mt-1">
                  <Clock size={13} className="text-white/70" />
                  <p className="text-white/70 text-xs">{remaining} days remaining</p>
                </div>
              ) : (
                <p className="text-white/70 text-xs mt-1 capitalize">{tier} plan</p>
              )}
            </div>
          </div>

          {/* ── Coupon slot usage ───────────────────────────────────────── */}
          <div className={`rounded-2xl border p-5 mb-4 ${cfg.bg}`}>
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <Tag size={16} className={cfg.accent} />
                <span className="font-semibold text-sm text-slate-700">Coupon Wallet Usage</span>
              </div>
              <span className={`text-sm font-bold ${cfg.accent}`}>{savedCount} / {saveLimit}</span>
            </div>
            <div className="h-2 bg-white/60 rounded-full overflow-hidden">
              <div
                className={`h-full rounded-full transition-all bg-gradient-to-r ${cfg.gradient}`}
                style={{ width: `${savePct}%` }}
              />
            </div>
            <p className="text-xs text-slate-500 mt-2">
              {saveLimit - savedCount > 0
                ? `${saveLimit - savedCount} slot${saveLimit - savedCount !== 1 ? 's' : ''} available`
                : 'Wallet full — redeem a coupon to free up space'}
            </p>
          </div>
        </div>{/* /left column */}

        {/* ── Right column: stats + benefits + upgrade + card link ── */}
        <div className="space-y-4">

          {/* ── Stats ───────────────────────────────────────────────────── */}
          <div className="grid grid-cols-3 gap-3">
            {[
              { Icon: Tag,      label: 'Saved',    value: statsData?.saved    ?? 0, color: 'bg-brand-50  text-brand-600' },
              { Icon: BarChart2, label: 'Redeemed', value: statsData?.redeemed ?? 0, color: 'bg-green-50  text-green-600' },
              { Icon: Zap,      label: 'Referrals', value: statsData?.referrals ?? 0, color: 'bg-orange-50 text-orange-600' },
            ].map(({ Icon, label, value, color }) => (
              <div key={label} className="bg-white rounded-2xl border border-slate-100 p-4 text-center">
                <div className={`w-8 h-8 rounded-xl flex items-center justify-center mx-auto mb-2 ${color}`}>
                  <Icon size={16} />
                </div>
                <p className="font-bold text-xl text-slate-800">{value}</p>
                <p className="text-xs text-slate-500">{label}</p>
              </div>
            ))}
          </div>

          {/* ── Benefits list ───────────────────────────────────────────── */}
          <div className="bg-white rounded-2xl border border-slate-100 p-5">
            <h2 className="font-semibold text-sm text-slate-700 mb-4">Your Benefits</h2>
            <ul className="space-y-3">
              {cfg.benefits.map(b => (
                <li key={b.label} className="flex items-center gap-3">
                  {b.included ? (
                    <CheckCircle size={16} className="text-green-500 flex-shrink-0" />
                  ) : (
                    <Lock size={16} className="text-slate-300 flex-shrink-0" />
                  )}
                  <span className={`text-sm ${b.included ? 'text-slate-700' : 'text-slate-400'}`}>
                    {b.label}
                  </span>
                </li>
              ))}
            </ul>
          </div>

          {/* ── Upgrade CTA (standard only) ─────────────────────────────── */}
          {tier === 'standard' && (
            <Link
              to="/profile/subscription"
              className="flex items-center justify-between bg-gradient-to-r from-brand-500 to-brand-700 rounded-2xl p-5 text-white hover:shadow-lg transition-shadow"
            >
              <div>
                <p className="font-bold text-base">Upgrade to Premium</p>
                <p className="text-white/80 text-sm mt-0.5">Save 50 coupons · Unlock all benefits</p>
              </div>
              <ArrowUpRight size={22} className="text-white/80 flex-shrink-0" />
            </Link>
          )}

          {/* ── Physical card quick-link ──────────────────────────────────── */}
          <Link
            to="/profile/card"
            className="flex items-center gap-3 bg-white rounded-2xl border border-slate-100 p-4 hover:shadow-md transition-shadow"
          >
            <div className="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
              <CreditCard size={18} className="text-slate-500" />
            </div>
            <div className="flex-1">
              <p className="font-semibold text-sm text-slate-800">Physical Card</p>
              <p className="text-xs text-slate-500">View or activate your DealMachan card</p>
            </div>
            <Gift size={16} className="text-slate-400" />
          </Link>
        </div>{/* /right column */}

      </div>{/* /lg:grid */}
    </div>
  )
}

