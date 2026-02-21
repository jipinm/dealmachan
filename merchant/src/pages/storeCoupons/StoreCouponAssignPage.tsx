import { useState, useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Gift, Search, CheckCircle, Users } from 'lucide-react'
import toast from 'react-hot-toast'
import { storeCouponApi } from '@/api/endpoints/storeCoupons'
import { merchantCustomerApi, type MerchantCustomer } from '@/api/endpoints/customers'

export default function StoreCouponAssignPage() {
  const { id }   = useParams<{ id: string }>()
  const navigate = useNavigate()
  const qc       = useQueryClient()

  const [search, setSearch]         = useState('')
  const [debouncedSearch, setDebSearch] = useState('')
  const [selected, setSelected]     = useState<number[]>([])
  const [mode, setMode]             = useState<'single' | 'bulk'>('single')

  const handleSearch = useCallback((v: string) => {
    setSearch(v)
    clearTimeout((window as any).__assignSearchTimer)
    ;(window as any).__assignSearchTimer = setTimeout(() => setDebSearch(v), 400)
  }, [])

  const { data: coupon } = useQuery({
    queryKey: ['store-coupon', id],
    queryFn:  () => storeCouponApi.get(Number(id)).then(r => r.data.data),
    enabled:  !!id,
  })

  const { data: customersData, isLoading: loadingCustomers } = useQuery({
    queryKey: ['merchant-customers', 1, debouncedSearch],
    queryFn:  () => merchantCustomerApi.list({ page: 1, limit: 50, search: debouncedSearch || undefined }).then(r => r.data.data),
  })

  const customers = customersData ?? []

  const assignMutation = useMutation({
    mutationFn: (customerId: number) => storeCouponApi.assign(Number(id), { customer_id: customerId }),
    onSuccess: () => {
      toast.success('Coupon assigned!')
      qc.invalidateQueries({ queryKey: ['store-coupons'] })
      qc.invalidateQueries({ queryKey: ['store-coupon', id] })
      navigate(-1)
    },
    onError: (err: any) => toast.error(err?.response?.data?.message ?? 'Failed to assign'),
  })

  const bulkAssignMutation = useMutation({
    mutationFn: (customerIds: number[]) => storeCouponApi.bulkAssign(Number(id), { customer_ids: customerIds }),
    onSuccess: (res) => {
      toast.success(`Assigned to ${res.data.data.assigned_count} customer(s)!`)
      qc.invalidateQueries({ queryKey: ['store-coupons'] })
      qc.invalidateQueries({ queryKey: ['store-coupon', id] })
      navigate(-1)
    },
    onError: (err: any) => toast.error(err?.response?.data?.message ?? 'Bulk assign failed'),
  })

  const toggleCustomer = (cId: number) => {
    setSelected(prev =>
      prev.includes(cId)
        ? prev.filter(x => x !== cId)
        : prev.length < 10 ? [...prev, cId] : prev
    )
  }

  const isPending = assignMutation.isPending || bulkAssignMutation.isPending

  const discountLabel = coupon
    ? coupon.discount_type === 'percentage'
      ? `${parseFloat(coupon.discount_value).toFixed(0)}% OFF`
      : `₹${parseFloat(coupon.discount_value).toFixed(0)} OFF`
    : ''

  return (
    <div className="min-h-full bg-gray-50 pb-10">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-lg">Assign Coupon</h1>
            <p className="text-white/70 text-xs mt-0.5">{coupon?.title ?? '…'} · {discountLabel}</p>
          </div>
        </div>

        {/* Mode toggle */}
        <div className="flex gap-2 mt-4">
          <button
            onClick={() => { setMode('single'); setSelected([]) }}
            className={`flex-1 py-2 rounded-xl text-xs font-semibold text-center ${mode === 'single' ? 'bg-white text-brand-700' : 'bg-white/20 text-white/80'}`}
          >
            Single Assign
          </button>
          <button
            onClick={() => { setMode('bulk'); setSelected([]) }}
            className={`flex-1 py-2 rounded-xl text-xs font-semibold text-center ${mode === 'bulk' ? 'bg-white text-brand-700' : 'bg-white/20 text-white/80'}`}
          >
            Bulk Assign (up to 10)
          </button>
        </div>

        {/* Search */}
        <div className="mt-3 relative">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-white/50" />
          <input
            value={search}
            onChange={e => handleSearch(e.target.value)}
            placeholder="Search customers by name, phone, email…"
            className="w-full bg-white/20 text-white placeholder-white/60 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:bg-white/30"
          />
        </div>
      </div>

      <div className="px-4 py-4">
        {mode === 'bulk' && selected.length > 0 && (
          <div className="bg-purple-50 rounded-2xl p-3 mb-3 flex items-center justify-between">
            <span className="text-sm text-purple-700 font-medium">{selected.length} customer{selected.length !== 1 ? 's' : ''} selected</span>
            <button
              onClick={() => bulkAssignMutation.mutate(selected)}
              disabled={isPending}
              className="px-4 py-2 bg-purple-600 text-white text-xs font-bold rounded-xl disabled:opacity-50"
            >
              {isPending ? 'Assigning…' : 'Assign All'}
            </button>
          </div>
        )}

        <div className="bg-white rounded-2xl shadow-sm">
          {loadingCustomers ? (
            <div className="py-16 text-center text-gray-400 text-sm">Loading…</div>
          ) : customers.length === 0 ? (
            <div className="py-16 text-center">
              <Users size={36} className="text-gray-300 mx-auto mb-3" />
              <p className="text-gray-500 font-medium">No customers found</p>
              <p className="text-gray-400 text-sm mt-1">Try a different search term</p>
            </div>
          ) : (
            <div className="divide-y divide-gray-50">
              {customers.map((c) => {
                const isSelected = selected.includes(c.id)
                const initials = c.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()

                return (
                  <div key={c.id} className="flex items-center gap-3 px-4 py-3">
                    {mode === 'bulk' && (
                      <input
                        type="checkbox"
                        checked={isSelected}
                        onChange={() => toggleCustomer(c.id)}
                        disabled={!isSelected && selected.length >= 10}
                        className="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                      />
                    )}
                    <div className="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center shrink-0">
                      <span className="text-brand-700 text-sm font-bold">{initials}</span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-semibold text-gray-800 truncate">{c.name}</p>
                      <p className="text-xs text-gray-400">{c.phone ?? c.email ?? ''}</p>
                    </div>
                    {mode === 'single' && (
                      <button
                        onClick={() => assignMutation.mutate(c.id)}
                        disabled={isPending}
                        className="px-3 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-lg disabled:opacity-50 flex items-center gap-1"
                      >
                        <Gift size={12} /> Assign
                      </button>
                    )}
                  </div>
                )
              })}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
