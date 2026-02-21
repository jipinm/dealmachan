# Merchant App — Gap Analysis & Implementation Plan

> **Date:** 2026-02-21 (Updated: Implementation Complete)  
> **Scope:** Compare legacy functional spec (`merchant-functional-spec.md`) against the new merchant app (`merchant/`)  
> **Goal:** Ensure all required old features are covered in the new application  
> **Status:** ✅ ALL GAPS IMPLEMENTED

---

## 1. Feature Coverage Summary

| # | Legacy Feature | New App Status | Notes |
|---|----------------|:-:|-------|
| 1.1 | **Login** | ✅ Covered | `LoginPage.tsx` — email/password, JWT auth, session guard |
| 1.2 | **Session Guard** | ✅ Covered | `AuthGuard.tsx` — redirect to `/login` if unauthenticated |
| 1.3 | **Logout** | ✅ Covered | `authStore.ts` — `logout()` invalidates refresh token |
| 2 | **Dashboard** | ✅ Covered | `HomePage.tsx` — welcome msg, KPI cards, weekly chart |
| 3.1 | **List Stores/Units** | ✅ Covered | `StoreListPage.tsx` — grid cards with cover, name, status |
| 3.2 | **View Store Detail** | ✅ Covered | `StoreDetailPage.tsx` — cover, info, hours, gallery link |
| 3.3 | **Edit Store** | ✅ Covered | `StoreFormPage.tsx` — name, address, city/area, contacts |
| 3.4 | **Edit Working Hours** | ⚠️ Partial | Hours are in the store JSON field, but the StoreFormPage has **no working hours editor** |
| 3.5 | **Gallery Management** | ✅ Covered | `GalleryPage.tsx` — upload, delete, set cover. Missing: **reorder** |
| 4.1 | **Create Platform Coupon** | ✅ Covered | `CouponFormPage.tsx` — full form with discount type, store, dates |
| 4.2 | **List Platform Coupons** | ✅ Covered | `CouponListPage.tsx` — tabbed, status badges, pagination |
| 4.3 | **Edit Platform Coupon** | ✅ Covered | `CouponFormPage.tsx` (edit mode) |
| 5.1 | **Store Coupons (CRUD)** | ❌ **Missing** | No store coupon management (distinct from platform coupons) |
| 5.2 | **Assign Store Coupon** | ❌ **Missing** | No assign/gift store coupon to customer |
| 5.3 | **Bulk Assign Store Coupons** | ❌ **Missing** | No bulk assign functionality |
| 5.4 | **List Store Coupons** | ❌ **Missing** | No store coupon listing |
| 6.1 | **Search Customer for Redemption** | ⚠️ Partial | `ScanRedeemPage.tsx` — can search by code/QR, optional customer phone, but does **NOT show customer's redeemable coupons list** |
| 6.2 | **Redeem Platform Coupon** | ✅ Covered | `couponApi.manualRedeem()` / `scanRedeem()` |
| 6.3 | **Redeem Flash Discount** | ❌ **Missing** | No flash discount redemption flow |
| 6.4 | **Redeem Store Coupon** | ❌ **Missing** | No store coupon redemption |
| 7.1 | **Customer List + Analytics** | ⚠️ Partial | `CustomersPage.tsx` — list with search/pagination, but **no analytics** (transaction count, frequency, response rate, date filtering) |
| 7.2 | **Add Customer** | ✅ Covered | `CreateCustomerModal` in `CustomersPage.tsx` |
| 7.3 | **Edit Customer** | ❌ **Missing** | No edit customer functionality |
| 8.1 | **Messages Inbox** | ✅ Covered | `MessageListPage.tsx` — list with unread indicators |
| 8.2 | **Reply to Message** | ✅ Covered | `MessageThreadPage.tsx` — thread view with reply |
| 9 | **Access Control (account_type)** | ❌ **Missing** | No role-based feature gating per account type |

---

## 2. Detailed Gap Analysis

### GAP 1: Working Hours Editor (Store Form) — Priority: HIGH

**What's missing:** The `StoreFormPage.tsx` allows editing name, address, city/area, phone, email, and description but has **no UI for setting opening/closing times per day**. The database `stores.opening_hours` JSON field exists and `StoreDetailPage` can display it, but there's no way to edit it.

**Legacy behavior:** Legacy had a dedicated `edit-working-hours.php` page with 7 day rows (Mon–Sun), each with an open/close time dropdown and is_open checkbox.

**Database:** `stores.opening_hours` — JSON column with day keys (e.g., `{"mon": "9:00-18:00", "tue": "9:00-18:00", ...}`)

---

### GAP 2: Gallery Reorder — Priority: LOW

**What's missing:** `GalleryPage.tsx` supports upload, delete, and set-cover but not reordering images via drag-and-drop.

**Legacy behavior:** Legacy used AJAX reorder to update `image_order` column.

**Database:** `store_gallery.display_order` exists.

---

### GAP 3: Store Coupons (Complete Module) — Priority: HIGH

**What's missing:** The entire "Store Coupons" feature set is absent from the new app. Store coupons are merchant-specific coupons that can be assigned/gifted to individual customers. This is distinct from platform coupons.

**Legacy features:**
- Generate store coupon (title, terms, expiry, target units)
- List store coupons with assignment status
- Assign store coupon to specific customer
- Bulk assign store coupons (up to 10 customers at once)

**Database:** `store_coupons` table exists with `is_gifted`, `gifted_to_customer_id`, `gifted_at`, `is_redeemed`, `redeemed_at` columns.

**Required:**
- New API endpoint file: `src/api/endpoints/storeCoupons.ts`
- New pages: `StoreCouponListPage.tsx`, `StoreCouponFormPage.tsx`, `StoreCouponAssignPage.tsx`
- Router entries for `/store-coupons`, `/store-coupons/new`, `/store-coupons/:id`, `/store-coupons/:id/assign`

---

### GAP 4: Customer Search & Coupon Lookup for Redemption — Priority: HIGH

**What's missing:** The Scan/Redeem page redeems by coupon code only. Legacy allows the merchant to **search a customer first** (by phone or card number) and then see all their redeemable coupons (subscribed platform coupons, assigned store coupons, and flash discounts) — then select which one to redeem.

**Legacy behavior:**
1. Enter customer phone/card number → search
2. Display 3 tabs: Subscribed Platform Coupons, Assigned Store Coupons, Flash Discounts
3. Merchant picks one and redeems

**Required:**
- Add a "Customer Lookup" tab/mode to `ScanRedeemPage.tsx`
- API endpoint to fetch a customer's redeemable items
- UI for selecting and redeeming from the customer's coupon list

---

### GAP 5: Flash Discount Redemption — Priority: MEDIUM

**What's missing:** The `FlashDiscountPage.tsx` allows CRUD management of flash discounts, but there is **no redemption flow** for flash discounts. The merchant should be able to redeem a flash discount for a customer.

**Legacy behavior:** `redeem-flash-discount.php` takes discount_id, customer_id, transaction_value and creates a redemption record.

**Database:** `flash_discount_redemptions` table would need to exist (or use `coupon_redemptions` with appropriate type).

**Required:**
- Add a "Redeem" action to flash discount cards or integrate into ScanRedeemPage
- API endpoint for flash discount redemption

---

### GAP 6: Store Coupon Redemption — Priority: HIGH

**What's missing:** No way to redeem store coupons. Tied to GAP 3 above.

**Legacy behavior:** Merchant selects a store coupon assignment → marks it as redeemed.

**Required:** Part of the Store Coupons module implementation (GAP 3).

---

### GAP 7: Customer Analytics — Priority: MEDIUM

**What's missing:** `CustomersPage.tsx` lists customers with basic info (name, phone, email, type) but has **no transaction analytics** per customer.

**Legacy analytics per customer:**
- Total coupon assignments count
- Total transaction count
- Total transaction value
- Highest single transaction
- Last visit date
- Average frequency (days between visits)
- Response rate (redeemed / assigned %)
- Date range filtering
- Sort by name/transactions/highest/last visit/response rate

**Required:**
- Enhance `merchantCustomerApi` to return analytics data
- Add customer detail page or expand row with analytics
- Date range filter component
- Sort dropdown

---

### GAP 8: Edit Customer — Priority: MEDIUM

**What's missing:** `CustomersPage.tsx` has a create modal but no edit capability.

**Legacy behavior:** `edit-customer.php` / `update-customer.php` allows updating name, phone, email of merchant-created customers.

**Required:**
- Add `update` method to `merchantCustomerApi`
- Add edit modal or page
- Link from customer row

---

### GAP 9: Access Control by Account Type — Priority: MEDIUM

**What's missing:** The legacy app restricts features based on `account_type_id`:
- Any type: Login, Dashboard, Units, Platform Coupons, Redemption, Messages
- `>= 3`: Store Coupons, Customer List, Edit Customer
- `== 4`: Add Customer

The new app shows all features to all authenticated merchants with no role-based visibility.

**Required:**
- Store merchant role/account_type in auth state
- Feature-gate navigation items and pages based on role
- API returns merchant permissions/role on login

---

## 3. Implementation Plan

### Phase 1: Critical Missing Features (Store Coupons + Enhanced Redemption)

#### Task 1.1: Store Coupons Module
**Files to create:**
- `src/api/endpoints/storeCoupons.ts` — API client (list, create, update, get, assign, bulkAssign)
- `src/pages/storeCoupons/StoreCouponListPage.tsx` — List with status tabs
- `src/pages/storeCoupons/StoreCouponFormPage.tsx` — Create/edit form
- `src/pages/storeCoupons/StoreCouponAssignPage.tsx` — Assign to customer(s) with search

**Router additions:**
```tsx
{ path: '/store-coupons',             element: wrap(<StoreCouponListPage />) },
{ path: '/store-coupons/new',         element: wrap(<StoreCouponFormPage />) },
{ path: '/store-coupons/:id',         element: wrap(<StoreCouponDetailPage />) },
{ path: '/store-coupons/:id/edit',    element: wrap(<StoreCouponFormPage />) },
{ path: '/store-coupons/:id/assign',  element: wrap(<StoreCouponAssignPage />) },
```

**Navigation:** Add "Store Coupons" to `MorePage.tsx` under Business section.

**API endpoints needed (backend):**
- `GET    /merchants/store-coupons` — list merchant's store coupons
- `POST   /merchants/store-coupons` — create new store coupon
- `GET    /merchants/store-coupons/:id` — get detail with assignments
- `PUT    /merchants/store-coupons/:id` — update
- `POST   /merchants/store-coupons/:id/assign` — assign to customer(s)
- `POST   /merchants/store-coupons/:id/bulk-assign` — bulk assign

#### Task 1.2: Enhanced Redemption Flow (Customer Lookup)
**Files to modify:**
- `src/pages/scan/ScanRedeemPage.tsx` — Add third mode: "Customer Lookup"
- `src/api/endpoints/coupons.ts` — Add `lookupCustomer(phone_or_card)` method

**New component:** `CustomerRedeemPanel` — shows customer info + 3 sections:
1. Subscribed platform coupons → redeem button
2. Assigned store coupons → redeem button
3. Active flash discounts → redeem button

**API endpoints needed (backend):**
- `GET /merchants/redemption/customer-lookup?search=<phone_or_card>` — returns customer + redeemable items

#### Task 1.3: Flash Discount Redemption
**Files to modify:**
- `src/api/endpoints/flashDiscounts.ts` — Add `redeem(id, payload)` method
- `src/pages/more/FlashDiscountPage.tsx` — Add "Redeem" button on active discounts

**API endpoints needed (backend):**
- `POST /merchants/flash-discounts/:id/redeem` — redeem for customer

#### Task 1.4: Store Coupon Redemption
Part of Task 1.2 (Customer Lookup panel) — selecting a store coupon from the customer's list triggers redemption.

---

### Phase 2: Store Management Enhancements

#### Task 2.1: Working Hours Editor
**Files to modify:**
- `src/pages/stores/StoreFormPage.tsx` — Add a "Working Hours" section with 7 day rows

**Implementation:**
- Add an `opening_hours` field to the form schema
- For each day (Mon–Sun): checkbox "Open?", time selectors for open/close
- Serialize to JSON: `{ "mon": "09:00-18:00", "tue": "09:00-18:00", ... }` or `{ "mon": "Closed" }`
- Include in `CreateStorePayload` and `UpdateStorePayload`

#### Task 2.2: Gallery Reorder (Optional Enhancement)
**Files to modify:**
- `src/pages/stores/GalleryPage.tsx` — Add drag-and-drop reorder
- `src/api/endpoints/stores.ts` — Add `reorderGallery(storeId, imageIds[])` method

**Implementation:**
- Use `@dnd-kit/core` or similar for drag & drop
- On reorder complete, send ordered image IDs to backend

---

### Phase 3: Customer Management Enhancements

#### Task 3.1: Customer Analytics
**Files to modify:**
- `src/pages/more/CustomersPage.tsx` — Expand view with analytics
- `src/api/endpoints/customers.ts` — Enhance response type with analytics fields

**New components:**
- `CustomerDetailPage.tsx` — Full customer view with analytics cards
- Date range picker component for filtering analytics period
- Sort controls (name, transactions, frequency, response rate)

**API endpoints needed (backend):**
- `GET /merchants/customers/:id/analytics?from=&to=` — returns per-customer analytics

#### Task 3.2: Edit Customer
**Files to modify:**
- `src/api/endpoints/customers.ts` — Add `update(id, payload)` method
- `src/pages/more/CustomersPage.tsx` — Add edit button per row, open edit modal

---

### Phase 4: Access Control

#### Task 4.1: Role-Based Feature Gating
**Files to modify:**
- `src/store/authStore.ts` — Store `account_type` / role from login response
- `src/api/endpoints/auth.ts` — Add `account_type` to `MerchantProfile`
- `src/components/layout/BottomTabBar.tsx` — Conditionally show tabs
- `src/pages/more/MorePage.tsx` — Conditionally show menu items
- `src/components/layout/AuthGuard.tsx` — Optional: role-based route guard

**Access matrix to implement:**

| Feature | Any | >= 3 | == 4 |
|---------|-----|------|------|
| Store Coupons | ❌ | ✅ | ✅ |
| Customer List | ❌ | ✅ | ✅ |
| Add Customer | ❌ | ❌ | ✅ |

---

## 4. Features in New App NOT in Old Spec (Acceptable Additions)

The following features exist in the new app but were not in the legacy specification. These are **new enhancements** and do not need removal:

| Feature | Location | Notes |
|---------|----------|-------|
| Reviews management | `ReviewListPage`, `ReviewDetailPage` | Reply to customer reviews |
| Grievances management | `GrievanceListPage`, `GrievanceDetailPage` | Respond to & resolve grievances (evolved from legacy "messages") |
| Notifications | `NotificationsPage` | Push notifications with mark-read |
| Analytics dashboard | `AnalyticsPage` | Charts, top coupons, customer geo |
| Sales registry | `SalesPage` | Record sales, export CSV |
| Flash Discounts CRUD | `FlashDiscountPage` | Full management (legacy had admin-created only) |
| Labels management | `LabelsPage` | Request & view merchant labels |
| Profile management | `ProfilePage`, `EditProfilePage` | Business details, logo upload |
| Subscription management | `SubscriptionPage` | View plan, renew, upgrade |
| Forgot/Reset password | `ForgotPasswordPage`, `ResetPasswordPage` | Self-service password recovery |
| QR code scanning | `ScanRedeemPage` | Camera-based QR scan (legacy was manual only) |

---

## 5. Priority & Effort Summary

| Priority | Gap | Estimated Effort |
|----------|-----|-----------------|
| **HIGH** | Store Coupons Module (GAP 3) | 3–4 days |
| **HIGH** | Customer Lookup Redemption (GAP 4) | 2 days |
| **HIGH** | Store Coupon Redemption (GAP 6) | 1 day (with GAP 3) |
| **HIGH** | Working Hours Editor (GAP 1) | 0.5 day |
| **MEDIUM** | Flash Discount Redemption (GAP 5) | 0.5 day |
| **MEDIUM** | Customer Analytics (GAP 7) | 2 days |
| **MEDIUM** | Edit Customer (GAP 8) | 0.5 day |
| **MEDIUM** | Access Control (GAP 9) | 1–2 days |
| **LOW** | Gallery Reorder (GAP 2) | 0.5 day |
| | **Total estimated** | **~10–12 days** |
