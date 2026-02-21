# Deal Machan — Customer Application: Consolidated Implementation Plan

> **Version:** 3.2 | **Date:** February 2026
> **Stack:** Vite + React 18 + TypeScript + TailwindCSS + TanStack Query v5 + Zustand
> **Backend:** PHP 8.1 REST API (JWT-authenticated) + MySQL
> **Runs at:** `http://localhost:5174`

---

## ✅ Completed Progress (v3.2 — February 2026)

### What Has Been Built

| Area | Status | Notes |
|------|--------|-------|
| **GuestShell** — sticky header + footer for all public routes | ✅ Done | `components/layout/GuestShell.tsx` |
| **Footer** — newsletter + links + social + legal | ✅ Done | `components/layout/Footer.tsx` |
| **AuthLayout** — split-screen auth wrapper (brand panel left, form right) | ✅ Done | `components/layout/AuthLayout.tsx` |
| **router.tsx** — public/protected split | ✅ Done | Public: GuestShell; Protected: AuthGuard→AppShell |
| **Homepage** — hero + categories + flash deals + coupons + merchants + CTA | ✅ Done | `pages/home/HomePage.tsx` |
| **Login page** — modern AuthLayout design | ✅ Done | `pages/auth/LoginPage.tsx` |
| **Register page** — modern AuthLayout design | ✅ Done | `pages/auth/RegisterPage.tsx` |
| **OTP Verify page** — modern AuthLayout design | ✅ Done | `pages/auth/OtpVerifyPage.tsx` |
| **Forgot Password page** — modern AuthLayout design | ✅ Done | `pages/auth/ForgotPasswordPage.tsx` |
| **Reset Password page** — modern AuthLayout design | ✅ Done | `pages/auth/ResetPasswordPage.tsx` |
| **DealsPage** — browse all coupons | ✅ Done | `pages/deals/DealsPage.tsx` |
| **DealDetailPage** — single deal view | ✅ Done | `pages/deals/DealDetailPage.tsx` |
| **StoresPage** — merchant directory | ✅ Done | `pages/stores/StoresPage.tsx` |
| **StoreDetailPage** — merchant profile | ✅ Done | `pages/stores/StoreDetailPage.tsx` |
| **FlashDealsPage** — flash discount listing | ✅ Done | `pages/flash-deals/FlashDealsPage.tsx` |
| **BlogListPage** — blog post grid | ✅ Done | `pages/blog/BlogListPage.tsx` |
| **BlogDetailPage** — single post | ✅ Done | `pages/blog/BlogDetailPage.tsx` |
| **CategoriesPage** — category grid | ✅ Done | `pages/categories/CategoriesPage.tsx` |
| **Static pages** — About, Contact, Business Signup | ✅ Done | `pages/static/` |
| **DashboardPage** (protected, `/dashboard`) | ✅ Done | `pages/dashboard/DashboardPage.tsx` |
| **WishlistPage stub** | ✅ Done | `pages/wishlist/WishlistPage.tsx` |
| **LoyaltyCardsPage stub** | ✅ Done | `pages/loyalty/LoyaltyCardsPage.tsx` |
| **ImportantDaysPage stub** | ✅ Done | `pages/important-days/ImportantDaysPage.tsx` |
| **CORS** — allow ports 5173, 5174, 5175 + all localhost in dev | ✅ Done | `api/config/constants.php` |
| **HomeController** — field aliases match TypeScript types | ✅ Done | `featured_merchants`, `merchant_name`, `valid_until` |
| **Auth API types** — `AuthResponse` flat tokens, correct `resetPassword` body | ✅ Done | `api/endpoints/auth.ts` |
| **LocationModal** — city search + city/area pills, opens from header or homepage | ✅ Done | `components/ui/LocationModal.tsx` |
| **GuestShell** location badge — clickable (desktop + mobile menu) | ✅ Done | Opens LocationModal |
| **HomePage** location prompt — "Set your location" banner + "Change" link | ✅ Done | Shows when no city / has city |
| **API: cities/areas** — field names match TypeScript types (`city_name`, `area_name`) | ✅ Done | `api/index.php` |
| **HomeController** — merchants JOIN stores→areas→cities for real location data | ✅ Done | Also includes `trial` subscription |
| **index.css** — website-first scrollable layout + helper classes | ✅ Done | — |

### What Is Not Yet Built (Next Phases)

| Area | Priority | Notes |
|------|----------|-------|
| CouponDetailPage — subscribe flow + AuthModal blur | P1 | Core conversion feature |
| FlashDealDetailPage | P2 | Currently stub |
| Coupon subscribe API + business rule validation | P1 | `POST /customers/coupons/:id/subscribe` |
| Wishlist (real API) — save/remove stores | P2 | `POST/DELETE /customers/favourites` |
| Loyalty card selection | P2 | `/loyalty-cards` |
| CouponWalletPage — full QR redeem flow | P2 | Exists but needs polish |
| Important Days (real API) | P3 | — |
| Grievances pages | P3 | — |
| Notifications page | P3 | — |
| Profile / Edit profile | P3 | — |
| Blog API — `GET /public/blog` | P2 | Controller needed |
| Onboarding page | ✅ Exists | Minor: update city_name display |
| AuthModal (inline auth on deal detail) | P2 | Modal to avoid page redirect |

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
│   │   ├── client.ts               # Axios + JWT interceptors (public fallback, no-auth mode)
│   │   └── endpoints/
│   │       ├── auth.ts             # exists
│   │       ├── public.ts           # exists — home, banners, cities
│   │       ├── merchants.ts        # needs: directory, detail, stores, reviews
│   │       ├── coupons.ts          # exists — needs: subscribe, wallet, limits
│   │       ├── flashDiscounts.ts   # MISSING — listing, detail, tier filter
│   │       ├── blog.ts             # MISSING
│   │       ├── profile.ts          # exists
│   │       ├── cards.ts            # MISSING — card selection, activate
│   │       ├── favourites.ts       # MISSING — add/remove, list
│   │       ├── reviews.ts          # MISSING — submit/edit review
│   │       ├── grievances.ts       # MISSING
│   │       ├── importantDays.ts    # MISSING
│   │       ├── storeCoupons.ts     # MISSING
│   │       ├── surveys.ts          # MISSING
│   │       ├── contests.ts         # MISSING
│   │       ├── referrals.ts        # MISSING
│   │       ├── notifications.ts    # exists
│   │       ├── dealmaker.ts        # MISSING
│   │       └── businessSignup.ts   # MISSING
│   ├── components/
│   │   ├── layout/
│   │   │   ├── AppShell.tsx        # exists — review for public route support
│   │   │   ├── BottomTabBar.tsx    # exists
│   │   │   ├── Sidebar.tsx         # exists
│   │   │   ├── TopBar.tsx          # exists — add location badge
│   │   │   ├── AuthGuard.tsx       # exists
│   │   │   ├── GuestShell.tsx      # MISSING — public page wrapper with header+footer
│   │   │   └── MandatoryPasswordResetGuard.tsx  # MISSING
│   │   └── ui/
│   │       ├── AdBanner.tsx        # exists
│   │       ├── MerchantCard.tsx    # exists
│   │       ├── CouponCard.tsx      # exists
│   │       ├── FlashDiscountCard.tsx  # exists — verify fields
│   │       ├── CategoryGrid.tsx    # MISSING
│   │       ├── RatingStars.tsx     # MISSING
│   │       ├── GalleryTabs.tsx     # MISSING
│   │       ├── TapReveal.tsx       # MISSING
│   │       ├── AuthModal.tsx       # MISSING
│   │       ├── LocationModal.tsx   # MISSING
│   │       ├── SkeletonCard.tsx    # exists
│   │       ├── PageLoader.tsx      # exists
│   │       └── Toast.tsx           # MISSING (or via library)
│   ├── pages/
│   │   ├── auth/                   # exists: Login, Register, OtpVerify, ForgotPassword, Reset
│   │   ├── home/
│   │   │   └── HomePage.tsx        # exists — needs full sections + public route
│   │   ├── explore/
│   │   │   └── ExplorePage.tsx     # exists — deepen with filter panel
│   │   ├── merchants/
│   │   │   ├── MerchantListPage.tsx   # MISSING (directory by category)
│   │   │   ├── MerchantDetailPage.tsx # exists — needs galleries, reviews, map, coupons tab
│   │   │   └── StoreDetailPage.tsx    # MISSING (unit-level detail, reviews, complaint form)
│   │   ├── coupons/
│   │   │   ├── CouponBrowsePage.tsx   # exists — needs category/area/keyword filter
│   │   │   ├── CouponDetailPage.tsx   # exists — needs inline auth form if not logged in
│   │   │   ├── CouponWalletPage.tsx   # exists — needs status tabs, auto-expiry
│   │   │   ├── GiftCouponInboxPage.tsx  # MISSING
│   │   │   └── StoreCouponPage.tsx    # MISSING
│   │   ├── flashDiscounts/
│   │   │   ├── FlashDiscountListPage.tsx  # MISSING
│   │   │   └── FlashDiscountDetailPage.tsx # MISSING
│   │   ├── blog/
│   │   │   ├── BlogListPage.tsx    # MISSING
│   │   │   └── BlogDetailPage.tsx  # MISSING
│   │   ├── static/
│   │   │   ├── AboutPage.tsx       # MISSING
│   │   │   ├── ContactPage.tsx     # MISSING
│   │   │   └── CmsPage.tsx         # MISSING (generic CMS)
│   │   ├── business/
│   │   │   └── BusinessSignUpPage.tsx  # MISSING
│   │   ├── onboarding/
│   │   │   └── OnboardingPage.tsx  # exists — city+area selection
│   │   ├── loyalty/
│   │   │   └── LoyaltyCardSelectPage.tsx  # MISSING
│   │   ├── profile/
│   │   │   ├── ProfilePage.tsx     # exists
│   │   │   ├── EditProfilePage.tsx # exists
│   │   │   ├── SubscriptionPage.tsx # exists
│   │   │   ├── MyCardPage.tsx      # exists — add tap-to-reveal
│   │   │   ├── ChangePasswordPage.tsx  # MISSING
│   │   │   └── SetNewPasswordPage.tsx  # MISSING (mandatory reset)
│   │   ├── wishlist/
│   │   │   └── WishlistPage.tsx    # MISSING
│   │   ├── importantDays/
│   │   │   └── ImportantDaysPage.tsx  # MISSING
│   │   ├── grievances/
│   │   │   ├── GrievanceListPage.tsx  # stub in router -> ActivityPage
│   │   │   ├── GrievanceDetailPage.tsx # MISSING
│   │   │   └── GrievanceFormPage.tsx  # MISSING
│   │   ├── activity/
│   │   │   └── ActivityPage.tsx    # exists — surveys, mystery shopping, contests
│   │   ├── surveys/                # MISSING pages
│   │   ├── mystery-shopping/       # MISSING pages
│   │   ├── contests/               # MISSING pages
│   │   ├── referrals/
│   │   │   └── ReferralPage.tsx    # stub -> ProfilePage
│   │   ├── notifications/
│   │   │   └── NotificationsPage.tsx  # stub -> ActivityPage
│   │   └── more/
│   │       └── MorePage.tsx        # MISSING — Deal Maker portal + extras
│   ├── store/
│   │   ├── authStore.ts            # exists
│   │   └── locationStore.ts        # exists
│   ├── router.tsx                  # needs public routes + missing protected routes
│   ├── App.tsx                     # exists
│   ├── main.tsx                    # exists
│   └── index.css                   # exists
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

### 3.2 Authentication System — Partial

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Login (3-step: mobile OTP password) | Required | LoginPage + OtpVerifyPage | Review OTP step completeness |
| Registration | Required | RegisterPage | Verify mobile uniqueness check |
| Forgot / Reset Password | Required | ForgotPasswordPage | Verify SMS OTP flow |
| Logout | Required | authStore | Confirm session clear |
| Mandatory Password Reset Guard | Required | MISSING | Add guard + SetNewPasswordPage |
| Inline auth on CouponDetail (unauthenticated) | Required | MISSING | Embed AuthModal in CouponDetailPage |

### 3.3 Loyalty Card System — Missing

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Loyalty Card Type Selection page | Required | No page/route | Add LoyaltyCardSelectPage |
| Card number assignment (sequential pool) | Required | DB has cards table | Map to new schema |
| Card requirement guard (redirect if no card) | Required | MISSING | Add CardGuard component |
| Tap-to-reveal card number | Required | MISSING | Add TapReveal in MyCardPage |

### 3.4 Coupon System — Partial

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Coupon browse with filter (category, area, keyword) | Required | Basic CouponBrowsePage | Add full filter panel |
| Coupon detail with subscribe button | Required | CouponDetailPage | Wire subscribe API with limit checks |
| Coupon subscription 6-rule validation | Required (Critical) | Not in API | Implement in CouponController.php |
| Offer Zone (wallet) with status tabs | Required | CouponWalletPage | Add status filter tabs + auto-expiry trigger |
| Gifted coupons inbox | Required | No dedicated page | Add GiftCouponInboxPage |
| Store coupons | Required | No page/route | Add StoreCouponPage |
| Coupon auto-expiry on page load | Required | MISSING | Trigger API call on wallet load |
| Wallet navigation (All/Active/Redeemed/Expired/Gifted/Store) | Required | MISSING | Add to CouponWalletPage |

### 3.5 Flash Discounts — Entirely Missing

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Flash discount listing page | Required | No page/route | Add FlashDiscountListPage |
| Flash discount detail page | Required | No page/route | Add FlashDiscountDetailPage |
| Loyalty tier filtering (logged-in users) | Required | MISSING | Add tier filter logic in API |
| Favourite heart on flash discounts | Required | MISSING | Wire favourites to flash discount cards |
| No subscribe button (redeemed in-store) | Rule | Not enforced | Ensure no subscribe button on flash detail |

### 3.6 Merchant & Store — Partial

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Merchant listing / directory page | Required | No route (MerchantListPage missing) | Add MerchantListPage |
| Merchant directory filter (category, area, keyword) | Required | N/A | Add filter panel |
| Store detail page (unit-level) | Required | MerchantDetailPage (partial) | Add StoreDetailPage with all sections |
| Merchant gallery (unit images) | Required | MISSING | Add gallery tab |
| Customer gallery (review images) | Required | MISSING | Add customer gallery tab |
| Rate & Review form (3-criteria, images) | Required | MISSING | Add review form |
| Review submit: insert vs update logic | Required | MISSING | Implement in API |
| Complaint/Suggestion form on store detail | Required | MISSING | Add complaint form with auto-account creation |
| Working hours display | Required | MISSING | API + display |
| Google Maps embed | Required | MISSING | Add map component with lat/lng |
| Favourite heart per merchant unit | Required | FavouriteController exists | Wire to UI cards |

### 3.7 Profile & Account — Partial

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Profile view (all fields) | Required | ProfilePage | Verify all fields rendered |
| Edit profile with dynamic area dropdown | Required | EditProfilePage | Verify area dynamic load |
| Change password | Required | No page/route | Add ChangePasswordPage |
| Set new password (mandatory reset) | Required | No page/route | Add SetNewPasswordPage |

### 3.8 Wishlist & Important Days — Missing

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Wishlist / Favourite stores page | Required | No page/route | Add WishlistPage |
| Remove from favourites | Required | MISSING | Add to FavouriteController.php + UI |
| Important Days management | Required | No page/route | Add ImportantDaysPage |

### 3.9 Complaints — Partial

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| My complaints & suggestions list | Required | Stub to ActivityPage | Add GrievanceListPage |
| Complaint detail + merchant reply | Required | MISSING | Add GrievanceDetailPage |
| Archive complaint | Required | MISSING | Add archive action in API + UI |
| Complaint form on store detail page | Required | MISSING | Add inline form on StoreDetailPage |
| Auto-account creation (if mobile not found) | Required | MISSING | Add in GrievanceController.php |

### 3.10 Blog & Static Pages — Entirely Missing

| Feature | Old Spec | Current Status | Gap |
|---------|----------|----------------|-----|
| Blog listing page | Required | MISSING | Add BlogListPage |
| Blog detail page | Required | MISSING | Add BlogDetailPage |
| About page | Required | MISSING | Add AboutPage |
| Contact page with form | Required | MISSING | Add ContactPage |
| Generic CMS page | Required | MISSING | Add CmsPage |
| Business sign-up form | Required | MISSING | Add BusinessSignUpPage |

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
| GET | `/public/areas?city_id=` | **ADD** | Dynamic area loading for filters/profile |
| GET | `/public/tags` | Exists | Categories + sub-categories |
| GET | `/public/advertisements` | Exists | |
| GET | `/public/merchants` | **ADD** | Directory: city, area, tag, search, page |
| GET | `/public/merchants/:id` | **ADD** | Merchant profile + stores list |
| GET | `/public/merchants/:id/stores` | **ADD** | Stores with map coords |
| GET | `/public/merchants/:id/coupons` | **ADD** | Active coupon preview |
| GET | `/public/stores/:id` | **ADD** | Store/unit detail |
| GET | `/public/stores/:id/reviews` | **ADD** | Reviews list |
| POST | `/public/stores/:id/reviews` | **ADD** | Submit review (auto-creates account if needed) |
| POST | `/public/stores/:id/complaints` | **ADD** | Submit complaint (auto-creates account) |
| GET | `/public/flash-discounts` | **ADD** | Listing; tier filter when auth header present |
| GET | `/public/flash-discounts/:id` | **ADD** | Single flash discount |
| GET | `/public/coupons` | Exists | Review filter params |
| GET | `/public/coupons/:id` | Exists | |
| GET | `/public/blog` | **ADD** | Published posts list |
| GET | `/public/blog/:slug` | **ADD** | Single blog post |
| GET | `/public/contests` | **ADD** | Active public contests |
| GET | `/public/search?q=` | **ADD** | Search merchants + coupons |
| GET | `/public/page/:slug` | **ADD** | CMS page content |
| POST | `/public/contact` | **ADD** | Contact form (saves to DB + emails admin) |
| POST | `/public/business-signup` | **ADD** | Merchant interest registration |

### 5.3 Customer Profile

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/profile` | Exists | |
| PUT | `/customers/profile` | Exists | Verify all fields incl. area, city, pincode |
| POST | `/customers/profile/image` | Exists | |
| POST | `/customers/password/change` | **ADD** | Verify old + set new |
| POST | `/customers/password/reset-mandatory` | **ADD** | No old pwd required |
| GET | `/customers/subscription` | Exists | |
| GET | `/customers/card` | Exists | Verify tap-reveal fields returned |
| POST | `/customers/card/activate` | Exists | |
| GET | `/customers/card/available-types` | **ADD** | Card types for selection screen |
| POST | `/customers/card/select` | **ADD** | Select card type, assign sequential number |

### 5.4 Coupons

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/coupons/wallet` | Exists | Must trigger auto-expiry on call |
| GET | `/customers/coupons/wallet?status=active\|redeemed\|expired` | **ADD** | Status filter |
| GET | `/customers/coupons/history` | Exists | |
| POST | `/customers/coupons/:id/subscribe` | **ADD** | All 6 business rule checks |
| DELETE | `/customers/coupons/:id/subscribe` | **ADD** | Unsubscribe |
| GET | `/customers/gift-coupons` | **ADD** | Gifted coupons |
| POST | `/customers/gift-coupons/:id/accept` | **ADD** | |
| POST | `/customers/gift-coupons/:id/reject` | **ADD** | |
| GET | `/customers/store-coupons` | **ADD** | Store coupons assigned to customer |

### 5.5 Favourites

| Method | Endpoint | Status | Notes |
|--------|----------|--------|-------|
| GET | `/customers/favourites` | **ADD** | Favourite stores list |
| POST | `/customers/favourites` | **ADD** | Add `{store_id, merchant_id}` — idempotent |
| DELETE | `/customers/favourites/:store_id` | **ADD** | Remove by store |

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
| PUT | `/customers/notifications/read-all` | **ADD** | |
| DELETE | `/customers/notifications/:id` | **ADD** | |

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

### PHASE 1 — Foundation & Public Routes *(Highest Priority — Critical Blocker)*

#### P1-1: Router & Public Access Architecture
- [ ] Add `GuestShell.tsx` — public page layout with: logo + TopBar (search, location badge, Login link) + Footer
- [ ] Refactor `router.tsx` — create separate public route group (GuestShell, no auth) and protected group (AppShell + AuthGuard)
- [ ] Move `/coupons`, `/coupons/:id`, `/merchants/:id`, `/` to public routes
- [ ] Add all new public routes: `/merchants`, `/stores/:id`, `/flash-discounts`, `/flash-discounts/:id`, `/blog`, `/blog/:slug`, `/about`, `/contact`, `/page/:slug`, `/business-signup`
- **Files:** `router.tsx`, `components/layout/GuestShell.tsx`

#### P1-2: Location System
- [ ] Refine `locationStore.ts` — city + area (id + name), persist to localStorage
- [ ] Build `LocationModal.tsx` — searchable city list, then area list; confirm button
- [ ] Add location badge to `TopBar.tsx` — click opens `LocationModal`
- [ ] Wire location requirement: pages open `LocationModal` automatically if `cityId` is null
- **Files:** `store/locationStore.ts`, `components/ui/LocationModal.tsx`, `components/layout/TopBar.tsx`
- **API:** `GET /public/areas?city_id=`
- **Backend:** Add `areas()` handler in `PublicController.php`

#### P1-3: Homepage (Public)
- [ ] Wire `GET /public/home` — return sliders, categories, flash discounts, featured merchants, blogs, partners
- [ ] Implement hero slider with Embla Carousel (auto-rotate 5s, clickable, swipeable)
- [ ] `CategoryGrid.tsx` — responsive icon grid with split-view popover (Coupons / Flash / Merchants tabs per category)
- [ ] Flash discounts horizontal scroll strip with `FlashDiscountCard`
- [ ] Featured merchants horizontal scroll (card: logo, name, rating, distance chip)
- [ ] Latest blogs 2-card row (thumbnail, title, date, "Read more")
- [ ] Partners logo carousel (auto-rotate)
- [ ] AdBanner carousel at top (from `GET /public/advertisements`)
- **Files:** `pages/home/HomePage.tsx`, `components/ui/CategoryGrid.tsx`
- **Backend:** Ensure `GET /public/home` returns all required data

#### P1-4: Public Coupon Browser
- [ ] Full filter panel: tag/category chips, area multiselect, discount type chips, keyword search
- [ ] Mobile: filter opens as bottom sheet; Desktop: filter is a left sidebar always visible
- [ ] Favourite heart icon (visible only when logged in, red if favourited)
- [ ] Quick-view modal: coupon code, title, terms on hover/tap
- [ ] Pagination or infinite scroll
- **Files:** `pages/coupons/CouponBrowsePage.tsx`, `api/endpoints/coupons.ts`
- **API:** Verify `GET /public/coupons` supports all filter params

#### P1-5: Coupon Detail — Inline Auth + Subscribe
- [ ] Full display: banner image, coupon code (copyable), title, description, T&C, subscribe deadline, redeem deadline
- [ ] If logged in: "Get this offer" button wired to `POST /customers/coupons/:id/subscribe`
- [ ] If NOT logged in: Replace button with inline `AuthModal` (Login / Register / Reset tabs in slide-in panel)
- [ ] On auth success from this page: auto-trigger subscribe (no redirect)
- [ ] Favourite heart (logged in only)
- **Files:** `pages/coupons/CouponDetailPage.tsx`, `components/ui/AuthModal.tsx`

#### P1-6: Flash Discount Pages (Public)
- [ ] `FlashDiscountListPage.tsx` — same filter pattern as coupon browse (category, area, keyword)
- [ ] Apply loyalty tier filter: if JWT present, filter by `classification_id`; otherwise show all
- [ ] Favourite heart on each card (logged in only)
- [ ] `FlashDiscountDetailPage.tsx` — image, description, store name, store address, map, favourite heart
- [ ] **No** subscribe/redeem button — add a note "Redeemed in-store at the time of purchase"
- **Files:** `pages/flashDiscounts/FlashDiscountListPage.tsx`, `pages/flashDiscounts/FlashDiscountDetailPage.tsx`, `api/endpoints/flashDiscounts.ts`
- **API:** `GET /public/flash-discounts`, `GET /public/flash-discounts/:id`
- **Backend:** `FlashDiscountController.php` in `api/controllers/Public/`

#### P1-7: Blog Pages (Public)
- [ ] `BlogListPage.tsx` — card grid: large thumbnail, title, date, 2-line excerpt, "Read more" button
- [ ] `BlogDetailPage.tsx` — full-width featured image, heading, publish date, full HTML content, "Back to blog" link
- **Files:** `pages/blog/BlogListPage.tsx`, `pages/blog/BlogDetailPage.tsx`, `api/endpoints/blog.ts`
- **API:** `GET /public/blog`, `GET /public/blog/:slug`
- **Backend:** `BlogController.php` in `api/controllers/Public/`

#### P1-8: Static & CMS Pages (Public)
- [ ] `AboutPage.tsx` — renders HTML from `GET /public/page/about`
- [ ] `ContactPage.tsx` — form (name, mobile, subject, message) → `POST /public/contact` → success toast
- [ ] `CmsPage.tsx` — generic: load any slug via `GET /public/page/:slug`
- [ ] `BusinessSignUpPage.tsx` — form (contact name, org name, category dropdown, email, phone, message) → `POST /public/business-signup`
- **Files:** `pages/static/AboutPage.tsx`, `pages/static/ContactPage.tsx`, `pages/static/CmsPage.tsx`, `pages/business/BusinessSignUpPage.tsx`
- **API:** `GET /public/page/:slug`, `POST /public/contact`, `POST /public/business-signup`
- **DB:** Create `contact_messages`, `business_signups` tables
- **Backend:** Add handlers in `PublicController.php`

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

#### P3-2: AuthModal Component
- [ ] `AuthModal.tsx` — slide-up bottom sheet (mobile) / right-side panel (desktop) with 3 tabs: Login, Register, Reset Password
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
- [ ] `ChangePasswordPage.tsx` — old password + new password + confirm fields
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
- [ ] `TapReveal.tsx` — renders masked text; "Show" / "Hide" toggle; smooth reveal animation
- [ ] Card renders front face: gradient background + card design image + customer name + masked number
- [ ] Card back (tap/flip): partner logos
- **Files:** `pages/profile/MyCardPage.tsx`, `components/ui/TapReveal.tsx`

---

### PHASE 6 — Wishlist, Important Days & Complaints

#### P6-1: Wishlist / Favourite Stores
- [ ] `WishlistPage.tsx` — list of favourited stores
- [ ] Store entry: cover photo thumbnail, store name, address, trash/remove icon
- [ ] Remove action → `DELETE /customers/favourites/:store_id` → remove from list
- [ ] Wire add-favourite on all `MerchantCard`, `StoreDetailPage` heart buttons
- [ ] Favourite is idempotent (insert-or-ignore)
- [ ] Scope: per store, not per merchant company
- **Files:** `pages/wishlist/WishlistPage.tsx`, `api/endpoints/favourites.ts`
- **DB:** Create `customer_merchant_favourites` table
- **API:** `GET /customers/favourites`, `POST /customers/favourites`, `DELETE /customers/favourites/:store_id`
- **Backend:** Add `list()`, `add()`, `remove()` to `FavouriteController.php`

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
- [ ] `NotificationsPage.tsx` — full inbox replacing stub (currently goes to ActivityPage)
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
| **Phase 1** | Foundation + All Public Routes (Homepage, Coupons, Flash Discounts, Blog, Static, Business Signup) | Critical — blocks all unauthenticated use |
| **Phase 2** | Merchant Directory + Store Detail (Gallery, Reviews, Map, Complaint form) | High — core discovery experience |
| **Phase 3** | Auth completeness (Mandatory Reset, Inline Auth Modal, Card Selection, Change Password) | High — gates all personal features |
| **Phase 4** | Coupon Wallet + Subscription engine (6-rule validation, Auto-expiry, Gifts, Store Coupons) | High — core monetisation mechanic |
| **Phase 5** | Profile enhancements (all fields, dynamic area dropdown, tap-to-reveal card) | Medium |
| **Phase 6** | Wishlist, Important Days, Complaints management | Medium |
| **Phase 7** | Activity hub (Notifications, Surveys, Contests) | Medium |
| **Phase 8** | Referrals, Deal Maker, More page | Low-Medium |
| **Phase 9** | Quality, Performance, Desktop polish, SEO, Migration script | Continuous |

### Summary Counts

| Category | Total Items | Exists | Needs Work / New |
|----------|-------------|--------|-----------------|
| Frontend Pages | 45 | 17 | 28 |
| API Endpoints | 72 | 18 | 54 |
| UI Components | 18 | 9 | 9 |
| DB Tables (new) | 6 | 0 | 6 |
| DB Alterations | 3 | 0 | 3 |
| Backend Controllers | 12 | 6 | 6 |
