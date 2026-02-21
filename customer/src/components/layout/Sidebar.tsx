import { NavLink, useNavigate } from 'react-router-dom'
import {
  Home, Compass, Ticket, Zap, User, Bell, MessageSquare,
  ChevronRight, LogOut, Gift, History
} from 'lucide-react'
import { useAuthStore } from '@/store/authStore'
import { useQuery } from '@tanstack/react-query'
import { notificationsApi } from '@/api/endpoints/notifications'

interface NavSection {
  label?: string
  items: Array<{
    to: string
    icon: React.ElementType
    label: string
    exact?: boolean
    children?: Array<{ to: string; label: string }>
  }>
}

const NAV_SECTIONS: NavSection[] = [
  {
    items: [
      { to: '/',         icon: Home,    label: 'Home',       exact: true },
      { to: '/explore',  icon: Compass, label: 'Explore'    },
    ],
  },
  {
    label: 'My Deals',
    items: [
      { to: '/wallet',   icon: Ticket,  label: 'Wallet',
        children: [
          { to: '/wallet',         label: 'Saved Coupons' },
          { to: '/wallet/gifts',   label: 'Gift Inbox' },
          { to: '/wallet/history', label: 'Redeemed' },
        ],
      },
    ],
  },
  {
    label: 'Engage',
    items: [
      { to: '/activity',  icon: Zap,   label: 'Activity',
        children: [
          { to: '/activity/surveys',    label: 'Surveys' },
          { to: '/activity/tasks',      label: 'Mystery Shopping' },
          { to: '/activity/contests',   label: 'Contests' },
        ],
      },
    ],
  },
  {
    label: 'Account',
    items: [
      { to: '/notifications', icon: Bell,           label: 'Notifications' },
      { to: '/grievances',    icon: MessageSquare,  label: 'Grievances'    },
      { to: '/referrals',     icon: Gift,           label: 'Refer Friends' },
      { to: '/profile',       icon: User,           label: 'Profile'       },
    ],
  },
]

function NavItem({
  to,
  icon: Icon,
  label,
  exact,
}: {
  to: string
  icon: React.ElementType
  label: string
  exact?: boolean
}) {
  return (
    <NavLink
      to={to}
      end={exact}
      className={({ isActive }) =>
        `flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
          isActive
            ? 'bg-brand-600/10 text-brand-600 font-semibold'
            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
        }`
      }
    >
      <Icon size={18} strokeWidth={1.8} />
      <span className="flex-1">{label}</span>
    </NavLink>
  )
}

/**
 * Desktop-only left sidebar navigation.
 * Hidden on mobile (hidden sidebar:flex).
 */
export default function Sidebar() {
  const { customer, logout } = useAuthStore()
  const navigate = useNavigate()

  const { data: notifData } = useQuery({
    queryKey: ['notifications-unread-count'],
    queryFn: () => notificationsApi.getUnreadCount().then((r) => r.data.data ?? null),
    staleTime: 30_000,
    enabled: !!customer,
  })

  const unreadCount = notifData?.count ?? 0

  function handleLogout() {
    logout()
    navigate('/login')
  }

  return (
    <aside className="hidden sidebar:flex fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-100 flex-col">
      {/* Logo */}
      <div className="flex items-center gap-3 px-5 py-5 border-b border-gray-100">
        <div className="w-9 h-9 rounded-xl gradient-brand flex items-center justify-center shadow-brand">
          <span className="text-white font-heading font-bold text-lg">D</span>
        </div>
        <div>
          <p className="font-heading font-bold text-gray-900 text-base leading-tight">Deal Machan</p>
          <p className="text-xs text-gray-400 leading-tight">Discover · Save · Redeem</p>
        </div>
      </div>

      {/* User chip */}
      {customer && (
        <div className="mx-4 mt-4 mb-2 p-3 bg-brand-50 rounded-xl flex items-center gap-3">
          <div className="w-9 h-9 rounded-full gradient-brand flex items-center justify-center shrink-0">
            {customer.profile_image ? (
              <img src={customer.profile_image} alt={customer.name} className="w-9 h-9 rounded-full object-cover" />
            ) : (
              <span className="text-white font-bold text-sm">{customer.name.charAt(0).toUpperCase()}</span>
            )}
          </div>
          <div className="min-w-0">
            <p className="text-sm font-semibold text-gray-900 truncate">{customer.name}</p>
            <p className="text-xs text-gray-500 truncate">{customer.email || customer.phone}</p>
          </div>
          <NavLink to="/profile" className="shrink-0">
            <ChevronRight size={16} className="text-gray-400" />
          </NavLink>
        </div>
      )}

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto px-3 py-2 space-y-4">
        {NAV_SECTIONS.map((section, si) => (
          <div key={si}>
            {section.label && (
              <p className="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                {section.label}
              </p>
            )}
            <div className="space-y-0.5">
              {section.items.map((item) => (
                <div key={item.to}>
                  <div className="relative">
                    <NavItem to={item.to} icon={item.icon} label={item.label} exact={item.exact} />
                    {item.to === '/notifications' && unreadCount > 0 && (
                      <span className="absolute right-3 top-2.5 min-w-[18px] h-[18px] px-1 bg-cta-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                        {unreadCount > 99 ? '99+' : unreadCount}
                      </span>
                    )}
                  </div>
                  {item.children && (
                    <div className="ml-6 mt-0.5 space-y-0.5 pl-3 border-l border-gray-100">
                      {item.children.map((child) => (
                        <NavLink
                          key={child.to}
                          to={child.to}
                          end
                          className={({ isActive }) =>
                            `block py-1.5 px-2 text-xs rounded-lg transition-colors ${
                              isActive ? 'text-brand-600 font-semibold' : 'text-gray-500 hover:text-gray-800'
                            }`
                          }
                        >
                          {child.label}
                        </NavLink>
                      ))}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        ))}
      </nav>

      {/* Bottom actions */}
      <div className="px-3 py-4 border-t border-gray-100 space-y-0.5">
        <NavLink
          to="/wallet/history"
          className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
        >
          <History size={18} strokeWidth={1.8} />
          Redemption History
        </NavLink>
        <button
          onClick={handleLogout}
          className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-500 hover:bg-red-50 transition-colors"
        >
          <LogOut size={18} strokeWidth={1.8} />
          Logout
        </button>
      </div>
    </aside>
  )
}
