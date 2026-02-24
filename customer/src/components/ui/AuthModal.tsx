import { useState, useEffect, useRef } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { X, Eye, EyeOff, Loader2, LogIn, UserPlus } from 'lucide-react'
import { authApi } from '@/api/endpoints/auth'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

// ── Types ─────────────────────────────────────────────────────────────────

interface AuthModalProps {
  isOpen: boolean
  onClose: () => void
  /** Message shown above the form, e.g. "Sign in to reveal & copy this coupon code" */
  prompt?: string
  /** Which tab to open first. Default 'login' */
  defaultTab?: 'login' | 'register'
  /** Called after successful login/register so parent can continue its action */
  onSuccess?: () => void
}

// ── Password field ────────────────────────────────────────────────────────

function PwInput({
  id, value, onChange, placeholder, label, hint,
}: {
  id: string; value: string; onChange: (v: string) => void
  placeholder?: string; label: string; hint?: string
}) {
  const [show, setShow] = useState(false)
  return (
    <div>
      <label htmlFor={id} className="block text-sm font-medium text-slate-700 mb-1.5">{label}</label>
      <div className="relative">
        <input
          id={id}
          type={show ? 'text' : 'password'}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder ?? '••••••••'}
          autoComplete={id.includes('new') ? 'new-password' : 'current-password'}
          className="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-11 text-sm
                     focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent"
          required
        />
        <button
          type="button"
          onClick={() => setShow((s) => !s)}
          className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
        >
          {show ? <EyeOff size={15} /> : <Eye size={15} />}
        </button>
      </div>
      {hint && <p className="text-xs text-slate-400 mt-1">{hint}</p>}
    </div>
  )
}

// ── Input field ───────────────────────────────────────────────────────────

function Field({
  label, id, type = 'text', value, onChange, placeholder, maxLength, hint, required = true,
}: {
  label: string; id: string; type?: string; value: string
  onChange: (v: string) => void; placeholder?: string
  maxLength?: number; hint?: string; required?: boolean
}) {
  return (
    <div>
      <label htmlFor={id} className="block text-sm font-medium text-slate-700 mb-1.5">{label}</label>
      <input
        id={id}
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        maxLength={maxLength}
        required={required}
        className="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent"
      />
      {hint && <p className="text-xs text-slate-400 mt-1">{hint}</p>}
    </div>
  )
}

// ── Login form ────────────────────────────────────────────────────────────

function LoginForm({ onSuccess, onClose }: { onSuccess?: () => void; onClose: () => void }) {
  const { setAuth } = useAuthStore()
  const [login,    setLogin]    = useState('')
  const [password, setPassword] = useState('')

  const { mutate, isPending } = useMutation({
    mutationFn: () => authApi.login({ login, password }),
    onSuccess: (res) => {
      const { customer, access_token, refresh_token } = res.data.data!
      setAuth(customer, access_token, refresh_token)
      toast.success(`Welcome back, ${customer.name.split(' ')[0]}!`)
      onClose()
      onSuccess?.()
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); if (login.trim() && password) mutate() }}
      className="space-y-4"
    >
      <Field
        id="modal-login"
        label="Email or Phone"
        value={login}
        onChange={setLogin}
        placeholder="you@example.com or 9876543210"
        type="text"
      />
      <PwInput
        id="modal-password"
        label="Password"
        value={password}
        onChange={setPassword}
      />
      <button
        type="submit"
        disabled={!login.trim() || !password || isPending}
        className="w-full btn-brand py-2.5 rounded-xl font-semibold text-sm
                   disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        {isPending ? <Loader2 size={16} className="animate-spin" /> : <LogIn size={16} />}
        Sign In
      </button>
    </form>
  )
}

// ── Register form ─────────────────────────────────────────────────────────

function RegisterForm({ onClose }: { onClose: () => void }) {
  const navigate = useNavigate()
  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '', referral_code: '' })
  const set = (k: keyof typeof form) => (v: string) => setForm((p) => ({ ...p, [k]: v }))

  const { mutate, isPending } = useMutation({
    mutationFn: () => authApi.register(form),
    onSuccess: () => {
      toast.success('OTP sent to your phone!')
      onClose()
      navigate('/otp-verify', { state: { phone: form.phone } })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); mutate() }}
      className="space-y-3"
    >
      <Field id="modal-name"  label="Full Name"  value={form.name}  onChange={set('name')}  placeholder="Your full name" />
      <Field id="modal-email" label="Email"       value={form.email} onChange={set('email')} placeholder="you@example.com" type="email" />
      <Field
        id="modal-phone"
        label="Phone Number"
        value={form.phone}
        onChange={set('phone')}
        placeholder="10-digit mobile number"
        type="tel"
        maxLength={10}
        hint="An OTP will be sent to verify this number"
      />
      <PwInput id="modal-new-password" label="Password" value={form.password} onChange={set('password')} />
      <Field
        id="modal-referral"
        label="Referral Code (optional)"
        value={form.referral_code}
        onChange={set('referral_code')}
        placeholder="Enter referral code if you have one"
        required={false}
      />
      <button
        type="submit"
        disabled={!form.name || !form.email || !form.phone || !form.password || isPending}
        className="w-full btn-brand py-2.5 rounded-xl font-semibold text-sm
                   disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        {isPending ? <Loader2 size={16} className="animate-spin" /> : <UserPlus size={16} />}
        Create Account
      </button>
    </form>
  )
}

// ── Modal ─────────────────────────────────────────────────────────────────

export default function AuthModal({
  isOpen, onClose, prompt, defaultTab = 'login', onSuccess,
}: AuthModalProps) {
  const [tab, setTab] = useState<'login' | 'register'>(defaultTab)
  const overlayRef = useRef<HTMLDivElement>(null)

  // Sync tab when modal opens with a different defaultTab
  useEffect(() => { if (isOpen) setTab(defaultTab) }, [isOpen, defaultTab])

  // Close on Escape
  useEffect(() => {
    if (!isOpen) return
    const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose() }
    window.addEventListener('keydown', handler)
    return () => window.removeEventListener('keydown', handler)
  }, [isOpen, onClose])

  // Lock body scroll
  useEffect(() => {
    if (isOpen) document.body.style.overflow = 'hidden'
    else        document.body.style.overflow = ''
    return () => { document.body.style.overflow = '' }
  }, [isOpen])

  if (!isOpen) return null

  return (
    <div
      ref={overlayRef}
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      onClick={(e) => { if (e.target === overlayRef.current) onClose() }}
    >
      {/* Backdrop */}
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" aria-hidden />

      {/* Panel */}
      <div
        className="relative w-full max-w-sm bg-white rounded-3xl shadow-2xl overflow-hidden
                   animate-fade-slide-up"
        role="dialog"
        aria-modal
        aria-label={tab === 'login' ? 'Sign In' : 'Create Account'}
      >
        {/* Close */}
        <button
          onClick={onClose}
          className="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200
                     flex items-center justify-center text-slate-500 transition-colors z-10"
          aria-label="Close"
        >
          <X size={16} />
        </button>

        {/* Tab header */}
        <div className="px-6 pt-6 pb-0">
          {prompt && (
            <p className="text-xs text-slate-500 bg-brand-50 text-brand-700 px-3 py-2 rounded-xl mb-4 text-center font-medium">
              {prompt}
            </p>
          )}
          <div className="flex rounded-xl bg-slate-100 p-1 mb-5">
            <button
              onClick={() => setTab('login')}
              className={`flex-1 py-2 text-sm font-semibold rounded-lg transition-all ${
                tab === 'login'
                  ? 'bg-white text-slate-800 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              Sign In
            </button>
            <button
              onClick={() => setTab('register')}
              className={`flex-1 py-2 text-sm font-semibold rounded-lg transition-all ${
                tab === 'register'
                  ? 'bg-white text-slate-800 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              Register
            </button>
          </div>
        </div>

        {/* Form body */}
        <div className="px-6 pb-6">
          {tab === 'login' ? (
            <>
              <LoginForm onSuccess={onSuccess} onClose={onClose} />
              <p className="text-center text-xs text-slate-400 mt-4">
                Forgot password?{' '}
                <Link
                  to="/forgot-password"
                  onClick={onClose}
                  className="text-brand-600 font-medium hover:underline"
                >
                  Reset here
                </Link>
              </p>
            </>
          ) : (
            <RegisterForm onClose={onClose} />
          )}
        </div>
      </div>
    </div>
  )
}
