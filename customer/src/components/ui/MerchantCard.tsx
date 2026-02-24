import { Link } from 'react-router-dom'
import { Heart, MapPin, Tag } from 'lucide-react'
import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'
import { useAuthStore } from '@/store/authStore'
import { favouritesApi } from '@/api/endpoints/favourites'

export interface MerchantCardData {
  id: number
  business_name: string
  business_logo: string | null
  business_category: string | null
  business_description?: string | null
  city_name?: string | null
  area_name?: string | null
  avg_rating?: number | null
  total_reviews?: number | null
  active_coupon_count?: number
  subscription_status?: string
}

interface Props {
  merchant: MerchantCardData
  showFavourite?: boolean
  isFavourited?: boolean
  onFavouriteChange?: (id: number, newState: boolean) => void
}

export default function MerchantCard({ merchant, showFavourite, isFavourited = false, onFavouriteChange }: Props) {
  const { isAuthenticated } = useAuthStore()
  const [favoured, setFavoured] = useState(isFavourited)

  const addMutation = useMutation({
    mutationFn: () => favouritesApi.add(merchant.id),
    onSuccess: () => { setFavoured(true); onFavouriteChange?.(merchant.id, true) },
  })
  const removeMutation = useMutation({
    mutationFn: () => favouritesApi.remove(merchant.id),
    onSuccess: () => { setFavoured(false); onFavouriteChange?.(merchant.id, false) },
  })

  function handleFav(e: React.MouseEvent) {
    e.preventDefault()
    e.stopPropagation()
    if (!isAuthenticated) return
    if (addMutation.isPending || removeMutation.isPending) return
    favoured ? removeMutation.mutate() : addMutation.mutate()
  }

  const initial = merchant.business_name.charAt(0).toUpperCase()
  const couponCount = merchant.active_coupon_count ?? 0

  return (
    <Link
      to={`/stores/${merchant.id}/${slugify(merchant.business_name)}`}
      className="card group flex flex-col overflow-hidden hover:shadow-lg transition-shadow"
    >
      {/* Logo / banner */}
      <div className="relative h-32 bg-gradient-to-br from-brand-100 to-purple-100 flex items-center justify-center overflow-hidden shrink-0">
        {merchant.business_logo ? (
          <img
            src={getImageUrl(merchant.business_logo)}
            alt={merchant.business_name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-16 h-16 rounded-2xl gradient-brand flex items-center justify-center shadow-brand">
            <span className="text-white font-heading font-bold text-2xl">{initial}</span>
          </div>
        )}

        {/* Category badge */}
        {merchant.business_category && (
          <span className="absolute top-2 left-2 badge badge-brand text-[10px]">
            {merchant.business_category}
          </span>
        )}

        {/* Favourite button */}
        {showFavourite && isAuthenticated && (
          <button
            onClick={handleFav}
            className="absolute top-2 right-2 w-7 h-7 rounded-full bg-white/90 flex items-center justify-center shadow"
            aria-label={favoured ? 'Remove from wishlist' : 'Add to wishlist'}
          >
            <Heart
              size={14}
              className={favoured ? 'fill-red-500 text-red-500' : 'text-gray-400'}
            />
          </button>
        )}

        {/* Coupon count chip */}
        {couponCount > 0 && (
          <div className="absolute bottom-2 right-2 flex items-center gap-1 bg-white/90 rounded-full px-2 py-0.5 shadow text-[10px] font-semibold text-brand-700">
            <Tag size={10} />
            {couponCount} deal{couponCount !== 1 ? 's' : ''}
          </div>
        )}
      </div>

      {/* Info */}
      <div className="p-3 flex flex-col gap-1">
        <h3 className="font-heading font-semibold text-sm text-gray-900 truncate leading-tight">
          {merchant.business_name}
        </h3>

        {(merchant.area_name || merchant.city_name) && (
          <div className="flex items-center gap-1 text-xs text-gray-400">
            <MapPin size={10} className="shrink-0" />
            <span className="truncate">{merchant.area_name || merchant.city_name}</span>
          </div>
        )}

        {merchant.avg_rating != null && merchant.avg_rating > 0 && (
          <div className="flex items-center gap-1 mt-0.5">
            <span className="text-yellow-400 text-xs">?</span>
            <span className="text-xs font-semibold text-gray-700">{Number(merchant.avg_rating).toFixed(1)}</span>
            {merchant.total_reviews != null && (
              <span className="text-xs text-gray-400">({merchant.total_reviews})</span>
            )}
          </div>
        )}
      </div>
    </Link>
  )
}
