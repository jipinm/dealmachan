import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Send, CheckCircle, User, Store, Calendar } from 'lucide-react'
import toast from 'react-hot-toast'
import { grievanceApi } from '@/api/endpoints/grievances'

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

export default function GrievanceDetailPage() {
  const { id }     = useParams<{ id: string }>()
  const navigate   = useNavigate()
  const qc         = useQueryClient()
  const [response, setResponse] = useState('')
  const [showResolveConfirm, setResolveConfirm] = useState(false)

  const { data: grievance, isLoading } = useQuery({
    queryKey: ['grievance', id],
    queryFn:  () => grievanceApi.get(Number(id)).then(r => r.data.data),
    enabled:  !!id,
  })

  const respondMutation = useMutation({
    mutationFn: (notes: string) => grievanceApi.respond(Number(id), notes),
    onSuccess: () => {
      toast.success('Response submitted')
      qc.invalidateQueries({ queryKey: ['grievance', id] })
      qc.invalidateQueries({ queryKey: ['grievances'] })
      setResponse('')
    },
    onError: () => toast.error('Failed to submit response'),
  })

  const resolveMutation = useMutation({
    mutationFn: () => grievanceApi.resolve(Number(id)),
    onSuccess: () => {
      toast.success('Grievance resolved')
      qc.invalidateQueries({ queryKey: ['grievance', id] })
      qc.invalidateQueries({ queryKey: ['grievances'] })
      setResolveConfirm(false)
    },
    onError: () => toast.error('Failed to resolve'),
  })

  if (isLoading || !grievance) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="gradient-brand px-5 pt-14 pb-6">
          <div className="flex items-center gap-3">
            <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
              <ChevronLeft size={20} className="text-white" />
            </button>
            <div className="h-5 bg-white/20 w-32 rounded animate-pulse" />
          </div>
        </div>
        <div className="p-4 space-y-3">
          {Array.from({ length: 3 }).map((_, i) => <div key={i} className="h-16 bg-white rounded-2xl animate-pulse" />)}
        </div>
      </div>
    )
  }

  const isClosed = grievance.status === 'resolved' || grievance.status === 'closed'
  const date     = new Date(grievance.created_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })

  return (
    <div className="min-h-screen bg-gray-50 pb-32">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate(-1)}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30"
          >
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-lg leading-snug line-clamp-1">{grievance.subject}</h1>
            <div className="flex items-center gap-2 mt-1">
              <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full uppercase ${STATUS_STYLE[grievance.status]}`}>
                {grievance.status.replace('_', ' ')}
              </span>
              <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full uppercase ${PRIORITY_STYLE[grievance.priority]}`}>
                {grievance.priority}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div className="px-4 py-4 space-y-4">
        {/* Meta info */}
        <div className="bg-white rounded-2xl shadow-sm p-4 space-y-3">
          <div className="flex items-center gap-3">
            <User size={14} className="text-gray-400" />
            <span className="text-sm text-gray-700">{grievance.customer_name ?? 'Customer'}</span>
            {grievance.customer_phone && <span className="text-xs text-gray-400">· {grievance.customer_phone}</span>}
          </div>
          {grievance.store_name && (
            <div className="flex items-center gap-3">
              <Store size={14} className="text-gray-400" />
              <span className="text-sm text-gray-700">{grievance.store_name}</span>
            </div>
          )}
          <div className="flex items-center gap-3">
            <Calendar size={14} className="text-gray-400" />
            <span className="text-sm text-gray-500">{date}</span>
          </div>
        </div>

        {/* Description */}
        <div className="bg-white rounded-2xl shadow-sm p-4">
          <p className="text-xs font-semibold text-gray-500 uppercase mb-2">Customer's Grievance</p>
          <p className="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{grievance.description}</p>
        </div>

        {/* Existing resolution notes */}
        {grievance.resolution_notes && (
          <div className="bg-brand-50 border border-brand-100 rounded-2xl p-4">
            <p className="text-xs font-semibold text-brand-700 uppercase mb-2">Your Response</p>
            <p className="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{grievance.resolution_notes}</p>
            {grievance.resolved_at && (
              <p className="text-xs text-brand-500 mt-2">
                Resolved: {new Date(grievance.resolved_at).toLocaleDateString('en-IN')}
              </p>
            )}
          </div>
        )}

        {/* Respond form */}
        {!isClosed && (
          <div className="bg-white rounded-2xl shadow-sm p-4">
            <p className="text-xs font-semibold text-gray-500 uppercase mb-3">Your Response</p>
            <textarea
              value={response}
              onChange={e => setResponse(e.target.value)}
              rows={4}
              placeholder="Write your response to this grievance…"
              className="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-brand-300"
            />
            <div className="flex gap-3 mt-3">
              <button
                onClick={() => response.trim() && respondMutation.mutate(response.trim())}
                disabled={!response.trim() || respondMutation.isPending}
                className="flex-1 btn-primary flex items-center justify-center gap-2"
              >
                <Send size={14} />
                {respondMutation.isPending ? 'Sending…' : 'Send Response'}
              </button>
              {!showResolveConfirm ? (
                <button
                  onClick={() => setResolveConfirm(true)}
                  className="px-4 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700"
                >
                  Resolve
                </button>
              ) : (
                <button
                  onClick={() => resolveMutation.mutate()}
                  disabled={resolveMutation.isPending}
                  className="px-4 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-xl flex items-center gap-1 hover:bg-green-700"
                >
                  <CheckCircle size={14} />
                  Confirm
                </button>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
