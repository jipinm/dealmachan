import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Tag, CheckCircle, Send } from 'lucide-react'
import toast from 'react-hot-toast'
import { labelApi, type Label } from '@/api/endpoints/labels'

function LabelChip({ label, assigned }: { label: Label; assigned: boolean }) {
  return (
    <div
      className={`flex items-center gap-2 px-3 py-2 rounded-xl border ${
        assigned
          ? 'border-brand-200 bg-brand-50'
          : 'border-gray-200 bg-white'
      }`}
    >
      <Tag size={14} className={assigned ? 'text-brand-600' : 'text-gray-400'} />
      <span className={`text-sm font-medium ${assigned ? 'text-brand-700' : 'text-gray-600'}`}>
        {label.label_name}
      </span>
      {assigned && <CheckCircle size={14} className="text-brand-500 ml-auto" />}
    </div>
  )
}

export default function LabelsPage() {
  const navigate = useNavigate()
  const qc       = useQueryClient()
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [note, setNote]             = useState('')
  const [showRequest, setShowRequest] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['labels'],
    queryFn:  () => labelApi.list().then(r => r.data.data),
  })

  const requestMutation = useMutation({
    mutationFn: ({ id, note }: { id: number; note: string }) => labelApi.request(id, note),
    onSuccess: (_, vars) => {
      toast.success('Request submitted! Admin will review.')
      qc.invalidateQueries({ queryKey: ['labels'] })
      setSelectedId(null)
      setNote('')
      setShowRequest(false)
    },
    onError: (err: any) => {
      const msg = err?.response?.data?.message ?? 'Failed to submit request'
      toast.error(msg)
    },
  })

  const assigned  = data?.assigned  ?? []
  const available = data?.available ?? []
  const assignedIds = new Set(assigned.map(l => l.id))

  // Available but not yet assigned
  const requestable = available.filter(l => !assignedIds.has(l.id))

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div>
            <h1 className="text-white font-bold text-xl">Labels</h1>
            <p className="text-white/70 text-xs mt-0.5">Trust badges shown on your profile</p>
          </div>
        </div>
      </div>

      <div className="px-4 py-4 space-y-5">
        {isLoading ? (
          <div className="space-y-3">
            {Array.from({ length: 4 }).map((_, i) => <div key={i} className="h-10 bg-white rounded-xl animate-pulse" />)}
          </div>
        ) : (
          <>
            {/* Assigned labels */}
            <div className="bg-white rounded-2xl shadow-sm p-4">
              <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Your Labels</p>
              {assigned.length === 0 ? (
                <p className="text-sm text-gray-400 text-center py-4">No labels assigned yet</p>
              ) : (
                <div className="space-y-2">
                  {assigned.map(l => (
                    <LabelChip key={l.id} label={l} assigned />
                  ))}
                </div>
              )}
            </div>

            {/* Request labels */}
            {requestable.length > 0 && (
              <div className="bg-white rounded-2xl shadow-sm p-4">
                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Request a Label</p>
                <p className="text-xs text-gray-400 mb-3">Select a label to request it from admin</p>

                <div className="space-y-2">
                  {requestable.map(l => (
                    <button
                      key={l.id}
                      onClick={() => { setSelectedId(l.id === selectedId ? null : l.id); setShowRequest(true) }}
                      className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border transition-colors text-left ${
                        selectedId === l.id
                          ? 'border-brand-400 bg-brand-50'
                          : 'border-gray-200 hover:border-gray-300'
                      }`}
                    >
                      <Tag size={14} className="text-gray-400 shrink-0" />
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gray-700">{l.label_name}</p>
                        {l.description && <p className="text-xs text-gray-400 truncate">{l.description}</p>}
                      </div>
                      {selectedId === l.id && <CheckCircle size={16} className="text-brand-500 shrink-0" />}
                    </button>
                  ))}
                </div>

                {showRequest && selectedId !== null && (
                  <div className="mt-4 space-y-3">
                    <div>
                      <label className="block text-xs font-medium text-gray-600 mb-1">Note (optional)</label>
                      <textarea
                        value={note}
                        onChange={e => setNote(e.target.value)}
                        rows={3}
                        placeholder="Why do you qualify for this label?"
                        className="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-brand-300"
                      />
                    </div>
                    <button
                      onClick={() => requestMutation.mutate({ id: selectedId!, note })}
                      disabled={requestMutation.isPending}
                      className="btn-primary w-full flex items-center justify-center gap-2"
                    >
                      <Send size={14} />
                      {requestMutation.isPending ? 'Submitting…' : 'Submit Request'}
                    </button>
                  </div>
                )}
              </div>
            )}
          </>
        )}
      </div>
    </div>
  )
}
