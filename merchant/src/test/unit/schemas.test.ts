/**
 * Zod schema validation — unit tests
 * Tests the validation schemas used in forms across the app.
 */
import { describe, it, expect } from 'vitest'
import { z } from 'zod'

// ── Replicated schemas (keeps tests independent of UI components) ─────────────

const loginSchema = z.object({
  email:    z.string().email('Enter a valid email'),
  password: z.string().min(6, 'Password must be at least 6 characters'),
})

const flashDiscountSchema = z.object({
  title:               z.string().min(1, 'Title required'),
  description:         z.string().optional(),
  discount_percentage: z.string().refine(
    v => !isNaN(parseFloat(v)) && parseFloat(v) > 0 && parseFloat(v) <= 100,
    'Must be 1–100',
  ),
  store_id:        z.string().optional(),
  valid_from:      z.string().optional(),
  valid_until:     z.string().optional(),
  max_redemptions: z.string().optional(),
})

const createCustomerSchema = z.object({
  name:  z.string().min(1, 'Name required'),
  phone: z.string().optional(),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
}).refine(d => d.phone || d.email, {
  message: 'Phone or email is required',
  path: ['phone'],
})

// ── Login schema ──────────────────────────────────────────────────────────────

describe('loginSchema', () => {
  it('passes with valid email and password', () => {
    const result = loginSchema.safeParse({ email: 'user@test.com', password: 'secure123' })
    expect(result.success).toBe(true)
  })

  it('rejects non-email string', () => {
    const result = loginSchema.safeParse({ email: 'not-an-email', password: 'secure123' })
    expect(result.success).toBe(false)
    expect((result as any).error.flatten().fieldErrors.email).toContain('Enter a valid email')
  })

  it('rejects password shorter than 6 chars', () => {
    const result = loginSchema.safeParse({ email: 'user@test.com', password: 'abc' })
    expect(result.success).toBe(false)
    expect((result as any).error.flatten().fieldErrors.password[0]).toMatch(/6 characters/)
  })
})

// ── Flash discount schema ─────────────────────────────────────────────────────

describe('flashDiscountSchema', () => {
  it('passes with valid title and discount', () => {
    const result = flashDiscountSchema.safeParse({ title: 'Sale', discount_percentage: '25' })
    expect(result.success).toBe(true)
  })

  it('rejects empty title', () => {
    const result = flashDiscountSchema.safeParse({ title: '', discount_percentage: '25' })
    expect(result.success).toBe(false)
  })

  it('rejects discount_percentage above 100', () => {
    const result = flashDiscountSchema.safeParse({ title: 'Sale', discount_percentage: '101' })
    expect(result.success).toBe(false)
    expect((result as any).error.flatten().fieldErrors.discount_percentage[0]).toMatch(/1–100/)
  })

  it('rejects discount_percentage of zero', () => {
    const result = flashDiscountSchema.safeParse({ title: 'Sale', discount_percentage: '0' })
    expect(result.success).toBe(false)
  })

  it('rejects negative discount', () => {
    const result = flashDiscountSchema.safeParse({ title: 'Sale', discount_percentage: '-10' })
    expect(result.success).toBe(false)
  })

  it('accepts boundary value 100', () => {
    const result = flashDiscountSchema.safeParse({ title: 'Full', discount_percentage: '100' })
    expect(result.success).toBe(true)
  })
})

// ── Create customer schema ────────────────────────────────────────────────────

describe('createCustomerSchema', () => {
  it('passes with name and phone', () => {
    const result = createCustomerSchema.safeParse({ name: 'Alice', phone: '9876543210' })
    expect(result.success).toBe(true)
  })

  it('passes with name and email', () => {
    const result = createCustomerSchema.safeParse({ name: 'Bob', email: 'bob@test.com' })
    expect(result.success).toBe(true)
  })

  it('passes with name, phone and email', () => {
    const result = createCustomerSchema.safeParse({ name: 'Carol', phone: '1234567890', email: 'carol@test.com' })
    expect(result.success).toBe(true)
  })

  it('rejects when neither phone nor email provided', () => {
    const result = createCustomerSchema.safeParse({ name: 'Dave' })
    expect(result.success).toBe(false)
    const errors = (result as any).error.flatten().fieldErrors
    expect(errors.phone).toBeDefined()
  })

  it('rejects empty name', () => {
    const result = createCustomerSchema.safeParse({ name: '', phone: '1234567890' })
    expect(result.success).toBe(false)
  })

  it('rejects invalid email format', () => {
    const result = createCustomerSchema.safeParse({ name: 'Eve', email: 'not-an-email' })
    expect(result.success).toBe(false)
  })
})
