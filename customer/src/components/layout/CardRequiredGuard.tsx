import { Outlet } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Loader2 } from 'lucide-react'
import { profileApi } from '@/api/endpoints/profile'
import NoCardPrompt from '@/components/layout/NoCardPrompt'
import { useAuthStore } from '@/store/authStore'

function isCardActive(status?: string | null) {
  return status === 'activated' || status === 'active'
}

export default function CardRequiredGuard() {
  const { isAuthenticated } = useAuthStore()

  const { data, isLoading } = useQuery({
    queryKey: ['my-card-required-guard'],
    queryFn: () => profileApi.getCard().then((r) => r.data.data),
    enabled: isAuthenticated,
    staleTime: 120000,
  })

  if (isLoading) {
    return (
      <div className="site-container py-12 text-center text-slate-500">
        <Loader2 className="mx-auto h-6 w-6 animate-spin" />
      </div>
    )
  }

  const hasActiveCard = isCardActive((data as any)?.status)

  if (!hasActiveCard) {
    return <NoCardPrompt />
  }

  return <Outlet />
}
