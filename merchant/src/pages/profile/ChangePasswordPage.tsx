import { useNavigate } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ChevronLeft, Lock } from 'lucide-react'
import toast from 'react-hot-toast'
import { merchantApi } from '@/api/endpoints/merchant'

const schema = z
  .object({
    current_password: z.string().min(1, 'Current password is required'),
    new_password:     z.string().min(8, 'New password must be at least 8 characters'),
    confirm_password: z.string().min(1, 'Please confirm your new password'),
  })
  .refine((d) => d.new_password === d.confirm_password, {
    message: 'Passwords do not match',
    path: ['confirm_password'],
  })

type FormValues = z.infer<typeof schema>

function FormField({
  label, error, required, children,
}: {
  label: string; error?: string; required?: boolean; children: React.ReactNode
}) {
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
  'w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 bg-white placeholder-gray-300'

export default function ChangePasswordPage() {
  const navigate = useNavigate()

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  const mutation = useMutation({
    mutationFn: (data: FormValues) => merchantApi.changePassword(data),
    onSuccess: () => {
      toast.success('Password changed successfully')
      reset()
      navigate(-1)
    },
    onError: (err: any) => {
      const msg = err?.response?.data?.message ?? 'Failed to change password'
      toast.error(msg)
    },
  })

  const onSubmit = (data: FormValues) => mutation.mutate(data)

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
          <h1 className="text-white font-bold text-xl flex-1">Change Password</h1>
        </div>
      </div>

      <div className="px-4 -mt-2 space-y-4">
        {/* Info card */}
        <div className="bg-white rounded-2xl shadow-sm p-4 flex items-start gap-3">
          <div className="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center shrink-0">
            <Lock size={18} className="text-brand-600" />
          </div>
          <div>
            <p className="text-sm font-semibold text-gray-800">Update your password</p>
            <p className="text-xs text-gray-500 mt-0.5">
              Choose a strong password with at least 8 characters.
            </p>
          </div>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit(onSubmit)} className="bg-white rounded-2xl shadow-sm p-4 space-y-4">
          <FormField label="Current Password" required error={errors.current_password?.message}>
            <input
              type="password"
              autoComplete="current-password"
              placeholder="Enter current password"
              className={inputClass}
              {...register('current_password')}
            />
          </FormField>

          <FormField label="New Password" required error={errors.new_password?.message}>
            <input
              type="password"
              autoComplete="new-password"
              placeholder="At least 8 characters"
              className={inputClass}
              {...register('new_password')}
            />
          </FormField>

          <FormField label="Confirm New Password" required error={errors.confirm_password?.message}>
            <input
              type="password"
              autoComplete="new-password"
              placeholder="Re-enter new password"
              className={inputClass}
              {...register('confirm_password')}
            />
          </FormField>

          <button
            type="submit"
            disabled={mutation.isPending}
            className="w-full py-3 rounded-xl gradient-brand text-white text-sm font-semibold disabled:opacity-60 transition-opacity mt-2"
          >
            {mutation.isPending ? 'Saving…' : 'Change Password'}
          </button>
        </form>
      </div>
    </div>
  )
}
