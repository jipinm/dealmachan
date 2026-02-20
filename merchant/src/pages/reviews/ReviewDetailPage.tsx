import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Send, Star, Store } from 'lucide-react'
import toast from 'react-hot-toast'
import { reviewApi } from '@/api/endpoints/reviews'

function StarDisplay({ rating }: { rating: number }) {
  return (
    <div className="flex items-center gap-1">
      {Array.from({ length: 5 }).map((_, i) => (
        <Star
          key={i}
          size={20}
          className={i < rating ? 'text-amber-400 fill-amber-400' : 'text-gray-200 fill-gray-200'}
        />
      ))}
    </div>
  )
}

export default function ReviewDetailPage() {
  const { id }   = useParams<{ id: string }>()
  const navigate = useNavigate()
  const qc       = useQueryClient()
  const [replyText, setReplyText] = useState('')

  const { data: review, isLoading } = useQuery({
    queryKey: ['review', id],
    queryFn:  () => reviewApi.get(Number(id)).then(r => r.data.data),
    enabled:  !!id,
  })

  const replyMutation = useMutation({
    mutationFn: (text: string) => reviewApi.reply(Number(id), text),
    onSuccess: () => {
      toast.success('Reply posted!')
      qc.invalidateQueries({ queryKey: ['review', id] })
      qc.invalidateQueries({ queryKey: ['reviews'] })
      setReplyText('')
    },
    onError: () => toast.error('Failed to post reply'),
  })

  if (isLoading || !review) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="gradient-brand px-5 pt-14 pb-8">
          <div className="flex items-center gap-3">
            <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
              <ChevronLeft size={20} className="text-white" />
            </button>
            <div className="h-5 bg-white/20 w-24 rounded animate-pulse" />
          </div>
        </div>
        <div className="p-4 space-y-3">
          {Array.from({ length: 3 }).map((_, i) => <div key={i} className="h-20 bg-white rounded-2xl animate-pulse" />)}
        </div>
      </div>
    )
  }

  const date = new Date(review.created_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })

  return (
    <div className="min-h-screen bg-gray-50 pb-32">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-8">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate(-1)}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30"
          >
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div>
            <h1 className="text-white font-bold text-xl">Review</h1>
            <p className="text-white/70 text-xs mt-0.5">{date}</p>
          </div>
        </div>
      </div>

      <div className="px-4 -mt-4 space-y-4">
        {/* Review card */}
        <div className="bg-white rounded-2xl shadow-sm p-5">
          <div className="flex items-start justify-between">
            <div>
              <StarDisplay rating={review.rating} />
              <p className="text-sm font-medium text-gray-700 mt-2">{review.customer_name ?? 'Customer'}</p>
            </div>
            {review.store_name && (
              <div className="flex items-center gap-1 text-gray-400">
                <Store size={12} />
                <span className="text-xs">{review.store_name}</span>
              </div>
            )}
          </div>
          {review.review_text ? (
            <p className="text-gray-700 mt-4 text-sm leading-relaxed">{review.review_text}</p>
          ) : (
            <p className="text-gray-400 mt-4 text-sm italic">No written review</p>
          )}
        </div>

        {/* Existing reply */}
        {review.merchant_reply && (
          <div className="bg-brand-50 border border-brand-100 rounded-2xl p-4">
            <p className="text-xs font-semibold text-brand-700 mb-2">Your Reply</p>
            <p className="text-sm text-gray-700 leading-relaxed">{review.merchant_reply}</p>
            {review.merchant_reply_at && (
              <p className="text-xs text-brand-400 mt-2">
                {new Date(review.merchant_reply_at).toLocaleDateString('en-IN')}
              </p>
            )}
          </div>
        )}

        {/* Reply form */}
        <div className="bg-white rounded-2xl shadow-sm p-4">
          <p className="text-xs font-semibold text-gray-500 uppercase mb-3">
            {review.merchant_reply ? 'Edit Your Reply' : 'Reply to Review'}
          </p>
          <textarea
            value={replyText}
            onChange={e => setReplyText(e.target.value)}
            rows={4}
            placeholder="Write a public reply to this review…"
            className="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-brand-300"
          />
          <button
            onClick={() => replyText.trim() && replyMutation.mutate(replyText.trim())}
            disabled={!replyText.trim() || replyMutation.isPending}
            className="btn-primary w-full mt-3 flex items-center justify-center gap-2"
          >
            <Send size={14} />
            {replyMutation.isPending ? 'Posting…' : 'Post Reply'}
          </button>
        </div>
      </div>
    </div>
  )
}
