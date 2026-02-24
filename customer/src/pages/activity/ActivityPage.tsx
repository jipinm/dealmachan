import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { publicApi } from '@/api/endpoints/public'
import { surveysApi } from '@/api/endpoints/surveys'
import { Trophy, ClipboardList, Search, MessageSquare, ChevronRight, Sparkles, Clock } from 'lucide-react'

// ── Contest card ──────────────────────────────────────────────────────────

function ContestCard({ contest }: { contest: any }) {
  return (
    <Link to={`/contests/${contest.id}`} className="card card-hover p-4 flex items-start gap-3 block">
      <div className="w-10 h-10 rounded-xl gradient-cta flex items-center justify-center flex-shrink-0">
        <Trophy size={18} className="text-white" />
      </div>
      <div className="flex-1 min-w-0">
        <h3 className="font-semibold text-slate-800 text-sm line-clamp-1">{contest.title}</h3>
        {contest.description && (
          <p className="text-xs text-slate-500 mt-0.5 line-clamp-2">{contest.description}</p>
        )}
        {contest.end_date && (
          <span className="flex items-center gap-1 text-xs text-slate-400 mt-1">
            <Clock size={11} /> Ends {new Date(contest.end_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}
          </span>
        )}
      </div>
      <div className="flex-shrink-0 self-center">
        <span className="inline-flex items-center gap-1 text-xs font-semibold text-cta-600 bg-cta-50 px-2.5 py-1 rounded-full">
          Enter
        </span>
      </div>
    </Link>
  )
}

// ── Coming-soon section ───────────────────────────────────────────────────

function ComingSoonSection({
  icon: Icon,
  color,
  title,
  description,
  badge,
}: {
  icon: React.ElementType
  color: string
  title: string
  description: string
  badge?: string
}) {
  return (
    <div className="card p-5 relative overflow-hidden">
      <div className="flex items-start gap-3">
        <div className={`w-11 h-11 rounded-xl ${color} flex items-center justify-center flex-shrink-0`}>
          <Icon size={20} className="text-white" />
        </div>
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-1">
            <h3 className="font-semibold text-slate-800 text-sm">{title}</h3>
            {badge && (
              <span className="text-[10px] font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">{badge}</span>
            )}
          </div>
          <p className="text-xs text-slate-500 leading-relaxed">{description}</p>
        </div>
      </div>
      {/* Decorative blur circle */}
      <div className="absolute -right-4 -bottom-4 w-20 h-20 rounded-full bg-slate-100/80" />
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────

export default function ActivityPage() {
  const { data: contestsData, isLoading: contestsLoading } = useQuery({
    queryKey: ['public-contests'],
    queryFn: () => publicApi.getContests().then((r) => r.data.data ?? []),
    staleTime: 300_000,
  })

  const { data: surveysData, isLoading: surveysLoading } = useQuery({
    queryKey: ['surveys'],
    queryFn: () => surveysApi.list(),
    staleTime: 300_000,
  })

  const contests = (contestsData as any[]) ?? []
  const surveys = surveysData ?? []

  return (
      <div className="max-w-[1200px] mx-auto pb-8">
      {/* Hero */}
      <div className="px-4 pt-6 pb-5">
        <div className="rounded-3xl gradient-brand p-6 text-white relative overflow-hidden">
          <div className="absolute right-0 top-0 w-32 h-32 rounded-full bg-white/10 -translate-y-8 translate-x-8" />
          <div className="absolute right-8 bottom-0 w-20 h-20 rounded-full bg-white/5 translate-y-6" />
          <div className="flex items-center gap-3 mb-2">
            <Sparkles size={22} />
            <h1 className="font-heading font-bold text-xl">Activity Hub</h1>
          </div>
          <p className="text-white/80 text-sm leading-relaxed">
            Earn rewards by participating in contests, completing surveys, and mystery shopping missions.
          </p>
        </div>
      </div>

      <div className="px-4">
        <div className="lg:grid lg:grid-cols-2 lg:gap-8 space-y-6 lg:space-y-0">

          {/* Left column — Contests */}
          <div className="space-y-6">
        {/* Contests */}
        <section>
          <div className="flex items-center justify-between mb-3">
            <h2 className="font-heading font-bold text-base text-slate-800 flex items-center gap-2">
              <Trophy size={16} className="text-cta-500" /> Contests
            </h2>
            <span className="text-xs text-slate-400">{contests.length > 0 ? `${contests.length} active` : 'Coming soon'}</span>
          </div>

          {contestsLoading ? (
            <div className="space-y-3">
              {[1, 2].map((i) => <div key={i} className="h-20 skeleton rounded-2xl" />)}
            </div>
          ) : contests.length > 0 ? (
            <div className="space-y-3">
              {contests.map((c: any) => (
                <ContestCard key={c.id} contest={c} />
              ))}
            </div>
          ) : (
            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-6 text-center">
              <Trophy size={28} className="mx-auto mb-2 text-slate-300" />
              <p className="text-sm font-medium text-slate-500 mb-0.5">No contests right now</p>
              <p className="text-xs text-slate-400">Check back soon for new opportunities to win!</p>
            </div>
          )}
        </section>
          </div>{/* /left column */}

          {/* Right column — Surveys + Mystery + Complaints */}
          <div className="space-y-6">
        {/* Surveys */}
        <section>
          <div className="flex items-center justify-between mb-3">
            <h2 className="font-heading font-bold text-base text-slate-800 flex items-center gap-2">
              <ClipboardList size={16} className="text-brand-500" /> Surveys
            </h2>
            <span className="text-xs text-slate-400">{surveys.length > 0 ? `${surveys.length} available` : ''}</span>
          </div>

          {surveysLoading ? (
            <div className="space-y-3">
              {[1, 2].map((i) => <div key={i} className="h-20 skeleton rounded-2xl" />)}
            </div>
          ) : surveys.length > 0 ? (
            <div className="space-y-3">
              {surveys.map((s) => (
                <Link key={s.id} to={`/surveys/${s.id}`} className="card card-hover p-4 flex items-start gap-3 block">
                  <div className="w-10 h-10 rounded-xl gradient-brand flex items-center justify-center flex-shrink-0">
                    <ClipboardList size={18} className="text-white" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <h3 className="font-semibold text-slate-800 text-sm line-clamp-1">{s.title}</h3>
                    {s.description && (
                      <p className="text-xs text-slate-500 mt-0.5 line-clamp-2">{s.description}</p>
                    )}
                    <span className="text-xs text-slate-400 mt-1 inline-block">{s.question_count} question{s.question_count !== 1 ? 's' : ''}</span>
                  </div>
                  <div className="flex-shrink-0 self-center">
                    {s.already_submitted ? (
                      <span className="text-xs font-semibold text-green-600 bg-green-50 px-2.5 py-1 rounded-full">Done</span>
                    ) : (
                      <span className="text-xs font-semibold text-brand-600 bg-brand-50 px-2.5 py-1 rounded-full">Take</span>
                    )}
                  </div>
                </Link>
              ))}
            </div>
          ) : (
            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-6 text-center">
              <ClipboardList size={28} className="mx-auto mb-2 text-slate-300" />
              <p className="text-sm font-medium text-slate-500 mb-0.5">No surveys right now</p>
              <p className="text-xs text-slate-400">Check back soon to share your feedback and earn rewards!</p>
            </div>
          )}
        </section>

        {/* Mystery Shopping */}
        <section>
          <div className="flex items-center justify-between mb-3">
            <h2 className="font-heading font-bold text-base text-slate-800 flex items-center gap-2">
              <Search size={16} className="text-purple-500" /> Mystery Shopping
            </h2>
            <span className="text-[10px] font-semibold bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full">Coming Soon</span>
          </div>
          <ComingSoonSection
            icon={Search}
            color="bg-purple-500"
            title="Become a Mystery Shopper"
            description="Visit assigned stores, evaluate service quality, submit your report — and earn cash rewards or special coupons."
            badge="Exclusive"
          />
        </section>

        {/* Grievances quick link */}
        <section>
          <h2 className="font-heading font-bold text-base text-slate-800 flex items-center gap-2 mb-3">
            <MessageSquare size={16} className="text-rose-500" /> Complaints &amp; Feedback
          </h2>
          <Link to="/grievances" className="card card-hover p-4 flex items-center gap-3 block">
            <div className="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center flex-shrink-0">
              <MessageSquare size={18} className="text-rose-500" />
            </div>
            <div className="flex-1">
              <p className="font-semibold text-slate-800 text-sm">My Complaints</p>
              <p className="text-xs text-slate-500">View status and merchant replies</p>
            </div>
            <ChevronRight size={16} className="text-slate-300" />
          </Link>
          <Link to="/grievances/new" className="card card-hover p-4 flex items-center gap-3 block mt-2">
            <div className="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center flex-shrink-0">
              <MessageSquare size={18} className="text-rose-400" />
            </div>
            <div className="flex-1">
              <p className="font-semibold text-slate-800 text-sm">Submit a Complaint</p>
              <p className="text-xs text-slate-500">Report an issue with a merchant</p>
            </div>
            <ChevronRight size={16} className="text-slate-300" />
          </Link>
        </section>

          </div>{/* /right column */}
        </div>{/* /grid */}

      </div>
    </div>
  )
}

