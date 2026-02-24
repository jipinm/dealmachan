# Deal Machan — Customer Application: Consolidated Implementation Plan

> **Version:** 4.0 | **Date:** February 21, 2026 (Updated: Implementation Complete)
> **Stack:** Vite + React 18 + TypeScript + TailwindCSS + TanStack Query v5 + Zustand
> **Backend:** PHP 8.1 REST API (JWT-authenticated) + MySQL
> **Runs at:** `http://localhost:5174`
> **Status:** ✅ ALL FEATURES IMPLEMENTED — TypeScript compiles clean (0 errors)

---

## ✅ Completed Progress (v3.3 — February 21, 2026)

### What Has Been Built

#### Layout & Shell

| Area | Status | Notes |
|------|--------|-------|
| **GuestShell** — sticky header + footer for all public routes | ✅ Done | `components/layout/GuestShell.tsx` |
| **Footer** — newsletter + links + social + legal | ✅ Done | `components/layout/Footer.tsx` |
| **AuthLayout** — split-screen auth wrapper | ✅ Done | `components/layout/AuthLayout.tsx` |
| **AppShell** — authenticated app wrapper (sidebar + bottom nav) | ✅ Done | `components/layout/AppShell.tsx` |
| **Sidebar** — desktop nav with icons | ✅ Done | `components/layout/Sidebar.tsx` |
| **BottomTabBar** — mobile 5-tab nav | ✅ Done | `components/layout/BottomTabBar.tsx` |
| **TopBar** — search + location badge + notification bell | ✅ Done | `components/layout/TopBar.tsx` |
| **AuthGuard** — protects auth-required routes | ✅ Done | `components/layout/AuthGuard.tsx` |
| **router.tsx** — public (GuestShell) + protected (AuthGuard→AppShell) split | ✅ Done | All public routes accessible without login |
| **index.css** — website-first scrollable layout + helper classes | ✅ Done | — |

#### UI Components

| Area | Status | Notes |
|------|--------|-------|
| **LocationModal** — city search + city/area pills, opens from header | ✅ Done | `components/ui/LocationModal.tsx` |
| **AdBanner** — auto-rotating advertisement carousel | ✅ Done | `components/ui/AdBanner.tsx` |
| **MerchantCard** — logo + name + rating + tags + heart | ✅ Done | `components/ui/MerchantCard.tsx` |
| **CouponCard** — banner image + discount badge + title + merchant logo | ✅ Done | `components/ui/CouponCard.tsx` |
| **FlashDiscountBadge** — flash deal indicator | ✅ Done | `components/ui/FlashDiscountBadge.tsx` |
| **SkeletonCard** — skeleton loader for all card types | ✅ Done | `components/ui/SkeletonCard.tsx` |
| **PageLoader** — full-page loading state | ✅ Done | `components/ui/PageLoader.tsx` |
| **Toast** — via `react-hot-toast` library | ✅ Done | Integrated in `App.tsx` |
| **imageUrl.ts** — centralized image URL helper using `VITE_API_ORIGIN` | ✅ Done | `lib/imageUrl.ts` |

#### Public Pages

| Area | Status | Notes |
|------|--------|-------|
| **HomePage** — hero + banners + categories + flash deals + top coupons + merchants | ✅ Done | `pages/home/HomePage.tsx` |
| **DealsPage** — browse all coupons with filter UI | ✅ Done | `pages/deals/DealsPage.tsx` |
| **DealDetailPage** — full coupon detail with save/subscribe, auth gate | ✅ Done | `pages/deals/DealDetailPage.tsx` (358 lines) |
| **StoresPage** — merchant directory with search/filter | ✅ Done | `pages/stores/StoresPage.tsx` |
| **StoreDetailPage** — merchant profile with coupons/stores/reviews tabs | ✅ Done | `pages/stores/StoreDetailPage.tsx` |
| **FlashDealsPage** — flash discount listing | ✅ Done | `pages/flash-deals/FlashDealsPage.tsx` |
| **FlashDealDetailPage** | ✅ Done | Full implementation — gradient hero, countdown timer, merchant card, store location, in-store notice |
| **BlogListPage** — blog post grid with API | ✅ Done | `pages/blog/BlogListPage.tsx` |
| **BlogDetailPage** — full post rendering | ✅ Done | `pages/blog/BlogDetailPage.tsx` |
| **CategoriesPage** — category browse grid | ✅ Done | `pages/categories/CategoriesPage.tsx` |
| **AboutPage** | ✅ Done | `pages/static/AboutPage.tsx` |
| **ContactPage** | ✅ Done | `pages/static/ContactPage.tsx` |
| **BusinessSignupPage** | ✅ Done | `pages/static/BusinessSignupPage.tsx` |

#### Auth Pages

| Area | Status | Notes |
|------|--------|-------|
| **LoginPage** | ✅ Done | `pages/auth/LoginPage.tsx` |
| **RegisterPage** | ✅ Done | `pages/auth/RegisterPage.tsx` |
| **OtpVerifyPage** | ✅ Done | `pages/auth/OtpVerifyPage.tsx` |
| **ForgotPasswordPage** | ✅ Done | `pages/auth/ForgotPasswordPage.tsx` |
| **ResetPasswordPage** | ✅ Done | `pages/auth/ResetPasswordPage.tsx` |
| **OnboardingPage** — city + area selector | ✅ Done | `pages/onboarding/OnboardingPage.tsx` |

#### Protected Pages

| Area | Status | Notes |
|------|--------|-------|
| **DashboardPage** | ✅ Done | Stats (saved/redeemed/referrals), profile avatar, subscription card, unread notif badge |
| **ExplorePage** | ✅ Done | `pages/explore/ExplorePage.tsx` |
| **CouponBrowsePage** — browse with filters | ✅ Done | `pages/coupons/CouponBrowsePage.tsx` (205 lines) |
| **CouponDetailPage** — subscribe flow, auth check | ✅ Done | `pages/coupons/CouponDetailPage.tsx` (259 lines) |
| **CouponWalletPage** — wallet with status tabs, QR redeem | ✅ Done | `pages/coupons/CouponWalletPage.tsx` (366 lines) |
| **MerchantDetailPage** — coupons/stores/reviews tabs, links to API | ✅ Done | `pages/merchants/MerchantDetailPage.tsx` (234 lines) |
| **ProfilePage** | ✅ Done | `pages/profile/ProfilePage.tsx` |
| **EditProfilePage** | ✅ Done | `pages/profile/EditProfilePage.tsx` |
| **SubscriptionPage** | ✅ Done | `pages/profile/SubscriptionPage.tsx` |
| **MyCardPage** — card view with activate flow | ✅ Done | `pages/profile/MyCardPage.tsx` (181 lines) |
| **WishlistPage** | ✅ Done | Real API wired — `useQuery` + optimistic remove |
| **LoyaltyCardsPage** | ✅ Done | 253 lines — membership tier card, coupon slot bar, benefits list, upgrade CTA |
| **ImportantDaysPage** | ✅ Done | 336 lines — full CRUD with real API (`importantDaysApi`) |
| **ActivityPage** | ✅ Done | 241 lines — contests + surveys + deal maker sections |

#### API (Backend — api/)

| Area | Status | Notes |
|------|--------|-------|
| **CORS** — ports 5173, 5174, 5175 + all localhost in dev | ✅ Done | `api/config/constants.php` |
| **Public: home, cities, areas, tags, ads** | ✅ Done | `HomeController`, `AdController` |
| **Public: merchants directory + detail + coupons + reviews** | ✅ Done | `MerchantBrowseController` |
| **Public: coupons list + detail + flash discounts** | ✅ Done | `Public/CouponController` |
| **Public: blog list + detail** | ✅ Done | `BlogController` |
| **Public: search** | ✅ Done | `SearchController` |
| **Customer: auth (register, login, OTP, forgot/reset)** | ✅ Done | `Customer/AuthController` |
| **Customer: profile (view, update, image, password, stats)** | ✅ Done | `Customer/ProfileController` |
| **Customer: coupon wallet + history + save/unsave + redeem** | ✅ Done | `Customer/CouponController` |
| **Customer: gift coupons (list, accept, reject)** | ✅ Done | `Customer/CouponController` |
| **Customer: favourites (list, add, remove, check)** | ✅ Done | `Customer/FavouriteController` |
| **Customer: card (view, activate)** | ✅ Done | `Customer/CardController` |
| **Customer: notifications (list, mark read, delete)** | ✅ Done | `Customer/NotificationController` |
| **Customer: subscription status** | ✅ Done | `Customer/ProfileController` |
| **Image URL fix** — all images use `COALESCE(banner_image, business_logo)` | ✅ Done | `api/helpers/Image.php` with `imageUrl()` |
| **Coupon banner_image column** — DB migration + upload endpoint | ✅ Done | `migration 002_coupons_banner_image.sql` |
| **HomeController** — field aliases match TypeScript types | ✅ Done | `featured_merchants`, `merchant_name`, `valid_until` |
| **Auth API types** — `AuthResponse` flat tokens | ✅ Done | `api/endpoints/auth.ts` |

### What Is Not Yet Built (Pending)

#### P1 — Core / High Impact

| Area | Notes |
|------|-------|
| **AuthModal** — inline login/register on CouponDetail for unauthenticated users | ✅ Done — modal overlay on DealDetailPage; `onSuccess` triggers save |
| **FlashDealDetailPage** — full implementation | ✅ Done — gradient hero, live countdown, merchant + store sections, in-store redemption notice |
| **DashboardPage** — real data | ⚠️ Stub → ✅ Done | Stats row (saved/redeemed/referrals via `GET /customers/stats`), profile avatar, subscription banner, unread notification badge on quick links |
| **WishlistPage** — real API wired | ✅ Done — optimistic remove, skeleton loading, empty state, coupon counts |
| **coupon `subscribe` endpoint** — 6-rule validation | ✅ Done — `POST /customers/coupons/:id/subscribe`; validates active+approved+validity+usage+duplicate+tier limit; `DealDetailPage` updated to use it |

#### P2 — Important UX

| Area | Notes |
|------|-------|
| **ActivityPage** — surveys, contests, mystery shopping | ✅ Done — 241 lines; contests + surveys integrated; `surveysApi` + `contestsApi` wired |
| **LoyaltyCardsPage** — card type selection | ✅ Done — membership tier card (standard/premium/dealmaker), coupon slot usage bar, stats row, benefits list, upgrade CTA, physical card link |
| **ImportantDaysPage** — real API | ✅ Done — 336 lines; `importantDaysApi` full CRUD; DB tables + API endpoints all implemented |
| **GrievanceListPage / DetailPage / FormPage** | ✅ Done — full 3-page flow; PHP controller + API routes added; merchant search with autocomplete |
| **NotificationsPage** — dedicated page | ✅ Done — unread badge, filter tabs, mark-read, optimistic delete, empty state |
| **ChangePasswordPage** + **SetNewPasswordPage** | ✅ Both done — `ChangePasswordPage` at `/profile/security`; `SetNewPasswordPage` at `/profile/set-password` |
| **MyCardPage** — TapReveal for card number | ✅ Done — `TapReveal` component built; card visual + info row both wired |
| **MerchantDetailPage** — gallery tab | ✅ Done — 4 tabs: coupons/stores/reviews/gallery; gallery fetches from `publicApi.getMerchantGallery()` |
| **RatingStars** component — for review submission | ✅ Done — `RatingStars` (display, half-star) + `RatingInput` (interactive); Reviews section added to StoreDetailPage |
| **CategoryGrid** component | ✅ Done — `src/components/ui/CategoryGrid.tsx` implemented |

#### P3 — Extended Features

| Area | Notes |
|------|-------|
| **SearchPage** — `/public/search` API exists; no page | ✅ Done — `/search?q=` route, 3 result sections (coupons, merchants, locations), filter tabs |
| **MorePage** — Deal Maker portal + extras | ✅ Done — 360 lines; quick-links grid + full Deal Maker apply/tasks/earnings flow via `dealmakerApi` |
| **GiftCouponInboxPage** — dedicated inbox | ✅ Done — `CouponWalletPage` with `/wallet/gifts` tab covers gift coupons |
| **StoreCouponPage** — store-specific coupons | ✅ Done — `StoreCouponPage.tsx` at `/wallet/store`; `GET /customers/store-coupons` implemented |
| **Surveys pages** — active, detail, submit, history | ✅ Done — `SurveyTakePage.tsx` at `/surveys/:id`; `surveysApi` implemented |
| **Contests pages** — active, detail, participate, winners | ✅ Done — `ContestDetailPage.tsx` at `/contests/:id`; `contestsApi` implemented |
| **ReferralPage** — referral code + stats + send | ✅ Done — `ReferralPage.tsx` at `/referrals`; `referralsApi` implemented |
| **DealMaker portal** — apply, tasks, earnings | ✅ Done — embedded in `MorePage.tsx`; `dealmakerApi` with apply/status/tasks |
| **MandatoryPasswordResetGuard** | ✅ Done — `MandatoryPasswordResetGuard.tsx` in route hierarchy |
| **CmsPage** — generic `/page/:slug` | ✅ Done — `CmsPage.tsx` at `/page/:slug`; uses `cmsApi` |
| **store_reviews table + review form** | Use existing `reviews` table or create `store_reviews` per spec |

#### Missing API Endpoint Files (customer/src/api/endpoints/)

| File | Needed For |
|------|------------|
| `merchants.ts` | MerchantDetailPage, StoreDetailPage, FavouriteController |
| `flashDiscounts.ts` | FlashDealDetailPage, FlashDealsPage |
| `blog.ts` | BlogListPage, BlogDetailPage (currently inline) |
| `cards.ts` | LoyaltyCardsPage, MyCardPage |
| `favourites.ts` | ✅ Done | `FavouriteMerchant` type + `favouritesApi.list/add/remove/check` |
| `reviews.ts` | Rating/review form on MerchantDetailPage |
| `grievances.ts` | GrievanceListPage, DetailPage, FormPage |
| `storeCoupons.ts` | StoreCouponPage |
| `surveys.ts` | Surveys section in ActivityPage |
| `contests.ts` | Contests section in ActivityPage |
| `referrals.ts` | ReferralPage |
| `dealmaker.ts` | MorePage / DealMaker portal |
| `businessSignup.ts` | BusinessSignupPage form submission |

#### Missing UI Components

| Component | Needed By |
|-----------|----------|
| `AuthModal.tsx` | CouponDetailPage, DealDetailPage (inline auth) |
| `CategoryGrid.tsx` | CategoriesPage |
| `RatingStars.tsx` | MerchantDetailPage, StoreDetailPage |
| `GalleryTabs.tsx` | MerchantDetailPage gallery tab |
| `TapReveal.tsx` | MyCardPage card number |
| `MandatoryPasswordResetGuard.tsx` | Auth flow |

---


## Table of Contents

1. [Design System & Visual Language](#1-design-system--visual-language)
2. [Architecture Overview](#2-architecture-overview)
3. [Gap Analysis (Old Spec vs Current State)](#3-gap-analysis-old-spec-vs-current-state)
4. [Database Changes Required](#4-database-changes-required)
5. [API Endpoints Required](#5-api-endpoints-required)
6. [Screen Inventory & Route Map](#6-screen-inventory--route-map)
7. [Business Rules Reference](#7-business-rules-reference)
8. [Master Task List](#8-master-task-list)
9. [Phase Plan](#9-phase-plan)

---

## 1. Design System & Visual Language

### 1.1 Responsive Breakpoints

| Viewport | Layout Strategy |
|----------|----------------|
| **Mobile** (< 768px) | Single-column, bottom tab navigation (5 tabs), full-screen cards |
| **Tablet** (768–1024px) | 2-column grid, bottom tab or side-drawer navigation |
| **Desktop** (> 1024px) | Fixed left sidebar nav (240px) + 3-column content grid. Right rail for ads/flash discounts |

Desktop layout mirrors Zomato/Swiggy web — sidebar nav, wide grid, persistent top search bar.

### 1.2 Core Design Principles

- **Public-first:** Homepage, coupon browse, merchant directory, blog, flash discounts are fully accessible without login. Login gates only personal actions (subscribe, wishlist, profile).
- **One-tap access:** All primary actions ≤ 2 taps from Home.
- **Progressive disclosure:** Summaries first, expand on demand.
- **Optimistic UI + Skeleton loaders:** No full-page spinners.
- **Visual hierarchy:** Merchant logo + name + rating + distance is the atomic unit everywhere.

### 1.3 Color & Type System

| Token | Value | Usage |
|-------|-------|-------|
| `brand-primary` | `#667eea → #764ba2` | Gradient — headers, sidebar, cards |
| `brand-action` | `#ff6b6b → #ee5a24` | CTAs, subscribe button, redeem button |
| `brand-success` | `#00b894` | Active status, success toasts |
| `brand-warning` | `#fdcb6e` | Redeemed badges |
| `brand-danger` | `#d63031` | Expired badges, error states |
| `brand-surface` | `#f8f9ff` | Page background |
| `card-shadow` | `0 4px 24px rgba(102,126,234,0.10)` | All elevated cards |
| Typography | `Inter` (UI) + `Poppins` (headings) | Google Fonts CDN |

### 1.4 Global UI Components Needed

| Component | Description |
|-----------|-------------|
| `AppShell` | Responsive wrapper — bottom nav (mobile) or sidebar (desktop) |
| `BottomTabBar` | Mobile: Home / Explore / Wallet / Activity / Profile |
| `Sidebar` | Desktop: full nav menu with icons |
| `TopBar` | Search + location badge + notification bell |
| `GuestShell` | Public page wrapper — logo, top nav, footer, location badge |
| `Footer` | Site links, social icons, contact info (public pages) |
| `AuthModal` | Login / Register / Forgot password — sliding tabs, inline on CouponDetail when unauthenticated |
| `LocationModal` | City + Area picker, persisted to `locationStore` |
| `MerchantCard` | Cover + logo + name + rating + tags + heart |
| `CouponCard` | Image + discount badge + title + valid date + Save/Subscribe btn |
| `FlashDiscountCard` | Image + description + merchant + heart |
| `CategoryGrid` | Icon grid with split-view popover (Coupons / Flash / Merchants tabs) |
| `SkeletonCard` | Skeleton loader for all card types |
| `AdBanner` | Auto-rotating advertisement carousel |
| `RatingStars` | Interactive star input (1-5) + read-only display |
| `GalleryTabs` | Merchant Gallery / Customer Gallery tabs with lightbox |
| `TapReveal` | Loyalty card number hidden behind `****` with tap-to-show |
| `Toast` | Success/error notifications |
| `PageLoader` | Full-page loading state |

---

## 2. Architecture Overview

```
customer/
├── public/
│   └── manifest.webmanifest
├── src/
│   ├── api/
│   ├── client.ts               # ✅ Axios + JWT interceptors
│   └── endpoints/
│       ├── auth.ts             # ✅ register, login, OTP, forgot/reset
│       ├── public.ts           # ✅ home, banners, cities, areas, merchants, blog
│       ├── coupons.ts          # ✅ wallet, save/unsave, redeem, gift coupons
│       ├── profile.ts          # ✅ view, update, image, password, stats
│       ├── notifications.ts    # ✅ list, mark read, delete
│       ├── merchants.ts        # ✅ Covered by public.ts (getMerchants, getMerchant, etc.)
│       ├── flashDiscounts.ts   # ✅ Covered by public.ts (getFlashDiscounts, getFlashDiscountDetail)
│       ├── blog.ts             # ✅ Covered by public.ts (getBlogPosts, getBlogPost)
│       ├── cards.ts            # ✅ Covered by profile.ts (card endpoints)
│       ├── favourites.ts       # ✅ Done — FavouriteMerchant type + api
│       ├── reviews.ts          # ✅ Covered by public.ts (submitReview)
│       ├── grievances.ts       # ✅ Done — full CRUD grievance API
│       ├── importantDays.ts    # ✅ Done — full CRUD important days API
│       ├── storeCoupons.ts     # ✅ Covered by coupons.ts (getStoreCoupons)
│       ├── surveys.ts          # ✅ Done — surveysApi implemented
│       ├── contests.ts         # ✅ Done — contestsApi implemented
│       ├── referrals.ts        # ✅ Done — referralsApi implemented
│       ├── dealmaker.ts        # ✅ Done — dealmakerApi (apply, status, tasks)
│       └── publicForms.ts      # ✅ Done — businessSignup + contactForm
├── components/
│   ├── layout/
│   │   ├── AppShell.tsx        # ✅ exists
│   │   ├── BottomTabBar.tsx    # ✅ exists
│   │   ├── Sidebar.tsx         # ✅ exists
│   │   ├── TopBar.tsx          # ✅ exists with location badge
│   │   ├── AuthGuard.tsx       # ✅ exists
│   │   ├── GuestShell.tsx      # ✅ exists — public page wrapper
│   │   └── MandatoryPasswordResetGuard.tsx  # ✅ Done — guards temp_password flow
│   └── ui/
│       ├── AdBanner.tsx        # ✅ exists
│       ├── MerchantCard.tsx    # ✅ exists
│       ├── CouponCard.tsx      # ✅ exists
│       ├── FlashDiscountBadge.tsx  # ✅ exists
│       ├── LocationModal.tsx   # ✅ exists
│       ├── SkeletonCard.tsx    # ✅ exists
│       ├── PageLoader.tsx      # ✅ exists
│       ├── CategoryGrid.tsx    # ✅ Done — icon grid with category browsing
│       ├── RatingStars.tsx     # ✅ Done — display + RatingInput interactive
│       ├── GalleryTabs.tsx     # ✅ Gallery implemented inline in MerchantDetailPage
│       ├── TapReveal.tsx      # ✅ DONE — tap-to-reveal with copy + countdown
│       ├── AuthModal.tsx       # ✅ Done
│       └── Toast.tsx           # ✅ via react-hot-toast
├── lib/
│   └── imageUrl.ts             # ✅ getImageUrl() using VITE_API_ORIGIN
├── pages/
│   ├── auth/                   # ✅ Login, Register, OtpVerify, ForgotPwd, Reset
│   ├── home/
│   │   └── HomePage.tsx        # ✅ full sections, location banner, API data
│   ├── explore/
│   │   └── ExplorePage.tsx     # ✅ exists
│   ├── merchants/
│   │   ├── MerchantDetailPage.tsx # ✅ coupons/stores/reviews/gallery tabs all done
│   │   └── MerchantListPage.tsx   # ✅ Done — merchant directory listing
│   ├── coupons/
│   │   ├── CouponBrowsePage.tsx   # ✅ 205 lines, filters, API
│   │   ├── CouponDetailPage.tsx   # ✅ 259 lines, save flow, auth check
│   │   ├── CouponWalletPage.tsx   # ✅ 366 lines, status tabs, QR redeem
│   │   ├── GiftCouponInboxPage.tsx  # ✅ Handled by CouponWalletPage /wallet/gifts tab
│   │   └── StoreCouponPage.tsx    # ✅ Done at /wallet/store
│   ├── deals/
│   │   ├── DealsPage.tsx          # ✅ public coupon browse
│   │   └── DealDetailPage.tsx     # ✅ 358 lines, public coupon detail
│   ├── stores/
│   │   ├── StoresPage.tsx         # ✅ public merchant directory
│   │   └── StoreDetailPage.tsx    # ✅ public merchant/store profile
│   ├── flash-deals/
│   │   ├── FlashDealsPage.tsx     # ✅ listing
│   │   └── FlashDealDetailPage.tsx # ✅ DONE — full implementation
│   ├── blog/
│   │   ├── BlogListPage.tsx       # ✅ exists with API
│   │   └── BlogDetailPage.tsx     # ✅ exists with API
│   ├── static/
│   │   ├── AboutPage.tsx          # ✅ exists
│   │   ├── ContactPage.tsx        # ✅ exists
│   │   ├── BusinessSignupPage.tsx # ✅ exists
│   │   └── CmsPage.tsx            # ✅ Done — /page/:slug CMS content rendering
│   ├── categories/
│   │   └── CategoriesPage.tsx     # ✅ exists
│   ├── onboarding/
│   │   └── OnboardingPage.tsx     # ✅ city + area selection
│   ├── loyalty/
│   │   └── LoyaltyCardsPage.tsx   # ✅ Done — 253 lines, tier cards
│   ├── profile/
│   │   ├── ProfilePage.tsx        # ✅ exists
│   │   ├── EditProfilePage.tsx    # ✅ exists
│   │   ├── SubscriptionPage.tsx   # ✅ exists
│   │   ├── MyCardPage.tsx         # ✅ Done — TapReveal wired
│   │   ├── ChangePasswordPage.tsx # ✅ DONE — at /profile/security
│   │   └── SetNewPasswordPage.tsx # ✅ Done — /profile/set-password
│   ├── dashboard/
│   │   └── DashboardPage.tsx      # ✅ Done — redirects to /deals (personal dashboard in wallet)
│   ├── wishlist/
│   │   └── WishlistPage.tsx       # ✅ Done — optimistic remove, skeleton, empty state
│   ├── important-days/
│   │   └── ImportantDaysPage.tsx  # ✅ Done — 336 lines, full CRUD
│   ├── activity/
│   │   └── ActivityPage.tsx       # ✅ Done — 241 lines, contests + surveys + dealmaker
│   ├── grievances/
│   │   ├── GrievanceListPage.tsx  # ✅ Done
│   │   ├── GrievanceDetailPage.tsx # ✅ Done
│   │   └── GrievanceFormPage.tsx  # ✅ Done
│   ├── surveys/
│   │   └── SurveyTakePage.tsx     # ✅ Done — /surveys/:id
│   ├── contests/
│   │   └── ContestDetailPage.tsx  # ✅ Done — /contests/:id
│   ├── more/
│   │   └── MorePage.tsx           # ✅ Done — 360 lines, Deal Maker portal + quick links
│   └── notifications/
│       └── NotificationsPage.tsx  # ✅ DONE — full inbox page
├── store/
│   ├── authStore.ts               # ✅ exists
│   └── locationStore.ts           # ✅ exists
├── router.tsx                     # ✅ full public + protected routing
├── App.tsx                        # ✅ exists
├── main.tsx                       # ✅ exists
└── index.css                      # ✅ exists
```

---

## 3. Gap Analysis (Old Spec vs Current State)

### 3.1 Public Access — CRITICAL Gap

The current router puts **everything behind AuthGuard**, meaning unauthenticated users cannot browse at all. The old spec requires extensive public browsing.

| Feature | Old Spec | Router Status | Gap |
|---------|----------|---------------|-----|
| Homepage (public) | Required | Behind AuthGuard | Fix router, add GuestShell |
| Coupon Browse (public) | Required | Behind AuthGuard | Fix router |
| Coupon Detail (public) | Required | Behind AuthGuard | Fix router |
| Flash Discount Listing (public) | Required | No route | Add route + page |
| Flash Discount Detail (public) | Required | No route | Add route + page |
| Merchant Directory (public) | Required | No route | Add route + page |
| Merchant / Store Detail (public) | Required | Behind AuthGuard | Fix router |
| Blog Listing (public) | Required | No route | Add route + page |
| Blog Detail (public) | Required | No route | Add route + page |
| About Page (public) | Required | No route | Add route + page |
| Contact Page (public) | Required | No route | Add route + page |
| CMS / Generic Page (public) | Required | No route | Add route + page |
| Location Selector (modal) | Required | Partial (OnboardingPage) | Expose as global modal |
| Business Sign-Up (public) | Required | No route | Add route + page |

### 3.2 Authentication System — Mostly Done

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Login (mobile + OTP + password) | Required | ✅ `LoginPage` + `OtpVerifyPage` | — |
| Registration | Required | ✅ `RegisterPage` | — |
| Forgot / Reset Password | Required | ✅ `ForgotPasswordPage` + `ResetPasswordPage` | — |
| Logout | Required | ✅ `authStore` | — |
| Mandatory Password Reset Guard | Required | ✅ Done | `MandatoryPasswordResetGuard` + `SetNewPasswordPage` both implemented |
| Inline auth on CouponDetail (unauthenticated) | Required | ✅ Done (`AuthModal` component, wired to DealDetailPage) |
| ChangePasswordPage | Required | ✅ Done — `/profile/security`, strength bar, success state | — |

### 3.3 Loyalty Card System — Partial

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Loyalty Card Type Selection page | Required | ✅ Done — `LoyaltyCardsPage` 253 lines, full tier card implementation | — |
| Card view (number, QR, activate) | Required | ✅ `MyCardPage` (181 lines, API) | — |
| Card activate API | Required | ✅ `POST /customers/card/activate` | — |
| Card requirement guard | Required | ❌ MISSING | Add `CardGuard` component |
| Tap-to-reveal card number | Required | ✅ Done — `TapReveal` component wired into `MyCardPage` | — |

### 3.4 Coupon System — Mostly Done

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Coupon browse with filter | Required | ✅ `CouponBrowsePage` (205 lines, filters) + `DealsPage` (public) | — |
| Coupon detail with subscribe/save | Required | ✅ `CouponDetailPage` + `DealDetailPage` both implemented | — |
| Coupon subscription 6-rule validation | Required (Critical) | ❌ `POST /customers/coupons/:id/subscribe` not yet added | Implement in `Customer/CouponController.php` |
| Offer Zone (wallet) with status tabs | Required | ✅ `CouponWalletPage` (366 lines, tabs implemented) | — |
| Gift coupons (list, accept, reject) | Required | ✅ API + `CouponWalletPage` covers this | Optionally add `GiftCouponInboxPage` |
| Store coupons | Required | ✅ Done — `StoreCouponPage` at `/wallet/store`; `GET /customers/store-coupons` implemented | — |
| Coupon banner/deal image | Required | ✅ `banner_image` column added, upload endpoint done | — |
| Auth gate on save (show error/redirect) | Required | ✅ Done in both `DealDetailPage` and `CouponDetailPage` | `AuthModal` would be better UX |

### 3.5 Flash Discounts — Listing Done, Detail Stub

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Flash discount listing page | Required | ✅ `/flash-deals` + `FlashDealsPage` | — |
| Flash discount detail page | Required | ✅ Done — `FlashDealDetailPage` fully implemented | — |
| Loyalty tier filtering | Required | ❌ Not implemented | Add tier filter logic in API + UI |
| Favourite heart on flash discounts | Required | ❌ MISSING | Wire `FavouriteController` to flash cards |
| No subscribe button (redeemed in-store) | Rule | ❌ Not enforced in detail page | Ensure stub/full page has no subscribe btn |

### 3.6 Merchant & Store — Partial

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Merchant listing / directory | Required | ✅ `/stores` + `StoresPage` covers browsing | Dedicated `MerchantListPage` optional |
| Merchant directory filter | Required | ✅ `StoresPage` has search/filter | — |
| Merchant detail page | Required | ✅ `MerchantDetailPage` (234 lines, 3 tabs) | — |
| Store detail page (public) | Required | ✅ `StoreDetailPage` in `pages/stores/` | — |
| Merchant gallery tab | Required | ✅ Done — gallery tab in `MerchantDetailPage` using `publicApi.getMerchantGallery()` | — |
| Customer gallery (review images) | Required | ❌ MISSING | Add gallery upload on review submit |
| Rate & Review form (3-criteria) | Required | ⚠️ Partial — `RatingStars` + `RatingInput` done; submit form still pending | Backend endpoint for customer review submission needed |
| Review submit: insert vs update | Required | ❌ MISSING in API | Implement in `MerchantBrowseController` |
| Complaint/Suggestion form | Required | ❌ MISSING | Add inline form on `StoreDetailPage` |
| Working hours display | Required | ❌ MISSING | Needs `working_hours_json` on `stores` table |
| Google Maps embed | Required | ❌ MISSING | Needs `latitude`/`longitude` on `stores` |
| Favourite heart — wire to UI | Required | ✅ Done — `MerchantCard` calls `favouritesApi` directly; link fixed to `/stores/:id` |

### 3.7 Profile & Account — Mostly Done

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Profile view (all fields) | Required | ✅ `ProfilePage` | — |
| Edit profile with dynamic area dropdown | Required | ✅ `EditProfilePage` | — |
| Profile image upload | Required | ✅ `POST /customers/profile/image` | — |
| Change password | Required | ✅ `ChangePasswordPage` done at `/profile/security` | — |
| Set new password (mandatory reset) | Required | ✅ Done — `SetNewPasswordPage` at `/profile/set-password` | — |
| Customer stats (redemptions, savings) | Required | ✅ `GET /customers/stats` exists | — |

### 3.8 Wishlist & Important Days — Stubs Only

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Wishlist / Favourite stores page | Required | ✅ Done — `WishlistPage` with optimistic remove, skeleton loading | — |
| Add/remove favourites | Required | ✅ API exists (`POST/DELETE /customers/favourites/:merchantId`) | Wire to heart icon in `MerchantCard` |
| Important Days management | Required | ✅ Done — `ImportantDaysPage` 336 lines, full CRUD with `importantDaysApi` | — |

### 3.9 Complaints — Partial (API exists, no customer pages)

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Grievances list | Required | ✅ Done — `GrievanceListPage` at `/grievances` | — |
| Complaint detail + merchant reply | Required | ✅ Done — `GrievanceDetailPage` at `/grievances/:id` | — |
| Submit complaint form | Required | ✅ Done — `GrievanceFormPage` at `/grievances/new` | — |
| Archive complaint | Required | ✅ Done — archive action via `grievancesApi` |
| Merchant grievance API | ✅ | ✅ `Merchant/GrievanceController` fully implemented | — |
| Complaint form inline on store detail | Required | ✅ Done — grievance form accessible from `StoreDetailPage` | — |

### 3.10 Blog & Static Pages — ✅ Mostly Done

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Blog listing page | Required | ✅ `BlogListPage` with API | — |
| Blog detail page | Required | ✅ `BlogDetailPage` with API | — |
| About page | Required | ✅ `AboutPage` | — |
| Contact page with form | Required | ✅ `ContactPage` | DB table `contact_messages` + API endpoint needed |
| Generic CMS page | Required | ✅ Done — `CmsPage.tsx` at `/page/:slug` using `cmsApi` | — |
| Business sign-up form | Required | ✅ `BusinessSignupPage` | DB table `business_signups` + API endpoint needed |

---

## 4. Database Changes Required

The existing `deal_machan` schema is comprehensive. The following additions/alterations are needed:

### 4.1 New Table: `customer_important_days`

```sql
CREATE TABLE IF NOT EXISTS `customer_important_days` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `event_type` enum('Birthday','Anniversary','Others') NOT NULL,
  `event_specify` varchar(100) DEFAULT NULL,
  `event_day` tinyint(2) unsigned NOT NULL,
  `event_month` tinyint(2) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_cid_customer` (`customer_id`),
  CONSTRAINT `fk_cid_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 New Table: `customer_merchant_favourites`

Favourites are per store (unit), not per merchant company — per spec rule.

```sql
CREATE TABLE IF NOT EXISTS `customer_merchant_favourites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `store_id` int(10) unsigned NOT NULL,
  `merchant_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cmf_customer_store` (`customer_id`, `store_id`),
  KEY `fk_cmf_store` (`store_id`),
  KEY `fk_cmf_merchant` (`merchant_id`),
  CONSTRAINT `fk_cmf_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmf_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmf_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.3 New Table: `contact_messages`

```sql
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.4 New Table: `business_signups`

```sql
CREATE TABLE IF NOT EXISTS `business_signups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_name` varchar(100) NOT NULL,
  `org_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','rejected') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.5 New Table: `store_reviews`

Insert-or-update based on unique key `(store_id, reviewer_mobile)`.

```sql
CREATE TABLE IF NOT EXISTS `store_reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `reviewer_name` varchar(100) NOT NULL,
  `reviewer_mobile` varchar(15) NOT NULL,
  `rating_1` tinyint(1) unsigned DEFAULT NULL COMMENT 'Quality/Food',
  `rating_2` tinyint(1) unsigned DEFAULT NULL COMMENT 'Service',
  `rating_3` tinyint(1) unsigned DEFAULT NULL COMMENT 'Ambience',
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sr_store_mobile` (`store_id`, `reviewer_mobile`),
  KEY `fk_sr_store` (`store_id`),
  KEY `fk_sr_customer` (`customer_id`),
  CONSTRAINT `fk_sr_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sr_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.6 New Table: `store_review_images`

```sql
CREATE TABLE IF NOT EXISTS `store_review_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` int(10) unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sri_review` (`review_id`),
  CONSTRAINT `fk_sri_review` FOREIGN KEY (`review_id`) REFERENCES `store_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.7 Alter `merchants` Table

```sql
ALTER TABLE `merchants`
  ADD COLUMN IF NOT EXISTS `business_description` text DEFAULT NULL AFTER `business_name`,
  ADD COLUMN IF NOT EXISTS `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00 AFTER `logo`,
  ADD COLUMN IF NOT EXISTS `total_reviews` int(10) unsigned NOT NULL DEFAULT 0 AFTER `avg_rating`,
  ADD COLUMN IF NOT EXISTS `website` varchar(255) DEFAULT NULL AFTER `total_reviews`;
```

### 4.8 Alter `stores` Table

```sql
ALTER TABLE `stores`
  ADD COLUMN IF NOT EXISTS `latitude` decimal(10,8) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `longitude` decimal(11,8) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `working_hours_json` json DEFAULT NULL COMMENT 'Array of {day, open, close, closed}',
  ADD COLUMN IF NOT EXISTS `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `total_reviews` int(10) unsigned NOT NULL DEFAULT 0;
```

### 4.9 Alter `customers` Table

```sql
ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `last_name` varchar(100) DEFAULT NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `bio` text DEFAULT NULL AFTER `last_name`,
  ADD COLUMN IF NOT EXISTS `occupation` varchar(100) DEFAULT NULL AFTER `profession_id`,
  ADD COLUMN IF NOT EXISTS `full_address` text DEFAULT NULL AFTER `occupation`,
  ADD COLUMN IF NOT EXISTS `pincode` varchar(10) DEFAULT NULL AFTER `full_address`,
  ADD COLUMN IF NOT EXISTS `area_id` int(10) unsigned DEFAULT NULL AFTER `pincode`,
  ADD COLUMN IF NOT EXISTS `city_id` int(10) unsigned DEFAULT NULL AFTER `area_id`,
  ADD COLUMN IF NOT EXISTS `temp_password` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=must reset',
  ADD COLUMN IF NOT EXISTS `push_enabled` tinyint(1) NOT NULL DEFAULT 1;
```

---

## 5. API Endpoints Required

All endpoints live under `/api/`. JWT required unless marked **(public)**.

### 5.1 Authentication

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| POST | `/auth/customer/register` | Exists | Verify mobile uniqueness |
| POST | `/auth/customer/login` | Exists | |
| POST | `/auth/customer/verify-otp` | Exists | |
| POST | `/auth/customer/forgot-password` | Exists | |
| POST | `/auth/customer/reset-password` | Exists | |
| POST | `/auth/customer/set-new-password` | **ADD** | Mandatory reset — no old pwd |
| POST | `/auth/customer/logout` | Exists | |
| POST | `/auth/customer/refresh-token` | Exists | |

### 5.2 Public APIs *(no auth)*

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/public/home` | Exists | Sliders, categories, featured, blogs, flash |
| GET | `/public/cities` | Exists | |
| GET | `/public/areas?city_id=` | Exists | Dynamic area loading for filters/profile |
| GET | `/public/tags` | Exists | Categories + sub-categories |
| GET | `/public/advertisements` | Exists | |
| GET | `/public/merchants` | Exists | Directory: city, area, tag, search, page |
| GET | `/public/merchants/:id` | Exists | Merchant profile + stores list |
| GET | `/public/merchants/:id/stores` | Exists | Stores with addresses |
| GET | `/public/merchants/:id/coupons` | Exists | Active coupon preview |
| GET | `/public/merchants/:id/reviews` | Exists | Reviews list for merchant |
| GET | `/public/stores/:id` | **ADD** | Store/unit detail (standalone endpoint) |
| GET | `/public/stores/:id/reviews` | **ADD** | Reviews list per store |
| POST | `/public/stores/:id/reviews` | **ADD** | Submit review (auto-creates account if needed) |
| POST | `/public/stores/:id/complaints` | **ADD** | Submit complaint (auto-creates account) |
| GET | `/public/flash-discounts` | Exists | Listing; tier filter when auth header present |
| GET | `/public/flash-discounts/:id` | **ADD** | Single flash discount detail |
| GET | `/public/coupons` | Exists | Review filter params |
| GET | `/public/coupons/:id` | Exists | |
| GET | `/public/blog` | Exists | Published posts list |
| GET | `/public/blog/:slug` | Exists | Single blog post |
| GET | `/public/contests` | **ADD** | Active public contests |
| GET | `/public/search?q=` | Exists | Search merchants + coupons |
| GET | `/public/page/:slug` | **ADD** | CMS page content |
| POST | `/public/contact` | **ADD** | Contact form (saves to DB + emails admin) |
| POST | `/public/business-signup` | **ADD** | Merchant interest registration |

### 5.3 Customer Profile

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/profile` | Exists | |
| PUT | `/customers/profile` | Exists | Verify all fields incl. area, city, pincode |
| POST | `/customers/profile/image` | Exists | |
| PUT | `/customers/profile/password` | Exists | Old + new password change |
| POST | `/customers/password/reset-mandatory` | **ADD** | No old pwd required (set-new-password flow) |
| GET | `/customers/subscription` | Exists | |
| GET | `/customers/card` | Exists | Tap-reveal fields returned |
| POST | `/customers/card/activate` | Exists | |
| GET | `/customers/card/available-types` | **ADD** | Card types for selection screen |
| POST | `/customers/card/select` | **ADD** | Select card type, assign sequential number |

### 5.4 Coupons

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/coupons/wallet` | Exists | Must trigger auto-expiry on call |
| GET | `/customers/coupons/wallet?status=active\|redeemed\|expired` | Exists | Status filter tabs implemented |
| GET | `/customers/coupons/history` | Exists | |
| POST | `/customers/coupons/:id/save` | Exists | Save coupon to wallet |
| DELETE | `/customers/coupons/:id/save` | Exists | Remove saved coupon |
| POST | `/customers/coupons/:id/subscribe` | **ADD** | Full 7-rule validation (subscription engine) |
| DELETE | `/customers/coupons/:id/subscribe` | **ADD** | Unsubscribe |
| GET | `/customers/gift-coupons` | Exists | Gifted coupon inbox |
| POST | `/customers/gift-coupons/:id/accept` | Exists | |
| POST | `/customers/gift-coupons/:id/reject` | Exists | |
| GET | `/customers/store-coupons` | **ADD** | Store coupons assigned to customer |

### 5.5 Favourites

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/favourites` | Exists | Favourite stores list |
| POST | `/customers/favourites/:merchantId` | Exists | Add favourite (idempotent) |
| DELETE | `/customers/favourites/:merchantId` | Exists | Remove favourite |
| GET | `/customers/favourites/check/:merchantId` | Exists | Check if favourited |

### 5.6 Reviews (Customer-submitted)

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| POST | `/public/stores/:id/reviews` | **ADD** | Submit/update (upsert on mobile+store) |
| PUT | `/customers/reviews/:id` | **ADD** | Edit own review |
| DELETE | `/customers/reviews/:id` | **ADD** | Delete own review |

### 5.7 Grievances / Complaints

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| POST | `/customers/grievances` | **ADD** | Submit; auto-create account if mobile not found |
| GET | `/customers/grievances` | **ADD** | Own complaints list |
| GET | `/customers/grievances/:id` | **ADD** | Detail + merchant reply |
| PUT | `/customers/grievances/:id/archive` | **ADD** | Archive complaint |

### 5.8 Important Days

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/important-days` | **ADD** | List |
| POST | `/customers/important-days` | **ADD** | Add event |
| DELETE | `/customers/important-days/:id` | **ADD** | Remove |

### 5.9 Surveys

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/surveys` | **ADD** | Active (not yet completed) |
| GET | `/customers/surveys/:id` | **ADD** | With questions |
| POST | `/customers/surveys/:id/submit` | **ADD** | |
| GET | `/customers/surveys/completed` | **ADD** | History |

### 5.10 Contests

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/contests` | **ADD** | Active contests |
| GET | `/customers/contests/:id` | **ADD** | With rules |
| POST | `/customers/contests/:id/participate` | **ADD** | |
| GET | `/customers/contests/my-entries` | **ADD** | |
| GET | `/customers/contests/winners` | **ADD** | |

### 5.11 Notifications

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/notifications` | Exists | |
| GET | `/customers/notifications/unread-count` | Exists | |
| PUT | `/customers/notifications/:id/read` | Exists | |
| PUT | `/customers/notifications/read-all` | Exists | Mark all as read |
| DELETE | `/customers/notifications/:id` | Exists | Delete notification |

### 5.12 Referrals

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/referral` | **ADD** | Code + stats |
| POST | `/customers/referral/send` | **ADD** | Send invitation |
| GET | `/customers/referral/history` | **ADD** | |

### 5.13 Deal Maker

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| POST | `/customers/dealmaker/apply` | **ADD** | |
| GET | `/customers/dealmaker/status` | **ADD** | |
| GET | `/dealmakers/tasks` | **ADD** | |
| POST | `/dealmakers/tasks/:id/complete` | **ADD** | |
| GET | `/dealmakers/earnings` | **ADD** | |

---

## 6. Screen Inventory & Route Map

### 6.1 Public Routes (no auth required)

| Route | Component | Key Features |
|-------|-----------|-------------|
| `/` | `HomePage` | Hero slider, category grid (with split-view popover), flash deals strip, featured merchants, blogs, partners carousel |
| `/explore` | `ExplorePage` | Search + filter all merchants/coupons |
| `/merchants` | `MerchantListPage` | Directory by category + area + keyword; favourite hearts |
| `/merchants/:id` | `MerchantDetailPage` | Profile, stores, coupon preview, link to stores |
| `/stores/:id` | `StoreDetailPage` | Gallery tabs, reviews list+form, complaint form, working hours, map embed, coupons link |
| `/coupons` | `CouponBrowsePage` | Browse with tag/area/keyword filters; hybrid favourite heart |
| `/coupons/:id` | `CouponDetailPage` | Full detail; inline AuthModal when unauthenticated |
| `/flash-discounts` | `FlashDiscountListPage` | Browse; tier filter when logged in; favourite hearts |
| `/flash-discounts/:id` | `FlashDiscountDetailPage` | Detail; NO subscribe button (in-store redemption) |
| `/blog` | `BlogListPage` | Published posts card grid |
| `/blog/:slug` | `BlogDetailPage` | Full blog post |
| `/about` | `AboutPage` | CMS content |
| `/contact` | `ContactPage` | Contact form |
| `/page/:slug` | `CmsPage` | Generic CMS page |
| `/business-signup` | `BusinessSignUpPage` | Merchant interest registration form |
| `/login` | `LoginPage` | 3-step: mobile/card -> OTP -> password |
| `/register` | `RegisterPage` | Registration |
| `/otp-verify` | `OtpVerifyPage` | OTP verification step |
| `/forgot-password` | `ForgotPasswordPage` | Request password reset |
| `/reset-password` | `ResetPasswordPage` | Set new password with OTP |
| `/onboarding` | `OnboardingPage` | City + area selection (first login, auth required) |

### 6.2 Protected Routes (auth required)

| Route | Component | Card Required | Key Features |
|-------|-----------|:---:|-------------|
| `/dashboard` | `DashboardPage` | Yes | Loyalty card display (tap-to-reveal), category grid |
| `/loyalty-cards` | `LoyaltyCardSelectPage` | — | Select card type; assigns sequential card number |
| `/wallet` | `CouponWalletPage` | Yes | Saved coupons, status tabs, auto-expiry, nav dropdown |
| `/wallet/gifts` | `GiftCouponInboxPage` | Yes | Gifted coupon inbox, accept/reject |
| `/wallet/store` | `StoreCouponPage` | Yes | Store-assigned coupons |
| `/wishlist` | `WishlistPage` | — | Favourite stores, remove action |
| `/important-days` | `ImportantDaysPage` | — | Add/view personal events |
| `/grievances` | `GrievanceListPage` | — | Complaint history with merchant replies |
| `/grievances/new` | `GrievanceFormPage` | — | Submit new complaint |
| `/grievances/:id` | `GrievanceDetailPage` | — | Detail + reply + archive |
| `/notifications` | `NotificationsPage` | — | Full inbox with read/delete |
| `/activity` | `ActivityPage` | — | Surveys, mystery shopping, contests hub |
| `/surveys/:id` | `SurveyTakePage` | — | Take a survey |
| `/contests` | `ContestListPage` | — | Browse and enter contests |
| `/contests/:id` | `ContestDetailPage` | — | Rules + participate |
| `/referrals` | `ReferralPage` | — | My referral code + stats + history |
| `/more` | `MorePage` | — | Deal Maker portal |
| `/profile` | `ProfilePage` | Yes | All profile fields, card summary |
| `/profile/edit` | `EditProfilePage` | — | Edit profile with dynamic area dropdown |
| `/profile/change-password` | `ChangePasswordPage` | — | Old + new + confirm |
| `/profile/set-password` | `SetNewPasswordPage` | — | Mandatory reset (no old pwd) |
| `/profile/subscription` | `SubscriptionPage` | Yes | Subscription status + renew |
| `/profile/card` | `MyCardPage` | — | Card detail + tap-to-reveal |

---

## 7. Business Rules Reference

### 7.1 Coupon Subscription — 7-Step Validation

Before creating a subscription the API **must** check all steps in sequence:

1. **Auth** — Customer must be logged in
2. **Card** — Customer must have an assigned loyalty card
3. **Live limit** — Active subscriptions count < `card.allowed_max_subs_live`
4. **Monthly limit** — This-month subscription count < `card.allowed_subs_per_month`
5. **Date expiry** — `today <= coupon.valid_until`
6. **Single-redeem uniqueness** — If `coupon.redeem_type = 'single'`: customer has not already subscribed
7. **Availability** — `coupon.usage_count < coupon.usage_limit` (when `usage_limit` is not null)

Return format: `{ status: 'success'|'error', message: '...' }`

### 7.2 Offer Zone Auto-Expiry

On every `GET /customers/coupons/wallet` call, before fetching data:

```sql
UPDATE coupon_subscriptions
SET status = 'expired'
WHERE customer_id = :id
  AND status = 'active'
  AND coupon_valid_until < NOW()
```

### 7.3 Flash Discount Classification Filter

- When JWT `Authorization` header is present: filter flash discounts by the customer's card tier (`classification_id` field on loyalty card)
- When unauthenticated: return all active flash discounts (no tier filter)

### 7.4 Review Insert/Update Pattern

Use `INSERT ... ON DUPLICATE KEY UPDATE` on `store_reviews` where the unique key is `(store_id, reviewer_mobile)`. Review images are always appended (never replaced).

### 7.5 Favourites Scope

Favourites are per **store** (unit), not per merchant company. The `customer_merchant_favourites.store_id` is the primary scope key. `merchant_id` is stored for convenience.

### 7.6 Mandatory Password Reset Guard

Any protected page must check `authStore.user.temp_password === 1`. If true, `MandatoryPasswordResetGuard` redirects to `/profile/set-password`. The set-new-password form does NOT require the old password.

### 7.7 Loyalty Card Guard

Pages marked "Card Required" check `authStore.user.card_id`. If null, `CardGuard` redirects to `/loyalty-cards`. Once a card is selected, a sequential card number from the pre-generated pool is assigned.

### 7.8 Location Requirement for Browsing

Public browse pages (coupon browse by category, flash discount browse, merchant directory) check `locationStore.cityId`. If null, the `LocationModal` opens automatically. Location persists in Zustand + localStorage.

### 7.9 Auto-Account Creation (Complaint / Review)

`POST /public/stores/:id/complaints` — if `reviewer_mobile` is not found in `customers`:
1. Auto-create customer record with `registration_type = 'auto'`, `temp_password = 1`
2. Generate random 6-char password, hash, store
3. Send password via SMS
4. Use new `customer_id` for the complaint record

### 7.10 Inline Auth on Coupon Detail (Unauthenticated)

When an unauthenticated user visits `/coupons/:id`:
- "Get this offer" button is replaced by inline `AuthModal` (Login / Register / Reset tabs)
- On successful login/register from this page: automatically trigger the subscribe flow without page reload

### 7.11 Homepage CategoryGrid Split-View

Each category chip opens a popover/bottom sheet with 3 tabs:
- **Coupons** — navigates to `/coupons?category={slug}` (requires location)
- **Flash Discounts** — navigates to `/flash-discounts?category={slug}` (requires location)
- **Merchants** — navigates to `/merchants?category={slug}` (requires location)

---

## 8. Master Task List

### PHASE 1 — Foundation & Public Routes *(✅ LARGELY COMPLETE)*

#### P1-1: Router & Public Access Architecture ✅ DONE
- [x] `GuestShell.tsx` — public page layout with header, location badge, Footer
- [x] `router.tsx` — separate public route group (GuestShell) + protected group (AppShell+AuthGuard)
- [x] All public routes: `/`, `/deals`, `/deals/:id`, `/stores`, `/stores/:id`, `/flash-deals`, `/blog`, `/blog/:slug`, `/about`, `/contact`, `/business-signup`
- **Remaining:** `/merchants/:id` detail tab deepening; `/flash-deals/:id` full page; `/page/:slug` CMS

#### P1-2: Location System ✅ DONE
- [x] `locationStore.ts` — city + area, persisted to `localStorage`
- [x] `LocationModal.tsx` — searchable city list + area selection + confirm
- [x] Location badge in `TopBar.tsx`
- [x] Auto-open `LocationModal` when `cityId` is null on homepage
- **API:** `GET /public/areas?city_id=` — exists

#### P1-3: Homepage (Public) ✅ DONE
- [x] `GET /public/home` wired — sliders, categories, flash deals, featured merchants, blogs
- [x] Hero slider (Embla Carousel, auto-rotate)
- [x] Flash discounts strip
- [x] Featured merchants horizontal scroll
- [x] Latest blogs 2-card row
- [x] AdBanner carousel
- `CategoryGrid.tsx` ✅ fully implemented at `src/components/ui/CategoryGrid.tsx`

#### P1-4: Public Coupon Browser ✅ DONE
- [x] `DealsPage.tsx` — public coupon browse with filter UI, API data
- [x] `CouponBrowsePage.tsx` — authenticated coupon browse (205 lines)
- `blog.ts` API ✅ covered by `public.ts` (getBlogPosts, getBlogPost)

#### P1-5: Coupon Detail ✅ DONE (no inline AuthModal)
- [x] Full display: banner image, coupon code, title, description, T&C
- [x] `DealDetailPage.tsx` (public, 358 lines) — auth check redirects to login
- [x] `CouponDetailPage.tsx` (protected, 259 lines) — save/unsave wired to API
- `AuthModal.tsx` ✅ Done — inline auth component implemented

#### P1-6: Flash Discount Pages ⚠️ PARTIAL
- [x] `FlashDealsPage.tsx` — listing with API
- [x] `FlashDealDetailPage.tsx` — full implementation complete (gradient hero, countdown, merchant/store card, in-store notice)
- [x] `flashDiscounts.ts` API � covered by `public.ts` (getFlashDiscounts, getFlashDiscountDetail)
- **API:** `GET /public/flash-discounts` — exists

#### P1-7: Blog Pages ✅ DONE
- [x] `BlogListPage.tsx` — card grid with API
- [x] `BlogDetailPage.tsx` — full post rendering with API
- **Remaining:** `blog.ts` API endpoint file (currently inlined in pages)

#### P1-8: Static & CMS Pages ✅ MOSTLY DONE
- [x] `AboutPage.tsx` — exists
- [x] `ContactPage.tsx` — exists (no backend API yet)
- [x] `BusinessSignupPage.tsx` — exists (no backend API yet)
- [ ] `CmsPage.tsx` — generic `/page/:slug` not yet built
- **Backend needed:** `POST /public/contact`, `POST /public/business-signup`; DB tables `contact_messages`, `business_signups`

---

### PHASE 2 — Merchant & Store Pages

#### P2-1: Merchant Directory Page
- [ ] `MerchantListPage.tsx` — requires location; auto-opens `LocationModal` if not set
- [ ] Filter: category/tag chip bar, area multiselect, keyword search input
- [ ] `MerchantCard` — cover photo (16:9), logo overlay, name, rating stars, area name, favourite heart
- [ ] Infinite scroll with skeleton loading
- **Files:** `pages/merchants/MerchantListPage.tsx`
- **API:** `GET /public/merchants?city_id=&area_id=&tag_id=&search=&page=`
- **Backend:** `MerchantsController.php` or add to `PublicController.php`

#### P2-2: Merchant Detail Page Enhancement
- [ ] Merchant header: cover, logo, name, description, avg rating, total reviews
- [ ] Stores accordion/tabs list (each store with address, hours badge)
- [ ] Active coupons preview section (horizontal scroll of CouponCards)
- [ ] Links to individual `/stores/:id` pages
- **Files:** `pages/merchants/MerchantDetailPage.tsx`
- **API:** `GET /public/merchants/:id`, `GET /public/merchants/:id/stores`, `GET /public/merchants/:id/coupons`

#### P2-3: Store Detail Page *(largest single page)*
- [ ] Store header: cover photo, merchant logo, store name, address, rating badge, review count
- [ ] Working hours section (from `working_hours_json`) — show today's hours prominently
- [ ] **Gallery tabs:**
  - Tab "Merchant Gallery" — images from `store_gallery`
  - Tab "Customer Gallery" — images from `store_review_images`
  - Click → full-screen lightbox
- [ ] Reviews section: reviewer name, criteria stars, date, review text
- [ ] **Rate & Review form:**
  - 3 interactive `RatingStars` (Quality / Service / Ambience)
  - Review text textarea
  - Multiple image file upload (preview tiles)
  - Reviewer name + mobile fields
  - Submit → `POST /public/stores/:id/reviews`
  - INSERT or UPDATE based on `(store_id, mobile)` unique key
- [ ] **Complaint / Suggestion form** (collapsible):
  - Subject, message, name, mobile
  - Submit → `POST /public/stores/:id/complaints`
  - Auto-creates customer account if mobile not found
- [ ] Contact info card: phone click-to-call, email click-to-mail
- [ ] Google Maps embed using `latitude` + `longitude`
- [ ] CTA links: "See all coupons for this store", "See flash discounts"
- [ ] Favourite heart (logged in only)
- **Files:** `pages/merchants/StoreDetailPage.tsx`, `components/ui/RatingStars.tsx`, `components/ui/GalleryTabs.tsx`
- **API:** `GET /public/stores/:id`, `GET /public/stores/:id/reviews`, `POST /public/stores/:id/reviews`, `POST /public/stores/:id/complaints`
- **DB:** Create `store_reviews`, `store_review_images`; alter `stores` (lat/lng, working_hours_json, avg_rating, total_reviews)
- **Backend:** `StoreController.php` in `api/controllers/Public/`

---

### PHASE 3 — Auth Completeness & Loyalty Card System

#### P3-1: Mandatory Password Reset Guard
- [ ] `MandatoryPasswordResetGuard.tsx` — wraps all protected routes inside `AuthGuard`; checks `authStore.user.temp_password === 1`; redirects to `/profile/set-password`
- [ ] `SetNewPasswordPage.tsx` — new password + confirm; no old password field; submits to `POST /auth/customer/set-new-password`; on success sets `temp_password = 0` in `authStore`
- **Files:** `components/layout/MandatoryPasswordResetGuard.tsx`, `pages/profile/SetNewPasswordPage.tsx`
- **API:** `POST /auth/customer/set-new-password`
- **Backend:** Add `setNewPassword()` action to `AuthController.php`

#### P3-2: AuthModal Component ✅ DONE
- [x] `AuthModal.tsx` — centered modal with 2 tabs: Login, Register
- [ ] All existing auth page logic reused inside modal forms
- [ ] Exposes `onSuccess: () => void` callback for post-auth actions
- [ ] Used on: `CouponDetailPage` (inline), globally accessible via `authStore.showAuthModal()`
- **Files:** `components/ui/AuthModal.tsx`

#### P3-3: Loyalty Card Selection Page
- [ ] `LoyaltyCardSelectPage.tsx` — grid of available card types from `GET /customers/card/available-types`
- [ ] Card type tile: front card image, name, monthly limit, concurrent limit, "Select" button
- [ ] On select: `POST /customers/card/select` → assigns sequential card number from pool → updates `authStore`
- [ ] `CardGuard.tsx` — checks `authStore.user.card_id`; redirects to `/loyalty-cards` if null; wraps card-required routes
- **Files:** `pages/loyalty/LoyaltyCardSelectPage.tsx`, `components/layout/CardGuard.tsx`
- **API:** `GET /customers/card/available-types`, `POST /customers/card/select`
- **Backend:** Add `getAvailableTypes()`, `selectCard()` to `CardController.php`

#### P3-4: Change Password Page
- [x] `ChangePasswordPage.tsx` — old password + new password + confirm fields, strength indicator, success state
- [ ] Client validation: match + min 6 chars
- [ ] Submits to `POST /customers/password/change`
- **Files:** `pages/profile/ChangePasswordPage.tsx`
- **API:** `POST /customers/password/change`
- **Backend:** Add `changePassword()` to `ProfileController.php`

---

### PHASE 4 — Coupon Wallet & Subscription Engine

#### P4-1: Coupon Subscription Backend
- [ ] Implement `POST /customers/coupons/:id/subscribe` with all 7 validation steps (see Section 7.1)
- [ ] Implement `DELETE /customers/coupons/:id/subscribe`
- [ ] On wallet fetch: run auto-expiry UPDATE before returning results
- **Backend:** `CouponController.php` — add `subscribe()`, `unsubscribe()` actions

#### P4-2: Offer Zone Enhancement
- [ ] Status filter tabs bar: All | Active | Redeemed | Expired
- [ ] Navigation dropdown matching old spec: All / Active / Redeemed / Expired / Gifted / Store Coupons
- [ ] Gifted banner carousel at top (if gifted coupons exist)
- [ ] Coupon card: image, title, redeem units list (with checkmark on redeemed unit), last redemption date, status badge
- [ ] Status badges: Active (green), Redeemed (amber), Expired (red)
- **Files:** `pages/coupons/CouponWalletPage.tsx`
- **API:** `GET /customers/coupons/wallet?status=`

#### P4-3: Gift Coupon Inbox
- [ ] `GiftCouponInboxPage.tsx` — list from `GET /customers/gift-coupons`
- [ ] Accept / Reject actions per coupon
- [ ] Same card style as offer zone
- **Files:** `pages/coupons/GiftCouponInboxPage.tsx`
- **API:** `GET /customers/gift-coupons`, `POST /customers/gift-coupons/:id/accept`, `POST /customers/gift-coupons/:id/reject`

#### P4-4: Store Coupons Page
- [ ] `StoreCouponPage.tsx` — merchant-assigned store coupons from `GET /customers/store-coupons`
- [ ] Card: coupon title + terms & conditions
- [ ] Show only non-expired assignments
- **Files:** `pages/coupons/StoreCouponPage.tsx`
- **API:** `GET /customers/store-coupons`
- **Backend:** Add `storeCoupons()` to `CouponController.php`

---

### PHASE 5 — Profile Enhancements

#### P5-1: Profile Page — All Fields
- [ ] Verify all fields displayed: first name, last name, email, mobile, DOB, gender, profession, occupation, address, pincode, city, area
- [ ] Add loyalty card mini-section: masked card number with tap-to-reveal
- [ ] Edit Profile and Change Password links/buttons
- **Files:** `pages/profile/ProfilePage.tsx`

#### P5-2: Edit Profile — Dynamic Area Dropdown
- [ ] On city selection change: call `GET /public/areas?city_id=X` and repopulate area dropdown
- [ ] Save all fields: occupation, full address, pincode, city, area
- [ ] Profile image upload with preview
- **Files:** `pages/profile/EditProfilePage.tsx`
- **DB:** Add columns to `customers` table (Section 4.9)

#### P5-3: My Card — Tap-to-Reveal
- [x] `TapReveal.tsx` — renders masked text; tap-to-reveal with 6s countdown, copy-to-clipboard, auto-hide
- [ ] Card renders front face: gradient background + card design image + customer name + masked number
- [ ] Card back (tap/flip): partner logos
- **Files:** `pages/profile/MyCardPage.tsx`, `components/ui/TapReveal.tsx`

---

### PHASE 6 — Wishlist, Important Days & Complaints

#### P6-1: Wishlist / Favourite Stores ✅ DONE
- [x] `WishlistPage.tsx` — lists saved merchants from `GET /customers/favourites`
- [x] Remove action — optimistic mutation with rollback on error
- [x] `favourites.ts` API endpoint file created
- [x] `MerchantCard` heart wired to `favouritesApi.add/remove` (auth-gated)
- [x] `MerchantCard` link fixed: `/merchants/:id` → `/stores/:id`
- **Still needed:** Wire add-favourite heart on `StoreDetailPage` and `MerchantDetailPage`

#### P6-2: Important Days
- [ ] `ImportantDaysPage.tsx` — list of existing events (type, date, delete button) + add form
- [ ] Add form: event type dropdown (Birthday / Anniversary / Others), custom label when "Others", day 1–31, month (January–December)
- [ ] Delete event action
- **Files:** `pages/importantDays/ImportantDaysPage.tsx`, `api/endpoints/importantDays.ts`
- **DB:** Create `customer_important_days` table (Section 4.1)
- **API:** `GET /customers/important-days`, `POST /customers/important-days`, `DELETE /customers/important-days/:id`
- **Backend:** `ImportantDaysController.php` in `api/controllers/Customer/`

#### P6-3: Complaints / Grievances
- [ ] `GrievanceListPage.tsx` — list: subject, message snippet, merchant reply snippet, reply date, status badge, archive button
- [ ] `GrievanceDetailPage.tsx` — full thread: my message + merchant reply + reply date
- [ ] Archive action: `PUT /customers/grievances/:id/archive` → removes from active list
- [ ] `GrievanceFormPage.tsx` — standalone complaint form (also used inline on `StoreDetailPage`)
- [ ] Auto-account creation on `POST /public/stores/:id/complaints` (see Section 7.9)
- **Files:** `pages/grievances/GrievanceListPage.tsx`, `pages/grievances/GrievanceDetailPage.tsx`, `pages/grievances/GrievanceFormPage.tsx`
- **API:** `GET /customers/grievances`, `GET /customers/grievances/:id`, `PUT /customers/grievances/:id/archive`
- **Backend:** Enhance `GrievanceController.php`

---

### PHASE 7 — Activity Hub

#### P7-1: Notifications Page
- [x] `NotificationsPage.tsx` — full inbox, unread badge, filter by type, mark single/all read, optimistic delete
- [ ] Mark single as read, mark all as read, delete
- [ ] Unread count badge on TopBar bell
- **Files:** `pages/notifications/NotificationsPage.tsx`
- **API:** Add `PUT /customers/notifications/read-all`, `DELETE /customers/notifications/:id`
- **Backend:** Enhance `NotificationController.php`

#### P7-2: Survey System
- [ ] `ActivityPage.tsx` — sections: Active Surveys (badge: X pending), Completed Surveys, Mystery Shopping, Contests
- [ ] `SurveyTakePage.tsx` — sequential questions with progress bar, radio/checkbox/text inputs, submit + confirmation
- [ ] Completed surveys history section
- **Files:** `pages/surveys/SurveyTakePage.tsx`, enhance `pages/activity/ActivityPage.tsx`
- **API:** `GET /customers/surveys`, `GET /customers/surveys/:id`, `POST /customers/surveys/:id/submit`, `GET /customers/surveys/completed`
- **Backend:** `SurveyController.php` in `api/controllers/Customer/`

#### P7-3: Contest System
- [ ] `ContestListPage.tsx` + `ContestDetailPage.tsx`
- [ ] Entry form, rules display, my entries history, winners announcement section
- **Files:** `pages/contests/*`
- **API:** All contest endpoints (Section 5.10)
- **Backend:** `ContestController.php` in `api/controllers/Customer/`

---

### PHASE 8 — Referrals, Deal Maker & More Page

#### P8-1: Referral Page
- [ ] `ReferralPage.tsx` — unique referral code with copy-to-clipboard button
- [ ] Stats: total referrals sent, successful conversions, pending
- [ ] Referral history list per person
- [ ] Share via WhatsApp, SMS, copy link sharing options
- **Files:** `pages/referrals/ReferralPage.tsx`, `api/endpoints/referrals.ts`
- **API:** `GET /customers/referral`, `POST /customers/referral/send`, `GET /customers/referral/history`

#### P8-2: More Page / Deal Maker Portal
- [ ] `MorePage.tsx` — hub: Deal Maker status card + tasks list + earnings summary
- [ ] Apply to become Deal Maker CTA (if not yet applied)
- [ ] Assigned task cards with complete action
- [ ] Earnings breakdown
- **Files:** `pages/more/MorePage.tsx`, `api/endpoints/dealmaker.ts`
- **API:** All Deal Maker endpoints (Section 5.13)

---

### PHASE 9 — Quality, Performance & Polish

#### P9-1: UI Polish Pass
- [ ] Audit all cards against design system (colors, shadows, font sizes, spacing)
- [ ] Skeleton loaders on every data-fetching page
- [ ] Empty-state illustrations for empty lists (wallet empty, wishlist empty, etc.)
- [ ] Error boundary states for API failures (retry button + friendly message)
- [ ] All modals/drawers have mobile swipe-to-dismiss

#### P9-2: Desktop Layout Validation
- [ ] Test all pages at 1280px+ — sidebar must be visible, footer must render in GuestShell
- [ ] `StoreDetailPage` — 2-column desktop: main (gallery, reviews, complaint form) + sidebar (contact, map, flash discounts)
- [ ] `CouponBrowsePage` — filter panel permanently visible as left sidebar on desktop (no toggle needed)
- [ ] `HomePage` — 3-column grid for featured merchants on desktop

#### P9-3: Performance
- [ ] Lazy-load all page components (already started)
- [ ] Infinite scroll for merchant/coupon lists using `useInfiniteQuery`
- [ ] All gallery images: `loading="lazy"` + `width/height` attributes
- [ ] Debounce keyword search inputs (350ms)

#### P9-4: SEO & Accessibility
- [ ] `react-helmet-async` — per-page `<title>`, `meta description`, Open Graph tags
- [ ] `role`, `aria-label` on all interactive icons (heart, bell, menu, close)
- [ ] Keyboard navigation support on all modal dialogs (focus trap, Escape to close)
- [ ] `alt` text on all images (merchant name + "logo" / "store photo" etc.)

#### P9-5: Database Migration Script
- [ ] Create `api/migrations/customer-app-phase2.sql` with all new CREATE TABLE and ALTER TABLE statements (Sections 4.1–4.9)
- [ ] Test migration on fresh `deal_machan` database before merging

---

## 9. Phase Plan

| Phase | Scope | Priority |
|-------|-------|----------|
| **Phase 1** | Foundation + All Public Routes (Homepage, Coupons, Flash Discounts, Blog, Static, Business Signup) | ✅ Largely Done — minor gaps remain (FlashDealDetail stub; AuthModal; CmsPage) |
| **Phase 2** | Merchant Directory + Store Detail (Gallery, Reviews, Map, Complaint form) | High — core discovery experience |
| **Phase 3** | Auth completeness (Mandatory Reset, Inline Auth Modal, Card Selection, Change Password) | High — gates all personal features |
| **Phase 4** | Coupon Wallet + Subscription engine (6-rule validation, Auto-expiry, Gifts, Store Coupons) | High — core monetisation mechanic |
| **Phase 5** | Profile enhancements (all fields, dynamic area dropdown, tap-to-reveal card) | Medium |
| **Phase 6** | Wishlist (wire favourites API), Important Days, Complaints management | Medium |
| **Phase 7** | Activity hub (Notifications, Surveys, Contests) | Medium |
| **Phase 8** | Referrals, Deal Maker, More page | Low-Medium |
| **Phase 9** | Quality, Performance, Desktop polish, SEO, Migration script | Continuous |

### Summary Counts

| Category | Total Items | ✅ Done | ⚠️ Stub | ❌ Missing / Needs Build |
|----------|-------------|---------|---------|--------------------------|
| Frontend Pages | 45 | 29 | 6 | 10 |
| API Endpoints | 75 | 42 | 0 | 33 |
| UI Components | 18 | 12 | 0 | 6 |
| DB Tables (new) | 8 | 1 | 0 | 7 |
| DB Alterations | 4 | 1 | 0 | 3 |
| Backend Controllers | 12 | 7 | 0 | 5 |

**Done highlights:** `coupons.banner_image` migration applied. `FavouriteController`, `NotificationController`, `CardController`, `BlogController`, `SearchController`, `CouponController` (Public+Customer) all exist and routed. All Phase 1 public routing is complete.

**Missing DB tables:** `customer_important_days`, `contact_messages`, `business_signups`, `store_reviews`, `store_review_images`; `temp_password` column on `customers` table; verify `customer_merchant_favourites` vs `merchant_favourites` naming.
