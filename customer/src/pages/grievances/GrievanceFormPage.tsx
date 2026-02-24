import { useState, useRef, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Search, Building2, Loader2, CheckCircle } from 'lucide-react'
import { grievancesApi, CreateGrievanceBody } from '@/api/endpoints/grievances'
import { publicApi, PublicMerchant } from '@/api/endpoints/public'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'
import { useDebounce } from '@/lib/useDebounce'

// ── Page ─────────────────────────────────────────────────────────────────

export default function GrievanceFormPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  // ── Merchant search
  const [merchantQuery, setMerchantQuery] = useState('')
  const debouncedMQ = useDebounce(merchantQuery, 350)
  const [selectedMerchant, setSelectedMerchant] = useState<PublicMerchant | null>(null)
  const [showDropdown, setShowDropdown] = useState(false)
  const dropdownRef = useRef<HTMLDivElement>(null)

  const { data: merchantResults, isFetching: searchingMerchants } = useQuery({
    queryKey: ['merchant-search-grievance', debouncedMQ],
    queryFn: () => publicApi.getMerchants({ search: debouncedMQ, per_page: 8 }),
    enabled: debouncedMQ.length >= 2,
    staleTime: 30_000,
  })

  // Extract merchant list
  const merchantList: PublicMerchant[] =
    (merchantResults?.data as any)?.data ?? (merchantResults?.data as any) ?? []

  // ── Form state
  const [storeId, setStoreId] = useState<number | ''>('')
  const [subject, setSubject] = useState('')
  const [description, setDescription] = useState('')

  // Stores from selected merchant
  const stores = selectedMerchant?.stores ?? []

  // Close dropdown on outside click
  useEffect(() => {
    function handler(e: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setShowDropdown(false)
      }
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [])

  // ── Submit
  const mutation = useMutation({
    mutationFn: (body: CreateGrievanceBody) => grievancesApi.create(body),
    onSuccess: (data) => {
      toast.success('Grievance submitted successfully')
      queryClient.invalidateQueries({ queryKey: ['grievances'] })
      navigate(`/grievances/${data.id}`)
    },
    onError: (err) => {
      toast.error(getApiError(err))
    },
  })

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!selectedMerchant) {
      toast.error('Please select a merchant')
      return
    }
    if (subject.trim().length < 5) {
      toast.error('Subject must be at least 5 characters')
      return
    }
    if (description.trim().length < 20) {
      toast.error('Description must be at least 20 characters')
      return
    }
    mutation.mutate({
      merchant_id: selectedMerchant.id,
      store_id: storeId ? Number(storeId) : null,
      subject: subject.trim(),
      description: description.trim(),
    })
  }

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6">
      {/* Back */}
      <Link
        to="/grievances"
        className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-600 mb-5 transition-colors"
      >
        <ArrowLeft size={16} /> Back to Grievances
      </Link>

      <h1 className="font-heading font-bold text-2xl text-slate-800 mb-1">File a Grievance</h1>
      <p className="text-sm text-slate-500 mb-6">
        Report a complaint against a merchant. Our team will review and follow up.
      </p>

      <form onSubmit={handleSubmit}>
        <div className="card p-6 md:p-8 space-y-6">

          {/* Row 1: Merchant + Store */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            {/* Merchant search */}
            <div>
              <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                Merchant <span className="text-rose-500">*</span>
              </label>

              {selectedMerchant ? (
                <div className="flex items-center gap-3 p-3 bg-white border border-brand-300 rounded-2xl h-[48px]">
                  <div className="w-8 h-8 rounded-xl gradient-brand flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    {selectedMerchant.business_name.charAt(0)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="font-semibold text-sm text-slate-800 line-clamp-1">{selectedMerchant.business_name}</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => { setSelectedMerchant(null); setMerchantQuery(''); setStoreId('') }}
                    className="text-xs text-slate-400 hover:text-rose-500 transition-colors flex-shrink-0"
                  >
                    Change
                  </button>
                </div>
              ) : (
                <div ref={dropdownRef} className="relative">
                  <div className="relative">
                    <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                      type="text"
                      value={merchantQuery}
                      onChange={e => { setMerchantQuery(e.target.value); setShowDropdown(true) }}
                      onFocus={() => setShowDropdown(true)}
                      placeholder="Search for a merchant…"
                      className="w-full pl-9 pr-4 py-3 rounded-2xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 bg-white"
                    />
                    {searchingMerchants && (
                      <Loader2 size={16} className="absolute right-3 top-1/2 -translate-y-1/2 animate-spin text-slate-400" />
                    )}
                  </div>

                  {showDropdown && merchantList.length > 0 && (
                    <div className="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-2xl shadow-lg overflow-hidden">
                      {merchantList.map(m => (
                        <button
                          key={m.id}
                          type="button"
                          onClick={() => {
                            setSelectedMerchant(m)
                            setMerchantQuery(m.business_name)
                            setShowDropdown(false)
                          }}
                          className="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0"
                        >
                          <div className="w-8 h-8 rounded-xl gradient-brand flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                            {m.business_name.charAt(0)}
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-slate-800 line-clamp-1">{m.business_name}</p>
                            {m.tags?.length > 0 && (
                              <p className="text-xs text-slate-400">{m.tags[0].tag_name}</p>
                            )}
                          </div>
                        </button>
                      ))}
                    </div>
                  )}

                  {showDropdown && debouncedMQ.length >= 2 && !searchingMerchants && merchantList.length === 0 && (
                    <div className="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-2xl shadow-lg p-4 text-center text-sm text-slate-400">
                      No merchants found for "{debouncedMQ}"
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Store / Branch */}
            <div>
              <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                Store / Branch <span className="text-slate-400 font-normal">(optional)</span>
              </label>
              {selectedMerchant && stores.length > 0 ? (
                <select
                  value={storeId}
                  onChange={e => setStoreId(e.target.value ? Number(e.target.value) : '')}
                  className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 bg-white"
                >
                  <option value="">Not specific to a branch</option>
                  {stores.map(s => (
                    <option key={s.id} value={s.id}>
                      {s.store_name}{s.area_name ? ` — ${s.area_name}` : ''}
                    </option>
                  ))}
                </select>
              ) : (
                <div className="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm text-slate-400">
                  {selectedMerchant ? 'No branches available' : 'Select a merchant first'}
                </div>
              )}
            </div>
          </div>

          {/* Row 2: Subject + Description */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
            {/* Subject */}
            <div>
              <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                Subject <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                value={subject}
                onChange={e => setSubject(e.target.value)}
                placeholder="e.g. Coupon code not accepted at store"
                maxLength={255}
                className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
              <p className="text-xs text-slate-400 mt-1 text-right">{subject.length}/255</p>
            </div>

            {/* Description */}
            <div>
              <label className="block text-sm font-semibold text-slate-700 mb-1.5">
                Description <span className="text-rose-500">*</span>
              </label>
              <textarea
                value={description}
                onChange={e => setDescription(e.target.value)}
                rows={4}
                placeholder="Describe your issue in detail — what happened, when, and any relevant order/coupon details…"
                className="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 resize-none"
              />
              <p className="text-xs text-slate-400 mt-1">{description.length} chars (min 20)</p>
            </div>
          </div>

          {/* Row 3: Info box + Submit */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5 items-center">
            <div className="flex items-start gap-2.5 bg-blue-50 border border-blue-100 rounded-2xl p-4 text-sm text-blue-700">
              <Building2 size={16} className="flex-shrink-0 mt-0.5" />
              <p>
                The merchant will be notified. Our team reviews all grievances within 3–5 business days.
              </p>
            </div>

            <button
              type="submit"
              disabled={mutation.isPending}
              className="w-full btn-primary !py-3.5 !rounded-2xl flex items-center justify-center gap-2"
            >
              {mutation.isPending ? (
                <><Loader2 size={18} className="animate-spin" /> Submitting…</>
              ) : (
                <><CheckCircle size={18} /> Submit Grievance</>
              )}
            </button>
          </div>

        </div>
      </form>
    </div>
  )
}
