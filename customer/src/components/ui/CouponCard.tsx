import { Link } from 'react-router-dom'
import { Bookmark, BookmarkCheck, Clock, Store } from 'lucide-react'
import { useState } from 'react'
import { getImageUrl } from '@/lib/imageUrl'

export interface CouponCardData {
  id: number
  title: string
  description?: string | null
  discount_type: 'percentage' | 'flat' | 'free_item' | 'bogo'
  discount_value: number
  min_purchase?: number | null
  max_discount?: number | null
  valid_until: string
  coupon_code?: string | null   // null = guest user; hidden by API
  business_name: string
  business_logo?: string | null
  save_count?: number
  is_saved?: boolean
  store_name?: string | null
}

interface Props {
  coupon: CouponCardData
  onSaveToggle?: (id: number, current: boolean) => void
  compact?: boolean
}

function daysLeft(until: string): number {
  return Math.max(0, Math.ceil((new Date(until).getTime() - Date.now()) / 86_400_000))
}

export default function CouponCard({ coupon, onSaveToggle, compact }: Props) {
  const [saved, setSaved] = useState(coupon.is_saved ?? false)
  const days = daysLeft(coupon.valid_until)
  const urgent = days <= 3

  function handleSave(e: React.MouseEvent) {
    e.preventDefault()
    e.stopPropagation()
    setSaved((v) => !v)
    onSaveToggle?.(coupon.id, saved)
  }

  return (
    <Link
      to={`/coupons/${coupon.id}`}
      className="card flex overflow-hidden hover:shadow-md transition-shadow"
    >
      {/* Left strip — discount value */}
      <div className="w-20 shrink-0 gradient-brand flex flex-col items-center justify-center gap-0.5 p-2 text-white">
        <span className="font-heading font-bold text-xl leading-tight">
          {coupon.discount_type === 'percentage' || coupon.discount_type === 'flat'
            ? (coupon.discount_type === 'percentage' ? `${coupon.discount_value}%` : `₹${coupon.discount_value}`)
            : coupon.discount_type === 'free_item' ? 'FREE' : 'BOGO'
          }
        </span>
        <span className="text-white/70 text-[9px] font-semibold uppercase tracking-wide">
          {coupon.discount_type === 'flat' ? 'FLAT OFF' : 'OFF'}
        </span>
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0 p-3 flex flex-col justify-between gap-1">
        <div>
          <div className="flex items-start justify-between gap-2">
            <h3 className="text-sm font-semibold text-gray-900 leading-tight line-clamp-2 flex-1">
              {coupon.title}
            </h3>
            <button onClick={handleSave} className="shrink-0 mt-0.5">
              {saved
                ? <BookmarkCheck size={18} className="text-brand-600 fill-brand-100" />
                : <Bookmark size={18} className="text-gray-300 hover:text-brand-400 transition-colors" />
              }
            </button>
          </div>

          <div className="flex items-center gap-1.5 mt-1 text-xs text-gray-500">
            {coupon.business_logo
              ? <img src={getImageUrl(coupon.business_logo)} alt="" loading="lazy" className="w-4 h-4 rounded-full object-cover border border-gray-100" />
              : <Store size={12} />
            }
            <span className="truncate">{coupon.store_name || coupon.business_name}</span>
          </div>
        </div>

        {!compact && coupon.description && (
          <p className="text-xs text-gray-400 line-clamp-1">{coupon.description}</p>
        )}

        <div className="flex items-center justify-between mt-1">
          {/* Min purchase */}
          {coupon.min_purchase && coupon.min_purchase > 0 ? (
            <span className="text-[10px] text-gray-400">Min ₹{coupon.min_purchase}</span>
          ) : (
            <span className="text-[10px] text-green-500 font-medium">No min purchase</span>
          )}

          {/* Expiry */}
          <div className={`flex items-center gap-0.5 text-[10px] font-medium ${urgent ? 'text-cta-500' : 'text-gray-400'}`}>
            <Clock size={10} />
            {days === 0 ? 'Expires today' : `${days}d left`}
          </div>
        </div>
      </div>
    </Link>
  )
}
