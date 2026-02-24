import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'
import { useLocationStore } from '@/store/locationStore'

/**
 * Protects routes that require authentication.
 * - If not authenticated → redirect to /login (preserving the intended path)
 * - If authenticated but temp_password === 1 → redirect to /profile/set-password
 * - If authenticated and is_new_user AND no location chosen yet → redirect to /onboarding
 *   (if location is already stored — auto-detected or previously set — skip onboarding)
 */
export default function AuthGuard() {
  const { isAuthenticated, customer } = useAuthStore()
  const { cityId } = useLocationStore()
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  // Mandatory password reset (temp_password flag from API)
  if (
    customer?.temp_password === 1 &&
    location.pathname !== '/profile/set-password'
  ) {
    return <Navigate to="/profile/set-password" replace />
  }

  // Only redirect to onboarding when the user is truly new AND no location
  // has been set yet (either by auto-detect or by a previous manual selection).
  if (customer?.is_new_user && !cityId && location.pathname !== '/onboarding') {
    return <Navigate to="/onboarding" replace />
  }

  return <Outlet />
}
