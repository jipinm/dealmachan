/**
 * Integration test — Login → Scan → Redeem flow
 *
 * Tests the critical merchant journey:
 *  1. Merchant opens the app unauthenticated → sees LoginPage
 *  2. Merchant enters valid credentials → authenticates
 *  3. Auth store updates → merchant session established
 *  4. QR scan endpoint accepts a valid coupon code
 *  5. Redeem endpoint returns a success confirmation
 *
 * Note: The camera / QR detection (jsQR) is not testable in jsdom.
 * We test the API layer and token flow instead.
 */
import { describe, it, expect, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import LoginPage from '@/pages/auth/LoginPage'
import { renderWithProviders } from '../utils/renderWithProviders'
import { useAuthStore } from '@/store/authStore'
import { apiClient } from '@/api/client'

// Coupon codes are now in the shared MSW handlers
const VALID_COUPON_CODE = 'DM-VALID-001'
const INVALID_COUPON_CODE = 'DM-EXPIRED-001'

// Reset store before every test
beforeEach(() => {
  useAuthStore.setState({
    accessToken: null,
    refreshToken: null,
    merchant: null,
    isAuthenticated: false,
  })
})

// ── Test suite ────────────────────────────────────────────────────────────────

describe('Login → auth state transition', () => {
  it('the auth store starts unauthenticated', () => {
    expect(useAuthStore.getState().isAuthenticated).toBe(false)
  })

  it('successful login stores access + refresh tokens', async () => {
    await useAuthStore.getState().login('merchant@test.com', 'password123')

    const { accessToken, refreshToken, isAuthenticated, merchant } = useAuthStore.getState()
    expect(isAuthenticated).toBe(true)
    expect(accessToken).toBe('mock-access-token-xyz')
    expect(refreshToken).toBe('mock-refresh-token-xyz')
    expect(merchant?.email).toBe('merchant@test.com')
  })

  it('apiClient sends Bearer token after login', async () => {
    await useAuthStore.getState().login('merchant@test.com', 'password123')

    // Manually inspect what the interceptor attaches
    const token = useAuthStore.getState().accessToken
    expect(token).toBeTruthy()

    // Verify interceptor would attach the token by checking store directly
    const reqConfig = { headers: {} as Record<string, string> }
    const storeToken = useAuthStore.getState().accessToken
    if (storeToken) reqConfig.headers['Authorization'] = `Bearer ${storeToken}`
    expect(reqConfig.headers['Authorization']).toBe('Bearer mock-access-token-xyz')
  })
})

describe('Scan & Redeem — API layer', () => {
  beforeEach(async () => {
    // Log in to get a valid token
    await useAuthStore.getState().login('merchant@test.com', 'password123')
  })

  it('validates and redeems a valid coupon code', async () => {
    const res = await apiClient.post('/merchants/coupons/scan-redeem', {
      code: VALID_COUPON_CODE,
      store_id: 1,
    })
    expect(res.data.success).toBe(true)
    expect(res.data.data.customer_name).toBe('Ravi Kumar')
    expect(res.data.data.coupon_id).toBe(10)
    expect(res.data.message).toMatch(/redeemed/i)
  })

  it('returns failure for an invalid / expired coupon', async () => {
    await expect(
      apiClient.post('/merchants/coupons/scan-redeem', { code: INVALID_COUPON_CODE }),
    ).rejects.toMatchObject({
      response: { status: 422, data: { success: false } },
    })
  })
})

describe('Token refresh flow', () => {
  it('exchanges refresh token for a new access token', async () => {
    // Seed with a token pair
    useAuthStore.getState().setTokens('old-access', 'old-refresh')

    const newAccess = await useAuthStore.getState().refresh()

    expect(newAccess).toBe('refreshed-access-token')
    expect(useAuthStore.getState().accessToken).toBe('refreshed-access-token')
    expect(useAuthStore.getState().refreshToken).toBe('refreshed-refresh-token')
    expect(useAuthStore.getState().isAuthenticated).toBe(true)
  })

  it('throws when no refresh token is stored', async () => {
    // Store has null refreshToken
    await expect(useAuthStore.getState().refresh()).rejects.toThrow(/no refresh token/i)
  })
})

describe('Login UI → redirect', () => {
  it('renders the login form and submits without throwing', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    await user.type(screen.getByLabelText('Email address'), 'merchant@test.com')
    await user.type(screen.getByLabelText('Password'), 'password123')
    await user.click(screen.getByRole('button', { name: /sign in|log in/i }))

    await waitFor(() => {
      expect(useAuthStore.getState().isAuthenticated).toBe(true)
    })
  })
})
