import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ChevronRight } from 'lucide-react'
import { useAuthStore } from '@/store/authStore'
import { useLocationStore } from '@/store/locationStore'
import { publicApi } from '@/api/endpoints/public'
import AdBanner from '@/components/ui/AdBanner'
import { FlashDiscountChip } from '@/components/ui/FlashDiscountBadge'
import MerchantCard from '@/components/ui/MerchantCard'
import CouponCard from '@/components/ui/CouponCard'
import SkeletonCard, { SkeletonRow } from '@/components/ui/SkeletonCard'

const CATEGORIES = [
  { label: 'Restaurants', emoji: '🍽️', tag: 'restaurants' },
  { label: 'Fashion',     emoji: '👗', tag: 'fashion'      },
  { label: 'Beauty',      emoji: '💆', tag: 'beauty'       },
  { label: 'Electronics', emoji: '📱', tag: 'electronics'  },
  { label: 'Health',      emoji: '🏥', tag: 'health'       },
  { label: 'Entertainment', emoji: '🎭', tag: 'entertainment' },
  { label: 'Groceries',   emoji: '🛒', tag: 'groceries'   },
  { label: 'Travel',      emoji: '✈️', tag: 'travel'       },
]

function SectionHeader({ title, to }: { title: string; to?: string }) {
  return (
    <div className="flex items-center justify-between mb-3">
      <h2 className="font-heading font-bold text-base text-gray-900">{title}</h2>
      {to && (
        <Link to={to} className="flex items-center gap-0.5 text-xs text-brand-600 font-semibold hover:underline">
          See all <ChevronRight size={13} />
        </Link>
      )}
    </div>
  )
}

export default function HomePage() {
  const { customer } = useAuthStore()
  const { cityId, cityName, areaId } = useLocationStore()

  const { data, isLoading } = useQuery({
    queryKey: ['home-feed', cityId, areaId],
    queryFn: () =>
      publicApi.getHomeFeed({ city_id: cityId ?? undefined, limit: 8 }).then((r) => r.data.data),
    staleTime: 1000 * 60 * 5,
  })

  const ads            = data?.advertisements  ?? []
  const flashDiscounts = data?.flash_discounts  ?? []
  const merchants      = data?.merchants        ?? []
  const topCoupons     = data?.top_coupons      ?? []
  const tags           = data?.tags             ?? []

  return (
    <div className="max-w-2xl mx-auto px-4 py-5 space-y-7">
      {/* ── Greeting ─────────────────────────────────────────────────────── */}
      <div>
        <h1 className="font-heading font-bold text-2xl text-gray-900">
          Hi, {customer?.name?.split(' ')[0] ?? 'there'} 👋
        </h1>
        <p className="text-gray-500 text-sm mt-0.5">
          {cityName ? `Deals in ${cityName}` : 'Discover great deals near you'}
        </p>
      </div>

      {/* ── Ads Carousel ─────────────────────────────────────────────────── */}
      {isLoading ? (
        <div className="skeleton h-40 rounded-2xl" />
      ) : ads.length > 0 ? (
        <AdBanner ads={ads as any} />
      ) : null}

      {/* ── Category Grid ────────────────────────────────────────────────── */}
      <section>
        <SectionHeader title="Browse by Category" to="/explore" />
        <div className="grid grid-cols-4 gap-2">
          {(tags.length > 0 ? tags.slice(0, 8) : CATEGORIES.map((c) => ({ id: 0, tag_name: c.label, icon: c.emoji, color: null }))).map((tag: any, i: number) => (
            <Link
              key={tag.id || i}
              to={`/explore?tag=${tag.id || tag.tag}`}
              className="flex flex-col items-center gap-1.5 p-2.5 bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow"
            >
              <span className="text-2xl">{tag.icon || CATEGORIES[i % CATEGORIES.length]?.emoji}</span>
              <span className="text-[10px] font-semibold text-gray-600 text-center leading-tight line-clamp-2">{tag.tag_name}</span>
            </Link>
          ))}
        </div>
      </section>

      {/* ── Flash Discounts ──────────────────────────────────────────────── */}
      {(isLoading || flashDiscounts.length > 0) && (
        <section>
          <SectionHeader title="⚡ Flash Discounts" to="/explore?type=flash" />
          {isLoading ? (
            <div className="flex gap-3 scroll-x pb-1">
              {Array.from({ length: 3 }).map((_, i) => (
                <div key={i} className="skeleton w-44 h-24 rounded-2xl shrink-0" />
              ))}
            </div>
          ) : (
            <div className="flex gap-3 scroll-x pb-1">
              {(flashDiscounts as any[]).map((deal) => (
                <FlashDiscountChip key={deal.id} deal={deal} />
              ))}
            </div>
          )}
        </section>
      )}

      {/* ── Featured Merchants ───────────────────────────────────────────── */}
      <section>
        <SectionHeader title="Featured Merchants" to="/explore?tab=merchants" />
        {isLoading ? (
          <div className="grid grid-cols-2 gap-3">
            {Array.from({ length: 4 }).map((_, i) => <SkeletonCard key={i} />)}
          </div>
        ) : merchants.length > 0 ? (
          <div className="grid grid-cols-2 gap-3">
            {(merchants as any[]).slice(0, 6).map((m) => (
              <MerchantCard key={m.id} merchant={m} showFavourite />
            ))}
          </div>
        ) : (
          <p className="text-sm text-gray-400 py-4 text-center">No merchants available in your area yet.</p>
        )}
      </section>

      {/* ── Top Coupons ──────────────────────────────────────────────────── */}
      <section>
        <SectionHeader title="🏷️ Top Deals" to="/coupons" />
        {isLoading ? (
          <div className="space-y-3">
            {Array.from({ length: 4 }).map((_, i) => <SkeletonRow key={i} />)}
          </div>
        ) : topCoupons.length > 0 ? (
          <div className="space-y-3">
            {(topCoupons as any[]).slice(0, 6).map((c) => (
              <CouponCard key={c.id} coupon={c} />
            ))}
          </div>
        ) : (
          <p className="text-sm text-gray-400 py-4 text-center">No coupons available yet — check back soon!</p>
        )}
      </section>

      {/* ── Bottom CTA ───────────────────────────────────────────────────── */}
      <div className="card p-5 gradient-brand text-white text-center">
        <p className="font-heading font-bold text-lg mb-1">Find more deals</p>
        <p className="text-white/70 text-sm mb-4">Explore all merchants and exclusive offers near you.</p>
        <Link
          to="/explore"
          className="inline-block bg-white text-brand-700 font-semibold text-sm px-6 py-2.5 rounded-xl shadow"
        >
          Explore All Deals
        </Link>
      </div>
    </div>
  )
}
