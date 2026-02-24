import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  Calendar, Plus, Trash2, Cake, Heart, Star, ChevronDown, Loader2, X,
} from 'lucide-react'
import {
  importantDaysApi,
  type EventType,
  type CreateImportantDayBody,
  MONTH_NAMES,
} from '@/api/endpoints/importantDays'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

// ── Event type config ─────────────────────────────────────────────────────

const EVENT_ICONS: Record<EventType | string, React.ElementType> = {
  Birthday:    Cake,
  Anniversary: Heart,
  Others:      Star,
}
const EVENT_COLORS: Record<EventType | string, string> = {
  Birthday:    'bg-pink-100 text-pink-600',
  Anniversary: 'bg-rose-100 text-rose-500',
  Others:      'bg-amber-100 text-amber-600',
}

// ── Add event form ────────────────────────────────────────────────────────

function AddEventForm({ onClose }: { onClose: () => void }) {
  const qc = useQueryClient()
  const [type,     setType]     = useState<EventType>('Birthday')
  const [specify,  setSpecify]  = useState('')
  const [name,     setName]     = useState('')
  const [day,      setDay]      = useState('')
  const [month,    setMonth]    = useState('')

  const { mutate, isPending } = useMutation({
    mutationFn: () => {
      const body: CreateImportantDayBody = {
        event_type:  type,
        event_day:   Number(day),
        event_month: Number(month),
        ...(specify.trim() ? { event_specify: specify.trim() } : {}),
        ...(name.trim()    ? { person_name:   name.trim()    } : {}),
      }
      return importantDaysApi.add(body)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['important-days'] })
      toast.success('Event added!')
      onClose()
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (!day || !month) { toast.error('Please select a day and month.'); return }
    mutate()
  }

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center p-4"
      onClick={onClose}
    >
      <div
        className="bg-white rounded-3xl w-full max-w-sm p-6 animate-fade-slide-up"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between mb-5">
          <h2 className="font-heading font-bold text-lg text-slate-800">Add Important Day</h2>
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600">
            <X size={15} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Event type */}
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1.5">Event Type</label>
            <div className="grid grid-cols-3 gap-2">
              {(['Birthday', 'Anniversary', 'Others'] as EventType[]).map((t) => {
                const Icon = EVENT_ICONS[t]
                return (
                  <button
                    key={t}
                    type="button"
                    onClick={() => setType(t)}
                    className={`flex flex-col items-center gap-1 p-3 rounded-2xl border-2 text-xs font-medium transition-all ${
                      type === t
                        ? 'border-brand-400 bg-brand-50 text-brand-700'
                        : 'border-slate-200 text-slate-500'
                    }`}
                  >
                    <Icon size={18} />
                    {t}
                  </button>
                )
              })}
            </div>
          </div>

          {/* Specify (Others only) */}
          {type === 'Others' && (
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">Describe the occasion *</label>
              <input
                type="text"
                value={specify}
                onChange={(e) => setSpecify(e.target.value)}
                placeholder="e.g. Graduation, Retirement…"
                className="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
                required
              />
            </div>
          )}

          {/* Person name */}
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1.5">
              For whom? <span className="font-normal text-slate-400">(optional)</span>
            </label>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="e.g. Mum, Partner, Me…"
              className="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>

          {/* Day + Month */}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">Day</label>
              <div className="relative">
                <select
                  value={day}
                  onChange={(e) => setDay(e.target.value)}
                  required
                  className="w-full appearance-none border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 bg-white"
                >
                  <option value="">Day</option>
                  {Array.from({ length: 31 }, (_, i) => i + 1).map((d) => (
                    <option key={d} value={d}>{d}</option>
                  ))}
                </select>
                <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">Month</label>
              <div className="relative">
                <select
                  value={month}
                  onChange={(e) => setMonth(e.target.value)}
                  required
                  className="w-full appearance-none border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 bg-white"
                >
                  <option value="">Month</option>
                  {MONTH_NAMES.map((m, i) => (
                    <option key={m} value={i + 1}>{m}</option>
                  ))}
                </select>
                <ChevronDown size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
              </div>
            </div>
          </div>

          <button
            type="submit"
            disabled={isPending}
            className="btn-primary w-full flex items-center justify-center gap-2 disabled:opacity-50"
          >
            {isPending ? <><Loader2 size={15} className="animate-spin" /> Saving…</> : <><Plus size={15} /> Add Event</>}
          </button>
        </form>
      </div>
    </div>
  )
}

// ── ImportantDay card ─────────────────────────────────────────────────────

function DayCard({ item, onDelete }: { item: any; onDelete: () => void }) {
  const Icon = EVENT_ICONS[item.event_type] ?? Star
  const colorClass = EVENT_COLORS[item.event_type] ?? EVENT_COLORS.Others
  const label = item.event_type === 'Others' ? (item.event_specify ?? 'Other') : item.event_type

  // Compute days until next occurrence
  const now = new Date()
  const thisYear = now.getFullYear()
  let next = new Date(thisYear, (item.event_month as number) - 1, item.event_day as number)
  if (next < now) next = new Date(thisYear + 1, (item.event_month as number) - 1, item.event_day as number)
  const daysUntil = Math.ceil((next.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

  return (
    <div className="group bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-brand-100 transition-all duration-200 overflow-hidden flex flex-col">
      {/* Colour top band */}
      <div className={`h-1 w-full ${
        item.event_type === 'Birthday'    ? 'bg-gradient-to-r from-pink-400 to-rose-400' :
        item.event_type === 'Anniversary' ? 'bg-gradient-to-r from-rose-400 to-red-400' :
                                            'bg-gradient-to-r from-amber-400 to-orange-400'
      }`} />

      <div className="p-4 flex items-start gap-3 flex-1">
        <div className={`w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 ${colorClass}`}>
          <Icon size={20} />
        </div>

        <div className="flex-1 min-w-0">
          <p className="font-semibold text-slate-800 text-sm leading-snug">{label}</p>
          {item.person_name && (
            <p className="text-xs text-slate-500 mt-0.5">For {item.person_name}</p>
          )}
          <p className="text-xs text-slate-400 mt-1 font-medium">
            {item.event_day} {MONTH_NAMES[(item.event_month as number) - 1]}
          </p>
        </div>

        <div className="flex flex-col items-end gap-2 flex-shrink-0">
          {daysUntil <= 30 && (
            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
              daysUntil <= 7 ? 'bg-red-50 text-red-500 border border-red-100' :
                               'bg-brand-50 text-brand-600 border border-brand-100'
            }`}>
              {daysUntil === 0 ? 'Today!' : `${daysUntil}d`}
            </span>
          )}
          <button
            onClick={onDelete}
            className="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-400 transition-colors border border-slate-100"
            aria-label="Remove event"
          >
            <Trash2 size={13} />
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────

export default function ImportantDaysPage() {
  const qc = useQueryClient()
  const [showForm, setShowForm] = useState(false)

  const { data: days = [], isLoading } = useQuery({
    queryKey: ['important-days'],
    queryFn: () => importantDaysApi.list(),
    staleTime: 60_000,
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => importantDaysApi.remove(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['important-days'] })
      toast.success('Event removed.')
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  return (
    <div className="max-w-[1200px] mx-auto pb-10">
      {/* Header */}
      <div className="px-4 pt-6 pb-4">
        <div className="flex items-start justify-between">
          <div>
            <h1 className="font-heading font-bold text-2xl text-slate-800 mb-1 flex items-center gap-2">
              <Calendar size={22} className="text-brand-500" /> Important Days
            </h1>
            <p className="text-sm text-slate-500">
              Save birthdays &amp; anniversaries to get notified about special deals in advance.
            </p>
          </div>
          <button
            onClick={() => setShowForm(true)}
            className="btn-primary !py-2.5 !px-4 flex items-center gap-1.5 flex-shrink-0 ml-3"
          >
            <Plus size={16} /> Add
          </button>
        </div>
      </div>

      {/* Info banner */}
      <div className="mx-4 mb-5 bg-brand-50 border border-brand-100 rounded-2xl p-4 flex items-start gap-3">
        <Calendar size={16} className="text-brand-500 mt-0.5 flex-shrink-0" />
        <p className="text-xs text-brand-700 leading-relaxed">
          We'll match your special occasions with relevant merchant offers — restaurants, gift shops, spas — so you never miss a perfect deal.
        </p>
      </div>

      {/* List */}
      <div className="px-4">
        {isLoading ? (
          <div className="space-y-3">
            {[1, 2, 3].map((i) => <div key={i} className="h-16 skeleton rounded-2xl" />)}
          </div>
        ) : days.length === 0 ? (
          <div className="text-center py-16">
            <div className="w-20 h-20 bg-slate-100 rounded-3xl flex items-center justify-center mx-auto mb-4">
              <Calendar size={32} className="text-slate-300" />
            </div>
            <p className="font-semibold text-slate-500 mb-1">No events saved yet</p>
            <p className="text-sm text-slate-400 mb-5">
              Add your first important day to get personalised deals!
            </p>
            <button
              onClick={() => setShowForm(true)}
              className="btn-primary !px-6 inline-flex items-center gap-2"
            >
              <Plus size={15} /> Add First Event
            </button>
          </div>
        ) : (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {(days as any[]).map((item) => (
              <DayCard
                key={item.id}
                item={item}
                onDelete={() => deleteMutation.mutate(item.id)}
              />
            ))}
          </div>
        )}
      </div>

      {/* Add form modal */}
      {showForm && <AddEventForm onClose={() => setShowForm(false)} />}
    </div>
  )
}

