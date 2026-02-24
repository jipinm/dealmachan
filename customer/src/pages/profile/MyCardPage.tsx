import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, CreditCard, CheckCircle, Loader2, Info } from 'lucide-react'
import { profileApi } from '@/api/endpoints/profile'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import TapReveal from '@/components/ui/TapReveal'
import toast from 'react-hot-toast'

export default function MyCardPage() {
  const { customer } = useAuthStore()
  const qc = useQueryClient()
  const [cardNumber, setCardNumber] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['my-card'],
    queryFn: () => profileApi.getCard().then((r) => r.data.data),
    staleTime: 300_000,
  })

  const activateMutation = useMutation({
    mutationFn: () => profileApi.activateCard(cardNumber.replace(/\s/g, '')),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['my-card'] })
      toast.success('Card activated!')
      setCardNumber('')
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
              <img src={card.card_image} alt="Deal Machan Card" loading="lazy" className="w-full" />
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
            {card.generated_at && (
              <div className="flex items-center justify-between">
                <span className="text-xs text-gray-500">Issued</span>
                <span className="text-xs text-gray-700">
                  {new Date(card.generated_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                </span>
              </div>
            )}
          </div>

          {card.status === 'inactive' && (
            <div className="bg-amber-50 border border-amber-100 rounded-2xl p-4 flex items-start gap-2">
              <Info size={14} className="text-amber-500 mt-0.5 shrink-0" />
              <p className="text-xs text-amber-700">
                Your card is inactive. Please visit your nearest Deal Machan center or contact support to activate it.
              </p>
            </div>
          )}
        </>
      ) : (
        /* ── No card — activate form ─────────────────────────────────── */
        <>
          <div className="text-center py-10 mb-8">
            <div className="w-20 h-20 rounded-3xl gradient-brand flex items-center justify-center mx-auto mb-4 shadow-brand">
              <CreditCard size={32} className="text-white" />
            </div>
            <h2 className="font-heading font-bold text-xl text-gray-900 mb-2">No Card Found</h2>
            <p className="text-sm text-gray-500 max-w-xs mx-auto">
              You don't have a Deal Machan card yet. Activate a preprinted card or request one.
            </p>
          </div>

          <div className="card p-5">
            <h3 className="font-semibold text-gray-900 text-sm mb-3">Activate a Card</h3>
            <div className="space-y-3">
              <div>
                <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Card Number</label>
                <input
                  type="text"
                  className="input w-full font-mono tracking-widest"
                  placeholder="0000 0000 0000 0000"
                  value={cardNumber}
                  maxLength={19}
                  onChange={(e) => {
                    const v = e.target.value.replace(/\D/g, '').slice(0, 16)
                    setCardNumber(v.replace(/(.{4})/g, '$1 ').trim())
                  }}
                />
              </div>
              <button
                onClick={() => activateMutation.mutate()}
                disabled={cardNumber.replace(/\s/g, '').length < 12 || activateMutation.isPending}
                className="w-full btn-primary py-3 flex items-center justify-center gap-2 disabled:opacity-50"
              >
                {activateMutation.isPending ? <Loader2 size={16} className="animate-spin" /> : null}
                Activate Card
              </button>
            </div>
          </div>

          <p className="text-center text-xs text-gray-400 mt-4">
            Don't have a card?{' '}
            <a href="mailto:support@dealmachan.com" className="text-brand-600 hover:underline">
              Contact support
            </a>
          </p>
        </>
      )}
    </div>
  )
}
