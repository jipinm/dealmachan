import { Navigate } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'

interface Props {
  children: React.ReactNode
  /** Where to redirect a store admin. Defaults to /stores/:scopedStoreId or /home. */
  fallback?: string
}

/**
 * Blocks merchant-only routes from store admin users.
 * Store admins are silently redirected to their assigned store page (or a
 * custom `fallback` path) while regular merchants pass through unchanged.
 */
export default function StoreAdminGuard({ children, fallback }: Props) {
  const isStoreAdmin  = useAuthStore(s => s.isStoreAdmin())
  const scopedStoreId = useAuthStore(s => s.scopedStoreId())

  if (isStoreAdmin) {
    const to = fallback ?? (scopedStoreId ? `/stores/${scopedStoreId}` : '/home')
    return <Navigate to={to} replace />
  }

  return <>{children}</>
}
