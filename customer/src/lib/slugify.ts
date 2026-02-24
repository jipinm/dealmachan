/**
 * Converts a string into a URL-safe slug.
 * e.g. "Super Summer Discount 20%" → "super-summer-discount-20"
 */
export function slugify(text: string): string {
  return text
    .toLowerCase()
    .replace(/[^\w\s-]/g, '')   // remove non-word chars (keep letters, digits, spaces, hyphens)
    .replace(/\s+/g, '-')       // spaces → hyphens
    .replace(/-+/g, '-')        // collapse consecutive hyphens
    .replace(/^-|-$/g, '')      // trim leading/trailing hyphens
}
