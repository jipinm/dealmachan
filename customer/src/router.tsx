import { lazy, Suspense } from 'react'
import { createBrowserRouter, Navigate } from 'react-router-dom'
import PageLoader from '@/components/ui/PageLoader'
import AuthGuard from '@/components/layout/AuthGuard'
import AppShell from '@/components/layout/AppShell'

// ── Auth pages (small, load eagerly) ─────────────────────────────────────────
import LoginPage from '@/pages/auth/LoginPage'
import RegisterPage from '@/pages/auth/RegisterPage'
import OtpVerifyPage from '@/pages/auth/OtpVerifyPage'
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage'
import ResetPasswordPage from '@/pages/auth/ResetPasswordPage'
import OnboardingPage from '@/pages/onboarding/OnboardingPage'

// ── Protected pages (lazy-loaded) ────────────────────────────────────────────
const HomePage            = lazy(() => import('@/pages/home/HomePage'))
const ExplorePage         = lazy(() => import('@/pages/explore/ExplorePage'))
const CouponWalletPage    = lazy(() => import('@/pages/coupons/CouponWalletPage'))
const CouponBrowsePage    = lazy(() => import('@/pages/coupons/CouponBrowsePage'))
const CouponDetailPage    = lazy(() => import('@/pages/coupons/CouponDetailPage'))
const MerchantDetailPage  = lazy(() => import('@/pages/merchants/MerchantDetailPage'))
const ActivityPage        = lazy(() => import('@/pages/activity/ActivityPage'))
const ProfilePage         = lazy(() => import('@/pages/profile/ProfilePage'))
const EditProfilePage     = lazy(() => import('@/pages/profile/EditProfilePage'))
const SubscriptionPage    = lazy(() => import('@/pages/profile/SubscriptionPage'))
const MyCardPage          = lazy(() => import('@/pages/profile/MyCardPage'))

/** Wraps lazy-loaded content in a Suspense fallback */
function Lazy({ element }: { element: React.ReactNode }) {
  return <Suspense fallback={<PageLoader />}>{element}</Suspense>
}

export const router = createBrowserRouter([
  // ── Public / Auth routes ──────────────────────────────────────────────────
  { path: '/login',           element: <LoginPage /> },
  { path: '/register',        element: <RegisterPage /> },
  { path: '/otp-verify',      element: <OtpVerifyPage /> },
  { path: '/forgot-password', element: <ForgotPasswordPage /> },
  { path: '/reset-password',  element: <ResetPasswordPage /> },

  // ── Onboarding (auth required, but skips is_new_user check) ──────────────
  {
    element: <AuthGuard />,
    children: [
      { path: '/onboarding', element: <OnboardingPage /> },
    ],
  },

  // ── Protected routes (inside AppShell) ───────────────────────────────────
  {
    element: <AuthGuard />,
    children: [
      {
        element: <AppShell />,
        children: [
          { index: true,            element: <Lazy element={<HomePage />} /> },
          { path: 'explore',        element: <Lazy element={<ExplorePage />} /> },
          { path: 'merchants/:id',  element: <Lazy element={<MerchantDetailPage />} /> },
          { path: 'coupons',        element: <Lazy element={<CouponBrowsePage />} /> },
          { path: 'coupons/:id',    element: <Lazy element={<CouponDetailPage />} /> },
          { path: 'wallet',         element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'wallet/gifts',   element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'wallet/history', element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'activity',       element: <Lazy element={<ActivityPage />} /> },
          { path: 'activity/*',     element: <Lazy element={<ActivityPage />} /> },
          { path: 'profile',              element: <Lazy element={<ProfilePage />} /> },
          { path: 'profile/edit',         element: <Lazy element={<EditProfilePage />} /> },
          { path: 'profile/subscription', element: <Lazy element={<SubscriptionPage />} /> },
          { path: 'profile/card',         element: <Lazy element={<MyCardPage />} /> },
          { path: 'profile/*',            element: <Lazy element={<ProfilePage />} /> },
          // Stub routes — will grow in Phase 4+
          { path: 'notifications',        element: <Lazy element={<ActivityPage />} /> },
          { path: 'grievances',           element: <Lazy element={<ActivityPage />} /> },
          { path: 'referrals',            element: <Lazy element={<ProfilePage />} /> },
        ],
      },
    ],
  },

  // ── Fallback ─────────────────────────────────────────────────────────────
  { path: '*', element: <Navigate to="/" replace /> },
])
