import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query'
import { ChevronLeft, Plus, X, Users, Phone, Mail, CheckCircle } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import toast from 'react-hot-toast'
import {
  merchantCustomerApi,
  type MerchantCustomer,
  type CreateCustomerPayload,
} from '@/api/endpoints/customers'

// ── Schema ────────────────────────────────────────────────────────────────────

const schema = z.object({
  name:  z.string().min(1, 'Name required'),
  phone: z.string().optional(),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
}).refine(d => d.phone || d.email, {
  message: 'Phone or email is required',
  path: ['phone'],
})
type FormData = z.infer<typeof schema>

// ── Create Modal ──────────────────────────────────────────────────────────────

function CreateCustomerModal({ onClose }: { onClose: (created?: MerchantCustomer) => void }) {
  const qc = useQueryClient()

  const { register, handleSubmit, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
  })

  const mutation = useMutation({
    mutationFn: (data: CreateCustomerPayload) => merchantCustomerApi.create(data),
    onSuccess: (res) => {
      toast.success('Customer created!')
      qc.invalidateQueries({ queryKey: ['merchant-customers'] })
      onClose(res.data.data)
    },
    onError: (err: any) => {
      const msg = err?.response?.data?.message ?? 'Failed to create customer'
      toast.error(msg)
    },
  })

  const onSubmit = (values: FormData) => {
    mutation.mutate({
      name:  values.name,
      phone: values.phone || undefined,
      email: values.email || undefined,
    })
  }

  return (
    <div className="fixed inset-0 z-[60] flex items-end">
      <div className="absolute inset-0 bg-black/40" onClick={() => onClose()} />
      <div className="relative w-full bg-white rounded-t-3xl pb-safe">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h2 className="font-semibold text-gray-800">Add Customer</h2>
          <button onClick={() => onClose()} className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
            <X size={18} className="text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="px-5 py-4 space-y-4">
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Full Name *</label>
            <input {...register('name')} placeholder="Customer name" className="input-field" />
            {errors.name && <p className="text-xs text-red-500 mt-1">{errors.name.message}</p>}
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Phone</label>
            <input {...register('phone')} type="tel" placeholder="+91 98765 43210" className="input-field" />
            {errors.phone && <p className="text-xs text-red-500 mt-1">{errors.phone.message}</p>}
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Email</label>
            <input {...register('email')} type="email" placeholder="customer@email.com" className="input-field" />
            {errors.email && <p className="text-xs text-red-500 mt-1">{errors.email.message}</p>}
          </div>

          <p className="text-xs text-gray-400">
            A customer account will be created. They can reset their password via the customer app.
          </p>

          <button type="submit" disabled={mutation.isPending} className="btn-primary w-full">
            {mutation.isPending ? 'Creating…' : 'Create Customer'}
          </button>
        </form>
      </div>
    </div>
  )
}

// ── Customer Row ──────────────────────────────────────────────────────────────

function CustomerRow({ customer }: { customer: MerchantCustomer }) {
  const date = new Date(customer.created_at).toLocaleDateString('en-IN', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
  const initials = customer.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()

  return (
    <div className="flex items-center gap-3 py-3 border-b border-gray-50 last:border-0">
      <div className="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center shrink-0">
        <span className="text-brand-700 text-sm font-bold">{initials}</span>
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-sm font-semibold text-gray-800 truncate">{customer.name}</p>
        <div className="flex items-center gap-2 mt-0.5">
          {customer.phone && (
            <span className="flex items-center gap-1 text-xs text-gray-400">
              <Phone size={9} />{customer.phone}
            </span>
          )}
          {customer.email && (
            <span className="flex items-center gap-1 text-xs text-gray-400">
              <Mail size={9} />{customer.email}
            </span>
          )}
        </div>
      </div>
      <div className="text-right shrink-0">
        <div className="flex items-center gap-1 justify-end">
          <CheckCircle size={10} className="text-green-500" />
          <span className="text-xs text-gray-400">{date}</span>
        </div>
        <span className="text-[10px] text-gray-400 capitalize">{customer.customer_type}</span>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function CustomersPage() {
  const navigate = useNavigate()
  const [page, setPage]         = useState(1)
  const [showModal, setModal]   = useState(false)
  const [search, setSearch]     = useState('')
  const [debouncedSearch, setDebSearch] = useState('')

  // Debounce search
  const handleSearch = (v: string) => {
    setSearch(v)
    clearTimeout((window as any).__searchTimer)
    ;(window as any).__searchTimer = setTimeout(() => { setDebSearch(v); setPage(1) }, 400)
  }

  const { data, isLoading, isFetching } = useQuery({
    queryKey: ['merchant-customers', page, debouncedSearch],
    queryFn:  () => merchantCustomerApi.list({ page, limit: 20, search: debouncedSearch || undefined }).then(r => ({
      items: r.data.data,
      meta:  r.data.meta ?? { total: 0, page: 1, limit: 20, pages: 1 },
    })),
    placeholderData: keepPreviousData,
  })

  const customers = data?.items ?? []
  const meta      = data?.meta  ?? { total: 0, page: 1, limit: 20, pages: 1 }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-xl">Customers</h1>
            <p className="text-white/70 text-xs mt-0.5">{meta.total} registered via your app</p>
          </div>
          <button onClick={() => setModal(true)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30">
            <Plus size={20} className="text-white" />
          </button>
        </div>

        {/* Search */}
        <div className="mt-4">
          <input
            value={search}
            onChange={e => handleSearch(e.target.value)}
            placeholder="Search by name, phone or email…"
            className="w-full bg-white/20 text-white placeholder-white/60 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white/30"
          />
        </div>
      </div>

      <div className="px-4 py-4">
        <div className="bg-white rounded-2xl shadow-sm px-4">
          {isLoading ? (
            <div className="py-16 text-center text-gray-400 text-sm">Loading…</div>
          ) : customers.length === 0 ? (
            <div className="py-16 text-center">
              <Users size={36} className="text-gray-300 mx-auto mb-3" />
              <p className="text-gray-500 font-medium">No customers yet</p>
              <p className="text-gray-400 text-sm mt-1">Tap + to register a customer</p>
            </div>
          ) : (
            <>
              {customers.map(c => <CustomerRow key={c.id} customer={c} />)}

              {meta.pages > 1 && (
                <div className="flex items-center justify-between py-3 border-t border-gray-100">
                  <button
                    onClick={() => setPage(p => Math.max(1, p - 1))}
                    disabled={page <= 1 || isFetching}
                    className="text-sm text-brand-600 font-medium disabled:text-gray-300"
                  >
                    ← Prev
                  </button>
                  <span className="text-xs text-gray-500">Page {meta.page} of {meta.pages}</span>
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

      {showModal && <CreateCustomerModal onClose={() => setModal(false)} />}
    </div>
  )
}
