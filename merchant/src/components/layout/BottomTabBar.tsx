import type React from 'react'
import { NavLink } from 'react-router-dom'
import { Home, Ticket, ScanLine, BarChart2, MoreHorizontal } from 'lucide-react'

const TABS: Array<{ to: string; icon: React.ElementType; label: string; fab?: boolean }> = [
  { to: '/', icon: Home, label: 'Home' },
  { to: '/coupons', icon: Ticket, label: 'Coupons' },
  { to: '/scan', icon: ScanLine, label: 'Scan', fab: true },
  { to: '/analytics', icon: BarChart2, label: 'Analytics' },
  { to: '/more', icon: MoreHorizontal, label: 'More' },
]

export default function BottomTabBar() {
  return (
    <nav className="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-gray-100 pb-safe">
      <div className="flex items-end h-16">
        {TABS.map((tab) =>
          tab.fab ? (
            <div key={tab.to} className="flex-1 flex justify-center">
              <NavLink
                to={tab.to}
                className={({ isActive }) =>
                  `relative -top-5 flex flex-col items-center justify-center w-14 h-14 rounded-full shadow-lg transition-all ${
                    isActive
                      ? 'bg-brand-700 text-white shadow-brand-700/50'
                      : 'bg-gradient-to-br from-brand-600 to-brand-700 text-white'
                  }`
                }
              >
                <tab.icon size={22} strokeWidth={2} />
                <span className="sr-only">{tab.label}</span>
              </NavLink>
            </div>
          ) : (
            <NavLink
              key={tab.to}
              to={tab.to}
              end={tab.to === '/'}
              className={({ isActive }) =>
                `flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-[10px] font-medium transition-colors ${
                  isActive ? 'text-brand-600' : 'text-gray-400'
                }`
              }
            >
              {({ isActive }) => (
                <>
                  <tab.icon
                    size={20}
                    strokeWidth={isActive ? 2.5 : 1.8}
                    className={isActive ? 'text-brand-600' : 'text-gray-400'}
                  />
                  <span>{tab.label}</span>
                </>
              )}
            </NavLink>
          ),
        )}
      </div>
    </nav>
  )
}
