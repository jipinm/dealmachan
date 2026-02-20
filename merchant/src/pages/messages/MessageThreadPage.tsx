import { useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Send } from 'lucide-react'
import toast from 'react-hot-toast'
import { messageApi, type Message } from '@/api/endpoints/messages'

function Bubble({ msg, isMe }: { msg: Message; isMe: boolean }) {
  const time = new Date(msg.sent_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })
  return (
    <div className={`flex ${isMe ? 'justify-end' : 'justify-start'}`}>
      <div
        className={`max-w-[78%] px-4 py-2.5 rounded-2xl text-sm leading-relaxed ${
          isMe
            ? 'bg-brand-600 text-white rounded-br-sm'
            : 'bg-white text-gray-800 shadow-sm rounded-bl-sm'
        }`}
      >
        <p>{msg.message_text}</p>
        <p className={`text-[10px] mt-1 text-right ${isMe ? 'text-brand-200' : 'text-gray-400'}`}>{time}</p>
      </div>
    </div>
  )
}

function NewMessageForm() {
  const navigate = useNavigate()
  const qc       = useQueryClient()
  const [subject, setSubject] = useState('')
  const [body, setBody]       = useState('')

  const mutation = useMutation({
    mutationFn: () => messageApi.send({ subject: subject.trim() || 'Message from merchant', message_text: body.trim() }),
    onSuccess: (res) => {
      toast.success('Message sent!')
      qc.invalidateQueries({ queryKey: ['messages'] })
      navigate(`/messages/${res.data.data.id}`, { replace: true })
    },
    onError: () => toast.error('Failed to send'),
  })

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
          <h1 className="text-white font-bold text-xl">New Message</h1>
        </div>
      </div>
      <div className="px-4 py-4 space-y-4">
        <div className="bg-white rounded-2xl shadow-sm p-4 space-y-4">
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Subject</label>
            <input
              value={subject}
              onChange={e => setSubject(e.target.value)}
              placeholder="e.g. Issue with coupon approval"
              className="input-field"
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Message *</label>
            <textarea
              value={body}
              onChange={e => setBody(e.target.value)}
              rows={6}
              placeholder="Write your message to admin…"
              className="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-brand-300"
            />
          </div>
          <button
            onClick={() => body.trim() && mutation.mutate()}
            disabled={!body.trim() || mutation.isPending}
            className="btn-primary w-full flex items-center justify-center gap-2"
          >
            <Send size={14} />
            {mutation.isPending ? 'Sending…' : 'Send Message'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default function MessageThreadPage() {
  const { id }   = useParams<{ id: string }>()
  const navigate = useNavigate()
  const qc       = useQueryClient()
  const bottomRef = useRef<HTMLDivElement>(null)
  const [replyText, setReplyText] = useState('')

  // New message form
  if (!id || id === 'new') return <NewMessageForm />

  // eslint-disable-next-line react-hooks/rules-of-hooks
  const { data, isLoading } = useQuery({
    queryKey: ['message-thread', id],
    queryFn:  () => messageApi.getThread(Number(id)).then(r => r.data.data),
    enabled:  !!id && id !== 'new',
  })

  // eslint-disable-next-line react-hooks/rules-of-hooks
  const replyMutation = useMutation({
    mutationFn: (text: string) => messageApi.send({
      message_text:      text,
      parent_message_id: Number(id),
    }),
    onSuccess: () => {
      setReplyText('')
      qc.invalidateQueries({ queryKey: ['message-thread', id] })
      qc.invalidateQueries({ queryKey: ['messages'] })
    },
    onError: () => toast.error('Failed to send'),
  })

  // Scroll to bottom when messages load
  // eslint-disable-next-line react-hooks/rules-of-hooks
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [data])

  const root    = data?.message
  const replies = data?.replies ?? []
  // All messages in chronological order
  const allMsgs: Message[] = root ? [root, ...replies] : []
  // Merchant id is encoded in sender — we know merchant messages have sender_type === 'merchant'
  const merchantId = root?.sender_type === 'merchant' ? root.sender_id
                   : replies.find(r => r.sender_type === 'merchant')?.sender_id ?? 0

  const subject = root?.subject ?? 'Message'

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-5">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate(-1)}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0 hover:bg-white/30"
          >
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1 min-w-0">
            <h1 className="text-white font-bold text-base truncate">{subject}</h1>
            <p className="text-white/70 text-xs mt-0.5">Admin</p>
          </div>
        </div>
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto px-4 py-4 pb-28 space-y-3">
        {isLoading ? (
          Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className={`flex ${i % 2 === 0 ? 'justify-end' : 'justify-start'}`}>
              <div className="bg-white rounded-2xl h-10 w-48 animate-pulse" />
            </div>
          ))
        ) : (
          allMsgs.map(m => (
            <Bubble
              key={m.id}
              msg={m}
              isMe={m.sender_type === 'merchant' && m.sender_id === merchantId}
            />
          ))
        )}
        <div ref={bottomRef} />
      </div>

      {/* Reply input — fixed at bottom */}
      <div className="fixed bottom-0 inset-x-0 bg-white border-t border-gray-100 px-4 py-3 pb-safe">
        <div className="flex items-end gap-2">
          <textarea
            value={replyText}
            onChange={e => setReplyText(e.target.value)}
            rows={1}
            placeholder="Reply…"
            onKeyDown={e => {
              if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault()
                replyText.trim() && replyMutation.mutate(replyText.trim())
              }
            }}
            className="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-brand-300 max-h-32 overflow-y-auto"
          />
          <button
            onClick={() => replyText.trim() && replyMutation.mutate(replyText.trim())}
            disabled={!replyText.trim() || replyMutation.isPending}
            className="w-10 h-10 bg-brand-600 rounded-full flex items-center justify-center shrink-0 hover:bg-brand-700 disabled:opacity-40"
          >
            <Send size={16} className="text-white" />
          </button>
        </div>
      </div>
    </div>
  )
}
