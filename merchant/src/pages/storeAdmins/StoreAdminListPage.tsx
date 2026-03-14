import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Plus, UserCog, Store, Phone, Mail, Clock, CheckCircle, XCircle } from 'lucide-react'
import { storeAdminApi, type StoreAdmin } from '@/api/endpoints/storeAdmins'
import { SkeletonCard } from '@/components/ui/PageLoader'

// ── Helpers ───────────────────────────────────────────────────────────────────

function initials(name: string): string {
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? '')
    .join('')
}

function timeAgo(isoDate: string): string {
  const diff = Date.now() - new Date(isoDate).getTime()
  const mins  = Math.floor(diff / 60000)
  if (mins <  1)  return 'just now'
  if (mins < 60)  return `${mins}m ago`
  const hrs = Math.floor(mins / 60)
  if (hrs  < 24)  return `${hrs}h ago`
  const days = Math.floor(hrs / 24)
  if (days <  7)  return `${days}d ago`
  return new Date(isoDate).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
}

// ── Store admin card ──────────────────────────────────────────────────────────

function StoreAdminCard({ admin, onClick }: { admin: StoreAdmin; onClick: () => void }) {
  const isActive = admin.status === 'active'

  return (
    <div
      onClick={onClick}
      className="bg-white rounded-2xl shadow-sm p-4 flex items-center gap-3 active:bg-gray-50 cursor-pointer transition-colors"
    >
      {/* Initials avatar */}
      <div className="w-12 h-12 rounded-2xl bg-brand-100 flex items-center justify-center shrink-0">
        <span className="text-brand-700 font-bold text-sm">{initials(admin.name)}</span>
      </div>

      {/* Info */}
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2 mb-0.5">
          <p className="text-sm font-bold text-gray-900 truncate">{admin.name}</p>
          {isActive ? (
            <span className="flex items-center gap-0.5 text-[10px] font-bold text-green-700 bg-green-100 px-1.5 py-0.5 rounded-full shrink-0">
              <CheckCircle size={9} />Active
            </span>
          ) : (
            <span className="flex items-center gap-0.5 text-[10px] font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded-full shrink-0">
              <XCircle size={9} />Inactive
            </span>
          )}
        </div>

        <p className="flex items-center gap-1 text-xs text-gray-500 truncate">
          <Mail size={11} className="shrink-0" />{admin.email}
        </p>

        {admin.phone && (
          <p className="flex items-center gap-1 text-xs text-gray-400 truncate mt-0.5">
            <Phone size={11} className="shrink-0" />{admin.phone}
          </p>
        )}

        <div className="flex items-center gap-2 mt-1">
          {admin.store_name && (
            <span className="flex items-center gap-1 text-[11px] text-brand-600 font-medium">
              <Store size={11} />{admin.store_name}
            </span>
          )}
          {admin.last_login && (
            <span className="flex items-center gap-1 text-[11px] text-gray-400">
              <Clock size={10} />Last login {timeAgo(admin.last_login)}
            </span>
          )}
        </div>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function StoreAdminListPage() {
  const navigate = useNavigate()

  const { data, isLoading } = useQuery({
    queryKey: ['store-admins'],
    queryFn:  () => storeAdminApi.list().then((r) => r.data.data),
    staleTime: 2 * 60 * 1000,
  })

  return (
    <div className="min-h-full bg-gray-50 pb-24">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate(-1)}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0"
          >
            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-xl">Store Admins</h1>
            <p className="text-white/70 text-xs mt-0.5">
              {data ? `${data.length} admin${data.length !== 1 ? 's' : ''}` : 'Manage store staff logins'}
            </p>
          </div>
        </div>
      </div>

      <div className="px-4 pt-4 space-y-3">
        {isLoading ? (
          <>{[1, 2, 3].map((i) => <SkeletonCard key={i} />)}</>
        ) : !data || data.length === 0 ? (
          /* Empty state */
          <div className="flex flex-col items-center justify-center pt-20 gap-4 text-center px-8">
            <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
              <UserCog size={28} className="text-gray-300" />
            </div>
            <div>
              <p className="font-semibold text-gray-600">No store admins yet</p>
              <p className="text-xs text-gray-400 mt-1">
                Create a store admin to allow staff to manage a specific store.
              </p>
            </div>
            <button
              onClick={() => navigate('/store-admins/new')}
              className="px-5 py-2.5 gradient-brand text-white text-sm font-semibold rounded-xl"
            >
              Add First Store Admin
            </button>
          </div>
        ) : (
          /* Admin list */
          data.map((admin) => (
            <StoreAdminCard
              key={admin.id}
              admin={admin}
              onClick={() => navigate(`/store-admins/${admin.id}/edit`)}
            />
          ))
        )}
      </div>

      {/* FAB */}
      {data && data.length > 0 && (
        <button
          onClick={() => navigate('/store-admins/new')}
          className="fixed right-5 bottom-24 w-14 h-14 gradient-brand rounded-full shadow-lg flex items-center justify-center active:scale-95 transition-all"
        >
          <Plus size={24} className="text-white" />
        </button>
      )}
    </div>
  )
}
