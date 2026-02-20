import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ChevronLeft, Save } from 'lucide-react'
import toast from 'react-hot-toast'
import { useEffect } from 'react'
import { storeApi, type CreateStorePayload } from '@/api/endpoints/stores'

const schema = z.object({
  store_name:  z.string().min(2, 'Min 2 characters'),
  address:     z.string().min(5, 'Enter a valid address'),
  city_id:     z.coerce.number().min(1, 'Select a city'),
  area_id:     z.coerce.number().optional().nullable(),
  phone:       z.string().regex(/^[0-9]{10}$/, '10-digit number').optional().or(z.literal('')),
  email:       z.string().email('Invalid email').optional().or(z.literal('')),
  description: z.string().optional().or(z.literal('')),
})

type FormValues = z.infer<typeof schema>

const inputCls = 'w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 bg-white'
const selectCls = inputCls + ' appearance-none'

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

export default function StoreFormPage() {
  const { id }      = useParams<{ id: string }>()
  const isEdit      = !!id
  const navigate    = useNavigate()
  const queryClient = useQueryClient()

  const { data: cities = [] } = useQuery({
    queryKey: ['cities'],
    queryFn:  () => storeApi.getCities().then((r) => r.data.data),
    staleTime: Infinity,
  })

  const {
    register, handleSubmit, watch, reset, control,
    formState: { errors, isDirty },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  const selectedCity = watch('city_id')

  const { data: areas = [] } = useQuery({
    queryKey: ['areas', selectedCity],
    queryFn:  () => storeApi.getAreas(selectedCity ? Number(selectedCity) : undefined).then((r) => r.data.data),
    enabled:  !!selectedCity,
    staleTime: 5 * 60 * 1000,
  })

  const { data: existingStore } = useQuery({
    queryKey: ['store', id],
    queryFn:  () => storeApi.get(Number(id)).then((r) => r.data.data),
    enabled:  isEdit,
    staleTime: 2 * 60 * 1000,
  })

  useEffect(() => {
    if (existingStore) {
      reset({
        store_name:  existingStore.store_name,
        address:     existingStore.address,
        city_id:     existingStore.city_id,
        area_id:     existingStore.area_id ?? null,
        phone:       existingStore.phone ?? '',
        email:       existingStore.email ?? '',
        description: existingStore.description ?? '',
      })
    }
  }, [existingStore, reset])

  const createMutation = useMutation({
    mutationFn: (data: CreateStorePayload) => storeApi.create(data),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['stores'] })
      toast.success('Store created!')
      navigate(`/stores/${res.data.data.id}`, { replace: true })
    },
    onError: () => toast.error('Failed to create store'),
  })

  const updateMutation = useMutation({
    mutationFn: (data: CreateStorePayload) => storeApi.update(Number(id), data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stores'] })
      queryClient.invalidateQueries({ queryKey: ['store', id] })
      toast.success('Store updated!')
      navigate(-1)
    },
    onError: () => toast.error('Failed to update store'),
  })

  const isPending = createMutation.isPending || updateMutation.isPending

  const onSubmit = (values: FormValues) => {
    const payload: CreateStorePayload = {
      store_name:  values.store_name,
      address:     values.address,
      city_id:     Number(values.city_id),
      area_id:     values.area_id ? Number(values.area_id) : null,
      phone:       values.phone || undefined,
      email:       values.email || undefined,
      description: values.description || undefined,
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
        <h1 className="text-white font-bold text-lg flex-1">{isEdit ? 'Edit Store' : 'Add Store'}</h1>
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
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Basic Info</p>
            <Field label="Store Name" error={errors.store_name?.message} required>
              <input {...register('store_name')} placeholder="Branch name or store identifier" className={inputCls} />
            </Field>
            <Field label="Address" error={errors.address?.message} required>
              <textarea {...register('address')} placeholder="Full street address" rows={3} className={`${inputCls} resize-none`} />
            </Field>
            <Field label="Description" error={errors.description?.message}>
              <textarea {...register('description')} placeholder="Brief description of this store (optional)" rows={2} className={`${inputCls} resize-none`} />
            </Field>
          </div>

          {/* Location */}
          <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Location</p>
            <Field label="City" error={errors.city_id?.message} required>
              <Controller
                name="city_id"
                control={control}
                render={({ field }) => (
                  <select {...field} value={field.value ?? ''} onChange={(e) => field.onChange(Number(e.target.value))} className={selectCls}>
                    <option value="">Select city…</option>
                    {cities.map((c) => <option key={c.id} value={c.id}>{c.city_name}</option>)}
                  </select>
                )}
              />
            </Field>
            <Field label="Area" error={errors.area_id?.message}>
              <Controller
                name="area_id"
                control={control}
                render={({ field }) => (
                  <select {...field} value={field.value ?? ''} onChange={(e) => field.onChange(e.target.value ? Number(e.target.value) : null)} className={selectCls} disabled={!selectedCity}>
                    <option value="">Select area (optional)…</option>
                    {areas.map((a) => <option key={a.id} value={a.id}>{a.area_name}</option>)}
                  </select>
                )}
              />
            </Field>
          </div>

          {/* Contact */}
          <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Contact</p>
            <Field label="Phone" error={errors.phone?.message}>
              <input {...register('phone')} type="tel" placeholder="10-digit mobile" maxLength={10} className={inputCls} />
            </Field>
            <Field label="Email" error={errors.email?.message}>
              <input {...register('email')} type="email" placeholder="store@email.com" className={inputCls} />
            </Field>
          </div>
        </form>
      </div>
    </div>
  )
}
