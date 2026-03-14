import { apiClient } from '@/api/client'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface DashboardAnalytics {
  // Analytics-page KPIs
  total_coupons: number
  active_coupons: number
  total_redemptions: number
  this_month_redemptions: number
  chart: { date: string; count: number }[]
  // Home-page KPIs
  today_redemptions: number
  total_customers: number
  avg_rating: number | null
  pending_grievances: number
  weekly_redemptions: { date: string; count: number }[]
}

export interface RedemptionAnalytics {
  daily: { date: string; count: number; discount: string }[]
  total_redemptions: number
  total_discount: string
}

export interface CustomerAnalytics {
  by_city: { city: string; count: number }[]
  new_customers: number
  returning_customers: number
}

export interface TopCoupon {
  id: number
  title: string
  coupon_code: string
  redemption_count: number
}

export interface RevenueAnalytics {
  total_sales: number
  total_revenue: string
  by_store: { store_id: number; store_name: string; count: number; revenue: string }[]
}

// ── Sales Registry Types ───────────────────────────────────────────────────────

export interface SaleRecord {
  id: number
  merchant_id: number
  store_id: number
  store_name: string | null
  customer_id: number | null
  customer_name: string | null
  transaction_amount: string
  transaction_date: string
  payment_method: 'cash' | 'card' | 'upi' | 'wallet' | 'other' | null
  coupon_used: number | null
  coupon_title: string | null
  coupon_code: string | null
  discount_amount: string
}

export interface SalesMeta {
  total: number
  page: number
  limit: number
  pages: number
}

export interface SalesSummary {
  period: { from: string; to: string }
  total_sales: number
  total_revenue: number
  avg_transaction: number
  total_discounts: number
  by_payment: { payment_method: string | null; cnt: number; total: string }[]
  daily: { date: string; cnt: number; revenue: string }[]
}

export interface CreateSalePayload {
  store_id: number
  transaction_amount: number
  payment_method?: 'cash' | 'card' | 'upi' | 'wallet' | 'other'
  coupon_code?: string
  discount_amount?: number
  transaction_date?: string
  customer_id?: number
}

// ── API Calls ─────────────────────────────────────────────────────────────────

type R<T> = { success: boolean; message: string; data: T }
type Paged<T> = R<T> & { meta: SalesMeta }

export const analyticsApi = {
  dashboard: () =>
    apiClient.get<R<DashboardAnalytics>>('/merchants/analytics/dashboard'),

  redemptions: (params?: { period?: number; from?: string; to?: string }) =>
    apiClient.get<R<RedemptionAnalytics>>('/merchants/analytics/redemptions', { params }),

  customers: () =>
    apiClient.get<R<CustomerAnalytics>>('/merchants/analytics/customers'),

  topCoupons: () =>
    apiClient.get<R<TopCoupon[]>>('/merchants/analytics/top-coupons'),

  revenue: (params?: { from?: string; to?: string }) =>
    apiClient.get<R<RevenueAnalytics>>('/merchants/analytics/revenue', { params }),
}

export const salesApi = {
  list: (params?: {
    page?: number
    limit?: number
    store_id?: number
    from?: string
    to?: string
    payment_method?: string
  }) =>
    apiClient.get<Paged<SaleRecord[]>>('/merchants/sales-registry', { params }),

  summary: (params?: { from?: string; to?: string; store_id?: number }) =>
    apiClient.get<R<SalesSummary>>('/merchants/sales-registry/summary', { params }),

  record: (data: CreateSalePayload) =>
    apiClient.post<R<SaleRecord>>('/merchants/sales-registry', data),

  exportUrl: (from: string, to: string) =>
    `${apiClient.defaults.baseURL}/merchants/sales-registry/export?from=${from}&to=${to}`,
}
