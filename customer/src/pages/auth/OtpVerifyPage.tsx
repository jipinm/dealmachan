import { useState, useRef, useEffect } from 'react'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { Loader2, RefreshCw } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'
import AuthLayout from '@/components/layout/AuthLayout'

const OTP_LENGTH = 6

export default function OtpVerifyPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const { setAuth } = useAuthStore()
  const phone: string = (location.state as { phone?: string })?.phone || ''

  const [otp, setOtp] = useState<string[]>(Array(OTP_LENGTH).fill(''))
  const [countdown, setCountdown] = useState(60)
  const inputRefs = useRef<HTMLInputElement[]>([])

  // Countdown timer
  useEffect(() => {
    if (countdown <= 0) return
    const t = setTimeout(() => setCountdown((c) => c - 1), 1_000)
    return () => clearTimeout(t)
  }, [countdown])

  const { mutate: verify, isPending } = useMutation({
    mutationFn: () => authApi.verifyOtp({ phone, otp: otp.join('') }),
    onSuccess: (res) => {
      const { customer, access_token, refresh_token } = res.data.data!
      setAuth(customer, access_token, refresh_token)
      toast.success('Phone verified!')
      navigate(customer.is_new_user ? '/onboarding' : '/dashboard', { replace: true })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const { mutate: resend, isPending: isResending } = useMutation({
    mutationFn: () => authApi.resendOtp(phone),
    onSuccess: () => {
      toast.success('New OTP sent!')
      setCountdown(60)
      setOtp(Array(OTP_LENGTH).fill(''))
      inputRefs.current[0]?.focus()
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  function handleChange(index: number, value: string) {
    const digit = value.replace(/\D/, '')
    if (!digit && value !== '') return
    const next = [...otp]
    next[index] = digit
    setOtp(next)
    if (digit && index < OTP_LENGTH - 1) {
      inputRefs.current[index + 1]?.focus()
    }
    // Auto-submit when all filled
    if (digit && next.every(Boolean)) {
      verify()
    }
  }

  function handleKeyDown(index: number, e: React.KeyboardEvent<HTMLInputElement>) {
    if (e.key === 'Backspace' && !otp[index] && index > 0) {
      inputRefs.current[index - 1]?.focus()
    }
  }

  function handlePaste(e: React.ClipboardEvent) {
    const text = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, OTP_LENGTH)
    if (text.length === OTP_LENGTH) {
      setOtp(text.split(''))
    }
  }

  return (
    <AuthLayout
      title="Verify Your Phone"
      subtitle={`We sent a ${OTP_LENGTH}-digit code to ${phone || 'your phone number'}`}
    >
      <div onPaste={handlePaste}>
        {/* OTP input boxes */}
        <div className="flex gap-3 justify-center mb-8">
          {otp.map((digit, i) => (
            <input
              key={i}
              ref={(el) => { if (el) inputRefs.current[i] = el }}
              type="text"
              inputMode="numeric"
              maxLength={1}
              value={digit}
              onChange={(e) => handleChange(i, e.target.value)}
              onKeyDown={(e) => handleKeyDown(i, e)}
              className={`w-12 h-14 text-center text-xl font-bold border-2 rounded-xl outline-none transition-all ${
                digit
                  ? 'border-brand-500 bg-brand-50 text-brand-700'
                  : 'border-slate-200 bg-white text-slate-900 focus:border-brand-400 focus:ring-2 focus:ring-brand-100'
              }`}
            />
          ))}
        </div>

        {/* Submit */}
        <button
          onClick={() => verify()}
          disabled={isPending || otp.some((d) => !d)}
          className="btn-primary w-full"
        >
          {isPending && <Loader2 size={17} className="animate-spin" />}
          {isPending ? 'Verifying…' : 'Verify Phone Number'}
        </button>

        {/* Resend timer */}
        <div className="text-center mt-6">
          {countdown > 0 ? (
            <p className="text-sm text-slate-500">
              Resend OTP in{' '}
              <span className="font-semibold text-slate-700">{countdown}s</span>
            </p>
          ) : (
            <button
              onClick={() => resend()}
              disabled={isResending}
              className="inline-flex items-center gap-1.5 text-sm text-brand-600 font-semibold hover:text-brand-700 transition-colors disabled:opacity-50"
            >
              {isResending ? <Loader2 size={14} className="animate-spin" /> : <RefreshCw size={14} />}
              Resend OTP
            </button>
          )}
        </div>

        {/* Wrong number */}
        <p className="text-center text-sm text-slate-500 mt-5">
          Wrong number?{' '}
          <Link to="/register" className="text-brand-600 font-semibold hover:text-brand-700 transition-colors">
            Go back
          </Link>
        </p>
      </div>
    </AuthLayout>
  )
}
