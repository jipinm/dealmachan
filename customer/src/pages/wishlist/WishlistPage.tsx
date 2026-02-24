import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Heart, Star, Tag, Trash2, Store } from 'lucide-react'
import { favouritesApi } from '@/api/endpoints/favourites'
import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'
import toast from 'react-hot-toast'

function SkeletonCard() {
  return (
    <div className="bg-white rounded-2xl border border-slate-100 overflow-hidden animate-pulse">
      <div className="h-28 bg-slate-200" />
      <div className="p-4 space-y-2">
        <div className="h-4 bg-slate-200 rounded w-3/4" />
        <div className="h-3 bg-slate-200 rounded w-1/2" />
        <div className="flex gap-2 mt-3">
          <div className="h-5 bg-slate-200 rounded-full w-16" />
          <div className="h-5 bg-slate-200 rounded-full w-20" />
        </div>
      </div>
    </div>
  )
}

export default function WishlistPage() {
  const qc = useQueryClient()

  const { data: favourites = [], isLoading } = useQuery({
    queryKey: ['favourites'],
    queryFn: favouritesApi.list,
  })

  const removeMutation = useMutation({
    mutationFn: (merchantId: number) => favouritesApi.remove(merchantId),
    onMutate: async (merchantId) => {
      await qc.cancelQueries({ queryKey: ['favourites'] })
      const prev = qc.getQueryData<typeof favourites>(['favourites'])
      qc.setQueryData(['favourites'], (old: typeof favourites = []) =>
        old.filter(m => m.id !== merchantId)
      )
      return { prev }
    },
    onError: (_err, _id, ctx) => {
      qc.setQueryData(['favourites'], ctx?.prev)
      toast.error('Could not remove from wishlist')
    },
    onSuccess: () => toast.success('Removed from wishlist'),
  })

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-8">
      {/* Header */}
      <div className="flex items-center gap-3 mb-6">
        <div className="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center">
          <Heart size={20} className="text-cta-500" />
        </div>
        <div>
          <h1 className="font-heading font-bold text-xl text-slate-800">My Wishlist</h1>
          {!isLoading && (
            <p className="text-slate-500 text-xs">
              {favourites.length} saved {favourites.length === 1 ? 'merchant' : 'merchants'}
            </p>
          )}
        </div>
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          {Array.from({ length: 8 }).map((_, i) => <SkeletonCard key={i} />)}
        </div>
      )}

      {/* Empty state */}
      {!isLoading && favourites.length === 0 && (
        <div className="text-center py-20">
          <div className="w-20 h-20 bg-rose-50 rounded-3xl flex items-center justify-center mx-auto mb-5">
            <Heart size={36} className="text-rose-300" />
          </div>
          <h2 className="font-heading font-bold text-xl text-slate-800 mb-2">Nothing saved yet</h2>
          <p className="text-slate-500 mb-8 max-w-sm mx-auto text-sm">
            Tap the heart icon on any merchant page to save it here for quick access.
          </p>
          <Link to="/stores" className="btn-primary !px-8 !py-3 !rounded-xl inline-flex items-center gap-2">
            <Store size={16} /> Browse Merchants
          </Link>
        </div>
      )}

      {/* Grid */}
      {!isLoading && favourites.length > 0 && (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          {favourites.map(merchant => {
            const logoSrc = getImageUrl(merchant.business_logo)
            return (
              <div key={merchant.id} className="group relative bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-brand-100 transition-all duration-200 overflow-hidden flex flex-col">
                {/* Logo / cover area */}
                <Link to={`/stores/${merchant.id}/${slugify(merchant.business_name)}`} className="block relative">
                  {logoSrc ? (
                    <img
                      src={logoSrc}
                      alt={merchant.business_name}
                      className="w-full h-28 object-cover"
                    />
                  ) : (
                    <div className="w-full h-28 bg-gradient-to-br from-brand-50 to-brand-100 flex items-center justify-center">
                      <Store size={32} className="text-brand-300" />
                    </div>
                  )}
                  {/* Active offers badge */}
                  {merchant.active_coupon_count > 0 && (
                    <span className="absolute top-2 left-2 text-[10px] font-bold text-brand-700 bg-white/90 backdrop-blur-sm border border-brand-100 px-2 py-0.5 rounded-full flex items-center gap-1 shadow-sm">
                      <Tag size={9} />
                      {merchant.active_coupon_count} {merchant.active_coupon_count === 1 ? 'offer' : 'offers'}
                    </span>
                  )}
                </Link>

                {/* Info */}
                <Link to={`/stores/${merchant.id}/${slugify(merchant.business_name)}`} className="flex-1 p-3 flex flex-col">
                  <p className="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 mb-0.5">
                    {merchant.business_name}
                  </p>
                  {merchant.business_category && (
                    <p className="text-[11px] text-slate-400 truncate mb-2">{merchant.business_category}</p>
                  )}
                  {merchant.avg_rating > 0 && (
                    <span className="flex items-center gap-1 text-xs text-amber-600 font-medium mt-auto">
                      <Star size={11} className="fill-amber-400 text-amber-400" />
                      {Number(merchant.avg_rating).toFixed(1)}
                      <span className="text-slate-400 font-normal">({merchant.total_reviews})</span>
                    </span>
                  )}
                </Link>

                {/* Remove button — visible on hover/focus */}
                <button
                  onClick={() => removeMutation.mutate(merchant.id)}
                  disabled={removeMutation.isPending}
                  className="absolute top-2 right-2 w-7 h-7 rounded-full bg-white/90 backdrop-blur-sm border border-slate-100 shadow-sm flex items-center justify-center text-slate-400 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200 transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100"
                  aria-label={`Remove ${merchant.business_name} from wishlist`}
                >
                  <Trash2 size={12} />
                </button>
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}
