/**
 * Flash Discount API client — unit tests
 * Uses MSW to intercept HTTP requests and verifies request/response handling.
 */
import { describe, it, expect, beforeEach } from 'vitest'
import { flashDiscountApi } from '@/api/endpoints/flashDiscounts'
import { useAuthStore } from '@/store/authStore'

// Give the API client a valid token so auth header is attached
beforeEach(() => {
  useAuthStore.setState({
    accessToken: 'mock-access-token-xyz',
    refreshToken: 'mock-refresh-token-xyz',
    merchant: null,
    isAuthenticated: true,
  })
})

describe('flashDiscountApi.list', () => {
  it('returns array of flash discounts', async () => {
    const res = await flashDiscountApi.list()
    expect(res.data.success).toBe(true)
    expect(Array.isArray(res.data.data)).toBe(true)
    expect(res.data.data).toHaveLength(1)
    expect(res.data.data[0].title).toBe('Weekend Flash Sale')
    expect(res.data.data[0].status).toBe('active')
  })
})

describe('flashDiscountApi.create', () => {
  it('creates a new flash discount and returns the record', async () => {
    const res = await flashDiscountApi.create({
      title: 'Midweek Special',
      discount_percentage: 15,
    })
    expect(res.data.success).toBe(true)
    expect(res.data.data.title).toBe('Midweek Special')
    expect(res.data.data.discount_percentage).toBe(15)
  })
})

describe('flashDiscountApi.remove', () => {
  it('deletes a discount and returns its id', async () => {
    const res = await flashDiscountApi.remove(1)
    expect(res.data.success).toBe(true)
    expect(res.data.data.id).toBe(1)
  })
})
