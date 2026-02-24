import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Plus, MapPin, Ticket, Store, Image, CheckCircle, XCircle, ChevronLeft } from 'lucide-react'
import { storeApi, type Store as StoreType } from '@/api/endpoints/stores'
import { SkeletonCard } from '@/components/ui/PageLoader'
import { getImageUrl } from '@/lib/imageUrl'

function StoreBadge({ status }: { status: string }) {
  return status === 'active'
    ? <span className="flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full"><CheckCircle size={10} />Active</span>
    : <span className="flex items-center gap-1 text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><XCircle size={10} />Inactive</span>
}

function StoreCard({ store, onClick }: { store: StoreType; onClick: () => void }) {
  const coverUrl = store.cover_image ? getImageUrl(store.cover_image) : null
  return (
    <div onClick={onClick} className="bg-white rounded-2xl shadow-sm overflow-hidden active:scale-[0.98] transition-transform cursor-pointer">
      {/* Cover image */}
      <div className="h-32 bg-gray-100 flex items-center justify-center overflow-hidden relative">
        {coverUrl
          ? <img src={coverUrl} alt={store.store_name} className="w-full h-full object-cover" />
          : <Store size={36} className="text-gray-200" />}
        <div className="absolute top-2 right-2">
          <StoreBadge status={store.status} />
        </div>
      </div>
      {/* Info */}
      <div className="p-3">
        <h3 className="font-bold text-gray-900 text-sm leading-tight">{store.store_name}</h3>
        <p className="text-xs text-gray-500 mt-0.5 flex items-center gap-1 line-clamp-1">
          <MapPin size={11} />{store.city_name ?? ''}{store.area_name ? `, ${store.area_name}` : ''}
        </p>
        <p className="text-xs text-gray-400 mt-0.5 line-clamp-1">{store.address}</p>
        <div className="flex gap-3 mt-2">
          <span className="flex items-center gap-1 text-xs text-brand-600 font-semibold">
            <Ticket size={12} />{store.active_coupons} coupons
          </span>
          <span className="flex items-center gap-1 text-xs text-gray-400">
            <Image size={12} />{store.gallery_count} photos
          </span>
        </div>
      </div>
    </div>
  )
}

export default function StoreListPage() {
  const navigate = useNavigate()

  const { data, isLoading } = useQuery({
    queryKey: ['stores'],
    queryFn:  () => storeApi.list().then((r) => r.data.data),
    staleTime: 2 * 60 * 1000,
  })

  return (
    <div className="min-h-full bg-gray-50 pb-24">
      <div className="gradient-brand px-5 pt-14 pb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <div className="flex-1">
            <h1 className="text-white font-bold text-xl">Stores</h1>
            <p className="text-white/70 text-xs mt-0.5">
              {data ? `${data.length} store${data.length !== 1 ? 's' : ''}` : 'Loading…'}
            </p>
          </div>
        </div>
      </div>

      <div className="px-4 -mt-2 pt-4 space-y-0">
        {isLoading ? (
          <div className="grid grid-cols-1 gap-3">
            {[1, 2, 3].map((i) => <SkeletonCard key={i} />)}
          </div>
        ) : !data || data.length === 0 ? (
          <div className="flex flex-col items-center justify-center pt-20 gap-3 text-center px-8">
            <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
              <Store size={28} className="text-gray-300" />
            </div>
            <p className="font-semibold text-gray-500">No stores yet</p>
            <p className="text-xs text-gray-400">Add your first store to start managing coupons</p>
            <button
              onClick={() => navigate('/stores/new')}
              className="mt-2 px-5 py-2.5 gradient-brand text-white text-sm font-semibold rounded-xl"
            >Add Store</button>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-3">
            {data.map((store) => (
              <StoreCard key={store.id} store={store} onClick={() => navigate(`/stores/${store.id}`)} />
            ))}
          </div>
        )}
      </div>

      {/* FAB */}
      <button
        onClick={() => navigate('/stores/new')}
        className="fixed bottom-24 right-5 w-14 h-14 gradient-brand rounded-full shadow-lg flex items-center justify-center z-20 active:scale-95 transition-transform"
      >
        <Plus size={24} className="text-white" />
      </button>
    </div>
  )
}
