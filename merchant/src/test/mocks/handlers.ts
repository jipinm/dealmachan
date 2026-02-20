import { http, HttpResponse } from 'msw'

const BASE = '/api'

// ── Shared fixtures ────────────────────────────────────────────────────────

export const MOCK_MERCHANT = {
  id: 1,
  user_id: 101,
  business_name: 'Test Merchant',
  email: 'merchant@test.com',
  phone: '9876543210',
  logo_url: null,
  profile_status: 'approved' as const,
  subscription_status: 'active' as const,
  subscription_expires_at: '2027-01-01',
}

export const MOCK_TOKENS = {
  access_token: 'mock-access-token-xyz',
  refresh_token: 'mock-refresh-token-xyz',
}

export const MOCK_FLASH_DISCOUNT = {
  id: 1,
  merchant_id: 1,
  store_id: null,
  store_name: null,
  title: 'Weekend Flash Sale',
  description: 'Save big this weekend!',
  discount_percentage: '20.00',
  valid_from: '2026-02-20T10:00:00',
  valid_until: '2026-02-22T23:59:00',
  max_redemptions: 100,
  current_redemptions: 5,
  status: 'active',
  created_at: '2026-02-20T09:00:00',
  updated_at: null,
}

// ── Handlers ──────────────────────────────────────────────────────────────

export const handlers = [
  // Auth — login
  http.post(`${BASE}/auth/merchant/login`, async ({ request }) => {
    const body = await request.json() as { email: string; password: string }
    if (body.email === 'merchant@test.com' && body.password === 'password123') {
      return HttpResponse.json({
        success: true,
        data: { merchant: MOCK_MERCHANT, tokens: MOCK_TOKENS },
        message: 'Login successful',
      })
    }
    return HttpResponse.json(
      { success: false, message: 'Invalid credentials' },
      { status: 401 },
    )
  }),

  // Auth — logout
  http.post(`${BASE}/auth/merchant/logout`, () =>
    HttpResponse.json({ success: true, message: 'Logged out' }),
  ),

  // Auth — refresh
  http.post(`${BASE}/auth/merchant/refresh`, () =>
    HttpResponse.json({
      success: true,
      data: {
        access_token: 'refreshed-access-token',
        refresh_token: 'refreshed-refresh-token',
      },
    }),
  ),

  // Flash discounts — list
  http.get(`${BASE}/merchants/flash-discounts`, () =>
    HttpResponse.json({
      success: true,
      data: [MOCK_FLASH_DISCOUNT],
      message: 'OK',
    }),
  ),

  // Flash discounts — create
  http.post(`${BASE}/merchants/flash-discounts`, async ({ request }) => {
    const body = await request.json() as Record<string, unknown>
    return HttpResponse.json({
      success: true,
      data: { ...MOCK_FLASH_DISCOUNT, ...body, id: 2 },
      message: 'Created',
    }, { status: 201 })
  }),

  // Flash discounts — delete
  http.delete(`${BASE}/merchants/flash-discounts/:id`, ({ params }) =>
    HttpResponse.json({
      success: true,
      data: { id: Number(params.id) },
      message: 'Deleted',
    }),
  ),

  // Labels — list
  http.get(`${BASE}/merchants/labels`, () =>
    HttpResponse.json({
      success: true,
      data: {
        assigned: [{ id: 1, label_name: 'Verified Partner', description: null, priority_weight: 1, status: 'active' }],
        available: [
          { id: 1, label_name: 'Verified Partner', description: null, priority_weight: 1, status: 'active', assigned: true },
          { id: 2, label_name: 'Top Rated', description: null, priority_weight: 2, status: 'active', assigned: false },
        ],
      },
      message: 'OK',
    }),
  ),

  // Labels — request
  http.post(`${BASE}/merchants/labels/request`, async ({ request }) => {
    const body = await request.json() as { label_id: number }
    if (!body.label_id) {
      return HttpResponse.json({ success: false, message: 'label_id required' }, { status: 422 })
    }
    return HttpResponse.json({ success: true, data: null, message: 'Request submitted' })
  }),

  // Customers — list
  http.get(`${BASE}/merchants/customers`, () =>
    HttpResponse.json({
      success: true,
      data: [],
      meta: { total: 0, page: 1, limit: 20, pages: 0 },
      message: 'OK',
    }),
  ),

  // Customers — create
  http.post(`${BASE}/merchants/customers`, async ({ request }) => {
    const body = await request.json() as { name: string; phone?: string; email?: string }
    if (!body.name) {
      return HttpResponse.json({ success: false, message: 'name required' }, { status: 422 })
    }
    return HttpResponse.json({
      success: true,
      data: { id: 1, name: body.name, phone: body.phone ?? null, email: body.email ?? null, customer_type: 'regular', created_at: new Date().toISOString() },
      message: 'Customer created',
    }, { status: 201 })
  }),

  // Scan & Redeem
  http.post(`${BASE}/merchants/coupons/scan-redeem`, async ({ request }) => {
    const body = await request.json() as { code: string; store_id?: number }
    if (body.code === 'DM-VALID-001') {
      return HttpResponse.json({
        success: true,
        data: {
          coupon_id: 10,
          title: '20% Off Burger',
          discount_value: 20,
          discount_type: 'percentage',
          customer_name: 'Ravi Kumar',
          redemption_id: 9001,
        },
        message: 'Coupon redeemed successfully',
      })
    }
    return HttpResponse.json(
      { success: false, message: 'Coupon is expired or already redeemed' },
      { status: 422 },
    )
  }),

  // Manual Redeem
  http.post(`${BASE}/merchants/coupons/manual-redeem`, async ({ request }) => {
    const body = await request.json() as { code: string }
    if (body.code === 'DM-VALID-001') {
      return HttpResponse.json({ success: true, data: { redemption_id: 9002 }, message: 'Redeemed' })
    }
    return HttpResponse.json({ success: false, message: 'Invalid code' }, { status: 422 })
  }),

  // Dashboard
  http.get(`${BASE}/merchants/analytics/dashboard`, () =>
    HttpResponse.json({
      success: true,
      data: {
        redemptions_today: 3,
        redemptions_this_week: 18,
        redemptions_this_month: 72,
        total_active_coupons: 5,
        total_active_customers: 40,
        average_rating: 4.3,
        pending_grievances: 1,
      },
    }),
  ),
]
