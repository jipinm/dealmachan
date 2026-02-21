import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { Eye, EyeOff, Loader2 } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'
import AuthLayout from '@/components/layout/AuthLayout'

export default function ResetPasswordPage() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') || ''

  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [showPassword, setShowPassword] = useState(false)

  const { mutate, isPending } = useMutation({
    mutationFn: () => authApi.resetPassword({ token, password }),
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
    <AuthLayout
      title="Set new password"
      subtitle="Choose a strong password for your DealMachan account"
    >
      {!token && (
        <div className="mb-5 p-4 bg-red-50 border border-red-100 rounded-xl text-sm text-red-700">
          This reset link is invalid or has expired.{' '}
          <Link to="/forgot-password" className="underline font-semibold">Request a new one</Link>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-5">
        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">New Password</label>
          <div className="relative">
            <input
              type={showPassword ? 'text' : 'password'}
              placeholder="Min 8 characters"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              minLength={8}
              className="input-field pr-11"
              required
            />
            <button
              type="button"
              onClick={() => setShowPassword((v) => !v)}
              className="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
            >
              {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
          <input
            type={showPassword ? 'text' : 'password'}
            placeholder="Repeat your password"
            value={confirm}
            onChange={(e) => setConfirm(e.target.value)}
            className={`input-field ${
              confirm && confirm !== password ? 'border-red-400 focus:border-red-400' : ''
            }`}
            required
          />
          {confirm && confirm !== password && (
            <p className="text-xs text-red-500 mt-1.5">Passwords don't match</p>
          )}
        </div>

        <button
          type="submit"
          disabled={isPending || !token}
          className="btn-primary w-full"
        >
          {isPending && <Loader2 size={17} className="animate-spin" />}
          {isPending ? 'Updating…' : 'Update Password'}
        </button>
      </form>

      <p className="text-center text-sm text-slate-600 mt-7">
        <Link to="/login" className="text-brand-600 font-semibold hover:text-brand-700 transition-colors">
          Back to Sign In
        </Link>
      </p>
    </AuthLayout>
  )
}
