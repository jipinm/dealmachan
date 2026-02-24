import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  MessageSquareWarning, Clock, CheckCircle, XCircle,
  AlertTriangle, ChevronRight, PlusCircle, Loader2,
} from 'lucide-react'
import { grievancesApi, GrievanceStatus } from '@/api/endpoints/grievances'
import { getImageUrl } from '@/lib/imageUrl'

// ── Helpers ────────────────────────────────────────────────────────────────

const STATUS_CONFIG: Record<GrievanceStatus, { label: string; color: string; Icon: React.ElementType }> = {
  open:        { label: 'Open',        color: 'bg-yellow-100 text-yellow-700', Icon: Clock          },
  in_progress: { label: 'In Progress', color: 'bg-blue-100   text-blue-700',   Icon: AlertTriangle  },
  resolved:    { label: 'Resolved',    color: 'bg-green-100  text-green-700',  Icon: CheckCircle    },
  closed:      { label: 'Closed',      color: 'bg-slate-100  text-slate-600',  Icon: XCircle        },
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
}

type FilterTab = 'all' | GrievanceStatus

const TABS: { id: FilterTab; label: string }[] = [
  { id: 'all',        label: 'All'         },
  { id: 'open',       label: 'Open'        },
  { id: 'in_progress', label: 'In Progress' },
  { id: 'resolved',  label: 'Resolved'    },
  { id: 'closed',    label: 'Closed'      },
]

// ── Page ─────────────────────────────────────────────────────────────────

export default function GrievanceListPage() {
  const [tab, setTab] = useState<FilterTab>('all')

  const { data, isLoading, isError } = useQuery({
    queryKey: ['grievances', tab],
    queryFn: () => grievancesApi.list(tab !== 'all' ? { status: tab as GrievanceStatus } : {}),
    staleTime: 30_000,
  })

  const items = data?.data ?? []

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-5">
        <div>
          <h1 className="font-heading font-bold text-2xl text-slate-800">My Grievances</h1>
          <p className="text-sm text-slate-500 mt-0.5">Track complaints filed against merchants</p>
        </div>
        <Link
          to="/grievances/new"
          className="btn-primary !py-2.5 !px-4 !rounded-xl !text-sm inline-flex items-center gap-1.5"
        >
          <PlusCircle size={16} /> New
        </Link>
      </div>

      {/* Filter tabs */}
      <div className="flex items-center gap-2 flex-wrap mb-5">
        {TABS.map(t => (
          <button
            key={t.id}
            onClick={() => setTab(t.id)}
            className={`px-4 py-1.5 rounded-xl text-sm font-medium transition-all ${
              tab === t.id
                ? 'bg-brand-600 text-white'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="flex justify-center py-16">
          <Loader2 size={28} className="animate-spin text-brand-500" />
        </div>
      )}

      {/* Error */}
      {isError && (
        <div className="text-center py-10 text-slate-500">
          <p>Could not load grievances. Please try again.</p>
        </div>
      )}

      {/* Empty */}
      {!isLoading && !isError && items.length === 0 && (
        <div className="text-center py-16 text-slate-400">
          <MessageSquareWarning size={48} className="mx-auto mb-4 opacity-30" />
          <p className="font-medium text-slate-500">
            {tab === 'all' ? 'No grievances yet' : `No ${tab.replace('_', ' ')} grievances`}
          </p>
          <p className="text-sm mt-1">Had a bad experience? File a complaint and we'll help resolve it.</p>
          <Link to="/grievances/new" className="btn-primary !py-2 !px-5 !rounded-xl !text-sm mt-5 inline-block">
            File a Grievance
          </Link>
        </div>
      )}

      {/* List */}
      {!isLoading && items.length > 0 && (
        <div className="space-y-3">
          {items.map(g => {
            const cfg = STATUS_CONFIG[g.status]
            return (
              <Link
                key={g.id}
                to={`/grievances/${g.id}`}
                className="bg-white rounded-2xl border border-slate-100 p-4 flex items-start gap-3 hover:shadow-md transition-shadow"
              >
                {/* Merchant logo */}
                {g.business_logo ? (
                  <img
                    src={getImageUrl(g.business_logo)}
                    alt={g.business_name}
                    className="w-10 h-10 rounded-xl object-cover bg-slate-100 flex-shrink-0"
                  />
                ) : (
                  <div className="w-10 h-10 rounded-xl gradient-brand flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    {g.business_name.charAt(0)}
                  </div>
                )}

                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-2">
                    <p className="font-semibold text-sm text-slate-800 line-clamp-1">{g.subject}</p>
                    <span className={`text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0 ${cfg.color}`}>
                      {cfg.label}
                    </span>
                  </div>
                  <p className="text-xs text-slate-500 mt-0.5">{g.business_name}</p>
                  <p className="text-xs text-slate-400 mt-1">Filed {fmtDate(g.created_at)}</p>
                </div>

                <ChevronRight size={16} className="text-slate-300 flex-shrink-0 mt-1" />
              </Link>
            )
          })}
        </div>
      )}
    </div>
  )
}
