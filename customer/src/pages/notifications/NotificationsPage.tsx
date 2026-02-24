import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  Bell, Tag, Zap, Gift, Trophy, BarChart2, ShoppingBag,
  Info, CheckCheck, Trash2, ChevronRight, BellOff,
} from 'lucide-react'
import { notificationsApi, type Notification } from '@/api/endpoints/notifications'
import toast from 'react-hot-toast'

// ── Helpers ───────────────────────────────────────────────────────────────

function timeAgo(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime()
  const m = Math.floor(diff / 60_000)
  if (m < 1)   return 'just now'
  if (m < 60)  return `${m}m ago`
  const h = Math.floor(m / 60)
  if (h < 24)  return `${h}h ago`
  const d = Math.floor(h / 24)
  if (d < 7)   return `${d}d ago`
  return new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })
}

const TYPE_META: Record<string, { icon: React.ElementType; bg: string; color: string }> = {
  coupon:         { icon: Tag,        bg: 'bg-brand-50',   color: 'text-brand-600'  },
  flash_deal:     { icon: Zap,        bg: 'bg-orange-50',  color: 'text-orange-500' },
  flash_discount: { icon: Zap,        bg: 'bg-orange-50',  color: 'text-orange-500' },
  contest:        { icon: Trophy,     bg: 'bg-yellow-50',  color: 'text-yellow-600' },
  survey:         { icon: BarChart2,  bg: 'bg-purple-50',  color: 'text-purple-500' },
  referral:       { icon: Gift,       bg: 'bg-green-50',   color: 'text-green-600'  },
  order:          { icon: ShoppingBag,bg: 'bg-sky-50',     color: 'text-sky-600'    },
  promotion:      { icon: Tag,        bg: 'bg-rose-50',    color: 'text-rose-500'   },
}

function typeIcon(type: string) {
  const meta = TYPE_META[type] ?? { icon: Info, bg: 'bg-slate-100', color: 'text-slate-500' }
  const Icon = meta.icon
  return (
    <div className={`w-10 h-10 rounded-xl ${meta.bg} flex items-center justify-center flex-shrink-0`}>
      <Icon size={18} className={meta.color} />
    </div>
  )
}

// ── Skeleton ─────────────────────────────────────────────────────────────

function SkeletonRow() {
  return (
    <div className="card p-4 flex items-start gap-3 animate-pulse">
      <div className="w-10 h-10 rounded-xl bg-slate-200 flex-shrink-0" />
      <div className="flex-1 space-y-2 pt-1">
        <div className="h-3.5 bg-slate-200 rounded w-3/5" />
        <div className="h-3 bg-slate-200 rounded w-4/5" />
        <div className="h-3 bg-slate-100 rounded w-1/4" />
      </div>
    </div>
  )
}

// ── Single notification row ───────────────────────────────────────────────

function NotificationRow({
  notif,
  onRead,
  onDelete,
}: {
  notif: Notification
  onRead: (id: number) => void
  onDelete: (id: number) => void
}) {
  const navigate = useNavigate()
  const unread = notif.is_read === 0

  function handleClick() {
    if (unread) onRead(notif.id)
    if (notif.action_url) navigate(notif.action_url)
  }

  return (
    <div
      className={`card p-4 flex items-start gap-3 group transition-colors
        ${unread ? 'bg-brand-50/40 border-brand-100' : 'bg-white'}`}
    >
      {/* Icon */}
      {typeIcon(notif.type)}

      {/* Content */}
      <button
        onClick={handleClick}
        className="flex-1 text-left min-w-0"
      >
        <div className="flex items-start justify-between gap-2">
          <p className={`text-sm leading-snug ${unread ? 'font-semibold text-slate-800' : 'font-medium text-slate-700'}`}>
            {notif.title}
          </p>
          {unread && (
            <span className="w-2 h-2 rounded-full bg-brand-500 flex-shrink-0 mt-1" />
          )}
        </div>
        <p className="text-xs text-slate-500 mt-0.5 line-clamp-2">{notif.message}</p>
        <div className="flex items-center gap-3 mt-1.5">
          <span className="text-xs text-slate-400">{timeAgo(notif.created_at)}</span>
          {notif.action_url && (
            <span className="text-xs text-brand-600 flex items-center gap-0.5 font-medium">
              View <ChevronRight size={10} />
            </span>
          )}
        </div>
      </button>

      {/* Delete */}
      <button
        onClick={() => onDelete(notif.id)}
        className="opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity
                   w-7 h-7 rounded-lg bg-slate-100 hover:bg-red-50 hover:text-red-500
                   flex items-center justify-center flex-shrink-0 text-slate-400"
        aria-label="Delete notification"
      >
        <Trash2 size={13} />
      </button>
    </div>
  )
}

// ── Main page ─────────────────────────────────────────────────────────────

const FILTERS = [
  { value: '',            label: 'All'       },
  { value: 'coupon',      label: 'Coupons'   },
  { value: 'flash_deal',  label: 'Flash'     },
  { value: 'contest',     label: 'Contests'  },
  { value: 'promotion',   label: 'Promos'    },
]

export default function NotificationsPage() {
  const qc = useQueryClient()
  const [filter, setFilter] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['notifications', filter],
    queryFn: () =>
      notificationsApi.getAll({ type: filter || undefined }).then((r) => r.data),
    staleTime: 30_000,
  })

  const notifications: Notification[] = data?.data?.data ?? []
  const unreadCount = notifications.filter(n => n.is_read === 0).length

  // Mark one as read
  const markReadMutation = useMutation({
    mutationFn: (id: number) => notificationsApi.markRead(id),
    onSuccess: (_data, id) => {
      qc.setQueryData(['notifications', filter], (old: typeof data) => {
        if (!old?.data) return old
        return {
          ...old,
          data: {
            ...old.data,
            data: old.data.data.map((n: Notification) =>
              n.id === id ? { ...n, is_read: 1 as const } : n
            ),
          },
        }
      })
      qc.invalidateQueries({ queryKey: ['notifications-unread'] })
    },
  })

  // Mark all as read
  const markAllMutation = useMutation({
    mutationFn: notificationsApi.markAllRead,
    onSuccess: () => {
      qc.setQueryData(['notifications', filter], (old: typeof data) => {
        if (!old?.data) return old
        return {
          ...old,
          data: {
            ...old.data,
            data: old.data.data.map((n: Notification) => ({ ...n, is_read: 1 as const })),
          },
        }
      })
      qc.invalidateQueries({ queryKey: ['notifications-unread'] })
      toast.success('All notifications marked as read')
    },
    onError: () => toast.error('Failed to mark all as read'),
  })

  // Delete one
  const deleteMutation = useMutation({
    mutationFn: (id: number) => notificationsApi.deleteNotification(id),
    onMutate: async (id) => {
      await qc.cancelQueries({ queryKey: ['notifications', filter] })
      const prev = qc.getQueryData(['notifications', filter])
      qc.setQueryData(['notifications', filter], (old: typeof data) => {
        if (!old?.data) return old
        return {
          ...old,
          data: {
            ...old.data,
            data: old.data.data.filter((n: Notification) => n.id !== id),
          },
        }
      })
      return { prev }
    },
    onError: (_e, _id, ctx) => {
      qc.setQueryData(['notifications', filter], ctx?.prev)
      toast.error('Could not delete notification')
    },
  })

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-8">
      {/* ── Header ───────────────────────────────────────────────────── */}
      <div className="flex items-center justify-between mb-5">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center relative">
            <Bell size={20} className="text-brand-600" />
            {unreadCount > 0 && (
              <span className="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-cta-500 text-white
                               text-[10px] font-bold flex items-center justify-center leading-none">
                {unreadCount > 9 ? '9+' : unreadCount}
              </span>
            )}
          </div>
          <div>
            <h1 className="font-heading font-bold text-xl text-slate-800 leading-tight">
              Notifications
            </h1>
            {!isLoading && (
              <p className="text-xs text-slate-500">
                {unreadCount > 0 ? `${unreadCount} unread` : 'All caught up'}
              </p>
            )}
          </div>
        </div>

        {/* Mark all read */}
        {unreadCount > 0 && (
          <button
            onClick={() => markAllMutation.mutate()}
            disabled={markAllMutation.isPending}
            className="inline-flex items-center gap-1.5 text-xs text-brand-600 hover:text-brand-700
                       font-semibold bg-brand-50 hover:bg-brand-100 px-3 py-2 rounded-xl transition-colors
                       disabled:opacity-60"
          >
            <CheckCheck size={14} />
            Mark all read
          </button>
        )}
      </div>

      {/* ── Filter tabs ──────────────────────────────────────────────── */}
      <div className="flex gap-2 overflow-x-auto pb-1 mb-5 scrollbar-hide">
        {FILTERS.map((f) => (
          <button
            key={f.value}
            onClick={() => setFilter(f.value)}
            className={`px-4 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors flex-shrink-0
              ${filter === f.value
                ? 'bg-brand-600 text-white'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}
          >
            {f.label}
          </button>
        ))}
      </div>

      {/* ── List ─────────────────────────────────────────────────────── */}
      <div className="space-y-2">
        {isLoading ? (
          [1, 2, 3, 4, 5].map((i) => <SkeletonRow key={i} />)
        ) : notifications.length === 0 ? (
          /* Empty state */
          <div className="py-20 flex flex-col items-center text-center gap-3">
            <div className="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
              <BellOff size={26} className="text-slate-400" />
            </div>
            <p className="font-semibold text-slate-700">
              {filter ? 'No notifications here' : 'No notifications yet'}
            </p>
            <p className="text-sm text-slate-400 max-w-xs">
              {filter
                ? 'Try a different filter to see more.'
                : "We'll let you know when something interesting happens!"}
            </p>
          </div>
        ) : (
          notifications.map((notif) => (
            <NotificationRow
              key={notif.id}
              notif={notif}
              onRead={(id) => markReadMutation.mutate(id)}
              onDelete={(id) => deleteMutation.mutate(id)}
            />
          ))
        )}
      </div>
    </div>
  )
}
