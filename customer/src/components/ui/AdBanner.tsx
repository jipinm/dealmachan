import { useState, useEffect, useCallback } from 'react'
import { ChevronLeft, ChevronRight, ExternalLink } from 'lucide-react'
import { getImageUrl } from '@/lib/imageUrl'

export interface Ad {
  id: number
  title: string
  image_url: string
  link_url?: string | null
  ad_type: string
}

interface Props {
  ads: Ad[]
  autoPlayMs?: number
  className?: string
}

/**
 * Full-width auto-rotating ad banner carousel with dot indicators.
 */
export default function AdBanner({ ads, autoPlayMs = 4000, className = '' }: Props) {
  const [current, setCurrent] = useState(0)

  const next = useCallback(() => setCurrent((c) => (c + 1) % ads.length), [ads.length])
  const prev = () => setCurrent((c) => (c - 1 + ads.length) % ads.length)

  useEffect(() => {
    if (ads.length <= 1) return
    const timer = setInterval(next, autoPlayMs)
    return () => clearInterval(timer)
  }, [next, autoPlayMs, ads.length])

  if (!ads.length) return null

  const ad = ads[current]

  return (
    <div className={`relative overflow-hidden rounded-2xl bg-gray-100 aspect-[16/6] ${className}`}>
      {/* Image */}
      <img
        key={ad.id}
        src={getImageUrl(ad.image_url)}
        alt={ad.title}
        className="w-full h-full object-cover transition-opacity duration-500"
      />

      {/* Overlay gradient */}
      <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent" />

      {/* Title */}
      <div className="absolute bottom-3 left-4 right-14">
        <p className="text-white text-sm font-semibold line-clamp-1 drop-shadow">{ad.title}</p>
      </div>

      {/* External link */}
      {ad.link_url && (
        <a
          href={ad.link_url}
          target="_blank"
          rel="noopener noreferrer"
          className="absolute bottom-3 right-4 w-7 h-7 rounded-full bg-white/20 backdrop-blur flex items-center justify-center"
          onClick={(e) => e.stopPropagation()}
        >
          <ExternalLink size={13} className="text-white" />
        </a>
      )}

      {/* Arrows */}
      {ads.length > 1 && (
        <>
          <button
            onClick={prev}
            aria-label="Previous banner"
            className="absolute left-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-black/30 flex items-center justify-center text-white hover:bg-black/50 transition-colors"
          >
            <ChevronLeft size={14} />
          </button>
          <button
            onClick={next}
            aria-label="Next banner"
            className="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-black/30 flex items-center justify-center text-white hover:bg-black/50 transition-colors"
          >
            <ChevronRight size={14} />
          </button>
        </>
      )}

      {/* Dots */}
      {ads.length > 1 && (
        <div className="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
          {ads.map((_, i) => (
            <button
              key={i}
              onClick={() => setCurrent(i)}
              className={`h-1.5 rounded-full transition-all ${
                i === current ? 'w-5 bg-white' : 'w-1.5 bg-white/50'
              }`}
            />
          ))}
        </div>
      )}
    </div>
  )
}
