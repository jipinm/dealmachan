# Deal Machan — Customer Application: Implementation Plan

> **Version:** 1.0 | **Date:** February 2026  
> **Stack:** Vite + React 18 + TypeScript + TailwindCSS + TanStack Query v5 + Zustand  
> **Backend:** PHP 8.1 REST API (JWT-authenticated) + MySQL  
> **Runs at:** `http://localhost:5174`

---

## Table of Contents

1. [Design Principles](#1-design-principles)
2. [Architecture Overview](#2-architecture-overview)
3. [Database Schema Changes](#3-database-schema-changes)
4. [API Endpoints](#4-api-endpoints)
5. [Application Screens & User Flows](#5-application-screens--user-flows)
6. [UI/UX Design System](#6-uiux-design-system)
7. [Development Phases](#7-development-phases)
8. [Master Task List](#8-master-task-list)

---

## 1. Design Principles

### 1.1 Mobile-First, But Not Mobile-Only

| Viewport         | Layout Strategy                                              |
|------------------|--------------------------------------------------------------|
| **Mobile** (< 768px) | Single-column, bottom tab navigation, full-screen cards |
| **Tablet** (768–1024px) | 2-column grid, side drawer navigation                  |
| **Desktop** (> 1024px) | Fixed left sidebar nav + 3-column content grid (true web-app) |

Desktop layout mirrors apps like Zomato/Swiggy web — sidebar for navigation, wide content area with card grids, persistent search bar at top.

### 1.2 Core UX Principles

- **One-tap access**: All primary actions (redeem, browse, wallet) reachable within 2 taps from Home
- **Progressive disclosure**: Show summaries; expand on demand. No wall-of-text screens
- **Optimistic UI**: UI updates immediately on tap; rolls back silently on API error
- **Speed over completeness**: Skeleton loaders everywhere; no blocking spinners
- **Visual hierarchy**: Merchant logo + name + rating + distance is the atomic unit everywhere

### 1.3 Visual Language

- **Gradient brand**: `#667eea → #764ba2` (purple-indigo — consistent with Admin and Merchant apps)
- **Card-based design**: All content in elevated cards with subtle shadows
- **Glassmorphism accents**: Hero banners, featured merchant cards
- **High-contrast CTAs**: Orange-red gradient for primary actions (`#ff6b6b → #ee5a24`)
- **Typography**: `Inter` for UI, `Poppins` for headings

---

## 2. Architecture Overview

```
customer/
├── public/
│   └── manifest.webmanifest
├── src/
│   ├── api/
│   │   ├── client.ts               # Axios instance + JWT interceptors
│   │   └── endpoints/
│   │       ├── auth.ts
│   │       ├── profile.ts
│   │       ├── coupons.ts
│   │       ├── merchants.ts
│   │       ├── surveys.ts
│   │       ├── mysteryShopping.ts
│   │       ├── referrals.ts
│   │       ├── contests.ts
│   │       ├── grievances.ts
│   │       ├── notifications.ts
│   │       ├── cards.ts
│   │       ├── dealmaker.ts
│   │       └── public.ts
│   ├── components/
│   │   ├── layout/
│   │   │   ├── AppShell.tsx         # Responsive wrapper (mobile bottom-nav / desktop sidebar)
│   │   │   ├── BottomTabBar.tsx     # Mobile only (z-50)
│   │   │   ├── Sidebar.tsx          # Desktop only (fixed left)
│   │   │   ├── TopBar.tsx           # Search + location + notifications
│   │   │   └── AuthGuard.tsx
│   │   └── ui/
│   │       ├── MerchantCard.tsx
│   │       ├── CouponCard.tsx
│   │       ├── FlashDiscountBadge.tsx
│   │       ├── RatingStars.tsx
│   │       ├── SkeletonCard.tsx
│   │       ├── AdBanner.tsx
│   │       ├── PageLoader.tsx
│   │       └── Toast.tsx
│   ├── hooks/
│   │   ├── useLocation.ts           # City/area selection persisted to localStorage
│   │   └── useAuth.ts
│   ├── pages/
│   │   ├── auth/
│   │   │   ├── LoginPage.tsx
│   │   │   ├── RegisterPage.tsx
│   │   │   ├── OtpVerifyPage.tsx
│   │   │   └── ForgotPasswordPage.tsx
│   │   ├── home/
│   │   │   └── HomePage.tsx         # Discovery feed
│   │   ├── explore/
│   │   │   └── ExplorePage.tsx      # Search + filter merchants
│   │   ├── merchants/
│   │   │   ├── MerchantListPage.tsx
│   │   │   ├── MerchantDetailPage.tsx
│   │   │   ├── StoreDetailPage.tsx
│   │   │   └── MerchantReviewPage.tsx
│   │   ├── coupons/
│   │   │   ├── CouponBrowsePage.tsx
│   │   │   ├── CouponDetailPage.tsx
│   │   │   ├── CouponWalletPage.tsx
│   │   │   ├── RedemptionHistoryPage.tsx
│   │   │   └── GiftCouponInboxPage.tsx
│   │   ├── surveys/
│   │   │   ├── SurveyListPage.tsx
│   │   │   └── SurveyTakePage.tsx
│   │   ├── mystery-shopping/
│   │   │   ├── TaskListPage.tsx
│   │   │   └── TaskDetailPage.tsx
│   │   ├── referrals/
│   │   │   └── ReferralPage.tsx
│   │   ├── contests/
│   │   │   ├── ContestListPage.tsx
│   │   │   └── ContestDetailPage.tsx
│   │   ├── grievances/
│   │   │   ├── GrievanceListPage.tsx
│   │   │   └── GrievanceFormPage.tsx
│   │   ├── notifications/
│   │   │   └── NotificationsPage.tsx
│   │   ├── profile/
│   │   │   ├── ProfilePage.tsx
│   │   │   ├── EditProfilePage.tsx
│   │   │   ├── SubscriptionPage.tsx
│   │   │   └── MyCardPage.tsx
│   │   └── more/
│   │       └── MorePage.tsx         # Deal Maker portal + extras
│   ├── store/
│   │   ├── authStore.ts             # Zustand: user + tokens
│   │   └── locationStore.ts         # Zustand: selected city/area
│   ├── router.tsx
│   ├── App.tsx
│   ├── main.tsx
│   └── index.css
├── index.html
├── vite.config.ts
├── tailwind.config.ts
├── tsconfig.json
└── package.json
```

---

## 3. Database Schema Changes

The existing schema is comprehensive. The following additions/alterations are needed:

### 3.1 New Table: `customer_coupon_subscriptions`
Track which coupons a customer has "saved/subscribed to" (bookmarked from the browse feed):

```sql
CREATE TABLE IF NOT EXISTS `customer_coupon_subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `coupon_id` int(10) unsigned NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_coupon` (`customer_id`, `coupon_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_coupon_id` (`coupon_id`),
  CONSTRAINT `fk_ccs_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ccs_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 New Table: `customer_merchant_favourites`
Let customers favourite merchants:

```sql
CREATE TABLE IF NOT EXISTS `customer_merchant_favourites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `merchant_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_merchant` (`customer_id`, `merchant_id`),
  CONSTRAINT `fk_cmf_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmf_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.3 New Table: `contest_participations`
Track customer contest entries (spec mentions participation but no tracking table):

```sql
CREATE TABLE IF NOT EXISTS `contest_participations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contest_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `participated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `entry_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`entry_data`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_contest_customer` (`contest_id`, `customer_id`),
  CONSTRAINT `fk_cp_contest` FOREIGN KEY (`contest_id`) REFERENCES `contests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.4 Alter `customers` Table
Add fields needed by the customer app:

```sql
ALTER TABLE `customers`
  ADD COLUMN `date_of_birth` date DEFAULT NULL AFTER `profile_image`,
  ADD COLUMN `gender` enum('male','female','other') DEFAULT NULL AFTER `date_of_birth`,
  ADD COLUMN `city_id` int(10) unsigned DEFAULT NULL AFTER `gender`,
  ADD COLUMN `area_id` int(10) unsigned DEFAULT NULL AFTER `city_id`,
  ADD COLUMN `bio` varchar(500) DEFAULT NULL AFTER `area_id`,
  ADD COLUMN `profession_id` int(10) unsigned DEFAULT NULL AFTER `bio`,
  ADD COLUMN `push_enabled` tinyint(1) NOT NULL DEFAULT 1 AFTER `profession_id`;
```

### 3.5 Alter `coupons` Table
Add display fields and category tag:

```sql
ALTER TABLE `coupons`
  ADD COLUMN `banner_image` varchar(255) DEFAULT NULL AFTER `description`,
  ADD COLUMN `terms_and_conditions` text DEFAULT NULL AFTER `banner_image`,
  ADD COLUMN `tag_id` int(10) unsigned DEFAULT NULL AFTER `terms_and_conditions`,
  ADD INDEX `idx_tag_id` (`tag_id`);
```

### 3.6 Alter `merchants` Table
Add fields for public merchant profile:

```sql
ALTER TABLE `merchants`
  ADD COLUMN `business_description` text DEFAULT NULL AFTER `business_name`,
  ADD COLUMN `business_logo` varchar(255) DEFAULT NULL AFTER `business_description`,
  ADD COLUMN `website_url` varchar(255) DEFAULT NULL AFTER `business_logo`,
  ADD COLUMN `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)) AFTER `website_url`,
  ADD COLUMN `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00 AFTER `social_links`,
  ADD COLUMN `total_reviews` int(10) unsigned NOT NULL DEFAULT 0 AFTER `avg_rating`;
```

---

## 4. API Endpoints

All endpoints live under `/api/`. JWT required unless marked **(public)**.

### 4.1 Authentication (shared with Merchant)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/customer/register` | Register new customer |
| POST | `/auth/customer/login` | Login (email or phone + password) |
| POST | `/auth/customer/verify-otp` | Verify OTP for phone login |
| POST | `/auth/customer/forgot-password` | Request password reset OTP |
| POST | `/auth/customer/reset-password` | Reset password with OTP |
| POST | `/auth/customer/refresh-token` | Refresh JWT access token |
| POST | `/auth/customer/logout` | Logout + invalidate refresh token |

### 4.2 Public APIs (no auth required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/public/home` | Home feed: banners, featured merchants, flash discounts, top coupons |
| GET | `/public/merchants` | Browse merchants (city/area filter, tag filter, search, pagination) |
| GET | `/public/merchants/:id` | Merchant public profile |
| GET | `/public/merchants/:id/stores` | Merchant stores with map coords |
| GET | `/public/merchants/:id/coupons` | Merchant active coupons (no auth preview) |
| GET | `/public/merchants/:id/reviews` | Merchant reviews |
| GET | `/public/coupons` | Browse all coupons (filter by tag, city, discount type) |
| GET | `/public/flash-discounts` | Active flash discounts |
| GET | `/public/tags` | All tags (categories + subcategories) |
| GET | `/public/cities` | Cities + areas list |
| GET | `/public/advertisements` | Active ads (for carousel/popup) |
| GET | `/public/blog` | Published blog posts list |
| GET | `/public/blog/:slug` | Single blog post |
| GET | `/public/contests` | Active public contests |
| GET | `/public/search?q=` | Search merchants + coupons |

### 4.3 Customer Profile

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers/profile` | Get full profile |
| PUT | `/customers/profile` | Update profile (name, DOB, gender, bio, city, area, profession) |
| POST | `/customers/profile/image` | Upload profile photo |
| GET | `/customers/subscription` | Subscription status + expiry |
| POST | `/customers/subscription/renew` | Renew yearly subscription |
| GET | `/customers/card` | Get assigned card details |
| POST | `/customers/card/activate` | Activate preprinted card by card number |

### 4.4 Coupon Operations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers/coupons/wallet` | Saved + gifted coupons in wallet |
| GET | `/customers/coupons/history` | Redemption history (paginated) |
| POST | `/customers/coupons/:id/save` | Save coupon to wallet |
| DELETE | `/customers/coupons/:id/save` | Remove saved coupon |
| POST | `/customers/coupons/redeem` | Redeem coupon (`{coupon_code, store_id}`) |
| GET | `/customers/gift-coupons` | Received gift coupons (pending + accepted) |
| POST | `/customers/gift-coupons/:id/accept` | Accept gift coupon |
| POST | `/customers/gift-coupons/:id/reject` | Reject gift coupon |

### 4.5 Merchant Interaction

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers/favourites` | Get favourite merchants |
| POST | `/customers/favourites/:merchant_id` | Add to favourites |
| DELETE | `/customers/favourites/:merchant_id` | Remove from favourites |
| POST | `/customers/reviews` | Submit/update review for merchant |
| PUT | `/customers/reviews/:id` | Edit own review |
| DELETE | `/customers/reviews/:id` | Delete own review |
| POST | `/customers/grievances` | Submit grievance to merchant |
| GET | `/customers/grievances` | List own grievances |
| GET | `/customers/grievances/:id` | Grievance detail + resolution |

### 4.6 Surveys

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers/surveys` | Active surveys (not yet completed) |
| GET | `/customers/surveys/:id` | Survey with questions |
| POST | `/customers/surveys/:id/submit` | Submit survey responses |
| GET | `/customers/surveys/completed` | Completed surveys history |

### 4.7 Mystery Shopping

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers/mystery-shopping` | Assigned tasks (status: pending, in_progress, completed) |
| GET | `/customers/mystery-shopping/:id` | Task details + brief |
| PUT | `/customers/mystery-shopping/:id/start` | Mark task as in_progress |
| POST | `/customers/mystery-shopping/:id/submit` | Submit report (JSON + images) |
| POST | `/customers/mystery-shopping/:id/upload` | Upload evidence images |

### 4.8 Referrals

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers/referral` | My referral code + stats (total, successful, pending) |
| POST | `/customers/referral/send` | Send referral invitation (email or phone) |
| GET | `/customers/referral/history` | List of referrals + status |

### 4.9 Contests

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers/contests` | Active contests |
| GET | `/customers/contests/:id` | Contest details + rules |
| POST | `/customers/contests/:id/participate` | Enter contest |
| GET | `/customers/contests/my-entries` | My contest participations |
| GET | `/customers/contests/winners` | Announced winners |

### 4.10 Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/customers/notifications` | Notification list (paginated) |
| GET | `/customers/notifications/unread-count` | Badge count |
| PUT | `/customers/notifications/:id/read` | Mark single as read |
| PUT | `/customers/notifications/read-all` | Mark all as read |
| DELETE | `/customers/notifications/:id` | Delete notification |

### 4.11 Deal Maker Module

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/customers/dealmaker/apply` | Apply to become deal maker |
| GET | `/customers/dealmaker/status` | Application status |
| GET | `/dealmakers/tasks` | Assigned dealmaker tasks |
| POST | `/dealmakers/tasks/:id/complete` | Mark task complete |
| GET | `/dealmakers/earnings` | Earnings + performance stats |

---

## 5. Application Screens & User Flows

### 5.1 Authentication Flow

```
Landing Page (unauthenticated)
  ├── [Login] → LoginPage (email/phone + password)
  │     ├── Forgot Password → OtpVerifyPage → ResetPasswordPage
  │     └── [Login Success] → HomePage
  └── [Register] → RegisterPage
        ├── Fill: name, phone, email, password, referral_code (optional)
        ├── OTP verification (phone)
        └── [Success] → Onboarding (city/area selection) → HomePage
```

**Onboarding** (first login): 2-step — select City → select Area → Finish. Stored in `locationStore` and saved to profile.

### 5.2 Home Page (Discovery Feed)

The central hub. Layout differs by device:

**Mobile layout (bottom tabs):**
```
TopBar: [☰ Logo]  [📍 Kochi, Ernakulam ▾]  [🔔 3]
────────────────────────────────────────
Ad Carousel (auto-rotate, 5s, dismissable)
────────────────────────────────────────
🔥 Flash Discounts (horizontal scroll chips)
────────────────────────────────────────
Featured Merchants (horizontal card scroll)
  [Card: Logo | Name | Rating | Labels]
────────────────────────────────────────
Browse by Category (tag grid, 2x3)
  [🍔 Food] [💇 Beauty] [🏥 Health] [👗 Fashion] ...
────────────────────────────────────────
Top Coupons Near You (vertical list)
  [CouponCard: Merchant | Discount | Valid Until | Save btn]
────────────────────────────────────────
New on DealMachan (new merchants this week)
────────────────────────────────────────
Latest Blog Posts (2 card row)
────────────────────────────────────────
BottomTabBar: [Home] [Explore] [Wallet] [Activity] [Profile]
```

**Desktop layout (sidebar):**
- Left sidebar: Full nav menu with icons + labels
- Main area: 3-column grid — merchant cards, coupon cards
- Right panel: Active flash discounts + ad banner (sticky)

### 5.3 Merchant Discovery Flow

```
ExplorePage
  TopBar: [← Back]  [🔍 Search...]  [⚙ Filter]
  
  Filter options (drawer/modal):
    - City / Area
    - Category (Tags)
    - Labels (Premium, Verified...)
    - Rating (3+, 4+)
    - Has active coupon: Yes/No
    - Premium Partner only: toggle
  
  Results: Infinite scroll, MerchantCard grid
    [MerchantCard]
      Business logo + name
      Labels (badge row)
      Rating ★★★★☆ (4.2 · 128 reviews)
      Area, City
      Active coupons count chip
      [♥ Favourite] [→ View]
  
  MerchantDetailPage
    Hero: store photo / logo banner
    Business name + labels
    Rating + reviews count
    [⭐ Write Review] [♥ Favourite] [📞 Call] [🗺 Directions]
    
    Tabs: [Coupons] [Stores] [Gallery] [Reviews] [About]
    
    Coupons tab:
      CouponCard list
        Discount chip | Title
        Valid from → to
        [Save to Wallet] [Redeem Now]
    
    Stores tab:
      StoreCard (address, phone, open hours)
      Embedded map (OpenStreetMap / Google Maps)
    
    Reviews tab:
      Overall rating donut chart
      Individual reviews with report option
      [+ Write a Review] (auth required)
```

### 5.4 Coupon Wallet Flow

```
CouponWalletPage  (Tab: Wallet)
  Tabs: [Saved] [Gift Inbox] [Redeemed]
  
  Saved Tab:
    CouponCard list (saved by customer)
      [Redeem] quick-action button
  
  Gift Inbox Tab:
    GiftCouponCard (from admin/merchant)
      Pending: [Accept] [Decline]
      Accepted: [Redeem]
  
  Redeemed Tab:
    RedemptionHistoryCard
      Coupon name | Date | Amount saved | Merchant
  
  CouponDetailPage
    Full coupon information
    Terms & Conditions (expandable)
    Validity countdown timer
    QR code (for merchant to scan) OR manual code display
    [Redeem at Store] — triggers confirmation modal
    
  Redeem Flow:
    Customer shows QR/code at store
    Merchant scans via Merchant App
    Customer sees: [✓ Redeemed successfully! You saved ₹250]
```

### 5.5 Profile & Account Flow

```
ProfilePage
  Hero: avatar + name + subscription badge (Active/Expired)
  Quick stats: [X Coupons Saved] [X Redeemed] [X Referrals]
  
  My Card section (if card assigned):
    Card image display
    Card number (masked: XXXX-XXXX-1234)
    Status badge
  
  Menu:
    [✏ Edit Profile]
    [🔔 Subscription] 
    [🎫 My Card]
    [👥 Refer Friends]
    [📋 Grievances]
    [🔒 Change Password]
    [🚪 Logout]
  
  EditProfilePage:
    Profile photo upload
    Name, DOB, Gender
    City, Area (location picker)
    Profession, Bio
    [Save Changes]
  
  SubscriptionPage:
    Current plan card (Start date, Expiry, auto-renew toggle)
    Plan benefits list
    [Renew Now] (Razorpay integration)
```

### 5.6 Activity Tab (Surveys, Mystery Shopping, Contests)

A unified "Activity" tab that shows all engagement features:

```
ActivityPage
  Section: Active Surveys (badge: X pending)
    SurveyCard: title | # questions | Reward chip | [Take Survey]
  
  Section: Mystery Shopping Tasks (if assigned)
    TaskCard: merchant name | deadline | reward | [View Task]
  
  Section: Active Contests
    ContestCard: banner | title | end date | prizes | [Join]
  
  Section: Deal Maker (if is_dealmaker)
    DealMaker dashboard chip → MorePage dealmaker section
```

**SurveyTakePage:**
- Progress bar (Question X of Y)
- Question types: radio, checkbox, text, rating
- Navigation: [← Prev] [Next →] [Submit]
- Confirmation screen: "Thank you! Response submitted."

**TaskDetailPage (Mystery Shopping):**
- Task brief + instructions
- Photo upload evidence
- Text report form
- [Submit Report] → confirmation

### 5.7 Referral Flow

```
ReferralPage
  My Code: [JIPIN2024] [📋 Copy] [📤 Share]
  Share via: [WhatsApp] [SMS] [Email] [Copy Link]
  
  Stats card:
    Total Referred: 12
    Successful Registrations: 8
    Pending: 4
    Rewards Earned: ₹800
  
  Referral History list:
    Name (masked) | Status | Date | Reward
```

### 5.8 Notifications Flow

```
NotificationsPage
  Filter tabs: [All] [Coupons] [Surveys] [System]
  
  NotificationItem:
    Icon (type) | Title | Message | Time (relative)
    On tap → navigate to relevant content
    Swipe to delete (mobile)
  
  TopBar bell shows unread count badge
  [Mark all as read] action
```

---

## 6. UI/UX Design System

### 6.1 Color Tokens

```css
--brand-primary: #667eea;
--brand-secondary: #764ba2;
--brand-gradient: linear-gradient(135deg, #667eea, #764ba2);
--accent-cta: #ff6b6b;          /* Redeem / Primary action */
--accent-cta-2: #ee5a24;
--success: #00b894;
--warning: #fdcb6e;
--danger: #d63031;
--text-primary: #2d3436;
--text-secondary: #636e72;
--surface: #ffffff;
--surface-alt: #f8f9fa;
--border: #e2e8f0;
```

### 6.2 Component Patterns

- **MerchantCard** (grid): 240px wide, 1:1 top image ratio, name + rating + area below
- **CouponCard** (list): Full width, left color strip (brand gradient), discount big on right
- **Flash Discount Chip**: Pill with countdown timer + percentage
- **Category Grid**: 2 rows × 3 cols of icon + label tiles
- **Ad Carousel**: Auto-rotate, 16:9 ratio, overlay dismiss 'X', supports image + video
- **QR/Coupon Display**: Full-screen modal with scannable code, brightness auto-up on mobile

### 6.3 Navigation (Responsive)

**Mobile Bottom Tabs (5 tabs):**
```
[🏠 Home] [🔍 Explore] [🎫 Wallet] [📋 Activity] [👤 Profile]
```

**Desktop Left Sidebar (expanded):**
```
[🏠 Home]
[🔍 Explore]
[🎫 Wallet]
  ├─ Saved Coupons
  ├─ Gift Inbox
  └─ History
[📋 Activity]
  ├─ Surveys
  ├─ Mystery Shopping
  └─ Contests
[👤 Profile]
  ├─ My Account
  ├─ Subscription
  ├─ My Card
  └─ Referrals
[💬 Grievances]
[🔔 Notifications]
```

### 6.4 Key Micro-Interactions

- **Coupon save**: Heart icon animates fill + count increments
- **Ad dismiss**: Slide-up with 0.3s ease
- **Redeem confirmation**: Lottie confetti animation on success
- **Survey submit**: Slide-out + "Thank you" confetti
- **Skeleton loaders**: Match exact shape of card they replace
- **Pull-to-refresh**: Native feel on home feed and wallet

---

## 7. Development Phases

### Phase 1 — Foundation (Week 1–2)
Setup, auth, public browsing — fully functional without login.

**Deliverables:**
- Vite + React + TS + Tailwind project scaffold at `customer/`
- Zustand auth store + TanStack Query setup
- JWT-authenticated Axios client (with refresh interceptor)
- Login, Register, OTP, Forgot Password pages
- AppShell (responsive: BottomTabBar mobile / Sidebar desktop)
- TopBar with location selector + notification bell

**API Controllers:**
- `api/controllers/Customer/AuthController.php`
- `api/controllers/Public/HomeController.php`
- `api/controllers/Public/MerchantBrowseController.php`
- `api/controllers/Public/SearchController.php`

---

### Phase 2 — Discovery & Browsing (Week 3–4)
Public home feed, merchant browsing, coupon browsing.

**Deliverables:**
- HomePage with all sections (ads carousel, flash discounts, featured merchants, category grid, top coupons)
- ExplorePage with search + filters
- MerchantDetailPage with tabs (Coupons, Stores, Gallery, Reviews)
- CouponBrowsePage + CouponDetailPage
- Location picker with city/area selector

**API Controllers:**
- `api/controllers/Public/CouponController.php`
- `api/controllers/Public/AdController.php`
- `api/controllers/Public/BlogController.php`

---

### Phase 3 — Authenticated Features (Week 5–7)
Wallet, profile, redemption — core value of the app.

**Deliverables:**
- Coupon wallet (saved, gift inbox, history)
- Gift coupon accept/reject flow
- Coupon redemption (QR display + confirmation)
- Profile page + Edit Profile
- Subscription status + renew
- My Card page
- Favourites (merchants)

**API Controllers:**
- `api/controllers/Customer/CouponController.php`
- `api/controllers/Customer/ProfileController.php`
- `api/controllers/Customer/FavouriteController.php`
- `api/controllers/Customer/CardController.php`

---

### Phase 4 — Engagement (Week 8–10)
Surveys, mystery shopping, referrals, notifications.

**Deliverables:**
- Activity tab (unified engagement hub)
- Survey list + take survey + completion
- Mystery Shopping task list + detail + submit report
- Referral page + share functionality
- Notification centre + bell badge
- Contest list + participate flow

**API Controllers:**
- `api/controllers/Customer/SurveyController.php`
- `api/controllers/Customer/MysteryShoppingController.php`
- `api/controllers/Customer/ReferralController.php`
- `api/controllers/Customer/NotificationController.php`
- `api/controllers/Customer/ContestController.php`

---

### Phase 5 — Reviews, Grievances & Deal Maker (Week 11–12)
Completing the feedback loop features.

**Deliverables:**
- Review submission + edit + delete
- Grievance form + track status
- Deal Maker: apply, status, task list, earnings (for customers who are deal makers)
- Blog reader

**API Controllers:**
- `api/controllers/Customer/ReviewController.php`
- `api/controllers/Customer/GrievanceController.php`
- `api/controllers/Customer/DealMakerController.php`

---

### Phase 6 — Polish & PWA (Week 13)
Performance, accessibility, PWA, final QA.

**Deliverables:**
- PWA manifest + service worker (Vite PWA plugin)
- Offline fallback page
- Lazy loading all route pages
- Image optimization (WebP, lazy)
- Lighthouse score ≥ 90 on mobile
- Full TanStack Query caching strategy review
- E2E smoke tests (Playwright)
- Database migration SQL file for schema changes
- Git push with full test pass

---

## 8. Master Task List

### API Layer

- [ ] Create `api/controllers/Customer/` directory structure
- [ ] `AuthController.php` — register, login, OTP, refresh, logout
- [ ] `ProfileController.php` — get, update, upload image, subscription, card
- [ ] `CouponController.php` — wallet, save, unsave, redeem, gift inbox, accept/reject, history
- [ ] `FavouriteController.php` — list, add, remove merchant favourites
- [ ] `ReviewController.php` — submit, edit, delete review
- [ ] `GrievanceController.php` — submit, list, detail
- [ ] `SurveyController.php` — list, detail, submit, completed
- [ ] `MysteryShoppingController.php` — tasks, detail, start, submit, upload
- [ ] `ReferralController.php` — code, stats, send, history
- [ ] `ContestController.php` — list, detail, participate, my entries, winners
- [ ] `NotificationController.php` — list, unread count, mark read, delete
- [ ] `DealMakerController.php` — apply, status, tasks, earnings
- [ ] Create `api/controllers/Public/` directory structure
- [ ] `HomeController.php` — home feed composition (banners, featured, flash, coupons)
- [ ] `MerchantBrowseController.php` — list (filter/search/paginate), detail, stores, coupons, reviews
- [ ] `CouponBrowseController.php` — list (filter/tag/city), detail
- [ ] `AdController.php` — active advertisements
- [ ] `BlogController.php` — posts list, single post
- [ ] `SearchController.php` — merchants + coupons unified search
- [ ] `PublicDataController.php` — cities/areas, tags, flash-discounts, contests
- [ ] Add route definitions in `api/index.php` for all new endpoints
- [ ] Write database migration SQL (new tables + ALTER statements)

### React Customer App

- [ ] Scaffold `customer/` project with Vite + React + TS + Tailwind
- [ ] Configure `tailwind.config.ts` with brand tokens
- [ ] Create `src/api/client.ts` (Axios + JWT interceptors + refresh logic)
- [ ] Create all API endpoint files in `src/api/endpoints/`
- [ ] Create `authStore.ts` (Zustand) + `locationStore.ts`
- [ ] Build `AppShell.tsx` with responsive layout (mobile/tablet/desktop)
- [ ] Build `BottomTabBar.tsx` (mobile only)
- [ ] Build `Sidebar.tsx` (desktop only)
- [ ] Build `TopBar.tsx` with location picker + notification bell
- [ ] `AuthGuard.tsx` + protected route logic
- [ ] **Auth**: LoginPage, RegisterPage, OtpVerifyPage, ForgotPasswordPage, ResetPasswordPage
- [ ] **Onboarding**: City/Area selection (first login)
- [ ] **Home**: Ad carousel, flash discount chips, featured merchants, category grid, top coupons, blog row
- [ ] **Explore**: Search bar, filter drawer, merchant card grid (infinite scroll)
- [ ] **MerchantDetailPage**: Hero, tabs, coupon list, store cards, gallery, reviews
- [ ] **CouponWalletPage**: Saved tab, gift inbox tab, history tab
- [ ] **CouponDetailPage**: Info, T&C, QR display, redeem button, countdown timer
- [ ] **ProfilePage**: Stats, card display, menu
- [ ] **EditProfilePage**: All fields + photo upload
- [ ] **SubscriptionPage**: Plan card + renew flow
- [ ] **MyCardPage**: Card visual display
- [ ] **ActivityPage**: Unified hub with survey/mystery/contest sections
- [ ] **SurveyListPage + SurveyTakePage**: Progress, question types, submit
- [ ] **TaskListPage + TaskDetailPage**: Mystery shopping UI
- [ ] **ReferralPage**: Code display, share, stats, history
- [ ] **ContestListPage + ContestDetailPage**: Browse + participate
- [ ] **GrievanceListPage + GrievanceFormPage**: Submit + track
- [ ] **ReviewSubmitPage**: Rating + text + submit (from MerchantDetail)
- [ ] **NotificationsPage**: List + mark read + filter
- [ ] **MorePage**: Deal Maker section, blog, about
- [ ] Shared UI components: `MerchantCard`, `CouponCard`, `SkeletonCard`, `RatingStars`, `AdBanner`, `FlashBadge`, `Toast`
- [ ] PWA setup (vite-plugin-pwa)
- [ ] Responsive verification: mobile, tablet, desktop viewports
- [ ] All TanStack Query `queryFn` return `null` fallback (not `undefined`)
- [ ] `router.tsx` — all routes + lazy loading + auth guards
- [ ] `.env` and `.env.production` setup
- [ ] Build + verification (`npm run build && npm run preview`)
- [ ] Push to GitHub
