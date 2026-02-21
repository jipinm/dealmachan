// ── AuthLayout — shared split-screen wrapper for all auth pages ───────────────
// Desktop: brand gradient panel (left, sticky) + scrollable form panel (right)
// Mobile:  form fills full width — header/footer provided by GuestShell
import type { ReactNode } from 'react'
import { Percent, Store, Zap, Gift } from 'lucide-react'

const BENEFITS = [
  { icon: Percent, text: '5,000+ active deals & coupons' },
  { icon: Store,   text: '500+ partner stores & restaurants' },
  { icon: Zap,     text: 'Flash deals with up to 80% off' },
  { icon: Gift,    text: 'Exclusive loyalty card perks' },
]

interface AuthLayoutProps {
  title: string
  subtitle?: string
  children: ReactNode
}

export default function AuthLayout({ title, subtitle, children }: AuthLayoutProps) {
  return (
    <div className="flex flex-col lg:flex-row">

      {/* ── Left brand panel — desktop only ─────────────────────── */}
      <div className="hidden lg:flex flex-col justify-between w-[420px] xl:w-[480px] gradient-brand p-10 text-white shrink-0 sticky top-16 h-[calc(100vh-4rem)]">
        <div>
          <h2 className="font-heading font-bold text-[1.875rem] leading-tight mb-3">
            Discover Amazing<br />Deals Near You
          </h2>
          <p className="text-white/70 text-base leading-relaxed mb-10">
            Join thousands of smart shoppers saving money every day with local merchants.
          </p>

          <div className="space-y-4">
            {BENEFITS.map(({ icon: Icon, text }) => (
              <div key={text} className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                  <Icon size={17} />
                </div>
                <span className="text-white/90 text-sm leading-snug">{text}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Stats bar */}
        <div className="border-t border-white/20 pt-6 grid grid-cols-3 gap-4">
          {[
            { value: '10K+',  label: 'Members' },
            { value: '500+',  label: 'Stores'  },
            { value: '₹50L+', label: 'Saved'   },
          ].map(({ value, label }) => (
            <div key={label}>
              <div className="font-heading font-bold text-xl">{value}</div>
              <div className="text-white/60 text-xs">{label}</div>
            </div>
          ))}
        </div>
      </div>

      {/* ── Right form panel ─────────────────────────────────────── */}
      <div className="flex-1 flex flex-col bg-[#f8f9fc]">

        {/* Form area */}
        <div className="flex-1 px-5 py-8 lg:px-10 lg:py-12">
          <div className="max-w-md mx-auto lg:mx-0 xl:ml-14">

            <h1 className="font-heading font-bold text-[1.625rem] text-slate-900 mb-1">{title}</h1>
            {subtitle && <p className="text-slate-500 text-sm mb-7 leading-relaxed">{subtitle}</p>}

            {children}
          </div>
        </div>
      </div>
    </div>
  )
}
