import { useState } from 'react'
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import {
  Search, MapPin, Menu, X, Tag, Store, Zap, BookOpen,
  ChevronDown, User, LogOut, Heart, Wallet,
} from 'lucide-react'
import { useAuthStore } from '@/store/authStore'
import { useLocationStore } from '@/store/locationStore'
import Footer from './Footer'

const NAV_LINKS = [
  { to: '/deals',        label: 'Deals',        icon: Tag },
  { to: '/stores',       label: 'Stores',       icon: Store },
  { to: '/flash-deals',  label: 'Flash Deals',  icon: Zap },
  { to: '/blog',         label: 'Blog',         icon: BookOpen },
]

export default function GuestShell() {
  const { customer, isAuthenticated, logout } = useAuthStore()
  const { cityName } = useLocationStore()
  const navigate = useNavigate()

  const [mobileOpen, setMobileOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    const q = searchQuery.trim()
    if (q) {
      navigate(`/deals?q=${encodeURIComponent(q)}`)
      setSearchQuery('')
      setMobileOpen(false)
    }
  }

  function handleLogout() {
    logout()
    setUserMenuOpen(false)
    navigate('/')
  }

  return (
    <div className="min-h-screen flex flex-col bg-[#f8f9fc]">
      {/* ─── Sticky Site Header ─────────────────────────────────────────── */}
      <header className="sticky top-0 z-50 bg-white/95 backdrop-blur shadow-sm border-b border-slate-100">
        <div className="site-container">
          <div className="flex items-center gap-4 h-16">

            {/* Logo */}
            <Link to="/" className="flex items-center gap-2 flex-shrink-0 mr-2">
              <div className="w-8 h-8 rounded-lg gradient-brand flex items-center justify-center">
                <Tag size={16} className="text-white" />
              </div>
              <span className="font-heading font-bold text-xl text-slate-800 hidden sm:block">
                Deal<span className="text-brand-600">Machan</span>
              </span>
            </Link>

            {/* Desktop nav links */}
            <nav className="hidden lg:flex items-center gap-1 flex-1">
              {NAV_LINKS.map(({ to, label }) => (
                <NavLink
                  key={to}
                  to={to}
                  className={({ isActive }) =>
                    `nav-link px-3 py-2 rounded-lg transition-colors ${
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
                placeholder="Search deals, stores…"
                className="flex-1 px-3 py-2.5 bg-transparent text-sm text-slate-700 placeholder-slate-400 outline-none"
              />
            </form>

            {/* Location badge */}
            {cityName && (
              <div className="hidden lg:flex items-center gap-1 text-sm text-slate-500 flex-shrink-0">
                <MapPin size={13} className="text-cta-500" />
                <span className="max-w-[100px] truncate">{cityName}</span>
              </div>
            )}

            {/* Auth area */}
            <div className="ml-auto flex items-center gap-2">
              {isAuthenticated && customer ? (
                <div className="relative">
                  <button
                    onClick={() => setUserMenuOpen(!userMenuOpen)}
                    className="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors text-slate-700 text-sm font-medium"
                  >
                    <div className="w-7 h-7 rounded-full gradient-brand flex items-center justify-center text-white text-xs font-bold">
                      {customer.name.charAt(0).toUpperCase()}
                    </div>
                    <span className="hidden sm:block max-w-[100px] truncate">{customer.name.split(' ')[0]}</span>
                    <ChevronDown size={14} className={`transition-transform ${userMenuOpen ? 'rotate-180' : ''}`} />
                  </button>

                  {userMenuOpen && (
                    <>
                      <div
                        className="fixed inset-0 z-40"
                        onClick={() => setUserMenuOpen(false)}
                      />
                      <div className="absolute right-0 top-full mt-2 w-52 bg-white rounded-2xl shadow-lg border border-slate-100 py-2 z-50">
                        <div className="px-4 py-2 mb-1 border-b border-slate-50">
                          <p className="font-semibold text-slate-800 text-sm">{customer.name}</p>
                          <p className="text-xs text-slate-500 truncate">{customer.email || customer.phone}</p>
                        </div>
                        <Link
                          to="/dashboard"
                          onClick={() => setUserMenuOpen(false)}
                          className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                          <User size={15} />  My Dashboard
                        </Link>
                        <Link
                          to="/wallet"
                          onClick={() => setUserMenuOpen(false)}
                          className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                          <Wallet size={15} />  My Wallet
                        </Link>
                        <Link
                          to="/wishlist"
                          onClick={() => setUserMenuOpen(false)}
                          className="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                          <Heart size={15} />  Wishlist
                        </Link>
                        <button
                          onClick={handleLogout}
                          className="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                        >
                          <LogOut size={15} />  Sign Out
                        </button>
                      </div>
                    </>
                  )}
                </div>
              ) : (
                <div className="flex items-center gap-2">
                  <Link
                    to="/login"
                    className="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-brand-600 hover:text-brand-700 transition-colors"
                  >
                    Sign In
                  </Link>
                  <Link
                    to="/register"
                    className="btn-primary !py-2 !px-4 !text-sm !rounded-lg"
                  >
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

        {/* Mobile menu */}
        {mobileOpen && (
          <div className="lg:hidden border-t border-slate-100 bg-white pb-4">
            <div className="site-container pt-3 space-y-1">
              {/* Mobile search */}
              <form onSubmit={handleSearch} className="flex items-center gap-2 mb-3">
                <div className="flex flex-1 items-center bg-slate-50 border border-slate-200 rounded-xl overflow-hidden">
                  <Search size={15} className="ml-3 text-slate-400" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Search deals, stores…"
                    className="flex-1 px-2 py-3 bg-transparent text-sm outline-none"
                  />
                </div>
                <button type="submit" className="btn-primary !py-3 !px-4 !rounded-xl !text-sm">
                  Go
                </button>
              </form>

              {/* Mobile nav links */}
              {NAV_LINKS.map(({ to, label, icon: Icon }) => (
                <NavLink
                  key={to}
                  to={to}
                  onClick={() => setMobileOpen(false)}
                  className={({ isActive }) =>
                    `flex items-center gap-3 px-3 py-3 rounded-xl text-sm font-medium transition-colors ${
                      isActive
                        ? 'text-brand-600 bg-brand-50'
                        : 'text-slate-700 hover:bg-slate-50'
                    }`
                  }
                >
                  <Icon size={17} />
                  {label}
                </NavLink>
              ))}

              {!isAuthenticated && (
                <div className="flex gap-2 pt-2">
                  <Link
                    to="/login"
                    onClick={() => setMobileOpen(false)}
                    className="flex-1 btn-ghost !py-2.5 !text-sm !rounded-xl text-center"
                  >
                    Sign In
                  </Link>
                  <Link
                    to="/register"
                    onClick={() => setMobileOpen(false)}
                    className="flex-1 btn-primary !py-2.5 !text-sm !rounded-xl text-center"
                  >
                    Join Free
                  </Link>
                </div>
              )}
            </div>
          </div>
        )}
      </header>

      {/* ─── Page content ───────────────────────────────────────────────── */}
      <main className="flex-1">
        <Outlet />
      </main>

      {/* ─── Footer ─────────────────────────────────────────────────────── */}
      <Footer />
    </div>
  )
}
