import { apiClient } from '@/api/client'

export interface ContactBody {
  name: string
  mobile: string
  subject: string
  message: string
}

export interface BusinessSignupBody {
  contact_name: string
  org_name: string
  category?: string
  email: string
  phone: string
  message?: string
}

export const publicFormsApi = {
  contact: (data: ContactBody) =>
    apiClient.post('/public/contact', data).then(r => r.data),

  businessSignup: (data: BusinessSignupBody) =>
    apiClient.post('/public/business-signup', data).then(r => r.data),
}
