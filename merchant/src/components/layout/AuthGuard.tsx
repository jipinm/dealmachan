import { useEffect, useRef } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'

interface Props {
  children: React.ReactNode
}

export default function AuthGuard({ children }: Props) {
  const { isAuthenticated, accessToken, refreshToken, refresh, logout } = useAuthStore()
  const location = useLocation()
  const didRefresh = useRef(false)

  useEffect(() => {
    // If we have a refresh token but no access token (e.g. page reload), restore session
    if (!accessToken && refreshToken && !didRefresh.current) {
      didRefresh.current = true
      refresh().catch(() => logout())
    }
  }, [accessToken, refreshToken, refresh, logout])

  if (!isAuthenticated && !refreshToken) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  return <>{children}</>
}
