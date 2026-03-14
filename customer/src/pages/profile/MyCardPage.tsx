import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, CreditCard, CheckCircle, Loader2, Info, Mail, KeyRound, Star, Users, ArrowUpRight } from 'lucide-react'
import { profileApi } from '@/api/endpoints/profile'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import { getImageUrl } from '@/lib/imageUrl'
import TapReveal from '@/components/ui/TapReveal'
import toast from 'react-hot-toast'

// Type for the multi-step activation flow
type ActivationStep = 'enter_number' | 'code_requested' | 'enter_code'

export default function MyCardPage() {
  const { customer } = useAuthStore()
  const qc = useQueryClient()
  const [cardNumber, setCardNumber]   = useState('')
  const [authCode, setAuthCode]       = useState('')
  const [step, setStep]               = useState<ActivationStep>('enter_number')

  const { data, isLoading } = useQuery({
    queryKey: ['my-card'],
    queryFn: () => profileApi.getCard().then((r) => r.data.data),
    staleTime: 300_000,
  })

  const requestCodeMutation = useMutation({
    mutationFn: () => profileApi.requestCardAuthCode(cardNumber.replace(/\s/g, '')),
    onSuccess: () => {
      setStep('code_requested')
      toast.success('Auth code requested! The card issuer will receive it shortly.')
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  const activateMutation = useMutation({
    mutationFn: () => profileApi.activateCard(
      cardNumber.replace(/\s/g, ''),
      step === 'enter_code' ? authCode.replace(/\s/g, '') : undefined,
    ),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['my-card'] })
      toast.success('Card activated!')
      setCardNumber('')
      setAuthCode('')
      setStep('enter_number')
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  const card = data as any

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 size={24} className="animate-spin text-brand-500" />
      </div>
    )
  }

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6 pb-10">
      {/* Header */}
      <div className="flex items-center gap-3 mb-6">
        <Link to="/profile" className="p-2 hover:bg-gray-100 rounded-xl">
          <ChevronLeft size={20} className="text-gray-600" />
        </Link>
        <h1 className="font-heading font-bold text-xl text-gray-900">My Card</h1>
      </div>

      {card ? (
        /* ── Card display ─────────────────────────────────────────────── */
        <>
          <div className="rounded-3xl overflow-hidden shadow-brand mb-6 relative">
            {card.card_image ? (
              <img src={getImageUrl(card.card_image)} alt="Deal Machan Card" loading="lazy" className="w-full" />
            ) : (
              <div className="gradient-brand px-6 pt-8 pb-6 min-h-48">
                {/* Decorative circles */}
                <div className="absolute top-0 right-0 w-48 h-48 rounded-full bg-white/10 -translate-y-1/2 translate-x-1/3" />
                <div className="absolute bottom-0 left-0 w-32 h-32 rounded-full bg-white/10 translate-y-1/3 -translate-x-1/4" />

                <div className="relative">
                  <div className="flex items-center justify-between mb-8">
                    <p className="text-white/60 text-xs uppercase tracking-widest font-semibold">Deal Machan</p>
                    <CreditCard size={22} className="text-white/80" />
                  </div>

                  {card.card_number ? (
                    <TapReveal
                      value={card.card_number}
                      masked={card.card_number.replace(/(.{4})/g, '$1 ').trim().replace(/.(?=.{4})/g, '\u2022')}
                      formatRevealed={(v) => v.replace(/(.{4})/g, '$1 ').trim()}
                      className="mb-4"
                    />
                  ) : (
                    <p className="font-mono font-bold text-white/40 text-xl tracking-[0.2em] mb-4">
                      {'\u2022\u2022\u2022\u2022 \u2022\u2022\u2022\u2022 \u2022\u2022\u2022\u2022'}
                    </p>
                  )}

                  <div className="flex items-end justify-between">
                    <div>
                      <p className="text-white/50 text-[10px] uppercase tracking-wider">Cardholder</p>
                      <p className="text-white font-semibold text-sm">{customer?.name}</p>
                    </div>
                    <div className="text-right">
                      <p className="text-white/50 text-[10px] uppercase tracking-wider">Type</p>
                      <p className="text-white font-semibold text-sm capitalize">{card.card_variant ?? 'Standard'}</p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Card info */}
          <div className="card p-5 mb-5 space-y-3">
            {/* Config name + classification badge */}
            {(card.config_name || card.classification) && (
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-500">Plan</span>
                <div className="flex items-center gap-2">
                  {card.config_name && (
                    <span className="text-xs font-semibold text-gray-900">{card.config_name}</span>
                  )}
                  {card.classification && ['gold', 'platinum', 'diamond'].includes(card.classification) && (
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-semibold capitalize">
                      <Star size={9} className="fill-amber-500 text-amber-500" /> {card.classification}
                    </span>
                  )}
                  {card.classification === 'silver' && (
                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-semibold capitalize">
                      Silver
                    </span>
                  )}
                </div>
              </div>
            )}
            <div className="flex items-center justify-between">
              <span className="text-xs text-gray-500">Status</span>
              <span className={`flex items-center gap-1 text-xs font-semibold ${
                card.status === 'active' ? 'text-emerald-600' : 'text-red-500'
              }`}>
                {card.status === 'active' ? <CheckCircle size={12} /> : null}
                {card.status?.charAt(0).toUpperCase() + card.status?.slice(1)}
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-xs text-gray-500">Card Number</span>
              {card.card_number ? (
                <TapReveal
                  value={card.card_number}
                  masked={`\u2022\u2022\u2022\u2022 ${card.card_number.slice(-4)}`}
                  formatRevealed={(v) => v.replace(/(.{4})/g, '$1 ').trim()}
                  variant="inline"
                />
              ) : (
                <span className="text-xs font-mono font-semibold text-gray-900">\u2014</span>
              )}
            </div>
            <div className="flex items-center justify-between">
              <span className="text-xs text-gray-500">Type</span>
              <span className="text-xs font-semibold text-gray-900 capitalize">{card.card_variant ?? 'Standard'}</span>
            </div>
            {card.expiry_date && (
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-500">Expires</span>
                <span className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full ${
                  card.expiry_status === 'expired'
                    ? 'bg-red-100 text-red-700'
                    : card.expiry_status === 'expiring_soon'
                    ? 'bg-amber-100 text-amber-700'
                    : 'bg-green-100 text-green-700'
                }`}>
                  {card.expiry_status === 'expired' ? '⚠ Expired' :
                   card.expiry_status === 'expiring_soon' ? `⏰ ${card.days_remaining}d left` :
                   new Date(card.expiry_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                </span>
              </div>
            )}
            {card.generated_at && (
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-500">Issued</span>
                <span className="text-xs text-gray-700">
                  {new Date(card.generated_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                </span>
              </div>
            )}
          </div>

          {/* Select new card CTA if expired */}
          {card.expiry_status === 'expired' && (
            <Link
              to="/loyalty/select-card"
              className="flex items-center justify-between bg-red-50 border border-red-200 rounded-2xl p-4 mb-5 hover:shadow-md transition-shadow"
            >
              <div>
                <p className="font-bold text-sm text-red-700">Card Expired</p>
                <p className="text-xs text-red-500 mt-0.5">Select a new card to continue enjoying benefits</p>
              </div>
              <ArrowUpRight size={18} className="text-red-400 flex-shrink-0" />
            </Link>
          )}

          {/* Partner logos */}
          {card.partners && card.partners.length > 0 && (
            <div className="card p-5 mb-5">
              <h3 className="font-semibold text-sm text-gray-800 mb-3 flex items-center gap-2">
                <Users size={14} className="text-slate-500" /> Partner Merchants
              </h3>
              {/* Premium partners (larger tiles) */}
              {card.partners.filter((p: any) => p.partner_type === 'premium').length > 0 && (
                <div className="flex flex-wrap gap-3 mb-3">
                  {card.partners
                    .filter((p: any) => p.partner_type === 'premium')
                    .map((p: any) => (
                      <a
                        key={p.id}
                        href={p.url ?? undefined}
                        target={p.url ? '_blank' : undefined}
                        rel="noopener noreferrer"
                        title=""
                        className="w-16 h-16 rounded-xl border border-slate-200 bg-white flex items-center justify-center overflow-hidden hover:shadow-md transition-shadow"
                      >
                        {p.partner_image ? (
                          <img
                            src={getImageUrl(p.partner_image)}
                            alt="Partner"
                            className="w-full h-full object-contain p-1"
                          />
                        ) : (
                          <span className="text-[10px] text-slate-500 text-center px-1 leading-tight">Partner</span>
                        )}
                      </a>
                    ))}
                </div>
              )}
              {/* Normal partners (smaller tiles) */}
              {card.partners.filter((p: any) => p.partner_type === 'normal').length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {card.partners
                    .filter((p: any) => p.partner_type === 'normal')
                    .map((p: any) => (
                      <a
                        key={p.id}
                        href={p.url ?? undefined}
                        target={p.url ? '_blank' : undefined}
                        rel="noopener noreferrer"
                        title=""
                        className="w-11 h-11 rounded-lg border border-slate-200 bg-white flex items-center justify-center overflow-hidden hover:shadow-sm transition-shadow"
                      >
                        {p.partner_image ? (
                          <img
                            src={getImageUrl(p.partner_image)}
                            alt="Partner"
                            className="w-full h-full object-contain p-0.5"
                          />
                        ) : (
                          <span className="text-[9px] text-slate-500 text-center px-0.5 leading-tight">Partner</span>
                        )}
                      </a>
                    ))}
                </div>
              )}
            </div>
          )}

          {card.status === 'inactive' && (
            <div className="bg-amber-50 border border-amber-100 rounded-2xl p-4 space-y-3">
              <div className="flex items-start gap-2">
                <Info size={14} className="text-amber-500 mt-0.5 shrink-0" />
                <p className="text-xs text-amber-700">
                  Your card is inactive. Enter the card number below and request an activation code, then activate it.
                </p>
              </div>
              <ActivationForm
                cardNumber={cardNumber}
                authCode={authCode}
                step={step}
                onCardNumberChange={setCardNumber}
                onAuthCodeChange={setAuthCode}
                onRequestCode={() => requestCodeMutation.mutate()}
                onActivate={() => activateMutation.mutate()}
                onProceedToCode={() => setStep('enter_code')}
                requestPending={requestCodeMutation.isPending}
                activatePending={activateMutation.isPending}
              />
            </div>
          )}
        </>
      ) : (
        /* ── No card — multi-step activation ─────────────────────────── */
        <>
          <div className="text-center py-10 mb-8">
            <div className="w-20 h-20 rounded-3xl gradient-brand flex items-center justify-center mx-auto mb-4 shadow-brand">
              <CreditCard size={32} className="text-white" />
            </div>
            <h2 className="font-heading font-bold text-xl text-gray-900 mb-2">No Card Found</h2>
            <p className="text-sm text-gray-500 max-w-xs mx-auto">
              Activate your pre-printed DealMachan card below or select a card plan.
            </p>
            <Link to="/loyalty/select-card" className="mt-4 inline-flex items-center gap-1.5 btn-primary !px-6 !py-2.5 !text-sm !rounded-xl">
              <CreditCard size={15} /> Choose a Card Plan <ArrowUpRight size={14} />
            </Link>
          </div>

          <div className="card p-5">
            <h3 className="font-semibold text-gray-900 text-sm mb-4">Activate a Card</h3>
            <ActivationForm
              cardNumber={cardNumber}
              authCode={authCode}
              step={step}
              onCardNumberChange={setCardNumber}
              onAuthCodeChange={setAuthCode}
              onRequestCode={() => requestCodeMutation.mutate()}
              onActivate={() => activateMutation.mutate()}
              onProceedToCode={() => setStep('enter_code')}
              requestPending={requestCodeMutation.isPending}
              activatePending={activateMutation.isPending}
            />
          </div>

          <p className="text-center text-xs text-gray-400 mt-4">
            Don't have a card?{' '}
            <a href={`mailto:${import.meta.env.VITE_SUPPORT_EMAIL}`} className="text-brand-600 hover:underline">
              Contact support
            </a>
          </p>
        </>
      )}
    </div>
  )
}

// ── ActivationForm sub-component ─────────────────────────────────────────────

interface ActivationFormProps {
  cardNumber: string
  authCode: string
  step: ActivationStep
  onCardNumberChange: (v: string) => void
  onAuthCodeChange: (v: string) => void
  onRequestCode: () => void
  onActivate: () => void
  onProceedToCode: () => void
  requestPending: boolean
  activatePending: boolean
}

function ActivationForm({
  cardNumber, authCode, step,
  onCardNumberChange, onAuthCodeChange,
  onRequestCode, onActivate, onProceedToCode,
  requestPending, activatePending,
}: ActivationFormProps) {
  const rawNumber = cardNumber.replace(/\s/g, '')

  return (
    <div className="space-y-4">
      {/* Card number input */}
      <div>
        <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">
          Card Number
        </label>
        <input
          type="text"
          className="input w-full font-mono tracking-widest"
          placeholder="0000 0000 0000 0000"
          value={cardNumber}
          maxLength={19}
          onChange={(e) => {
            const v = e.target.value.replace(/\D/g, '').slice(0, 16)
            onCardNumberChange(v.replace(/(.{4})/g, '$1 ').trim())
          }}
        />
      </div>

      {/* Step: initial — request code OR activate directly */}
      {step === 'enter_number' && (
        <div className="flex flex-col gap-2">
          <button
            onClick={onRequestCode}
            disabled={rawNumber.length < 8 || requestPending}
            className="w-full btn-secondary py-3 flex items-center justify-center gap-2 disabled:opacity-50"
          >
            {requestPending ? <Loader2 size={16} className="animate-spin" /> : <Mail size={16} />}
            Request Activation Code
          </button>
          <button
            onClick={onActivate}
            disabled={rawNumber.length < 8 || activatePending}
            className="w-full btn-primary py-3 flex items-center justify-center gap-2 disabled:opacity-50"
          >
            {activatePending ? <Loader2 size={16} className="animate-spin" /> : <CreditCard size={16} />}
            Activate Without Code
          </button>
          <p className="text-center text-xs text-gray-400">
            Already have a code?{' '}
            <button onClick={onProceedToCode} className="text-brand-600 hover:underline font-medium">
              Enter it here
            </button>
          </p>
        </div>
      )}

      {/* Step: code requested — show confirmation + code entry */}
      {(step === 'code_requested' || step === 'enter_code') && (
        <>
          {step === 'code_requested' && (
            <div className="flex items-start gap-2 bg-green-50 border border-green-100 rounded-xl p-3">
              <CheckCircle size={14} className="text-green-600 mt-0.5 shrink-0" />
              <p className="text-xs text-green-700">
                Activation code requested. The card issuer/merchant will receive it. Once you have the 6-digit code, enter it below.
              </p>
            </div>
          )}
          <div>
            <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">
              6-Digit Activation Code
            </label>
            <input
              type="text"
              inputMode="numeric"
              className="input w-full font-mono text-center tracking-[0.4em] text-lg"
              placeholder="• • • • • •"
              value={authCode}
              maxLength={6}
              onChange={(e) => onAuthCodeChange(e.target.value.replace(/\D/g, '').slice(0, 6))}
            />
          </div>
          <button
            onClick={onActivate}
            disabled={rawNumber.length < 8 || authCode.length !== 6 || activatePending}
            className="w-full btn-primary py-3 flex items-center justify-center gap-2 disabled:opacity-50"
          >
            {activatePending ? <Loader2 size={16} className="animate-spin" /> : <KeyRound size={16} />}
            Activate Card
          </button>
          <button
            onClick={onRequestCode}
            disabled={rawNumber.length < 8 || requestPending}
            className="w-full py-2 text-xs text-brand-600 hover:underline disabled:opacity-40"
          >
            {requestPending ? 'Sending…' : 'Resend code'}
          </button>
        </>
      )}
    </div>
  )
}
