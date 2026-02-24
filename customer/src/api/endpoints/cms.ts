import { apiClient } from '../client'

export interface CmsPage {
  id: number
  slug: string
  title: string
  content: string
  meta_description: string | null
  updated_at: string | null
}

export const cmsApi = {
  getPage: (slug: string): Promise<CmsPage> =>
    apiClient.get(`/public/page/${slug}`).then(r => r.data.data ?? r.data),
}
