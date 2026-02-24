import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Wallet, Heart, Gift, Bell, Star, ChevronRight, Tag, Bookmark, RefreshCw, Users } from 'lucide-react'
import { useAuthStore } from '@/store/authStore'
import { profileApi } from '@/api/endpoints/profile'
import { notificationsApi } from '@/api/endpoints/notifications'
import { getImageUrl } from '@/lib/imageUrl'
import { Helmet } from 'react-helmet-async'

function StatCard({ icon: Icon, label, value, color }: {
  icon: React.ElementType
  label: string
  value: number | undefined
  color: string
}) {
  return (
    <div className="card p-4 flex flex-col items-center gap-2 text-center">
      <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${color}`}>
        <Icon size={18} />
      </div>
      <div>
        <p className="font-heading font-bold text-xl text-slate-800 leading-none">
          {value ?? <span className="inline-block w-6 h-5 bg-slate-200 rounded animate-pulse" />}
        </p>
        <p className="text-xs text-slate-500 mt-0.5">{label}</p>
      </div>
    </div>
  )
}

export default function DashboardPage() {
  const { customer } = useAuthStore()

  const { data: stats } = useQuery({
    queryKey: ['customer-stats'],
    queryFn: profileApi.getStats,
    staleTime: 60_000,
  })

  const { data: notifMeta } = useQuery({
    queryKey: ['notifications-unread'],
    queryFn: () => notificationsApi.getUnreadCount(),
    staleTime: 30_000,
  })
  const unreadCount: number = (notifMeta?.data as any)?.data?.count ?? (notifMeta?.data as any)?.count ?? 0

  const firstName = customer?.name?.split(' ')[0] ?? 'there'
  const avatarSrc = getImageUrl(customer?.profile_image ?? null)

  const QUICK_LINKS = [
    {
      to: '/wallet',
      icon: Wallet,
      label: 'My Wallet',
      desc: 'Saved coupons & rewards',
      color: 'bg-brand-50 text-brand-600',
      badge: null,
    },
    {
      to: '/wishlist',
      icon: Heart,
      label: 'Wishlist',
      desc: 'Saved merchants',
      color: 'bg-rose-50 text-cta-500',
      badge: null,
    },
    {
      to: '/loyalty-cards',
      icon: Gift,
      label: 'Loyalty Cards',
      desc: 'Stamp cards & rewards',
      color: 'bg-green-50 text-green-600',
      badge: null,
    },
    {
      to: '/notifications',
      icon: Bell,
      label: 'Notifications',
      desc: 'Deals & updates',
      color: 'bg-amber-50 text-amber-600',
      badge: unreadCount > 0 ? unreadCount : null,
    },
  ]

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-8">
      <Helmet>
        <title>Dashboard | Deal Machan</title>
      </Helmet>

      {/* Greeting */}
      <div className="flex items-center gap-4 mb-6">
        {avatarSrc ? (
          <img
            src={avatarSrc}
            alt={customer?.name}
            className="w-12 h-12 rounded-full object-cover border-2 border-brand-100 flex-shrink-0"
          />
        ) : (
          <div className="w-12 h-12 rounded-full gradient-brand flex items-center justify-center flex-shrink-0 shadow-brand">
            <span className="text-white font-heading font-bold text-lg">
              {firstName.charAt(0).toUpperCase()}
            </span>
          </div>
        )}
        <div>
          <h1 className="font-heading font-bold text-xl text-slate-800 leading-tight">
            Hi, {firstName} ??
          </h1>
          <p className="text-slate-500 text-xs mt-0.5">Welcome to your dashboard</p>
        </div>
        <Link to="/profile" className="ml-auto text-slate-400 hover:text-brand-600 transition-colors">
          <ChevronRight size={20} />
        </Link>
      </div>

      {/* Stats row */}
      <div className="grid grid-cols-3 gap-3 mb-6">
        <StatCard
          icon={Bookmark}
          label="Saved"
          value={stats?.saved}
          color="bg-brand-50 text-brand-600"
        />
        <StatCard
          icon={RefreshCw}
          label="Redeemed"
          value={stats?.redeemed}
          color="bg-green-50 text-green-600"
        />
        <StatCard
          icon={Users}
          label="Referrals"
          value={stats?.referrals}
          color="bg-purple-50 text-purple-600"
        />
      </div>

      {/* Subscription status */}
      {customer?.subscription_status === 'active' && (
        <Link to="/subscription" className="card p-4 gradient-brand mb-6 flex items-center justify-between group">
          <div>
            <div className="flex items-center gap-2 text-white font-semibold text-sm">
              <Star size={15} className="fill-yellow-300 text-yellow-300" />
              DealMaker Member
            </div>
            {customer.subscription_expiry && (
              <p className="text-white/70 text-xs mt-0.5">
                Valid until{' '}
                {new Date(customer.subscription_expiry).toLocaleDateString('en-IN', {
                  day: 'numeric',
                  month: 'short',
                  year: 'numeric',
                })}
              </p>
            )}
          </div>
          <ChevronRight size={18} className="text-white/60 group-hover:translate-x-0.5 transition-transform" />
        </Link>
      )}

      {/* Quick links */}
      <div className="grid grid-cols-2 gap-3 mb-6">
        {QUICK_LINKS.map(({ to, icon: Icon, label, desc, color, badge }) => (
          <Link key={to} to={to} className="card card-hover p-4 flex items-center gap-3">
            <div className={`w-10 h-10 ${color} rounded-xl flex items-center justify-center flex-shrink-0 relative`}>
              <Icon size={18} />
              {badge !== null && (
                <span className="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none">
                  {badge > 99 ? '99+' : badge}
                </span>
              )}
            </div>
            <div className="min-w-0">
              <p className="font-semibold text-slate-800 text-sm">{label}</p>
              <p className="text-xs text-slate-500 truncate">{desc}</p>
            </div>
          </Link>
        ))}
      </div>

      {/* Discover deals CTA */}
      <div className="card p-6 bg-gradient-to-br from-brand-50 to-purple-50 border border-brand-100 text-center">
        <Tag size={28} className="mx-auto mb-3 text-brand-600" />
        <h3 className="font-heading font-bold text-slate-800 mb-2">Discover New Deals</h3>
        <p className="text-slate-500 text-sm mb-4">Browse the latest coupons and offers from local stores.</p>
        <Link
          to="/deals"
          className="btn-primary !py-2.5 !px-6 !text-sm !rounded-xl inline-flex items-center gap-2"
        >
          Browse Deals <ChevronRight size={15} />
        </Link>
      </div>

    </div>
  )
}
