import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Loader2, ArrowLeft } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

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
      <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4">
        <div className="w-full max-w-sm bg-white rounded-2xl shadow-lg p-8 text-center">
          <div className="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <span className="text-3xl">📧</span>
          </div>
          <h2 className="font-heading font-bold text-xl text-gray-900 mb-2">Check your email</h2>
          <p className="text-sm text-gray-500 mb-6">
            We've sent a password reset link to <span className="font-semibold">{email}</span>
          </p>
          <Link to="/login" className="btn-primary w-full py-3 inline-flex items-center justify-center">
            Back to Login
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen flex flex-col bg-gray-50">
      <div className="h-36 gradient-brand flex items-center justify-center">
        <div className="text-center text-white">
          <h1 className="font-heading font-bold text-xl">Forgot Password</h1>
          <p className="text-white/70 text-sm mt-1">We'll send you a reset link</p>
        </div>
      </div>

      <div className="flex-1 flex flex-col items-center px-4 -mt-6">
        <div className="w-full max-w-sm bg-white rounded-2xl shadow-lg p-6">
          <Link to="/login" className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-5">
            <ArrowLeft size={14} />
            Back to Login
          </Link>

          <h2 className="font-heading font-bold text-xl text-gray-900 mb-1">Reset Password</h2>
          <p className="text-sm text-gray-500 mb-6">
            Enter the email address linked to your account and we'll send a reset link.
          </p>

          <form
            onSubmit={(e) => { e.preventDefault(); mutate() }}
            className="space-y-4"
          >
            <div>
              <label className="block text-xs font-semibold text-gray-700 mb-1">Email Address</label>
              <input
                type="email"
                placeholder="you@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition"
                required
              />
            </div>

            <button
              type="submit"
              disabled={isPending}
              className="btn-primary w-full py-3 flex items-center justify-center gap-2"
            >
              {isPending && <Loader2 size={16} className="animate-spin" />}
              {isPending ? 'Sending…' : 'Send Reset Link'}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
