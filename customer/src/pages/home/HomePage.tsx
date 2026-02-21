// ── New Marketing Homepage — DealMachan v3.0 ─────────────────────────────────
import { useQuery } from '@tanstack/react-query'
import { Link, useNavigate } from 'react-router-dom'
import { useState } from 'react'
import {
  Search, MapPin, Zap, Tag, Store, ArrowRight, ChevronRight,
  Star, Clock, Percent, Gift, Heart, Shield, TrendingUp,
} from 'lucide-react'
import { publicApi, type TopCoupon, type FeaturedMerchant, type FlashDiscount } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'
import { useAuthStore } from '@/store/authStore'

// ── Static category data ──────────────────────────────────────────────────────
const STATIC_CATEGORIES = [
  { id: 'food',        label: 'Food & Dining',    emoji: '🍔', color: 'bg-orange-50  text-orange-600  border-orange-200' },
  { id: 'fashion',     label: 'Fashion',          emoji: '👗', color: 'bg-pink-50    text-pink-600    border-pink-200'   },
  { id: 'beauty',      label: 'Beauty & Spa',     emoji: '💆', color: 'bg-rose-50    text-rose-600    border-rose-200'   },
  { id: 'electronics', label: 'Electronics',      emoji: '📱', color: 'bg-blue-50    text-blue-600    border-blue-200'   },
  { id: 'travel',      label: 'Travel',           emoji: '✈️', color: 'bg-sky-50     text-sky-600     border-sky-200'    },
  { id: 'fitness',     label: 'Fitness',          emoji: '💪', color: 'bg-green-50   text-green-600   border-green-200'  },
  { id: 'education',   label: 'Education',        emoji: '📚', color: 'bg-indigo-50  text-indigo-600  border-indigo-200' },
  { id: 'home',        label: 'Home & Living',    emoji: '🏠', color: 'bg-amber-50   text-amber-600   border-amber-200'  },
  { id: 'auto',        label: 'Auto & Services',  emoji: '🚗', color: 'bg-slate-50   text-slate-600   border-slate-200'  },
  { id: 'health',      label: 'Health',           emoji: '❤️', color: 'bg-red-50     text-red-600     border-red-200'    },
  { id: 'kids',        label: 'Kids & Toys',      emoji: '🧸', color: 'bg-yellow-50  text-yellow-600  border-yellow-200' },
  { id: 'gifts',       label: 'Gifts',            emoji: '🎁', color: 'bg-purple-50  text-purple-600  border-purple-200' },
]

// ── How It Works steps ────────────────────────────────────────────────────────
const HOW_IT_WORKS = [
  {
    step: '01', icon: Search, title: 'Browse Deals',
    desc: 'Search and discover thousands of deals, coupons, and offers from local merchants.',
    color: 'text-brand-600 bg-brand-50',
  },
  {
    step: '02', icon: Heart, title: 'Save & Collect',
    desc: 'Save your favourite deals to your wallet and get notified before they expire.',
    color: 'text-cta-500 bg-rose-50',
  },
  {
    step: '03', icon: Gift, title: 'Redeem & Save',
    desc: 'Show the coupon at the store or use the code online to get your discount instantly.',
    color: 'text-green-600 bg-green-50',
  },
]

// ── Small helpers ─────────────────────────────────────────────────────────────
function timeLeft(until: string | null): string {
  if (!until) return 'Limited time'
  const diff = new Date(until).getTime() - Date.now()
  if (diff <= 0) return 'Expired'
  const h = Math.floor(diff / 3_600_000)
  const m = Math.floor((diff % 3_600_000) / 60_000)
  if (h >= 24) return `${Math.floor(h / 24)}d left`
  return h > 0 ? `${h}h ${m}m left` : `${m}m left`
}

function imgSrc(path: string | null, fallback = 'https://via.placeholder.com/400x300?text=No+Image'): string {
  if (!path) return fallback
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL?.replace('/api', '') ?? 'http://localhost:8000'}${path}`
}

// ── Section Header ─────────────────────────────────────────────────────────────
function SectionHeader({ title, subtitle, linkTo, linkLabel }: {
  title: string; subtitle?: string; linkTo?: string; linkLabel?: string
}) {
  return (
    <div className="flex items-end justify-between mb-6">
      <div>
        <h2 className="section-heading">{title}</h2>
        {subtitle && <p className="text-slate-500 text-sm mt-1">{subtitle}</p>}
      </div>
      {linkTo && (
        <Link to={linkTo} className="flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors">
          {linkLabel ?? 'View all'} <ChevronRight size={16} />
        </Link>
      )}
    </div>
  )
}

// ── Coupon card ───────────────────────────────────────────────────────────────
function CouponCard({ coupon }: { coupon: TopCoupon }) {
  const discountLabel = coupon.discount_type === 'percentage'
    ? `${coupon.discount_value}% OFF`
    : `₹${coupon.discount_value} OFF`
  return (
    <Link to={`/deals/${coupon.id}`} className="card card-hover group block overflow-hidden">
      <div className="relative h-40 bg-slate-100 overflow-hidden">
        <img
          src={imgSrc(coupon.banner_image)}
          alt={coupon.title}
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/400x200?text=Deal' }}
        />
        <span className="absolute top-2 right-2 badge-discount">{discountLabel}</span>
      </div>
      <div className="p-4">
        <div className="flex items-center gap-2 mb-1">
          <img
            src={imgSrc(coupon.merchant_logo)}
            alt={coupon.merchant_name}
            className="w-6 h-6 rounded-full object-cover bg-slate-100"
            onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/40?text=M' }}
          />
          <span className="text-xs text-slate-500 font-medium truncate">{coupon.merchant_name}</span>
        </div>
        <h3 className="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 mb-2">{coupon.title}</h3>
        <div className="flex items-center justify-between">
          <span className="inline-flex items-center gap-1 text-xs bg-slate-50 text-slate-600 px-2 py-1 rounded-lg font-mono font-semibold border border-dashed border-slate-300">
            <Tag size={10} /> {coupon.coupon_code}
          </span>
          {coupon.valid_until && (
            <span className="flex items-center gap-1 text-xs text-slate-400">
              <Clock size={11} /> {timeLeft(coupon.valid_until)}
            </span>
          )}
        </div>
      </div>
    </Link>
  )
}

// ── Merchant card ─────────────────────────────────────────────────────────────
function MerchantCardLocal({ merchant }: { merchant: FeaturedMerchant }) {
  return (
    <Link to={`/stores/${merchant.id}`} className="card card-hover group flex flex-col overflow-hidden">
      <div className="relative h-32 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
        {merchant.business_logo ? (
          <img
            src={imgSrc(merchant.business_logo)}
            alt={merchant.business_name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/300x150?text=Store' }}
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <Store size={36} className="text-slate-300" />
          </div>
        )}
        {merchant.is_premium && <span className="absolute top-2 left-2 badge-new">✨ Premium</span>}
      </div>
      <div className="p-4 flex-1">
        <h3 className="font-semibold text-slate-800 text-sm mb-1 line-clamp-1">{merchant.business_name}</h3>
        <div className="flex items-center gap-1 mb-2">
          <Star size={12} className="text-yellow-400 fill-yellow-400" />
          <span className="text-xs font-semibold text-slate-700">{merchant.avg_rating?.toFixed(1) ?? '–'}</span>
          <span className="text-xs text-slate-400">({merchant.total_reviews ?? 0})</span>
          {(merchant.area_name || merchant.city_name) && (
            <>
              <span className="text-slate-300 mx-1">·</span>
              <MapPin size={11} className="text-slate-400" />
              <span className="text-xs text-slate-400 truncate">{merchant.area_name ?? merchant.city_name}</span>
            </>
          )}
        </div>
        {merchant.active_coupons_count > 0 && (
          <span className="text-xs text-brand-600 font-semibold">
            {merchant.active_coupons_count} active deal{merchant.active_coupons_count > 1 ? 's' : ''}
          </span>
        )}
      </div>
    </Link>
  )
}

// ── Flash deal card ───────────────────────────────────────────────────────────
function FlashDealCard({ deal }: { deal: FlashDiscount }) {
  return (
    <Link to={`/flash-deals/${deal.id}`} className="flex-shrink-0 w-64 card card-hover overflow-hidden group">
      <div className="h-28 bg-gradient-cta relative overflow-hidden flex items-center justify-center">
        {deal.merchant_logo ? (
          <img
            src={imgSrc(deal.merchant_logo)}
            alt={deal.merchant_name}
            className="w-16 h-16 object-contain rounded-full bg-white p-1"
          />
        ) : (
          <Zap size={40} className="text-white/60" />
        )}
        <span className="absolute top-2 right-2 text-white font-black text-2xl">{deal.discount_percentage}%</span>
      </div>
      <div className="p-3">
        <h3 className="font-semibold text-slate-800 text-sm line-clamp-2 mb-1">{deal.title}</h3>
        <p className="text-xs text-slate-500 mb-2">{deal.merchant_name}</p>
        <div className="flex items-center gap-1 text-xs text-cta-500 font-semibold">
          <Clock size={11} /> {timeLeft(deal.valid_until)}
        </div>
      </div>
    </Link>
  )
}

// ─────────────────────────────────────────────────────────────────────────────
// Main Page
// ─────────────────────────────────────────────────────────────────────────────
export default function HomePage() {
  const { cityId, cityName, areaName, openLocationModal } = useLocationStore()
  const { isAuthenticated } = useAuthStore()
  const navigate = useNavigate()
  const [searchQuery, setSearchQuery] = useState('')

  const { data: homeData, isLoading } = useQuery({
    queryKey: ['home-feed', cityId],
    queryFn: () => publicApi.getHomeFeed({ city_id: cityId ?? undefined }).then((r) => r.data.data),
    staleTime: 3 * 60 * 1000,
  })

  const { data: blogData } = useQuery({
    queryKey: ['blog-posts'],
    queryFn: () => publicApi.getBlogPosts({ page: 1 }).then((r) => r.data.data ?? []),
    staleTime: 10 * 60 * 1000,
  })

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    if (searchQuery.trim()) navigate(`/deals?q=${encodeURIComponent(searchQuery.trim())}`)
  }

  const coupons    = homeData?.top_coupons       ?? []
  const merchants  = homeData?.featured_merchants ?? []
  const flashDeals = homeData?.flash_discounts    ?? []

  return (
    <div>

      {/* ═══ HERO ════════════════════════════════════════════════════════════ */}
      <section
        className="relative overflow-hidden"
        style={{ background: 'linear-gradient(135deg, #312e81 0%, #6d28d9 40%, #9d174d 100%)' }}
      >
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute -top-20 -left-20 w-80 h-80 bg-white/5 rounded-full blur-3xl" />
          <div className="absolute -bottom-20 -right-20 w-96 h-96 bg-white/5 rounded-full blur-3xl" />
        </div>
        <div className="site-container relative py-20 md:py-28">
          <div className="max-w-2xl mx-auto text-center">
            <span className="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 backdrop-blur rounded-full text-white/90 text-sm font-medium mb-6 border border-white/20">
              <Zap size={14} className="text-yellow-300 fill-yellow-300" />
              10,000+ deals updated daily
            </span>
            <h1 className="font-heading font-black text-4xl md:text-6xl text-white leading-tight mb-4">
              Discover Amazing
              <span className="block text-yellow-300"> Deals Near You</span>
            </h1>
            <p className="text-white/75 text-lg md:text-xl mb-10 max-w-lg mx-auto">
              Coupons, flash discounts, and exclusive offers from your favourite local stores — all in one place.
            </p>
            <form onSubmit={handleSearch} className="flex gap-2 max-w-xl mx-auto">
              <div className="flex flex-1 items-center bg-white rounded-2xl overflow-hidden shadow-2xl shadow-black/20">
                <Search size={18} className="ml-4 text-slate-400 flex-shrink-0" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search deals, stores, coupons…"
                  className="flex-1 px-3 py-4 text-slate-700 outline-none text-base placeholder-slate-400 bg-transparent"
                />
              </div>
              <button type="submit" className="btn-cta !px-6 !py-4 !rounded-2xl !text-base shadow-xl">
                Search
              </button>
            </form>
            <div className="flex flex-wrap justify-center gap-2 mt-5">
              {['Restaurants', 'Fashion', 'Electronics', 'Spa'].map((term) => (
                <button
                  key={term}
                  onClick={() => navigate(`/deals?q=${encodeURIComponent(term)}`)}
                  className="px-3 py-1 bg-white/10 border border-white/20 text-white/80 text-sm rounded-full hover:bg-white/20 transition-colors"
                >
                  {term}
                </button>
              ))}
            </div>
          </div>
        </div>
        <div className="absolute bottom-0 left-0 right-0 overflow-hidden leading-none">
          <svg viewBox="0 0 1440 40" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-full h-10" preserveAspectRatio="none">
            <path d="M0 40 C360 0 1080 0 1440 40 L1440 40 L0 40 Z" fill="#f8f9fc"/>
          </svg>
        </div>
      </section>

      {/* ═══ LOCATION PROMPT ════════════════════════════════════════════════ */}
      {!cityId ? (
        <section className="bg-gradient-to-r from-brand-50 to-indigo-50 border-b border-brand-100">
          <div className="site-container py-4">
            <div className="flex flex-col sm:flex-row items-center gap-3 sm:gap-4">
              <div className="flex items-center gap-2.5 flex-1 min-w-0">
                <div className="w-9 h-9 rounded-xl bg-brand-100 flex items-center justify-center shrink-0">
                  <MapPin size={17} className="text-brand-600" />
                </div>
                <div>
                  <p className="text-sm font-semibold text-slate-800">Set your location</p>
                  <p className="text-xs text-slate-500">Choose a city to see deals and stores near you</p>
                </div>
              </div>
              <button
                onClick={openLocationModal}
                className="btn-primary !py-2.5 !px-5 !text-sm !rounded-xl shrink-0"
              >
                Choose City
              </button>
            </div>
          </div>
        </section>
      ) : (
        <section className="bg-white border-b border-slate-100">
          <div className="site-container py-2">
            <div className="flex items-center gap-2 text-sm text-slate-600">
              <MapPin size={14} className="text-cta-500" />
              <span>
                Showing deals in <strong className="text-slate-800">{areaName ? `${areaName}, ${cityName}` : cityName}</strong>
              </span>
              <button
                onClick={openLocationModal}
                className="text-brand-600 hover:text-brand-700 font-medium ml-1 transition-colors"
              >
                Change
              </button>
            </div>
          </div>
        </section>
      )}

      {/* ═══ STATS ═══════════════════════════════════════════════════════════ */}
      <section className="bg-white border-b border-slate-100">
        <div className="site-container py-6">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            {[
              { label: 'Active Deals',    value: '5,000+',  icon: Tag,        color: 'text-brand-600' },
              { label: 'Partner Stores',  value: '500+',    icon: Store,      color: 'text-green-600' },
              { label: 'Flash Discounts', value: '200+',    icon: Zap,        color: 'text-cta-500'   },
              { label: 'Happy Members',   value: '10,000+', icon: TrendingUp, color: 'text-purple-600' },
            ].map(({ label, value, icon: Icon, color }) => (
              <div key={label} className="flex flex-col items-center">
                <Icon size={20} className={`${color} mb-1`} />
                <span className="font-heading font-black text-2xl text-slate-800">{value}</span>
                <span className="text-xs text-slate-500 font-medium">{label}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ═══ CATEGORIES ══════════════════════════════════════════════════════ */}
      <section className="py-12">
        <div className="site-container">
          <SectionHeader title="Browse by Category" subtitle="Find deals in your favourite category" linkTo="/deals" linkLabel="All deals" />
          <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
            {STATIC_CATEGORIES.map((cat) => (
              <Link
                key={cat.id}
                to={`/deals?category=${encodeURIComponent(cat.label)}`}
                className={`flex flex-col items-center gap-2 p-4 rounded-2xl border text-center hover:shadow-md transition-all ${cat.color}`}
              >
                <span className="text-2xl">{cat.emoji}</span>
                <span className="text-xs font-semibold leading-tight">{cat.label}</span>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* ═══ FLASH DEALS ═════════════════════════════════════════════════════ */}
      {(isLoading || flashDeals.length > 0) && (
        <section className="py-12 bg-gradient-to-r from-orange-50 to-rose-50">
          <div className="site-container">
            <SectionHeader title="⚡ Flash Deals" subtitle="Limited time offers — grab them before they're gone!" linkTo="/flash-deals" linkLabel="All flash deals" />
            {isLoading ? (
              <div className="flex gap-4 overflow-hidden">
                {[1, 2, 3, 4].map((i) => <div key={i} className="flex-shrink-0 w-64 h-56 skeleton rounded-2xl" />)}
              </div>
            ) : (
              <div className="scroll-x flex gap-4 pb-2">
                {flashDeals.map((deal) => <FlashDealCard key={deal.id} deal={deal} />)}
              </div>
            )}
          </div>
        </section>
      )}

      {/* ═══ TOP DEALS ═══════════════════════════════════════════════════════ */}
      <section className="py-12">
        <div className="site-container">
          <SectionHeader title="Top Deals Today" subtitle="Hand-picked coupons and offers" linkTo="/deals" linkLabel="View all deals" />
          {isLoading ? (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => <div key={i} className="h-60 skeleton rounded-2xl" />)}
            </div>
          ) : coupons.length > 0 ? (
            <>
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                {coupons.slice(0, 8).map((c) => <CouponCard key={c.id} coupon={c} />)}
              </div>
              <div className="text-center mt-8">
                <Link to="/deals" className="btn-ghost !px-8 !py-3 !rounded-xl inline-flex items-center gap-2">
                  Explore All Deals <ArrowRight size={16} />
                </Link>
              </div>
            </>
          ) : (
            <div className="text-center py-16 text-slate-400">
              <Tag size={40} className="mx-auto mb-3 opacity-40" />
              <p className="font-medium">No deals available right now</p>
              <p className="text-sm mt-1">Check back soon for amazing offers!</p>
            </div>
          )}
        </div>
      </section>

      {/* ═══ FEATURED MERCHANTS ══════════════════════════════════════════════ */}
      <section className="py-12 bg-white">
        <div className="site-container">
          <SectionHeader title="Featured Stores" subtitle="Top-rated merchants with exclusive offers" linkTo="/stores" linkLabel="All stores" />
          {isLoading ? (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              {[1, 2, 3, 4].map((i) => <div key={i} className="h-52 skeleton rounded-2xl" />)}
            </div>
          ) : merchants.length > 0 ? (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              {merchants.slice(0, 8).map((m) => <MerchantCardLocal key={m.id} merchant={m} />)}
            </div>
          ) : (
            <div className="text-center py-12 text-slate-400">
              <Store size={36} className="mx-auto mb-2 opacity-40" />
              <p>No stores found nearby</p>
            </div>
          )}
        </div>
      </section>

      {/* ═══ HOW IT WORKS ════════════════════════════════════════════════════ */}
      <section className="py-16 bg-gradient-to-br from-slate-50 to-indigo-50/40">
        <div className="site-container">
          <div className="text-center mb-12">
            <h2 className="section-heading">How DealMachan Works</h2>
            <p className="text-slate-500 mt-2 max-w-md mx-auto">
              From discovering a deal to saving money — it takes less than 30 seconds.
            </p>
          </div>
          <div className="grid md:grid-cols-3 gap-8 max-w-3xl mx-auto">
            {HOW_IT_WORKS.map(({ step, icon: Icon, title, desc, color }) => (
              <div key={step} className="text-center">
                <div className={`w-16 h-16 ${color} rounded-3xl flex items-center justify-center mx-auto mb-4`}>
                  <Icon size={28} />
                </div>
                <div className="text-4xl font-black text-slate-100 mb-1">{step}</div>
                <h3 className="font-heading font-bold text-slate-800 text-lg mb-2">{title}</h3>
                <p className="text-slate-500 text-sm leading-relaxed">{desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ═══ BLOG ════════════════════════════════════════════════════════════ */}
      {(blogData ?? []).length > 0 && (
        <section className="py-12 bg-white">
          <div className="site-container">
            <SectionHeader title="From Our Blog" subtitle="Tips, guides, and saving hacks" linkTo="/blog" linkLabel="All articles" />
            <div className="grid sm:grid-cols-2 md:grid-cols-3 gap-6">
              {(blogData ?? []).slice(0, 3).map((post) => (
                <Link key={post.id} to={`/blog/${post.slug}`} className="card card-hover group overflow-hidden">
                  <div className="h-40 bg-slate-100 overflow-hidden">
                    {post.featured_image ? (
                      <img
                        src={imgSrc(post.featured_image)}
                        alt={post.title}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      />
                    ) : (
                      <div className="w-full h-full gradient-brand flex items-center justify-center">
                        <span className="text-white/40 text-4xl font-black">DM</span>
                      </div>
                    )}
                  </div>
                  <div className="p-4">
                    <p className="text-xs text-slate-400 mb-1">
                      {new Date(post.published_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}
                    </p>
                    <h3 className="font-semibold text-slate-800 text-sm leading-snug line-clamp-2">{post.title}</h3>
                    {post.excerpt && <p className="text-xs text-slate-500 mt-2 line-clamp-2">{post.excerpt}</p>}
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* ═══ REGISTER CTA ════════════════════════════════════════════════════ */}
      {!isAuthenticated && (
        <section className="py-16 gradient-brand relative overflow-hidden">
          <div className="absolute inset-0 bg-black/10 pointer-events-none" />
          <div className="site-container relative text-center">
            <div className="flex justify-center mb-5 gap-4">
              <Shield size={20} className="text-white/70" />
              <Percent size={20} className="text-white/70" />
              <Gift size={20} className="text-white/70" />
            </div>
            <h2 className="font-heading font-black text-3xl md:text-4xl text-white mb-4">
              Join 10,000+ Smart Shoppers
            </h2>
            <p className="text-white/80 text-lg mb-8 max-w-lg mx-auto">
              Create a free account to save deals, get wallet rewards, and unlock exclusive member-only offers.
            </p>
            <div className="flex flex-col sm:flex-row gap-3 justify-center">
              <Link
                to="/register"
                className="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-brand-700 font-bold text-base rounded-2xl hover:bg-white/95 transition-colors shadow-xl"
              >
                Create Free Account <ArrowRight size={18} />
              </Link>
              <Link
                to="/login"
                className="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/15 text-white font-semibold text-base rounded-2xl border border-white/30 hover:bg-white/25 transition-colors"
              >
                Sign In
              </Link>
            </div>
          </div>
        </section>
      )}
    </div>
  )
}
