import { useParams, Link, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  ArrowLeft, Clock, CheckCircle, XCircle, AlertTriangle,
  Store, MapPin, Loader2, MessageSquareWarning, Archive,
} from 'lucide-react'
import { grievancesApi, GrievanceStatus } from '@/api/endpoints/grievances'
import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

// ── Helpers ────────────────────────────────────────────────────────────────

const STATUS_CONFIG: Record<GrievanceStatus, { label: string; colorClass: string; Icon: React.ElementType }> = {
  open:        { label: 'Open',        colorClass: 'bg-yellow-100 text-yellow-700 border-yellow-200', Icon: Clock          },
  in_progress: { label: 'In Progress', colorClass: 'bg-blue-100   text-blue-700   border-blue-200',   Icon: AlertTriangle  },
  resolved:    { label: 'Resolved',    colorClass: 'bg-green-100  text-green-700  border-green-200',  Icon: CheckCircle    },
  closed:      { label: 'Closed',      colorClass: 'bg-slate-100  text-slate-600  border-slate-200',  Icon: XCircle        },
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleString('en-IN', {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}

// ── Page ─────────────────────────────────────────────────────────────────

export default function GrievanceDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data: grievance, isLoading, isError } = useQuery({
    queryKey: ['grievance', id],
    queryFn: () => grievancesApi.get(Number(id)),
    enabled: !!id,
    staleTime: 60_000,
  })

  const archiveMutation = useMutation({
    mutationFn: () => grievancesApi.archive(Number(id)),
    onSuccess: () => {
      toast.success('Grievance archived')
      queryClient.invalidateQueries({ queryKey: ['grievances'] })
      navigate('/grievances')
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-24">
        <Loader2 size={32} className="animate-spin text-brand-500" />
      </div>
    )
  }

  if (isError || !grievance) {
    return (
      <div className="max-w-[1200px] mx-auto px-4 py-20 text-center text-slate-400">
        <MessageSquareWarning size={48} className="mx-auto mb-4 opacity-30" />
        <p className="font-medium text-slate-500">Grievance not found.</p>
        <Link to="/grievances" className="btn-primary !py-2 !px-5 !rounded-xl !text-sm mt-5 inline-block">
          Back to Grievances
        </Link>
      </div>
    )
  }

  const cfg = STATUS_CONFIG[grievance.status]
  const StatusIcon = cfg.Icon

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6">
      {/* Back button */}
      <Link
        to="/grievances"
        className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 mb-5 transition-colors"
      >
        <ArrowLeft size={16} /> Back to Grievances
      </Link>

      {/* Status badge */}
      <div className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-medium border mb-4 ${cfg.colorClass}`}>
        <StatusIcon size={15} />
        {cfg.label}
      </div>

      {/* Subject */}
      <h1 className="font-heading font-bold text-2xl text-slate-800 mb-1">{grievance.subject}</h1>
      <p className="text-sm text-slate-400 mb-6">Filed on {fmtDate(grievance.created_at)}</p>

      {/* Description */}
      <div className="bg-white rounded-2xl border border-slate-100 p-5 mb-4">
        <h2 className="font-semibold text-sm text-slate-500 uppercase tracking-wide mb-3">Description</h2>
        <p className="text-slate-700 text-sm leading-relaxed whitespace-pre-wrap">{grievance.description}</p>
      </div>

      {/* Merchant / Store */}
      <div className="bg-white rounded-2xl border border-slate-100 p-5 mb-4">
        <h2 className="font-semibold text-sm text-slate-500 uppercase tracking-wide mb-3">Merchant</h2>
        <Link
          to={`/stores/${grievance.merchant_id}/${slugify(grievance.business_name)}`}
          className="flex items-center gap-3 group"
        >
          {grievance.business_logo ? (
            <img
              src={getImageUrl(grievance.business_logo)}
              alt={grievance.business_name}
              className="w-12 h-12 rounded-xl object-cover bg-slate-100"
            />
          ) : (
            <div className="w-12 h-12 rounded-xl gradient-brand flex items-center justify-center text-white font-bold">
              {grievance.business_name.charAt(0)}
            </div>
          )}
          <div>
            <p className="font-semibold text-slate-800 group-hover:text-brand-600 transition-colors">
              {grievance.business_name}
            </p>
            {grievance.store_name && (
              <div className="flex items-center gap-1 text-xs text-slate-500 mt-0.5">
                <Store size={12} />
                <span>{grievance.store_name}</span>
              </div>
            )}
            {grievance.store_address && (
              <div className="flex items-center gap-1 text-xs text-slate-400 mt-0.5">
                <MapPin size={12} />
                <span>{grievance.store_address}</span>
              </div>
            )}
          </div>
        </Link>
      </div>

      {/* Resolution (if any) */}
      {(grievance.status === 'resolved' || grievance.status === 'closed') && (
        <div className="bg-green-50 rounded-2xl border border-green-200 p-5">
          <h2 className="font-semibold text-sm text-green-700 uppercase tracking-wide mb-2 flex items-center gap-1.5">
            <CheckCircle size={15} /> Resolution
          </h2>
          {grievance.resolved_at && (
            <p className="text-xs text-green-600 mb-2">Resolved on {fmtDate(grievance.resolved_at)}</p>
          )}
          {grievance.resolution_notes ? (
            <p className="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">
              {grievance.resolution_notes}
            </p>
          ) : (
            <p className="text-sm text-slate-500 italic">No resolution notes provided.</p>
          )}
        </div>
      )}

      {/* Archive button — only for open or in_progress */}
      {(grievance.status === 'open' || grievance.status === 'in_progress') && (
        <div className="mt-6">
          <button
            onClick={() => archiveMutation.mutate()}
            disabled={archiveMutation.isPending}
            className="w-full flex items-center justify-center gap-2 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors disabled:opacity-50"
          >
            {archiveMutation.isPending
              ? <><Loader2 size={16} className="animate-spin" /> Archiving…</>
              : <><Archive size={16} /> Archive Grievance</>
            }
          </button>
          <p className="text-xs text-center text-slate-400 mt-2">
            Archiving closes this grievance without waiting for resolution.
          </p>
        </div>
      )}
    </div>
  )
}
