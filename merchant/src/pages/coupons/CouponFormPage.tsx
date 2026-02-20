import { useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Save } from 'lucide-react'
import toast from 'react-hot-toast'
import { couponApi, type CreateCouponPayload } from '@/api/endpoints/coupons'
import { storeApi } from '@/api/endpoints/stores'

const schema = z.object({
  title:               z.string().min(2, 'Min 2 characters'),
  description:         z.string().optional(),
  coupon_code:         z.string().optional(),
  discount_type:       z.enum(['percentage', 'fixed']),
  discount_value:      z.coerce.number().positive('Must be > 0'),
  min_purchase_amount: z.coerce.number().min(0).nullable().optional(),
  max_discount_amount: z.coerce.number().min(0).nullable().optional(),
  store_id:            z.coerce.number().nullable().optional(),
  valid_from:          z.string().optional(),
  valid_until:         z.string().optional(),
  usage_limit:         z.coerce.number().int().positive().nullable().optional(),
  terms_conditions:    z.string().optional(),
})

type FormValues = z.infer<typeof schema>

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="block text-xs font-semibold text-gray-600 mb-1">{label}</label>
      {children}
      {error && <p className="text-xs text-red-500 mt-0.5">{error}</p>}
    </div>
  )
}

const inputCls = 'w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 bg-white'

export default function CouponFormPage() {
  const navigate     = useNavigate()
  const { id }       = useParams<{ id: string }>()
  const isEdit       = Boolean(id)
  const qc           = useQueryClient()

  const { register, handleSubmit, control, watch, reset, formState: { errors } } =
    useForm<FormValues>({
      resolver: zodResolver(schema),
      defaultValues: { discount_type: 'percentage' },
    })

  const discountType = watch('discount_type')

  // Fetch existing coupon for edit
  const { data: existingData } = useQuery({
    queryKey: ['coupon', id],
    queryFn: async () => (await couponApi.get(Number(id))).data.data,
    enabled: isEdit,
  })

  useEffect(() => {
    if (existingData) {
      reset({
        title:               existingData.title,
        description:         existingData.description ?? '',
        coupon_code:         existingData.coupon_code,
        discount_type:       existingData.discount_type,
        discount_value:      parseFloat(existingData.discount_value),
        min_purchase_amount: existingData.min_purchase_amount ? parseFloat(existingData.min_purchase_amount) : null,
        max_discount_amount: existingData.max_discount_amount ? parseFloat(existingData.max_discount_amount) : null,
        store_id:            existingData.store_id,
        valid_from:          existingData.valid_from ? existingData.valid_from.slice(0, 16) : '',
        valid_until:         existingData.valid_until ? existingData.valid_until.slice(0, 16) : '',
        usage_limit:         existingData.usage_limit,
        terms_conditions:    existingData.terms_conditions ?? '',
      })
    }
  }, [existingData, reset])

  // Fetch stores for selector
  const { data: storesData } = useQuery({
    queryKey: ['stores'],
    queryFn: async () => (await storeApi.list()).data.data,
  })

  const mutation = useMutation({
    mutationFn: async (values: FormValues) => {
      const payload: CreateCouponPayload = {
        ...values,
        discount_value:      values.discount_value,
        min_purchase_amount: values.min_purchase_amount ?? null,
        max_discount_amount: values.max_discount_amount ?? null,
        store_id:            values.store_id ?? null,
        valid_from:          values.valid_from || null,
        valid_until:         values.valid_until || null,
        usage_limit:         values.usage_limit ?? null,
      }
      if (isEdit) {
        const res = await couponApi.update(Number(id), payload)
        return res.data
      }
      const res = await couponApi.create(payload)
      return res.data
    },
    onSuccess: (data) => {
      toast.success(data.message ?? (isEdit ? 'Coupon updated!' : 'Coupon created!'))
      qc.invalidateQueries({ queryKey: ['coupons'] })
      if (isEdit) {
        navigate(`/coupons/${id}`, { replace: true })
      } else {
        navigate(`/coupons/${data.data.id}`, { replace: true })
      }
    },
    onError: (e: any) => {
      toast.error(e?.response?.data?.message ?? 'Something went wrong')
    },
  })

  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6 flex items-center gap-3">
        <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30">
          <ChevronLeft size={20} className="text-white" />
        </button>
        <h1 className="text-white font-bold text-xl flex-1">
          {isEdit ? 'Edit Coupon' : 'New Coupon'}
        </h1>
        <button
          onClick={handleSubmit((v) => mutation.mutate(v))}
          disabled={mutation.isPending}
          className="flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-sm font-semibold px-3 py-1.5 rounded-xl transition-colors disabled:opacity-50"
        >
          <Save size={15} />
          {mutation.isPending ? 'Saving…' : 'Save'}
        </button>
      </div>

      <form onSubmit={handleSubmit((v) => mutation.mutate(v))} className="px-4 py-4 space-y-4">
        {/* Basic info */}
        <div className="bg-white rounded-2xl p-4 space-y-4 shadow-sm">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest">Basic Info</p>

          <Field label="Title *" error={errors.title?.message}>
            <input {...register('title')} placeholder="e.g. Flat 20% off on all items" className={inputCls} />
          </Field>

          <Field label="Description" error={errors.description?.message}>
            <textarea {...register('description')} rows={2} placeholder="Short description (optional)" className={inputCls} />
          </Field>

          <Field label="Custom Code (optional — auto-generated if empty)" error={errors.coupon_code?.message}>
            <input {...register('coupon_code')} placeholder="e.g. SAVE20" className={`${inputCls} uppercase`}
              onChange={(e) => { e.target.value = e.target.value.toUpperCase() }} />
          </Field>
        </div>

        {/* Discount */}
        <div className="bg-white rounded-2xl p-4 space-y-4 shadow-sm">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest">Discount</p>

          <Field label="Discount Type *" error={errors.discount_type?.message}>
            <Controller
              control={control}
              name="discount_type"
              render={({ field }) => (
                <div className="flex gap-2">
                  {(['percentage', 'fixed'] as const).map((t) => (
                    <button
                      key={t}
                      type="button"
                      onClick={() => field.onChange(t)}
                      className={`flex-1 py-2 rounded-xl text-sm font-semibold border-2 transition-colors ${
                        field.value === t
                          ? 'border-brand-600 bg-brand-50 text-brand-700'
                          : 'border-gray-200 text-gray-500'
                      }`}
                    >
                      {t === 'percentage' ? '% Percentage' : '₹ Fixed Amount'}
                    </button>
                  ))}
                </div>
              )}
            />
          </Field>

          <Field
            label={discountType === 'percentage' ? 'Discount % *' : 'Discount Amount (₹) *'}
            error={errors.discount_value?.message}
          >
            <input {...register('discount_value')} type="number" step="0.01" min="0"
              placeholder={discountType === 'percentage' ? '0–100' : 'e.g. 50'}
              className={inputCls} />
          </Field>

          <div className="grid grid-cols-2 gap-3">
            <Field label="Min Purchase (₹)" error={errors.min_purchase_amount?.message}>
              <input {...register('min_purchase_amount')} type="number" step="0.01" min="0"
                placeholder="Optional" className={inputCls} />
            </Field>
            {discountType === 'percentage' && (
              <Field label="Max Discount (₹)" error={errors.max_discount_amount?.message}>
                <input {...register('max_discount_amount')} type="number" step="0.01" min="0"
                  placeholder="Optional cap" className={inputCls} />
              </Field>
            )}
          </div>
        </div>

        {/* Validity */}
        <div className="bg-white rounded-2xl p-4 space-y-4 shadow-sm">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest">Validity & Limits</p>

          <div className="grid grid-cols-2 gap-3">
            <Field label="Valid From" error={errors.valid_from?.message}>
              <input {...register('valid_from')} type="datetime-local" className={inputCls} />
            </Field>
            <Field label="Valid Until" error={errors.valid_until?.message}>
              <input {...register('valid_until')} type="datetime-local" className={inputCls} />
            </Field>
          </div>

          <Field label="Usage Limit (leave blank for unlimited)" error={errors.usage_limit?.message}>
            <input {...register('usage_limit')} type="number" min="1" placeholder="e.g. 100" className={inputCls} />
          </Field>
        </div>

        {/* Store */}
        <div className="bg-white rounded-2xl p-4 space-y-4 shadow-sm">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest">Store Link</p>
          <Field label="Apply to Store (optional — blank = all stores)" error={errors.store_id?.message}>
            <Controller
              control={control}
              name="store_id"
              render={({ field }) => (
                <select
                  value={field.value ?? ''}
                  onChange={(e) => field.onChange(e.target.value ? Number(e.target.value) : null)}
                  className={inputCls}
                >
                  <option value="">All Stores</option>
                  {(storesData ?? []).map((s) => (
                    <option key={s.id} value={s.id}>{s.store_name}</option>
                  ))}
                </select>
              )}
            />
          </Field>
        </div>

        {/* Terms */}
        <div className="bg-white rounded-2xl p-4 space-y-4 shadow-sm">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-widest">Terms & Conditions</p>
          <Field label="" error={errors.terms_conditions?.message}>
            <textarea {...register('terms_conditions')} rows={3}
              placeholder="Any restrictions or conditions…" className={inputCls} />
          </Field>
        </div>

        <button
          type="submit"
          disabled={mutation.isPending}
          className="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-4 rounded-2xl transition-colors disabled:opacity-50"
        >
          {mutation.isPending ? 'Saving…' : isEdit ? 'Save Changes' : 'Create Coupon'}
        </button>
      </form>
    </div>
  )
}
