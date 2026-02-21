import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ChevronLeft, Save } from 'lucide-react'
import toast from 'react-hot-toast'
import { useEffect } from 'react'
import {
  storeCouponApi,
  type CreateStoreCouponPayload,
} from '@/api/endpoints/storeCoupons'
import { storeApi } from '@/api/endpoints/stores'

const schema = z.object({
  title:               z.string().min(1, 'Title required'),
  description:         z.string().optional(),
  terms_conditions:    z.string().optional(),
  discount_type:       z.enum(['percentage', 'fixed']),
  discount_value:      z.string().refine(v => !isNaN(parseFloat(v)) && parseFloat(v) > 0, 'Must be > 0'),
  min_purchase_amount: z.string().optional(),
  max_discount_amount: z.string().optional(),
  store_id:            z.string().optional(),
  valid_from:          z.string().optional(),
  valid_until:         z.string().optional(),
})

type FormData = z.infer<typeof schema>

const inputCls = 'w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 bg-white'

function Field({ label, error, required, children }: { label: string; error?: string; required?: boolean; children: React.ReactNode }) {
  return (
    <div>
      <label className="block text-xs font-semibold text-gray-600 mb-1.5">
        {label}{required && <span className="text-red-500 ml-0.5">*</span>}
      </label>
      {children}
      {error && <p className="text-red-500 text-[11px] mt-1">{error}</p>}
    </div>
  )
}

export default function StoreCouponFormPage() {
  const { id }      = useParams<{ id: string }>()
  const isEdit      = !!id
  const navigate    = useNavigate()
  const queryClient = useQueryClient()

  const { data: stores = [] } = useQuery({
    queryKey: ['stores'],
    queryFn:  () => storeApi.list().then(r => r.data.data),
  })

  const {
    register, handleSubmit, reset, control,
    formState: { errors, isDirty },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { discount_type: 'percentage' },
  })

  const { data: existingCoupon } = useQuery({
    queryKey: ['store-coupon', id],
    queryFn:  () => storeCouponApi.get(Number(id)).then(r => r.data.data),
    enabled:  isEdit,
  })

  useEffect(() => {
    if (existingCoupon) {
      reset({
        title:               existingCoupon.title,
        description:         existingCoupon.description ?? '',
        terms_conditions:    existingCoupon.terms_conditions ?? '',
        discount_type:       existingCoupon.discount_type,
        discount_value:      existingCoupon.discount_value,
        min_purchase_amount: existingCoupon.min_purchase_amount ?? '',
        max_discount_amount: existingCoupon.max_discount_amount ?? '',
        store_id:            existingCoupon.store_id ? String(existingCoupon.store_id) : '',
        valid_from:          existingCoupon.valid_from?.slice(0, 16) ?? '',
        valid_until:         existingCoupon.valid_until?.slice(0, 16) ?? '',
      })
    }
  }, [existingCoupon, reset])

  const createMutation = useMutation({
    mutationFn: (data: CreateStoreCouponPayload) => storeCouponApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['store-coupons'] })
      toast.success('Store coupon created!')
      navigate('/store-coupons', { replace: true })
    },
    onError: () => toast.error('Failed to create store coupon'),
  })

  const updateMutation = useMutation({
    mutationFn: (data: Partial<CreateStoreCouponPayload>) => storeCouponApi.update(Number(id), data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['store-coupons'] })
      queryClient.invalidateQueries({ queryKey: ['store-coupon', id] })
      toast.success('Store coupon updated!')
      navigate(-1)
    },
    onError: () => toast.error('Failed to update'),
  })

  const isPending = createMutation.isPending || updateMutation.isPending

  const onSubmit = (values: FormData) => {
    const payload: CreateStoreCouponPayload = {
      title:               values.title,
      description:         values.description || undefined,
      terms_conditions:    values.terms_conditions || undefined,
      discount_type:       values.discount_type,
      discount_value:      parseFloat(values.discount_value),
      min_purchase_amount: values.min_purchase_amount ? parseFloat(values.min_purchase_amount) : undefined,
      max_discount_amount: values.max_discount_amount ? parseFloat(values.max_discount_amount) : undefined,
      store_id:            values.store_id ? parseInt(values.store_id) : undefined,
      valid_from:          values.valid_from || undefined,
      valid_until:         values.valid_until || undefined,
    }
    if (isEdit) updateMutation.mutate(payload)
    else        createMutation.mutate(payload)
  }

  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-4 pt-12 pb-5 flex items-center gap-3 fixed top-0 inset-x-0 z-30">
        <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
          <ChevronLeft size={20} className="text-white" />
        </button>
        <h1 className="text-white font-bold text-lg flex-1">{isEdit ? 'Edit Store Coupon' : 'New Store Coupon'}</h1>
        <button
          onClick={handleSubmit(onSubmit)}
          disabled={isPending || (isEdit && !isDirty)}
          className="h-9 px-4 bg-white text-brand-700 text-sm font-bold rounded-full flex items-center gap-1.5 disabled:opacity-50"
        >
          <Save size={14} />{isPending ? 'Saving…' : 'Save'}
        </button>
      </div>

      <div className="pt-24 pb-10 px-4">
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {/* Basic Info */}
          <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Coupon Details</p>
            <Field label="Title" error={errors.title?.message} required>
              <input {...register('title')} placeholder="e.g. 10% off on first visit" className={inputCls} />
            </Field>
            <Field label="Description" error={errors.description?.message}>
              <textarea {...register('description')} placeholder="Optional description…" rows={2} className={`${inputCls} resize-none`} />
            </Field>
            <Field label="Terms & Conditions" error={errors.terms_conditions?.message}>
              <textarea {...register('terms_conditions')} placeholder="e.g. Valid only for dine-in…" rows={2} className={`${inputCls} resize-none`} />
            </Field>
          </div>

          {/* Discount */}
          <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Discount</p>
            <Field label="Discount Type" error={errors.discount_type?.message} required>
              <Controller
                name="discount_type"
                control={control}
                render={({ field }) => (
                  <select {...field} className={inputCls}>
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount (₹)</option>
                  </select>
                )}
              />
            </Field>
            <Field label="Discount Value" error={errors.discount_value?.message} required>
              <input {...register('discount_value')} type="number" step="0.01" min="0.01" placeholder="e.g. 10" className={inputCls} />
            </Field>
            <div className="grid grid-cols-2 gap-3">
              <Field label="Min Purchase (₹)" error={errors.min_purchase_amount?.message}>
                <input {...register('min_purchase_amount')} type="number" min="0" placeholder="0" className={inputCls} />
              </Field>
              <Field label="Max Discount (₹)" error={errors.max_discount_amount?.message}>
                <input {...register('max_discount_amount')} type="number" min="0" placeholder="No limit" className={inputCls} />
              </Field>
            </div>
          </div>

          {/* Store & Validity */}
          <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Store & Validity</p>
            <Field label="Store (optional)">
              <select {...register('store_id')} className={inputCls}>
                <option value="">All stores</option>
                {stores.map(s => <option key={s.id} value={s.id}>{s.store_name}</option>)}
              </select>
            </Field>
            <div className="grid grid-cols-2 gap-3">
              <Field label="Valid From" error={errors.valid_from?.message}>
                <input {...register('valid_from')} type="datetime-local" className={`${inputCls} text-sm`} />
              </Field>
              <Field label="Valid Until" error={errors.valid_until?.message}>
                <input {...register('valid_until')} type="datetime-local" className={`${inputCls} text-sm`} />
              </Field>
            </div>
          </div>
        </form>
      </div>
    </div>
  )
}
