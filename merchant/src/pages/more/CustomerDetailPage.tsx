import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  ChevronLeft, Phone, Mail, Calendar, BarChart2,
  TrendingUp, DollarSign, Clock, Percent, ArrowUpRight,
} from 'lucide-react'
import {
  merchantCustomerApi,
  type CustomerAnalytics,
} from '@/api/endpoints/customers'

function StatCard({
  label,
  value,
  icon: Icon,
  color,
}: {
  label: string
  value: string
  icon: React.ElementType
  color: string
}) {
  return (
    <div className="bg-white rounded-2xl shadow-sm p-4 flex items-center gap-3">
      <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${color}`}>
        <Icon size={18} className="text-white" />
      </div>
      <div className="min-w-0">
        <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">{label}</p>
        <p className="text-lg font-extrabold text-gray-900 truncate">{value}</p>
      </div>
    </div>
  )
}

export default function CustomerDetailPage() {
  const { id }   = useParams<{ id: string }>()
  const navigate = useNavigate()

  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo]     = useState('')

  const { data: customer, isLoading: loadingCustomer } = useQuery({
    queryKey: ['merchant-customer', id],
    queryFn:  () => merchantCustomerApi.get(Number(id)).then(r => r.data.data),
    enabled:  !!id,
  })

  const { data: analytics, isLoading: loadingAnalytics } = useQuery({
    queryKey: ['customer-analytics', id, dateFrom, dateTo],
    queryFn:  () => merchantCustomerApi.getAnalytics(Number(id), {
      from: dateFrom || undefined,
      to:   dateTo || undefined,
    }).then(r => r.data.data),
    enabled: !!id,
  })

  const isLoading = loadingCustomer || loadingAnalytics

  const formatCurrency = (v: number) =>
    `₹${v.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`

  const formatDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'

  return (
    <div className="min-h-full bg-gray-50 pb-10">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-lg">{customer?.name ?? 'Customer'}</h1>
            <p className="text-white/70 text-xs mt-0.5">
              {customer?.customer_type ? customer.customer_type.charAt(0).toUpperCase() + customer.customer_type.slice(1) : ''} Customer
            </p>
          </div>
        </div>
      </div>

      <div className="px-4 -mt-2 space-y-4">
        {/* Customer info card */}
        {customer && (
          <div className="bg-white rounded-2xl shadow-sm p-4">
            <div className="flex items-center gap-3">
              <div className="w-14 h-14 bg-brand-100 rounded-full flex items-center justify-center shrink-0">
                <span className="text-brand-700 text-xl font-bold">
                  {customer.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()}
                </span>
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-bold text-gray-900 text-base truncate">{customer.name}</p>
                <div className="flex items-center gap-3 mt-1">
                  {customer.phone && (
                    <span className="flex items-center gap-1 text-xs text-gray-500">
                      <Phone size={10} />{customer.phone}
                    </span>
                  )}
                  {customer.email && (
                    <span className="flex items-center gap-1 text-xs text-gray-500">
                      <Mail size={10} />{customer.email}
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-1 mt-1 text-xs text-gray-400">
                  <Calendar size={10} />
                  Joined {formatDate(customer.created_at)}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Date range filter */}
        <div className="bg-white rounded-2xl shadow-sm p-4 space-y-3">
          <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Analytics Period</p>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs text-gray-500 mb-1">From</label>
              <input
                type="date"
                value={dateFrom}
                onChange={e => setDateFrom(e.target.value)}
                className="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
              />
            </div>
            <div>
              <label className="block text-xs text-gray-500 mb-1">To</label>
              <input
                type="date"
                value={dateTo}
                onChange={e => setDateTo(e.target.value)}
                className="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
              />
            </div>
          </div>
          {(dateFrom || dateTo) && (
            <button
              onClick={() => { setDateFrom(''); setDateTo('') }}
              className="text-xs text-brand-600 font-medium"
            >
              Clear filters
            </button>
          )}
        </div>

        {/* Analytics cards */}
        {isLoading ? (
          <div className="grid grid-cols-2 gap-3">
            {[1,2,3,4,5,6].map(i => (
              <div key={i} className="bg-white rounded-2xl h-20 animate-pulse" />
            ))}
          </div>
        ) : analytics ? (
          <div className="grid grid-cols-2 gap-3">
            <StatCard
              label="Coupon Assignments"
              value={String(analytics.total_coupon_assignments)}
              icon={BarChart2}
              color="bg-brand-600"
            />
            <StatCard
              label="Total Transactions"
              value={String(analytics.total_transactions)}
              icon={TrendingUp}
              color="bg-emerald-500"
            />
            <StatCard
              label="Total Value"
              value={formatCurrency(analytics.total_transaction_value)}
              icon={DollarSign}
              color="bg-purple-500"
            />
            <StatCard
              label="Highest Transaction"
              value={formatCurrency(analytics.highest_transaction)}
              icon={ArrowUpRight}
              color="bg-amber-500"
            />
            <StatCard
              label="Avg Frequency"
              value={analytics.average_frequency_days !== null
                ? `${analytics.average_frequency_days.toFixed(0)} days`
                : '—'}
              icon={Clock}
              color="bg-indigo-500"
            />
            <StatCard
              label="Response Rate"
              value={`${analytics.response_rate.toFixed(1)}%`}
              icon={Percent}
              color="bg-rose-500"
            />
            <div className="col-span-2 bg-white rounded-2xl shadow-sm p-4">
              <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Last Visit</p>
              <p className="text-lg font-extrabold text-gray-900 mt-1">{formatDate(analytics.last_visit_date)}</p>
            </div>
          </div>
        ) : (
          <div className="bg-white rounded-2xl shadow-sm p-6 text-center">
            <BarChart2 size={32} className="text-gray-300 mx-auto mb-2" />
            <p className="text-gray-500 font-medium text-sm">No analytics available</p>
          </div>
        )}
      </div>
    </div>
  )
}
