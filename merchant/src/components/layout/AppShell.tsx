import { Outlet } from 'react-router-dom'
import BottomTabBar from './BottomTabBar'

// Note: AuthGuard is already applied by the router — do NOT wrap here again.
// A double-guard causes two simultaneous refresh() calls on page reload,
// the second of which gets a 401 and triggers logout().
export default function AppShell() {
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      {/* Page content — add bottom padding to clear the nav bar */}
      <main className="flex-1 pb-24">
        <Outlet />
      </main>
      <BottomTabBar />
    </div>
  )
}
