import { Link } from 'react-router-dom'
import { CreditCard } from 'lucide-react'

interface NoCardPromptProps {
  title?: string
  message?: string
  ctaText?: string
  ctaTo?: string
}

export default function NoCardPrompt({
  title = 'DealMachan Card Required',
  message = 'You need an activated DealMachan card to access this feature.',
  ctaText = 'Learn How to Get a Card',
  ctaTo = '/loyalty-cards',
}: NoCardPromptProps) {
  return (
    <div className="site-container py-14">
      <div className="max-w-xl mx-auto rounded-3xl border border-amber-200 bg-amber-50 p-8 text-center">
        <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
          <CreditCard size={24} />
        </div>
        <h1 className="text-xl font-bold text-amber-900">{title}</h1>
        <p className="mt-2 text-sm text-amber-800">{message}</p>

        <Link
          to={ctaTo}
          className="mt-6 inline-flex items-center justify-center rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-amber-700"
        >
          {ctaText}
        </Link>
      </div>
    </div>
  )
}
