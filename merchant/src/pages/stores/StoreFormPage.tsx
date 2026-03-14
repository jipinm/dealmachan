import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm, Controller, useFieldArray } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ChevronLeft, Save } from 'lucide-react'
import toast from 'react-hot-toast'
import { useEffect, useState, useMemo } from 'react'
import { storeApi, type CreateStorePayload, type SubCategory } from '@/api/endpoints/stores'
import { merchantApi } from '@/api/endpoints/merchant'

// ── Working hours helpers ─────────────────────────────────────────────────────

const DAYS = [
  { key: 'mon', label: 'Monday' },
  { key: 'tue', label: 'Tuesday' },
  { key: 'wed', label: 'Wednesday' },
  { key: 'thu', label: 'Thursday' },
  { key: 'fri', label: 'Friday' },
  { key: 'sat', label: 'Saturday' },
  { key: 'sun', label: 'Sunday' },
] as const

const dayHoursSchema = z.object({
  day:   z.string(),
  label: z.string(),
  open:  z.boolean(),
  from:  z.string(),
  to:    z.string(),
})

function defaultDayHours() {
  return DAYS.map((d) => ({
    day: d.key,
    label: d.label,
    open: true,
    from: '09:00',
    to: '18:00',
  }))
}

/** Serialize form day rows → JSON for API */
function serializeHours(rows: z.infer<typeof dayHoursSchema>[]): Record<string, string> {
  const result: Record<string, string> = {}
  for (const r of rows) {
    result[r.day] = r.open ? `${r.from}-${r.to}` : 'Closed'
  }
  return result
}

/** Deserialize JSON from API → form day rows (handles both string and legacy object format) */
function deserializeHours(json: Record<string, string | { open: string; close: string; closed: boolean }> | null | undefined) {
  return DAYS.map((d) => {
    const val = json?.[d.key]
    if (!val) return { day: d.key, label: d.label, open: false, from: '09:00', to: '18:00' }
    if (typeof val === 'object') {
      if (val.closed) return { day: d.key, label: d.label, open: false, from: '09:00', to: '18:00' }
      return { day: d.key, label: d.label, open: true, from: val.open ?? '09:00', to: val.close ?? '18:00' }
    }
    if (val === 'Closed') return { day: d.key, label: d.label, open: false, from: '09:00', to: '18:00' }
    const [from, to] = val.split('-')
    return { day: d.key, label: d.label, open: true, from: from ?? '09:00', to: to ?? '18:00' }
  })
}

const schema = z.object({
  store_name:   z.string().min(2, 'Min 2 characters'),
  address:      z.string().min(5, 'Enter a valid address'),
  city_id:      z.coerce.number().min(1, 'Select a city'),
  area_id:      z.coerce.number().optional().nullable(),
  phone:        z.string().regex(/^[0-9]{10}$/, '10-digit number').optional().or(z.literal('')),
  email:        z.string().email('Invalid email').optional().or(z.literal('')),
  description:  z.string().optional().or(z.literal('')),
  hours:        z.array(dayHoursSchema),
  category_ids: z.array(z.number()).min(1, 'Select at least one category'),
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

  const [subCategories, setSubCategories] = useState<SubCategory[]>([])

  const { data: cities = [] } = useQuery({
    queryKey: ['cities'],
    queryFn:  () => storeApi.getCities().then((r) => r.data.data),
    staleTime: Infinity,
  })

  const { data: merchantProfileData } = useQuery({
    queryKey: ['merchant-profile'],
    queryFn:  () => merchantApi.getProfile().then((r) => r.data.data),
    staleTime: 5 * 60 * 1000,
  })

  const categories = useMemo(() => {
    const cats = merchantProfileData?.profile?.categories ?? []
    const seen = new Set<number>()
    return cats
      .filter((c) => {
        if (seen.has(c.category_id)) return false
        seen.add(c.category_id)
        return true
      })
      .map((c) => ({ id: c.category_id, name: c.category_name ?? '' }))
  }, [merchantProfileData])

  const {
    register, handleSubmit, watch, reset, control,
    formState: { errors, isDirty },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { hours: defaultDayHours(), category_ids: [] },
  })

  const { fields: hourFields } = useFieldArray({ control, name: 'hours' })

  const selectedCity = watch('city_id')
  const selectedCategoryIds = watch('category_ids')

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
      const catIds = (existingStore.categories ?? []).map((c) => c.category_id)
      reset({
        store_name:   existingStore.store_name,
        address:      existingStore.address,
        city_id:      existingStore.city_id,
        area_id:      existingStore.area_id ?? null,
        phone:        existingStore.phone ?? '',
        email:        existingStore.email ?? '',
        description:  existingStore.description ?? '',
        hours:        deserializeHours(existingStore.opening_hours),
        category_ids: catIds,
      })
      // Load sub-categories for pre-selected categories
      if (catIds.length > 0) {
        Promise.all(catIds.map((cId) => storeApi.getSubCategories(cId).then((r) => r.data.data)))
          .then((results) => setSubCategories(results.flat()))
          .catch(() => {})
      }
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
      store_name:    values.store_name,
      address:       values.address,
      city_id:       Number(values.city_id),
      area_id:       values.area_id ? Number(values.area_id) : null,
      phone:         values.phone || undefined,
      email:         values.email || undefined,
      description:   values.description || undefined,
      opening_hours: serializeHours(values.hours),
      category_ids:  values.category_ids,
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

          {/* Categories */}
          <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Categories</p>
            <Field label="Category" error={errors.category_ids?.message} required>
              {categories.length === 0 ? (
                <p className="text-sm text-amber-600 bg-amber-50 rounded-xl p-3">
                  No categories are assigned to your merchant profile yet. Please add them in{' '}
                  <button type="button" onClick={() => navigate('/profile/edit')} className="font-semibold underline">
                    Edit Profile
                  </button>{' '}
                  before adding a store.
                </p>
              ) : (
                <Controller
                  name="category_ids"
                  control={control}
                  render={({ field }) => (
                    <div className="space-y-2">
                      {categories.map((cat) => {
                        const checked = field.value?.includes(cat.id) ?? false
                        return (
                          <label key={cat.id} className="flex items-center gap-2 cursor-pointer">
                            <input
                              type="checkbox"
                              checked={checked}
                              onChange={async (e) => {
                                const next = e.target.checked
                                  ? [...(field.value ?? []), cat.id]
                                  : (field.value ?? []).filter((id) => id !== cat.id)
                                field.onChange(next)
                                // Load/remove sub-categories for this category
                                if (e.target.checked) {
                                  try {
                                    const res = await storeApi.getSubCategories(cat.id)
                                    setSubCategories((prev) => [
                                      ...prev.filter((s) => s.category_id !== cat.id),
                                      ...res.data.data,
                                    ])
                                  } catch {}
                                } else {
                                  setSubCategories((prev) => prev.filter((s) => s.category_id !== cat.id))
                                }
                              }}
                              className="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                            />
                            <span className="text-sm text-gray-700">{cat.name}</span>
                          </label>
                        )
                      })}
                    </div>
                  )}
                />
              )}
            </Field>
            {subCategories.length > 0 && (
              <Field label="Sub-category (optional)">
                <div className="space-y-2">
                  {subCategories
                    .filter((s) => selectedCategoryIds?.includes(s.category_id))
                    .map((sub) => (
                      <label key={sub.id} className="flex items-center gap-2 cursor-pointer">
                        <input
                          type="checkbox"
                          defaultChecked={
                            existingStore?.categories?.some((c) => c.sub_category_id === sub.id) ?? false
                          }
                          className="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                        />
                        <span className="text-sm text-gray-700">{sub.name}</span>
                      </label>
                    ))}
                </div>
              </Field>
            )}
          </div>

          {/* Contact */}
          <div className="bg-white rounded-2xl p-4 shadow-sm space-y-4">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Contact</p>            <Field label="Phone" error={errors.phone?.message}>
              <input {...register('phone')} type="tel" placeholder="10-digit mobile" maxLength={10} className={inputCls} />
            </Field>
            <Field label="Email" error={errors.email?.message}>
              <input {...register('email')} type="email" placeholder="store@email.com" className={inputCls} />
            </Field>
          </div>

          {/* Working Hours */}
          <div className="bg-white rounded-2xl p-4 shadow-sm space-y-3">
            <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Working Hours</p>
            {hourFields.map((field, idx) => {
              const isOpen = watch(`hours.${idx}.open`)
              return (
                <div key={field.id} className="flex items-center gap-2">
                  <label className="flex items-center gap-1.5 w-24 shrink-0">
                    <input
                      type="checkbox"
                      {...register(`hours.${idx}.open`)}
                      className="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                    />
                    <span className="text-xs font-semibold text-gray-700">{field.label.slice(0, 3)}</span>
                  </label>
                  {isOpen ? (
                    <div className="flex items-center gap-1.5 flex-1">
                      <input
                        type="time"
                        {...register(`hours.${idx}.from`)}
                        className="flex-1 px-2 py-1.5 text-xs rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                      />
                      <span className="text-xs text-gray-400">to</span>
                      <input
                        type="time"
                        {...register(`hours.${idx}.to`)}
                        className="flex-1 px-2 py-1.5 text-xs rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                      />
                    </div>
                  ) : (
                    <span className="text-xs text-gray-400 italic">Closed</span>
                  )}
                </div>
              )
            })}
          </div>
        </form>
      </div>
    </div>
  )
}
