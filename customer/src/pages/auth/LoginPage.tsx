import { useState } from 'react'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { Eye, EyeOff, Loader2 } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'
import AuthLayout from '@/components/layout/AuthLayout'

export default function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const { setAuth } = useAuthStore()
  const from = (location.state as { from?: { pathname: string } })?.from?.pathname || '/dashboard'

  const [login, setLogin] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)

  const { mutate, isPending } = useMutation({
    mutationFn: () => authApi.login({ login, password }),
    onSuccess: (res) => {
      const { customer, access_token, refresh_token } = res.data.data!
      setAuth(customer, access_token, refresh_token)
      if (customer.is_new_user) {
        navigate('/onboarding', { replace: true })
      } else {
        navigate(from, { replace: true })
      }
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  return (
    <AuthLayout
      title="Welcome back!"
      subtitle="Sign in to your DealMachan account to continue"
    >
      <form
        onSubmit={(e) => { e.preventDefault(); if (login.trim() && password) mutate() }}
        className="space-y-5"
      >
        {/* Email / phone */}
        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">
            Email or Phone Number
          </label>
          <input
            type="text"
            autoComplete="username"
            placeholder="you@example.com or 9876543210"
            value={login}
            onChange={(e) => setLogin(e.target.value)}
            className="input-field"
            required
          />
        </div>

        {/* Password */}
        <div>
          <div className="flex items-center justify-between mb-1.5">
            <label className="block text-sm font-medium text-slate-700">Password</label>
            <Link
              to="/forgot-password"
              className="text-xs text-brand-600 font-medium hover:text-brand-700 transition-colors"
            >
              Forgot password?
            </Link>
          </div>
          <div className="relative">
            <input
              type={showPassword ? 'text' : 'password'}
              autoComplete="current-password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
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

        <button type="submit" disabled={isPending} className="btn-primary w-full mt-1">
          {isPending && <Loader2 size={17} className="animate-spin" />}
          {isPending ? 'Signing in…' : 'Sign In'}
        </button>
      </form>

      {/* Divider */}
      <div className="relative my-6">
        <div className="absolute inset-0 flex items-center">
          <div className="w-full border-t border-slate-200" />
        </div>
        <div className="relative flex justify-center">
          <span className="bg-[#f8f9fc] px-3 text-xs text-slate-400 uppercase tracking-wider">or</span>
        </div>
      </div>

      <p className="text-center text-sm text-slate-600">
        Don't have an account?{' '}
        <Link to="/register" className="text-brand-600 font-semibold hover:text-brand-700 transition-colors">
          Create free account
        </Link>
      </p>

      <p className="text-center text-[11px] text-slate-400 mt-5 leading-relaxed">
        By signing in you agree to our{' '}
        <Link to="/page/terms" className="underline hover:text-slate-600">Terms of Service</Link>
        {' and '}
        <Link to="/page/privacy" className="underline hover:text-slate-600">Privacy Policy</Link>.
      </p>
    </AuthLayout>
  )
}
