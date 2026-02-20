export default function PageLoader() {
  return (
    <div className="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white">
      <div className="w-14 h-14 rounded-2xl gradient-brand flex items-center justify-center mb-4 animate-pulse">
        <span className="text-white font-bold text-xl">DM</span>
      </div>
      <div className="flex gap-1.5">
        {[0, 1, 2].map((i) => (
          <span
            key={i}
            className="w-2 h-2 rounded-full bg-brand-600 animate-bounce"
            style={{ animationDelay: `${i * 0.15}s` }}
          />
        ))}
      </div>
    </div>
  )
}

/** Skeleton block helper — use inside page-specific loaders */
export function SkeletonBlock({ className = '' }: { className?: string }) {
  return (
    <div className={`bg-gray-200 rounded-lg animate-pulse ${className}`} />
  )
}

/** Card skeleton — 3 lines */
export function SkeletonCard() {
  return (
    <div className="bg-white rounded-2xl p-4 shadow-sm space-y-3">
      <SkeletonBlock className="h-4 w-3/4" />
      <SkeletonBlock className="h-3 w-1/2" />
      <SkeletonBlock className="h-3 w-2/3" />
    </div>
  )
}
