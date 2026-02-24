import { lazy, Suspense } from 'react'
import { createBrowserRouter, Navigate } from 'react-router-dom'
import PageLoader from '@/components/ui/PageLoader'
import AuthGuard from '@/components/layout/AuthGuard'
import GuestShell from '@/components/layout/GuestShell'
import MandatoryPasswordResetGuard from '@/components/layout/MandatoryPasswordResetGuard'
import ErrorBoundary from '@/components/ui/ErrorBoundary'

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
const MerchantListPage     = lazy(() => import('@/pages/merchants/MerchantListPage'))
const MerchantDetailPage   = lazy(() => import('@/pages/merchants/MerchantDetailPage'))
const FlashDealsPage       = lazy(() => import('@/pages/flash-deals/FlashDealsPage'))
const FlashDealDetailPage  = lazy(() => import('@/pages/flash-deals/FlashDealDetailPage'))
const BlogListPage         = lazy(() => import('@/pages/blog/BlogListPage'))
const BlogDetailPage       = lazy(() => import('@/pages/blog/BlogDetailPage'))
const AboutPage            = lazy(() => import('@/pages/static/AboutPage'))
const ContactPage          = lazy(() => import('@/pages/static/ContactPage'))
const BusinessSignupPage   = lazy(() => import('@/pages/static/BusinessSignupPage'))
const CategoriesPage       = lazy(() => import('@/pages/categories/CategoriesPage'))
const SearchPage           = lazy(() => import('@/pages/search/SearchPage'))

// ── Protected pages (lazy-loaded, requires auth) ──────────────────────────────
const ExplorePage          = lazy(() => import('@/pages/explore/ExplorePage'))
const CouponWalletPage     = lazy(() => import('@/pages/coupons/CouponWalletPage'))
const StoreCouponPage      = lazy(() => import('@/pages/coupons/StoreCouponPage'))
const WishlistPage         = lazy(() => import('@/pages/wishlist/WishlistPage'))
const LoyaltyCardsPage     = lazy(() => import('@/pages/loyalty/LoyaltyCardsPage'))
const ImportantDaysPage    = lazy(() => import('@/pages/important-days/ImportantDaysPage'))
const ActivityPage         = lazy(() => import('@/pages/activity/ActivityPage'))
const NotificationsPage    = lazy(() => import('@/pages/notifications/NotificationsPage'))
const GrievanceListPage    = lazy(() => import('@/pages/grievances/GrievanceListPage'))
const GrievanceDetailPage  = lazy(() => import('@/pages/grievances/GrievanceDetailPage'))
const GrievanceFormPage    = lazy(() => import('@/pages/grievances/GrievanceFormPage'))
const ProfilePage          = lazy(() => import('@/pages/profile/ProfilePage'))
const EditProfilePage      = lazy(() => import('@/pages/profile/EditProfilePage'))
const SubscriptionPage     = lazy(() => import('@/pages/profile/SubscriptionPage'))
const MyCardPage           = lazy(() => import('@/pages/profile/MyCardPage'))
const ChangePasswordPage   = lazy(() => import('@/pages/profile/ChangePasswordPage'))
const SetNewPasswordPage   = lazy(() => import('@/pages/profile/SetNewPasswordPage'))
const ReferralPage         = lazy(() => import('@/pages/referrals/ReferralPage'))
const MorePage             = lazy(() => import('@/pages/more/MorePage'))
const CmsPage              = lazy(() => import('@/pages/static/CmsPage'))
const SurveyTakePage       = lazy(() => import('@/pages/surveys/SurveyTakePage'))
const ContestDetailPage    = lazy(() => import('@/pages/contests/ContestDetailPage'))

/** Wraps lazy-loaded content in a Suspense fallback + ErrorBoundary */
function Lazy({ element }: { element: React.ReactNode }) {
  return (
    <ErrorBoundary>
      <Suspense fallback={<PageLoader />}>{element}</Suspense>
    </ErrorBoundary>
  )
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
      { path: 'deals',                  element: <Lazy element={<DealsPage />} /> },
      { path: 'deals/:id/:slug?',       element: <Lazy element={<DealDetailPage />} /> },

      // Stores / Merchants
      { path: 'stores',                   element: <Lazy element={<StoresPage />} /> },
      { path: 'stores/:id/:slug?',         element: <Lazy element={<StoreDetailPage />} /> },
      { path: 'merchants',          element: <Lazy element={<MerchantListPage />} /> },
      { path: 'merchants/:id',      element: <Lazy element={<MerchantDetailPage />} /> },

      // Flash Deals
      { path: 'flash-deals',               element: <Lazy element={<FlashDealsPage />} /> },
      { path: 'flash-deals/:id/:slug?',    element: <Lazy element={<FlashDealDetailPage />} /> },

      // Categories
      { path: 'categories',       element: <Lazy element={<CategoriesPage />} /> },

      // Search
      { path: 'search',           element: <Lazy element={<SearchPage />} /> },

      // Blog
      { path: 'blog',             element: <Lazy element={<BlogListPage />} /> },
      { path: 'blog/:slug',       element: <Lazy element={<BlogDetailPage />} /> },

      // Static pages
      { path: 'about',            element: <Lazy element={<AboutPage />} /> },
      { path: 'contact',          element: <Lazy element={<ContactPage />} /> },
      { path: 'business-signup',  element: <Lazy element={<BusinessSignupPage />} /> },
      { path: 'page/:slug',       element: <Lazy element={<CmsPage />} /> },

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
  // PROTECTED ROUTES — requires login → AuthGuard → MandatoryPasswordResetGuard → AppShell
  // Personal / account-specific pages
  // ═══════════════════════════════════════════════════════════════════════════
  {
    element: <AuthGuard />,
    children: [
      {
        element: <MandatoryPasswordResetGuard />,
        children: [
        {
        element: <GuestShell />,
        children: [
          { path: 'dashboard', element: <Navigate to="/deals" replace /> },
          { path: 'explore',                  element: <Lazy element={<ExplorePage />} /> },
          { path: 'wallet',                   element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'wallet/gifts',             element: <Lazy element={<CouponWalletPage />} /> },
          { path: 'wallet/store',             element: <Lazy element={<StoreCouponPage />} /> },
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
          { path: 'profile/security',         element: <Lazy element={<ChangePasswordPage />} /> },
          { path: 'profile/set-password',      element: <Lazy element={<SetNewPasswordPage />} /> },
          { path: 'profile/*',                element: <Lazy element={<ProfilePage />} /> },
          { path: 'notifications',            element: <Lazy element={<NotificationsPage />} /> },
          { path: 'grievances',               element: <Lazy element={<GrievanceListPage />} /> },
          { path: 'grievances/new',           element: <Lazy element={<GrievanceFormPage />} /> },
          { path: 'grievances/:id',           element: <Lazy element={<GrievanceDetailPage />} /> },
          { path: 'referrals',                element: <Lazy element={<ReferralPage />} /> },
          { path: 'more',                    element: <Lazy element={<MorePage />} /> },
          { path: 'surveys/:id',             element: <Lazy element={<SurveyTakePage />} /> },
          { path: 'contests/:id',            element: <Lazy element={<ContestDetailPage />} /> },
        ],
        },         // AppShell
        ],
      },           // MandatoryPasswordResetGuard
    ],
  },               // AuthGuard

  // ── Fallback ─────────────────────────────────────────────────────────────
  { path: '*', element: <Navigate to="/" replace /> },
])
