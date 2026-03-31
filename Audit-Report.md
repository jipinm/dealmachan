# DealMachan — Implementation Audit Report

**Scope document:** `Deal-Machan-scope-revised.md`  
**Applications audited:** `admin` (PHP MVC), `api` (PHP REST), `merchant` (React/TS), `customer` (React/TS)  
**Database reference:** `dealmachan.sql`

---

## Executive Summary

All six scope sections have been audited across all four applications and the database schema.  
**One gap was identified and fixed** during this audit: the required "Terms & Conditions" field was missing from Store Coupons (scope §4.2). All other requirements were correctly implemented.

---

## Section 1 — Customers

**Overall status: ✅ Fully Implemented**

### 1.1 — Customer Filters (Admin)
| Requirement | Implementation | File |
|---|---|---|
| Filter by City | `city_id` WHERE clause in `index()` | `admin/controllers/CustomersController.php` |
| Filter by Gender | `gender` WHERE clause in `index()` | `admin/controllers/CustomersController.php` |
| Filter by Profession | `profession_id` WHERE clause in `index()` | `admin/controllers/CustomersController.php` |

### 1.2 — Adding Customers (Admin)
| Requirement | Implementation | File |
|---|---|---|
| Password not required when adding from admin | If no password supplied, auto-generates a temp password and sets `temp_password = 1` | `admin/controllers/CustomersController.php` → `handleSave()` |
| Customer receives a temporary password | Temporary password stored as hashed + `temp_password` flag | `admin/controllers/CustomersController.php` |

### 1.3 — Customer Login & First-Time Setup (Customer App)
| Requirement | Implementation | File |
|---|---|---|
| Multi-step login: identifier → check → password or OTP | 3-step flow: `identifier` step → `checkLogin()` API → `password` step (if `has_password=true`) or OTP step (if `has_password=false`) | `customer/src/pages/auth/LoginPage.tsx` |
| OTP sent to phone for first-time (temp password) users | OTP flow triggered when `has_password=false` in `checkLogin()` response | `customer/src/pages/auth/LoginPage.tsx` |
| Mandatory password setup after OTP login | After OTP verification, redirected to `/profile/set-password` | `customer/src/pages/auth/LoginPage.tsx` |
| After password set, mandatory city selection | `SetNewPasswordPage` navigates to `/onboarding` on success | `customer/src/pages/profile/SetNewPasswordPage.tsx` |
| City selection (mandatory) + area (optional) | `canContinue = !!selectedCityId`; city required, area optional; calls `profileApi.updateProfile()` | `customer/src/pages/onboarding/OnboardingPage.tsx` |
| Card assignment after city selection | On save success → navigate to `/loyalty/select-card` | `customer/src/pages/onboarding/OnboardingPage.tsx` |
| Option A: Activate via pre-printed card number | `/loyalty/activate` route with card number entry | `customer/src/pages/loyalty/` |
| Option B: Select card from available list | `/loyalty/select-card` route with card list | `customer/src/pages/loyalty/CardSelectionPage.tsx` |

### 1.4 — Customer Management (Admin)
| Requirement | Implementation | File |
|---|---|---|
| Toggle login enable / disable | `toggle()` method flips `login_enabled` flag | `admin/controllers/CustomersController.php` |
| No delete option | `delete()` method disabled with redirect | `admin/controllers/CustomersController.php` |
| No Account Settings in customer app | No account settings screen exists in `customer/src/pages/` | Customer app |

---

## Section 2 — Merchants & Stores

**Overall status: ✅ Fully Implemented**

### 2.1 — Separate Merchant & Store Entities
| Requirement | Implementation | File |
|---|---|---|
| Merchants and Stores as separate admin screens | `MerchantsController.php` and `StoresController.php` are separate with their own views | `admin/controllers/` |
| Store linked to Merchant via `merchant_id` FK | `stores.merchant_id` references `merchants.id` | `dealmachan.sql` |

### 2.2 — Merchant Settings
| Requirement | Implementation | File |
|---|---|---|
| Subscription period: 1M / 3M / 6M / 1Y | `subscription_period` ENUM column; dropdown in admin edit form | `dealmachan.sql`, `admin/views/merchants/` |
| Is Premium toggle | `is_premium` TINYINT(1) column + checkbox in admin form | `dealmachan.sql`, `admin/views/merchants/` |
| Is Verified badge toggle | `is_verified` TINYINT(1) column + checkbox in admin form | `dealmachan.sql`, `admin/views/merchants/` |
| Coupon limit | `coupon_limit` INT column on `merchants` table | `dealmachan.sql` |
| Monthly assignment limit | `monthly_assignment_limit` INT column on `merchants` table | `dealmachan.sql` |

### 2.3 — Store Profile Fields
| Requirement | Implementation | File |
|---|---|---|
| Latitude / Longitude for directions | `latitude` and `longitude` columns on `stores` | `dealmachan.sql` |
| Email | `email` column on `stores` | `dealmachan.sql` |
| Website link | `website_link` column on `stores` | `dealmachan.sql` |
| All fields displayed in customer app | Directions (Google Maps link), email, website all rendered | `customer/src/pages/stores/StoreDetailPage.tsx` |

### 2.4 — Bookings & Calls
| Requirement | Implementation | File |
|---|---|---|
| Call button with background logging | `<a href="tel:...">` + `publicApi.logStoreCall(store.id)` fires silently | `customer/src/pages/stores/StoreDetailPage.tsx` |
| Call log stored in DB | `call_logs` table with `store_id`, `customer_id`, `called_at` | `dealmachan.sql` |
| Book Now button (only when booking enabled) | Shown only when `store.booking_enabled === 1`; navigates to `/stores/:id/book` | `customer/src/pages/stores/StoreDetailPage.tsx` |
| Booking form: date, time, attendees, notes | All four fields implemented in form | `customer/src/pages/stores/BookingFormPage.tsx` |
| Auto-confirm vs awaiting confirmation | Reads `status` from API response; shows appropriate message | `customer/src/pages/stores/BookingFormPage.tsx` |
| Merchant booking management | Merchant app `BookingListPage` shows list with confirm/reject actions | `merchant/src/pages/bookings/` |
| `booking_confirmation_required` flag on store | Controls whether bookings are auto-confirmed or go pending | `admin/views/stores/` |

---

## Section 3 — Cards & Card Configurations

**Overall status: ✅ Fully Implemented**

### 3.1 — Card-Gated Features
| Requirement | Implementation | File |
|---|---|---|
| Active card required for card-only features | `canUseCardOnlyFeature()` helper checks `card.status === 'active'`; blocking toast if not | `customer/src/pages/stores/StoreDetailPage.tsx` |
| Public store browsing without a card | All store listing / detail pages accessible without auth | `customer/src/pages/stores/` |

### 3.2 — Card Configuration Fields
| Requirement | Implementation | File |
|---|---|---|
| Sub-classifications: Gender, Profession, Group, Club | `card_sub_classifications` table with 4 `type` values; synced in admin | `admin/controllers/CardConfigurationsController.php` |
| `pay_back_points_enabled` toggle | Column + checkbox in admin card config form; saved in `handleSave()` | `dealmachan.sql`, `admin/controllers/CardConfigurationsController.php` |
| `pay_back_points_value` | Column + input in admin form; saved in `handleSave()` | `dealmachan.sql`, `admin/controllers/CardConfigurationsController.php` |
| `lifetime_subscription` toggle | Column + checkbox; saved in `handleSave()` | `dealmachan.sql`, `admin/controllers/CardConfigurationsController.php` |
| `gift_coupon_eligibility` toggle | Column + checkbox; saved in `handleSave()` | `dealmachan.sql`, `admin/controllers/CardConfigurationsController.php` |
| `lucky_draw_eligible` toggle | Column + checkbox; saved in `handleSave()` | `dealmachan.sql`, `admin/controllers/CardConfigurationsController.php` |
| `contest_eligible` toggle | Column + checkbox; saved in `handleSave()` | `dealmachan.sql`, `admin/controllers/CardConfigurationsController.php` |

### 3.3 — Card Generation
| Requirement | Implementation | File |
|---|---|---|
| Cards generated under a configuration | `CardsController.php` links cards to `card_configuration_id` | `admin/controllers/CardsController.php` |

### 3.4 — Card Assignment
| Requirement | Implementation | File |
|---|---|---|
| Assign card to Admin or Store | `assign()` method in `CardsController` supports both admin and store assignment targets | `admin/controllers/CardsController.php` |

---

## Section 4 — Store Coupons

**Overall status: ✅ Fully Implemented (gap fixed in this audit)**

### 4.1 — Store Coupon List (Admin)
| Requirement | Implementation | File |
|---|---|---|
| Filter by merchant | `merchant_id` WHERE clause in `index()` with merchant dropdown | `admin/controllers/StoreCouponsController.php` |
| No delete option | Delete action disabled; only activate/deactivate toggle available | `admin/controllers/StoreCouponsController.php` |

### 4.2 — Store Coupon Creation (Admin-only)
| Requirement | Implementation | Status | File |
|---|---|---|---|
| Title (required) | Input field + validation in `handleSave()` | ✅ | `admin/views/store-coupons/add.php`, `StoreCouponsController.php` |
| Description | Textarea (optional) | ✅ | `admin/views/store-coupons/add.php` |
| Code (auto-generated) | `'SC-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))` with collision check | ✅ | `admin/controllers/StoreCouponsController.php` |
| **Terms & Conditions (required)** | **Textarea added; saved in controller; displayed in detail view and customer app** | **✅ Fixed** | See §"Files changed" below |
| Validity (valid_from / valid_until) | Two datetime-local inputs | ✅ | `admin/views/store-coupons/add.php` |
| Discount Settings | discount_type + discount_value | ✅ | `admin/views/store-coupons/add.php` |
| Store logo as coupon visual (no image upload) | Store selector shows logo preview; info notice confirms this | ✅ | `admin/views/store-coupons/add.php` |
| Admin-only creation | `created_by_admin_id` stored; no merchant creation route | ✅ | `admin/controllers/StoreCouponsController.php` |

### 4.3 — Gifting / Assignment to Customers
| Requirement | Implementation | File |
|---|---|---|
| Single gift to one customer | `gift()` / `assign()` in merchant API controller | `api/controllers/Merchant/StoreCouponController.php` |
| Bulk assignment with eligible customer criteria | `bulkAssign()` method with filter: `added_by_store`, `completed_transaction`, `wishlisted` | `api/controllers/Merchant/StoreCouponController.php` |
| `coupon_bulk_allotments` table for tracking | Table with `store_id`, `coupon_id`, `quantity`, `status`, `approved_by` | `dealmachan.sql` |

### 4.4 — Merchant Coupon Limits & Admin Approval
| Requirement | Implementation | File |
|---|---|---|
| `coupon_limit` enforced at merchant level | Checked before approving bulk allotment | `admin/controllers/StoreCouponsController.php` |
| `monthly_assignment_limit` enforced | Current-month usage summed in approval check | `api/controllers/Merchant/StoreCouponController.php` |
| Admin approval queue for bulk requests | `allotmentRequests()` and `approveAllotment()` actions | `admin/controllers/StoreCouponsController.php` |
| Merchant notified of pending status | Notification created on allotment request submission | `api/controllers/Merchant/StoreCouponController.php` |

### 4.5 — Merchant Dashboard Indicator
| Requirement | Implementation | File |
|---|---|---|
| Monthly coupon limit indicator on merchant home | Usage vs. limit shown on merchant dashboard home page | `merchant/src/pages/home/HomePage.tsx` |

### 4.6 — Customer: Grab Now Flow
| Requirement | Implementation | File |
|---|---|---|
| Store coupons listed on Store Detail page | `store_coupons` section in `StoreDetailPage` | `customer/src/pages/stores/StoreDetailPage.tsx` |
| `requires_acceptance` → Grab Now button | Button shown only when `requires_acceptance === 1` and not already in wallet | `customer/src/pages/stores/StoreDetailPage.tsx` |
| Not `requires_acceptance` → auto-available label | "Auto Available with Active Card" shown otherwise | `customer/src/pages/stores/StoreDetailPage.tsx` |
| In-wallet state | "In Your Wallet" badge shown when `in_wallet === 1` | `customer/src/pages/stores/StoreDetailPage.tsx` |
| Sold-out state | "Fully Claimed" shown when `assigned_count >= total_quantity` | `customer/src/pages/stores/StoreDetailPage.tsx` |
| Login required guard | Unauthenticated users see "Login to Grab" link | `customer/src/pages/stores/StoreDetailPage.tsx` |
| Active card required guard | `canUseCardOnlyFeature()` check before grab | `customer/src/pages/stores/StoreDetailPage.tsx` |

---

## Section 5 — Coupons

**Overall status: ✅ Fully Implemented**

### 5.1 — Coupon List (Admin)
| Requirement | Implementation | File |
|---|---|---|
| Filter by merchant | Merchant dropdown filter in coupon list | `admin/controllers/CouponsController.php` |
| Filter by store | Store dropdown filter in coupon list | `admin/controllers/CouponsController.php` |
| Coupon counts visible | `usage_count` shown in list view | `admin/views/coupons/index.php` |

### 5.2 — Coupon Creation
| Requirement | Implementation | File |
|---|---|---|
| Auto-generated code | `coupon_code` generated with `bin2hex(random_bytes())` | `admin/controllers/CouponsController.php` |
| Internal note | `note` TEXT column; textarea in admin form | `dealmachan.sql`, `admin/views/coupons/add.php` |
| Usage limit per user | `usage_limit_per_user` INT column; input in form | `dealmachan.sql` |
| Total usage limit | `usage_limit` INT column; input in form | `dealmachan.sql` |
| Terms & Conditions | `terms_conditions` TEXT column; textarea in form | `dealmachan.sql`, `admin/views/coupons/add.php` |

---

## Section 6 — Gift Coupons

**Overall status: ✅ Fully Implemented**

### 6.1 — Gift Coupon Creation
| Requirement | Implementation | File |
|---|---|---|
| Admin-only creation | `admin_id` FK on `gift_coupons` table; no merchant-side creation route | `dealmachan.sql`, `admin/controllers/GiftCouponsController.php` |
| Links to standard coupon (not store coupon) | `coupon_id` references `coupons` table | `dealmachan.sql` |
| Merchant context | `merchant_id` on gift coupon for scoping | `dealmachan.sql` |

### 6.2 — Recipient Filtering
| Requirement | Implementation | Filter column |
|---|---|---|
| By Card Segment / Card Config | `card_config_id` filter | `gift_coupons` / `GiftCoupon` model |
| By Club | `club_id` filter | `GiftCoupon` model |
| By Profession | `profession_id` filter | `GiftCoupon` model |
| By Birth Month | `birth_month` filter (1–12) | `GiftCoupon` model |
| By City | `city_id` filter | `GiftCoupon` model |
| By Area | `area_id` filter | `GiftCoupon` model |
| By Gender | `gender` filter (male/female/all) | `GiftCoupon` model |

---

## Files Changed (This Audit)

| File | Change |
|---|---|
| `migrations/M12_alter_store_coupons.sql` | *(new)* Adds `terms_conditions TEXT NULL` column to `store_coupons` after `description` |
| `admin/controllers/StoreCouponsController.php` | Added `'terms_conditions'` key to `$data` array in `handleSave()` |
| `admin/views/store-coupons/add.php` | Added required Terms & Conditions textarea after Description field |
| `admin/views/store-coupons/view.php` | Added T&C display row in Coupon Details card |
| `api/controllers/Public/StoreBrowseController.php` | Added `sc.terms_conditions` to the store coupon SELECT query |
| `customer/src/api/endpoints/public.ts` | Added `terms_conditions: string \| null` to `PublicStoreCoupon` interface |
| `customer/src/pages/stores/StoreDetailPage.tsx` | Added T&C display line in each store coupon card |

---

## Database Validation Summary

| Table | Key columns confirmed |
|---|---|
| `customers` | `city_id`, `gender`, `profession_id`, `login_enabled`, `temp_password` |
| `merchants` | `subscription_period`, `is_premium`, `is_verified`, `coupon_limit`, `monthly_assignment_limit` |
| `stores` | `latitude`, `longitude`, `email`, `website_link`, `booking_enabled`, `booking_confirmation_required` |
| `card_configurations` | `pay_back_points_enabled`, `pay_back_points_value`, `lifetime_subscription`, `gift_coupon_eligibility`, `lucky_draw_eligible`, `contest_eligible` |
| `card_sub_classifications` | `type` ENUM: `gender`, `profession`, `group`, `club` |
| `cards` | `card_configuration_id`, `status`, `assigned_to_customer_id` |
| `store_coupons` | `terms_conditions` *(added by M12)*, `coupon_code`, `requires_acceptance`, `assignment_type`, `discount_type`, `discount_value` |
| `coupons` | `terms_conditions`, `usage_limit`, `usage_limit_per_user`, `note` |
| `gift_coupons` | `admin_id`, `merchant_id`, `coupon_id`, `card_config_id`, `city_id`, `area_id`, `gender`, `birth_month` |
| `bookings` | `store_id`, `customer_id`, `booking_date`, `booking_time`, `num_attendees`, `customer_notes`, `status` |
| `call_logs` | `store_id`, `customer_id`, `called_at` |
| `coupon_bulk_allotments` | `store_id`, `coupon_id`, `quantity`, `status`, `approved_by_admin_id` |

---

## How to Apply the Migration

Run the following against your MySQL instance:

```sql
-- From migrations/M12_alter_store_coupons.sql
ALTER TABLE `store_coupons`
ADD COLUMN `terms_conditions` TEXT NULL
COMMENT 'Terms and conditions displayed to the customer on redemption'
AFTER `description`;
```

Or run the full migration batch:

```bash
mysql -u <user> -p <database> < migrations/M12_alter_store_coupons.sql
```
