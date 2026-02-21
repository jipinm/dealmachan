import { useState, useEffect } from 'react'
import { Zap } from 'lucide-react'
import { Link } from 'react-router-dom'

export interface FlashDiscount {
  id: number
  name: string
  discount_percentage: number
  starts_at: string
  ends_at: string
  business_name: string
  business_logo?: string | null
  store_name?: string | null
  merchant_id?: number
}

function useCountdown(endsAt: string) {
  const [secs, setSecs] = useState(() => Math.max(0, Math.floor((new Date(endsAt).getTime() - Date.now()) / 1000)))

  useEffect(() => {
    if (secs <= 0) return
    const t = setInterval(() => setSecs((s) => Math.max(0, s - 1)), 1000)
    return () => clearInterval(t)
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  const h = Math.floor(secs / 3600)
  const m = Math.floor((secs % 3600) / 60)
  const s = secs % 60
  return { h, m, s, expired: secs === 0 }
}

function Pad({ n }: { n: number }) {
  return <span>{String(n).padStart(2, '0')}</span>
}

/**
 * Horizontal scrolling flash discount chip with live countdown.
 */
export function FlashDiscountChip({ deal }: { deal: FlashDiscount }) {
  const { h, m, s, expired } = useCountdown(deal.ends_at)

  if (expired) return null

  return (
    <Link
      to={`/merchants/${deal.merchant_id ?? ''}`}
      className="shrink-0 flex flex-col gap-1.5 p-3 bg-gradient-to-br from-cta-500/90 to-cta-600 rounded-2xl text-white w-44 shadow-md"
    >
      {/* Header */}
      <div className="flex items-center gap-1.5">
        <Zap size={14} className="fill-white text-white" />
        <span className="font-heading font-bold text-lg leading-none">{deal.discount_percentage}% OFF</span>
      </div>

      {/* Merchant */}
      <p className="text-white/80 text-xs font-medium truncate">{deal.store_name || deal.business_name}</p>

      {/* Countdown */}
      <div className="flex items-center gap-0.5 bg-black/20 rounded-lg px-2 py-1 text-xs font-mono font-bold self-start">
        {h > 0 && <><Pad n={h} /><span className="mx-0.5 opacity-60">:</span></>}
        <Pad n={m} />
        <span className="mx-0.5 opacity-60">:</span>
        <Pad n={s} />
      </div>
    </Link>
  )
}

/**
 * Compact list row for flash discounts.
 */
export function FlashDiscountRow({ deal }: { deal: FlashDiscount }) {
  const { h, m, s, expired } = useCountdown(deal.ends_at)

  if (expired) return null

  return (
    <Link
      to={`/merchants/${deal.merchant_id ?? ''}`}
      className="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors"
    >
      <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-cta-500 to-cta-600 flex items-center justify-center shrink-0">
        <Zap size={18} className="fill-white text-white" />
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-sm font-semibold text-gray-900 truncate">{deal.name}</p>
        <p className="text-xs text-gray-500 truncate">{deal.store_name || deal.business_name}</p>
      </div>
      <div className="shrink-0 text-right">
        <p className="text-base font-heading font-bold text-cta-500">{deal.discount_percentage}%</p>
        <p className="text-[10px] font-mono text-gray-400">
          {h > 0 ? `${h}:` : ''}{String(m).padStart(2,'0')}:{String(s).padStart(2,'0')}
        </p>
      </div>
    </Link>
  )
}
