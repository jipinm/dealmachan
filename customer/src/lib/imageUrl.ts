/**
 * Image URL utility for the Customer app.
 *
 * Converts a relative upload path returned by the API (e.g. `/uploads/logos/foo.jpg`)
 * into a fully-qualified URL using the configured API origin.
 *
 * Configuration:
 *   VITE_API_ORIGIN – the scheme+host of the API server, no trailing slash.
 *   Example dev:  VITE_API_ORIGIN=http://dealmachan-api.local
 *   Example prod: VITE_API_ORIGIN=https://api.dealmachan.com
 *
 * If the path is already an absolute URL it is returned unchanged.
 */
const API_ORIGIN: string =
  (import.meta.env.VITE_API_ORIGIN as string | undefined) || 'http://dealmachan-api.local'

/**
 * Returns a fully-qualified image URL.
 *
 * @param path      Relative path like `/uploads/logos/x.jpg`, or a full URL, or null/undefined.
 * @param fallback  Optional fallback URL returned when path is empty.
 */
export function getImageUrl(
  path: string | null | undefined,
  fallback = '',
): string {
  if (!path) return fallback
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('//')) {
    return path
  }
  return `${API_ORIGIN}${path.startsWith('/') ? '' : '/'}${path}`
}
