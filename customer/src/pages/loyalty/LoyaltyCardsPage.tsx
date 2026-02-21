import { Gift } from 'lucide-react'

export default function LoyaltyCardsPage() {
  return (
    <div className="max-w-2xl mx-auto px-4 py-12 text-center">
      <div className="w-20 h-20 bg-green-50 rounded-3xl flex items-center justify-center mx-auto mb-5">
        <Gift size={36} className="text-green-600" />
      </div>
      <h1 className="font-heading font-bold text-2xl text-slate-800 mb-2">Loyalty Cards</h1>
      <p className="text-slate-500 max-w-sm mx-auto">
        Your stamp cards and loyalty rewards from partner stores will appear here.
        Visit a participating store to start collecting stamps!
      </p>
    </div>
  )
}
