import { useState, useRef, useEffect } from 'react'
import { useNavigate, useLocation } from 'react-router-dom'
import { Loader2, RefreshCw } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

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
      const { customer, tokens } = res.data.data!
      setAuth(customer, tokens.access_token, tokens.refresh_token)
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
    <div className="min-h-screen flex flex-col bg-gray-50">
      <div className="h-36 gradient-brand flex items-center justify-center">
        <div className="text-center text-white">
          <div className="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center mx-auto mb-2">
            <span className="font-heading font-bold text-xl">📱</span>
          </div>
          <h1 className="font-heading font-bold text-lg">Verify Your Phone</h1>
        </div>
      </div>

      <div className="flex-1 flex flex-col items-center px-4 -mt-6">
        <div className="w-full max-w-sm bg-white rounded-2xl shadow-lg p-6">
          <h2 className="font-heading font-bold text-xl text-gray-900 mb-1">Enter OTP</h2>
          <p className="text-sm text-gray-500 mb-6">
            We sent a {OTP_LENGTH}-digit code to{' '}
            <span className="font-semibold text-gray-800">{phone || 'your phone'}</span>
          </p>

          {/* OTP inputs */}
          <div className="flex gap-2 justify-center mb-6" onPaste={handlePaste}>
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
                className={`w-11 h-13 text-center text-xl font-bold border-2 rounded-xl focus:outline-none transition ${
                  digit ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-200 focus:border-brand-400'
                }`}
              />
            ))}
          </div>

          <button
            onClick={() => verify()}
            disabled={isPending || otp.some((d) => !d)}
            className="btn-primary w-full py-3 flex items-center justify-center gap-2"
          >
            {isPending && <Loader2 size={16} className="animate-spin" />}
            {isPending ? 'Verifying…' : 'Verify OTP'}
          </button>

          {/* Resend */}
          <div className="flex items-center justify-center gap-2 mt-5 text-sm">
            {countdown > 0 ? (
              <p className="text-gray-400">Resend OTP in <span className="font-semibold text-gray-600">{countdown}s</span></p>
            ) : (
              <button
                onClick={() => resend()}
                disabled={isResending}
                className="flex items-center gap-1.5 text-brand-600 font-semibold hover:underline disabled:opacity-50"
              >
                <RefreshCw size={14} className={isResending ? 'animate-spin' : ''} />
                Resend OTP
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
