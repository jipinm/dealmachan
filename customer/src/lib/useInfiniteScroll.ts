import { useEffect, useRef, useCallback } from 'react'

/**
 * Returns a ref to attach to a sentinel element.
 * When the sentinel enters the viewport, `onIntersect` is called.
 * Calls are suppressed when `enabled` is false (e.g. already fetching or no more pages).
 */
export function useInfiniteScroll(
  onIntersect: () => void,
  enabled: boolean,
): React.RefObject<HTMLDivElement> {
  const sentinelRef = useRef<HTMLDivElement>(null)

  const handleObserver = useCallback(
    (entries: IntersectionObserverEntry[]) => {
      if (entries[0].isIntersecting && enabled) {
        onIntersect()
      }
    },
    [onIntersect, enabled],
  )

  useEffect(() => {
    const observer = new IntersectionObserver(handleObserver, { rootMargin: '200px' })
    const el = sentinelRef.current
    if (el) observer.observe(el)
    return () => {
      if (el) observer.unobserve(el)
    }
  }, [handleObserver])

  return sentinelRef
}
