import { useNavigate } from 'react-router-dom'
import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  Bell, CheckCheck, Info, CheckCircle2, AlertTriangle, XCircle,
  ChevronLeft,
} from 'lucide-react'
import toast from 'react-hot-toast'
import { useEffect, useRef } from 'react'
import { merchantApi, type Notification } from '@/api/endpoints/merchant'

function timeAgo(isoDate: string): string {
  const diff = Date.now() - new Date(isoDate).getTime()
  const mins  = Math.floor(diff / 60000)
  if (mins <  1)  return 'just now'
  if (mins < 60)  return `${mins}m ago`
  const hrs = Math.floor(mins / 60)
  if (hrs  < 24)  return `${hrs}h ago`
  const days = Math.floor(hrs / 24)
  if (days <  7)  return `${days}d ago`
  return new Date(isoDate).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })
}

function isToday(isoDate: string) {
  const d = new Date(isoDate)
  const n = new Date()
  return d.getFullYear() === n.getFullYear() && d.getMonth() === n.getMonth() && d.getDate() === n.getDate()
}

const TYPE_CONFIG: Record<string, { icon: typeof Info; bg: string; icon_color: string }> = {
  info:    { icon: Info,         bg: 'bg-blue-50',  icon_color: 'text-blue-500' },
  success: { icon: CheckCircle2, bg: 'bg-green-50', icon_color: 'text-green-500' },
  warning: { icon: AlertTriangle,bg: 'bg-amber-50', icon_color: 'text-amber-500' },
  error:   { icon: XCircle,      bg: 'bg-red-50',   icon_color: 'text-red-500' },
}

function NotifRow({ notif, onRead }: { notif: Notification; onRead: (n: Notification) => void }) {
  const cfg = TYPE_CONFIG[notif.notification_type] ?? TYPE_CONFIG.info
  const Icon = cfg.icon
  return (
    <div
      onClick={() => onRead(notif)}
      className={`flex items-start gap-3 px-4 py-3.5 cursor-pointer transition-colors ${notif.read_status ? 'bg-white' : 'bg-brand-50/60'} active:bg-gray-100`}
    >
      <div className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5 ${cfg.bg}`}>
        <Icon size={17} className={cfg.icon_color} />
      </div>
      <div className="flex-1 min-w-0">
        <div className="flex items-center justify-between gap-2">
          <p className={`text-sm leading-tight ${notif.read_status ? 'font-medium text-gray-800' : 'font-bold text-gray-900'}`}>{notif.title}</p>
          <span className="text-[10px] text-gray-400 shrink-0">{timeAgo(notif.created_at)}</span>
        </div>
        {notif.message && <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{notif.message}</p>}
      </div>
      {!notif.read_status && (
        <div className="w-2 h-2 rounded-full bg-brand-600 shrink-0 mt-1.5" />
      )}
    </div>
  )
}

export default function NotificationsPage() {
  const navigate    = useNavigate()
  const queryClient = useQueryClient()
  const loaderRef   = useRef<HTMLDivElement>(null)

  const {
    data, isLoading, fetchNextPage, hasNextPage, isFetchingNextPage,
  } = useInfiniteQuery({
    queryKey: ['notifications'],
    queryFn: ({ pageParam = 1 }) =>
      merchantApi.getNotifications({ page: pageParam as number, limit: 20 }).then((r) => r.data),
    getNextPageParam: (last) => {
      if (!last.meta) return undefined
      const lastPage = Math.ceil(last.meta.total_count / last.meta.per_page)
      return last.meta.current_page < lastPage ? last.meta.current_page + 1 : undefined
    },
    initialPageParam: 1,
  })

  // Infinite scroll observer
  useEffect(() => {
    if (!loaderRef.current) return
    const observer = new IntersectionObserver(
      ([entry]) => { if (entry.isIntersecting && hasNextPage && !isFetchingNextPage) fetchNextPage() },
      { threshold: 0.1 }
    )
    observer.observe(loaderRef.current)
    return () => observer.disconnect()
  }, [hasNextPage, isFetchingNextPage, fetchNextPage])

  const markAllMutation = useMutation({
    mutationFn: () => merchantApi.markAllNotificationsRead(),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notifications'] })
      toast.success('All marked as read')
    },
    onError: () => toast.error('Failed to update'),
  })

  const markOneMutation = useMutation({
    mutationFn: (id: number) => merchantApi.markNotificationRead(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  })

  const handleRead = (notif: Notification) => {
    if (!notif.read_status) markOneMutation.mutate(notif.id)
    if (notif.action_url) navigate(notif.action_url)
  }

  const allNotifs: Notification[] = data?.pages.flatMap((p) => p.data ?? []) ?? []
  const todayNotifs  = allNotifs.filter((n) => isToday(n.created_at))
  const earlierNotifs = allNotifs.filter((n) => !isToday(n.created_at))
  const unreadCount  = data?.pages[0]?.meta?.unread_count ?? 0

  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-4 pt-12 pb-5 flex items-center gap-3">
        <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
          <ChevronLeft size={20} className="text-white" />
        </button>
        <div className="flex-1">
          <h1 className="text-white font-bold text-lg leading-tight">Notifications</h1>
          {unreadCount > 0 && <p className="text-white/70 text-xs">{unreadCount} unread</p>}
        </div>
        {unreadCount > 0 && (
          <button
            onClick={() => markAllMutation.mutate()}
            disabled={markAllMutation.isPending}
            className="h-9 px-3 bg-white/20 rounded-full flex items-center gap-1.5 text-white text-xs font-semibold"
          >
            <CheckCheck size={15} />
            {markAllMutation.isPending ? '…' : 'Mark all read'}
          </button>
        )}
      </div>

      <div className="pb-6">
        {isLoading ? (
          <div className="space-y-0.5 mt-2">
            {Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="bg-white h-16 animate-pulse mx-4 rounded-xl mb-2" />
            ))}
          </div>
        ) : allNotifs.length === 0 ? (
          <div className="flex flex-col items-center justify-center pt-24 gap-3 text-center px-8">
            <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
              <Bell size={28} className="text-gray-300" />
            </div>
            <p className="font-semibold text-gray-500">No notifications yet</p>
            <p className="text-xs text-gray-400">We'll notify you when something important happens</p>
          </div>
        ) : (
          <>
            {todayNotifs.length > 0 && (
              <div>
                <div className="px-4 py-2 mt-3">
                  <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Today</p>
                </div>
                <div className="bg-white divide-y divide-gray-50 shadow-sm">
                  {todayNotifs.map((n) => <NotifRow key={n.id} notif={n} onRead={handleRead} />)}
                </div>
              </div>
            )}
            {earlierNotifs.length > 0 && (
              <div>
                <div className="px-4 py-2 mt-3">
                  <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Earlier</p>
                </div>
                <div className="bg-white divide-y divide-gray-50 shadow-sm">
                  {earlierNotifs.map((n) => <NotifRow key={n.id} notif={n} onRead={handleRead} />)}
                </div>
              </div>
            )}
            <div ref={loaderRef} className="h-12 flex items-center justify-center">
              {isFetchingNextPage && <div className="w-5 h-5 border-2 border-brand-500 border-t-transparent rounded-full animate-spin" />}
            </div>
          </>
        )}
      </div>
    </div>
  )
}

