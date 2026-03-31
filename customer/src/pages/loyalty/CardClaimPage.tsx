import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import {
  CreditCard, KeyRound, ChevronLeft, Loader2, CheckCircle2, AlertCircle, Clock,
} from 'lucide-react'
import { profileApi } from '@/api/endpoints/profile'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

type Step = 'enter-number' | 'enter-code' | 'done'

export default function CardClaimPage() {
  const navigate   = useNavigate()
  const qc         = useQueryClient()

  const [step,       setStep]       = useState<Step>('enter-number')
  const [cardNumber, setCardNumber] = useState('')
  const [authCode,   setAuthCode]   = useState('')
  const [expiresAt,  setExpiresAt]  = useState<string | null>(null)

  // ── Step 1: Request auth code ────────────────────────────────────────────

  const requestMutation = useMutation({
    mutationFn: () => profileApi.requestCardAuthCode(cardNumber.trim().toUpperCase()),
    onSuccess: (res) => {
      const data = (res as any)?.data?.data
      setExpiresAt(data?.expires_at ?? null)
      toast.success('Auth code sent to the card issuer!')
      setStep('enter-code')
    },
    onError: (err: any) => {
      const code = err?.response?.data?.code
      if (code === 'NOT_PREPRINTED') {
        // Non-preprinted card assigned to this customer — activate directly (no auth code needed)
        activateMutation.mutate()
      } else {
        toast.error(getApiError(err))
      }
    },
  })

  // ── Step 2: Activate with auth code ─────────────────────────────────────

  const activateMutation = useMutation({
    mutationFn: () =>
      profileApi.activateCard(cardNumber.trim().toUpperCase(), authCode.trim() || undefined),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['my-card'] })
      qc.invalidateQueries({ queryKey: ['my-card-required-guard'] })
      setStep('done')
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const handleRequestCode = (e: React.FormEvent) => {
    e.preventDefault()
    if (!cardNumber.trim()) {
      toast.error('Please enter your card number.')
      return
    }
    requestMutation.mutate()
  }

  const handleActivate = (e: React.FormEvent) => {
    e.preventDefault()
    if (!authCode.trim()) {
      toast.error('Please enter the authorization code.')
      return
    }
    activateMutation.mutate()
  }

  // ── Success state ────────────────────────────────────────────────────────

  if (step === 'done') {
    return (
      <div className="min-h-[60vh] flex items-center justify-center p-4">
        <div className="max-w-sm w-full bg-white rounded-3xl shadow-brand p-8 text-center animate-fade-slide-up">
          <div className="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
            <CheckCircle2 size={32} className="text-green-500" />
          </div>
          <h2 className="font-heading font-bold text-xl text-slate-800 mb-2">Card Activated!</h2>
          <p className="text-sm text-slate-500 mb-6">
            Your card is now active. You have full access to deals, coupons, and loyalty benefits.
          </p>
          <button
            onClick={() => navigate('/profile/card', { replace: true })}
            className="btn-primary w-full"
          >
            View My Card
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-lg mx-auto px-4 py-6 pb-12">
      {/* Back */}
      <div className="flex items-center gap-3 mb-6">
        <button
          onClick={() => navigate(-1)}
          className="p-2 hover:bg-gray-100 rounded-xl"
        >
          <ChevronLeft size={20} className="text-gray-600" />
        </button>
        <div>
          <h1 className="font-heading font-bold text-xl text-gray-900">Claim Your Card</h1>
          <p className="text-sm text-slate-500">Enter your pre-printed card number</p>
        </div>
      </div>

      {/* Step indicator */}
      {(() => {
        const s         = step as string
        const step1Done   = s === 'enter-code' || s === 'done'
        const step2Done   = s === 'done'
        const step1Active = s === 'enter-number'
        const step2Active = s === 'enter-code'
        return (
          <div className="flex items-center gap-3 mb-6">
            <StepDot active={step1Active} done={step1Done} label="Card Number" />
            <div className="flex-1 h-px bg-slate-200" />
            <StepDot active={step2Active} done={step2Done} label="Auth Code" />
          </div>
        )
      })()}

      {/* ─── Step 1 ──────────────────────────────────────────────────────── */}
      {step === 'enter-number' && (
        <div className="bg-white rounded-3xl shadow-brand p-6 animate-fade-slide-up">
          <div className="flex items-center gap-3 mb-5">
            <div className="w-10 h-10 rounded-2xl gradient-brand flex items-center justify-center shadow-brand flex-shrink-0">
              <CreditCard size={18} className="text-white" />
            </div>
            <div>
              <h2 className="font-semibold text-slate-800 text-sm">Enter Card Number</h2>
              <p className="text-xs text-slate-500">Found on the back of your pre-printed card</p>
            </div>
          </div>

          <form onSubmit={handleRequestCode} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                Card Number
              </label>
              <input
                type="text"
                value={cardNumber}
                onChange={(e) => setCardNumber(e.target.value.toUpperCase())}
                placeholder="e.g. DM-2024-00001"
                className="input-field w-full font-mono tracking-widest"
                autoComplete="off"
                autoCorrect="off"
                spellCheck={false}
                maxLength={30}
              />
            </div>

            <div className="bg-amber-50 border border-amber-200 rounded-2xl p-3 flex items-start gap-2.5">
              <AlertCircle size={15} className="text-amber-500 mt-0.5 flex-shrink-0" />
              <p className="text-xs text-amber-700 leading-relaxed">
                An authorization code will be sent to the merchant or admin who issued this card.
                You'll need to contact them to get the code.
              </p>
            </div>

            <button
              type="submit"
              disabled={requestMutation.isPending || !cardNumber.trim()}
              className="btn-primary w-full flex items-center justify-center gap-2"
            >
              {requestMutation.isPending ? (
                <Loader2 size={16} className="animate-spin" />
              ) : (
                <KeyRound size={16} />
              )}
              Request Auth Code
            </button>
          </form>
        </div>
      )}

      {/* ─── Step 2 ──────────────────────────────────────────────────────── */}
      {step === 'enter-code' && (
        <div className="bg-white rounded-3xl shadow-brand p-6 animate-fade-slide-up">
          <div className="flex items-center gap-3 mb-5">
            <div className="w-10 h-10 rounded-2xl gradient-brand flex items-center justify-center shadow-brand flex-shrink-0">
              <KeyRound size={18} className="text-white" />
            </div>
            <div>
              <h2 className="font-semibold text-slate-800 text-sm">Enter Authorization Code</h2>
              <p className="text-xs text-slate-500">Get the 6-digit code from your card issuer</p>
            </div>
          </div>

          {/* Card number display */}
          <div className="bg-slate-50 rounded-2xl px-4 py-3 flex items-center justify-between mb-5">
            <div>
              <p className="text-[10px] uppercase tracking-wide text-slate-400 font-semibold mb-0.5">Card Number</p>
              <p className="text-sm font-mono font-semibold text-slate-700">{cardNumber.trim().toUpperCase()}</p>
            </div>
            <button
              type="button"
              onClick={() => { setStep('enter-number'); setAuthCode('') }}
              className="text-xs text-brand-500 hover:underline font-medium"
            >
              Change
            </button>
          </div>

          {expiresAt && (
            <div className="flex items-center gap-2 text-xs text-slate-500 bg-slate-50 rounded-xl px-3 py-2 mb-5">
              <Clock size={13} className="text-slate-400" />
              Code expires at: <span className="font-semibold text-slate-700">{new Date(expiresAt).toLocaleTimeString()}</span>
            </div>
          )}

          <form onSubmit={handleActivate} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">
                Authorization Code
              </label>
              <input
                type="text"
                value={authCode}
                onChange={(e) => setAuthCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                placeholder="6-digit code"
                className="input-field w-full font-mono tracking-widest text-center text-lg"
                inputMode="numeric"
                autoComplete="one-time-code"
                maxLength={6}
              />
            </div>

            <button
              type="submit"
              disabled={activateMutation.isPending || authCode.trim().length !== 6}
              className="btn-primary w-full flex items-center justify-center gap-2"
            >
              {activateMutation.isPending ? (
                <Loader2 size={16} className="animate-spin" />
              ) : (
                <CheckCircle2 size={16} />
              )}
              Activate Card
            </button>

            {/* Resend */}
            <button
              type="button"
              onClick={() => requestMutation.mutate()}
              disabled={requestMutation.isPending}
              className="w-full text-sm text-slate-500 hover:text-brand-500 underline underline-offset-2"
            >
              {requestMutation.isPending ? 'Resending…' : 'Resend auth code'}
            </button>
          </form>
        </div>
      )}

      {/* Option B link */}
      <p className="text-center text-sm text-slate-400 mt-8">
        Don't have a pre-printed card?{' '}
        <button
          type="button"
          onClick={() => navigate('/loyalty/select-card')}
          className="text-brand-500 font-semibold hover:underline"
        >
          Select a card plan instead
        </button>
      </p>
    </div>
  )
}

function StepDot({ active, done, label }: { active: boolean; done: boolean; label: string }) {
  return (
    <div className="flex flex-col items-center gap-1">
      <div className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all ${
        done
          ? 'bg-green-500 text-white'
          : active
            ? 'gradient-brand text-white shadow-brand'
            : 'bg-slate-200 text-slate-400'
      }`}>
        {done ? <CheckCircle2 size={14} /> : active ? '●' : '○'}
      </div>
      <span className={`text-[10px] font-semibold uppercase tracking-wide ${
        active ? 'text-brand-600' : done ? 'text-green-600' : 'text-slate-400'
      }`}>{label}</span>
    </div>
  )
}
