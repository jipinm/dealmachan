import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { ChevronLeft, CheckCircle2, ClipboardList, Star } from 'lucide-react'
import { surveysApi, type SurveyQuestion, type SurveyResponses } from '@/api/endpoints/surveys'

// ── Star rating input ─────────────────────────────────────────────────────
function RatingInput({ scale, value, onChange }: { scale: number; value: number; onChange: (v: number) => void }) {
  return (
    <div className="flex gap-1">
      {Array.from({ length: scale }, (_, i) => i + 1).map(i => (
        <button
          key={i}
          type="button"
          onClick={() => onChange(i)}
          className={`w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all
            ${i <= value ? 'bg-amber-400 text-white shadow-sm' : 'bg-gray-100 text-gray-400 hover:bg-amber-100'}`}
        >
          {scale <= 5 ? <Star size={16} fill={i <= value ? 'white' : 'none'} /> : i}
        </button>
      ))}
    </div>
  )
}

// ── Single question renderer ──────────────────────────────────────────────
function QuestionField({
  question,
  value,
  onChange,
}: {
  question: SurveyQuestion
  value: string | number | string[] | undefined
  onChange: (v: string | number | string[]) => void
}) {
  const { type, options = [], scale = 5 } = question

  if (type === 'rating') {
    return (
      <RatingInput
        scale={scale}
        value={typeof value === 'number' ? value : 0}
        onChange={onChange}
      />
    )
  }

  if (type === 'radio') {
    return (
      <div className="space-y-2">
        {options.map(opt => (
          <label key={opt} className="flex items-center gap-3 cursor-pointer">
            <div className={`w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
              ${value === opt ? 'border-indigo-500' : 'border-gray-300'}`}>
              {value === opt && <div className="w-2 h-2 rounded-full bg-indigo-500" />}
            </div>
            <input type="radio" className="hidden" checked={value === opt} onChange={() => onChange(opt)} />
            <span className="text-sm text-gray-700">{opt}</span>
          </label>
        ))}
      </div>
    )
  }

  if (type === 'select') {
    return (
      <select
        value={typeof value === 'string' ? value : ''}
        onChange={e => onChange(e.target.value)}
        className="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none"
      >
        <option value="">Select an option…</option>
        {options.map(opt => (
          <option key={opt} value={opt}>{opt}</option>
        ))}
      </select>
    )
  }

  if (type === 'checkbox') {
    const checked = Array.isArray(value) ? value : []
    const toggle = (opt: string) => {
      const next = checked.includes(opt) ? checked.filter(v => v !== opt) : [...checked, opt]
      onChange(next)
    }
    return (
      <div className="space-y-2">
        {options.map(opt => (
          <label key={opt} className="flex items-center gap-3 cursor-pointer">
            <div className={`w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0
              ${checked.includes(opt) ? 'border-indigo-500 bg-indigo-500' : 'border-gray-300'}`}>
              {checked.includes(opt) && <CheckCircle2 size={10} className="text-white" />}
            </div>
            <input type="checkbox" className="hidden" checked={checked.includes(opt)} onChange={() => toggle(opt)} />
            <span className="text-sm text-gray-700">{opt}</span>
          </label>
        ))}
      </div>
    )
  }

  if (type === 'textarea') {
    return (
      <textarea
        value={typeof value === 'string' ? value : ''}
        onChange={e => onChange(e.target.value)}
        rows={4}
        placeholder="Type your answer here…"
        className="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-indigo-400 focus:outline-none"
      />
    )
  }

  // text input (default)
  return (
    <input
      type="text"
      value={typeof value === 'string' ? value : ''}
      onChange={e => onChange(e.target.value)}
      placeholder="Type your answer…"
      className="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none"
    />
  )
}

// ── Main page ─────────────────────────────────────────────────────────────
export default function SurveyTakePage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const surveyId = Number(id)

  const [responses, setResponses] = useState<SurveyResponses>({})
  const [submitted, setSubmitted] = useState(false)

  const { data: survey, isLoading, isError } = useQuery({
    queryKey: ['survey', surveyId],
    queryFn: () => surveysApi.get(surveyId),
    enabled: !!surveyId,
    staleTime: 5 * 60 * 1000,
  })

  const submitMutation = useMutation({
    mutationFn: () => surveysApi.submit(surveyId, responses),
    onSuccess: () => { setSubmitted(true) },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? 'Failed to submit survey.')
    },
  })

  const setAnswer = (qId: number, value: string | number | string[]) => {
    setResponses(prev => ({ ...prev, [qId]: value }))
  }

  const canSubmit = () => {
    if (!survey) return false
    return survey.questions.every(q => {
      if (!q.required) return true
      const val = responses[q.id]
      if (val === undefined || val === null || val === '') return false
      if (Array.isArray(val) && val.length === 0) return false
      return true
    })
  }

  // ── Loading ─────────────────────────────────────────────────────────────
  if (isLoading) {
    return (
      <div className="max-w-[1200px] mx-auto px-4 py-8 animate-pulse space-y-4">
        <div className="h-6 w-1/4 bg-gray-200 rounded" />
        <div className="h-8 w-3/4 bg-gray-200 rounded" />
        {[1, 2, 3].map(i => (
          <div key={i} className="h-32 bg-gray-100 rounded-2xl" />
        ))}
      </div>
    )
  }

  if (isError || !survey) {
    return (
      <div className="max-w-[1200px] mx-auto px-4 py-20 text-center space-y-3">
        <ClipboardList size={40} className="mx-auto text-gray-300" />
        <p className="font-semibold text-gray-700">Survey not found</p>
        <button onClick={() => navigate('/activity')} className="text-indigo-600 text-sm">← Back to Activity</button>
      </div>
    )
  }

  // ── Already submitted ────────────────────────────────────────────────────
  if (survey.already_submitted || submitted) {
    return (
      <div className="max-w-[1200px] mx-auto px-4 py-20 text-center space-y-4">
        <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
          <CheckCircle2 size={32} className="text-green-500" />
        </div>
        <h2 className="text-xl font-bold text-gray-900">
          {submitted ? 'Thank you!' : 'Already Submitted'}
        </h2>
        <p className="text-gray-500 text-sm">
          {submitted
            ? 'Your response has been recorded. We appreciate your feedback!'
            : 'You have already completed this survey.'}
        </p>
        <button
          onClick={() => navigate('/activity')}
          className="bg-indigo-600 text-white rounded-xl px-5 py-2.5 text-sm font-medium hover:bg-indigo-700"
        >
          Back to Activity
        </button>
      </div>
    )
  }

  const totalQ = survey.questions.length
  const answeredQ = survey.questions.filter(q => {
    const val = responses[q.id]
    if (Array.isArray(val)) return val.length > 0
    return val !== undefined && val !== ''
  }).length

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-[1200px] mx-auto px-4 py-3 flex items-center gap-3">
          <button onClick={() => navigate('/activity')} className="text-gray-500 hover:text-gray-700">
            <ChevronLeft size={22} />
          </button>
          <div className="flex-1 min-w-0">
            <h1 className="font-bold text-gray-900 text-sm line-clamp-1">{survey.title}</h1>
            <p className="text-xs text-gray-400">{answeredQ}/{totalQ} answered</p>
          </div>
        </div>
        {/* Progress bar */}
        <div className="h-1 bg-gray-100">
          <div
            className="h-full bg-indigo-500 transition-all"
            style={{ width: `${totalQ > 0 ? (answeredQ / totalQ) * 100 : 0}%` }}
          />
        </div>
      </div>

      <div className="max-w-[1200px] mx-auto px-4 py-5 space-y-4 pb-24">

        {/* Description */}
        {survey.description && (
          <p className="text-sm text-gray-500 bg-white border border-gray-200 rounded-2xl p-4 leading-relaxed">
            {survey.description}
          </p>
        )}

        {/* Questions */}
        {survey.questions.map((q, idx) => (
          <div key={q.id} className="bg-white border border-gray-200 rounded-2xl p-5 space-y-3">
            <div className="flex items-start gap-2">
              <span className="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center">
                {idx + 1}
              </span>
              <p className="text-sm font-medium text-gray-800 flex-1">
                {q.question}
                {q.required && <span className="text-red-500 ml-1">*</span>}
              </p>
            </div>
            <div className="pl-8">
              <QuestionField
                question={q}
                value={responses[q.id]}
                onChange={val => setAnswer(q.id, val)}
              />
            </div>
          </div>
        ))}
      </div>

      {/* Submit bar */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3 safe-area-bottom">
        <div className="max-w-[1200px] mx-auto">
          <button
            onClick={() => submitMutation.mutate()}
            disabled={!canSubmit() || submitMutation.isPending}
            className="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-xl py-3 text-sm font-semibold"
          >
            {submitMutation.isPending ? 'Submitting…' : 'Submit Survey'}
          </button>
        </div>
      </div>
    </div>
  )
}
