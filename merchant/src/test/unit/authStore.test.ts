/**
 * authStore — unit tests
 * Tests token storage, login success/failure and logout clearing state.
 */
import { describe, it, expect, beforeEach } from 'vitest'
import { useAuthStore } from '@/store/authStore'

// Reset store state before every test
beforeEach(() => {
  useAuthStore.setState({
    accessToken: null,
    refreshToken: null,
    merchant: null,
    isAuthenticated: false,
  })
})

describe('authStore — initial state', () => {
  it('starts unauthenticated with no tokens', () => {
    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(false)
    expect(state.accessToken).toBeNull()
    expect(state.refreshToken).toBeNull()
    expect(state.merchant).toBeNull()
  })
})

describe('authStore — setTokens', () => {
  it('marks authenticated and stores tokens', () => {
    useAuthStore.getState().setTokens('access-abc', 'refresh-xyz')
    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(true)
    expect(state.accessToken).toBe('access-abc')
    expect(state.refreshToken).toBe('refresh-xyz')
  })
})

describe('authStore — setMerchant', () => {
  it('stores merchant profile', () => {
    const profile = {
      id: 1,
      user_id: 101,
      business_name: 'Test Co',
      email: 'test@test.com',
      phone: '1234567890',
      logo_url: null,
      profile_status: 'approved' as const,
      subscription_status: 'active' as const,
      subscription_expires_at: '2027-01-01',
    }
    useAuthStore.getState().setMerchant(profile)
    expect(useAuthStore.getState().merchant).toMatchObject({ business_name: 'Test Co' })
  })
})

describe('authStore — login (via MSW)', () => {
  it('sets tokens and merchant on successful login', async () => {
    await useAuthStore.getState().login('merchant@test.com', 'password123')

    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(true)
    expect(state.accessToken).toBe('mock-access-token-xyz')
    expect(state.refreshToken).toBe('mock-refresh-token-xyz')
    expect(state.merchant?.business_name).toBe('Test Merchant')
  })

  it('throws and leaves state unchanged on bad credentials', async () => {
    await expect(
      useAuthStore.getState().login('merchant@test.com', 'wrong-password'),
    ).rejects.toThrow()

    expect(useAuthStore.getState().isAuthenticated).toBe(false)
  })
})

describe('authStore — logout', () => {
  it('clears all state after logout', async () => {
    // Log in first
    await useAuthStore.getState().login('merchant@test.com', 'password123')
    expect(useAuthStore.getState().isAuthenticated).toBe(true)

    // Now log out
    useAuthStore.getState().logout()

    const state = useAuthStore.getState()
    expect(state.isAuthenticated).toBe(false)
    expect(state.accessToken).toBeNull()
    expect(state.refreshToken).toBeNull()
    expect(state.merchant).toBeNull()
  })
})
