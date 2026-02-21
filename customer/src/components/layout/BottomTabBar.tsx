import type React from 'react'
import { NavLink } from 'react-router-dom'
import { Home, Compass, Ticket, Zap, User } from 'lucide-react'

const TABS: Array<{ to: string; icon: React.ElementType; label: string }> = [
  { to: '/',         icon: Home,    label: 'Home'     },
  { to: '/explore',  icon: Compass, label: 'Explore'  },
  { to: '/wallet',   icon: Ticket,  label: 'Wallet'   },
  { to: '/activity', icon: Zap,     label: 'Activity' },
  { to: '/profile',  icon: User,    label: 'Profile'  },
]

/**
 * Mobile-only bottom navigation bar.
 * Hidden on desktop (sidebar:hidden).
 */
export default function BottomTabBar() {
  return (
    <nav className="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-gray-100 pb-safe sidebar:hidden">
      <div className="flex items-stretch h-16">
        {TABS.map((tab) => (
          <NavLink
            key={tab.to}
            to={tab.to}
            end={tab.to === '/'}
            className={({ isActive }) =>
              `flex-1 flex flex-col items-center justify-center gap-0.5 py-2 transition-colors ${
                isActive ? 'text-brand-600' : 'text-gray-400'
              }`
            }
          >
            {({ isActive }) => (
              <>
                <tab.icon
                  size={20}
                  strokeWidth={isActive ? 2.5 : 1.8}
                />
                <span className="text-[10px] font-medium">{tab.label}</span>
              </>
            )}
          </NavLink>
        ))}
      </div>
    </nav>
  )
}
