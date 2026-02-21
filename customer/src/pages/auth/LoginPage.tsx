import { useState } from 'react'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { Eye, EyeOff, Loader2 } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

export default function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const { setAuth } = useAuthStore()
  const from = (location.state as { from?: { pathname: string } })?.from?.pathname || '/'

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

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (!login.trim() || !password) return
    mutate()
  }

  return (
    <div className="min-h-screen flex flex-col bg-gray-50">
      {/* Header gradient */}
      <div className="h-40 gradient-brand flex items-center justify-center">
        <div className="text-center text-white">
          <div className="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center mx-auto mb-2">
            <span className="font-heading font-bold text-2xl">D</span>
          </div>
          <h1 className="font-heading font-bold text-xl">Deal Machan</h1>
          <p className="text-white/70 text-xs">Discover · Save · Redeem</p>
        </div>
      </div>

      {/* Form card */}
      <div className="flex-1 flex flex-col items-center px-4 -mt-6">
        <div className="w-full max-w-sm bg-white rounded-2xl shadow-lg p-6">
          <h2 className="font-heading font-bold text-xl text-gray-900 mb-1">Welcome back!</h2>
          <p className="text-sm text-gray-500 mb-6">Sign in to your account</p>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-gray-700 mb-1">Email or Phone</label>
              <input
                type="text"
                autoComplete="username"
                placeholder="you@example.com or 9876543210"
                value={login}
                onChange={(e) => setLogin(e.target.value)}
                className="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-700 mb-1">Password</label>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  autoComplete="current-password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
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

            <div className="flex justify-end">
              <Link to="/forgot-password" className="text-xs text-brand-600 hover:underline">
                Forgot password?
              </Link>
            </div>

            <button
              type="submit"
              disabled={isPending}
              className="btn-primary w-full py-3 flex items-center justify-center gap-2"
            >
              {isPending && <Loader2 size={16} className="animate-spin" />}
              {isPending ? 'Signing in…' : 'Sign In'}
            </button>
          </form>

          <p className="text-center text-sm text-gray-500 mt-6">
            Don't have an account?{' '}
            <Link to="/register" className="text-brand-600 font-semibold hover:underline">
              Register
            </Link>
          </p>
        </div>
      </div>
    </div>
  )
}
