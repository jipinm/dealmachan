import '@testing-library/jest-dom'
import { beforeAll, afterAll, afterEach } from 'vitest'
import { server } from './mocks/server'

// Start MSW server before all tests
beforeAll(() => server.listen({ onUnhandledRequest: 'warn' }))

// Reset handlers after each test (ensures test isolation)
afterEach(() => server.resetHandlers())

// Stop server after all tests
afterAll(() => server.close())
