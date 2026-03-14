import { Link } from 'react-router-dom'
import { MapPin, Tag } from 'lucide-react'
import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'
import type { PublicStore } from '@/api/endpoints/public'

interface Props {
  store: PublicStore
}

export default function StoreCard({ store }: Props) {
  const initial   = store.store_name.charAt(0).toUpperCase()
  const couponCnt = store.active_coupons_count ?? 0

  return (
    <Link
      to={`/stores/${store.id}/${slugify(store.store_name)}`}
      className="card group flex flex-col overflow-hidden hover:shadow-lg transition-shadow"
    >
      {/* Banner / logo */}
      <div className="relative h-32 bg-gradient-to-br from-brand-100 to-purple-100 flex items-center justify-center overflow-hidden shrink-0">
        {store.business_logo ? (
          <img
            src={getImageUrl(store.business_logo)}
            alt={store.store_name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-16 h-16 rounded-2xl gradient-brand flex items-center justify-center shadow-brand">
            <span className="text-white font-heading font-bold text-2xl">{initial}</span>
          </div>
        )}

        {/* Category badges */}
        {store.categories && store.categories.length > 0 && (
          <span className="absolute top-2 left-2 badge badge-brand text-[10px]">
            {store.categories[0].icon && <span className="mr-0.5">{store.categories[0].icon}</span>}
            {store.categories[0].name}
          </span>
        )}

        {/* Coupon count chip */}
        {couponCnt > 0 && (
          <div className="absolute bottom-2 right-2 flex items-center gap-1 bg-white/90 rounded-full px-2 py-0.5 shadow text-[10px] font-semibold text-brand-700">
            <Tag size={10} />
            {couponCnt} deal{couponCnt !== 1 ? 's' : ''}
          </div>
        )}
      </div>

      {/* Info */}
      <div className="p-3 flex flex-col gap-1">
        <h3 className="font-heading font-semibold text-sm text-gray-900 truncate leading-tight">
          {store.store_name}
        </h3>

        <p className="text-[11px] text-gray-500 truncate">{store.business_name}</p>

        {(store.area_name || store.city_name) && (
          <div className="flex items-center gap-1 text-xs text-gray-400">
            <MapPin size={10} className="shrink-0" />
            <span className="truncate">{store.area_name || store.city_name}</span>
          </div>
        )}

        {store.avg_rating != null && store.avg_rating > 0 && (
          <div className="flex items-center gap-1 mt-0.5">
            <span className="text-yellow-400 text-xs">★</span>
            <span className="text-xs font-semibold text-gray-700">{Number(store.avg_rating).toFixed(1)}</span>
            {store.total_reviews > 0 && (
              <span className="text-xs text-gray-400">({store.total_reviews})</span>
            )}
          </div>
        )}
      </div>
    </Link>
  )
}
