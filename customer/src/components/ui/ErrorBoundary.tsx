import { Component, type ErrorInfo, type ReactNode } from 'react'
import { RefreshCw, AlertCircle } from 'lucide-react'

interface Props {
  children: ReactNode
  /** Custom fallback — if not provided the default card is shown */
  fallback?: ReactNode
}

interface State {
  hasError: boolean
  error: Error | null
}

/**
 * Class-based ErrorBoundary that catches render/lifecycle errors in its subtree.
 * Use this to wrap route components so that one broken page doesn't crash the
 * entire shell.
 *
 * @example
 * <ErrorBoundary>
 *   <SomePage />
 * </ErrorBoundary>
 */
export default class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props)
    this.state = { hasError: false, error: null }
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    // In production you could forward to Sentry / LogRocket here
    console.error('[ErrorBoundary]', error, info.componentStack)
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null })
  }

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) return this.props.fallback

      return (
        <div className="flex min-h-[60vh] items-center justify-center px-4">
          <div className="w-full max-w-sm rounded-2xl border border-red-100 bg-white p-8 shadow-lg text-center">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-50">
              <AlertCircle className="h-8 w-8 text-red-500" />
            </div>
            <h2 className="mb-2 text-lg font-bold text-gray-900">
              Something went wrong
            </h2>
            <p className="mb-6 text-sm text-gray-500">
              An unexpected error occurred. Please try refreshing or go back to
              the home page.
            </p>
            {import.meta.env.DEV && this.state.error && (
              <pre className="mb-6 overflow-auto rounded-lg bg-red-50 p-3 text-left text-xs text-red-700">
                {this.state.error.message}
              </pre>
            )}
            <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
              <button
                onClick={this.handleReset}
                className="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 active:scale-95"
              >
                <RefreshCw className="h-4 w-4" />
                Try again
              </button>
              <a
                href="/"
                className="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50"
              >
                Go to Home
              </a>
            </div>
          </div>
        </div>
      )
    }

    return this.props.children
  }
}
