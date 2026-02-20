import { createBrowserRouter, Navigate } from 'react-router-dom'
import { lazy, Suspense } from 'react'
import AppShell from '@/components/layout/AppShell'
import AuthGuard from '@/components/layout/AuthGuard'
import PageLoader from '@/components/ui/PageLoader'

// Auth pages (not lazy — small and needed immediately)
import LoginPage         from '@/pages/auth/LoginPage'
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage'
import ResetPasswordPage  from '@/pages/auth/ResetPasswordPage'

// Lazy-load all app pages (improves initial bundle size)
const HomePage         = lazy(() => import('@/pages/home/HomePage'))
const CouponListPage   = lazy(() => import('@/pages/coupons/CouponListPage'))
const CouponDetailPage = lazy(() => import('@/pages/coupons/CouponDetailPage'))
const CouponFormPage   = lazy(() => import('@/pages/coupons/CouponFormPage'))
const ScanRedeemPage   = lazy(() => import('@/pages/scan/ScanRedeemPage'))
const StoreListPage    = lazy(() => import('@/pages/stores/StoreListPage'))
const StoreDetailPage  = lazy(() => import('@/pages/stores/StoreDetailPage'))
const StoreFormPage    = lazy(() => import('@/pages/stores/StoreFormPage'))
const GalleryPage      = lazy(() => import('@/pages/stores/GalleryPage'))
const AnalyticsPage    = lazy(() => import('@/pages/analytics/AnalyticsPage'))
const SalesPage        = lazy(() => import('@/pages/analytics/SalesPage'))
const GrievanceListPage   = lazy(() => import('@/pages/grievances/GrievanceListPage'))
const GrievanceDetailPage = lazy(() => import('@/pages/grievances/GrievanceDetailPage'))
const ReviewListPage   = lazy(() => import('@/pages/reviews/ReviewListPage'))
const ReviewDetailPage = lazy(() => import('@/pages/reviews/ReviewDetailPage'))
const MessageListPage  = lazy(() => import('@/pages/messages/MessageListPage'))
const MessageThreadPage = lazy(() => import('@/pages/messages/MessageThreadPage'))
const NotificationsPage = lazy(() => import('@/pages/notifications/NotificationsPage'))
const ProfilePage      = lazy(() => import('@/pages/profile/ProfilePage'))
const EditProfilePage  = lazy(() => import('@/pages/profile/EditProfilePage'))
const SubscriptionPage = lazy(() => import('@/pages/profile/SubscriptionPage'))
const MorePage         = lazy(() => import('@/pages/more/MorePage'))
const FlashDiscountPage = lazy(() => import('@/pages/more/FlashDiscountPage'))
const LabelsPage        = lazy(() => import('@/pages/more/LabelsPage'))
const CustomersPage     = lazy(() => import('@/pages/more/CustomersPage'))

const wrap = (el: JSX.Element) => <Suspense fallback={<PageLoader />}>{el}</Suspense>

const router = createBrowserRouter([
  // ── Public routes ─────────────────────────────────────────────────────────
  { path: '/login',          element: <LoginPage /> },
  { path: '/forgot-password', element: <ForgotPasswordPage /> },
  { path: '/reset-password',  element: <ResetPasswordPage /> },

  // ── Protected app routes (wrapped in AppShell + AuthGuard) ────────────────
  {
    element: <AuthGuard><AppShell /></AuthGuard>,
    children: [
      { index: true,                          element: wrap(<HomePage />) },
      { path: '/home',                        element: wrap(<HomePage />) },

      // Coupons
      { path: '/coupons',                     element: wrap(<CouponListPage />) },
      { path: '/coupons/new',                 element: wrap(<CouponFormPage />) },
      { path: '/coupons/:id',                 element: wrap(<CouponDetailPage />) },
      { path: '/coupons/:id/edit',            element: wrap(<CouponFormPage />) },

      // Scan
      { path: '/scan',                        element: wrap(<ScanRedeemPage />) },

      // Stores
      { path: '/stores',                      element: wrap(<StoreListPage />) },
      { path: '/stores/new',                  element: wrap(<StoreFormPage />) },
      { path: '/stores/:id',                  element: wrap(<StoreDetailPage />) },
      { path: '/stores/:id/edit',             element: wrap(<StoreFormPage />) },
      { path: '/stores/:id/gallery',          element: wrap(<GalleryPage />) },

      // Analytics
      { path: '/analytics',                   element: wrap(<AnalyticsPage />) },
      { path: '/analytics/sales',             element: wrap(<SalesPage />) },

      // Grievances
      { path: '/grievances',                  element: wrap(<GrievanceListPage />) },
      { path: '/grievances/:id',              element: wrap(<GrievanceDetailPage />) },

      // Reviews
      { path: '/reviews',                     element: wrap(<ReviewListPage />) },
      { path: '/reviews/:id',                 element: wrap(<ReviewDetailPage />) },

      // Messages
      { path: '/messages',                    element: wrap(<MessageListPage />) },
      { path: '/messages/new',                element: wrap(<MessageThreadPage />) },
      { path: '/messages/:id',                element: wrap(<MessageThreadPage />) },

      // Notifications
      { path: '/notifications',               element: wrap(<NotificationsPage />) },

      // Profile
      { path: '/profile',                     element: wrap(<ProfilePage />) },
      { path: '/profile/edit',                element: wrap(<EditProfilePage />) },
      { path: '/profile/subscription',        element: wrap(<SubscriptionPage />) },

      // More
      { path: '/more',                        element: wrap(<MorePage />) },

      // Flash Discounts, Labels, Customers (Phase 7)
      { path: '/flash-discounts',             element: wrap(<FlashDiscountPage />) },
      { path: '/profile/labels',              element: wrap(<LabelsPage />) },
      { path: '/customers',                   element: wrap(<CustomersPage />) },

      // Legacy / convenience redirects
      { path: '/sales',                       element: <Navigate to="/analytics/sales" replace /> },
    ],
  },
])

export default router
