import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query'
import { ChevronLeft, Plus, X, TrendingUp, ShoppingCart, BarChart2, Download } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import toast from 'react-hot-toast'
import { salesApi, type SaleRecord, type CreateSalePayload } from '@/api/endpoints/analytics'
import { storeApi } from '@/api/endpoints/stores'

// ── Form schema ──────────────────────────────────────────────────────────────

const saleSchema = z.object({
  store_id:           z.string().min(1, 'Select a store'),
  transaction_amount: z.string().min(1, 'Amount required').refine(v => !isNaN(parseFloat(v)) && parseFloat(v) > 0, 'Must be > 0'),
  payment_method:     z.enum(['cash', 'card', 'upi', 'wallet', 'other']).optional(),
  coupon_code:        z.string().optional(),
  discount_amount:    z.string().optional(),
})
type SaleForm = z.infer<typeof saleSchema>

// ── Payment method meta ──────────────────────────────────────────────────────

const PM_COLORS: Record<string, string> = {
  cash:   'bg-green-100  text-green-700',
  card:   'bg-blue-100   text-blue-700',
  upi:    'bg-purple-100 text-purple-700',
  wallet: 'bg-amber-100  text-amber-700',
  other:  'bg-gray-100   text-gray-600',
}
const PM_LABELS: Record<string, string> = {
  cash: 'Cash', card: 'Card', upi: 'UPI', wallet: 'Wallet', other: 'Other',
}

// ── Single row ───────────────────────────────────────────────────────────────

function SaleRow({ sale }: { sale: SaleRecord }) {
  const date = new Date(sale.transaction_date)
  const dateStr = date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' })
  const timeStr = date.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })

  return (
    <div className="flex items-start gap-3 py-3 border-b border-gray-50 last:border-0">
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <p className="text-sm font-semibold text-gray-800">
            ₹{parseFloat(sale.transaction_amount).toLocaleString('en-IN')}
          </p>
          {sale.payment_method && (
            <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-full ${PM_COLORS[sale.payment_method] ?? 'bg-gray-100 text-gray-500'}`}>
              {PM_LABELS[sale.payment_method] ?? sale.payment_method}
            </span>
          )}
          {parseFloat(sale.discount_amount) > 0 && (
            <span className="text-[10px] text-red-600 font-medium">
              -{parseFloat(sale.discount_amount).toFixed(0)} off
            </span>
          )}
        </div>
        <p className="text-xs text-gray-500 mt-0.5 truncate">
          {sale.store_name ?? 'Store'}{sale.coupon_code ? ` · ${sale.coupon_code}` : ''}
        </p>
      </div>
      <div className="text-right shrink-0">
        <p className="text-xs font-medium text-gray-700">{dateStr}</p>
        <p className="text-[10px] text-gray-400">{timeStr}</p>
      </div>
    </div>
  )
}

// ── Record Sale Modal ────────────────────────────────────────────────────────

function RecordSaleModal({ onClose }: { onClose: () => void }) {
  const queryClient = useQueryClient()

  const { data: stores = [] } = useQuery({
    queryKey: ['stores'],
    queryFn:  () => storeApi.list().then(r => r.data.data),
  })

  const { register, handleSubmit, formState: { errors } } = useForm<SaleForm>({
    resolver: zodResolver(saleSchema),
  })

  const mutation = useMutation({
    mutationFn: (data: CreateSalePayload) => salesApi.record(data),
    onSuccess: () => {
      toast.success('Sale recorded!')
      queryClient.invalidateQueries({ queryKey: ['sales'] })
      queryClient.invalidateQueries({ queryKey: ['sales-summary'] })
      onClose()
    },
    onError: () => toast.error('Failed to record sale'),
  })

  const onSubmit = (values: SaleForm) => {
    mutation.mutate({
      store_id:           parseInt(values.store_id),
      transaction_amount: parseFloat(values.transaction_amount),
      payment_method:     values.payment_method,
      coupon_code:        values.coupon_code || undefined,
      discount_amount:    values.discount_amount ? parseFloat(values.discount_amount) : undefined,
    })
  }

  return (
    <div className="fixed inset-0 z-[60] flex items-end">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />
      <div className="relative w-full bg-white rounded-t-3xl pb-safe">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h2 className="font-semibold text-gray-800">Record Sale</h2>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
            <X size={18} className="text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
          {/* Store */}
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Store *</label>
            <select {...register('store_id')} className="input-field">
              <option value="">Select store…</option>
              {stores.map(s => (
                <option key={s.id} value={s.id}>{s.store_name}</option>
              ))}
            </select>
            {errors.store_id && <p className="text-xs text-red-500 mt-1">{errors.store_id.message}</p>}
          </div>

          {/* Amount */}
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Amount (₹) *</label>
            <input
              {...register('transaction_amount')}
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              className="input-field"
            />
            {errors.transaction_amount && <p className="text-xs text-red-500 mt-1">{errors.transaction_amount.message}</p>}
          </div>

          {/* Payment method */}
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-2">Payment Method</label>
            <div className="grid grid-cols-5 gap-2">
              {(['cash', 'card', 'upi', 'wallet', 'other'] as const).map(pm => (
                <label key={pm} className="flex flex-col items-center gap-1 cursor-pointer">
                  <input type="radio" value={pm} {...register('payment_method')} className="sr-only peer" />
                  <span className="w-full text-center py-2 rounded-xl text-xs font-medium border border-gray-200 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700 transition-colors">
                    {PM_LABELS[pm]}
                  </span>
                </label>
              ))}
            </div>
          </div>

          {/* Coupon code */}
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Coupon Code (optional)</label>
            <input
              {...register('coupon_code')}
              placeholder="e.g. SAVE20"
              className="input-field uppercase"
            />
          </div>

          {/* Discount */}
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Discount Given (₹)</label>
            <input
              {...register('discount_amount')}
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              className="input-field"
            />
          </div>

          <button
            type="submit"
            disabled={mutation.isPending}
            className="btn-primary w-full"
          >
            {mutation.isPending ? 'Recording…' : 'Record Sale'}
          </button>
        </form>
      </div>
    </div>
  )
}

// ── Main Page ────────────────────────────────────────────────────────────────

export default function SalesPage() {
  const navigate     = useNavigate()
  const [page, setPage]       = useState(1)
  const [showModal, setModal] = useState(false)

  // Summary (current month default)
  const { data: summary } = useQuery({
    queryKey: ['sales-summary'],
    queryFn:  () => salesApi.summary().then(r => r.data.data ?? null),
  })

  // Paginated list
  const { data: salesResp, isLoading, isFetching } = useQuery({
    queryKey:    ['sales', page],
    queryFn:     () => salesApi.list({ page, limit: 20 }).then(r => {
      const meta = r.data.meta ?? { total: 0, page: 1, limit: 20, pages: 1 }
      return { items: (r.data.data ?? []) as SaleRecord[], meta }
    }),
    placeholderData: keepPreviousData,
  })

  const sales = salesResp?.items ?? []
  const meta  = salesResp?.meta ?? { total: 0, page: 1, limit: 20, pages: 1 }

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
            <h1 className="text-white font-bold text-xl">Sales Registry</h1>
            <p className="text-white/70 text-xs mt-0.5">Track your transactions</p>
          </div>
          <button
            onClick={() => {
              const from = new Date()
              from.setDate(1)
              const fromStr = from.toISOString().slice(0, 10)
              const toStr   = new Date().toISOString().slice(0, 10)
              window.open(salesApi.exportUrl(fromStr, toStr), '_blank')
            }}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30"
            title="Export CSV"
          >
            <Download size={16} className="text-white" />
          </button>
        </div>

        {/* Summary pill cards */}
        {summary && (
          <div className="grid grid-cols-3 gap-2 mt-4">
            <div className="bg-white/15 rounded-2xl p-3 text-center">
              <ShoppingCart size={14} className="text-white/80 mx-auto mb-1" />
              <p className="text-white font-bold text-lg leading-none">{summary.total_sales}</p>
              <p className="text-white/70 text-[10px] mt-1">Sales</p>
            </div>
            <div className="bg-white/15 rounded-2xl p-3 text-center">
              <TrendingUp size={14} className="text-white/80 mx-auto mb-1" />
              <p className="text-white font-bold text-lg leading-none">
                ₹{summary.total_revenue.toLocaleString('en-IN')}
              </p>
              <p className="text-white/70 text-[10px] mt-1">Revenue</p>
            </div>
            <div className="bg-white/15 rounded-2xl p-3 text-center">
              <BarChart2 size={14} className="text-white/80 mx-auto mb-1" />
              <p className="text-white font-bold text-lg leading-none">
                ₹{summary.avg_transaction.toLocaleString('en-IN', { maximumFractionDigits: 0 })}
              </p>
              <p className="text-white/70 text-[10px] mt-1">Avg</p>
            </div>
          </div>
        )}
      </div>

      <div className="px-4 py-4">
        {/* Transaction list */}
        <div className="bg-white rounded-2xl shadow-sm px-4">
          {isLoading ? (
            <div className="py-16 text-center text-gray-400 text-sm">Loading…</div>
          ) : sales.length === 0 ? (
            <div className="py-16 text-center">
              <ShoppingCart size={32} className="text-gray-300 mx-auto mb-3" />
              <p className="text-gray-500 text-sm font-medium">No sales recorded yet</p>
              <p className="text-gray-400 text-xs mt-1">Tap the + button to record your first sale</p>
            </div>
          ) : (
            <>
              {sales.map(s => <SaleRow key={s.id} sale={s} />)}

              {/* Pagination */}
              {meta.pages > 1 && (
                <div className="flex items-center justify-between py-3 border-t border-gray-100">
                  <button
                    onClick={() => setPage(p => Math.max(1, p - 1))}
                    disabled={page <= 1 || isFetching}
                    className="text-sm text-brand-600 font-medium disabled:text-gray-300"
                  >
                    ← Prev
                  </button>
                  <span className="text-xs text-gray-500">
                    Page {meta.page} of {meta.pages}
                  </span>
                  <button
                    onClick={() => setPage(p => Math.min(meta.pages, p + 1))}
                    disabled={page >= meta.pages || isFetching}
                    className="text-sm text-brand-600 font-medium disabled:text-gray-300"
                  >
                    Next →
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      </div>

      {/* FAB */}
      <button
        onClick={() => setModal(true)}
        className="fixed right-5 bottom-24 w-14 h-14 bg-brand-600 rounded-full shadow-lg flex items-center justify-center hover:bg-brand-700 active:scale-95 transition-all"
      >
        <Plus size={24} className="text-white" />
      </button>

      {showModal && <RecordSaleModal onClose={() => setModal(false)} />}
    </div>
  )
}

