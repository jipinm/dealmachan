import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { ChevronLeft, Trophy, Clock, Users, CheckCircle2, Medal } from 'lucide-react'
import { contestsApi, type ContestDetail } from '@/api/endpoints/contests'

// ── Helpers ───────────────────────────────────────────────────────────────
function formatDate(d: string | null) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusBadge(status: string) {
  const map: Record<string, string> = {
    active:    'bg-green-100 text-green-700',
    completed: 'bg-gray-100 text-gray-600',
    cancelled: 'bg-red-100 text-red-600',
  }
  return map[status] ?? 'bg-gray-100 text-gray-600'
}

const POSITION_ICONS: Record<number, string> = { 1: '🥇', 2: '🥈', 3: '🥉' }

// ── Winners section ───────────────────────────────────────────────────────
function WinnersSection({ contest }: { contest: ContestDetail }) {
  if (contest.status !== 'completed' || !contest.winners.length) return null
  return (
    <div className="bg-amber-50 border border-amber-200 rounded-2xl p-5 space-y-3">
      <h3 className="font-bold text-amber-800 flex items-center gap-2">
        <Trophy size={18} className="text-amber-500" /> Winners
      </h3>
      <div className="space-y-2.5">
        {contest.winners.map(w => (
          <div key={w.position} className="flex items-start gap-3 bg-white rounded-xl px-4 py-3">
            <span className="text-2xl">{POSITION_ICONS[w.position] ?? `#${w.position}`}</span>
            <div className="flex-1">
              <p className="font-semibold text-sm text-gray-800">{w.customer_name}</p>
              {w.prize_details && (
                <p className="text-xs text-gray-500 mt-0.5">{w.prize_details}</p>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

// ── Participate button ────────────────────────────────────────────────────
function ParticipateButton({ contest }: { contest: ContestDetail }) {
  const qc = useQueryClient()
  const mutation = useMutation({
    mutationFn: () => contestsApi.participate(contest.id),
    onSuccess: (res) => {
      toast.success(res.message ?? 'You have entered the contest!')
      qc.invalidateQueries({ queryKey: ['contest', contest.id] })
      qc.invalidateQueries({ queryKey: ['contests'] })
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? 'Failed to enter contest.')
    },
  })

  if (contest.status !== 'active') {
    return (
      <div className="text-center py-2 text-sm text-gray-400">
        {contest.status === 'completed' ? 'Contest has ended' : 'Contest not active'}
      </div>
    )
  }

  const endDate = contest.end_date ? new Date(contest.end_date) : null
  const hasEnded = endDate && endDate < new Date()

  if (hasEnded) {
    return <div className="text-center py-2 text-sm text-gray-400">This contest has ended</div>
  }

  if (contest.has_entered) {
    return (
      <div className="flex items-center justify-center gap-2 bg-green-50 border border-green-200 rounded-xl py-3 text-sm font-semibold text-green-700">
        <CheckCircle2 size={18} /> You're entered!
      </div>
    )
  }

  return (
    <button
      onClick={() => mutation.mutate()}
      disabled={mutation.isPending}
      className="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl py-3 text-sm font-semibold"
    >
      {mutation.isPending ? 'Entering…' : 'Enter Contest — Free'}
    </button>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────
export default function ContestDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const contestId = Number(id)

  const { data: contest, isLoading, isError } = useQuery<ContestDetail>({
    queryKey: ['contest', contestId],
    queryFn: () => contestsApi.get(contestId),
    enabled: !!contestId,
    staleTime: 2 * 60 * 1000,
  })

  if (isLoading) {
    return (
      <div className="max-w-[1200px] mx-auto px-4 py-8 animate-pulse space-y-4">
        <div className="h-6 w-1/4 bg-gray-200 rounded" />
        <div className="h-8 w-3/4 bg-gray-200 rounded" />
        <div className="h-48 bg-gray-100 rounded-2xl" />
      </div>
    )
  }

  if (isError || !contest) {
    return (
      <div className="max-w-[1200px] mx-auto px-4 py-20 text-center space-y-3">
        <Trophy size={40} className="mx-auto text-gray-300" />
        <p className="font-semibold text-gray-700">Contest not found</p>
        <button onClick={() => navigate('/activity')} className="text-indigo-600 text-sm">← Back</button>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-[1200px] mx-auto px-4 py-3 flex items-center gap-3">
          <button onClick={() => navigate('/activity')} className="text-gray-500 hover:text-gray-700">
            <ChevronLeft size={22} />
          </button>
          <h1 className="font-bold text-gray-900 text-sm flex-1 line-clamp-1">{contest.title}</h1>
          <span className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${statusBadge(contest.status)}`}>
            {contest.status}
          </span>
        </div>
      </div>

      <div className="max-w-[1200px] mx-auto px-4 py-5 pb-24 space-y-4">

        {/* Hero section */}
        <div className="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-5 text-white">
          <div className="flex items-center gap-3 mb-3">
            <div className="w-11 h-11 bg-white/20 rounded-full flex items-center justify-center">
              <Trophy size={22} className="text-white" />
            </div>
            <div>
              <h2 className="font-bold text-base leading-tight">{contest.title}</h2>
            </div>
          </div>

          {/* Stats row */}
          <div className="grid grid-cols-2 gap-3 mt-3">
            <div className="bg-white/15 rounded-xl p-3 flex items-center gap-2">
              <Users size={16} />
              <div>
                <p className="text-xs text-white/70">Participants</p>
                <p className="font-bold text-sm">{contest.participant_count}</p>
              </div>
            </div>
            {contest.end_date && (
              <div className="bg-white/15 rounded-xl p-3 flex items-center gap-2">
                <Clock size={16} />
                <div>
                  <p className="text-xs text-white/70">
                    {contest.status === 'active' ? 'Ends' : 'Ended'}
                  </p>
                  <p className="font-bold text-sm">{formatDate(contest.end_date)}</p>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Description */}
        {contest.description && (
          <div className="bg-white border border-gray-200 rounded-2xl p-5">
            <h3 className="font-semibold text-gray-900 mb-2 text-sm">About</h3>
            <p className="text-sm text-gray-600 leading-relaxed">{contest.description}</p>
          </div>
        )}

        {/* Rules */}
        {contest.rules.length > 0 && (
          <div className="bg-white border border-gray-200 rounded-2xl p-5">
            <h3 className="font-semibold text-gray-900 mb-3 text-sm flex items-center gap-2">
              <Medal size={16} className="text-indigo-500" /> Contest Rules
            </h3>
            <ul className="space-y-2">
              {contest.rules.map((rule, i) => (
                <li key={i} className="flex items-start gap-2 text-sm text-gray-600">
                  <span className="flex-shrink-0 w-5 h-5 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center mt-0.5">
                    {i + 1}
                  </span>
                  {rule}
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Timeline */}
        {(contest.start_date || contest.end_date) && (
          <div className="bg-white border border-gray-200 rounded-2xl p-5">
            <h3 className="font-semibold text-gray-900 mb-3 text-sm">Timeline</h3>
            <div className="flex gap-6">
              {contest.start_date && (
                <div>
                  <p className="text-xs text-gray-400">Start Date</p>
                  <p className="font-semibold text-sm text-gray-700">{formatDate(contest.start_date)}</p>
                </div>
              )}
              {contest.end_date && (
                <div>
                  <p className="text-xs text-gray-400">End Date</p>
                  <p className="font-semibold text-sm text-gray-700">{formatDate(contest.end_date)}</p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* Winners */}
        <WinnersSection contest={contest} />

      </div>

      {/* CTA bar */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3">
        <div className="max-w-[1200px] mx-auto">
          <ParticipateButton contest={contest} />
        </div>
      </div>
    </div>
  )
}
