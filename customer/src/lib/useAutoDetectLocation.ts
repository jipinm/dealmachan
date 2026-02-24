import { useEffect } from 'react'
import { useLocationStore } from '@/store/locationStore'
import { publicApi, type City } from '@/api/endpoints/public'

/**
 * useAutoDetectLocation
 *
 * On first app load (only when no city is already stored and detection hasn't
 * been attempted before), requests browser geolocation, reverse-geocodes via
 * OpenStreetMap Nominatim, and auto-selects the nearest matching city from the
 * database.
 *
 * Rules:
 * - Runs at most once per browser session (persisted flag `detectionAttempted`).
 * - Never blocks the UI — runs silently in the background.
 * - User can always override via the header location picker.
 */
export function useAutoDetectLocation() {
  const { cityId, detectionAttempted, setLocation, setDetectionAttempted } = useLocationStore()

  useEffect(() => {
    // Skip if a city is already chosen or we already tried detection
    if (cityId || detectionAttempted) return

    // Mark as attempted immediately to prevent duplicate runs
    setDetectionAttempted(true)

    if (!navigator.geolocation) return

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          const { latitude, longitude } = position.coords

          // Reverse geocode via Nominatim (free, no API key needed)
          const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json&accept-language=en`,
            { headers: { 'User-Agent': 'DealMachan/1.0 (dealmachan.com)' } },
          )
          if (!res.ok) return

          const geo = await res.json()
          const addr = geo.address ?? {}

          // Nominatim may return city in different fields depending on urban/rural
          const candidates: string[] = [
            addr.city, addr.town, addr.village, addr.county,
            addr.state_district, addr.district, addr.suburb,
          ].filter(Boolean)

          // Load city list and find a fuzzy match
          const citiesRes = await publicApi.getCities()
          const cities: City[] = citiesRes.data.data ?? []

          const matched = findNearestCity(cities, candidates)
          if (matched) {
            setLocation(matched.id, matched.city_name)
          }
        } catch {
          // Silently ignore network/parse errors — location is optional
        }
      },
      () => {
        // Permission denied or timeout — silently ignore
      },
      { timeout: 8000, maximumAge: 60_000 },
    )
  }, []) // eslint-disable-line react-hooks/exhaustive-deps
}

/**
 * Find the best matching city from the DB list against Nominatim address candidates.
 * Tries exact match first, then substring match (e.g. "Thiruvananthapuram" in "Greater Thiruvananthapuram").
 */
function findNearestCity(cities: City[], candidates: string[]): City | null {
  const normalize = (s: string) => s.toLowerCase().trim()

  for (const city of cities) {
    const cityNorm = normalize(city.city_name)
    for (const candidate of candidates) {
      const candidateNorm = normalize(candidate)
      if (candidateNorm === cityNorm ||
          candidateNorm.includes(cityNorm) ||
          cityNorm.includes(candidateNorm)) {
        return city
      }
    }
  }
  return null
}
