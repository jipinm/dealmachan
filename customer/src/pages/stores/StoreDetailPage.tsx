import { useState } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { publicApi, type PublicStoreDetail, type StoreReview } from '@/api/endpoints/public'
import { favouritesApi } from '@/api/endpoints/favourites'
import { Star, MapPin, Phone, Tag, ChevronRight, Store, Clock, ArrowLeft, MessageSquare, User, Mail, Navigation, Send, Heart, Lock, Loader2 as HeartLoader } from 'lucide-react'
import RatingStars from '@/components/ui/RatingStars'
import { getImageUrl } from '@/lib/imageUrl'
import { slugify } from '@/lib/slugify'
import { useAuthStore } from '@/store/authStore'
import { Helmet } from 'react-helmet-async'
import toast from 'react-hot-toast'

function imgSrc(path: string | null): string {
  return getImageUrl(path)
}

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

function TodayHours({ hours }: { hours: { day: string; open?: string; close?: string; closed?: boolean }[] }) {
  if (!hours?.length) return null
  const todayName = DAYS[new Date().getDay()]
  const today = hours.find(h => h.day?.toLowerCase() === todayName.toLowerCase())
  if (!today) return null
  return (
    <span className={`flex items-center gap-1 text-xs mt-1 font-medium ${today.closed ? 'text-red-500' : 'text-green-600'}`}>
      <Clock size={11} />
      {today.closed ? 'Closed today' : `Today: ${today.open ?? ''} – ${today.close ?? ''}`}
    </span>
  )
}

function OpeningHoursGrid({ hours }: { hours: { day: string; open?: string; close?: string; closed?: boolean }[] }) {
  if (!hours?.length) return null
  const todayName = DAYS[new Date().getDay()]
  return (
    <div className="mt-3 grid grid-cols-2 gap-x-4 gap-y-0.5 text-[11px]">
      {hours.map(h => (
        <div key={h.day} className={`flex justify-between gap-2 py-0.5 border-b border-slate-50 ${
          h.day?.toLowerCase() === todayName.toLowerCase() ? 'font-semibold text-slate-800' : 'text-slate-500'
        }`}>
          <span className="truncate">{h.day?.slice(0, 3)}</span>
          {h.closed
            ? <span className="text-red-400">Closed</span>
            : <span>{h.open} – {h.close}</span>
          }
        </div>
      ))}
    </div>
  )
}

function StarPicker({ value, onChange }: { value: number; onChange: (n: number) => void }) {
  const [hover, setHover] = useState(0)
  return (
    <div className="flex items-center gap-1">
      {[1, 2, 3, 4, 5].map(n => (
        <button
          key={n}
          type="button"
          onMouseEnter={() => setHover(n)}
          onMouseLeave={() => setHover(0)}
          onClick={() => onChange(n)}
          className="p-0.5 transition-transform hover:scale-110"
          aria-label={`${n} star`}
        >
          <Star
            size={24}
            className={`transition-colors ${
              n <= (hover || value) ? 'text-amber-400 fill-amber-400' : 'text-slate-300'
            }`}
          />
        </button>
      ))}
    </div>
  )
}

function timeLeft(until: string | null): string {
  if (!until) return 'Limited time'
  const diff = new Date(until).getTime() - Date.now()
  if (diff <= 0) return 'Expired'
  const h = Math.floor(diff / 3_600_000)
  if (h >= 24) return `${Math.floor(h / 24)}d left`
  return `${h}h left`
}

export default function StoreDetailPage() {
  const { id } = useParams<{ id: string; slug?: string }>()
  const { isAuthenticated } = useAuthStore()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [rating, setRating] = useState(0)
  const [reviewText, setReviewText] = useState('')
  const [showHours, setShowHours] = useState(false)
  const [reviewSubmitted, setReviewSubmitted] = useState(false)

  // ── Store data ───────────────────────────────────────────────────────────
  const { data: storeDetail, isLoading } = useQuery({
    queryKey: ['store', id],
    queryFn: () =>
      publicApi.getStore(Number(id)).then((r) => {
        const payload = (r.data as any).data
        return payload as { store: PublicStoreDetail; coupons: any[]; reviews: StoreReview[] }
      }),
    enabled: !!id,
  })

  const store = storeDetail?.store
  const coupons = storeDetail?.coupons ?? []
  const reviews = storeDetail?.reviews ?? []

  // ── Favourite state (merchant-scoped, derived from loaded store) ─────────
  const merchantIdForFav = store?.merchant_id ?? 0

  const { data: favData } = useQuery({
    queryKey: ['fav-check', merchantIdForFav],
    queryFn: () => favouritesApi.check(merchantIdForFav).then((r) => r.is_favourite ?? false),
    enabled: !!store && isAuthenticated,
    staleTime: 60_000,
  })
  const isFavourited = favData === true

  const favMutation = useMutation({
    mutationFn: () => {
      if (!isAuthenticated) throw new Error('login')
      return isFavourited
        ? favouritesApi.remove(merchantIdForFav)
        : favouritesApi.add(merchantIdForFav)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['fav-check', merchantIdForFav] })
      queryClient.invalidateQueries({ queryKey: ['favourites'] })
      toast.success(isFavourited ? 'Removed from favourites' : 'Added to favourites')
    },
    onError: (err: any) => {
      if (err.message === 'login') {
        navigate(`/login?redirect=${encodeURIComponent(location.pathname)}`)
      } else {
        toast.error('Could not update favourites')
      }
    },
  })

  const reviewMutation = useMutation({
    mutationFn: (vars: { rating: number; review_text?: string }) =>
      publicApi.submitStoreReview(Number(id), vars),
    onSuccess: () => {
      setReviewSubmitted(true)
      setRating(0)
      setReviewText('')
      queryClient.invalidateQueries({ queryKey: ['store', id] })
    },
  })

  if (isLoading) {
    return (
      <div className="site-container py-10">
        <div className="h-48 skeleton rounded-3xl mb-6" />
        <div className="h-8 skeleton rounded-xl w-1/2 mb-3" />
        <div className="grid grid-cols-2 gap-3">
          {[1, 2, 3, 4].map((i) => <div key={i} className="h-48 skeleton rounded-2xl" />)}
        </div>
      </div>
    )
  }

  if (!store) {
    return (
      <div className="site-container py-20 text-center text-slate-400">
        <Store size={48} className="mx-auto mb-4 opacity-30" />
        <p className="font-semibold">Store not found</p>
        <Link to="/stores" className="mt-4 btn-primary !px-6 !py-2.5 !text-sm !rounded-xl inline-flex items-center gap-2">
          <ArrowLeft size={16} /> Back to Stores
        </Link>
      </div>
    )
  }

  return (
    <div>
      <Helmet>
        <title>{`${store.store_name} | Deal Machan`}</title>
        <meta name="description" content={`${store.store_name} in ${store.area_name ?? store.city_name ?? 'your area'} – browse exclusive deals, coupons, and store information on Deal Machan.`} />
      </Helmet>
      {/* Breadcrumb */}
      <div className="bg-white border-b border-slate-100">
        <div className="site-container py-3">
          <nav className="flex items-center gap-1 text-sm text-slate-500">
            <Link to="/" className="hover:text-brand-600">Home</Link>
            <ChevronRight size={13} />
            <Link to="/stores" className="hover:text-brand-600">Stores</Link>
            <ChevronRight size={13} />
            <span className="text-slate-800 font-medium truncate max-w-[200px]">{store.store_name}</span>
          </nav>
        </div>
      </div>

      <div className="site-container py-8">
        <div className="lg:grid lg:grid-cols-[300px,1fr] lg:gap-8">
          {/* Sidebar: store info */}
          <aside>
            <div className="card p-6 mb-5 sticky top-24">
              {/* Store image */}
              <div className="w-full h-32 rounded-2xl bg-slate-100 overflow-hidden mb-4">
                {(store.store_image || store.business_logo) ? (
                  <img src={imgSrc(store.store_image ?? store.business_logo)} alt={store.store_name} loading="lazy" className="w-full h-full object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <Store size={36} className="text-slate-300" />
                  </div>
                )}
              </div>

              {/* Store name + favourite */}
              <div className="flex items-center justify-between mb-2">
                <h1 className="font-heading font-bold text-xl text-slate-800 flex-1">{store.store_name}</h1>
                <button
                  onClick={() => favMutation.mutate()}
                  disabled={favMutation.isPending}
                  aria-label={isFavourited ? 'Remove from favourites' : 'Add to favourites'}
                  className="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 transition-colors hover:bg-rose-50"
                >
                  {favMutation.isPending
                    ? <HeartLoader size={16} className="animate-spin text-slate-400" />
                    : <Heart size={18} className={isFavourited ? 'fill-rose-500 text-rose-500' : 'text-slate-300 hover:text-rose-400'} />
                  }
                </button>
              </div>

              {/* Rating */}
              <div className="flex items-center gap-1.5 mb-3">
                <RatingStars rating={store.avg_rating ?? 0} size={15} showValue reviewCount={store.total_reviews ?? 0} />
              </div>

              {/* Description */}
              {store.description && (
                <p className="text-sm text-slate-500 leading-relaxed mb-3">{store.description}</p>
              )}

              {/* Address */}
              {store.address && (
                <div className="flex items-start gap-2 text-sm text-slate-500 mb-2">
                  <MapPin size={14} className="flex-shrink-0 mt-0.5 text-slate-400" />
                  <span className="leading-relaxed">
                    {store.address}
                    {store.area_name ? `, ${store.area_name}` : ''}
                    {store.city_name ? `, ${store.city_name}` : ''}
                  </span>
                </div>
              )}

              {/* Phone */}
              {store.phone && (
                <a href={`tel:${store.phone}`} className="flex items-center gap-2 text-sm text-brand-600 hover:underline mb-2">
                  <Phone size={13} /> {store.phone}
                </a>
              )}

              {/* Email */}
              {store.email && (
                <a href={`mailto:${store.email}`} className="flex items-center gap-2 text-sm text-brand-600 hover:underline mb-2">
                  <Mail size={13} /> {store.email}
                </a>
              )}

              {/* Directions */}
              {store.latitude && store.longitude && (
                <a
                  href={`https://www.google.com/maps/dir/?api=1&destination=${store.latitude},${store.longitude}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-2 text-sm text-emerald-600 hover:underline mb-2"
                >
                  <Navigation size={13} /> Get Directions
                </a>
              )}

              {/* Opening hours */}
              {store.opening_hours?.length > 0 && (
                <div className="mt-3">
                  <TodayHours hours={store.opening_hours} />
                  <button
                    onClick={() => setShowHours(!showHours)}
                    className="mt-1 text-[11px] text-slate-400 hover:text-brand-600 underline"
                  >
                    {showHours ? 'Hide hours' : 'View all hours'}
                  </button>
                  {showHours && <OpeningHoursGrid hours={store.opening_hours} />}
                </div>
              )}

              {/* Categories */}
              {store.categories?.length > 0 && (
                <div className="flex flex-wrap gap-1.5 mt-4">
                  {store.categories.map((cat) => (
                    <span key={cat.id} className="px-2.5 py-1 bg-brand-50 text-brand-700 text-xs font-medium rounded-full">
                      {cat.name}
                    </span>
                  ))}
                </div>
              )}

              {/* Merchant attribution */}
              <div className="mt-4 pt-4 border-t border-slate-100">
                <Link
                  to={`/merchants/${store.merchant_id}`}
                  className="flex items-center gap-2 text-sm text-slate-500 hover:text-brand-600 transition-colors group"
                >
                  <div className="w-8 h-8 rounded-full bg-slate-100 overflow-hidden flex-shrink-0">
                    {store.business_logo ? (
                      <img src={imgSrc(store.business_logo)} alt={store.business_name} className="w-full h-full object-cover" />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-slate-400 text-xs font-bold">{store.business_name?.charAt(0)}</div>
                    )}
                  </div>
                  <span className="truncate group-hover:underline">{store.business_name}</span>
                  <ChevronRight size={13} className="flex-shrink-0 ml-auto" />
                </Link>
              </div>
            </div>
          </aside>

          {/* Main: deals + reviews */}
          <main>
            {/* Active deals */}
            <section className="mb-8">
              <h2 className="section-heading mb-4 text-xl">
                Active Deals <span className="text-slate-400 text-base font-normal">({coupons?.length ?? 0})</span>
              </h2>
              {(coupons ?? []).length > 0 ? (
                <div className="grid sm:grid-cols-2 gap-4">
                  {(coupons ?? []).map((c) => (
                    <Link
                      key={c.id}
                      to={`/deals/${c.id}/${slugify(c.title)}`}
                      className="card card-hover p-4 block"
                    >
                      <div className="flex items-start justify-between mb-2">
                        <h3 className="font-semibold text-slate-800 text-sm line-clamp-2 flex-1 mr-2">{c.title}</h3>
                        <span className="badge-discount flex-shrink-0">
                          {c.discount_type === 'percentage' ? `${c.discount_value}%` : `₹${c.discount_value}`} OFF
                        </span>
                      </div>
                      {c.banner_image && (
                        <div className="h-24 rounded-xl overflow-hidden mb-2 -mx-0">
                          <img
                            src={imgSrc(c.banner_image)}
                            alt={c.title}
                            className="w-full h-full object-cover"
                            onError={(e) => { (e.target as HTMLImageElement).style.display = 'none' }}
                          />
                        </div>
                      )}
                      <div className="flex items-center justify-between mt-2">
                        {c.coupon_code ? (
                          <span className="font-mono text-xs bg-slate-50 text-slate-600 px-2 py-1 rounded-lg border border-dashed border-slate-300">
                            {c.coupon_code}
                          </span>
                        ) : (
                          <Link
                            to="/login"
                            onClick={(e) => e.stopPropagation()}
                            className="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-400 px-2 py-1 rounded-lg border border-dashed border-slate-200 hover:border-brand-300 hover:text-brand-500 transition-colors"
                          >
                            <Lock size={10} /> Login to see code
                          </Link>
                        )}
                        {c.valid_until && (
                          <span className="flex items-center gap-1 text-xs text-slate-400">
                            <Clock size={11} /> {timeLeft(c.valid_until)}
                          </span>
                        )}
                      </div>
                    </Link>
                  ))}
                </div>
              ) : (
                <div className="text-center py-10 text-slate-400 bg-white rounded-2xl border border-slate-100">
                  <Tag size={32} className="mx-auto mb-2 opacity-30" />
                  <p className="text-sm">No active deals right now</p>
                </div>
              )}
            </section>

            {/* Reviews */}
            <section>
              <h2 className="section-heading mb-4 text-xl flex items-center gap-2">
                <MessageSquare size={18} className="text-slate-400" />
                Reviews
                <span className="text-slate-400 text-base font-normal">({reviews.length})</span>
              </h2>

              {reviews.length === 0 ? (
                <div className="text-center py-10 bg-white rounded-2xl border border-slate-100">
                  <Star size={30} className="mx-auto mb-2 text-slate-200 fill-slate-100" />
                  <p className="text-sm text-slate-400">No reviews yet — be the first!</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {reviews.map((review: any) => (
                    <div key={review.id} className="card p-4">
                      <div className="flex items-start justify-between gap-3 mb-2">
                        <div className="flex items-center gap-2">
                          <div className="w-8 h-8 rounded-full bg-brand-50 flex items-center justify-center flex-shrink-0">
                            <User size={14} className="text-brand-400" />
                          </div>
                          <div>
                            <p className="text-sm font-semibold text-slate-800 leading-tight">
                              {review.customer_name ?? 'Anonymous'}
                            </p>
                            <p className="text-[11px] text-slate-400">
                              {new Date(review.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                            </p>
                          </div>
                        </div>
                        <RatingStars rating={review.rating} size={13} />
                      </div>
                      {review.review_text && (
                        <p className="text-sm text-slate-600 leading-relaxed">{review.review_text}</p>
                      )}
                    </div>
                  ))}
                </div>
              )}

              {/* Review submission form */}
              <div className="mt-6">
                <h3 className="font-semibold text-slate-800 mb-3 text-base">Write a Review</h3>
                {!isAuthenticated ? (
                  <div className="card p-5 text-center border-dashed">
                    <MessageSquare size={28} className="mx-auto mb-2 text-slate-300" />
                    <p className="text-sm text-slate-500 mb-3">Sign in to share your experience</p>
                    <Link to={`/login?redirect=${encodeURIComponent(location.pathname)}`} className="btn-primary !px-5 !py-2 !text-sm inline-block">
                      Sign in
                    </Link>
                  </div>
                ) : reviewSubmitted ? (
                  <div className="card p-5 text-center bg-green-50 border-green-100">
                    <Star size={28} className="mx-auto mb-2 text-amber-400 fill-amber-300" />
                    <p className="text-sm font-semibold text-green-700">Thanks for your review!</p>
                    <p className="text-xs text-green-600 mt-1">It will appear after admin approval.</p>
                    <button onClick={() => setReviewSubmitted(false)} className="mt-3 text-xs text-brand-600 underline">Edit review</button>
                  </div>
                ) : (
                  <form
                    className="card p-5 space-y-4"
                    onSubmit={(e) => {
                      e.preventDefault()
                      if (rating === 0) return
                      reviewMutation.mutate({ rating, review_text: reviewText || undefined })
                    }}
                  >
                    <div>
                      <p className="text-sm text-slate-600 mb-2">Your rating <span className="text-red-500">*</span></p>
                      <StarPicker value={rating} onChange={setRating} />
                      {rating === 0 && reviewMutation.isError && (
                        <p className="text-xs text-red-500 mt-1">Please select a rating.</p>
                      )}
                    </div>
                    <div>
                      <textarea
                        value={reviewText}
                        onChange={e => setReviewText(e.target.value)}
                        rows={3}
                        placeholder="Tell others about your experience (optional)…"
                        className="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-300 resize-none"
                      />
                    </div>
                    {reviewMutation.isError && (
                      <p className="text-xs text-red-500">Something went wrong. Please try again.</p>
                    )}
                    <button
                      type="submit"
                      disabled={rating === 0 || reviewMutation.isPending}
                      className="btn-primary !px-6 !py-2.5 !text-sm flex items-center gap-2 disabled:opacity-50"
                    >
                      <Send size={14} />
                      {reviewMutation.isPending ? 'Submitting…' : 'Submit Review'}
                    </button>
                  </form>
                )}
              </div>
            </section>
          </main>
        </div>
      </div>
    </div>
  )
}
