import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Heart, MapPin, Star, Clock, Globe, ChevronLeft, Loader2, Images, X, ChevronRight as Next, ChevronLeft as Prev } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import { favouritesApi } from '@/api/endpoints/favourites'
import { useAuthStore } from '@/store/authStore'
import CouponCard from '@/components/ui/CouponCard'
import SkeletonCard, { SkeletonRow } from '@/components/ui/SkeletonCard'
import { getApiError } from '@/api/client'
import { getImageUrl } from '@/lib/imageUrl'
import toast from 'react-hot-toast'

type Tab = 'coupons' | 'stores' | 'reviews' | 'gallery'

export default function MerchantDetailPage() {
  const { id } = useParams<{ id: string }>()
  const merchantId = Number(id)
  const { isAuthenticated } = useAuthStore()
  const qc = useQueryClient()

  const [tab, setTab] = useState<Tab>('coupons')
  const [lightboxIdx, setLightboxIdx] = useState<number | null>(null)

  const { data: merchantData, isLoading } = useQuery({
    queryKey: ['merchant-detail', merchantId],
    queryFn: () => publicApi.getMerchant(merchantId).then((r) => r.data.data),
    enabled: !!merchantId,
    staleTime: 60_000,
  })

  const { data: favData } = useQuery({
    queryKey: ['fav-check', merchantId],
    queryFn: () => favouritesApi.check(merchantId).then((r) => r.is_favourite ?? false),
    enabled: isAuthenticated && !!merchantId,
    staleTime: 60_000,
  })

  const isFavourited = favData === true

  const { data: reviewsData, isLoading: reviewsLoading } = useQuery({
    queryKey: ['merchant-reviews', merchantId],
    queryFn: () => publicApi.getMerchantReviews(merchantId).then((r) => r.data.data ?? []),
    enabled: tab === 'reviews',
    staleTime: 60_000,
  })

  const { data: galleryData, isLoading: galleryLoading } = useQuery({
    queryKey: ['merchant-gallery', merchantId],
    queryFn: () => publicApi.getMerchantGallery(merchantId).then((r) => r.data.data ?? []),
    enabled: tab === 'gallery',
    staleTime: 300_000,
  })

  const favMutation = useMutation({
    mutationFn: async () => {
      if (!isAuthenticated) throw new Error('Please log in to save favourites')
      return isFavourited
        ? favouritesApi.remove(merchantId)
        : favouritesApi.add(merchantId)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['fav-check', merchantId] })
      qc.invalidateQueries({ queryKey: ['favourites'] })
      toast.success(isFavourited ? 'Removed from favourites' : 'Added to favourites')
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const merchant = (merchantData as any)?.merchant
  const stores   = (merchantData as any)?.stores  ?? []
  const coupons  = (merchantData as any)?.coupons ?? []
  const reviews  = (reviewsData as any[]) ?? []
  const gallery  = (galleryData  as any[]) ?? []

  if (isLoading) {
    return (
      <div className="max-w-[1200px] mx-auto px-4 py-5 space-y-4">
        <div className="skeleton h-48 rounded-2xl" />
        <SkeletonCard />
        <SkeletonRow />
        <SkeletonRow />
      </div>
    )
  }

  if (!merchant) {
    return (
      <div className="text-center py-20 px-4">
        <p className="text-4xl mb-3">😕</p>
        <p className="text-gray-500">Merchant not found.</p>
        <Link to="/explore" className="mt-4 inline-block text-brand-600 text-sm font-semibold hover:underline">Back to Explore</Link>
      </div>
    )
  }

  const initial = merchant.business_name.charAt(0).toUpperCase()

  return (
    <div className="max-w-[1200px] mx-auto pb-8">
      {/* ── Hero ─────────────────────────────────────────────────────────── */}
      <div className="relative h-48 bg-gradient-to-br from-brand-100 to-purple-100 overflow-hidden">
        {merchant.business_logo ? (
          <img src={getImageUrl(merchant.business_logo)} alt={merchant.business_name} loading="lazy" className="w-full h-full object-cover" />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <div className="w-24 h-24 rounded-3xl gradient-brand flex items-center justify-center shadow-brand">
              <span className="text-white font-heading font-bold text-5xl">{initial}</span>
            </div>
          </div>
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />

        {/* Back button */}
        <Link to="/explore" className="absolute top-4 left-4 w-9 h-9 rounded-full bg-black/30 backdrop-blur flex items-center justify-center text-white">
          <ChevronLeft size={18} />
        </Link>

        {/* Favourite button */}
        <button
          onClick={() => favMutation.mutate()}
          className="absolute top-4 right-4 w-9 h-9 rounded-full bg-black/30 backdrop-blur flex items-center justify-center"
        >
          {favMutation.isPending
            ? <Loader2 size={16} className="animate-spin text-white" />
            : <Heart size={16} className={isFavourited ? 'fill-red-400 text-red-400' : 'text-white'} />
          }
        </button>

        {/* Name overlay */}
        <div className="absolute bottom-4 left-4 right-4">
          <h1 className="text-white font-heading font-bold text-2xl leading-tight drop-shadow">{merchant.business_name}</h1>
          {merchant.business_category && (
            <span className="text-white/70 text-xs">{merchant.business_category}</span>
          )}
        </div>
      </div>

      {/* ── Meta row ─────────────────────────────────────────────────────── */}
      <div className="px-4 mt-4 flex flex-wrap gap-3">
        {merchant.avg_rating > 0 && (
          <span className="flex items-center gap-1 text-sm text-gray-700">
            <Star size={14} className="fill-yellow-400 text-yellow-400" />
            <span className="font-semibold">{Number(merchant.avg_rating).toFixed(1)}</span>
            {merchant.total_reviews > 0 && <span className="text-gray-400">({merchant.total_reviews})</span>}
          </span>
        )}
        {coupons.length > 0 && (
          <span className="text-sm text-gray-500">🏷️ {coupons.length} active deal{coupons.length !== 1 ? 's' : ''}</span>
        )}
        {merchant.website_url && (
          <a href={merchant.website_url} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1 text-xs text-brand-600 hover:underline">
            <Globe size={12} /> Website
          </a>
        )}
      </div>

      {/* ── Description ──────────────────────────────────────────────────── */}
      {merchant.business_description && (
        <p className="px-4 mt-3 text-sm text-gray-600">{merchant.business_description}</p>
      )}

      {/* ── Tabs ─────────────────────────────────────────────────────────── */}
      <div className="px-4 mt-5 flex bg-gray-100 rounded-xl p-1 gap-1">
        {(['coupons', 'stores', 'reviews', 'gallery'] as Tab[]).map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`flex-1 py-2 rounded-lg text-xs font-semibold capitalize transition-all ${
              tab === t ? 'bg-white text-brand-700 shadow' : 'text-gray-500'
            }`}
          >
            {t === 'coupons' && `🏷️ Deals (${coupons.length})`}
            {t === 'stores'  && `📍 Stores (${stores.length})`}
            {t === 'reviews' && `⭐ Reviews`}
            {t === 'gallery' && `🖼️ Photos`}
          </button>
        ))}
      </div>

      {/* ── Tab content ──────────────────────────────────────────────────── */}
      <div className="px-4 mt-4">
        {/* Coupons */}
        {tab === 'coupons' && (
          coupons.length > 0
            ? <div className="grid sm:grid-cols-2 gap-3">{coupons.map((c: any) => (
                <CouponCard key={c.id} coupon={{ ...c, business_name: merchant.business_name, business_logo: merchant.business_logo }} />
              ))}</div>
            : <p className="text-sm text-gray-400 text-center py-8">No active deals right now.</p>
        )}

        {/* Stores */}
        {tab === 'stores' && (
          stores.length > 0
            ? <div className="grid sm:grid-cols-2 gap-3">{stores.map((s: any) => (
                <div key={s.id} className="card p-4 space-y-1">
                  <h3 className="font-semibold text-gray-900 text-sm">{s.store_name ?? s.name}</h3>
                  {s.address && (
                    <div className="flex items-start gap-1.5 text-xs text-gray-500">
                      <MapPin size={12} className="mt-0.5 shrink-0 text-brand-400" />
                      <span>{s.address}{s.area_name ? `, ${s.area_name}` : ''}{s.city_name ? `, ${s.city_name}` : ''}</span>
                    </div>
                  )}
                  {s.opening_hours && Array.isArray(s.opening_hours) && (() => {
                    const today = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][new Date().getDay()]
                    const todayHours = s.opening_hours.find((h: any) => h.day?.toLowerCase() === today.toLowerCase())
                    if (!todayHours) return null
                    return (
                      <div className="flex items-center gap-1.5 text-xs text-gray-400">
                        <Clock size={11} />
                        {todayHours.closed ? 'Closed today' : `Today: ${todayHours.open ?? ''} – ${todayHours.close ?? ''}`}
                      </div>
                    )
                  })()}
                  {s.phone && (
                    <a href={`tel:${s.phone}`} className="text-xs text-brand-600 hover:underline">{s.phone}</a>
                  )}
                </div>
              ))}</div>
            : <p className="text-sm text-gray-400 text-center py-8">No stores listed.</p>
        )}

        {/* Reviews */}
        {tab === 'reviews' && (
          reviewsLoading
            ? <div className="grid sm:grid-cols-2 gap-3">{Array.from({ length: 3 }).map((_, i) => <SkeletonRow key={i} />)}</div>
            : reviews.length > 0
              ? <div className="grid sm:grid-cols-2 gap-3">{reviews.map((r: any) => (
                  <div key={r.id} className="card p-4">
                    <div className="flex items-center gap-2 mb-1">
                      <div className="w-8 h-8 rounded-full gradient-brand flex items-center justify-center text-white text-xs font-bold">
                        {r.customer_name?.charAt(0).toUpperCase() ?? '?'}
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-gray-900">{r.customer_name}</p>
                        <div className="flex text-yellow-400 text-xs">
                          {'★'.repeat(r.rating ?? 0)}{'☆'.repeat(5 - (r.rating ?? 0))}
                        </div>
                      </div>
                    </div>
                    {r.review_text && <p className="text-xs text-gray-600">{r.review_text}</p>}
                  </div>
                ))}</div>
              : <p className="text-sm text-gray-400 text-center py-8">No reviews yet.</p>
        )}

        {/* Gallery */}
        {tab === 'gallery' && (
          galleryLoading
            ? <div className="grid grid-cols-3 gap-1.5">{Array.from({ length: 9 }).map((_, i) => <div key={i} className="aspect-square skeleton rounded-xl" />)}</div>
            : gallery.length > 0
              ? (
                <>
                  <div className="grid grid-cols-3 gap-1.5">
                    {gallery.map((img: any, idx: number) => (
                      <button
                        key={img.id}
                        onClick={() => setLightboxIdx(idx)}
                        className="relative aspect-square rounded-xl overflow-hidden group"
                      >
                        <img
                          src={img.image_url}
                          alt={img.store_name}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                        />
                        <div className="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity p-1.5">
                          <p className="text-white text-[10px] truncate">{img.store_name}</p>
                        </div>
                      </button>
                    ))}
                  </div>

                  {/* Lightbox */}
                  {lightboxIdx !== null && (
                    <div
                      className="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
                      onClick={() => setLightboxIdx(null)}
                    >
                      <button
                        className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white"
                        onClick={() => setLightboxIdx(null)}
                      >
                        <X size={18} />
                      </button>
                      {lightboxIdx > 0 && (
                        <button
                          className="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white"
                          onClick={(e) => { e.stopPropagation(); setLightboxIdx(lightboxIdx - 1) }}
                        >
                          <Prev size={20} />
                        </button>
                      )}
                      {lightboxIdx < gallery.length - 1 && (
                        <button
                          className="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white"
                          onClick={(e) => { e.stopPropagation(); setLightboxIdx(lightboxIdx + 1) }}
                        >
                          <Next size={20} />
                        </button>
                      )}
                      <div className="max-w-lg w-full" onClick={(e) => e.stopPropagation()}>
                        <img
                          src={gallery[lightboxIdx].image_url}
                          alt={gallery[lightboxIdx].store_name}
                          className="w-full rounded-2xl object-contain max-h-[80vh]"
                        />
                        <p className="text-white/70 text-sm text-center mt-3">
                          {gallery[lightboxIdx].store_name} · {lightboxIdx + 1}/{gallery.length}
                        </p>
                      </div>
                    </div>
                  )}
                </>
              )
              : (
                <div className="text-center py-12 text-gray-400">
                  <Images size={40} className="mx-auto mb-3 opacity-30" />
                  <p className="text-sm">No photos uploaded yet.</p>
                </div>
              )
        )}
      </div>
    </div>
  )
}
