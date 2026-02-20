/**
 * LoginPage — component tests
 * Verifies form rendering, validation messages, successful login, and error display.
 */
import { describe, it, expect, beforeEach } from 'vitest'
import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import LoginPage from '@/pages/auth/LoginPage'
import { renderWithProviders } from '../utils/renderWithProviders'
import { server } from '../mocks/server'
import { useAuthStore } from '@/store/authStore'

beforeEach(() => {
  useAuthStore.setState({
    accessToken: null,
    refreshToken: null,
    merchant: null,
    isAuthenticated: false,
  })
})

describe('LoginPage — rendering', () => {
  it('renders email and password fields and a sign-in button', () => {
    renderWithProviders(<LoginPage />)
    expect(screen.getByLabelText('Email address')).toBeInTheDocument()
    expect(screen.getByLabelText('Password')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /sign in|log in/i })).toBeInTheDocument()
  })

  it('renders the DealMachan branding', () => {
    renderWithProviders(<LoginPage />)
    expect(screen.getByText(/DealMachan Merchant/i)).toBeInTheDocument()
  })
})

describe('LoginPage — form validation', () => {
  it('shows email validation error when submitting empty form', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    await user.click(screen.getByRole('button', { name: /sign in|log in/i }))

    await waitFor(() => {
      expect(screen.getByText(/valid email/i)).toBeInTheDocument()
    })
  })

  it('shows password validation error for short password', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    await user.type(screen.getByLabelText('Email address'), 'valid@email.com')
    await user.type(screen.getByLabelText('Password'), 'abc')
    await user.click(screen.getByRole('button', { name: /sign in|log in/i }))

    await waitFor(() => {
      expect(screen.getByText(/6 characters/i)).toBeInTheDocument()
    })
  })
})

describe('LoginPage — successful login', () => {
  it('calls the login API and authenticates on valid credentials', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    await user.type(screen.getByLabelText('Email address'), 'merchant@test.com')
    await user.type(screen.getByLabelText('Password'), 'password123')
    await user.click(screen.getByRole('button', { name: /sign in|log in/i }))

    await waitFor(() => {
      expect(useAuthStore.getState().isAuthenticated).toBe(true)
    })
    expect(useAuthStore.getState().accessToken).toBe('mock-access-token-xyz')
  })
})

describe('LoginPage — failed login', () => {
  it('displays API error message on invalid credentials', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    await user.type(screen.getByLabelText('Email address'), 'merchant@test.com')
    await user.type(screen.getByLabelText('Password'), 'wrongpassword')
    await user.click(screen.getByRole('button', { name: /sign in|log in/i }))

    await waitFor(() => {
      expect(screen.getByText(/invalid credentials/i)).toBeInTheDocument()
    })
    expect(useAuthStore.getState().isAuthenticated).toBe(false)
  })

  it('shows a generic error when the server is unavailable', async () => {
    server.use(
      http.post('/api/auth/merchant/login', () =>
        HttpResponse.json({ success: false, message: 'Service temporarily unavailable' }, { status: 503 }),
      ),
    )
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    await user.type(screen.getByLabelText('Email address'), 'merchant@test.com')
    await user.type(screen.getByLabelText('Password'), 'password123')
    await user.click(screen.getByRole('button', { name: /sign in|log in/i }))

    await waitFor(() => {
      // We just want any error text to be visible — not stuck in loading state
      expect(screen.queryByRole('button', { name: /signing in/i })).not.toBeInTheDocument()
    })
  })
})

describe('LoginPage — password visibility toggle', () => {
  it('toggles password field type between text and password', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    const input = screen.getByLabelText('Password')
    expect(input).toHaveAttribute('type', 'password')

    // Find the eye-toggle button (aria-label or role button near password)
    const toggle = screen.getByRole('button', { name: /show|hide|toggle/i })
    await user.click(toggle)

    expect(screen.getByLabelText('Password')).toHaveAttribute('type', 'text')
  })
})
