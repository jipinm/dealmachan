import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { ArrowLeft, Mail, CheckCircle } from 'lucide-react'
import { authApi } from '@/api/endpoints/auth'
import { getApiError } from '@/api/client'

const schema = z.object({
  email: z.string().email('Enter a valid email'),
})

type FormValues = z.infer<typeof schema>

export default function ForgotPasswordPage() {
  const [submitted, setSubmitted] = useState(false)
  const [apiErr, setApiErr] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    getValues,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  const onSubmit = async (data: FormValues) => {
    setApiErr(null)
    try {
      await authApi.forgotPassword(data.email)
      setSubmitted(true)
    } catch (err) {
      const { message } = getApiError(err)
      setApiErr(message)
    }
  }

  if (submitted) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center px-6 bg-white">
        <div className="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
          <CheckCircle size={32} className="text-green-600" />
        </div>
        <h2 className="text-xl font-bold text-gray-900 mb-2">Check your inbox</h2>
        <p className="text-sm text-gray-500 text-center max-w-xs mb-8">
          We sent a password reset link to{' '}
          <strong className="text-gray-700">{getValues('email')}</strong>. It expires in 1 hour.
        </p>
        <Link
          to="/login"
          className="text-sm text-brand-600 font-medium flex items-center gap-1.5 hover:underline"
        >
          <ArrowLeft size={16} />
          Back to sign in
        </Link>
      </div>
    )
  }

  return (
    <div className="min-h-screen flex flex-col bg-white">
      {/* Top bar */}
      <div className="px-4 pt-12 pb-4">
        <Link
          to="/login"
          className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors"
        >
          <ArrowLeft size={16} />
          Back
        </Link>
      </div>

      <div className="flex-1 px-6 pt-4">
        <div className="w-12 h-12 rounded-xl bg-brand-50 flex items-center justify-center mb-5">
          <Mail size={24} className="text-brand-600" />
        </div>

        <h1 className="text-2xl font-bold text-gray-900 mb-2">Forgot password?</h1>
        <p className="text-gray-500 text-sm mb-8">
          Enter your email and we'll send you a link to reset your password.
        </p>

        {apiErr && (
          <div className="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            {apiErr}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">
              Email address
            </label>
            <input
              {...register('email')}
              type="email"
              autoComplete="email"
              inputMode="email"
              placeholder="you@business.com"
              className={`w-full px-4 py-3 rounded-xl border text-sm outline-none transition-colors ${
                errors.email
                  ? 'border-red-400 focus:border-red-500 bg-red-50'
                  : 'border-gray-200 focus:border-brand-600 bg-gray-50'
              }`}
            />
            {errors.email && (
              <p className="mt-1 text-xs text-red-600">{errors.email.message}</p>
            )}
          </div>

          <button
            type="submit"
            disabled={isSubmitting}
            className="w-full flex items-center justify-center gap-2 gradient-brand text-white font-semibold py-3.5 rounded-xl transition-opacity disabled:opacity-60"
          >
            {isSubmitting ? (
              <span className="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            ) : (
              'Send reset link'
            )}
          </button>
        </form>
      </div>
    </div>
  )
}
