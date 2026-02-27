import { useState } from 'react'
import { Link, NavLink, Outlet, ScrollRestoration, useNavigate } from 'react-router-dom'
import {
  Search, MapPin, Menu, X, Tag, Store, Zap, BookOpen,
  ChevronDown, User, LogOut, Heart, Wallet, Bell,
  Gift, CreditCard, Calendar, MessageSquare, Compass,
} from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { useAuthStore } from '@/store/authStore'
import { useLocationStore } from '@/store/locationStore'
import { notificationsApi } from '@/api/endpoints/notifications'
import Footer from './Footer'
import LocationModal from '@/components/ui/LocationModal'

// ── Main nav links ────────────────────────────────────────────────────────────
const NAV_LINKS = [
  { to: '/deals',       label: 'Deals',       icon: Tag     },
  { to: '/stores',      label: 'Stores',      icon: Store   },
  { to: '/flash-deals', label: 'Flash Deals', icon: Zap     },
  { to: '/blog',        label: 'Blog',        icon: BookOpen },
]

// ── Authenticated user dropdown items ────────────────────────────────────────
const USER_MENU_GROUPS = [
  {
    items: [
      { to: '/wallet',         icon: Wallet,       label: 'My Coupons'    },
      { to: '/wishlist',       icon: Heart,        label: 'Saved Deals'   },
      { to: '/loyalty-cards',  icon: CreditCard,   label: 'Loyalty Cards' },
    ],
  },
  {
    items: [
      { to: '/activity',       icon: Zap,          label: 'My Activity'   },
      { to: '/important-days', icon: Calendar,     label: 'Important Days' },
      { to: '/referrals',      icon: Gift,         label: 'Refer & Earn'  },
    ],
  },
  {
    items: [
      { to: '/notifications',  icon: Bell,         label: 'Notifications' },
      { to: '/grievances',     icon: MessageSquare,label: 'Help & Support' },
    ],
  },
]

// ── Authenticated mobile bottom-nav tabs ─────────────────────────────────────
const AUTH_BOTTOM_TABS = [
  { to: '/',              icon: Tag,     label: 'Home',    exact: true  },
  { to: '/explore',       icon: Compass, label: 'Explore', exact: false },
  { to: '/wallet',        icon: Wallet,  label: 'Wallet',  exact: false },
  { to: '/notifications', icon: Bell,    label: 'Alerts',  exact: false },
  { to: '/profile',       icon: User,    label: 'Account', exact: false },
]

export default function GuestShell() {
  const { customer, isAuthenticated, logout } = useAuthStore()
  const { cityName, areaName, modalOpen: locationOpen, openLocationModal, closeLocationModal } = useLocationStore()
  const navigate = useNavigate()

  const [mobileOpen, setMobileOpen]     = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [searchQuery, setSearchQuery]   = useState('')

  // Unread notification count (authenticated only)
  const { data: notifData } = useQuery({
    queryKey: ['notifications-unread-count'],
    queryFn:  () => notificationsApi.getUnreadCount().then((r) => r.data.data ?? null),
    staleTime: 30_000,
    enabled:  isAuthenticated,
  })
  const unreadCount = notifData?.count ?? 0

  const locationLabel = areaName
    ? `${areaName}, ${cityName}`
    : cityName || 'Set location'

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    const q = searchQuery.trim()
    if (q) {
      navigate(`/search?q=${encodeURIComponent(q)}`)
      setSearchQuery('')
      setMobileOpen(false)
    }
  }

  function handleLogout() {
    logout()
    setUserMenuOpen(false)
    setMobileOpen(false)
    navigate('/')
  }

  return (
    <div className="min-h-screen flex flex-col bg-[#f8f9fc]">

      {/* ══════════════════════════════════════════════════════════════════════
          STICKY SITE HEADER
      ══════════════════════════════════════════════════════════════════════ */}
      <header className="sticky top-0 z-50 bg-white/95 backdrop-blur shadow-sm border-b border-slate-100">
        <div className="site-container">
          <div className="flex items-center gap-3 h-16 min-w-0">

            {/* Logo */}
            <Link to="/" className="flex items-center gap-2 flex-shrink-0 mr-2">
              <div className="w-8 h-8 rounded-lg gradient-brand flex items-center justify-center">
                <Tag size={16} className="text-white" />
              </div>
              <span className="font-heading font-bold text-xl text-slate-800 hidden sm:block">
                Deal<span className="text-brand-600">Machan</span>
              </span>
            </Link>

            {/* Desktop nav */}
            <nav className="hidden lg:flex items-center gap-1 shrink-0">
              {NAV_LINKS.map(({ to, label }) => (
                <NavLink
                  key={to}
                  to={to}
                  className={({ isActive }) =>
                    `nav-link px-3 py-2 rounded-lg transition-colors whitespace-nowrap ${
                      isActive ? 'text-brand-600 bg-brand-50 font-semibold' : 'hover:bg-slate-50'
                    }`
                  }
                >
                  {label}
                </NavLink>
              ))}
            </nav>

            {/* Desktop search bar */}
            <form
              onSubmit={handleSearch}
              className="hidden md:flex flex-1 max-w-sm items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-100 transition-all"
            >
              <Search size={16} className="ml-3 text-slate-400 flex-shrink-0" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search deals, stores..."
                className="flex-1 px-3 py-2.5 bg-transparent text-sm text-slate-700 placeholder-slate-400 outline-none"
              />
            </form>

            {/* Location badge */}
            <button
              onClick={openLocationModal}
              className="hidden lg:flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm text-slate-600 hover:bg-slate-50 border border-slate-200 transition-colors flex-shrink-0"
            >
              <MapPin size={14} className={cityName ? 'text-cta-500' : 'text-slate-400'} />
              <span className="max-w-[120px] truncate font-medium">{locationLabel}</span>
              <ChevronDown size={13} className="text-slate-400" />
            </button>

            {/* ── Auth area ── */}
            <div className="ml-auto flex items-center gap-2 shrink-0 min-w-0">
              {isAuthenticated && customer ? (
                <>
                  {/* Notifications bell */}
                  <Link
                    to="/notifications"
                    className="relative hidden sm:flex items-center justify-center w-9 h-9 rounded-xl text-slate-500 hover:bg-slate-100 transition-colors"
                    aria-label="Notifications"
                  >
                    <Bell size={18} />
                    {unreadCount > 0 && (
                      <span className="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 bg-cta-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none">
                        {unreadCount > 9 ? '9+' : unreadCount}
                      </span>
                    )}
                  </Link>

                  {/* User avatar + dropdown */}
                  <div className="relative">
                    <button
                      onClick={() => setUserMenuOpen(!userMenuOpen)}
                      className="flex items-center gap-2 px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors text-slate-700 text-sm font-medium"
                    >
                      <div className="w-7 h-7 rounded-full gradient-brand flex items-center justify-center text-white text-xs font-bold overflow-hidden">
                        {customer.profile_image ? (
                          <img src={customer.profile_image} alt={customer.name} className="w-full h-full object-cover" />
                        ) : (
                          customer.name.charAt(0).toUpperCase()
                        )}
                      </div>
                      <span className="hidden sm:block max-w-[90px] truncate">{customer.name.split(' ')[0]}</span>
                      <ChevronDown size={14} className={`transition-transform ${userMenuOpen ? 'rotate-180' : ''}`} />
                    </button>

                    {/* Dropdown */}
                    {userMenuOpen && (
                      <>
                        <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                        <div className="absolute right-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-50">

                          {/* User info header */}
                          <Link
                            to="/profile"
                            onClick={() => setUserMenuOpen(false)}
                            className="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-100"
                          >
                            <div className="w-9 h-9 rounded-full gradient-brand flex items-center justify-center text-white font-bold text-sm overflow-hidden shrink-0">
                              {customer.profile_image ? (
                                <img src={customer.profile_image} alt={customer.name} className="w-full h-full object-cover" />
                              ) : (
                                customer.name.charAt(0).toUpperCase()
                              )}
                            </div>
                            <div className="min-w-0">
                              <p className="font-semibold text-slate-800 text-sm leading-tight truncate">{customer.name}</p>
                              <p className="text-xs text-slate-500 truncate">{customer.email || customer.phone}</p>
                            </div>
                          </Link>

                          {/* Menu groups */}
                          {USER_MENU_GROUPS.map((group, gi) => (
                            <div key={gi} className={gi > 0 ? 'border-t border-slate-100' : ''}>
                              {group.items.map(({ to, icon: Icon, label }) => (
                                <Link
                                  key={to}
                                  to={to}
                                  onClick={() => setUserMenuOpen(false)}
                                  className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                                >
                                  <Icon size={15} className="text-slate-400 shrink-0" />
                                  {label}
                                  {to === '/notifications' && unreadCount > 0 && (
                                    <span className="ml-auto bg-cta-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                                      {unreadCount > 9 ? '9+' : unreadCount}
                                    </span>
                                  )}
                                </Link>
                              ))}
                            </div>
                          ))}

                          {/* Sign out */}
                          <div className="border-t border-slate-100">
                            <button
                              onClick={handleLogout}
                              className="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                            >
                              <LogOut size={15} className="shrink-0" />
                              Sign Out
                            </button>
                          </div>
                        </div>
                      </>
                    )}
                  </div>
                </>
              ) : (
                <div className="flex items-center gap-2">
                  <Link
                    to="/login"
                    className="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors"
                  >
                    Sign In
                  </Link>
                  <Link to="/register" className="btn-primary !py-2 !px-4 !text-sm !rounded-lg">
                    Join Free
                  </Link>
                </div>
              )}

              {/* Mobile hamburger */}
              <button
                onClick={() => setMobileOpen(!mobileOpen)}
                className="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-50 transition-colors"
                aria-label="Menu"
              >
                {mobileOpen ? <X size={20} /> : <Menu size={20} />}
              </button>
            </div>
          </div>
        </div>

        {/* ── Mobile slide-down menu ── */}
        {mobileOpen && (
          <div className="lg:hidden border-t border-slate-100 bg-white pb-4 max-h-[80vh] overflow-y-auto">
            <div className="site-container pt-3 space-y-1">

              {/* Search */}
              <form onSubmit={handleSearch} className="flex items-center gap-2 mb-3">
                <div className="flex flex-1 items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden">
                  <Search size={15} className="ml-3 text-slate-400" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Search deals, stores..."
                    className="flex-1 px-2 py-3 bg-transparent text-sm outline-none"
                  />
                </div>
                <button type="submit" className="btn-primary !py-3 !px-4 !rounded-xl !text-sm">Go</button>
              </form>

              {/* Location */}
              <button
                onClick={() => { openLocationModal(); setMobileOpen(false) }}
                className="flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors w-full"
              >
                <MapPin size={17} className={cityName ? 'text-cta-500' : 'text-slate-400'} />
                {locationLabel}
              </button>

              {/* Public nav */}
              {NAV_LINKS.map(({ to, label, icon: Icon }) => (
                <NavLink
                  key={to}
                  to={to}
                  onClick={() => setMobileOpen(false)}
                  className={({ isActive }) =>
                    `flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors ${
                      isActive ? 'text-brand-600 bg-brand-50' : 'text-slate-700 hover:bg-slate-50'
                    }`
                  }
                >
                  <Icon size={17} /> {label}
                </NavLink>
              ))}

              {/* Authenticated section */}
              {isAuthenticated && customer ? (
                <>
                  <div className="pt-2 mt-2 border-t border-slate-100">
                    <p className="px-3 py-1 text-xs font-semibold text-slate-400 uppercase tracking-wider">My Account</p>
                    {[
                      { to: '/wallet',        icon: Wallet,        label: 'My Coupons'    },
                      { to: '/wishlist',       icon: Heart,         label: 'Saved Deals'   },
                      { to: '/loyalty-cards',  icon: CreditCard,    label: 'Loyalty Cards' },
                      { to: '/activity',       icon: Zap,           label: 'My Activity'   },
                      { to: '/referrals',      icon: Gift,          label: 'Refer & Earn'  },
                      { to: '/notifications',  icon: Bell,          label: 'Notifications' },
                      { to: '/profile',        icon: User,          label: 'My Profile'    },
                    ].map(({ to, icon: Icon, label }) => (
                      <NavLink
                        key={to}
                        to={to}
                        onClick={() => setMobileOpen(false)}
                        className={({ isActive }) =>
                          `flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors ${
                            isActive ? 'text-brand-600 bg-brand-50' : 'text-slate-700 hover:bg-slate-50'
                          }`
                        }
                      >
                        <Icon size={17} />
                        {label}
                        {to === '/notifications' && unreadCount > 0 && (
                          <span className="ml-auto bg-cta-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                            {unreadCount > 9 ? '9+' : unreadCount}
                          </span>
                        )}
                      </NavLink>
                    ))}
                  </div>
                  <div className="pt-1">
                    <button
                      onClick={handleLogout}
                      className="flex items-center gap-3 w-full px-3 py-3 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                    >
                      <LogOut size={17} /> Sign Out
                    </button>
                  </div>
                </>
              ) : (
                <div className="flex gap-2 pt-2">
                  <Link to="/login"    onClick={() => setMobileOpen(false)} className="flex-1 btn-ghost !py-2.5 !text-sm !rounded-xl text-center">Sign In</Link>
                  <Link to="/register" onClick={() => setMobileOpen(false)} className="flex-1 btn-primary !py-2.5 !text-sm !rounded-xl text-center">Join Free</Link>
                </div>
              )}
            </div>
          </div>
        )}
      </header>

      {/* ── Page content ── */}
      <main className={`flex-1${isAuthenticated ? ' pb-16 lg:pb-0' : ''}`}>
        <Outlet />
      </main>

      {/* Footer — hidden on mobile when authenticated (bottom nav replaces it) */}
      <div className={isAuthenticated ? 'hidden lg:block' : ''}>
        <Footer />
      </div>

      {/* ── Authenticated mobile bottom nav ── */}
      {isAuthenticated && (
        <nav className="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-white border-t border-slate-100 pb-safe">
          <div className="flex items-stretch h-14">
            {AUTH_BOTTOM_TABS.map(({ to, icon: Icon, label, exact }) => (
              <NavLink
                key={to}
                to={to}
                end={exact}
                className={({ isActive }) =>
                  `flex-1 flex flex-col items-center justify-center gap-0.5 py-1.5 transition-colors ${
                    isActive ? 'text-brand-600' : 'text-slate-400'
                  }`
                }
              >
                {({ isActive }) => (
                  <>
                    <div className="relative">
                      <Icon size={19} strokeWidth={isActive ? 2.5 : 1.8} />
                      {to === '/notifications' && unreadCount > 0 && (
                        <span className="absolute -top-1 -right-1 w-3.5 h-3.5 bg-cta-500 rounded-full border-2 border-white" />
                      )}
                    </div>
                    <span className="text-[10px] font-medium leading-tight">{label}</span>
                  </>
                )}
              </NavLink>
            ))}
          </div>
        </nav>
      )}

      {/* ── Location modal ── */}
      <LocationModal open={locationOpen} onClose={closeLocationModal} />

      {/* Scroll to top on push/replace; restore position on back */}
      <ScrollRestoration />
    </div>
  )
}
