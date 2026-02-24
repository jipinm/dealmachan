import { useState, useEffect, useRef } from 'react'
import { Eye, EyeOff, Copy, Check } from 'lucide-react'

interface TapRevealProps {
  /** The real sensitive value (e.g. full card number) */
  value: string
  /** What to show while hidden. Defaults to all-bullet mask keeping length */
  masked?: string
  /** Seconds to show the value before auto-masking. Default 6 */
  duration?: number
  /** Optional formatter applied to the revealed value (e.g. add spaces) */
  formatRevealed?: (v: string) => string
  /** Extra className on the root wrapper */
  className?: string
  /** Display style: 'inline' for text-only chip, 'block' for card-style with bar */
  variant?: 'inline' | 'block'
}

/**
 * TapReveal — shows sensitive text masked by default.
 * Tap/click to reveal for `duration` seconds, then auto-hides.
 * Includes a copy-to-clipboard button and a countdown progress bar.
 */
export default function TapReveal({
  value,
  masked,
  duration = 6,
  formatRevealed,
  className = '',
  variant = 'block',
}: TapRevealProps) {
  const [revealed,  setRevealed]  = useState(false)
  const [copied,    setCopied]    = useState(false)
  const [remaining, setRemaining] = useState(duration)
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null)

  // Default mask: replace all but last 4 chars with •, then split into groups of 4
  const maskedDisplay = masked ?? value.replace(/./g, '•').replace(/(.{4})/g, '$1 ').trim()
  const revealedDisplay = formatRevealed ? formatRevealed(value) : value

  function startCountdown() {
    setRemaining(duration)
    if (timerRef.current) clearInterval(timerRef.current)
    timerRef.current = setInterval(() => {
      setRemaining((r) => {
        if (r <= 1) {
          clearInterval(timerRef.current!)
          setRevealed(false)
          return duration
        }
        return r - 1
      })
    }, 1000)
  }

  function handleReveal() {
    if (revealed) {
      // Tap again to hide immediately
      if (timerRef.current) clearInterval(timerRef.current)
      setRevealed(false)
      setRemaining(duration)
    } else {
      setRevealed(true)
      startCountdown()
    }
  }

  async function handleCopy(e: React.MouseEvent) {
    e.stopPropagation()
    try {
      await navigator.clipboard.writeText(value)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    } catch {
      // clipboard not available
    }
  }

  // Cleanup on unmount
  useEffect(() => () => { if (timerRef.current) clearInterval(timerRef.current) }, [])

  if (variant === 'inline') {
    return (
      <span
        className={`inline-flex items-center gap-1.5 cursor-pointer select-none ${className}`}
        onClick={handleReveal}
        role="button"
        aria-label={revealed ? 'Hide value' : 'Tap to reveal'}
      >
        <span className={`font-mono text-xs font-semibold tracking-wider ${revealed ? 'text-slate-900' : 'text-slate-500'}`}>
          {revealed ? revealedDisplay : maskedDisplay}
        </span>
        {revealed ? (
          <>
            <button
              onClick={handleCopy}
              className="text-slate-400 hover:text-brand-600 transition-colors"
              aria-label="Copy to clipboard"
            >
              {copied ? <Check size={11} className="text-green-500" /> : <Copy size={11} />}
            </button>
            <EyeOff size={11} className="text-slate-300" />
          </>
        ) : (
          <Eye size={11} className="text-slate-400" />
        )}
      </span>
    )
  }

  // Block variant — card-style pill with countdown bar
  return (
    <div
      className={`relative rounded-2xl overflow-hidden cursor-pointer select-none transition-all ${
        revealed
          ? 'bg-slate-800 shadow-lg'
          : 'bg-white/10 hover:bg-white/20 backdrop-blur'
      } ${className}`}
      onClick={handleReveal}
      role="button"
      aria-label={revealed ? 'Tap to hide' : 'Tap to reveal card number'}
    >
      <div className="px-4 py-3 flex items-center justify-between gap-3">
        {/* Number */}
        <span
          className={`font-mono font-bold tracking-[0.2em] transition-all duration-300 ${
            revealed ? 'text-white text-xl' : 'text-white/70 text-xl blur-[3px]'
          }`}
        >
          {revealedDisplay}
        </span>

        {/* Actions */}
        <div className="flex items-center gap-1.5 flex-shrink-0">
          {revealed ? (
            <>
              <button
                onClick={handleCopy}
                className="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                aria-label="Copy card number"
              >
                {copied
                  ? <Check size={13} className="text-green-400" />
                  : <Copy size={13} className="text-white/70" />}
              </button>
              <div className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center">
                <span className="text-white/60 text-[11px] font-bold leading-none">{remaining}s</span>
              </div>
            </>
          ) : (
            <div className="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center">
              <Eye size={14} className="text-white/70" />
            </div>
          )}
        </div>
      </div>

      {/* Countdown bar — shrinks from right to left while revealed */}
      {revealed && (
        <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-white/10">
          <div
            className="h-full bg-brand-400 transition-all duration-1000 ease-linear"
            style={{ width: `${(remaining / duration) * 100}%` }}
          />
        </div>
      )}

      {/* Hint text when masked */}
      {!revealed && (
        <div className="px-4 pb-2 flex items-center gap-1">
          <Eye size={10} className="text-white/40" />
          <span className="text-white/40 text-[10px]">Tap to reveal</span>
        </div>
      )}
    </div>
  )
}
