import { useQuery } from '@tanstack/react-query'
import { Copy, Share2, Gift, Users, Clock, Award, CheckCircle2, ChevronRight } from 'lucide-react'
import toast from 'react-hot-toast'
import { referralsApi, ReferralHistoryItem } from '@/api/endpoints/referrals'
import PageLoader from '@/components/ui/PageLoader'

// ── Helpers ───────────────────────────────────────────────────────────────────

function statusBadge(status: ReferralHistoryItem['status']) {
  const map = {
    pending:   'bg-amber-100 text-amber-700',
    completed: 'bg-blue-100 text-blue-700',
    rewarded:  'bg-green-100 text-green-700',
  }
  const label = { pending: 'Pending', completed: 'Completed', rewarded: 'Rewarded' }
  return (
    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${map[status]}`}>
      {label[status]}
    </span>
  )
}

function fmt(dateStr: string) {
  return new Intl.DateTimeFormat('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }).format(
    new Date(dateStr)
  )
}

// ── Stat Card ────────────────────────────────────────────────────────────────

function StatCard({
  icon, label, value, accent,
}: {
  icon: React.ReactNode
  label: string
  value: string | number
  accent: string
}) {
  return (
    <div className={`flex flex-col items-center gap-1 rounded-2xl p-4 ${accent}`}>
      <div className="text-2xl">{icon}</div>
      <div className="text-xl font-bold text-slate-800">{value}</div>
      <div className="text-xs text-slate-500 text-center leading-tight">{label}</div>
    </div>
  )
}

// ── Main Page ─────────────────────────────────────────────────────────────────

export default function ReferralPage() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['referral'],
    queryFn: referralsApi.get,
    staleTime: 30_000,
  })

  // ── Copy code ──────────────────────────────────────────────────────────────
  const copyCode = async () => {
    if (!data?.referral_code) return
    await navigator.clipboard.writeText(data.referral_code)
    toast.success('Referral code copied!')
  }

  // ── Share ──────────────────────────────────────────────────────────────────
  const share = async () => {
    if (!data?.referral_code) return
    const text = `Join DealMachan and enjoy exclusive deals! Use my referral code: ${data.referral_code}`
    if (navigator.share) {
      await navigator.share({ title: 'DealMachan Referral', text }).catch(() => null)
    } else {
      await navigator.clipboard.writeText(text)
      toast.success('Share text copied to clipboard!')
    }
  }

  if (isLoading) return <PageLoader />

  if (isError || !data) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-slate-500 text-sm">Could not load referral data.</p>
      </div>
    )
  }

  const { referral_code, stats, history } = data

  return (
    <div className="min-h-screen bg-gradient-to-b from-brand-50 to-slate-50 pb-20">

      {/* ── Hero / Code Card ── */}
      <div className="bg-gradient-to-br from-brand-500 to-brand-700 pt-12 pb-20 px-5 text-center">
        <div className="flex items-center justify-center gap-2 mb-3">
          <Gift className="w-6 h-6 text-brand-100" />
          <span className="text-brand-100 font-semibold text-sm tracking-wide uppercase">
            Invite &amp; Earn
          </span>
        </div>
        <h1 className="text-2xl font-extrabold text-white mb-1">Your Referral Code</h1>
        <p className="text-brand-200 text-sm mb-7">
          Share your code with friends. Earn rewards when they join!
        </p>

        {/* Code pill */}
        <div className="inline-flex items-center gap-3 bg-white/20 backdrop-blur-sm border border-white/30
                        rounded-2xl px-6 py-4 mb-6">
          <span className="text-3xl font-extrabold tracking-widest text-white font-mono">
            {referral_code}
          </span>
          <button
            onClick={copyCode}
            className="bg-white/25 hover:bg-white/40 transition-colors rounded-xl p-2"
            aria-label="Copy referral code"
          >
            <Copy className="w-5 h-5 text-white" />
          </button>
        </div>

        {/* Action buttons */}
        <div className="flex gap-3 justify-center">
          <button
            onClick={copyCode}
            className="flex items-center gap-2 bg-white text-brand-600 font-semibold text-sm
                       rounded-xl px-5 py-2.5 shadow-md hover:shadow-lg transition-all active:scale-95"
          >
            <Copy className="w-4 h-4" />
            Copy Code
          </button>
          <button
            onClick={share}
            className="flex items-center gap-2 bg-brand-400 text-white font-semibold text-sm
                       rounded-xl px-5 py-2.5 shadow-md hover:shadow-lg transition-all active:scale-95"
          >
            <Share2 className="w-4 h-4" />
            Share
          </button>
        </div>
      </div>

      {/* ── Content (overlaps hero) ── */}
      <div className="-mt-10 px-4 space-y-5 max-w-[1200px] mx-auto">

        <div className="lg:grid lg:grid-cols-2 lg:gap-5 space-y-5 lg:space-y-0">

        {/* Stats grid */}
        <div className="bg-white rounded-3xl shadow-lg p-5">
          <h2 className="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">
            Your Stats
          </h2>
          <div className="grid grid-cols-2 gap-3">
            <StatCard
              icon={<Users className="w-5 h-5 text-blue-500" />}
              label="Total Referrals"
              value={stats.total}
              accent="bg-blue-50"
            />
            <StatCard
              icon={<CheckCircle2 className="w-5 h-5 text-green-500" />}
              label="Completed"
              value={stats.completed}
              accent="bg-green-50"
            />
            <StatCard
              icon={<Clock className="w-5 h-5 text-amber-500" />}
              label="Pending"
              value={stats.pending}
              accent="bg-amber-50"
            />
            <StatCard
              icon={<Award className="w-5 h-5 text-purple-500" />}
              label="Total Rewards"
              value={stats.total_rewards > 0 ? `₹${stats.total_rewards}` : '—'}
              accent="bg-purple-50"
            />
          </div>
        </div>

        {/* How it works */}
        <div className="bg-white rounded-3xl shadow-lg p-5">
          <h2 className="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">
            How It Works
          </h2>
          <div className="space-y-3">
            {[
              { step: '1', title: 'Share Your Code', desc: 'Send your unique referral code to friends via WhatsApp, SMS, or social media.' },
              { step: '2', title: 'Friend Signs Up', desc: 'Your friend registers on DealMachan using your referral code.' },
              { step: '3', title: 'Both Earn Rewards', desc: 'Once they complete signup, both you and your friend receive exclusive rewards!' },
            ].map(({ step, title, desc }) => (
              <div key={step} className="flex gap-4">
                <div className="flex-none w-8 h-8 bg-brand-100 text-brand-600 font-bold text-sm
                               rounded-full flex items-center justify-center">
                  {step}
                </div>
                <div>
                  <div className="font-semibold text-sm text-slate-800">{title}</div>
                  <div className="text-xs text-slate-500 mt-0.5 leading-relaxed">{desc}</div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Referral history */}
        <div className="bg-white rounded-3xl shadow-lg p-5">
          <h2 className="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">
            Referral History
          </h2>
          {history.length === 0 ? (
            <div className="py-8 text-center">
              <Users className="w-10 h-10 text-slate-200 mx-auto mb-2" />
              <p className="text-sm text-slate-400">No referrals yet</p>
              <p className="text-xs text-slate-400 mt-1">Share your code to get started!</p>
            </div>
          ) : (
            <ul className="divide-y divide-slate-100">
              {history.map((item) => (
                <li key={item.id} className="py-3 flex items-center gap-3">
                  <div className="flex-none w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center">
                    <Users className="w-4 h-4 text-slate-400" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="text-sm font-medium text-slate-800 truncate">
                        {item.referee_name ?? 'Anonymous'}
                      </span>
                      {statusBadge(item.status)}
                    </div>
                    <div className="text-xs text-slate-400 mt-0.5">{fmt(item.created_at)}</div>
                  </div>
                  {item.reward_given === 1 && item.reward_amount && (
                    <div className="flex-none text-sm font-bold text-green-600">
                      +₹{parseFloat(item.reward_amount).toFixed(0)}
                    </div>
                  )}
                  <ChevronRight className="w-4 h-4 text-slate-300 flex-none" />
                </li>
              ))}
            </ul>
          )}
        </div>

        </div>{/* /lg:grid */}
      </div>
    </div>
  )
}
