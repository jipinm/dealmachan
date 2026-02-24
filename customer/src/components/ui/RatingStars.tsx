import { Star } from 'lucide-react'

// ── Display mode ──────────────────────────────────────────────────────────

interface RatingStarsProps {
  /** 0–5, supports decimals for partial fill */
  rating: number
  /** Total number of stars. Default 5 */
  max?: number
  /** px size of each star icon. Default 16 */
  size?: number
  /** Show numeric label after stars */
  showValue?: boolean
  /** Show review count in parentheses */
  reviewCount?: number
  /** Extra class on the wrapper */
  className?: string
}

/**
 * Read-only star rating display.
 * Supports full, half, and empty stars via SVG clip-path.
 */
export function RatingStars({
  rating,
  max = 5,
  size = 16,
  showValue = false,
  reviewCount,
  className = '',
}: RatingStarsProps) {
  const clamped = Math.max(0, Math.min(max, rating))

  return (
    <span className={`inline-flex items-center gap-1 ${className}`}>
      <span className="flex items-center gap-[2px]">
        {Array.from({ length: max }, (_, i) => {
          const fill = Math.min(1, Math.max(0, clamped - i)) // 0, 0–1, or 1
          return <StarIcon key={i} fill={fill} size={size} />
        })}
      </span>
      {showValue && (
        <span className="font-semibold text-slate-700 text-sm leading-none">
          {clamped.toFixed(1)}
        </span>
      )}
      {reviewCount !== undefined && (
        <span className="text-slate-400 text-sm leading-none">
          ({reviewCount})
        </span>
      )}
    </span>
  )
}

// ── Single star icon (supports partial fill via SVG linearGradient) ────────

function StarIcon({ fill, size }: { fill: number; size: number }) {
  const id = `sf-${Math.random().toString(36).slice(2, 7)}`

  if (fill >= 1) {
    return <Star size={size} className="text-yellow-400 fill-yellow-400 flex-shrink-0" />
  }
  if (fill <= 0) {
    return <Star size={size} className="text-slate-200 fill-slate-200 flex-shrink-0" />
  }

  // Partial fill — use inline SVG gradient
  const pct = Math.round(fill * 100)
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      className="flex-shrink-0"
      aria-hidden
    >
      <defs>
        <linearGradient id={id} x1="0" x2="1" y1="0" y2="0">
          <stop offset={`${pct}%`} stopColor="#facc15" />
          <stop offset={`${pct}%`} stopColor="#e2e8f0" />
        </linearGradient>
      </defs>
      <path
        d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.877L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
        fill={`url(#${id})`}
      />
    </svg>
  )
}

// ── Interactive / input mode ───────────────────────────────────────────────

interface RatingInputProps {
  /** Current selected value (0 = unrated) */
  value: number
  onChange: (rating: number) => void
  max?: number
  size?: number
  className?: string
  disabled?: boolean
}

/**
 * Clickable star rating input.
 * Hover shows preview; click commits the value; clicking the same star clears it.
 */
export function RatingInput({
  value,
  onChange,
  max = 5,
  size = 24,
  className = '',
  disabled = false,
}: RatingInputProps) {
  return (
    <span
      className={`inline-flex items-center gap-1 ${disabled ? 'opacity-50 pointer-events-none' : ''} ${className}`}
      role="radiogroup"
      aria-label="Star rating"
    >
      {Array.from({ length: max }, (_, i) => {
        const starVal = i + 1
        const filled  = starVal <= value
        return (
          <button
            key={i}
            type="button"
            onClick={() => onChange(value === starVal ? 0 : starVal)}
            className="transition-transform hover:scale-110 focus:outline-none focus-visible:ring-2 ring-brand-400 rounded"
            aria-label={`Rate ${starVal} out of ${max}`}
            aria-pressed={starVal <= value}
          >
            <Star
              size={size}
              className={`transition-colors ${
                filled
                  ? 'text-yellow-400 fill-yellow-400'
                  : 'text-slate-300 fill-slate-100 hover:text-yellow-300 hover:fill-yellow-100'
              }`}
            />
          </button>
        )
      })}
    </span>
  )
}

// Default export = display mode for convenience
export default RatingStars
