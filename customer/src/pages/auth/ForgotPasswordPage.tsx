import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Loader2 } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'
import AuthLayout from '@/components/layout/AuthLayout'
import { Helmet } from 'react-helmet-async'

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('')

  const { mutate, isPending, isSuccess } = useMutation({
    mutationFn: () => authApi.forgotPassword(email),
    onSuccess: () => {
      toast.success('Reset link sent to your email!')
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  if (isSuccess) {
    return (
      <AuthLayout title="Check your email">
        <div className="text-center py-4">
          <div className="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
            <span className="text-3xl">📧</span>
          </div>
          <p className="text-sm text-slate-600 mb-2">
            We sent a password reset link to{' '}
            <span className="font-semibold text-slate-900">{email}</span>.
          </p>
          <p className="text-sm text-slate-500 mb-8">
            Check your inbox and follow the link to reset your password.
          </p>
          <Link to="/login" className="btn-primary w-full">
            Back to Sign In
          </Link>
        </div>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout
      title="Forgot your password?"
      subtitle="Enter the email address linked to your account — we'll send you a reset link"
    >
      <Helmet>
        <title>Reset Password | Deal Machan</title>
        <meta name="description" content="Forgot your Deal Machan password? Enter your email and we'll send you a reset link." />
      </Helmet>
      <form
        onSubmit={(e) => { e.preventDefault(); mutate() }}
        className="space-y-5"
      >
        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
          <input
            type="email"
            placeholder="you@example.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="input-field"
            required
          />
        </div>

        <button type="submit" disabled={isPending} className="btn-primary w-full">
          {isPending && <Loader2 size={17} className="animate-spin" />}
          {isPending ? 'Sending…' : 'Send Reset Link'}
        </button>
      </form>

      <p className="text-center text-sm text-slate-600 mt-7">
        Remember your password?{' '}
        <Link to="/login" className="text-brand-600 font-semibold hover:text-brand-700 transition-colors">
          Sign In
        </Link>
      </p>
    </AuthLayout>
  )
}
