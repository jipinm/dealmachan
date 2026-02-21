import { Link } from 'react-router-dom'
import { Tag, Facebook, Instagram, Twitter, Mail, Phone, MapPin } from 'lucide-react'

const QUICK_LINKS = [
  { to: '/deals',        label: 'Browse Deals' },
  { to: '/stores',       label: 'Find Stores' },
  { to: '/flash-deals',  label: 'Flash Deals' },
  { to: '/categories',   label: 'Categories' },
]

const ACCOUNT_LINKS = [
  { to: '/login',        label: 'Sign In' },
  { to: '/register',     label: 'Create Account' },
  { to: '/dashboard',    label: 'My Dashboard' },
  { to: '/wallet',       label: 'My Wallet' },
]

const INFO_LINKS = [
  { to: '/about',        label: 'About Us' },
  { to: '/contact',      label: 'Contact' },
  { to: '/blog',         label: 'Blog' },
  { to: '/business-signup', label: 'List Your Business' },
]

export default function Footer() {
  const year = new Date().getFullYear()

  return (
    <footer className="bg-slate-900 text-slate-300 mt-auto">

      {/* Newsletter strip */}
      <div className="gradient-brand">
        <div className="site-container py-10">
          <div className="flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
              <h3 className="font-heading font-bold text-white text-xl mb-1">
                Never miss a deal!
              </h3>
              <p className="text-white/80 text-sm">
                Get the best coupons and offers delivered to your inbox.
              </p>
            </div>
            <form
              onSubmit={(e) => e.preventDefault()}
              className="flex gap-2 w-full md:w-auto"
            >
              <input
                type="email"
                placeholder="Enter your email"
                className="flex-1 md:w-72 px-4 py-2.5 rounded-xl text-sm text-slate-800 bg-white outline-none focus:ring-2 focus:ring-white/40 placeholder-slate-400"
              />
              <button
                type="submit"
                className="px-5 py-2.5 bg-white text-brand-700 font-semibold text-sm rounded-xl hover:bg-white/90 transition-colors flex-shrink-0"
              >
                Subscribe
              </button>
            </form>
          </div>
        </div>
      </div>

      {/* Main footer grid */}
      <div className="site-container py-12">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

          {/* Brand column */}
          <div>
            <Link to="/" className="flex items-center gap-2 mb-4">
              <div className="w-8 h-8 rounded-lg gradient-brand flex items-center justify-center">
                <Tag size={15} className="text-white" />
              </div>
              <span className="font-heading font-bold text-lg text-white">
                DealMachan
              </span>
            </Link>
            <p className="text-sm text-slate-400 leading-relaxed mb-5">
              Discover the best coupons, deals, and discounts from local merchants.
              Save more on every purchase!
            </p>
            <div className="flex gap-3">
              {[
                { icon: Facebook, label: 'Facebook' },
                { icon: Instagram, label: 'Instagram' },
                { icon: Twitter,  label: 'Twitter' },
              ].map(({ icon: Icon, label }) => (
                <a
                  key={label}
                  href="#"
                  aria-label={label}
                  className="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-brand-600 transition-colors"
                >
                  <Icon size={16} />
                </a>
              ))}
            </div>
          </div>

          {/* Quick links */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">
              Explore
            </h4>
            <ul className="space-y-2.5">
              {QUICK_LINKS.map(({ to, label }) => (
                <li key={to}>
                  <Link
                    to={to}
                    className="text-sm text-slate-400 hover:text-white transition-colors"
                  >
                    {label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Account links */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">
              Account
            </h4>
            <ul className="space-y-2.5">
              {ACCOUNT_LINKS.map(({ to, label }) => (
                <li key={to}>
                  <Link
                    to={to}
                    className="text-sm text-slate-400 hover:text-white transition-colors"
                  >
                    {label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Contact / Info */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">
              Company
            </h4>
            <ul className="space-y-2.5 mb-5">
              {INFO_LINKS.map(({ to, label }) => (
                <li key={to}>
                  <Link
                    to={to}
                    className="text-sm text-slate-400 hover:text-white transition-colors"
                  >
                    {label}
                  </Link>
                </li>
              ))}
            </ul>
            <div className="space-y-2 text-sm text-slate-500">
              <div className="flex items-center gap-2">
                <Mail size={13} className="flex-shrink-0" />
                <span>hello@dealmachan.com</span>
              </div>
              <div className="flex items-center gap-2">
                <Phone size={13} className="flex-shrink-0" />
                <span>+91 98765 43210</span>
              </div>
              <div className="flex items-start gap-2">
                <MapPin size={13} className="flex-shrink-0 mt-0.5" />
                <span>Kerala, India</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom bar */}
      <div className="border-t border-slate-800">
        <div className="site-container py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
          <p>© {year} DealMachan. All rights reserved.</p>
          <div className="flex gap-5">
            <Link to="/privacy" className="hover:text-slate-300 transition-colors">Privacy Policy</Link>
            <Link to="/terms"   className="hover:text-slate-300 transition-colors">Terms of Service</Link>
            <Link to="/sitemap" className="hover:text-slate-300 transition-colors">Sitemap</Link>
          </div>
        </div>
      </div>
    </footer>
  )
}
