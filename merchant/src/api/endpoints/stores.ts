import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface GalleryImage {
  id: number
  image_url: string
  caption: string | null
  is_cover: boolean
  display_order: number
}

export interface StoreCategory {
  category_id: number
  sub_category_id: number | null
  category_name: string | null
  sub_category_name: string | null
}

export interface Store {
  id: number
  merchant_id: number
  store_name: string
  address: string
  city_id: number
  area_id: number | null
  location_id: number | null
  phone: string | null
  email: string | null
  latitude: string | null
  longitude: string | null
  opening_hours: Record<string, string | { open: string; close: string; closed: boolean }> | null
  description: string | null
  status: 'active' | 'inactive'
  created_at: string
  updated_at: string | null
  deleted_at: string | null
  // joined
  city_name: string | null
  area_name: string | null
  active_coupons: number
  gallery_count: number
  cover_image: string | null
  gallery?: GalleryImage[]
  categories?: StoreCategory[]
}

export interface City {
  id: number
  city_name: string
  state: string
}

export interface Area {
  id: number
  area_name: string
  city_id: number
}

export interface Category {
  id: number
  name: string
  icon: string | null
  color_code: string | null
}

export interface SubCategory {
  id: number
  category_id: number
  name: string
  icon: string | null
}

export interface CreateStorePayload {
  store_name: string
  address: string
  city_id: number
  area_id?: number | null
  phone?: string
  email?: string
  description?: string
  opening_hours?: Record<string, string | { open: string; close: string; closed: boolean }>
  latitude?: number
  longitude?: number
  category_ids?: number[]
}

export type UpdateStorePayload = Partial<CreateStorePayload & { status: 'active' | 'inactive' }>

// ── API Calls ─────────────────────────────────────────────────────────────────

export const storeApi = {
  // Stores CRUD
  list: (params?: { status?: 'active' | 'inactive' }) =>
    apiClient.get<{ success: boolean; data: Store[] }>('/merchants/stores', { params }),

  get: (id: number) =>
    apiClient.get<{ success: boolean; data: Store }>(`/merchants/stores/${id}`),

  create: (data: CreateStorePayload) =>
    apiClient.post<{ success: boolean; data: Store }>('/merchants/stores', data),

  update: (id: number, data: UpdateStorePayload) =>
    apiClient.put<{ success: boolean; data: Store }>(`/merchants/stores/${id}`, data),

  delete: (id: number) =>
    apiClient.delete<{ success: boolean }>(`/merchants/stores/${id}`),

  // Gallery
  uploadImages: (storeId: number, files: File[]) => {
    const form = new FormData()
    files.forEach((f) => form.append('images[]', f))
    return apiClient.post<{ success: boolean; data: GalleryImage[] }>(
      `/merchants/stores/${storeId}/gallery`,
      form,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
  },

  deleteImage: (storeId: number, imageId: number) =>
    apiClient.delete<{ success: boolean }>(`/merchants/stores/${storeId}/gallery/${imageId}`),

  setCover: (storeId: number, imageId: number) =>
    apiClient.put<{ success: boolean }>(`/merchants/stores/${storeId}/gallery/${imageId}`, {}),

  reorderGallery: (storeId: number, imageIds: number[]) =>
    apiClient.put<{ success: boolean }>(`/merchants/stores/${storeId}/gallery/reorder`, {
      image_ids: imageIds,
    }),

  // Master data
  getCities: () =>
    apiClient.get<{ success: boolean; data: City[] }>('/public/cities'),

  getAreas: (cityId?: number) =>
    apiClient.get<{ success: boolean; data: Area[] }>('/public/areas', {
      params: cityId ? { city_id: cityId } : undefined,
    }),

  getCategories: () =>
    apiClient.get<{ success: boolean; data: Category[] }>('/public/categories'),

  getSubCategories: (categoryId: number) =>
    apiClient.get<{ success: boolean; data: SubCategory[] }>(`/public/categories/${categoryId}/sub-categories`),
}
