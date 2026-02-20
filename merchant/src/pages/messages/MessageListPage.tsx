import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ChevronLeft, ChevronRight, MessageSquare, Plus } from 'lucide-react'
import { messageApi, type Message } from '@/api/endpoints/messages'

function MessageRow({ msg }: { msg: Message }) {
  const navigate   = useNavigate()
  const date       = new Date(msg.sent_at)
  const isToday    = new Date().toDateString() === date.toDateString()
  const dateLabel  = isToday
    ? date.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })
    : date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' })
  const hasUnread  = !!(msg.has_unread)

  return (
    <button
      onClick={() => navigate(`/messages/${msg.id}`)}
      className="w-full flex items-start gap-3 bg-white rounded-2xl shadow-sm p-4 text-left hover:shadow-md transition-shadow active:scale-[0.99]"
    >
      <div className="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center shrink-0">
        <MessageSquare size={16} className="text-brand-600" />
      </div>
      <div className="flex-1 min-w-0">
        <div className="flex items-start justify-between gap-2">
          <p className={`text-sm truncate ${hasUnread ? 'font-bold text-gray-900' : 'font-medium text-gray-700'}`}>
            {msg.subject ?? 'Message'}
          </p>
          <span className="text-[10px] text-gray-400 shrink-0">{dateLabel}</span>
        </div>
        <p className="text-xs text-gray-500 mt-0.5 line-clamp-1">{msg.message_text}</p>
        <div className="flex items-center gap-2 mt-1.5">
          {msg.reply_count ? (
            <span className="text-[10px] text-gray-400">{msg.reply_count} repl{msg.reply_count === 1 ? 'y' : 'ies'}</span>
          ) : null}
          {hasUnread && (
            <span className="w-2 h-2 bg-brand-600 rounded-full" />
          )}
        </div>
      </div>
      <ChevronRight size={14} className="text-gray-400 mt-1 shrink-0" />
    </button>
  )
}

export default function MessageListPage() {
  const navigate = useNavigate()

  const { data, isLoading } = useQuery({
    queryKey: ['messages'],
    queryFn:  () => messageApi.list().then(r => ({
      items:        r.data.data,
      unread_count: r.data.meta?.unread_count ?? 0,
    })),
  })

  const items       = data?.items        ?? []
  const unreadCount = data?.unread_count ?? 0

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
          <div className="flex-1">
            <h1 className="text-white font-bold text-xl">Messages</h1>
            <p className="text-white/70 text-xs mt-0.5">
              {unreadCount > 0 ? `${unreadCount} unread` : 'All caught up'}
            </p>
          </div>
          <button
            onClick={() => navigate('/messages/new')}
            className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30"
          >
            <Plus size={20} className="text-white" />
          </button>
        </div>
      </div>

      <div className="px-4 py-4 space-y-2">
        {isLoading ? (
          Array.from({ length: 4 }).map((_, i) => (
            <div key={i} className="bg-white rounded-2xl shadow-sm p-4 flex gap-3 animate-pulse">
              <div className="w-10 h-10 bg-gray-100 rounded-full" />
              <div className="flex-1 space-y-2">
                <div className="h-3 bg-gray-100 rounded w-1/2" />
                <div className="h-3 bg-gray-100 rounded w-3/4" />
              </div>
            </div>
          ))
        ) : items.length === 0 ? (
          <div className="py-20 text-center">
            <MessageSquare size={40} className="text-gray-300 mx-auto mb-3" />
            <p className="text-gray-500 font-medium">No messages yet</p>
            <p className="text-gray-400 text-sm mt-1">Send a message to admin to get started</p>
            <button
              onClick={() => navigate('/messages/new')}
              className="btn-primary mt-4 inline-flex items-center gap-2"
            >
              <Plus size={14} /> New Message
            </button>
          </div>
        ) : (
          items.map(m => <MessageRow key={m.id} msg={m} />)
        )}
        <div className="h-4" />
      </div>
    </div>
  )
}
