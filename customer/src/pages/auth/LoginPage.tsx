import { useState } from 'react'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { Eye, EyeOff, Loader2, ArrowRight, ChevronLeft } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'
import AuthLayout from '@/components/layout/AuthLayout'
import { Helmet } from 'react-helmet-async'

type LoginStep = 'identifier' | 'password' | 'otp'

export default function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const { setAuth } = useAuthStore()
  const from = (location.state as { from?: { pathname: string } })?.from?.pathname || '/'

  const [step,          setStep]          = useState<LoginStep>('identifier')
  const [login,         setLogin]         = useState('')
  const [password,      setPassword]      = useState('')
  const [showPassword,  setShowPassword]  = useState(false)
  const [otpPhone,      setOtpPhone]      = useState('')
  const [otpCode,       setOtpCode]       = useState('')

  // ── Step 1: Check login (identifier → has_password?) ────────────────────
  const checkMutation = useMutation({
    mutationFn: () => authApi.checkLogin(login.trim()),
    onSuccess: (res) => {
      const data = res.data.data
      if (data.has_password) {
        setStep('password')
      } else {
        // OTP already sent by the server — show OTP panel
        setOtpPhone(data.phone ?? login.trim())
        setOtpCode('')
        if (data.otp) toast(`Dev OTP: ${data.otp}`, { icon: '🔐', duration: 8000 })
        else toast.success('OTP sent to your registered phone.')
        setStep('otp')
      }
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  // ── Step 2a: Password login ──────────────────────────────────────────────
  const loginMutation = useMutation({
    mutationFn: () => authApi.login({ login: login.trim(), password }),
    onSuccess: (res) => {
      const { customer, access_token, refresh_token } = res.data.data!
      setAuth(customer, access_token, refresh_token)
      navigate(customer.is_new_user ? '/onboarding' : from, { replace: true })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  // ── Step 2b: OTP verify (resend) ─────────────────────────────────────────
  const resendMutation = useMutation({
    mutationFn: () => authApi.loginOtpRequest(otpPhone),
    onSuccess: (res) => {
      const data = (res as any)?.data?.data
      setOtpCode('')
      if (data?.otp) toast(`Dev OTP: ${data.otp}`, { icon: '🔐', duration: 8000 })
      else toast.success('New OTP sent.')
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const verifyOtpMutation = useMutation({
    mutationFn: () => authApi.verifyLoginOtp({ phone: otpPhone, otp: otpCode }),
    onSuccess: (res) => {
      const { customer, access_token, refresh_token } = res.data.data!
      setAuth(customer, access_token, refresh_token)
      // OTP login is only for temp_password=1 accounts — scope says: OTP → set password → city → card
      navigate('/profile/set-password', { replace: true })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  return (
    <AuthLayout
      title="Welcome back!"
      subtitle="Sign in to your DealMachan account to continue"
    >
      <Helmet>
        <title>Sign In | Deal Machan</title>
        <meta name="description" content="Sign in to your Deal Machan account to access your saved deals, coupon wallet, and personalised offers." />
      </Helmet>

      {/* ── Step 1: Identifier ────────────────────────────────────────────── */}
      {step === 'identifier' && (
        <form
          onSubmit={(e) => { e.preventDefault(); if (login.trim()) checkMutation.mutate() }}
          className="space-y-5"
        >
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1.5">
              Mobile Number or Email ID
            </label>
            <input
              type="text"
              autoComplete="username"
              placeholder="you@example.com or 9876543210"
              value={login}
              onChange={(e) => setLogin(e.target.value)}
              className="input-field"
              autoFocus
              required
            />
          </div>

          <button
            type="submit"
            disabled={checkMutation.isPending || !login.trim()}
            className="btn-primary w-full flex items-center justify-center gap-2"
          >
            {checkMutation.isPending
              ? <Loader2 size={17} className="animate-spin" />
              : <ArrowRight size={17} />
            }
            {checkMutation.isPending ? 'Checking…' : 'Continue'}
          </button>
        </form>
      )}

      {/* ── Step 2a: Password ─────────────────────────────────────────────── */}
      {step === 'password' && (
        <form
          onSubmit={(e) => { e.preventDefault(); if (password) loginMutation.mutate() }}
          className="space-y-5"
        >
          {/* Identifier display + back */}
          <div className="flex items-center justify-between bg-slate-50 rounded-2xl px-4 py-3">
            <div>
              <p className="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-0.5">Signing in as</p>
              <p className="text-sm font-semibold text-slate-700">{login.trim()}</p>
            </div>
            <button
              type="button"
              onClick={() => { setStep('identifier'); setPassword('') }}
              className="flex items-center gap-1 text-xs text-brand-500 font-medium hover:underline"
            >
              <ChevronLeft size={13} /> Change
            </button>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
            <div className="relative">
              <input
                type={showPassword ? 'text' : 'password'}
                autoComplete="current-password"
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="input-field pr-11"
                autoFocus
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

          <button
            type="submit"
            disabled={loginMutation.isPending || !password}
            className="btn-primary w-full flex items-center justify-center gap-2"
          >
            {loginMutation.isPending && <Loader2 size={17} className="animate-spin" />}
            {loginMutation.isPending ? 'Signing in…' : 'Sign In'}
          </button>

          <div className="text-right -mt-2">
            <Link
              to="/forgot-password"
              className="text-xs text-brand-600 font-medium hover:text-brand-700 transition-colors"
            >
              Forgot password?
            </Link>
          </div>
        </form>
      )}

      {/* ── Step 2b: OTP ─────────────────────────────────────────────────── */}
      {step === 'otp' && (
        <div className="space-y-5">
          {/* Identifier display + back */}
          <div className="flex items-center justify-between bg-slate-50 rounded-2xl px-4 py-3">
            <div>
              <p className="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-0.5">OTP sent to</p>
              <p className="text-sm font-semibold text-slate-700">{otpPhone}</p>
            </div>
            <button
              type="button"
              onClick={() => { setStep('identifier'); setOtpCode('') }}
              className="flex items-center gap-1 text-xs text-brand-500 font-medium hover:underline"
            >
              <ChevronLeft size={13} /> Change
            </button>
          </div>

          <div className="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            Enter the OTP sent to your phone to sign in and set up your password.
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1.5">One-Time Password</label>
            <input
              type="text"
              inputMode="numeric"
              placeholder="6-digit code"
              value={otpCode}
              onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
              className="input-field tracking-widest text-center text-lg"
              maxLength={6}
              autoFocus
              autoComplete="one-time-code"
            />
          </div>

          <button
            type="button"
            disabled={otpCode.length < 4 || verifyOtpMutation.isPending}
            onClick={() => verifyOtpMutation.mutate()}
            className="btn-primary w-full flex items-center justify-center gap-2"
          >
            {verifyOtpMutation.isPending && <Loader2 size={17} className="animate-spin" />}
            {verifyOtpMutation.isPending ? 'Verifying…' : 'Verify & Sign In'}
          </button>

          <p className="text-center text-sm text-slate-500">
            Didn&apos;t receive the code?{' '}
            <button
              type="button"
              disabled={resendMutation.isPending}
              className="text-brand-600 font-semibold hover:text-brand-700 disabled:opacity-50"
              onClick={() => resendMutation.mutate()}
            >
              {resendMutation.isPending ? 'Sending…' : 'Resend OTP'}
            </button>
          </p>
        </div>
      )}

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
