import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────

export interface Advertisement {
  id: number
  title: string
  media_type: 'image' | 'video'
  media_url: string
  link_url: string | null
  display_duration: number
}

export interface FlashDiscount {
  id: number
  title: string
  discount_percentage: number
  valid_until: string
  merchant_id: number
  merchant_name: string
  merchant_logo: string | null
}

export interface FeaturedMerchant {
  id: number
  business_name: string
  business_logo: string | null
  avg_rating: number
  total_reviews: number
  area_name: string | null
  city_name: string | null
  labels: Array<{ id: number; label_name: string; label_icon: string | null }>
  active_coupons_count: number
  is_premium: boolean
}

export interface HomeData {
  advertisements: Advertisement[]
  flash_discounts: FlashDiscount[]
  featured_merchants: FeaturedMerchant[]
  top_coupons: TopCoupon[]
  new_merchants: FeaturedMerchant[]
  tags: Tag[]
}

export interface TopCoupon {
  id: number
  title: string
  coupon_code: string
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  valid_until: string | null
  banner_image: string | null
  merchant_id: number
  merchant_name: string
  merchant_logo: string | null
}

export interface Tag {
  id: number
  tag_name: string
  tag_category: string | null
  parent_tag_id: number | null
  icon?: string
}

export interface City {
  id: number
  city_name: string
  state: string | null
}

export interface Area {
  id: number
  area_name: string
  city_id: number
}

export interface PublicMerchant {
  id: number
  business_name: string
  business_description: string | null
  business_logo: string | null
  avg_rating: number
  total_reviews: number
  website_url: string | null
  is_premium: boolean
  labels: Array<{ id: number; label_name: string; label_icon: string | null }>
  tags: Array<{ id: number; tag_name: string }>
  active_coupons_count: number
  stores: Array<{
    id: number
    store_name: string
    address: string
    area_name: string | null
    city_name: string | null
    phone: string | null
  }>
}

export interface BlogPost {
  id: number
  title: string
  slug: string
  featured_image: string | null
  published_at: string
  excerpt?: string
}

export interface Contest {
  id: number
  title: string
  description: string
  start_date: string
  end_date: string
  status: 'active' | 'ended' | 'upcoming'
}

export interface SearchResult {
  merchants: FeaturedMerchant[]
  coupons: TopCoupon[]
}

// ── Public API ────────────────────────────────────────────────────────────

export const publicApi = {
  /** Home feed: banners + flash discounts + featured merchants + top coupons */
  getHomeFeed: (params?: { city_id?: number; area_id?: number }) =>
    apiClient.get<{ data: HomeData }>('/public/home', { params }),

  /** Browse all merchants with filtering */
  getMerchants: (params: {
    city_id?: number
    area_id?: number
    tag_id?: string | number
    label_id?: number
    search?: string
    q?: string
    min_rating?: number
    is_premium?: boolean
    has_coupons?: boolean
    page?: number
    per_page?: number
  }) => apiClient.get<{ data: PublicMerchant[]; pagination?: { pages: number; total: number }; meta?: { total: number; page: number; per_page: number } }>(
    '/public/merchants',
    { params },
  ),

  /** Single merchant public profile */
  getMerchant: (id: number) =>
    apiClient.get<{ data: PublicMerchant }>(`/public/merchants/${id}`),

  /** Merchant's stores */
  getMerchantStores: (id: number) =>
    apiClient.get<{ data: unknown[] }>(`/public/merchants/${id}/stores`),

  /** Merchant's active coupons (preview, no auth) */
  getMerchantCoupons: (id: number) =>
    apiClient.get<{ data: TopCoupon[] }>(`/public/merchants/${id}/coupons`),

  /** Merchant's reviews */
  getMerchantReviews: (id: number, params?: { page?: number }) =>
    apiClient.get<{ data: unknown[] }>(`/public/merchants/${id}/reviews`, { params }),

  /** Browse all coupons */
  getCoupons: (params?: {
    tag_id?: string | number
    city_id?: number
    discount_type?: string
    search?: string
    /** alias for search — sent as `q` */
    q?: string
    page?: number
    per_page?: number
  }) => apiClient.get<{ data: TopCoupon[]; pagination?: { pages: number; total: number } }>('/public/coupons', { params }),

  /** Single coupon public detail */
  getCouponDetail: (id: number) =>
    apiClient.get<{ data: { coupon: TopCoupon & { terms_and_conditions?: string; min_purchase_amount?: number; max_discount_amount?: number }; merchant: FeaturedMerchant; stores: unknown[] } }>(`/public/coupons/${id}`),

  /** Active flash discounts */
  getFlashDiscounts: (params?: { city_id?: number }) =>
    apiClient.get<{ data: FlashDiscount[] }>('/public/flash-discounts', { params }),

  /** All tags (categories) */
  getTags: () =>
    apiClient.get<{ data: Tag[] }>('/public/tags'),

  /** Cities list */
  getCities: () =>
    apiClient.get<{ data: City[] }>('/public/cities'),

  /** Areas by city */
  getAreas: (cityId: number) =>
    apiClient.get<{ data: Area[] }>('/public/areas', { params: { city_id: cityId } }),

  /** Active advertisements */
  getAdvertisements: () =>
    apiClient.get<{ data: Advertisement[] }>('/public/advertisements'),

  /** Blog posts */
  getBlogPosts: (params?: { page?: number }) =>
    apiClient.get<{ data: BlogPost[] }>('/public/blog', { params }),

  /** Single blog post */
  getBlogPost: (slug: string) =>
    apiClient.get<{ data: BlogPost & { content: string } }>(`/public/blog/${slug}`),

  /** Active contests */
  getContests: () =>
    apiClient.get<{ data: Contest[] }>('/public/contests'),

  /** Unified search */
  search: (q: string, params?: { city_id?: number }) =>
    apiClient.get<{ data: SearchResult }>('/public/search', { params: { q, ...params } }),
}
