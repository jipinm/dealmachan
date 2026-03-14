import { Link, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  LogOut, ChevronRight, User, Shield, Bell, HelpCircle, Gift,
  CreditCard, Star, Bookmark, CheckCircle,
} from 'lucide-react'
import { useAuthStore } from '@/store/authStore'
import { profileApi } from '@/api/endpoints/profile'
import { couponsApi } from '@/api/endpoints/coupons'
import { getImageUrl } from '@/lib/imageUrl'
import { Helmet } from 'react-helmet-async'

interface MenuSection {
  title?: string
  items: Array<{ to: string; icon: React.ElementType; label: string; badge?: string }>
}

export default function ProfilePage() {
  const navigate = useNavigate()
  const { customer, logout } = useAuthStore()

  // Fetch real profile (for latest subscription status)
  const { data: profileData } = useQuery({
    queryKey: ['profile'],
    queryFn: () => profileApi.getProfile().then((r) => r.data.data),
    staleTime: 120_000,
  })

  // Wallet stats — saved count + history count
  const { data: walletData } = useQuery({
    queryKey: ['wallet'],
    queryFn: () => couponsApi.getWallet().then((r) => r.data.data),
    staleTime: 120_000,
  })

  const { data: cardData } = useQuery({
    queryKey: ['my-card'],
    queryFn: () => profileApi.getCard().then((r) => r.data.data),
    staleTime: 300_000,
  })

  const profile = profileData ?? customer
  const saved   = (walletData as any)?.saved?.length ?? 0
  const card    = cardData as any

  const isActive = profile?.subscription_status === 'active'

  const handleLogout = () => {
    logout()
    navigate('/login', { replace: true })
  }

  const MENU: MenuSection[] = [
    {
      items: [
        { to: '/profile/edit',          icon: User,       label: 'Edit Profile'        },
        { to: '/profile/card',           icon: CreditCard, label: 'My Card'             },
        { to: '/profile/subscription',   icon: Star,       label: 'Subscription'        },
        { to: '/referrals',              icon: Gift,       label: 'Refer & Earn'        },
      ],
    },
    {
      title: 'Settings',
      items: [
        { to: '/profile/security',       icon: Shield,     label: 'Password & Security' },
        { to: '/notifications',          icon: Bell,       label: 'Notifications'       },
      ],
    },
    {
      title: 'Support',
      items: [
        { to: '/grievances',             icon: HelpCircle, label: 'Help & Grievances'   },
      ],
    },
  ]

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6">
      <Helmet>
        <title>My Profile | Deal Machan</title>
      </Helmet>

      {/* Desktop 2-column layout */}
      <div className="lg:grid lg:grid-cols-[320px,1fr] lg:gap-8">

        {/* ── Left column — Avatar + quick stats + menu ─────────────────── */}
        <div className="space-y-4">

      {/* ── Avatar + name ─────────────────────────────────────────────── */}
      <div className="card p-5 flex items-center gap-4 mb-4">
        <div className="relative w-16 h-16 rounded-2xl shrink-0">
          {profile?.profile_image ? (
            <img src={profile.profile_image} alt={profile.name} loading="lazy" className="w-16 h-16 rounded-2xl object-cover" />
          ) : (
            <div className="w-16 h-16 rounded-2xl gradient-brand flex items-center justify-center shadow-brand">
              <span className="text-white font-bold text-2xl">{profile?.name?.charAt(0).toUpperCase() ?? '?'}</span>
            </div>
          )}
          {/* Online green dot */}
          <span className="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full" />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <p className="font-heading font-bold text-lg text-gray-900 truncate">{profile?.name}</p>
            {isActive ? (
              <span className="flex items-center gap-0.5 text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                <CheckCircle size={9} /> Active
              </span>
            ) : (
              <span className="text-[10px] font-semibold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                Free Plan
              </span>
            )}
          </div>
          <p className="text-sm text-gray-500 truncate">{profile?.email}</p>
          {(profile as any)?.phone && <p className="text-xs text-gray-400">{(profile as any).phone}</p>}
        </div>
        <Link to="/profile/edit" className="p-2 hover:bg-gray-100 rounded-xl transition-colors shrink-0">
          <ChevronRight size={18} className="text-gray-400" />
        </Link>
      </div>

      {/* ── Stats row ─────────────────────────────────────────────────── */}
      <div className="grid grid-cols-3 gap-3 mb-4">
        {[
          { label: 'Saved',     value: saved,                    icon: Bookmark,  to: '/wallet'   },
          { label: 'Referrals', value: '--',                     icon: Gift,      to: '/referrals' },
          { label: 'Card',      value: card ? 'Active' : '—',   icon: CreditCard, to: '/profile/card' },
        ].map(({ label, value, icon: Icon, to }) => (
          <Link key={label} to={to} className="card p-3 text-center hover:shadow-md transition-shadow">
            <Icon size={16} className="text-brand-500 mx-auto mb-1" />
            <p className="font-heading font-bold text-lg text-gray-900 leading-none">{value}</p>
            <p className="text-[10px] text-gray-500 mt-0.5">{label}</p>
          </Link>
        ))}
      </div>

      {/* ── My Card preview ───────────────────────────────────────────── */}
      {card && (
        <Link to="/profile/card" className="block mb-4">
          <div className="rounded-2xl overflow-hidden shadow-brand relative h-28">
            {card.card_image ? (
              <img src={getImageUrl(card.card_image)} alt="My Card" loading="lazy" className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full gradient-brand flex flex-col justify-between p-4">
                <p className="text-white/60 text-[10px] uppercase tracking-widest">Deal Machan</p>
                <div>
                  <p className="text-white font-mono font-semibold text-sm tracking-widest">
                    {card.card_number?.replace(/.(?=.{4})/g, '•') ?? '•••• •••• ••••'}
                  </p>
                  <div className="flex items-center justify-between mt-1">
                    <p className="text-white/70 text-xs">{profile?.name}</p>
                    <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                      card.status === 'active' ? 'bg-white/20 text-white' : 'bg-red-400/30 text-red-100'
                    }`}>{card.status}</span>
                  </div>
                </div>
              </div>
            )}
          </div>
        </Link>
      )}

      {/* ── Menu sections ─────────────────────────────────────────────── */}
      {MENU.map((section, si) => (
        <div key={si} className="mb-3">
          {section.title && (
            <p className="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2 px-1">
              {section.title}
            </p>
          )}
          <div className="card overflow-hidden divide-y divide-gray-50">
            {section.items.map(({ to, icon: Icon, label, badge }) => (
              <Link
                key={to}
                to={to}
                className="flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 transition-colors"
              >
                <span className="w-8 h-8 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
                  <Icon size={15} className="text-brand-600" />
                </span>
                <span className="flex-1 text-sm font-medium text-gray-700">{label}</span>
                {badge && (
                  <span className="text-[10px] font-bold bg-red-500 text-white rounded-full px-1.5">
                    {badge}
                  </span>
                )}
                <ChevronRight size={15} className="text-gray-300" />
              </Link>
            ))}
          </div>
        </div>
      ))}

      {/* ── Logout ───────────────────────────────────────────────────── */}
      <button
        onClick={handleLogout}
        className="w-full mt-2 flex items-center gap-3 px-4 py-4 card hover:bg-red-50 transition-colors group"
      >
        <span className="w-8 h-8 rounded-xl bg-red-50 group-hover:bg-red-100 flex items-center justify-center shrink-0">
          <LogOut size={15} className="text-red-500" />
        </span>
        <span className="text-sm font-medium text-red-500">Sign Out</span>
      </button>

      {customer?.referral_code && (
        <p className="text-center text-[10px] text-gray-300 mt-4">Referral code: {customer.referral_code}</p>
      )}

        </div>{/* /left column */}

        {/* ── Right column — Card visual + menu links grid (desktop) ──── */}
        <div className="hidden lg:block space-y-4 mt-0">
          {/* Quick action grid */}
          <div className="card p-6">
            <h2 className="font-heading font-bold text-base text-gray-800 mb-4">Quick Actions</h2>
            <div className="grid grid-cols-2 gap-3">
              {[
                { to: '/wallet',              icon: Bookmark,   label: 'My Wallet',       desc: 'Saved coupons & gifts' },
                { to: '/referrals',           icon: Gift,       label: 'Refer & Earn',    desc: 'Invite friends for rewards' },
                { to: '/profile/card',        icon: CreditCard, label: 'My Card',         desc: 'View loyalty card' },
                { to: '/profile/subscription',icon: Star,       label: 'Subscription',    desc: 'Manage your plan' },
                { to: '/notifications',       icon: Bell,       label: 'Notifications',   desc: 'Alerts & updates' },
                { to: '/grievances',          icon: HelpCircle, label: 'Help & Support',  desc: 'Issues & complaints' },
              ].map(({ to, icon: Icon, label, desc }) => (
                <Link
                  key={to}
                  to={to}
                  className="flex items-start gap-3 p-4 rounded-2xl border border-slate-100 hover:border-brand-200 hover:bg-brand-50/40 transition-all group"
                >
                  <span className="w-10 h-10 rounded-xl bg-brand-50 group-hover:bg-brand-100 flex items-center justify-center shrink-0 mt-0.5 transition-colors">
                    <Icon size={18} className="text-brand-600" />
                  </span>
                  <div>
                    <p className="text-sm font-semibold text-gray-800">{label}</p>
                    <p className="text-xs text-gray-400 mt-0.5 leading-tight">{desc}</p>
                  </div>
                </Link>
              ))}
            </div>
          </div>

          {/* Card preview (if exists) */}
          {card && (
            <Link to="/profile/card" className="block">
              <div className="card p-5">
                <h2 className="font-heading font-bold text-base text-gray-800 mb-3">Loyalty Card</h2>
                <div className="rounded-2xl overflow-hidden shadow-brand relative h-36">
                  {card.card_image ? (
                    <img src={getImageUrl(card.card_image)} alt="My Card" loading="lazy" className="w-full h-full object-cover" />
                  ) : (
                    <div className="w-full h-full gradient-brand flex flex-col justify-between p-4">
                      <p className="text-white/60 text-[10px] uppercase tracking-widest">Deal Machan</p>
                      <div>
                        <p className="text-white font-mono font-semibold text-sm tracking-widest">
                          {card.card_number?.replace(/.(?=.{4})/g, '•') ?? '•••• •••• ••••'}
                        </p>
                        <p className="text-white/70 text-xs mt-1">{profile?.name}</p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </Link>
          )}
        </div>{/* /right column */}

      </div>{/* /grid */}
    </div>
  )
}
