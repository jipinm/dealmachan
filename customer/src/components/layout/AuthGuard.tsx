import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'

/**
 * Protects routes that require authentication.
 * - If not authenticated → redirect to /login (preserving the intended path)
 * - If authenticated but is_new_user → redirect to /onboarding (once)
 */
export default function AuthGuard() {
  const { isAuthenticated, customer } = useAuthStore()
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  if (customer?.is_new_user && location.pathname !== '/onboarding') {
    return <Navigate to="/onboarding" replace />
  }

  return <Outlet />
}
