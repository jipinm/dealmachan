import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { Eye, EyeOff, Lock, ShieldCheck, Loader2, KeyRound } from 'lucide-react'
import { profileApi } from '@/api/endpoints/profile'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

// ── Password strength helper ──────────────────────────────────────────────

function strengthLabel(pw: string): { label: string; color: string; pct: number } {
  if (!pw) return { label: '', color: '', pct: 0 }
  let score = 0
  if (pw.length >= 8)          score++
  if (pw.length >= 12)         score++
  if (/[A-Z]/.test(pw))        score++
  if (/[0-9]/.test(pw))        score++
  if (/[^A-Za-z0-9]/.test(pw)) score++
  if (score <= 1) return { label: 'Weak',   color: 'bg-red-400',    pct: 25  }
  if (score <= 2) return { label: 'Fair',   color: 'bg-orange-400', pct: 50  }
  if (score <= 3) return { label: 'Good',   color: 'bg-yellow-400', pct: 75  }
  return              { label: 'Strong', color: 'bg-green-500',  pct: 100 }
}

// ── Password input with show/hide ─────────────────────────────────────────

function PasswordInput({
  id, label, value, onChange, placeholder,
}: {
  id: string
  label: string
  value: string
  onChange: (v: string) => void
  placeholder?: string
}) {
  const [show, setShow] = useState(false)
  return (
    <div>
      <label htmlFor={id} className="block text-sm font-medium text-slate-700 mb-1.5">
        {label}
      </label>
      <div className="relative">
        <input
          id={id}
          type={show ? 'text' : 'password'}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          className="w-full border border-slate-200 rounded-xl px-4 py-3 pr-11 text-sm
                     focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent
                     bg-white text-slate-800 placeholder-slate-400"
          autoComplete="new-password"
        />
        <button
          type="button"
          onClick={() => setShow((s) => !s)}
          className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
          aria-label={show ? 'Hide password' : 'Show password'}
        >
          {show ? <EyeOff size={16} /> : <Eye size={16} />}
        </button>
      </div>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────

export default function SetNewPasswordPage() {
  const navigate = useNavigate()
  const { customer, setCustomer } = useAuthStore()
  const [newPw,   setNewPw]   = useState('')
  const [confirm, setConfirm] = useState('')
  const [done,    setDone]    = useState(false)

  const strength = strengthLabel(newPw)

  const { mutate, isPending } = useMutation({
    mutationFn: () =>
      profileApi.setNewPassword({ new_password: newPw, confirm_password: confirm }),
    onSuccess: () => {
      // Clear temp_password flag locally so the guard stops redirecting
      if (customer) setCustomer({ ...customer, temp_password: 0 })
      setDone(true)
      toast.success('Password set! Welcome aboard.')
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (newPw.length < 8) {
      toast.error('Password must be at least 8 characters.')
      return
    }
    if (newPw !== confirm) {
      toast.error('Passwords do not match.')
      return
    }
    mutate()
  }

  // ── Success state ─────────────────────────────────────────────────────────

  if (done) {
    return (
      <div className="min-h-screen bg-brand-surface flex items-center justify-center p-4">
        <div className="max-w-sm w-full bg-white rounded-3xl shadow-brand p-8 text-center animate-fade-slide-up">
          <div className="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
            <ShieldCheck size={32} className="text-green-500" />
          </div>
          <h2 className="font-heading font-bold text-xl text-slate-800 mb-2">Password Set!</h2>
          <p className="text-sm text-slate-500 mb-6">
            Your new password is active. You can now use the app normally.
          </p>
          <button
            onClick={() => navigate('/dashboard', { replace: true })}
            className="btn-primary w-full"
          >
            Go to Dashboard
          </button>
        </div>
      </div>
    )
  }

  // ── Form ──────────────────────────────────────────────────────────────────

  return (
    <div className="min-h-screen bg-brand-surface flex items-center justify-center p-4">
      <div className="max-w-sm w-full">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="w-16 h-16 rounded-2xl gradient-brand flex items-center justify-center mx-auto mb-4 shadow-brand">
            <KeyRound size={28} className="text-white" />
          </div>
          <h1 className="font-heading font-bold text-2xl text-slate-800 mb-1">Set Your Password</h1>
          <p className="text-sm text-slate-500">
            Please create a new password to secure your account.
          </p>
        </div>

        {/* Alert */}
        <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
          <Lock size={16} className="text-amber-500 mt-0.5 flex-shrink-0" />
          <p className="text-sm text-amber-700">
            For your security, you need to set a personal password before continuing.
          </p>
        </div>

        {/* Form card */}
        <div className="bg-white rounded-3xl shadow-brand p-6 space-y-5">
          <form onSubmit={handleSubmit} className="space-y-5">
            <PasswordInput
              id="new-password"
              label="New Password"
              value={newPw}
              onChange={setNewPw}
              placeholder="Min 8 characters"
            />

            {/* Strength bar */}
            {newPw && (
              <div className="space-y-1 -mt-2">
                <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full transition-all duration-300 ${strength.color}`}
                    style={{ width: `${strength.pct}%` }}
                  />
                </div>
                <p className="text-xs text-slate-400">{strength.label}</p>
              </div>
            )}

            <PasswordInput
              id="confirm-password"
              label="Confirm New Password"
              value={confirm}
              onChange={setConfirm}
              placeholder="Repeat your password"
            />

            {/* Match indicator */}
            {confirm && newPw && (
              <p className={`text-xs -mt-2 ${confirm === newPw ? 'text-green-500' : 'text-red-400'}`}>
                {confirm === newPw ? '✓ Passwords match' : '✗ Passwords do not match'}
              </p>
            )}

            <button
              type="submit"
              disabled={isPending || newPw.length < 8 || newPw !== confirm}
              className="btn-primary w-full flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isPending ? (
                <><Loader2 size={16} className="animate-spin" /> Setting Password…</>
              ) : (
                <><Lock size={16} /> Set Password</>
              )}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
