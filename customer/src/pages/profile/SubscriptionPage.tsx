import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ChevronLeft, CheckCircle, Clock, Star, Loader2, ExternalLink } from 'lucide-react'
import { profileApi } from '@/api/endpoints/profile'

const PLAN_BENEFITS = [
  'Unlimited coupon saves to wallet',
  'Access to flash deals before everyone else',
  'Exclusive member-only discounts',
  'Priority customer support',
  'Gift coupons from partner merchants',
  'Participate in exclusive contests',
  'Deal Maker eligibility',
]

function daysLeft(expiry: string | null): number {
  if (!expiry) return 0
  return Math.max(0, Math.ceil((new Date(expiry).getTime() - Date.now()) / 86_400_000))
}

export default function SubscriptionPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['subscription'],
    queryFn: () => profileApi.getSubscription().then((r) => r.data.data),
    staleTime: 120_000,
  })

  const sub = data as any
  const isActive = sub?.status === 'active'
  const remaining = daysLeft(sub?.expiry_date ?? sub?.subscription_expiry)
  const pct = sub?.expiry_date
    ? Math.min(100, Math.max(0, (remaining / 365) * 100))
    : 0

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 size={24} className="animate-spin text-brand-500" />
      </div>
    )
  }

  return (
    <div className="max-w-lg mx-auto px-4 py-6 pb-10">
      {/* Header */}
      <div className="flex items-center gap-3 mb-6">
        <Link to="/profile" className="p-2 hover:bg-gray-100 rounded-xl">
          <ChevronLeft size={20} className="text-gray-600" />
        </Link>
        <h1 className="font-heading font-bold text-xl text-gray-900">Subscription</h1>
      </div>

      {/* Plan card */}
      <div className={`rounded-3xl p-6 mb-6 relative overflow-hidden ${
        isActive ? 'gradient-brand text-white shadow-brand' : 'bg-gray-100 text-gray-600'
      }`}>
        {isActive && (
          <div className="absolute top-0 right-0 w-40 h-40 rounded-full bg-white/10 -translate-y-1/2 translate-x-1/2" />
        )}
        <div className="relative">
          <div className="flex items-center gap-2 mb-1">
            <Star size={16} className={isActive ? 'fill-white text-white' : 'text-gray-400'} />
            <p className="text-xs font-semibold uppercase tracking-widest opacity-80">Deal Machan</p>
          </div>
          <h2 className="font-heading font-bold text-3xl mb-1">
            {sub?.plan_type ? sub.plan_type.replace(/_/g, ' ').replace(/\b\w/g, (c: string) => c.toUpperCase()) : 'Free Plan'}
          </h2>
          <p className={`text-sm ${isActive ? 'text-white/80' : 'text-gray-400'}`}>
            {isActive
              ? `Active — ${remaining} day${remaining !== 1 ? 's' : ''} remaining`
              : 'No active subscription'}
          </p>

          {/* Progress bar */}
          {isActive && (
            <div className="mt-4">
              <div className="h-1.5 bg-white/20 rounded-full overflow-hidden">
                <div className="h-full bg-white/80 rounded-full" style={{ width: `${pct}%` }} />
              </div>
              <div className="flex justify-between mt-1 text-[10px] text-white/60">
                <span>
                  {sub?.start_date
                    ? new Date(sub.start_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
                    : ''}
                </span>
                <span>
                  {sub?.expiry_date
                    ? new Date(sub.expiry_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
                    : ''}
                </span>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Status notice */}
      {!isActive && (
        <div className="bg-amber-50 border border-amber-100 rounded-2xl p-4 mb-6 flex items-start gap-3">
          <Clock size={16} className="text-amber-500 mt-0.5 shrink-0" />
          <div>
            <p className="text-sm font-semibold text-amber-800">Your subscription has expired</p>
            <p className="text-xs text-amber-600 mt-0.5">Renew to unlock all deal-saving features.</p>
          </div>
        </div>
      )}

      {/* Benefits */}
      <div className="card p-5 mb-6">
        <h3 className="font-semibold text-gray-900 text-sm mb-3">Plan Benefits</h3>
        <div className="space-y-3">
          {PLAN_BENEFITS.map((b) => (
            <div key={b} className="flex items-center gap-2.5">
              <CheckCircle size={14} className={isActive ? 'text-emerald-500 shrink-0' : 'text-gray-300 shrink-0'} />
              <span className={`text-sm ${isActive ? 'text-gray-700' : 'text-gray-400'}`}>{b}</span>
            </div>
          ))}
        </div>
      </div>

      {/* Renew CTA */}
      {!isActive && (
        <a
          href="https://dealmachan.com/plans"
          target="_blank"
          rel="noopener noreferrer"
          className="w-full btn-primary text-base py-4 flex items-center justify-center gap-2"
        >
          Renew Subscription <ExternalLink size={14} />
        </a>
      )}

      {isActive && remaining <= 30 && (
        <div className="card p-4 border border-brand-100 bg-brand-50 text-center">
          <p className="text-sm text-brand-700">
            Your subscription expires in <span className="font-bold">{remaining} days</span>.
          </p>
          <a
            href="https://dealmachan.com/plans"
            target="_blank"
            rel="noopener noreferrer"
            className="mt-2 inline-block text-xs font-semibold text-brand-600 hover:underline"
          >
            Renew early →
          </a>
        </div>
      )}
    </div>
  )
}
