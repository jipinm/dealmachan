import type React from 'react'
import { Bell, TrendingUp, Users, Star, AlertCircle, ScanLine, Plus, FileText, MessageSquare } from 'lucide-react'
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  CartesianGrid,
} from 'recharts'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useAuthStore } from '@/store/authStore'
import { apiClient } from '@/api/client'
import { merchantApi } from '@/api/endpoints/merchant'
import { SkeletonBlock, SkeletonCard } from '@/components/ui/PageLoader'

// ── Types ────────────────────────────────────────────────────────────────────
interface DashboardData {
  today_redemptions: number
  total_customers: number
  avg_rating: number | null
  pending_grievances: number
  weekly_redemptions: Array<{ date: string; count: number }>
}

// ── KPI card ─────────────────────────────────────────────────────────────────
function KpiCard({
  icon: Icon,
  label,
  value,
  accent,
}: {
  icon: React.ElementType
  label: string
  value: string | number
  accent: string
}) {
  return (
    <div className="bg-white rounded-2xl p-4 shadow-sm flex items-center gap-3">
      <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${accent}`}>
        <Icon size={20} className="text-white" />
      </div>
      <div className="min-w-0">
        <p className="text-xs text-gray-500 leading-none mb-0.5">{label}</p>
        <p className="text-xl font-bold text-gray-900 leading-tight truncate">{value}</p>
      </div>
    </div>
  )
}

// ── Quick action button ───────────────────────────────────────────────────────
function QuickAction({
  icon: Icon,
  label,
  onClick,
}: {
  icon: React.ElementType
  label: string
  onClick: () => void
}) {
  return (
    <button
      onClick={onClick}
      className="flex flex-col items-center gap-1.5 group"
    >
      <div className="w-14 h-14 bg-brand-50 group-active:bg-brand-100 rounded-2xl flex items-center justify-center transition-colors">
        <Icon size={22} className="text-brand-600" />
      </div>
      <span className="text-[10px] font-medium text-gray-600 leading-tight text-center max-w-[56px]">
        {label}
      </span>
    </button>
  )
}

// ── Custom chart tooltip ──────────────────────────────────────────────────────
function ChartTooltip({ active, payload, label }: { active?: boolean; payload?: { value: number }[]; label?: string }) {
  if (!active || !payload?.length) return null
  return (
    <div className="bg-white px-3 py-2 rounded-xl shadow-md border border-gray-100 text-xs">
      <p className="text-gray-500 mb-0.5">{label}</p>
      <p className="font-bold text-brand-700">{payload[0].value} redemptions</p>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────────
export default function HomePage() {
  const navigate = useNavigate()
  const merchant = useAuthStore((s) => s.merchant)

  const { data, isLoading } = useQuery<DashboardData>({
    queryKey: ['dashboard'],
    queryFn: async () => {
      const res = await apiClient.get('/merchants/analytics/dashboard')
      return res.data.data as DashboardData
    },
    staleTime: 60 * 1000, // 1 min
  })

  const { data: notifMeta } = useQuery({
    queryKey: ['notifications-unread-count'],
    queryFn: () => merchantApi.getNotifications({ limit: 1 }).then(r => r.data.meta ?? null),
    staleTime: 30 * 1000, // 30 s
  })

  const unreadCount = notifMeta?.unread_count ?? 0

  const greeting = () => {
    const h = new Date().getHours()
    if (h < 12) return 'Good morning'
    if (h < 17) return 'Good afternoon'
    return 'Good evening'
  }

  // Format chart dates to short label e.g. "Mon", "Tue"
  const chartData = data?.weekly_redemptions?.map((d) => ({
    day: new Date(d.date).toLocaleDateString('en-IN', { weekday: 'short' }),
    Redemptions: d.count,
  })) ?? []

  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-20">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-white/70 text-sm">{greeting()},</p>
            <h1 className="text-white font-bold text-xl leading-snug">
              {merchant?.business_name ?? 'Merchant'}
            </h1>
          </div>
          <button
            onClick={() => navigate('/notifications')}
            className="relative w-10 h-10 bg-white/20 rounded-full flex items-center justify-center"
          >
            <Bell size={20} className="text-white" />
            {unreadCount > 0 && (
              <span className="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-400 rounded-full border-2 border-white/20" />
            )}
          </button>
        </div>
      </div>

      {/* Content — floated up to overlap header */}
      <div className="px-4 -mt-14 space-y-4 pb-6">
        {/* KPI grid */}
        {isLoading ? (
          <div className="grid grid-cols-2 gap-3">
            {[0, 1, 2, 3].map((i) => (
              <SkeletonCard key={i} />
            ))}
          </div>
        ) : (
          <div className="grid grid-cols-2 gap-3">
            <KpiCard
              icon={TrendingUp}
              label="Today's Redemptions"
              value={data?.today_redemptions ?? 0}
              accent="bg-brand-600"
            />
            <KpiCard
              icon={Users}
              label="Total Customers"
              value={data?.total_customers ?? 0}
              accent="bg-indigo-500"
            />
            <KpiCard
              icon={Star}
              label="Avg Rating"
              value={data?.avg_rating != null ? data.avg_rating.toFixed(1) : '—'}
              accent="bg-amber-500"
            />
            <KpiCard
              icon={AlertCircle}
              label="Pending Grievances"
              value={data?.pending_grievances ?? 0}
              accent="bg-rose-500"
            />
          </div>
        )}

        {/* Quick actions */}
        <div className="bg-white rounded-2xl p-4 shadow-sm">
          <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">
            Quick Actions
          </p>
          <div className="flex justify-around">
            <QuickAction icon={ScanLine} label="Scan Coupon" onClick={() => navigate('/scan')} />
            <QuickAction icon={Plus} label="Add Coupon" onClick={() => navigate('/coupons/new')} />
            <QuickAction icon={FileText} label="View Sales" onClick={() => navigate('/analytics/sales')} />
            <QuickAction icon={MessageSquare} label="Message" onClick={() => navigate('/messages/new')} />
          </div>
        </div>

        {/* Weekly redemption chart */}
        <div className="bg-white rounded-2xl p-4 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <p className="text-sm font-bold text-gray-800">Redemptions — Last 7 Days</p>
            <button
              onClick={() => navigate('/analytics')}
              className="text-xs text-brand-600 font-medium hover:underline"
            >
              Full report
            </button>
          </div>

          {isLoading ? (
            <SkeletonBlock className="h-32 w-full" />
          ) : chartData.length === 0 ? (
            <div className="h-32 flex items-center justify-center text-sm text-gray-400">
              No data yet
            </div>
          ) : (
            <ResponsiveContainer width="100%" height={120}>
              <AreaChart data={chartData} margin={{ top: 2, right: 4, left: -28, bottom: 0 }}>
                <defs>
                  <linearGradient id="brandGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#667eea" stopOpacity={0.25} />
                    <stop offset="95%" stopColor="#667eea" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                <XAxis dataKey="day" tick={{ fontSize: 10, fill: '#9ca3af' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 10, fill: '#9ca3af' }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip content={<ChartTooltip />} />
                <Area
                  type="monotone"
                  dataKey="Redemptions"
                  stroke="#667eea"
                  strokeWidth={2}
                  fill="url(#brandGradient)"
                  dot={{ fill: '#667eea', r: 3, strokeWidth: 0 }}
                  activeDot={{ r: 5 }}
                />
              </AreaChart>
            </ResponsiveContainer>
          )}
        </div>
      </div>
    </div>
  )
}
