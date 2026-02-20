import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ChevronLeft, TrendingUp, Users, Award, RefreshCw } from 'lucide-react'
import {
  AreaChart, Area, BarChart, Bar,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer
} from 'recharts'
import { analyticsApi } from '@/api/endpoints/analytics'

type Period = 7 | 30 | 90

const PERIODS: { value: Period; label: string }[] = [
  { value: 7,  label: '7d' },
  { value: 30, label: '30d' },
  { value: 90, label: '90d' },
]

function SectionHeader({ icon, title }: { icon: React.ReactNode; title: string }) {
  return (
    <div className="flex items-center gap-2 mb-3">
      <span className="text-brand-600">{icon}</span>
      <h2 className="font-semibold text-gray-800 text-sm">{title}</h2>
    </div>
  )
}

export default function AnalyticsPage() {
  const navigate   = useNavigate()
  const [period, setPeriod] = useState<Period>(30)

  const { data: redemptionData, isLoading: rLoading, refetch: rRefetch } =
    useQuery({
      queryKey: ['analytics-redemptions', period],
      queryFn:  () => analyticsApi.redemptions({ period }).then(r => r.data.data ?? null),
    })

  const { data: customerData, isLoading: cLoading } =
    useQuery({
      queryKey: ['analytics-customers', period],
      queryFn:  () => analyticsApi.customers().then(r => r.data.data ?? null),
    })

  const { data: topCoupons, isLoading: tLoading } =
    useQuery({
      queryKey: ['analytics-top-coupons'],
      queryFn:  () => analyticsApi.topCoupons().then(r => r.data.data ?? null),
    })

  const isLoading = rLoading || cLoading || tLoading

  // Customer new vs returning bar data
  const customerBarData = customerData
    ? [
        { name: 'New',       count: customerData.new_customers },
        { name: 'Returning', count: customerData.returning_customers },
      ]
    : []

  return (
    <div className="min-h-screen bg-gray-50">
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
            <h1 className="text-white font-bold text-xl">Analytics</h1>
            <p className="text-white/70 text-xs mt-0.5">Performance insights</p>
          </div>
          <button
            onClick={() => rRefetch()}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30"
          >
            <RefreshCw size={16} className="text-white" />
          </button>
        </div>

        {/* Period selector */}
        <div className="flex gap-2 mt-4">
          {PERIODS.map(p => (
            <button
              key={p.value}
              onClick={() => setPeriod(p.value)}
              className={`px-4 py-1.5 rounded-full text-sm font-medium transition-colors ${
                period === p.value
                  ? 'bg-white text-brand-700'
                  : 'bg-white/20 text-white hover:bg-white/30'
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>
      </div>

      <div className="px-4 py-5 space-y-5">
        {/* Redemptions chart */}
        <div className="bg-white rounded-2xl shadow-sm p-4">
          <SectionHeader icon={<TrendingUp size={16} />} title="Redemptions" />
          {rLoading ? (
            <div className="h-40 flex items-center justify-center text-gray-400 text-sm">Loading…</div>
          ) : redemptionData && (redemptionData.daily?.length ?? 0) > 0 ? (
            <>
              <div className="flex gap-4 mb-3">
                <div>
                  <p className="text-2xl font-bold text-gray-900">{redemptionData.total_redemptions}</p>
                  <p className="text-xs text-gray-500">Total redemptions</p>
                </div>
                <div>
                  <p className="text-2xl font-bold text-brand-600">
                    ₹{parseFloat(redemptionData.total_discount).toLocaleString('en-IN')}
                  </p>
                  <p className="text-xs text-gray-500">Discount given</p>
                </div>
              </div>
              <ResponsiveContainer width="100%" height={160}>
                <AreaChart data={redemptionData.daily} margin={{ top: 4, right: 0, left: -32, bottom: 0 }}>
                  <defs>
                    <linearGradient id="redemGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%"  stopColor="#7C3AED" stopOpacity={0.3} />
                      <stop offset="95%" stopColor="#7C3AED" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                  <XAxis dataKey="date" tick={{ fontSize: 10 }} tickFormatter={v => v.slice(5)} />
                  <YAxis tick={{ fontSize: 10 }} allowDecimals={false} />
                  <Tooltip
                    formatter={(v: number) => [v, 'Redemptions']}
                    labelFormatter={l => `Date: ${l}`}
                  />
                  <Area type="monotone" dataKey="count" stroke="#7C3AED" fill="url(#redemGrad)" strokeWidth={2} />
                </AreaChart>
              </ResponsiveContainer>
            </>
          ) : (
            <p className="text-center text-gray-400 text-sm py-10">No redemptions in this period</p>
          )}
        </div>

        {/* Customers */}
        <div className="bg-white rounded-2xl shadow-sm p-4">
          <SectionHeader icon={<Users size={16} />} title="Customers" />
          {cLoading ? (
            <div className="h-32 flex items-center justify-center text-gray-400 text-sm">Loading…</div>
          ) : customerData ? (
            <div className="space-y-4">
              {/* New vs Returning bar */}
              <ResponsiveContainer width="100%" height={120}>
                <BarChart data={customerBarData} margin={{ top: 4, right: 0, left: -32, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                  <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 10 }} allowDecimals={false} />
                  <Tooltip formatter={(v: number) => [v, 'Customers']} />
                  <Bar dataKey="count" fill="#7C3AED" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>

              {/* By city */}
              {customerData.by_city.length > 0 && (
                <div>
                  <p className="text-xs font-medium text-gray-600 mb-2">By City</p>
                  <div className="space-y-1.5">
                    {customerData.by_city.map((c: { city: string; count: number }) => (
                      <div key={c.city} className="flex items-center gap-2">
                        <span className="text-xs text-gray-600 w-24 truncate">{c.city}</span>
                        <div className="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                          <div
                            className="bg-brand-500 h-2 rounded-full"
                            style={{
                              width: `${Math.round(
                                (c.count / (customerData.by_city[0]?.count || 1)) * 100
                              )}%`,
                            }}
                          />
                        </div>
                        <span className="text-xs font-semibold text-gray-700 w-6 text-right">{c.count}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          ) : (
            <p className="text-center text-gray-400 text-sm py-10">No customer data</p>
          )}
        </div>

        {/* Top coupons */}
        <div className="bg-white rounded-2xl shadow-sm p-4">
          <SectionHeader icon={<Award size={16} />} title="Top Coupons" />
          {tLoading ? (
            <div className="h-32 flex items-center justify-center text-gray-400 text-sm">Loading…</div>
          ) : topCoupons && topCoupons.length > 0 ? (
            <div className="space-y-2">
              {topCoupons.map((c: { id: number; title: string; coupon_code: string; redemption_count: number }, i: number) => (
                <div key={c.id} className="flex items-center gap-3">
                  <span
                    className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${
                      i === 0 ? 'bg-amber-100 text-amber-700'
                      : i === 1 ? 'bg-gray-100 text-gray-600'
                      : i === 2 ? 'bg-orange-100 text-orange-600'
                      : 'bg-gray-50 text-gray-400'
                    }`}
                  >
                    {i + 1}
                  </span>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-800 truncate">{c.title}</p>
                    <p className="text-xs text-gray-400 font-mono">{c.coupon_code}</p>
                  </div>
                  <span className="text-sm font-bold text-brand-600">{c.redemption_count}</span>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-center text-gray-400 text-sm py-8">No coupon data yet</p>
          )}
        </div>

        <div className="h-4" />
      </div>
    </div>
  )
}
