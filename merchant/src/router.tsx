import { createBrowserRouter, Navigate } from 'react-router-dom'
import { lazy, Suspense } from 'react'
import AppShell from '@/components/layout/AppShell'
import AuthGuard from '@/components/layout/AuthGuard'
import StoreAdminGuard from '@/components/layout/StoreAdminGuard'
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
const ChangePasswordPage = lazy(() => import('@/pages/profile/ChangePasswordPage'))
const SubscriptionPage = lazy(() => import('@/pages/profile/SubscriptionPage'))
const MorePage         = lazy(() => import('@/pages/more/MorePage'))
const FlashDiscountPage = lazy(() => import('@/pages/more/FlashDiscountPage'))
const LabelsPage        = lazy(() => import('@/pages/more/LabelsPage'))
const CustomersPage     = lazy(() => import('@/pages/more/CustomersPage'))
const StoreCouponListPage   = lazy(() => import('@/pages/storeCoupons/StoreCouponListPage'))
const StoreCouponDetailPage = lazy(() => import('@/pages/storeCoupons/StoreCouponDetailPage'))
const StoreCouponFormPage   = lazy(() => import('@/pages/storeCoupons/StoreCouponFormPage'))
const StoreCouponAssignPage = lazy(() => import('@/pages/storeCoupons/StoreCouponAssignPage'))
const CustomerDetailPage    = lazy(() => import('@/pages/more/CustomerDetailPage'))
const StoreAdminListPage    = lazy(() => import('@/pages/storeAdmins/StoreAdminListPage'))
const StoreAdminFormPage    = lazy(() => import('@/pages/storeAdmins/StoreAdminFormPage'))

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
      { path: '/stores/new',                  element: wrap(<StoreAdminGuard><StoreFormPage /></StoreAdminGuard>) },
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
      { path: '/profile',                     element: wrap(<StoreAdminGuard fallback="/home"><ProfilePage /></StoreAdminGuard>) },
      { path: '/profile/edit',                element: wrap(<StoreAdminGuard fallback="/home"><EditProfilePage /></StoreAdminGuard>) },
      { path: '/profile/subscription',        element: wrap(<StoreAdminGuard fallback="/home"><SubscriptionPage /></StoreAdminGuard>) },
      { path: '/profile/security',            element: wrap(<ChangePasswordPage />) },

      // More
      { path: '/more',                        element: wrap(<MorePage />) },

      // Flash Discounts, Labels, Customers (Phase 7)
      { path: '/flash-discounts',             element: wrap(<FlashDiscountPage />) },
      { path: '/profile/labels',              element: wrap(<StoreAdminGuard fallback="/home"><LabelsPage /></StoreAdminGuard>) },
      { path: '/customers',                   element: wrap(<StoreAdminGuard fallback="/home"><CustomersPage /></StoreAdminGuard>) },
      { path: '/customers/:id',               element: wrap(<StoreAdminGuard fallback="/home"><CustomerDetailPage /></StoreAdminGuard>) },

      // Store Coupons
      { path: '/store-coupons',               element: wrap(<StoreCouponListPage />) },
      { path: '/store-coupons/new',            element: wrap(<StoreCouponFormPage />) },
      { path: '/store-coupons/:id',            element: wrap(<StoreCouponDetailPage />) },
      { path: '/store-coupons/:id/edit',       element: wrap(<StoreCouponFormPage />) },
      { path: '/store-coupons/:id/assign',     element: wrap(<StoreCouponAssignPage />) },

      // Store Admins (merchant-only)
      { path: '/store-admins',                 element: wrap(<StoreAdminGuard fallback="/more"><StoreAdminListPage /></StoreAdminGuard>) },
      { path: '/store-admins/new',             element: wrap(<StoreAdminGuard fallback="/more"><StoreAdminFormPage /></StoreAdminGuard>) },
      { path: '/store-admins/:id/edit',        element: wrap(<StoreAdminGuard fallback="/more"><StoreAdminFormPage /></StoreAdminGuard>) },

      // Legacy / convenience redirects
      { path: '/sales',                       element: <Navigate to="/analytics/sales" replace /> },
    ],
  },
])

export default router
