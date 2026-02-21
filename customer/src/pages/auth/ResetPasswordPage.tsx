import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { Eye, EyeOff, Loader2 } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

export default function ResetPasswordPage() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') || searchParams.get('otp') || ''
  const phone = searchParams.get('phone') || ''

  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [showPassword, setShowPassword] = useState(false)

  const { mutate, isPending } = useMutation({
    mutationFn: () => authApi.resetPassword({ otp: token, phone, new_password: password }),
    onSuccess: () => {
      toast.success('Password updated! Please sign in.')
      navigate('/login', { replace: true })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (password !== confirm) {
      toast.error('Passwords do not match')
      return
    }
    if (!token) {
      toast.error('Invalid or expired reset link')
      return
    }
    mutate()
  }

  return (
    <div className="min-h-screen flex flex-col bg-gray-50">
      <div className="h-36 gradient-brand flex items-center justify-center">
        <div className="text-center text-white">
          <h1 className="font-heading font-bold text-xl">Set New Password</h1>
          <p className="text-white/70 text-sm mt-1">Choose a strong password</p>
        </div>
      </div>

      <div className="flex-1 flex flex-col items-center px-4 -mt-6">
        <div className="w-full max-w-sm bg-white rounded-2xl shadow-lg p-6">
          <h2 className="font-heading font-bold text-xl text-gray-900 mb-1">New Password</h2>
          <p className="text-sm text-gray-500 mb-6">Enter and confirm your new password.</p>

          {!token && (
            <div className="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-xs text-red-600">
              This reset link is invalid or has expired.{' '}
              <Link to="/forgot-password" className="underline font-semibold">Request a new one</Link>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-gray-700 mb-1">New Password</label>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Min 8 characters"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  minLength={8}
                  className="w-full border border-gray-200 rounded-xl px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                  {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-700 mb-1">Confirm Password</label>
              <input
                type={showPassword ? 'text' : 'password'}
                placeholder="Repeat your password"
                value={confirm}
                onChange={(e) => setConfirm(e.target.value)}
                className={`w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 transition ${
                  confirm && confirm !== password
                    ? 'border-red-300 focus:ring-red-300'
                    : 'border-gray-200 focus:ring-brand-400'
                }`}
                required
              />
              {confirm && confirm !== password && (
                <p className="text-xs text-red-500 mt-1">Passwords don't match</p>
              )}
            </div>

            <button
              type="submit"
              disabled={isPending || !token}
              className="btn-primary w-full py-3 flex items-center justify-center gap-2"
            >
              {isPending && <Loader2 size={16} className="animate-spin" />}
              {isPending ? 'Updating…' : 'Update Password'}
            </button>
          </form>

          <p className="text-center text-sm text-gray-500 mt-5">
            <Link to="/login" className="text-brand-600 hover:underline font-semibold">Back to Login</Link>
          </p>
        </div>
      </div>
    </div>
  )
}
