import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ChevronLeft, ChevronRight, Star } from 'lucide-react'
import { reviewApi, type Review } from '@/api/endpoints/reviews'

function StarRow({ rating, max = 5 }: { rating: number; max?: number }) {
  return (
    <div className="flex items-center gap-0.5">
      {Array.from({ length: max }).map((_, i) => (
        <Star
          key={i}
          size={12}
          className={i < rating ? 'text-amber-400 fill-amber-400' : 'text-gray-200 fill-gray-200'}
        />
      ))}
    </div>
  )
}

function RatingBar({ label, count, total }: { label: string; count: number; total: number }) {
  const pct = total > 0 ? (count / total) * 100 : 0
  return (
    <div className="flex items-center gap-2">
      <span className="text-xs text-gray-500 w-4">{label}</span>
      <div className="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
        <div className="bg-amber-400 h-2 rounded-full transition-all" style={{ width: `${pct}%` }} />
      </div>
      <span className="text-xs text-gray-500 w-5 text-right">{count}</span>
    </div>
  )
}

function ReviewCard({ review }: { review: Review }) {
  const navigate = useNavigate()
  const date = new Date(review.created_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })

  return (
    <button
      onClick={() => navigate(`/reviews/${review.id}`)}
      className="w-full bg-white rounded-2xl shadow-sm p-4 text-left hover:shadow-md transition-shadow active:scale-[0.99]"
    >
      <div className="flex items-start justify-between gap-2">
        <div>
          <StarRow rating={review.rating} />
          <p className="text-xs text-gray-500 mt-1">{review.customer_name ?? 'Customer'}</p>
        </div>
        <div className="flex items-center gap-1">
          <span className="text-xs text-gray-400">{date}</span>
          <ChevronRight size={14} className="text-gray-400" />
        </div>
      </div>
      {review.review_text && (
        <p className="text-sm text-gray-700 mt-2 line-clamp-2">{review.review_text}</p>
      )}
      {review.merchant_reply && (
        <p className="text-xs text-brand-600 mt-2 font-medium">✓ You replied</p>
      )}
      {review.store_name && (
        <p className="text-xs text-gray-400 mt-2">{review.store_name}</p>
      )}
    </button>
  )
}

export default function ReviewListPage() {
  const navigate  = useNavigate()
  const [filter, setFilter] = useState<number | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['reviews', filter],
    queryFn:  () => reviewApi.list(filter ? { rating: filter } : {}).then(r => ({
      items:   r.data.data,
      summary: r.data.meta?.summary,
    })),
  })

  const items   = data?.items   ?? []
  const summary = data?.summary ?? { total: 0, avg_rating: null, five: 0, four: 0, three: 0, two: 0, one: 0 }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate(-1)}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30"
          >
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div>
            <h1 className="text-white font-bold text-xl">Reviews</h1>
            <p className="text-white/70 text-xs mt-0.5">
              {summary.total} reviews · {summary.avg_rating ?? '—'}★ avg
            </p>
          </div>
        </div>

        {/* Rating summary */}
        {summary.total > 0 && (
          <div className="mt-4 bg-white/15 rounded-2xl p-4">
            <div className="flex items-center gap-4">
              <div className="text-center">
                <p className="text-white font-bold text-3xl">{summary.avg_rating ?? '—'}</p>
                <StarRow rating={Math.round(parseFloat(summary.avg_rating ?? '0'))} />
                <p className="text-white/70 text-[10px] mt-1">{summary.total} reviews</p>
              </div>
              <div className="flex-1 space-y-1.5">
                <RatingBar label="5" count={summary.five}  total={summary.total} />
                <RatingBar label="4" count={summary.four}  total={summary.total} />
                <RatingBar label="3" count={summary.three} total={summary.total} />
                <RatingBar label="2" count={summary.two}   total={summary.total} />
                <RatingBar label="1" count={summary.one}   total={summary.total} />
              </div>
            </div>
          </div>
        )}

        {/* Filter pills */}
        <div className="flex gap-2 mt-3 overflow-x-auto">
          {[null, 5, 4, 3, 2, 1].map(v => (
            <button
              key={v ?? 'all'}
              onClick={() => setFilter(v)}
              className={`px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-colors ${
                filter === v ? 'bg-white text-brand-700' : 'bg-white/20 text-white hover:bg-white/30'
              }`}
            >
              {v === null ? 'All' : `${'★'.repeat(v)}`}
            </button>
          ))}
        </div>
      </div>

      <div className="px-4 py-4 space-y-3">
        {isLoading ? (
          Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="bg-white rounded-2xl shadow-sm p-4 animate-pulse">
              <div className="flex gap-1 mb-2">{Array.from({ length: 5 }).map((_, j) => <div key={j} className="w-3 h-3 bg-gray-100 rounded" />)}</div>
              <div className="h-3 bg-gray-100 rounded w-2/3" />
            </div>
          ))
        ) : items.length === 0 ? (
          <div className="py-20 text-center">
            <Star size={40} className="text-gray-300 mx-auto mb-3" />
            <p className="text-gray-500 font-medium">No reviews yet</p>
          </div>
        ) : (
          items.map(r => <ReviewCard key={r.id} review={r} />)
        )}
        <div className="h-4" />
      </div>
    </div>
  )
}
