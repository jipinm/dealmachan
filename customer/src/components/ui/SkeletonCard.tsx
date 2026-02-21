/**
 * Generic shimmer skeleton placeholder for coupon / merchant cards.
 */
export default function SkeletonCard({ className = '' }: { className?: string }) {
  return (
    <div className={`card overflow-hidden animate-pulse ${className}`}>
      {/* Image area */}
      <div className="skeleton h-36 w-full rounded-none" />
      <div className="p-3 space-y-2">
        <div className="skeleton h-3 w-3/4 rounded" />
        <div className="skeleton h-3 w-1/2 rounded" />
        <div className="flex items-center justify-between pt-1">
          <div className="skeleton h-5 w-16 rounded-full" />
          <div className="skeleton h-7 w-20 rounded-lg" />
        </div>
      </div>
    </div>
  )
}

/** Row-style skeleton (for list views) */
export function SkeletonRow({ className = '' }: { className?: string }) {
  return (
    <div className={`flex items-center gap-3 p-3 animate-pulse ${className}`}>
      <div className="skeleton w-12 h-12 rounded-xl shrink-0" />
      <div className="flex-1 space-y-2">
        <div className="skeleton h-3 w-3/5 rounded" />
        <div className="skeleton h-3 w-2/5 rounded" />
      </div>
      <div className="skeleton h-6 w-14 rounded-full shrink-0" />
    </div>
  )
}
