# Deal Machan — Merchant Application: Implementation Plan

---

## 1. Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Vite + React 18 + TypeScript |
| Styling | Tailwind CSS v3 (mobile-first, utility-first) |
| State Management | Zustand (lightweight, no boilerplate) |
| API Client | Axios + React Query (TanStack Query v5) |
| Routing | React Router v6 |
| Forms | React Hook Form + Zod validation |
| Charts | Recharts (lightweight, composable) |
| Icons | Lucide React |
| Animations | Framer Motion (page transitions, micro-interactions) |
| Notifications | react-hot-toast |
| Auth | JWT (access + refresh tokens), stored in memory + httpOnly cookie pattern |

---

## 2. API Implementation Plan (PHP REST — `e:\DealMachan\api\`)

### 2.1 API Infrastructure (Foundation — build first)

```
api/
├── index.php              ← Front controller / router
├── config/
│   ├── Database.php
│   ├── JWT.php
│   └── constants.php
├── middleware/
│   ├── AuthMiddleware.php  ← Validate JWT, inject $merchant
│   └── CorsMiddleware.php
├── helpers/
│   ├── Response.php        ← Standardised JSON response
│   └── Validator.php
└── controllers/
    ├── AuthController.php
    ├── MerchantController.php
    ├── StoreController.php
    ├── CouponController.php
    ├── AnalyticsController.php
    ├── GrievanceController.php
    ├── ReviewController.php
    ├── MessageController.php
    └── NotificationController.php
```

**Standardised API response format:**
```json
{ "success": true, "data": {}, "message": "OK", "meta": { "page": 1, "total": 50 } }
{ "success": false, "error": "INVALID_TOKEN", "message": "Token expired" }
```

---

### 2.2 Full API Endpoint Catalogue

#### Auth
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/merchant/login` | Login with email+password, returns access+refresh tokens |
| POST | `/api/auth/merchant/refresh` | Rotate access token using refresh token |
| POST | `/api/auth/merchant/logout` | Invalidate refresh token |
| POST | `/api/auth/merchant/forgot-password` | Send OTP/reset link |
| POST | `/api/auth/merchant/reset-password` | Reset with token |

#### Dashboard & Analytics
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/analytics/dashboard` | KPI cards: total coupons, redemptions today/week/month, active customers, avg rating, pending grievances |
| GET | `/api/merchants/analytics/redemptions` | Redemption trend by date range (`?from=&to=&period=7\|30\|90`) |
| GET | `/api/merchants/analytics/customers` | Customer demographics: city, profession, age group |
| GET | `/api/merchants/analytics/top-coupons` | Top performing coupons by redemption count |
| GET | `/api/merchants/analytics/revenue` | Sales registry summary |

#### Profile
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/profile` | Full merchant profile + stores summary + labels + subscription status |
| PUT | `/api/merchants/profile` | Update business name, description, contact info, category, tags |
| POST | `/api/merchants/profile/logo` | Upload/replace logo (`multipart/form-data`) |
| POST | `/api/merchants/subscription/renew` | Submit renewal request |
| POST | `/api/merchants/subscription/upgrade` | Request plan upgrade |

#### Stores
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/stores` | List all stores with address, status, coupon count |
| GET | `/api/merchants/stores/:id` | Full store detail (address, hours, gallery, active coupons) |
| POST | `/api/merchants/stores` | Create store |
| PUT | `/api/merchants/stores/:id` | Update store |
| DELETE | `/api/merchants/stores/:id` | Soft-delete store |
| POST | `/api/merchants/stores/:id/gallery` | Upload up to N gallery images |
| DELETE | `/api/merchants/stores/:id/gallery/:imageId` | Delete single gallery image |
| PUT | `/api/merchants/stores/:id/gallery/:imageId` | Set as cover/primary image |

#### Coupons
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/coupons` | List coupons (filter: status, store, date range) |
| GET | `/api/merchants/coupons/:id` | Coupon detail + redemption stats |
| POST | `/api/merchants/coupons` | Create coupon — fields: title, description, discount_type, discount_value, valid_from, valid_to, max_redemptions, store_id, tags |
| PUT | `/api/merchants/coupons/:id` | Update coupon (only draft/pending) |
| DELETE | `/api/merchants/coupons/:id` | Delete coupon |
| POST | `/api/merchants/coupons/scan-redeem` | **QR/code scan → validate + redeem in one call** — body: `{ code, customer_id?, store_id }` |
| POST | `/api/merchants/coupons/manual-redeem` | Redeem by entering coupon code manually |
| GET | `/api/merchants/coupons/:id/redemptions` | Redemption log for a coupon |

#### Store Coupons
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/store-coupons` | List store coupons |
| POST | `/api/merchants/store-coupons` | Create store coupon |
| PUT | `/api/merchants/store-coupons/:id` | Update |
| DELETE | `/api/merchants/store-coupons/:id` | Delete |
| POST | `/api/merchants/store-coupons/:id/gift` | Gift to customer by phone/customer ID |

#### Sales Registry
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/sales-registry` | Paginated list (`?store_id=&from=&to=&page=`) |
| POST | `/api/merchants/sales-registry` | Record a sale entry |
| GET | `/api/merchants/sales-registry/export` | CSV export |
| GET | `/api/merchants/sales-registry/summary` | Totals grouped by store / product |

#### Customers (Merchant-created)
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/customers` | List customers registered by this merchant |
| POST | `/api/merchants/customers` | Create customer account (name, phone, email optional) |

#### Grievances
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/grievances` | List grievances (`?status=open\|resolved\|in_progress`) |
| GET | `/api/merchants/grievances/:id` | Detail + thread |
| POST | `/api/merchants/grievances/:id/respond` | Post a response |
| PUT | `/api/merchants/grievances/:id/resolve` | Mark resolved |

#### Reviews
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/reviews` | List reviews with rating breakdown (1–5 counts) |
| GET | `/api/merchants/reviews/:id` | Single review |
| POST | `/api/merchants/reviews/:id/reply` | Post merchant reply |

#### Messages
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/messages` | Inbox with unread count |
| GET | `/api/merchants/messages/:id` | Thread |
| POST | `/api/merchants/messages` | New message to admin |
| PUT | `/api/merchants/messages/:id/read` | Mark as read |

#### Notifications
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/notifications` | List notifications (`?unread=1`) |
| PUT | `/api/merchants/notifications/read-all` | Mark all read |
| PUT | `/api/merchants/notifications/:id/read` | Mark single read |

#### Labels & Flash Discounts
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/merchants/labels` | Labels assigned + available to request |
| POST | `/api/merchants/labels/request` | Request a label |
| GET | `/api/merchants/flash-discounts` | List flash discounts |
| POST | `/api/merchants/flash-discounts` | Create flash discount (short-lived, time-boxed) |
| PUT | `/api/merchants/flash-discounts/:id` | Update |
| DELETE | `/api/merchants/flash-discounts/:id` | Delete |

---

## 3. Merchant Application — Screens & Navigation

### Bottom Tab Navigation (always visible — 5 tabs)
```
[Home]  [Coupons]  [⊕ Scan]  [Analytics]  [More]
```
The centre **Scan** button is a floating action — one tap to redeem any coupon.

---

### Screen Inventory

#### Authentication Flow
| Screen | Route | Notes |
|---|---|---|
| Splash | `/` | Logo + tagline, auto-redirect if token valid |
| Login | `/login` | Email/password, show/hide password, "Forgot password" link |
| Forgot Password | `/forgot-password` | Email input → OTP sent |
| Reset Password | `/reset-password` | New password + confirm |

#### Home (Dashboard)
| Screen | Route | Notes |
|---|---|---|
| Dashboard | `/home` | KPI cards (today's redemptions, total customers, avg rating, pending grievances), quick-action row, recent activity feed, mini chart (7-day redemptions) |

**Quick action row (one-tap):** Scan Coupon · Add Coupon · View Sales · New Message

#### Coupons
| Screen | Route | Notes |
|---|---|---|
| Coupon List | `/coupons` | Tabs: Active / Pending / Expired / All. Cards show title, discount, redemption count, progress bar to max redemptions |
| Coupon Detail | `/coupons/:id` | Stats, redemption log, QR code |
| Create/Edit Coupon | `/coupons/new`, `/coupons/:id/edit` | Step-form: basics → discount → schedule → store assignment → tags |
| Scan & Redeem | `/scan` | Camera QR scanner (primary flow) + manual code fallback → confirmation card → success animation |
| Store Coupons | `/store-coupons` | Separate tab in coupons section |
| Gift Store Coupon | `/store-coupons/:id/gift` | Search customer by phone → one-tap gift |

#### Stores
| Screen | Route | Notes |
|---|---|---|
| Store List | `/stores` | Cards with address, active coupon count, store status badge |
| Store Detail | `/stores/:id` | Gallery carousel, hours, active coupons list, edit button |
| Add/Edit Store | `/stores/new`, `/stores/:id/edit` | Name, address, city, area, location, phone, hours, operating days |
| Gallery Manager | `/stores/:id/gallery` | Photo grid, add/delete/set-as-cover |

#### Scan & Redeem (focal feature)
- Opens camera full-screen
- Real-time QR code detection
- On scan → API call → show result in bottom sheet:
  - ✅ Valid: customer name, coupon title, discount → **Confirm Redeem** button
  - ❌ Invalid: reason (expired / already redeemed / wrong merchant)
- Manual fallback: type 6-digit code

#### Analytics
| Screen | Route | Notes |
|---|---|---|
| Analytics Home | `/analytics` | Redemptions chart (7/30/90 day toggle), customer demographics doughnut, top 5 coupons bar chart |
| Sales Registry | `/analytics/sales` | Filterable table, daily/weekly/monthly grouping, export button |
| Customer List | `/analytics/customers` | Customers linked to this merchant, filter by city/status |

#### Profile & Settings
| Screen | Route | Notes |
|---|---|---|
| Profile | `/profile` | Business info, logo, subscription status badge, labels chips |
| Edit Profile | `/profile/edit` | All editable fields |
| Store Management | accessed from Profile | Link to `/stores` |
| Subscription | `/profile/subscription` | Current plan, expiry date, Renew / Upgrade buttons |
| Labels | `/profile/labels` | Assigned labels, request new label |

#### Grievances
| Screen | Route | Notes |
|---|---|---|
| Grievance List | `/grievances` | Tabs: Open / In Progress / Resolved. Sorted by urgency. |
| Grievance Detail | `/grievances/:id` | Message thread, Respond input, Resolve button |

#### Reviews
| Screen | Route | Notes |
|---|---|---|
| Reviews | `/reviews` | Rating summary (avg + bar breakdown), review cards with star rating, date, customer name (masked) |
| Review Detail | `/reviews/:id` | Full review + reply input |

#### Messages
| Screen | Route | Notes |
|---|---|---|
| Message List | `/messages` | Chat-style list, unread badge |
| Message Thread | `/messages/:id` | Bubble chat UI, send box fixed at bottom |
| New Message | `/messages/new` | Subject + body, send to admin |

#### Notifications
| Screen | Route | Notes |
|---|---|---|
| Notifications | `/notifications` | Grouped by Today / Earlier, tap to navigate to related screen, "Mark all read" |

#### More Menu
| Item | Action |
|---|---|
| Flash Discounts | `/flash-discounts` — create time-limited deals |
| Generate Customer | Quick form: name + phone → create customer account |
| Settings | App preferences (language, notification toggles) |
| Help & Support | Static guidance + contact link |
| Logout | Confirm → clear tokens → `/login` |

---

## 4. UI/UX Design Principles

### Visual Theme
- **Primary color:** `#667eea` → `#764ba2` gradient (matches admin panel — brand consistency)
- **Background:** Off-white `#F8FAFC` with white cards
- **Typography:** Inter (system font stack fallback)
- **Card style:** Rounded-2xl, soft shadow, subtle border
- **Feedback:** Every action has a loading state, success/error toast, and haptic-like micro-animation

### Mobile-First Patterns
- **Bottom sheet** for confirmations and quick-actions (not modals)
- **Pull-to-refresh** on all lists
- **Skeleton loaders** (not spinners) while data loads
- **Optimistic UI** on redeem: show success immediately, roll back on failure
- **Sticky headers** with card-based content below
- **FAB (Floating Action Button)** on Coupons and Stores for "Add New"
- **Swipe actions** on list items (e.g. swipe left → Respond on grievance)

### Navigation UX
- Bottom tab bar is always accessible — max 2 taps to any feature
- Scan button elevated in centre tab bar = primary action = zero friction redemption
- Back navigation uses the browser/OS native back gesture
- Deep-link support: notification tap → opens correct screen directly

### Performance
- React Query caches all GET responses — instant repeat loads
- Images lazy-loaded with blur placeholder
- Dashboard numbers animate (count-up) on first load
- Paginated infinite scroll for long lists (no page numbers)

---

## 5. Development Phases & Task List

### Phase 1 — API Foundation & Auth (Week 1)
- [ ] Set up `api/` folder structure, front controller, router
- [ ] `Response.php` helper (standardised JSON)
- [ ] `JWT.php` — generate, verify, refresh access/refresh token pair
- [ ] `CorsMiddleware.php` — allow merchant app origin
- [ ] `AuthMiddleware.php` — validate Bearer token, inject `$merchant_id`
- [ ] `POST /api/auth/merchant/login`
- [ ] `POST /api/auth/merchant/refresh`
- [ ] `POST /api/auth/merchant/logout`
- [ ] `POST /api/auth/merchant/forgot-password`
- [ ] `POST /api/auth/merchant/reset-password`
- [ ] Vite + React + TypeScript project scaffold (`merchant/`)
- [ ] Tailwind CSS, Zustand, React Query, React Router, Axios setup
- [ ] `AuthStore` (token management, auto-refresh on 401)
- [ ] Login screen, Forgot/Reset Password screens

### Phase 2 — Dashboard & Profile (Week 2)
- [ ] `GET /api/merchants/analytics/dashboard`
- [ ] `GET /api/merchants/profile`
- [ ] `PUT /api/merchants/profile`
- [ ] `POST /api/merchants/profile/logo`
- [ ] `GET /api/merchants/notifications`
- [ ] `PUT /api/merchants/notifications/read-all`
- [ ] Dashboard screen (KPI cards, mini chart, quick actions)
- [ ] Profile screen + Edit Profile screen
- [ ] Notification centre screen
- [ ] Subscription status display + renew/upgrade request buttons

### Phase 3 — Stores & Gallery (Week 2–3)
- [ ] Full Stores CRUD API (5 endpoints)
- [ ] Gallery upload/delete/set-cover API (3 endpoints)
- [ ] Store list screen
- [ ] Store detail screen (gallery carousel, hours, map link)
- [ ] Add/Edit store screen (with location picker)
- [ ] Gallery manager screen (drag-to-reorder optional)

### Phase 4 — Coupons & QR Scan (Week 3–4) ← Highest priority feature
- [ ] Full Coupons CRUD API
- [ ] `POST /api/merchants/coupons/scan-redeem` (core endpoint)
- [ ] `POST /api/merchants/coupons/manual-redeem`
- [ ] `GET /api/merchants/coupons/:id/redemptions`
- [ ] Store Coupons CRUD + gift endpoint
- [ ] Coupon list screen (tabs, FAB)
- [ ] Create/Edit coupon screen (multi-step form)
- [ ] Coupon detail screen + redemption log
- [ ] **Scan & Redeem screen** (camera QR + manual fallback + result bottom sheet)
- [ ] Store Coupon list + Gift flow

### Phase 5 — Analytics & Sales (Week 4–5)
- [ ] `GET /api/merchants/analytics/redemptions`
- [ ] `GET /api/merchants/analytics/customers`
- [ ] `GET /api/merchants/analytics/top-coupons`
- [ ] `GET /api/merchants/sales-registry` (paginated)
- [ ] `POST /api/merchants/sales-registry`
- [ ] `GET /api/merchants/sales-registry/export`
- [ ] Analytics home screen (charts, tabs)
- [ ] Sales registry screen (filterable list + export)
- [ ] Customer list screen

### Phase 6 — Grievances, Reviews & Messages (Week 5–6)
- [ ] Grievances CRUD API (4 endpoints)
- [ ] Reviews API (list + reply)
- [ ] Messages API (inbox, thread, send, read)
- [ ] Grievance list + detail + respond screen
- [ ] Reviews summary + detail + reply screen
- [ ] Messages inbox + thread screen + new message

### Phase 7 — Flash Discounts, Labels & Polish (Week 6–7)
- [ ] Flash Discounts CRUD API
- [ ] Labels list + request API
- [ ] `POST /api/merchants/customers` (generate customer account)
- [ ] Flash discounts screen
- [ ] Labels screen
- [ ] Generate customer screen
- [ ] Push notification wiring (if FCM/websocket available)
- [ ] Full error boundary and offline detection
- [ ] Accessibility audit (contrast, touch targets ≥44px)
- [ ] Performance profiling (React DevTools + Lighthouse)

### Phase 8 — Testing & Deployment (Week 7–8)
- [ ] API integration tests for all endpoints
- [ ] React component unit tests (Vitest + Testing Library)
- [ ] End-to-end test: Login → Scan → Redeem flow
- [ ] Cross-browser / cross-device testing (iOS Safari, Android Chrome)
- [ ] Production build optimisation (`vite build`, code splitting)
- [ ] Environment variable configuration (`.env.production`)
- [ ] Deployment to server / static host

---

## 6. Folder Structure

### API (`e:\DealMachan\api\`)
```
api/
├── index.php
├── .htaccess               ← Route all to index.php
├── config/
│   ├── constants.php
│   ├── Database.php
│   └── JWT.php
├── middleware/
│   ├── AuthMiddleware.php
│   └── CorsMiddleware.php
├── helpers/
│   ├── Response.php
│   └── Validator.php
├── models/                 ← Reuse/extend admin models
└── controllers/
    ├── Auth/
    │   └── MerchantAuthController.php
    ├── Merchant/
    │   ├── ProfileController.php
    │   ├── StoreController.php
    │   ├── CouponController.php
    │   ├── AnalyticsController.php
    │   ├── SalesController.php
    │   ├── GrievanceController.php
    │   ├── ReviewController.php
    │   ├── MessageController.php
    │   └── NotificationController.php
    └── Public/
        └── PublicController.php
```

### Merchant App (`e:\DealMachan\merchant\`)
```
merchant/
├── src/
│   ├── api/
│   │   ├── client.ts        ← Axios instance + interceptors
│   │   └── endpoints/       ← One file per domain
│   ├── store/               ← Zustand stores
│   │   ├── authStore.ts
│   │   └── uiStore.ts
│   ├── hooks/               ← React Query hooks
│   │   ├── useCoupons.ts
│   │   ├── useStores.ts
│   │   └── ...
│   ├── components/
│   │   ├── ui/              ← Button, Card, Badge, BottomSheet, Skeleton
│   │   ├── layout/          ← BottomTabBar, AppShell, PageHeader
│   │   └── domain/          ← CouponCard, StoreCard, RedemptionResult...
│   ├── pages/
│   │   ├── auth/
│   │   ├── home/
│   │   ├── coupons/
│   │   ├── stores/
│   │   ├── scan/
│   │   ├── analytics/
│   │   ├── grievances/
│   │   ├── reviews/
│   │   ├── messages/
│   │   ├── notifications/
│   │   └── profile/
│   ├── router.tsx
│   ├── App.tsx
│   └── main.tsx
├── public/
├── .env
├── .env.production
├── tailwind.config.ts
├── vite.config.ts
└── tsconfig.json
```

---

## 7. Priority Order Summary

| Priority | Deliverable | Rationale |
|---|---|---|
| 🔴 P0 | API infrastructure + Auth | Nothing else works without this |
| 🔴 P0 | Scan & Redeem (QR) | #1 daily-use feature for merchants |
| 🟠 P1 | Dashboard + Coupons CRUD | Core operational value |
| 🟠 P1 | Stores management | Required for coupon assignment |
| 🟡 P2 | Analytics + Sales | Business insights |
| 🟡 P2 | Grievances + Reviews | Customer relationship |
| 🟢 P3 | Messages + Notifications | Communication layer |
| 🟢 P3 | Flash Discounts + Labels | Advanced features |

---

**Estimated total:** 7–8 weeks for a complete, production-ready merchant application.
