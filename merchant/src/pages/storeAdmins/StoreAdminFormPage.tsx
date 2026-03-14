import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ChevronLeft, UserCog, Store, AlertTriangle, KeyRound, ChevronDown } from 'lucide-react'
import toast from 'react-hot-toast'
import { storeAdminApi } from '@/api/endpoints/storeAdmins'
import { storeApi } from '@/api/endpoints/stores'

// ── Shared helpers ────────────────────────────────────────────────────────────

function FormField({
  label, error, required, children,
}: { label: string; error?: string; required?: boolean; children: React.ReactNode }) {
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

const inputClass =
  'w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:outline-none ' +
  'focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 bg-white placeholder-gray-300'

const readonlyClass =
  'w-full px-3 py-2.5 text-sm rounded-xl border border-gray-100 bg-gray-50 text-gray-500'

// ── Schemas ───────────────────────────────────────────────────────────────────

const createSchema = z
  .object({
    name:             z.string().min(2, 'Name must be at least 2 characters'),
    email:            z.string().email('Enter a valid email address'),
    phone:            z.string().optional(),
    store_id:         z.coerce.number({ invalid_type_error: 'Please select a store' }).positive('Please select a store'),
    password:         z.string().min(8, 'Password must be at least 8 characters'),
    confirm_password: z.string().min(1, 'Please confirm password'),
  })
  .refine((d) => d.password === d.confirm_password, {
    message: 'Passwords do not match',
    path: ['confirm_password'],
  })

const editSchema = z.object({
  name:  z.string().min(2, 'Name must be at least 2 characters'),
  phone: z.string().optional(),
})

const resetPwSchema = z
  .object({
    new_password:     z.string().min(8, 'Password must be at least 8 characters'),
    confirm_password: z.string().min(1, 'Please confirm password'),
  })
  .refine((d) => d.new_password === d.confirm_password, {
    message: 'Passwords do not match',
    path: ['confirm_password'],
  })

type CreateValues  = z.infer<typeof createSchema>
type EditValues    = z.infer<typeof editSchema>
type ResetPwValues = z.infer<typeof resetPwSchema>

// ── Create sub-page ───────────────────────────────────────────────────────────

function CreateMode() {
  const navigate   = useNavigate()
  const queryClient = useQueryClient()

  const { data: stores = [] } = useQuery({
    queryKey: ['stores'],
    queryFn:  () => storeApi.list({ status: 'active' }).then((r) => r.data.data),
    staleTime: 5 * 60 * 1000,
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<CreateValues>({ resolver: zodResolver(createSchema) })

  const mutation = useMutation({
    mutationFn: (data: CreateValues) =>
      storeAdminApi.create({
        name:     data.name,
        email:    data.email,
        phone:    data.phone || undefined,
        password: data.password,
        store_id: data.store_id,
      }),
    onSuccess: () => {
      toast.success('Store admin created')
      queryClient.invalidateQueries({ queryKey: ['store-admins'] })
      navigate('/store-admins')
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? 'Failed to create store admin')
    },
  })

  return (
    <form onSubmit={handleSubmit((d) => mutation.mutate(d))} className="px-4 -mt-2 space-y-4 pb-8">
      {/* Info card */}
      <div className="bg-white rounded-2xl shadow-sm p-4 flex items-start gap-3">
        <div className="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
          <UserCog size={18} className="text-brand-600" />
        </div>
        <div>
          <p className="text-sm font-semibold text-gray-800">New Store Admin</p>
          <p className="text-xs text-gray-500 mt-0.5">
            Store admins can only access the store they are assigned to.
          </p>
        </div>
      </div>

      {/* Fields */}
      <div className="bg-white rounded-2xl shadow-sm p-4 space-y-4">
        <FormField label="Full Name" required error={errors.name?.message}>
          <input type="text" placeholder="e.g. Rahul Sharma" className={inputClass} {...register('name')} />
        </FormField>

        <FormField label="Email Address" required error={errors.email?.message}>
          <input type="email" placeholder="e.g. rahul@store.com" className={inputClass} {...register('email')} />
        </FormField>

        <FormField label="Phone Number" error={errors.phone?.message}>
          <input type="tel" placeholder="e.g. 9876543210" className={inputClass} {...register('phone')} />
        </FormField>

        <FormField label="Assign to Store" required error={errors.store_id?.message}>
          <select className={inputClass} {...register('store_id')}>
            <option value="">Select store…</option>
            {stores.map((s) => (
              <option key={s.id} value={s.id}>{s.store_name}</option>
            ))}
          </select>
        </FormField>
      </div>

      {/* Password */}
      <div className="bg-white rounded-2xl shadow-sm p-4 space-y-4">
        <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">Set Password</p>

        <FormField label="Password" required error={errors.password?.message}>
          <input type="password" autoComplete="new-password" placeholder="Min. 8 characters" className={inputClass} {...register('password')} />
        </FormField>

        <FormField label="Confirm Password" required error={errors.confirm_password?.message}>
          <input type="password" autoComplete="new-password" placeholder="Repeat password" className={inputClass} {...register('confirm_password')} />
        </FormField>
      </div>

      <button
        type="submit"
        disabled={mutation.isPending}
        className="w-full py-3 gradient-brand text-white text-sm font-bold rounded-xl disabled:opacity-60"
      >
        {mutation.isPending ? 'Creating…' : 'Create Store Admin'}
      </button>
    </form>
  )
}

// ── Edit sub-page ─────────────────────────────────────────────────────────────

function EditMode({ adminId }: { adminId: number }) {
  const navigate    = useNavigate()
  const queryClient = useQueryClient()
  const [showReset, setShowReset] = useState(false)

  const { data: admins, isLoading } = useQuery({
    queryKey: ['store-admins'],
    queryFn:  () => storeAdminApi.list().then((r) => r.data.data),
    staleTime: 2 * 60 * 1000,
  })

  const admin = admins?.find((a) => a.id === adminId)

  const {
    register: regEdit,
    handleSubmit: handleEdit,
    formState: { errors: editErrors },
  } = useForm<EditValues>({
    resolver: zodResolver(editSchema),
    values:   admin ? { name: admin.name, phone: admin.phone ?? '' } : undefined,
  })

  const {
    register: regReset,
    handleSubmit: handleReset,
    reset: resetResetForm,
    formState: { errors: resetErrors },
  } = useForm<ResetPwValues>({ resolver: zodResolver(resetPwSchema) })

  const updateMutation = useMutation({
    mutationFn: (data: EditValues) =>
      storeAdminApi.update(adminId, { name: data.name, phone: data.phone || undefined }),
    onSuccess: () => {
      toast.success('Admin updated')
      queryClient.invalidateQueries({ queryKey: ['store-admins'] })
      navigate('/store-admins')
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? 'Failed to update admin')
    },
  })

  const deactivateMutation = useMutation({
    mutationFn: () =>
      admin?.status === 'active'
        ? storeAdminApi.deactivate(adminId)
        : storeAdminApi.update(adminId, { status: 'active' }),
    onSuccess: () => {
      const msg = admin?.status === 'active' ? 'Admin deactivated' : 'Admin activated'
      toast.success(msg)
      queryClient.invalidateQueries({ queryKey: ['store-admins'] })
      navigate('/store-admins')
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? 'Action failed')
    },
  })

  const resetPwMutation = useMutation({
    mutationFn: (data: ResetPwValues) => storeAdminApi.resetPassword(adminId, data.new_password),
    onSuccess: () => {
      toast.success('Password reset successfully')
      resetResetForm()
      setShowReset(false)
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? 'Failed to reset password')
    },
  })

  if (isLoading) {
    return (
      <div className="px-4 pt-6 space-y-3">
        {[1, 2, 3].map((i) => (
          <div key={i} className="bg-white rounded-2xl shadow-sm h-16 animate-pulse" />
        ))}
      </div>
    )
  }

  if (!admin) {
    return (
      <div className="flex flex-col items-center justify-center pt-20 gap-3 text-center px-8">
        <p className="text-gray-500 font-semibold">Admin not found</p>
        <button onClick={() => navigate('/store-admins')} className="text-brand-600 text-sm font-semibold">
          Back to list
        </button>
      </div>
    )
  }

  return (
    <div className="px-4 -mt-2 space-y-4 pb-8">
      {/* Read-only info */}
      <div className="bg-white rounded-2xl shadow-sm p-4 space-y-3">
        <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">Account Info</p>
        <div>
          <p className="text-[11px] text-gray-400 mb-1">Email</p>
          <div className={readonlyClass}>{admin.email}</div>
        </div>
        {admin.store_name && (
          <div>
            <p className="text-[11px] text-gray-400 mb-1">Assigned Store</p>
            <div className={`${readonlyClass} flex items-center gap-1.5`}>
              <Store size={13} className="text-gray-400" />{admin.store_name}
            </div>
          </div>
        )}
      </div>

      {/* Editable fields */}
      <form onSubmit={handleEdit((d) => updateMutation.mutate(d))} className="bg-white rounded-2xl shadow-sm p-4 space-y-4">
        <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">Edit Details</p>

        <FormField label="Full Name" required error={editErrors.name?.message}>
          <input type="text" className={inputClass} {...regEdit('name')} />
        </FormField>

        <FormField label="Phone Number" error={editErrors.phone?.message}>
          <input type="tel" placeholder="Leave blank to remove" className={inputClass} {...regEdit('phone')} />
        </FormField>

        <button
          type="submit"
          disabled={updateMutation.isPending}
          className="w-full py-2.5 gradient-brand text-white text-sm font-bold rounded-xl disabled:opacity-60"
        >
          {updateMutation.isPending ? 'Saving…' : 'Save Changes'}
        </button>
      </form>

      {/* Reset Password accordion */}
      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        <button
          type="button"
          onClick={() => setShowReset((v) => !v)}
          className="w-full flex items-center gap-3 p-4 text-left"
        >
          <KeyRound size={18} className="text-gray-400" />
          <span className="flex-1 text-sm font-semibold text-gray-700">Reset Password</span>
          <ChevronDown
            size={16}
            className={`text-gray-400 transition-transform ${showReset ? 'rotate-180' : ''}`}
          />
        </button>

        {showReset && (
          <form
            onSubmit={handleReset((d) => resetPwMutation.mutate(d))}
            className="px-4 pb-4 space-y-4 border-t border-gray-50"
          >
            <div className="pt-3" />
            <FormField label="New Password" required error={resetErrors.new_password?.message}>
              <input
                type="password"
                autoComplete="new-password"
                placeholder="Min. 8 characters"
                className={inputClass}
                {...regReset('new_password')}
              />
            </FormField>
            <FormField label="Confirm Password" required error={resetErrors.confirm_password?.message}>
              <input
                type="password"
                autoComplete="new-password"
                placeholder="Repeat password"
                className={inputClass}
                {...regReset('confirm_password')}
              />
            </FormField>
            <button
              type="submit"
              disabled={resetPwMutation.isPending}
              className="w-full py-2.5 bg-gray-800 text-white text-sm font-bold rounded-xl disabled:opacity-60"
            >
              {resetPwMutation.isPending ? 'Resetting…' : 'Reset Password'}
            </button>
          </form>
        )}
      </div>

      {/* Deactivate / Activate */}
      <div className="bg-white rounded-2xl shadow-sm p-4">
        <div className="flex items-start gap-3 mb-4">
          <AlertTriangle size={18} className={admin.status === 'active' ? 'text-red-400' : 'text-green-500'} />
          <div>
            <p className="text-sm font-semibold text-gray-800">
              {admin.status === 'active' ? 'Deactivate Admin' : 'Activate Admin'}
            </p>
            <p className="text-xs text-gray-500 mt-0.5">
              {admin.status === 'active'
                ? 'Deactivating will prevent this admin from logging in.'
                : 'Reactivate this admin to restore their access.'}
            </p>
          </div>
        </div>
        <button
          type="button"
          disabled={deactivateMutation.isPending}
          onClick={() => deactivateMutation.mutate()}
          className={`w-full py-2.5 text-sm font-bold rounded-xl disabled:opacity-60 ${
            admin.status === 'active'
              ? 'bg-red-50 text-red-600 border border-red-200'
              : 'bg-green-50 text-green-700 border border-green-200'
          }`}
        >
          {deactivateMutation.isPending
            ? '…'
            : admin.status === 'active'
            ? 'Deactivate Admin'
            : 'Activate Admin'}
        </button>
      </div>
    </div>
  )
}

// ── Page wrapper ──────────────────────────────────────────────────────────────

export default function StoreAdminFormPage() {
  const navigate = useNavigate()
  const { id }   = useParams<{ id: string }>()
  const adminId  = id ? parseInt(id, 10) : undefined
  const isEdit   = adminId !== undefined && !isNaN(adminId)

  return (
    <div className="min-h-full bg-gray-50 pb-8">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => navigate(-1)}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0"
          >
            <ChevronLeft size={20} className="text-white" />
          </button>
          <h1 className="text-white font-bold text-xl flex-1">
            {isEdit ? 'Edit Store Admin' : 'New Store Admin'}
          </h1>
        </div>
      </div>

      {isEdit ? <EditMode adminId={adminId} /> : <CreateMode />}
    </div>
  )
}
