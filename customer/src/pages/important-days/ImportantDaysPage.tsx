import { Calendar } from 'lucide-react'

export default function ImportantDaysPage() {
  return (
    <div className="max-w-2xl mx-auto px-4 py-12 text-center">
      <div className="w-20 h-20 bg-amber-50 rounded-3xl flex items-center justify-center mx-auto mb-5">
        <Calendar size={36} className="text-amber-600" />
      </div>
      <h1 className="font-heading font-bold text-2xl text-slate-800 mb-2">Important Days</h1>
      <p className="text-slate-500 max-w-sm mx-auto">
        Add important dates like birthdays and anniversaries to get notified about special deals
        well in advance so you never miss the perfect gift opportunity.
      </p>
    </div>
  )
}
