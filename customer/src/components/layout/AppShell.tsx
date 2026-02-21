import { Outlet } from 'react-router-dom'
import BottomTabBar from './BottomTabBar'
import Sidebar from './Sidebar'
import TopBar from './TopBar'

/**
 * Root shell for the Customer app.
 *
 * Mobile  (< 1024px): sticky TopBar + scrollable content + fixed BottomTabBar
 * Desktop (≥ 1024px): fixed left Sidebar + sticky TopBar + content area
 *
 * AuthGuard is applied by the router — do NOT wrap here again.
 */
export default function AppShell() {
  return (
    <div className="min-h-screen bg-[#f4f6fb] flex">
      {/* ── Desktop: Fixed left sidebar (hidden on mobile) ── */}
      <Sidebar />

      {/* ── Content column ── */}
      <div className="flex-1 flex flex-col min-h-screen sidebar:ml-64">
        {/* Sticky top bar */}
        <TopBar />

        {/* Page content
            • Mobile: pad bottom for BottomTabBar + safe area
            • Desktop: no bottom pad needed */}
        <main className="flex-1 overflow-y-auto pb-24 sidebar:pb-8">
          <Outlet />
        </main>
      </div>

      {/* ── Mobile: Fixed bottom tab bar (hidden on desktop) ── */}
      <BottomTabBar />
    </div>
  )
}
