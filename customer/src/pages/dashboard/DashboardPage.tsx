import { Link } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'
import { Wallet, Heart, Gift, Bell, Star, ChevronRight, Tag } from 'lucide-react'

export default function DashboardPage() {
  const { customer } = useAuthStore()

  const QUICK_LINKS = [
    { to: '/wallet',       icon: Wallet, label: 'My Wallet',     desc: 'Saved coupons & rewards',  color: 'bg-brand-50  text-brand-600' },
    { to: '/wishlist',     icon: Heart,  label: 'Wishlist',      desc: 'Saved deals',              color: 'bg-rose-50   text-cta-500'   },
    { to: '/loyalty-cards',icon: Gift,   label: 'Loyalty Cards', desc: 'Stamp cards & rewards',    color: 'bg-green-50  text-green-600' },
    { to: '/notifications',icon: Bell,   label: 'Notifications', desc: 'Deals & updates',          color: 'bg-amber-50  text-amber-600' },
  ]

  return (
    <div className="max-w-2xl mx-auto px-4 py-8">
      {/* Greeting */}
      <div className="mb-8">
        <h1 className="font-heading font-bold text-2xl text-slate-800">
          Hi, {customer?.name?.split(' ')[0] ?? 'there'} 👋
        </h1>
        <p className="text-slate-500 text-sm mt-1">Welcome to your personal dashboard</p>
      </div>

      {/* Subscription status */}
      {customer?.subscription_status === 'active' && (
        <div className="card p-4 gradient-brand mb-6 flex items-center justify-between">
          <div>
            <div className="flex items-center gap-2 text-white font-semibold">
              <Star size={16} className="fill-yellow-300 text-yellow-300" />
              DealMaker Member
            </div>
            {customer.subscription_expiry && (
              <p className="text-white/70 text-xs mt-0.5">
                Valid until {new Date(customer.subscription_expiry).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
              </p>
            )}
          </div>
          <ChevronRight size={18} className="text-white/60" />
        </div>
      )}

      {/* Quick links */}
      <div className="grid grid-cols-2 gap-3 mb-8">
        {QUICK_LINKS.map(({ to, icon: Icon, label, desc, color }) => (
          <Link key={to} to={to} className="card card-hover p-4 flex items-center gap-3">
            <div className={`w-10 h-10 ${color} rounded-xl flex items-center justify-center flex-shrink-0`}>
              <Icon size={18} />
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
        <Link to="/deals" className="btn-primary !py-2.5 !px-6 !text-sm !rounded-xl inline-flex items-center gap-2">
          Browse Deals <ChevronRight size={15} />
        </Link>
      </div>
    </div>
  )
}
