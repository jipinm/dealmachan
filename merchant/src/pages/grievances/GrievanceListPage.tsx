import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ChevronLeft, ChevronRight, AlertCircle } from 'lucide-react'
import { grievanceApi, type Grievance, type GrievanceStatus } from '@/api/endpoints/grievances'

const TABS: { key: GrievanceStatus | 'all'; label: string }[] = [
  { key: 'all',         label: 'All' },
  { key: 'open',        label: 'Open' },
  { key: 'in_progress', label: 'In Progress' },
  { key: 'resolved',    label: 'Resolved' },
]

const STATUS_STYLE: Record<string, string> = {
  open:        'bg-red-100 text-red-700',
  in_progress: 'bg-amber-100 text-amber-700',
  resolved:    'bg-green-100 text-green-700',
  closed:      'bg-gray-100 text-gray-500',
}
const PRIORITY_STYLE: Record<string, string> = {
  urgent: 'bg-red-500 text-white',
  high:   'bg-orange-100 text-orange-700',
  medium: 'bg-blue-100 text-blue-700',
  low:    'bg-gray-100 text-gray-500',
}

function GrievanceCard({ g }: { g: Grievance }) {
  const navigate = useNavigate()
  const date = new Date(g.created_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })

  return (
    <button
      onClick={() => navigate(`/grievances/${g.id}`)}
      className="w-full bg-white rounded-2xl shadow-sm p-4 text-left hover:shadow-md transition-shadow active:scale-[0.99]"
    >
      <div className="flex items-start justify-between gap-2">
        <p className="font-semibold text-gray-800 text-sm flex-1 leading-snug">{g.subject}</p>
        <ChevronRight size={16} className="text-gray-400 shrink-0 mt-0.5" />
      </div>
      <div className="flex items-center gap-2 mt-2 flex-wrap">
        <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full uppercase ${STATUS_STYLE[g.status]}`}>
          {g.status.replace('_', ' ')}
        </span>
        <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase ${PRIORITY_STYLE[g.priority]}`}>
          {g.priority}
        </span>
        {g.store_name && (
          <span className="text-[10px] text-gray-500">{g.store_name}</span>
        )}
      </div>
      <div className="flex items-center justify-between mt-2">
        <p className="text-xs text-gray-500">{g.customer_name ?? 'Customer'}</p>
        <p className="text-xs text-gray-400">{date}</p>
      </div>
    </button>
  )
}

export default function GrievanceListPage() {
  const navigate = useNavigate()
  const [tab, setTab] = useState<GrievanceStatus | 'all'>('all')

  const { data, isLoading } = useQuery({
    queryKey: ['grievances', tab],
    queryFn:  () => grievanceApi.list(tab !== 'all' ? { status: tab } : {}).then(r => ({
      items: r.data.data,
      counts: r.data.meta?.counts ?? { open: 0, in_progress: 0, resolved: 0, closed: 0 },
    })),
  })

  const items  = data?.items  ?? []
  const counts = data?.counts ?? { open: 0, in_progress: 0, resolved: 0, closed: 0 }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate(-1)}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30"
          >
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div>
            <h1 className="text-white font-bold text-xl">Grievances</h1>
            <p className="text-white/70 text-xs mt-0.5">
              {counts.open} open · {counts.in_progress} in progress
            </p>
          </div>
        </div>

        {/* Tabs */}
        <div className="flex gap-2 mt-4 overflow-x-auto pb-0.5">
          {TABS.map(t => (
            <button
              key={t.key}
              onClick={() => setTab(t.key)}
              className={`px-4 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors ${
                tab === t.key
                  ? 'bg-white text-brand-700'
                  : 'bg-white/20 text-white hover:bg-white/30'
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>
      </div>

      <div className="px-4 py-4 space-y-3">
        {isLoading ? (
          Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="bg-white rounded-2xl shadow-sm p-4 animate-pulse">
              <div className="h-4 bg-gray-100 rounded w-3/4 mb-2" />
              <div className="h-3 bg-gray-100 rounded w-1/2" />
            </div>
          ))
        ) : items.length === 0 ? (
          <div className="py-20 text-center">
            <AlertCircle size={40} className="text-gray-300 mx-auto mb-3" />
            <p className="text-gray-500 font-medium">No grievances found</p>
            <p className="text-gray-400 text-sm mt-1">You're all clear!</p>
          </div>
        ) : (
          items.map(g => <GrievanceCard key={g.id} g={g} />)
        )}
        <div className="h-4" />
      </div>
    </div>
  )
}
