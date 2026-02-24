import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { ChevronLeft, Eye, EyeOff, Lock, ShieldCheck, Loader2 } from 'lucide-react'
import { profileApi } from '@/api/endpoints/profile'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

// ── Password strength helper ──────────────────────────────────────────────

function strengthLabel(pw: string): { label: string; color: string; pct: number } {
  if (!pw) return { label: '', color: '', pct: 0 }
  let score = 0
  if (pw.length >= 8)  score++
  if (pw.length >= 12) score++
  if (/[A-Z]/.test(pw)) score++
  if (/[0-9]/.test(pw)) score++
  if (/[^A-Za-z0-9]/.test(pw)) score++
  if (score <= 1) return { label: 'Weak',   color: 'bg-red-400',    pct: 25  }
  if (score <= 2) return { label: 'Fair',   color: 'bg-orange-400', pct: 50  }
  if (score <= 3) return { label: 'Good',   color: 'bg-yellow-400', pct: 75  }
  return              { label: 'Strong', color: 'bg-green-500',  pct: 100 }
}

// ── Password input with show/hide ─────────────────────────────────────────

function PasswordInput({
  id, label, value, onChange, placeholder, hint,
}: {
  id: string
  label: string
  value: string
  onChange: (v: string) => void
  placeholder?: string
  hint?: string
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
          autoComplete={id === 'current-password' ? 'current-password' : 'new-password'}
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
      {hint && <p className="text-xs text-slate-400 mt-1">{hint}</p>}
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────

export default function ChangePasswordPage() {
  const [current, setCurrent]   = useState('')
  const [newPw,   setNewPw]     = useState('')
  const [confirm, setConfirm]   = useState('')
  const [done,    setDone]      = useState(false)

  const strength = strengthLabel(newPw)

  const { mutate, isPending } = useMutation({
    mutationFn: () =>
      profileApi.changePassword({ current_password: current, new_password: newPw }),
    onSuccess: () => {
      setDone(true)
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  const confirmMismatch = confirm.length > 0 && confirm !== newPw
  const canSubmit =
    current.length > 0 && newPw.length >= 8 && confirm === newPw && !isPending

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!canSubmit) return
    mutate()
  }

  // ── Success state ────────────────────────────────────────────────────────
  if (done) {
    return (
      <div className="max-w-[1200px] mx-auto px-4 py-12 text-center">
        <div className="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
          <ShieldCheck size={36} className="text-green-600" />
        </div>
        <h2 className="text-2xl font-bold text-slate-800 mb-2">Password Updated</h2>
        <p className="text-slate-500 mb-8 max-w-xs mx-auto">
          Your password has been changed successfully. Use your new password next time you log in.
        </p>
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Link to="/profile" className="btn-brand px-8 py-3 rounded-xl font-semibold text-center">
            Back to Profile
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6">
      <div className="max-w-2xl mx-auto">
      {/* ── Header ─────────────────────────────────────────────────────── */}
      <div className="flex items-center gap-3 mb-6">
        <Link
          to="/profile"
          className="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors"
          aria-label="Back"
        >
          <ChevronLeft size={20} className="text-slate-600" />
        </Link>
        <div>
          <h1 className="font-heading font-bold text-xl text-slate-800 leading-tight">
            Password &amp; Security
          </h1>
          <p className="text-xs text-slate-500">Change your account password</p>
        </div>
      </div>

      {/* ── Form card ──────────────────────────────────────────────────── */}
      <div className="card p-6">
        <div className="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
          <div className="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center">
            <Lock size={18} className="text-brand-600" />
          </div>
          <div>
            <p className="font-semibold text-slate-800 text-sm">Change Password</p>
            <p className="text-xs text-slate-400">Keep your account secure with a strong password</p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Current password */}
          <PasswordInput
            id="current-password"
            label="Current Password"
            value={current}
            onChange={setCurrent}
            placeholder="Enter your current password"
          />

          {/* New password + strength bar */}
          <div className="space-y-2">
            <PasswordInput
              id="new-password"
              label="New Password"
              value={newPw}
              onChange={setNewPw}
              placeholder="At least 8 characters"
              hint="Mix uppercase, numbers & symbols for a stronger password"
            />
            {newPw.length > 0 && (
              <div className="flex items-center gap-3">
                <div className="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full transition-all duration-300 ${strength.color}`}
                    style={{ width: `${strength.pct}%` }}
                  />
                </div>
                <span className="text-xs font-medium text-slate-500 w-12 text-right">
                  {strength.label}
                </span>
              </div>
            )}
          </div>

          {/* Confirm new password */}
          <div>
            <PasswordInput
              id="confirm-password"
              label="Confirm New Password"
              value={confirm}
              onChange={setConfirm}
              placeholder="Re-enter new password"
            />
            {confirmMismatch && (
              <p className="text-xs text-red-500 mt-1">Passwords do not match</p>
            )}
          </div>

          {/* Requirements hint */}
          <div className="bg-slate-50 rounded-xl p-3 text-xs text-slate-500 space-y-1">
            <p className="font-medium text-slate-600 mb-1">Requirements</p>
            <p className={newPw.length >= 8 ? 'text-green-600' : ''}>
              {newPw.length >= 8 ? '✓' : '•'} At least 8 characters
            </p>
            <p className={/[A-Z]/.test(newPw) ? 'text-green-600' : ''}>
              {/[A-Z]/.test(newPw) ? '✓' : '•'} One uppercase letter
            </p>
            <p className={/[0-9]/.test(newPw) ? 'text-green-600' : ''}>
              {/[0-9]/.test(newPw) ? '✓' : '•'} One number
            </p>
          </div>

          {/* Submit */}
          <button
            type="submit"
            disabled={!canSubmit}
            className="w-full btn-brand py-3 rounded-xl font-semibold disabled:opacity-50
                       disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-opacity"
          >
            {isPending ? (
              <>
                <Loader2 size={16} className="animate-spin" /> Updating…
              </>
            ) : (
              <>
                <ShieldCheck size={16} /> Update Password
              </>
            )}
          </button>
        </form>
      </div>

      {/* ── Forgot password link ────────────────────────────────────────── */}
      <p className="text-center text-sm text-slate-500 mt-5">
        Forgot your current password?{' '}
        <Link to="/forgot-password" className="text-brand-600 hover:underline font-medium">
          Reset via email
        </Link>
      </p>
      </div>{/* /max-w-2xl */}
    </div>
  )
}
