import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ArrowLeft, Eye, EyeOff, CheckCircle, AlertCircle } from 'lucide-react'
import { authApi } from '@/api/endpoints/auth'
import { getApiError } from '@/api/client'

const schema = z
  .object({
    password: z.string().min(8, 'Password must be at least 8 characters'),
    password_confirmation: z.string(),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })

type FormValues = z.infer<typeof schema>

export default function ResetPasswordPage() {
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const token = params.get('token') ?? ''

  const [showPass, setShowPass] = useState(false)
  const [showConfirm, setShowConfirm] = useState(false)
  const [done, setDone] = useState(false)
  const [apiErr, setApiErr] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  if (!token) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center px-6 bg-white">
        <AlertCircle size={40} className="text-red-500 mb-4" />
        <h2 className="text-lg font-bold text-gray-900 mb-2">Invalid Link</h2>
        <p className="text-sm text-gray-500 text-center mb-6">
          This password reset link is invalid or has already been used.
        </p>
        <Link to="/forgot-password" className="text-sm text-brand-600 font-medium hover:underline">
          Request a new link
        </Link>
      </div>
    )
  }

  if (done) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center px-6 bg-white">
        <div className="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
          <CheckCircle size={32} className="text-green-600" />
        </div>
        <h2 className="text-xl font-bold text-gray-900 mb-2">Password updated!</h2>
        <p className="text-sm text-gray-500 text-center max-w-xs mb-8">
          Your password has been changed. You can now sign in with your new password.
        </p>
        <button
          onClick={() => navigate('/login', { replace: true })}
          className="gradient-brand text-white font-semibold px-8 py-3 rounded-xl"
        >
          Go to Sign In
        </button>
      </div>
    )
  }

  const onSubmit = async (data: FormValues) => {
    setApiErr(null)
    try {
      await authApi.resetPassword({ token, ...data })
      setDone(true)
    } catch (err) {
      const { message } = getApiError(err)
      setApiErr(message)
    }
  }

  const PasswordInput = ({
    name,
    label,
    show,
    toggle,
  }: {
    name: 'password' | 'password_confirmation'
    label: string
    show: boolean
    toggle: () => void
  }) => (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1.5">{label}</label>
      <div className="relative">
        <input
          {...register(name)}
          type={show ? 'text' : 'password'}
          autoComplete={name === 'password' ? 'new-password' : 'new-password'}
          placeholder="••••••••"
          className={`w-full px-4 py-3 pr-12 rounded-xl border text-sm outline-none transition-colors ${
            errors[name]
              ? 'border-red-400 focus:border-red-500 bg-red-50'
              : 'border-gray-200 focus:border-brand-600 bg-gray-50'
          }`}
        />
        <button
          type="button"
          onClick={toggle}
          className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1"
          tabIndex={-1}
        >
          {show ? <EyeOff size={18} /> : <Eye size={18} />}
        </button>
      </div>
      {errors[name] && (
        <p className="mt-1 text-xs text-red-600">{errors[name]?.message}</p>
      )}
    </div>
  )

  return (
    <div className="min-h-screen flex flex-col bg-white">
      <div className="px-4 pt-12 pb-4">
        <Link
          to="/login"
          className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors"
        >
          <ArrowLeft size={16} />
          Back to sign in
        </Link>
      </div>

      <div className="flex-1 px-6 pt-4">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Reset password</h1>
        <p className="text-gray-500 text-sm mb-8">Enter your new password below.</p>

        {apiErr && (
          <div className="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            {apiErr}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
          <PasswordInput
            name="password"
            label="New password"
            show={showPass}
            toggle={() => setShowPass((p) => !p)}
          />
          <PasswordInput
            name="password_confirmation"
            label="Confirm new password"
            show={showConfirm}
            toggle={() => setShowConfirm((p) => !p)}
          />

          <p className="text-xs text-gray-400">Minimum 8 characters</p>

          <button
            type="submit"
            disabled={isSubmitting}
            className="w-full flex items-center justify-center gap-2 gradient-brand text-white font-semibold py-3.5 rounded-xl transition-opacity disabled:opacity-60 mt-2"
          >
            {isSubmitting ? (
              <span className="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            ) : (
              'Update password'
            )}
          </button>
        </form>
      </div>
    </div>
  )
}
