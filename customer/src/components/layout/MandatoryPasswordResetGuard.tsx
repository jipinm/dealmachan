import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'

/**
 * MandatoryPasswordResetGuard
 *
 * Placed between AuthGuard and AppShell. After the user is confirmed logged in,
 * checks whether their account has `temp_password = 1` (set when the account was
 * auto-created or an admin reset their password).
 *
 * If the flag is set and the user is NOT already on the set-password page,
 * redirect them there so they must choose a new password before using the app.
 */
export default function MandatoryPasswordResetGuard() {
  const { customer } = useAuthStore()
  const { pathname }  = useLocation()

  const mustReset = customer?.temp_password === 1
  const onResetPage = pathname === '/profile/set-password'

  if (mustReset && !onResetPage) {
    return <Navigate to="/profile/set-password" replace />
  }

  return <Outlet />
}
