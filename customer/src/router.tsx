import { lazy, Suspense } from 'react'
import { createBrowserRouter, Navigate } from 'react-router-dom'
import PageLoader from '@/components/ui/PageLoader'
import AuthGuard from '@/components/layout/AuthGuard'
import AppShell from '@/components/layout/AppShell'
import GuestShell from '@/components/layout/GuestShell'

// ── Auth pages (small, load eagerly) ─────────────────────────────────────────
import LoginPage from '@/pages/auth/LoginPage'
import RegisterPage from '@/pages/auth/RegisterPage'
import OtpVerifyPage from '@/pages/auth/OtpVerifyPage'
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage'
import ResetPasswordPage from '@/pages/auth/ResetPasswordPage'
import OnboardingPage from '@/pages/onboarding/OnboardingPage'

// ── Public pages (lazy-loaded, no auth required) ─────────────────────────────
const HomePage             = lazy(() => import('@/pages/home/HomePage'))
const DealsPage            = lazy(() => import('@/pages/deals/DealsPage'))
const DealDetailPage       = lazy(() => import('@/pages/deals/DealDetailPage'))
const StoresPage           = lazy(() => import('@/pages/stores/StoresPage'))
const StoreDetailPage      = lazy(() => import('@/pages/stores/StoreDetailPage'))
const FlashDealsPage       = lazy(() => import('@/pages/flash-deals/FlashDealsPage'))
const FlashDealDetailPage  = lazy(() => import('@/pages/flash-deals/FlashDealDetailPage'))
const BlogListPage         = lazy(() => import('@/pages/blog/BlogListPage'))
const BlogDetailPage       = lazy(() => import('@/pages/blog/BlogDetailPage'))
const AboutPage            = lazy(() => import('@/pages/static/AboutPage'))
const ContactPage          = lazy(() => import('@/pages/static/ContactPage'))
const BusinessSignupPage   = lazy(() => import('@/pages/static/BusinessSignupPage'))
const CategoriesPage       = lazy(() => import('@/pages/categories/CategoriesPage'))

// ── Protected pages (lazy-loaded, requires auth) ──────────────────────────────
const DashboardPage        = lazy(() => import('@/pages/dashboard/DashboardPage'))
const ExplorePage          = lazy(() => import('@/pages/explore/ExplorePage'))
const CouponWalletPage     = lazy(() => import('@/pages/coupons/CouponWalletPage'))
const WishlistPage         = lazy(() => import('@/pages/wishlist/WishlistPage'))
const LoyaltyCardsPage     = lazy(() => import('@/pages/loyalty/LoyaltyCardsPage'))
const ImportantDaysPage    = lazy(() => import('@/pages/important-days/ImportantDaysPage'))
const ActivityPage         = lazy(() => import('@/pages/activity/ActivityPage'))
const ProfilePage          = lazy(() => import('@/pages/profile/ProfilePage'))
const EditProfilePage      = lazy(() => import('@/pages/profile/EditProfilePage'))
const SubscriptionPage     = lazy(() => import('@/pages/profile/SubscriptionPage'))
const MyCardPage           = lazy(() => import('@/pages/profile/MyCardPage'))

/** Wraps lazy-loaded content in a Suspense fallback */
function Lazy({ element }: { element: React.ReactNode }) {
  return <Suspense fallback={<PageLoader />}>{element}</Suspense>
}

export const router = createBrowserRouter([

  // ═══════════════════════════════════════════════════════════════════════════
  // PUBLIC ROUTES — wrapped in GuestShell (sticky nav header + footer)
  // No authentication required. All visitors can browse freely.
  // ═══════════════════════════════════════════════════════════════════════════
  {
    element: <GuestShell />,
    children: [
      // Home
      { index: true, element: <Lazy element={<HomePage />} /> },

      // Deals / Coupons
      { path: 'deals',            element: <Lazy element={<DealsPage />} /> },
      { path: 'deals/:id',        element: <Lazy element={<DealDetailPage />} /> },

      // Stores / Merchants
      { path: 'stores',           element: <Lazy element={<StoresPage />} /> },
      { path: 'stores/:id',       element: <Lazy element={<StoreDetailPage />} /> },

      // Flash Deals
      { path: 'flash-deals',      element: <Lazy element={<FlashDealsPage />} /> },
      { path: 'flash-deals/:id',  element: <Lazy element={<FlashDealDetailPage />} /> },

      // Categories
      { path: 'categories',       element: <Lazy element={<CategoriesPage />} /> },

      // Blog
      { path: 'blog',             element: <Lazy element={<BlogListPage />} /> },
      { path: 'blog/:slug',       element: <Lazy element={<BlogDetailPage />} /> },

      // Static pages
      { path: 'about',            element: <Lazy element={<AboutPage />} /> },
      { path: 'contact',          element: <Lazy element={<ContactPage />} /> },
      { path: 'business-signup',  element: <Lazy element={<BusinessSignupPage />} /> },

      // ── Auth pages — inside GuestShell so they share the site header + footer
      { path: 'login',            element: <LoginPage /> },
      { path: 'register',         element: <RegisterPage /> },
      { path: 'otp-verify',       element: <OtpVerifyPage /> },
      { path: 'forgot-password',  element: <ForgotPasswordPage /> },
      { path: 'reset-password',   element: <ResetPasswordPage /> },
    ],
  },

  // Onboarding (auth required, but bypasses new-user check inside)
  {
    element: <AuthGuard />,
    children: [
      { path: '/onboarding', element: <OnboardingPage /> },
    ],
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // PROTECTED ROUTES — requires login → AuthGuard → AppShell (dashboard style)
  // Personal / account-specific pages
  // ═══════════════════════════════════════════════════════════════════════════
  {
    element: <AuthGuard />,
    children: [
      {
        element: <AppShell />,
        children: [
          { path: 'dashboard',                element: <Lazy element={<DashboardPage />} /> },
          { path: 'explore',                  element: <Lazy element={<ExplorePage />} /> },
          { path: 'wallet',                   element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'wallet/gifts',             element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'wallet/store',             element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'wallet/history',           element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'wishlist',                 element: <Lazy element={<WishlistPage />} /> },
          { path: 'loyalty-cards',            element: <Lazy element={<LoyaltyCardsPage />} /> },
          { path: 'important-days',           element: <Lazy element={<ImportantDaysPage />} /> },
          { path: 'activity',                 element: <Lazy element={<ActivityPage />} /> },
          { path: 'activity/*',               element: <Lazy element={<ActivityPage />} /> },
          { path: 'profile',                  element: <Lazy element={<ProfilePage />} /> },
          { path: 'profile/edit',             element: <Lazy element={<EditProfilePage />} /> },
          { path: 'profile/subscription',     element: <Lazy element={<SubscriptionPage />} /> },
          { path: 'profile/card',             element: <Lazy element={<MyCardPage />} /> },
          { path: 'profile/*',                element: <Lazy element={<ProfilePage />} /> },
          { path: 'notifications',            element: <Lazy element={<ActivityPage />} /> },
          { path: 'grievances',               element: <Lazy element={<ActivityPage />} /> },
          { path: 'grievances/*',             element: <Lazy element={<ActivityPage />} /> },
          { path: 'referrals',                element: <Lazy element={<ProfilePage />} /> },
        ],
      },
    ],
  },

  // ── Fallback ─────────────────────────────────────────────────────────────
  { path: '*', element: <Navigate to="/" replace /> },
])
