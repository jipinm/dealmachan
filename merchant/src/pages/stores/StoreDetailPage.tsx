import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  ChevronLeft, Edit, MapPin, Phone, Mail, Clock, Ticket,
  Image as ImageIcon, Trash2, AlertTriangle,
} from 'lucide-react'
import { useState } from 'react'
import toast from 'react-hot-toast'
import { storeApi } from '@/api/endpoints/stores'
import { SkeletonBlock } from '@/components/ui/PageLoader'
import { getImageUrl } from '@/lib/imageUrl'

const DAY_LABELS: Record<string, string> = {
  mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu',
  fri: 'Fri', sat: 'Sat', sun: 'Sun',
}

function formatHours(val: string | { open: string; close: string; closed: boolean }): string {
  if (typeof val === 'string') return val
  if (val.closed) return 'Closed'
  return `${val.open} – ${val.close}`
}

export default function StoreDetailPage() {
  const { id }      = useParams<{ id: string }>()
  const navigate    = useNavigate()
  const queryClient = useQueryClient()
  const [confirmDelete, setConfirmDelete] = useState(false)

  const { data: store, isLoading } = useQuery({
    queryKey: ['store', id],
    queryFn:  () => storeApi.get(Number(id)).then((r) => r.data.data),
    enabled:  !!id,
    staleTime: 2 * 60 * 1000,
  })

  const deleteMutation = useMutation({
    mutationFn: () => storeApi.delete(Number(id)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['stores'] })
      toast.success('Store deleted')
      navigate('/stores', { replace: true })
    },
    onError: () => toast.error('Failed to delete store'),
  })

  const coverImage = store?.gallery?.find((g) => g.is_cover) ?? store?.gallery?.[0]

  return (
    <div className="min-h-full bg-gray-50 pb-10">
      {/* Sticky header */}
      <div className="fixed top-0 inset-x-0 z-30 gradient-brand px-4 pt-12 pb-4 flex items-center gap-3">
        <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
          <ChevronLeft size={20} className="text-white" />
        </button>
        <h1 className="text-white font-bold text-base flex-1 truncate">
          {isLoading ? 'Store Detail' : (store?.store_name ?? 'Store')}
        </h1>
        {store && (
          <button onClick={() => navigate(`/stores/${id}/edit`)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
            <Edit size={16} className="text-white" />
          </button>
        )}
      </div>

      {isLoading ? (
        <div className="pt-20 px-4 space-y-4">
          <SkeletonBlock className="h-48 w-full rounded-2xl" />
          <SkeletonBlock className="h-32 w-full rounded-2xl" />
          <SkeletonBlock className="h-24 w-full rounded-2xl" />
        </div>
      ) : !store ? (
        <div className="pt-32 text-center text-gray-400">
          <p>Store not found</p>
        </div>
      ) : (
        <div className="pt-20">
          {/* Cover / Gallery preview */}
          <div className="h-48 bg-gray-100 overflow-hidden relative">
            {coverImage
              ? <img src={getImageUrl(coverImage.image_url)} alt={store.store_name} className="w-full h-full object-cover" />
              : <div className="w-full h-full flex items-center justify-center"><ImageIcon size={48} className="text-gray-200" /></div>}
            {store.gallery && store.gallery.length > 0 && (
              <div className="absolute bottom-2 right-2 bg-black/50 text-white text-xs px-2 py-1 rounded-full">
                {store.gallery.length} photos
              </div>
            )}
          </div>

          <div className="px-4 space-y-3 mt-4">
            {/* Name + Status */}
            <div className="bg-white rounded-2xl p-4 shadow-sm">
              <div className="flex items-start justify-between gap-2">
                <h2 className="text-lg font-bold text-gray-900 leading-tight">{store.store_name}</h2>
                <span className={`shrink-0 text-[10px] font-bold px-2.5 py-1 rounded-full ${
                  store.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                }`}>{store.status === 'active' ? 'Active' : 'Inactive'}</span>
              </div>
              {store.description && <p className="text-sm text-gray-500 mt-2 leading-relaxed">{store.description}</p>}
            </div>

            {/* Contact & Location */}
            <div className="bg-white rounded-2xl p-4 shadow-sm space-y-3">
              <p className="text-xs font-bold text-gray-400 uppercase tracking-wide">Location & Contact</p>
              <div className="flex items-start gap-2.5 text-sm text-gray-700">
                <MapPin size={15} className="text-brand-500 mt-0.5 shrink-0" />
                <span>{store.address}{store.area_name ? `, ${store.area_name}` : ''}{store.city_name ? `, ${store.city_name}` : ''}</span>
              </div>
              {store.phone && (
                <a href={`tel:${store.phone}`} className="flex items-center gap-2.5 text-sm text-gray-700">
                  <Phone size={15} className="text-brand-500 shrink-0" />{store.phone}
                </a>
              )}
              {store.email && (
                <a href={`mailto:${store.email}`} className="flex items-center gap-2.5 text-sm text-gray-700 break-all">
                  <Mail size={15} className="text-brand-500 shrink-0" />{store.email}
                </a>
              )}
            </div>

            {/* Opening hours */}
            {store.opening_hours && Object.keys(store.opening_hours).length > 0 && (
              <div className="bg-white rounded-2xl p-4 shadow-sm">
                <p className="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Hours</p>
                {Object.entries(store.opening_hours).map(([day, hours]) => (
                  <div key={day} className="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                    <span className="text-sm text-gray-600 font-medium">{DAY_LABELS[day] ?? day}</span>
                    <span className="text-sm text-gray-800 flex items-center gap-1.5">
                      <Clock size={12} className="text-gray-300" />{formatHours(hours)}
                    </span>
                  </div>
                ))}
              </div>
            )}

            {/* Quick links */}
            <div className="grid grid-cols-2 gap-3">
              <button
                onClick={() => navigate(`/stores/${id}/gallery`)}
                className="bg-white rounded-2xl p-4 shadow-sm flex flex-col items-center gap-2 active:bg-gray-50"
              >
                <div className="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center">
                  <ImageIcon size={18} className="text-purple-500" />
                </div>
                <p className="text-xs font-semibold text-gray-700">Gallery</p>
                <p className="text-xs text-gray-400">{store.gallery?.length ?? 0} photos</p>
              </button>
              <button
                onClick={() => navigate(`/coupons?store_id=${id}`)}
                className="bg-white rounded-2xl p-4 shadow-sm flex flex-col items-center gap-2 active:bg-gray-50"
              >
                <div className="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center">
                  <Ticket size={18} className="text-brand-500" />
                </div>
                <p className="text-xs font-semibold text-gray-700">Coupons</p>
                <p className="text-xs text-gray-400">{store.active_coupons} active</p>
              </button>
            </div>

            {/* Delete */}
            {!confirmDelete ? (
              <button
                onClick={() => setConfirmDelete(true)}
                className="w-full py-3 border-2 border-red-100 text-red-500 rounded-2xl text-sm font-semibold flex items-center justify-center gap-2"
              >
                <Trash2 size={15} />Delete Store
              </button>
            ) : (
              <div className="bg-red-50 border border-red-200 rounded-2xl p-4">
                <div className="flex items-center gap-2 mb-3">
                  <AlertTriangle size={16} className="text-red-500" />
                  <p className="text-sm font-semibold text-red-700">Delete this store?</p>
                </div>
                <p className="text-xs text-red-600 mb-4">This action cannot be undone.</p>
                <div className="flex gap-2">
                  <button onClick={() => setConfirmDelete(false)} className="flex-1 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-600">Cancel</button>
                  <button
                    onClick={() => deleteMutation.mutate()}
                    disabled={deleteMutation.isPending}
                    className="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-sm font-semibold disabled:opacity-50"
                  >{deleteMutation.isPending ? 'Deleting…' : 'Confirm Delete'}</button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  )
}
