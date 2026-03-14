import { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, ImagePlus, Plus, X, Zap, Trash2, Edit2, Store as StoreIcon } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import toast from 'react-hot-toast'
import {
  flashDiscountApi,
  type FlashDiscount,
  type CreateFlashDiscountPayload,
  type RedeemFlashDiscountPayload,
} from '@/api/endpoints/flashDiscounts'
import { storeApi } from '@/api/endpoints/stores'
import { useAuthStore } from '@/store/authStore'
import { getImageUrl } from '@/lib/imageUrl'

// ── Form schema ───────────────────────────────────────────────────────────────

const schema = z.object({
  title:               z.string().min(1, 'Title required'),
  description:         z.string().optional(),
  discount_percentage: z.string().refine(v => !isNaN(parseFloat(v)) && parseFloat(v) > 0 && parseFloat(v) <= 100, 'Must be 1–100'),
  store_id:            z.string().optional(),
  valid_from:          z.string().optional(),
  valid_until:         z.string().optional(),
  max_redemptions:     z.string().optional(),
})
type FormData = z.infer<typeof schema>

const STATUS_STYLE: Record<string, string> = {
  active:   'bg-emerald-100 text-emerald-700',
  inactive: 'bg-gray-100 text-gray-500',
  expired:  'bg-red-100 text-red-600',
}

// ── Form Modal ────────────────────────────────────────────────────────────────

function FlashDiscountFormModal({
  editing,
  onClose,
}: {
  editing: FlashDiscount | null
  onClose: () => void
}) {
  const qc = useQueryClient()
  const isStoreAdmin  = useAuthStore(s => s.isStoreAdmin())
  const scopedStoreId = useAuthStore(s => s.scopedStoreId())

  const { data: stores = [] } = useQuery({
    queryKey: ['stores'],
    queryFn:  () => storeApi.list().then(r => r.data.data),
    enabled:  !isStoreAdmin,
  })

  const [bannerImage, setBannerImage]                 = useState<string | null>(editing?.banner_image ?? null)
  const [uploadingImg, setUploadingImg]               = useState(false)
  const [removingImg, setRemovingImg]                 = useState(false)
  const fileInputRef                                  = useRef<HTMLInputElement>(null)
  const [pendingImageFile, setPendingImageFile]       = useState<File | null>(null)
  const [pendingImagePreview, setPendingImagePreview] = useState<string | null>(null)
  const createFileInputRef                            = useRef<HTMLInputElement>(null)
  const pendingPreviewUrlRef                          = useRef<string | null>(null)

  useEffect(() => {
    return () => { if (pendingPreviewUrlRef.current) URL.revokeObjectURL(pendingPreviewUrlRef.current) }
  }, [])

  const { register, handleSubmit, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: editing ? {
      title:               editing.title,
      description:         editing.description ?? '',
      discount_percentage: editing.discount_percentage,
      store_id:            editing.store_id ? String(editing.store_id) : '',
      valid_from:          editing.valid_from?.slice(0, 16) ?? '',
      valid_until:         editing.valid_until?.slice(0, 16) ?? '',
      max_redemptions:     editing.max_redemptions ? String(editing.max_redemptions) : '',
    } : {},
  })

  const createMutation = useMutation({
    mutationFn: async (data: CreateFlashDiscountPayload) => {
      const res = await flashDiscountApi.create(data)
      let _imageUploadFailed = false
      if (pendingImageFile) {
        try { await flashDiscountApi.uploadImage(res.data.data.id, pendingImageFile) }
        catch { _imageUploadFailed = true }
      }
      return { ...res.data, _imageUploadFailed }
    },
    onSuccess: (data: any) => {
      if (data._imageUploadFailed) {
        toast.success('Flash discount created!')
        toast.error('Image upload failed — re-upload from the edit form.', { duration: 5000 })
      } else {
        toast.success('Flash discount created!')
      }
      qc.invalidateQueries({ queryKey: ['flash-discounts'] })
      onClose()
    },
    onError: () => toast.error('Failed to create'),
  })

  const updateMutation = useMutation({
    mutationFn: (data: Partial<CreateFlashDiscountPayload>) => flashDiscountApi.update(editing!.id, data),
    onSuccess: () => { toast.success('Updated!'); qc.invalidateQueries({ queryKey: ['flash-discounts'] }); onClose() },
    onError: () => toast.error('Failed to update'),
  })

  async function handleImageUpload(file: File) {
    setUploadingImg(true)
    try {
      const res = await flashDiscountApi.uploadImage(editing!.id, file)
      setBannerImage(res.data.data.banner_image)
      qc.invalidateQueries({ queryKey: ['flash-discounts'] })
      toast.success('Image uploaded!')
    } catch (e: any) {
      toast.error(e?.response?.data?.message ?? 'Upload failed')
    } finally {
      setUploadingImg(false)
    }
  }

  async function handleImageRemove() {
    setRemovingImg(true)
    try {
      await flashDiscountApi.deleteImage(editing!.id)
      setBannerImage(null)
      qc.invalidateQueries({ queryKey: ['flash-discounts'] })
      toast.success('Image removed')
    } catch (e: any) {
      toast.error(e?.response?.data?.message ?? 'Remove failed')
    } finally {
      setRemovingImg(false)
    }
  }

  function handlePendingImageSelect(file: File) {
    if (pendingPreviewUrlRef.current) URL.revokeObjectURL(pendingPreviewUrlRef.current)
    const url = URL.createObjectURL(file)
    pendingPreviewUrlRef.current = url
    setPendingImageFile(file)
    setPendingImagePreview(url)
  }

  function handlePendingImageClear() {
    if (pendingPreviewUrlRef.current) URL.revokeObjectURL(pendingPreviewUrlRef.current)
    pendingPreviewUrlRef.current = null
    setPendingImageFile(null)
    setPendingImagePreview(null)
  }

  const onSubmit = (values: FormData) => {
    const payload: CreateFlashDiscountPayload = {
      title:               values.title,
      description:         values.description || undefined,
      discount_percentage: parseFloat(values.discount_percentage),
      // For store admins the backend enforces their store_id; pass it
      // explicitly so the form stays consistent.
      store_id:            isStoreAdmin && scopedStoreId
                             ? scopedStoreId
                             : values.store_id ? parseInt(values.store_id) : undefined,
      valid_from:          values.valid_from || undefined,
      valid_until:         values.valid_until || undefined,
      max_redemptions:     values.max_redemptions ? parseInt(values.max_redemptions) : undefined,
    }
    editing ? updateMutation.mutate(payload) : createMutation.mutate(payload)
  }

  const isPending = createMutation.isPending || updateMutation.isPending

  return (
    <div className="fixed inset-0 z-[60] flex items-end">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />
      <div className="relative w-full bg-white rounded-t-3xl pb-safe max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100 sticky top-0 bg-white">
          <h2 className="font-semibold text-gray-800">{editing ? 'Edit Flash Discount' : 'New Flash Discount'}</h2>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
            <X size={18} className="text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="px-5 py-4 space-y-4">
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Title *</label>
            <input {...register('title')} placeholder="e.g. Weekend Flash Sale" className="input-field" />
            {errors.title && <p className="text-xs text-red-500 mt-1">{errors.title.message}</p>}
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Discount (%) *</label>
            <input {...register('discount_percentage')} type="number" step="0.01" min="1" max="100" placeholder="20" className="input-field" />
            {errors.discount_percentage && <p className="text-xs text-red-500 mt-1">{errors.discount_percentage.message}</p>}
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Store (optional)</label>
            {isStoreAdmin ? (
              <p className="input-field bg-gray-50 text-gray-500 cursor-not-allowed">
                Your assigned store
              </p>
            ) : (
              <select {...register('store_id')} className="input-field">
                <option value="">All stores</option>
                {stores.map(s => <option key={s.id} value={s.id}>{s.store_name}</option>)}
              </select>
            )}
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Valid From</label>
              <input {...register('valid_from')} type="datetime-local" className="input-field text-sm" />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Valid Until</label>
              <input {...register('valid_until')} type="datetime-local" className="input-field text-sm" />
            </div>
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Max Redemptions</label>
            <input {...register('max_redemptions')} type="number" min="1" placeholder="Unlimited" className="input-field" />
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Description</label>
            <textarea {...register('description')} rows={3} placeholder="Optional details…" className="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-brand-300" />
          </div>

          {/* Deal Image */}
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Deal Image</label>
            {editing ? (
              <>
                {bannerImage ? (
                  <div className="relative">
                    <img src={getImageUrl(bannerImage)} alt="Flash deal banner"
                      className="w-full h-36 object-cover rounded-xl bg-slate-100" />
                    <button type="button" onClick={handleImageRemove} disabled={removingImg}
                      className="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 disabled:opacity-50">
                      <Trash2 size={14} />
                    </button>
                  </div>
                ) : (
                  <div onClick={() => fileInputRef.current?.click()}
                    className="border-2 border-dashed border-gray-200 rounded-xl h-28 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-brand-400 hover:bg-brand-50 transition-colors">
                    {uploadingImg ? <span className="text-sm text-gray-500">Uploading…</span> : (
                      <>
                        <ImagePlus size={22} className="text-gray-400" />
                        <span className="text-sm text-gray-500">Tap to upload deal image</span>
                        <span className="text-xs text-gray-400">JPEG, PNG or WebP · max 5MB</span>
                      </>
                    )}
                  </div>
                )}
                <input ref={fileInputRef} type="file" accept="image/jpeg,image/png,image/webp" className="hidden"
                  onChange={(e) => { const f = e.target.files?.[0]; if (f) handleImageUpload(f); e.target.value = '' }} />
                {bannerImage && (
                  <button type="button" onClick={() => fileInputRef.current?.click()} disabled={uploadingImg}
                    className="mt-2 w-full border border-brand-300 text-brand-600 text-sm font-semibold py-2 rounded-xl hover:bg-brand-50 transition-colors disabled:opacity-50">
                    {uploadingImg ? 'Uploading…' : 'Replace Image'}
                  </button>
                )}
              </>
            ) : (
              <>
                {pendingImagePreview ? (
                  <div className="relative">
                    <img src={pendingImagePreview} alt="Deal image preview"
                      className="w-full h-36 object-cover rounded-xl bg-slate-100" />
                    <button type="button" onClick={handlePendingImageClear}
                      className="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5">
                      <Trash2 size={14} />
                    </button>
                  </div>
                ) : (
                  <div onClick={() => createFileInputRef.current?.click()}
                    className="border-2 border-dashed border-gray-200 rounded-xl h-28 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-brand-400 hover:bg-brand-50 transition-colors">
                    <ImagePlus size={22} className="text-gray-400" />
                    <span className="text-sm text-gray-500">Tap to add a deal image</span>
                    <span className="text-xs text-gray-400">JPEG, PNG or WebP · max 5MB</span>
                  </div>
                )}
                <input ref={createFileInputRef} type="file" accept="image/jpeg,image/png,image/webp" className="hidden"
                  onChange={(e) => { const f = e.target.files?.[0]; if (f) handlePendingImageSelect(f); e.target.value = '' }} />
                {pendingImagePreview && (
                  <button type="button" onClick={() => createFileInputRef.current?.click()}
                    className="mt-2 w-full border border-brand-300 text-brand-600 text-sm font-semibold py-2 rounded-xl hover:bg-brand-50 transition-colors">
                    Replace Image
                  </button>
                )}
              </>
            )}
          </div>

          <button type="submit" disabled={isPending} className="btn-primary w-full">
            {isPending ? 'Saving…' : editing ? 'Save Changes' : 'Create'}
          </button>
        </form>
      </div>
    </div>
  )
}

// ── Redeem Modal ──────────────────────────────────────────────────────────────

function RedeemFlashDiscountModal({
  discount,
  onClose,
}: {
  discount: FlashDiscount
  onClose: () => void
}) {
  const qc = useQueryClient()
  const [customerPhone, setCustomerPhone] = useState('')
  const [txAmount, setTxAmount] = useState('')

  const mutation = useMutation({
    mutationFn: (data: RedeemFlashDiscountPayload) => flashDiscountApi.redeem(discount.id, data),
    onSuccess: (res) => {
      const r = res.data.data
      toast.success(`Redeemed! Discount: ₹${r.discount_amount.toFixed(2)}`)
      qc.invalidateQueries({ queryKey: ['flash-discounts'] })
      onClose()
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? 'Redemption failed')
    },
  })

  const handleRedeem = () => {
    if (!customerPhone.trim()) { toast.error('Enter customer phone'); return }
    mutation.mutate({
      customer_phone: customerPhone.trim(),
      transaction_amount: txAmount ? parseFloat(txAmount) : undefined,
      store_id: discount.store_id ?? undefined,
    })
  }

  return (
    <div className="fixed inset-0 z-[60] flex items-end">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />
      <div className="relative w-full bg-white rounded-t-3xl pb-safe">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h2 className="font-semibold text-gray-800">Redeem Flash Discount</h2>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
            <X size={18} className="text-gray-500" />
          </button>
        </div>

        <div className="px-5 py-4 space-y-4">
          <div className="bg-gradient-to-r from-orange-500 to-rose-500 rounded-xl px-4 py-3 text-center">
            <p className="text-white font-bold text-lg">{parseFloat(discount.discount_percentage).toFixed(0)}% OFF</p>
            <p className="text-white/80 text-sm">{discount.title}</p>
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Customer Phone *</label>
            <input
              value={customerPhone}
              onChange={(e) => setCustomerPhone(e.target.value)}
              placeholder="10-digit mobile number"
              type="tel"
              inputMode="numeric"
              className="input-field"
            />
          </div>

          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Transaction Amount (₹) — optional</label>
            <input
              value={txAmount}
              onChange={(e) => setTxAmount(e.target.value)}
              placeholder="e.g. 500"
              type="number"
              min="0"
              className="input-field"
            />
          </div>

          <button
            onClick={handleRedeem}
            disabled={mutation.isPending || !customerPhone.trim()}
            className="btn-primary w-full"
          >
            {mutation.isPending ? 'Processing…' : '✓ Confirm Redemption'}
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Discount Card ─────────────────────────────────────────────────────────────

function DiscountCard({
  item,
  onEdit,
  onDelete,
  onRedeem,
}: {
  item: FlashDiscount
  onEdit: () => void
  onDelete: () => void
  onRedeem: () => void
}) {
  const now       = new Date()
  const validUntil = item.valid_until ? new Date(item.valid_until) : null
  const timeLeft   = validUntil && validUntil > now
    ? Math.round((validUntil.getTime() - now.getTime()) / 60000)
    : null

  const timeLabel = timeLeft !== null
    ? timeLeft < 60 ? `${timeLeft}m left`
      : timeLeft < 1440 ? `${Math.floor(timeLeft / 60)}h left`
      : `${Math.floor(timeLeft / 1440)}d left`
    : null

  return (
    <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div className="bg-gradient-to-r from-orange-500 to-rose-500 px-4 py-3 flex items-center justify-between">
        <span className="text-white font-bold text-xl">{parseFloat(item.discount_percentage).toFixed(0)}% OFF</span>
        <div className="flex items-center gap-2">
          {timeLabel && <span className="text-white/80 text-xs">{timeLabel}</span>}
          <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${STATUS_STYLE[item.status]}`}>
            {item.status.toUpperCase()}
          </span>
        </div>
      </div>
      {item.banner_image && (
        <img src={getImageUrl(item.banner_image)} alt={item.title} className="w-full h-36 object-cover" />
      )}
      <div className="px-4 py-3">
        <p className="font-semibold text-gray-800 text-sm">{item.title}</p>
        {item.description && <p className="text-xs text-gray-500 mt-0.5">{item.description}</p>}
        <div className="flex items-center gap-3 mt-2 text-xs text-gray-400">
          {item.store_name && (
            <span className="flex items-center gap-1"><StoreIcon size={10} />{item.store_name}</span>
          )}
          {item.max_redemptions && (
            <span>{item.current_redemptions}/{item.max_redemptions} used</span>
          )}
        </div>
        <div className="flex gap-2 mt-3">
          {item.status === 'active' && (
            <button onClick={onRedeem} className="flex-1 flex items-center justify-center gap-1 py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-medium text-emerald-600 hover:bg-emerald-100">
              <Zap size={12} /> Redeem
            </button>
          )}
          <button onClick={onEdit} className="flex-1 flex items-center justify-center gap-1 py-2 border border-gray-200 rounded-xl text-xs font-medium text-gray-600 hover:bg-gray-50">
            <Edit2 size={12} /> Edit
          </button>
          <button onClick={onDelete} className="flex-1 flex items-center justify-center gap-1 py-2 border border-red-100 rounded-xl text-xs font-medium text-red-500 hover:bg-red-50">
            <Trash2 size={12} /> Delete
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function FlashDiscountPage() {
  const navigate = useNavigate()
  const qc       = useQueryClient()
  const [showModal, setModal]   = useState(false)
  const [editing, setEditing]   = useState<FlashDiscount | null>(null)
  const [redeeming, setRedeeming] = useState<FlashDiscount | null>(null)
  const isStoreAdmin  = useAuthStore(s => s.isStoreAdmin())
  const scopedStoreId = useAuthStore(s => s.scopedStoreId())

  const { data: discounts = [], isLoading } = useQuery({
    queryKey: ['flash-discounts', scopedStoreId],
    queryFn:  () => {
      const params = isStoreAdmin && scopedStoreId ? { store_id: scopedStoreId } : undefined
      return flashDiscountApi.list(params).then(r => r.data.data)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: flashDiscountApi.remove,
    onSuccess: () => { toast.success('Deleted'); qc.invalidateQueries({ queryKey: ['flash-discounts'] }) },
    onError: () => toast.error('Failed to delete'),
  })

  const openCreate = () => { setEditing(null); setModal(true) }
  const openEdit   = (item: FlashDiscount) => { setEditing(item); setModal(true) }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-xl">Flash Discounts</h1>
            <p className="text-white/70 text-xs mt-0.5">Time-limited offers for customers</p>
          </div>
          <button onClick={openCreate} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30">
            <Plus size={20} className="text-white" />
          </button>
        </div>
      </div>

      <div className="px-4 py-4 space-y-3">
        {isLoading ? (
          Array.from({ length: 3 }).map((_, i) => <div key={i} className="bg-white rounded-2xl h-32 animate-pulse" />)
        ) : discounts.length === 0 ? (
          <div className="py-20 text-center">
            <Zap size={40} className="text-gray-300 mx-auto mb-3" />
            <p className="text-gray-500 font-medium">No flash discounts yet</p>
            <p className="text-gray-400 text-sm mt-1">Create a time-limited offer to attract customers</p>
            <button onClick={openCreate} className="btn-primary mt-4 inline-flex items-center gap-2">
              <Plus size={14} /> Create Flash Discount
            </button>
          </div>
        ) : (
          discounts.map(d => (
            <DiscountCard
              key={d.id}
              item={d}
              onEdit={() => openEdit(d)}
              onDelete={() => window.confirm('Delete this flash discount?') && deleteMutation.mutate(d.id)}
              onRedeem={() => setRedeeming(d)}
            />
          ))
        )}
        <div className="h-4" />
      </div>

      {showModal && (
        <FlashDiscountFormModal editing={editing} onClose={() => setModal(false)} />
      )}

      {redeeming && (
        <RedeemFlashDiscountModal discount={redeeming} onClose={() => setRedeeming(null)} />
      )}
    </div>
  )
}
