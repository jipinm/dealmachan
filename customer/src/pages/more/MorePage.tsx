import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  Briefcase,
  CheckCircle,
  CheckCircle2,
  Clock,
  IndianRupee,
  AlertTriangle,
  Gift,
  Heart,
  Sparkles,
  Star,
  Trophy,
  Users,
} from 'lucide-react'
import { dealmakerApi, type DealmakerStatus } from '@/api/endpoints/dealmaker'
import { useAuthStore } from '@/store/authStore'

// ── Quick link tile data ───────────────────────────────────────────────────
const QUICK_LINKS = [
  { to: '/referrals',      icon: Users,         label: 'Referrals',       color: 'bg-blue-50 text-blue-600 border-blue-100' },
  { to: '/wallet/store',   icon: Gift,          label: 'Store Coupons',   color: 'bg-green-50 text-green-600 border-green-100' },
  { to: '/important-days', icon: Heart,         label: 'Important Days',  color: 'bg-rose-50 text-rose-600 border-rose-100' },
  { to: '/grievances',     icon: AlertTriangle, label: 'Grievances',      color: 'bg-amber-50 text-amber-600 border-amber-100' },
  { to: '/wishlist',       icon: Star,          label: 'Wishlist',        color: 'bg-purple-50 text-purple-600 border-purple-100' },
  { to: '/loyalty-cards',  icon: Trophy,        label: 'Loyalty Cards',   color: 'bg-orange-50 text-orange-600 border-orange-100' },
]

// ── Apply form ─────────────────────────────────────────────────────────────
function ApplyModal({ onClose, onSuccess }: { onClose: () => void; onSuccess: () => void }) {
  const [motivation, setMotivation] = useState('')
  const [city, setCity] = useState('')
  const [experience, setExperience] = useState('')
  const qc = useQueryClient()

  useEffect(() => {
    const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose() }
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [onClose])

  const apply = useMutation({
    mutationFn: () => dealmakerApi.apply({ motivation, city, experience }),
    onSuccess: (res) => {
      toast.success(res.message ?? 'Application submitted!')
      qc.invalidateQueries({ queryKey: ['dealmaker-status'] })
      onSuccess()
      onClose()
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? 'Failed to submit application.')
    },
  })

  return (
    <div role="dialog" aria-modal="true" aria-label="Apply to become a Deal Maker" className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-6 space-y-4">
        <div className="flex items-center gap-3">
          <Sparkles className="w-7 h-7 text-amber-500" />
          <h2 className="text-xl font-bold text-gray-900">Become a Deal Maker</h2>
        </div>
        <p className="text-sm text-gray-500">
          Deal Makers help customers find the best local offers and earn rewards for every task completed.
        </p>

        <div className="space-y-3">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Why do you want to be a Deal Maker? <span className="text-red-500">*</span>
            </label>
            <textarea
              className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm resize-none focus:ring-2 focus:ring-amber-400 focus:outline-none"
              rows={3}
              placeholder="Share your motivation…"
              value={motivation}
              onChange={e => setMotivation(e.target.value)}
            />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
              <input
                className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                placeholder="e.g. Bengaluru"
                value={city}
                onChange={e => setCity(e.target.value)}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Experience</label>
              <input
                className="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                placeholder="e.g. Sales, Marketing"
                value={experience}
                onChange={e => setExperience(e.target.value)}
              />
            </div>
          </div>
        </div>

        <div className="flex gap-3 pt-1">
          <button
            onClick={onClose}
            className="flex-1 border border-gray-300 text-gray-700 rounded-xl py-2.5 text-sm font-medium hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            onClick={() => apply.mutate()}
            disabled={!motivation.trim() || apply.isPending}
            className="flex-1 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white rounded-xl py-2.5 text-sm font-semibold"
          >
            {apply.isPending ? 'Submitting…' : 'Submit Application'}
          </button>
        </div>
      </div>
    </div>
  )
}

// ── DealMaker status card ─────────────────────────────────────────────────
function DealmakerCard({ status }: { status: DealmakerStatus }) {
  const [showApply, setShowApply] = useState(false)

  if (status.is_dealmaker) {
    const ts = status.task_summary
    return (
      <div className="bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl p-5 text-white shadow-lg">
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-2">
            <Sparkles className="w-6 h-6" />
            <span className="font-bold text-lg">Deal Maker</span>
          </div>
          <CheckCircle2 className="w-6 h-6 text-white/80" />
        </div>
        {ts && (
          <div className="grid grid-cols-3 gap-2 mb-4">
            <Stat label="Tasks" value={String(ts.total)} />
            <Stat label="Active" value={String((ts.assigned ?? 0) + (ts.in_progress ?? 0))} />
            <Stat label="Done" value={String(ts.completed ?? 0)} />
          </div>
        )}
        <div className="flex items-center justify-between bg-white/20 rounded-xl px-4 py-3">
          <span className="text-sm">Total Earned</span>
          <span className="font-bold text-lg">₹{ts ? ts.total_earned.toFixed(2) : '0.00'}</span>
        </div>
        {ts && ts.pending_earnings > 0 && (
          <p className="text-xs text-white/70 mt-2 text-center">
            ₹{ts.pending_earnings.toFixed(2)} pending payout
          </p>
        )}
        <Link
          to="/dealmaker/tasks"
          className="block mt-3 text-center text-sm font-semibold bg-white text-amber-600 rounded-xl py-2 hover:bg-white/90"
        >
          View My Tasks
        </Link>
      </div>
    )
  }

  const app = status.application

  if (app && app.status === 'pending') {
    return (
      <div className="bg-amber-50 border border-amber-200 rounded-2xl p-5 space-y-2">
        <div className="flex items-center gap-2 text-amber-700">
          <Clock className="w-6 h-6" />
          <span className="font-semibold text-base">Application Under Review</span>
        </div>
        <p className="text-sm text-amber-600">
          Your Deal Maker application was submitted on{' '}
          {new Date(app.applied_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}.
          Our team typically reviews within 2 business days.
        </p>
      </div>
    )
  }

  if (app && app.status === 'rejected') {
    return (
      <div className="bg-red-50 border border-red-200 rounded-2xl p-5 space-y-3">
        <div className="flex items-center gap-2 text-red-700">
          <AlertTriangle className="w-6 h-6" />
          <span className="font-semibold text-base">Application Not Approved</span>
        </div>
        {app.rejection_reason && (
          <p className="text-sm text-red-600">{app.rejection_reason}</p>
        )}
        <button
          onClick={() => setShowApply(true)}
          className="w-full bg-red-600 hover:bg-red-700 text-white rounded-xl py-2.5 text-sm font-semibold"
        >
          Reapply
        </button>
        {showApply && (
          <ApplyModal onClose={() => setShowApply(false)} onSuccess={() => setShowApply(false)} />
        )}
      </div>
    )
  }

  // Not applied yet
  return (
    <>
      <div className="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-5 space-y-3">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 bg-amber-100 rounded-2xl flex items-center justify-center">
            <Briefcase className="w-6 h-6 text-amber-600" />
          </div>
          <div>
            <h3 className="font-bold text-gray-900">Become a Deal Maker</h3>
            <p className="text-xs text-gray-500">Earn rewards by helping your community</p>
          </div>
        </div>
        <ul className="space-y-1.5 text-sm text-gray-600">
          {['Complete tasks assigned by admins', 'Earn cash rewards for verified tasks', 'Get exclusive Deal Maker badge'].map(t => (
            <li key={t} className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4 text-green-500 flex-shrink-0" />
              {t}
            </li>
          ))}
        </ul>
        <button
          onClick={() => setShowApply(true)}
          className="w-full bg-amber-500 hover:bg-amber-600 text-white rounded-xl py-2.5 text-sm font-semibold"
        >
          Apply Now — It's Free
        </button>
      </div>
      {showApply && (
        <ApplyModal onClose={() => setShowApply(false)} onSuccess={() => setShowApply(false)} />
      )}
    </>
  )
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <div className="text-center">
      <div className="text-2xl font-bold">{value}</div>
      <div className="text-xs text-white/70">{label}</div>
    </div>
  )
}

// ── Earnings preview (approved dealmakers) ────────────────────────────────
function EarningsPreview({ earned, pending }: { earned: number; pending: number }) {
  if (earned === 0 && pending === 0) return null
  return (
    <div className="bg-white border border-gray-200 rounded-2xl p-4 flex gap-4">
      <div className="flex-1 flex items-center gap-3">
        <div className="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center">
          <IndianRupee className="w-5 h-5 text-green-600" />
        </div>
        <div>
          <p className="text-xs text-gray-400">Total Earned</p>
          <p className="font-bold text-gray-900">₹{earned.toFixed(2)}</p>
        </div>
      </div>
      {pending > 0 && (
        <div className="flex-1 flex items-center gap-3">
          <div className="w-10 h-10 bg-amber-50 rounded-full flex items-center justify-center">
          <Clock className="w-5 h-5 text-amber-600" />
          </div>
          <div>
            <p className="text-xs text-gray-400">Pending</p>
            <p className="font-bold text-gray-900">₹{pending.toFixed(2)}</p>
          </div>
        </div>
      )}
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────
export default function MorePage() {
  const { customer } = useAuthStore()

  const statusQuery = useQuery<DealmakerStatus>({
    queryKey: ['dealmaker-status'],
    queryFn: dealmakerApi.getStatus,
    staleTime: 2 * 60 * 1000,
  })

  const ts = statusQuery.data?.task_summary

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 px-4 py-4">
        <h1 className="text-xl font-bold text-gray-900">More</h1>
        {customer?.name && (
          <p className="text-sm text-gray-500 mt-0.5">Hi, {customer.name.split(' ')[0]} 👋</p>
        )}
      </div>

      <div className="max-w-[1200px] mx-auto px-4 py-5 space-y-5">

        {/* DealMaker status card */}
        {statusQuery.isLoading ? (
          <div className="bg-gray-100 rounded-2xl h-40 animate-pulse" />
        ) : statusQuery.data ? (
          <DealmakerCard status={statusQuery.data} />
        ) : null}

        {/* Earnings preview (approved only) */}
        {statusQuery.data?.is_dealmaker && ts && (
          <EarningsPreview earned={ts.total_earned} pending={ts.pending_earnings} />
        )}

        {/* Quick links grid */}
        <div>
          <h2 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Quick Access</h2>
          <div className="grid grid-cols-3 gap-3">
            {QUICK_LINKS.map(({ to, icon: Icon, label, color }) => (
              <Link
                key={to}
                to={to}
                className={`flex flex-col items-center gap-2 border rounded-2xl py-4 px-2 ${color} hover:opacity-80 transition-opacity`}
              >
                <Icon className="w-6 h-6" />
                <span className="text-xs font-medium text-center leading-tight">{label}</span>
              </Link>
            ))}
          </div>
        </div>

        {/* Static page links */}
        <div className="bg-white border border-gray-200 rounded-2xl divide-y divide-gray-100">
          {[
            { to: '/page/faq',                  label: 'FAQ' },
            { to: '/page/privacy-policy',        label: 'Privacy Policy' },
            { to: '/page/terms-and-conditions',  label: 'Terms & Conditions' },
            { to: '/contact',                    label: 'Contact Us' },
            { to: '/business-signup',            label: 'List Your Business' },
          ].map(({ to, label }) => (
            <Link
              key={to}
              to={to}
              className="flex items-center justify-between px-4 py-3.5 text-sm text-gray-700 hover:bg-gray-50"
            >
              <span>{label}</span>
              <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </Link>
          ))}
        </div>

        {/* App version */}
        <p className="text-center text-xs text-gray-300 pb-4">DealMachan v1.0</p>
      </div>
    </div>
  )
}
