# DealMachan — Task-by-Task Implementation Plan

> **Scope Reference:** Deal-Machan-scope-revised.md  
> **Generated After Full Codebase + DB Analysis**

---

## Legend

| Tag | Meaning |
|-----|---------|
| **DB** | Database migration required |
| **Admin** | Admin panel (PHP MVC) |
| **API** | PHP REST API layer |
| **Merchant** | React merchant app |
| **Customer** | React customer app |
| 🔴 Critical | Foundation/blocker for other tasks |
| 🟠 High | Major feature gap |
| 🟡 Medium | Improvement/enhancement |
| 🟢 Low | Minor fix / cleanup |

---

## Phase 0: Confirmed Current State

### What Already Exists (Do Not Recreate)
- `stores` table already has `email`, `latitude`, `longitude` columns ✅
- `customers` table already has `gender`, `profession_id`, `city_id`, `area_id` columns ✅
- `gift_coupons` table exists with `requires_acceptance` / `acceptance_status` support ✅
- `store_coupon_limits` table exists (but is empty and unused) ✅
- `coupon_bulk_allotments` table exists (but is empty and unused) ✅
- `merchants` table has `is_premium`, `subscription_status`, `subscription_expiry` ✅

### Confirmed Missing from Database
- `merchants`: `is_verified`, `subscription_period`, `coupon_limit`, `monthly_assignment_limit`
- `stores`: `website_link`, `booking_enabled`, `booking_confirmation_required`
- `card_configurations`: `pay_back_points_enabled`, `pay_back_points_value`, `lifetime_subscription`, `gift_coupon_eligibility`, `lucky_draw_eligible`, `contest_eligible`
- `cards`: `assigned_to_store_id`
- `coupons`: `note`, `usage_limit_per_user`
- `store_coupons`: `title`, `description`, `created_by_admin_id`, `requires_acceptance`, `total_quantity`, `assignment_type`
- `card_config_sub_class_map`: `gender_filter`, `profession_ids`
- New tables: `bookings`, `call_logs`, `gift_coupon_batches`

---

## Phase 1: Database Migrations (🔴 Must Be Done First)

All migrations must be applied before any application code changes.

---

### Migration M1 — `merchants` table: add missing columns

**Priority:** 🔴 Critical  
**Reason:** Required by Tasks B2, B3, B4, D3

```sql
ALTER TABLE `merchants`
  ADD COLUMN `is_verified` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Verified Partner badge toggle'
    AFTER `is_premium`,
  ADD COLUMN `subscription_period` enum('1M','3M','6M','1Y') DEFAULT '1Y'
    COMMENT 'Duration of current subscription; used to calculate expiry'
    AFTER `subscription_expiry`,
  ADD COLUMN `coupon_limit` int(10) unsigned DEFAULT NULL
    COMMENT 'Total store-coupon assignment cap for this merchant; NULL = unlimited'
    AFTER `subscription_period`,
  ADD COLUMN `monthly_assignment_limit` int(10) unsigned DEFAULT NULL
    COMMENT 'Monthly cap on store-coupon assignments; NULL = unlimited'
    AFTER `coupon_limit`,
  ADD KEY `idx_is_verified` (`is_verified`);
```

---

### Migration M2 — `stores` table: add missing columns

**Priority:** 🔴 Critical  
**Reason:** Required by Tasks B5, B6, B7, B8

```sql
ALTER TABLE `stores`
  ADD COLUMN `website_link` varchar(255) DEFAULT NULL
    COMMENT 'Public website URL of the store'
    AFTER `email`,
  ADD COLUMN `booking_enabled` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = Book Now button is visible on customer app store profile'
    AFTER `description`,
  ADD COLUMN `booking_confirmation_required` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = booking status starts as pending; 0 = auto-confirmed'
    AFTER `booking_enabled`;
```

---

### Migration M3 — `card_configurations` table: add feature toggle columns

**Priority:** 🔴 Critical  
**Reason:** Required by Task C1

```sql
ALTER TABLE `card_configurations`
  ADD COLUMN `pay_back_points_enabled` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card earns pay-back points on purchases'
    AFTER `coupon_authorization`,
  ADD COLUMN `pay_back_points_value` decimal(5,2) DEFAULT NULL
    COMMENT 'Points rate (% of purchase); active only when pay_back_points_enabled = 1'
    AFTER `pay_back_points_enabled`,
  ADD COLUMN `lifetime_subscription` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card has no expiry / lifetime validity'
    AFTER `validity_days`,
  ADD COLUMN `gift_coupon_eligibility` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders can receive gift coupons'
    AFTER `lifetime_subscription`,
  ADD COLUMN `lucky_draw_eligible` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders are eligible for lucky draws'
    AFTER `gift_coupon_eligibility`,
  ADD COLUMN `contest_eligible` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders can participate in contests'
    AFTER `lucky_draw_eligible`;
```

---

### Migration M4 — `cards` table: add `assigned_to_store_id`

**Priority:** 🔴 Critical  
**Reason:** Required by Task C6

```sql
ALTER TABLE `cards`
  ADD COLUMN `assigned_to_store_id` int(10) unsigned DEFAULT NULL
    COMMENT 'If card batch is assigned to a store for distribution'
    AFTER `assigned_to_merchant_id`,
  ADD KEY `idx_assigned_store` (`assigned_to_store_id`),
  ADD CONSTRAINT `fk_cards_store`
    FOREIGN KEY (`assigned_to_store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL;
```

---

### Migration M5 — `coupons` table: add new fields

**Priority:** 🟠 High  
**Reason:** Required by Task E2

```sql
ALTER TABLE `coupons`
  ADD COLUMN `note` text DEFAULT NULL
    COMMENT 'Internal admin note (not shown to customers)'
    AFTER `terms_conditions`,
  ADD COLUMN `usage_limit_per_user` int(11) DEFAULT NULL
    COMMENT 'Max times a single customer can use this coupon; NULL = unlimited'
    AFTER `usage_limit`;
```

---

### Migration M6 — `store_coupons` table: add admin-management columns

**Priority:** 🔴 Critical  
**Reason:** Required by Tasks D1, D2

```sql
ALTER TABLE `store_coupons`
  ADD COLUMN `title` varchar(255) DEFAULT NULL
    COMMENT 'Display name of the store coupon'
    AFTER `id`,
  ADD COLUMN `description` text DEFAULT NULL
    AFTER `title`,
  ADD COLUMN `created_by_admin_id` int(10) unsigned DEFAULT NULL
    COMMENT 'Admin who created this coupon (NULL = legacy merchant-created)'
    AFTER `description`,
  ADD COLUMN `requires_acceptance` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = customer must actively Grab the coupon; 0 = auto-assigned'
    AFTER `gifted_at`,
  ADD COLUMN `total_quantity` int(10) unsigned DEFAULT NULL
    COMMENT 'Total coupons available for assignment; NULL = unlimited'
    AFTER `requires_acceptance`,
  ADD COLUMN `assigned_count` int(10) unsigned NOT NULL DEFAULT 0
    COMMENT 'Running count of times assigned to customers'
    AFTER `total_quantity`,
  ADD COLUMN `assignment_type` enum('auto_assign','merchant_request') NOT NULL DEFAULT 'merchant_request'
    COMMENT 'auto_assign = customer gets on card activation; merchant_request = merchant requests via admin queue'
    AFTER `assigned_count`,
  ADD KEY `idx_created_by_admin` (`created_by_admin_id`),
  ADD CONSTRAINT `fk_sc_admin`
    FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
```

---

### Migration M7 — New table: `bookings`

**Priority:** 🟠 High  
**Reason:** Required by Task B8

```sql
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time DEFAULT NULL,
  `num_attendees` int(11) NOT NULL DEFAULT 1,
  `customer_notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `merchant_notes` text DEFAULT NULL,
  `confirmed_by_user_id` int(10) unsigned DEFAULT NULL COMMENT 'Store user who confirmed/rejected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_booking_date` (`booking_date`),
  CONSTRAINT `fk_bookings_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Migration M8 — New table: `call_logs`

**Priority:** 🟠 High  
**Reason:** Required by Task B7

```sql
CREATE TABLE IF NOT EXISTS `call_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `initiated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_initiated_at` (`initiated_at`),
  CONSTRAINT `fk_cl_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cl_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Migration M9 — New table: `gift_coupon_batches` + update `gift_coupons`

**Priority:** 🟠 High  
**Reason:** Required by Task F1 (bulk gift coupon creation with filters)

```sql
-- Batch record for bulk gifting operations
CREATE TABLE IF NOT EXISTS `gift_coupon_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `coupon_id` int(10) unsigned NOT NULL,
  `filter_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    COMMENT 'JSON snapshot of filters applied: card_segment, club_id, profession_ids, birth_month, city_id, area_id, gender'
    CHECK (json_valid(`filter_criteria`)),
  `total_recipients` int(11) NOT NULL DEFAULT 0,
  `requires_acceptance` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_coupon_id` (`coupon_id`),
  CONSTRAINT `fk_gcb_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gcb_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link individual gift_coupons records to a batch
ALTER TABLE `gift_coupons`
  ADD COLUMN `batch_id` int(10) unsigned DEFAULT NULL
    COMMENT 'References gift_coupon_batches.id for bulk-gifted records; NULL = single direct gift'
    AFTER `id`,
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD CONSTRAINT `fk_gc_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `gift_coupon_batches` (`id`) ON DELETE SET NULL;
```

---

### Migration M10 — `card_config_sub_class_map`: add Gender/Profession filters

**Priority:** 🟡 Medium  
**Reason:** Required by Tasks C3, C4

```sql
ALTER TABLE `card_config_sub_class_map`
  ADD COLUMN `gender_filter` enum('male','female','both') DEFAULT NULL
    COMMENT 'Only used when sub_class_id points to Gender sub-classification'
    AFTER `sub_class_id`,
  ADD COLUMN `profession_ids` text DEFAULT NULL
    COMMENT 'Comma-separated profession IDs when sub_class_id is Profession; NULL = all professions';
```

---

### Migration M11 — `customer_points` tables (if Pay-back Points tracking needed)

**Priority:** 🟡 Medium  
**Reason:** Required by Task H2 — only needed if full points wallet is in scope

```sql
CREATE TABLE IF NOT EXISTS `customer_points` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `points_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_id` (`customer_id`),
  CONSTRAINT `fk_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_points_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `points` decimal(10,2) NOT NULL,
  `transaction_type` enum('earn','redeem') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'e.g. coupon_redemption',
  `reference_id` int(10) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_cpt_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

---

## Phase 2: Task Group A — Customer Management

---

### Task A1 — Admin: Customer List Filters (City, Gender, Profession)

**Priority:** 🟡 Medium  
**Apps:** Admin  
**DB:** No migration needed (columns already exist)

**Changes Required:**

**`admin/controllers/CustomersController.php` — `index()` method:**
1. Read new filter params: `$city_id`, `$gender`, `$profession_id`
2. Add to SQL query:
   - `WHERE c.city_id = ?` (if $city_id set)
   - `WHERE c.gender = ?` (if $gender set)
   - `WHERE c.profession_id = ?` (if $profession_id set)
3. Pass `$cities`, `$professions` lists to view for dropdown population

**`admin/views/customers/index.php`:**
1. Add City dropdown filter (populated from `cities` table)
2. Add Gender dropdown filter (Male / Female / Other)
3. Add Profession dropdown filter (populated from `professions` table)
4. Ensure filter values are preserved on page reload (set `selected` on current values)

---

### Task A2 — Admin: Add Customer Without Password

**Priority:** 🟡 Medium  
**Apps:** Admin  
**DB:** No migration needed (`temp_password` column already exists)

**Current State:** Admin add-customer form likely prompts for password.  
**Scope Requirement:** Admin creates customer profile without setting a password; customer logs in via OTP.

**Changes Required:**

**`admin/controllers/CustomersController.php` — `handleSave()` (add flow):**
1. Remove password requirement when `$isNew === true`
2. When creating: generate a random temporary password (store hashed) and set `temp_password = 1`
3. OR: set `password = NULL` and handle NULL password in auth — customer must always use OTP
4. Do not show password field in admin add-customer form view

**`admin/views/customers/form.php` (add):**
1. Remove the Password input field from the add form entirely
2. Keep it visible (optional) only on edit, so admin can reset if needed

---

### Task A3 — Admin: Remove "Customer Type" from Customer UI

**Priority:** 🟢 Low  
**Apps:** Admin  
**DB:** Keep `customer_type` column (used elsewhere); remove from UI only

**Changes Required:**

**`admin/views/customers/form.php`:**
1. Remove the `customer_type` select dropdown from add/edit form

**`admin/views/customers/index.php`:**
1. Remove customer_type column from the list table (or make it a hidden stats column)

---

### Task A4 — Customer App: Mandatory City Selection on First Login / Registration

**Priority:** 🟠 High  
**Apps:** Customer, API  
**DB:** No migration needed (`city_id` already on customers table)

**Changes Required:**

**`api/controllers/Customer/ProfileController.php` (or AuthController.php):**
1. After OTP login, check if `customers.city_id IS NULL`
2. Return a flag in login response: `"city_required": true`
3. Add `PATCH /api/customer/profile/city` endpoint (or use existing profile update)

**Customer App `src/` (key areas):**
1. In auth context / login callback: if `city_required === true`, redirect to `/onboarding/city-select`
2. Create `CitySelectPage.tsx` component:
   - Fetch `GET /api/public/cities` for city list
   - Large selectable city tiles or dropdown
   - On submit: `PATCH /api/customer/profile` with `{ city_id }`
   - Redirect to home after saving

---

### Task A5 — Customer App: OTP-Only Login for Customers Without Password

**Priority:** 🟡 Medium  
**Apps:** Customer, API

**Note:** Verify whether this is already implemented. If `temp_password = 1` customers are already forced to OTP, this task may only require UI verification.

**Changes Required:**

**`api/controllers/Auth/` (or Customer auth flow):**
1. On login attempt with phone/email: check `temp_password` flag on user
2. If `temp_password = 1` or `password IS NULL`: reject password login, force OTP pathway
3. Existing OTP endpoints should already exist; verify they work for this case

**Customer App:**
1. On login page: if server returns `"otp_only": true`, show OTP input only (no password field)
2. After successful OTP login with `temp_password = 1`: prompt user to set a new password (optional or skip)

---

---

## Phase 2: Task Group B — Merchant Management

---

### Task B1 — Admin: Merchant List — Add City Filter

**Priority:** 🟡 Medium  
**Apps:** Admin  
**DB:** No migration needed

**Background:** Merchants don't have a direct `city_id`; stores do. A merchant's "city" is derived from their stores.

**Changes Required:**

**`admin/controllers/MerchantsController.php` — `index()` method:**
1. Add `$city_id = $_GET['city_id'] ?? null`
2. Update query to `LEFT JOIN stores s ON s.merchant_id = m.id` + `WHERE s.city_id = ?`
3. Use `GROUP BY m.id` to avoid duplicate rows
4. Pass `$cities` list to the view

**`admin/views/merchants/index.php`:**
1. Add City dropdown filter at top of merchant list
2. Preserve selected value on reload

---

### Task B2 — Admin: Merchant Form — Subscription Period Selector

**Priority:** 🟠 High  
**Apps:** Admin  
**DB:** Migration M1 required (adds `subscription_period`)

**Changes Required:**

**`admin/views/merchants/form.php`:**
1. Add subscription period selector: `<select name="subscription_period">` with options 1M / 3M / 6M / 1Y (default 1Y)
2. Show selector only when subscription_status = 'active' is chosen

**`admin/controllers/MerchantsController.php` — `handleSave()`:**
1. Read `$_POST['subscription_period']` (default '1Y')
2. Save to `merchants.subscription_period`
3. Auto-calculate `subscription_expiry` from today + period:
   - 1M → `date('Y-m-d', strtotime('+1 month'))`
   - 3M → `date('Y-m-d', strtotime('+3 months'))`
   - 6M → `date('Y-m-d', strtotime('+6 months'))`
   - 1Y → `date('Y-m-d', strtotime('+1 year'))`

---

### Task B3 — Admin: Merchant Form — Premium + Verified Toggles

**Priority:** 🟠 High  
**Apps:** Admin  
**DB:** Migration M1 required (adds `is_verified`)

**Current State:** `is_premium` exists as a tinyint. `label_id` is used for labels.  
**Scope Requirement:** Separate "Is Premium" and "Is Verified Partner" toggle checkboxes.

**Changes Required:**

**`admin/views/merchants/form.php`:**
1. Add `<input type="checkbox" name="is_premium">` — "Mark as Premium Merchant"
2. Add `<input type="checkbox" name="is_verified">` — "Mark as Verified Partner"
3. Labels should display as badges: "⭐ Premium" and "✓ Verified Partner"
4. Keep label_id field but make it secondary/optional

**`admin/controllers/MerchantsController.php` — `handleSave()`:**
1. Add `'is_verified' => isset($_POST['is_verified']) ? 1 : 0` to save data
2. Ensure `is_premium` already saves (confirm it does)

**`admin/views/merchants/index.php`:**
1. Show Premium badge and Verified badge in merchant list columns (based on new flags, not label_id)

---

### Task B4 — Admin: Merchant Form — Coupon Assignment Limits

**Priority:** 🟠 High  
**Apps:** Admin  
**DB:** Migration M1 required (adds `coupon_limit`, `monthly_assignment_limit`)

**Changes Required:**

**`admin/views/merchants/form.php`:**
1. Add "Total Store Coupon Limit" input: `<input type="number" name="coupon_limit" min="0">`  
   Help text: "Max total store coupons this merchant can have assigned. Leave blank for unlimited."
2. Add "Monthly Assignment Limit" input: `<input type="number" name="monthly_assignment_limit" min="0">`  
   Help text: "Max store coupons merchant can request per month. Leave blank for unlimited."

**`admin/controllers/MerchantsController.php` — `handleSave()`:**
1. Save `coupon_limit`: `$_POST['coupon_limit'] !== '' ? (int)$_POST['coupon_limit'] : null`
2. Save `monthly_assignment_limit`: same null-coalescing pattern

---

### Task B5 — Admin: Store Form — Website Link

**Priority:** 🟡 Medium  
**Apps:** Admin  
**DB:** Migration M2 required (adds `website_link`)

**Changes Required:**

**`admin/views/merchants/` (store add/edit form section):**
1. Add `<input type="url" name="website_link" placeholder="https://">` — "Store Website"

**`admin/controllers/MerchantsController.php`** (or wherever store save is handled):
1. Save `website_link` with basic URL validation (`filter_var($url, FILTER_VALIDATE_URL)`)

**API — Store detail response:**
1. Include `website_link` in the store data returned by relevant API endpoints (merchant + public store detail)

---

### Task B6 — Admin: Store Form — Booking Configuration

**Priority:** 🟠 High  
**Apps:** Admin  
**DB:** Migration M2 required

**Changes Required:**

**`admin/views/merchants/` (store add/edit form section):**
1. Add toggle: "Enable Book Now button on customer app" → `<input type="checkbox" name="booking_enabled">`
2. Add toggle (shown when booking is enabled): "Require merchant confirmation for bookings" → `<input type="checkbox" name="booking_confirmation_required">`  
   Help text: "If checked, customer booking status starts as 'pending' until you confirm."

**`admin/controllers/MerchantsController.php`** (or store save handler):
1. Save `booking_enabled`: `isset($_POST['booking_enabled']) ? 1 : 0`
2. Save `booking_confirmation_required`: `isset($_POST['booking_confirmation_required']) ? 1 : 0`

---

### Task B7 — Customer App + API + DB: Store Profile — Call Button

**Priority:** 🟠 High  
**Apps:** Customer, API  
**DB:** Migration M8 required (`call_logs` table)

**Changes Required:**

**Customer App — Store Detail / Profile page:**
1. Render a "Call" button only if `store.phone` is not null
2. On click: open `tel:{store.phone}` link (native phone dialer on mobile)
3. Simultaneously fire API log call in background (non-blocking):
   ```typescript
   // Fire-and-forget
   apiFetch(`/customer/stores/${storeId}/call-log`, { method: 'POST' });
   ```

**`api/controllers/Customer/StoreController.php` (or new endpoint):**
1. Add `POST /customer/stores/{id}/call-log` action:
   - Get `store_id` from URL param
   - Get authenticated `customer_id` from JWT
   - Get `phone_number` from stores table
   - Insert into `call_logs`: `(store_id, customer_id, phone_number)`
   - Return `200 OK`

---

### Task B8 — Customer App + Merchant App + API + DB: Book Now Button

**Priority:** 🟠 High  
**Apps:** Customer, Merchant, API  
**DB:** Migration M7 required (`bookings` table)

**Changes Required:**

**Customer App — Store Detail / Profile page:**
1. Show "Book Now" button only if `store.booking_enabled === true`
2. On click: open Booking Modal / navigate to Booking Form page
3. Booking form fields:
   - Date picker (no past dates)
   - Time picker (optional)
   - Number of attendees (number input, min 1)
   - Notes / special requests (textarea, optional)
4. On submit: call `POST /api/customer/stores/{storeId}/bookings`
5. If `booking_confirmation_required === false`: show "Booking Confirmed!" 
6. If `booking_confirmation_required === true`: show "Booking Requested — Awaiting Confirmation"
7. Add `/bookings` page to customer app to list customer's bookings (status tracking)

**Customer App API calls:**
- `POST /api/customer/stores/{id}/bookings` — create booking
- `GET /api/customer/bookings` — list my bookings (with status)
- `DELETE /api/customer/bookings/{id}` — cancel booking

**`api/controllers/Customer/BookingController.php` (new file):**
1. `create(storeId)` — validate store exists + booking_enabled, insert into bookings, handle auto-confirm vs pending
2. `index()` — list bookings for current customer (with store name, date, status)
3. `cancel(id)` — set status = 'cancelled' (only if status = 'pending' or 'confirmed')

**Merchant App — New Bookings section:**
1. Add route `/bookings` to merchant app
2. List bookings for all stores of the merchant
3. Filter by store / status / date
4. Actions:
   - "Confirm" button → `PATCH /api/merchant/bookings/{id}/confirm`
   - "Reject" button → `PATCH /api/merchant/bookings/{id}/reject`
   - "Mark Complete" button → `PATCH /api/merchant/bookings/{id}/complete`
5. Show booking details: customer name, date/time, attendees, notes

**`api/controllers/Merchant/BookingController.php` (new file):**
1. `index()` — list bookings for merchant's stores with filters
2. `confirm(id)` — set status = 'confirmed', set confirmed_by_user_id
3. `reject(id)` — set status = 'rejected', save merchant_notes
4. `complete(id)` — set status = 'completed'

---

---

## Phase 3: Task Group C — Card Configurations & Cards

---

### Task C1 — Admin + API + Customer: Card Config — New Feature Toggle Fields

**Priority:** 🔴 Critical  
**Apps:** Admin, API, Customer  
**DB:** Migration M3 required

**Admin `admin/controllers/CardConfigurationsController.php` — `handleSave()`:**

Add to `$data` array:
```php
'pay_back_points_enabled' => isset($_POST['pay_back_points_enabled']) ? 1 : 0,
'pay_back_points_value'   => isset($_POST['pay_back_points_enabled']) && $_POST['pay_back_points_value'] !== ''
                              ? (float)$_POST['pay_back_points_value']
                              : null,
'lifetime_subscription'   => isset($_POST['lifetime_subscription']) ? 1 : 0,
'gift_coupon_eligibility' => isset($_POST['gift_coupon_eligibility']) ? 1 : 0,
'lucky_draw_eligible'     => isset($_POST['lucky_draw_eligible']) ? 1 : 0,
'contest_eligible'        => isset($_POST['contest_eligible']) ? 1 : 0,
```

**Admin — Card Config View (form):**
Add the following toggles (checkboxes) to the card configuration form:
1. "Pay Back Points" checkbox → if checked, show "Points Rate (%)" number input beside it
2. "Lifetime Subscription" checkbox → if checked, grey out / hide validity_days field
3. "Gift Coupon Eligibility" checkbox
4. "Lucky Draw Eligible" checkbox
5. "Contest Eligible" checkbox

**API — Card Config detail endpoint (wherever card config data is returned):**
1. Include all 6 new fields in the JSON response

**Customer App:**
1. On the active card display page / card detail page:
   - Show "Pay Back Points: X%" badge if `pay_back_points_enabled = true`
   - Show "Lifetime" badge instead of expiry if `lifetime_subscription = true`
   - Show eligibility icons for gift coupons, lucky draw, contests when respective flags are true

---

### Task C2 — Admin: Remove Delete Button from Card Configurations

**Priority:** 🟢 Low (but required by scope)  
**Apps:** Admin  

**Scope rule:** "No delete option should exist anywhere."

**`admin/controllers/CardConfigurationsController.php`:**
1. Remove the `delete()` / `handleDelete()` action entirely
2. Replace with: only `status` toggle (active/inactive)

**`admin/views/card_configurations/index.php`:**
1. Remove the "Delete" button from the action column
2. Ensure "Toggle Status" is the only destructive action available

**`admin/routes/` (or router):**
1. Remove any route that maps to a delete action for card configurations

---

### Task C3 — Admin + API: Card Config — Gender Sub-classification M/F Split

**Priority:** 🟡 Medium  
**Apps:** Admin, API  
**DB:** Migration M10 required

**`admin/views/card_configurations/form.php`:**
1. When "Gender" sub-classification checkbox is ticked, show an additional selector:
   - Radio group: "All Genders" / "Male Only" / "Female Only"
   - Maps to `gender_filter`: `'both'` / `'male'` / `'female'`

**`admin/controllers/CardConfigurationsController.php` — `handleSave()`:**
1. When saving sub-classification map: include `gender_filter` value for the Gender entry

**API:**
1. Include `gender_filter` in sub-classification details when returning card config data

---

### Task C4 — Admin + API: Card Config — Profession from Master Data

**Priority:** 🟡 Medium  
**Apps:** Admin, API  
**DB:** Migration M10 required

**`admin/controllers/CardConfigurationsController.php`:**
1. In `edit()`/`add()` method: load professions list from `professions` table and pass to view
2. In `handleSave()`: save selected profession IDs comma-separated into `card_config_sub_class_map.profession_ids`

**`admin/views/card_configurations/form.php`:**
1. When "Profession" sub-classification checkbox is ticked, show multi-select box with professions from DB
2. Default: "All Professions" (null / empty = all)

---

### Task C5 — Admin: Cards Generate — Remove Non-Business Parameters

**Priority:** 🟡 Medium  
**Apps:** Admin  

**Read `admin/controllers/CardsController.php` generate action first to see current fields.**

**Expected Changes:**
1. In the card generation form, remove free-form `parameters_json` fields that are not business-relevant
2. Keep only: quantity, card_configuration_id, is_preprinted checkbox
3. Card variant name should auto-derive from the linked card configuration's `classification` (silver/gold/platinum/diamond)

---

### Task C6 — Admin: Cards Assign — Support Store OR Admin Assignment

**Priority:** 🟠 High  
**Apps:** Admin  
**DB:** Migration M4 required (`assigned_to_store_id` on `cards`)

**`admin/controllers/CardsController.php` — assign action:**
1. Add assignment type selector: "Assign to: [Merchant ▾] [Store ▾] [Admin ▾]"
2. When "Store" is selected: show store selector (searchable dropdown loaded from stores table)
3. On save: set `assigned_to_store_id = $storeId`, clear `assigned_to_merchant_id`, set `status = 'assigned'`
4. When "Admin" is selected: show admin selector, set `assigned_to_admin_id`
5. When "Merchant" is selected: existing flow unchanged

**`admin/views/cards/assign.php`:**
1. Dynamic form section that switches input fields based on assignment target type
2. JavaScript show/hide logic for the three assignment modes

---

---

## Phase 4: Task Group D — Store Coupons

---

### Task D1 — Admin + Merchant App: Store Coupons — Admin-Only Creation

**Priority:** 🔴 Critical  
**Apps:** Admin, Merchant, API  
**DB:** Migration M6 required

**Background:**  
Current flow: Merchants create store coupons from merchant app.  
Required flow: Only Admins create store coupons; merchants can request assignments.

**Admin `admin/controllers/StoreCouponsController.php` — `handleSave()`:**
1. `created_by_admin_id` = current admin ID (always set on create)
2. New required field: `title` — display name of the store coupon
3. No image upload — `store_image` from the `stores` table is used as the coupon's visual
4. `assignment_type`: dropdown "Auto-assign to customers on card activation" OR "Merchant must request"
5. `requires_acceptance`: checkbox — "Customer must actively Grab this coupon" (Grab Now behavior)
6. `total_quantity`: optional number field — max times this coupon can be assigned

**Admin view — Store Coupons form:**
1. Title field (required)
2. Store selector (which store this coupon belongs to, shows store logo preview)
3. Discount type + value (existing)
4. Valid from / until dates (existing)
5. Assignment type: Auto-assign / Merchant Request
6. Requires Acceptance toggle (Grab Now)
7. Total Quantity (optional)
8. No image upload input

**Merchant App:**
1. Remove any "Create Store Coupon" flow from merchant app
2. Merchant app store coupons section: read-only list of admin-created coupons for their stores
3. For `assignment_type = 'merchant_request'` coupons: show "Request Assignment" button (see Task D3)

---

### Task D2 — Customer App + API: Store Coupon Acceptance (Grab Now)

**Priority:** 🟠 High  
**Apps:** Customer, API

**Flow when `requires_acceptance = 1`:**
1. Customer sees store coupon listed as "Available — Grab Now"
2. Customer taps "Grab Now" button
3. Coupon is assigned to customer (`is_gifted = 1, gifted_to_customer_id = customerID`)
4. Counter `assigned_count` incremented

**Flow when `requires_acceptance = 0` (auto-assign):**
1. On card activation: coupon automatically added to customer's wallet
2. OR: shown to customer as already available (no grab action needed)

**API changes:**
1. `POST /api/customer/store-coupons/{id}/grab` — grab a store coupon
   - Check customer has active card
   - Check `requires_acceptance = 1`
   - Check `total_quantity` not exceeded (if set)
   - Set `is_gifted = 1, gifted_to_customer_id, gifted_at = NOW()`
   - Increment `assigned_count`
   - Return success

**Customer App:**
1. Store profile page: list available store coupons
2. "Grab Now" button for `requires_acceptance = 1` coupons
3. Show "In Your Wallet" label for already-grabbed coupons

---

### Task D3 — Admin + Merchant App + API: Store Coupon Assignment Limits + Approval Queue

**Priority:** 🔴 Critical  
**Apps:** Admin, Merchant, API  
**DB:** Migration M1 required (merchant limits); existing `coupon_bulk_allotments` table used

**For `assignment_type = 'merchant_request'` coupons:**

**Merchant App:**
1. Store coupon detail page: show "Request Bulk Assignment" button
2. Request form: enter quantity requested
3. Show current month usage: "X / Y coupons requested this month"
4. On submit: `POST /api/merchant/store-coupons/{id}/request-assignment` with `{ quantity: N }`

**`api/controllers/Merchant/StoreCouponController.php`:**
1. `requestAssignment(couponId)` action:
   - Validate merchant's `monthly_assignment_limit` not exceeded (sum of this month's approved allotments)
   - Validate against `merchants.coupon_limit` total cap
   - Insert into `coupon_bulk_allotments` with `status = 'pending'`
   - Return `201 Created`

**Admin — Bulk Allotment Approval page:**
1. List pending allotment requests from `coupon_bulk_allotments`
2. Show: merchant name, store, coupon, quantity requested, date
3. "Approve" button → set `status = 'approved'`, `reviewed_by`, `reviewed_at`; trigger customer allotment
4. "Reject" button → set `status = 'rejected'`, show review note input  
5. On approve: create `coupon_allotment_items` records for target customers (or trigger background process)

---

### Task D4 — Merchant App + API: Store Dashboard — Coupon Limit Indicators

**Priority:** 🟡 Medium  
**Apps:** Merchant, API

**Merchant App — Store overview / dashboard:**
1. Show "Store Coupons This Month: X / Y" progress indicator
2. X = approved allotments this moth; Y = `merchants.monthly_assignment_limit`
3. If no limit set: "Unlimited"

**API — Add to merchant stores response or dashboard endpoint:**
```json
{
  "coupon_usage": {
    "this_month": 12,
    "monthly_limit": 50,
    "total_limit": 200,
    "total_used": 45
  }
}
```

---

---

## Phase 5: Task Group E — Coupons

---

### Task E1 — Admin: Coupon List Filters

**Priority:** 🟡 Medium  
**Apps:** Admin

**Verify `admin/controllers/CouponsController.php` index() currently handles:**
- Status filter ✓ (likely exists)
- Merchant name text search (verify / add if missing)
- Category filter (verify / add if missing — JOIN `coupon_categories`)
- City filter (verify / add if missing — JOIN `coupon_city_targets`)

**Changes Required (add any missing filters):**
1. Merchant name: `LIKE` search on `merchants.business_name`
2. Category: `WHERE EXISTS (SELECT 1 FROM coupon_categories cc WHERE cc.coupon_id = c.id AND cc.category_id = ?)`
3. City: `WHERE EXISTS (SELECT 1 FROM coupon_city_targets cct WHERE cct.coupon_id = c.id AND cct.city_id = ?)`

**Admin coupon list view:**
- Add missing filter controls for category and city if not already present

---

### Task E2 — Admin: Coupon Form — `note` + `usage_limit_per_user` + Auto-Generate Code

**Priority:** 🟡 Medium  
**Apps:** Admin  
**DB:** Migration M5 required

**`admin/controllers/CouponsController.php` — `handleSave()`:**
1. Add `'note' => sanitize($_POST['note'] ?? null)` to `$data`
2. Add `'usage_limit_per_user' => $_POST['usage_limit_per_user'] !== '' ? (int)$_POST['usage_limit_per_user'] : null`

**Admin — Coupon form view:**
1. Add "Internal Note" textarea field (below Terms & Conditions; not customer-facing)
2. Add "Usage Limit Per User" number input (help text: "Leave blank for unlimited per-user usage")
3. Auto-generate coupon code feature:
   - Add "Auto-Generate" button beside the coupon code input
   - On click (JavaScript): `fetch('/admin/coupons/generate-code')` → fill input with returned unique code
4. Add corresponding admin action `generateCode()` in CouponsController: returns a unique 8-character alphanumeric code verified against DB

---

---

## Phase 6: Task Group F — Gift Coupons

---

### Task F1 — Admin: Gift Coupon Bulk Creation with Recipient Filters

**Priority:** 🟠 High  
**Apps:** Admin  
**DB:** Migration M9 required

**Scope:** Admin creates gift coupons selecting recipients based on 7 filter criteria simultaneously.

**`admin/controllers/GiftCouponsController.php` — new `create()` flow:**

Step 1 — Choose Coupon:
- Dropdown of active, approved regular coupons to gift

Step 2 — Apply Recipient Filters (all optional; combined with AND):
1. **Card Segment:** dropdown of card config classifications (silver / gold / platinum / diamond)
   - Query: `JOIN cards ca ON ca.assigned_to_customer_id = cu.id JOIN card_configurations cc ON cc.id = ca.card_configuration_id WHERE cc.classification = ?`
2. **Club:** multi-select of clubs (sub_classifications where type = 'Club')
   - Query customers linked to cards with those club sub-classifications
3. **Profession:** multi-select from `professions` table
   - Query: `WHERE cu.profession_id IN (?)`
4. **Birth Month / Important Days:** dropdown of months (Jan–Dec)
   - Query: `WHERE MONTH(cu.date_of_birth) = ?`
5. **City:** dropdown from `cities` table
   - Query: `WHERE cu.city_id = ?`
6. **Area:** dropdown (cascades from city selection)
   - Query: `WHERE cu.area_id = ?`
7. **Gender:** dropdown (Male / Female / Both)
   - Query: `WHERE cu.gender = ?` (for Male/Female), or no clause for Both

Step 3 — Preview:
- "Preview Recipients" button → AJAX call to `POST /admin/gift-coupons/preview` with filter JSON
- Returns count + sample of matching customer names/emails

Step 4 — Configure:
- `requires_acceptance` toggle: "Customer must accept gift coupon"

Step 5 — Confirm & Send:
- Insert into `gift_coupon_batches`: admin_id, coupon_id, filter_criteria (JSON), total_recipients, requires_acceptance
- For each matching customer: insert into `gift_coupons` with batch_id, customer_id, coupon_id, requires_acceptance

**Admin view — Gift Coupon form:**
1. Wizard-style form (4 steps as above)
2. Real-time recipient count preview via AJAX
3. Filter combinations summary table before confirmation

---

### Task F2 — Customer App + API: Gift Coupon Accept/Reject Flow

**Priority:** 🟠 High  
**Apps:** Customer, API

**Customer App:**
1. In notifications: show "You have a pending gift coupon from DealMachan"
2. In `/coupons` or new `/gift-coupons` section: list pending gift coupons
3. For `requires_acceptance = 1` gifts: show "Accept" and "Reject" buttons
4. For `requires_acceptance = 0` gifts: auto-show as active in wallet

**API:**
1. `GET /api/customer/gift-coupons` — list gift coupons for current customer
   - Include acceptance status, coupon details, expiry
2. `POST /api/customer/gift-coupons/{id}/accept` — set `acceptance_status = 'accepted'`, `accepted_at = NOW()`
3. `POST /api/customer/gift-coupons/{id}/reject` — set `acceptance_status = 'rejected'`

---

---

## Phase 7: Task Group G — Guest Access + Card Enforcement

---

### Task G1 — Customer App: Card-Gated Access Enforcement

**Priority:** 🔴 Critical  
**Apps:** Customer

**Scope requirement:** Most features require an active card. Guest/unactivated-card users should see a "Get Your Card" prompt.

**Current State:** Cards are assigned via `customers.card_id`. Card is active when `cards.status = 'activated'`.

**Changes Required:**

**Customer App `src/context/AuthContext.tsx` (or equivalent store):**
1. Add `hasActiveCard: boolean` to auth state
2. After login/profile load: check if `customer.card_id` is set AND card status is `'activated'`
3. Expose `hasActiveCard` via context

**Customer App — Protected route wrapper or per-page guard:**

Features that need card:
- Coupons list (save/redeem)
- Store Profile actions (book, call, view coupons)
- Gift Coupons wallet
- Contests participation
- Lucky Draw entry
- Flash Discount redemption

Features accessible without card (guest/preview):
- Home page (merchant listings, search)
- Store listing / browse
- Blog posts
- About / CMS pages

**Implementation:**
1. Create `CardRequiredGuard` component: checks `hasActiveCard`, if false renders `<NoCardPrompt />`
2. `NoCardPrompt` component: "You need a DealMachan card to access this feature" + CTA button "Learn How to Get a Card"
3. Wrap card-gated routes/sections with `<CardRequiredGuard>`

---

---

## Phase 8: Task Group H — Pay-Back Points Display

---

### Task H1 — Customer App: Pay-Back Points Display on Card

**Priority:** 🟡 Medium  
**Apps:** Customer, API  
**DB:** Migration M3 (pay_back_points columns on card_configurations) + optionally M11

**Scope:** When `pay_back_points_enabled = 1` is configured on a card configuration, a pay-back points section is displayed on the customer's active card.

**API changes (card detail endpoint):**
1. Include in card + card_configuration response:
   - `pay_back_points_enabled`
   - `pay_back_points_value`

**Customer App — Active Card page:**
1. If `card.configuration.pay_back_points_enabled === true`:
   - Show a "Pay Back Points" section
   - Display: "Earn {pay_back_points_value}% on every purchase at participating merchants"
   - If M11 (points transactions) is implemented: show `points_balance` from `customer_points`
   - If M11 not yet implemented: show static rate info only

---

---

## Phase 9: Task Group I — Admin Cleanups

---

### Task I1 — Admin: Remove All Delete Buttons Globally

**Priority:** 🟡 Medium  
**Apps:** Admin

**Scope rule:** "No delete option should exist anywhere."

**Controllers + Views – Remove delete functionality from:**
- `CardConfigurationsController.php` — ✅ already planned in Task C2
- `CardsController.php` — remove delete action; status toggle = only option
- `CustomersController.php` — remove delete action (confirm current state — audit log shows admin deleting customers, which should stop)
- `MerchantsController.php` — remove hard-delete; replace with status toggle only
- `StoreCouponsController.php` — remove delete; use status inactive
- `CouponsController.php` — remove delete; use status inactive/expired

> **Note:** Soft-delete via `deleted_at` timestamp may already exist on `stores` table. Extend this pattern to other entities if needed, retaining data for audit purposes.

---

### Task I2 — Admin: Customer Add — Keep Card ID Display (Read-Only)

**Priority:** 🟢 Low  
**Apps:** Admin

**Admin — Customer detail/profile view:**
1. On customer profile view (edit page), show Card ID as read-only info:
   - "Assigned Card: DMSTD00000011 (activated)" or "No card assigned"
2. This is already stored in `customers.card_id` — just needs to be surfaced in the view

---

---

## Summary of All Tasks by Priority

### 🔴 Critical (Foundation / Blockers)

| ID | Task | Apps | DB Migration |
|----|------|------|--------------|
| M1–M11 | All database migrations | — | Required first |
| C1 | Card Config — new toggle fields | Admin, API, Customer | M3 |
| D1 | Store Coupons — admin-only creation | Admin, Merchant, API | M6 |
| D3 | Store Coupon limits + approval queue | Admin, Merchant, API | M1 |
| G1 | Card-gated access enforcement | Customer | none |
| C6 | Cards assign to Store OR Admin | Admin | M4 |

### 🟠 High (Major Feature Gaps)

| ID | Task | Apps | DB Migration |
|----|------|------|--------------|
| B2 | Merchant subscription period selector | Admin | M1 |
| B3 | Merchant premium + verified toggles | Admin | M1 |
| B4 | Merchant coupon assignment limits | Admin | M1 |
| B7 | Store profile — Call button + logging | Customer, API | M8 |
| B8 | Store profile — Book Now + bookings | Customer, Merchant, API | M7 |
| D2 | Store coupon Grab Now acceptance | Customer, API | M6 |
| F1 | Gift coupon bulk creation with filters | Admin | M9 |
| F2 | Gift coupon accept/reject flow | Customer, API | none |
| A4 | Mandatory city selection on first login | Customer, API | none |

### 🟡 Medium (Enhancements)

| ID | Task | Apps | DB Migration |
|----|------|------|--------------|
| A1 | Customer list filters (city/gender/profession) | Admin | none |
| A2 | Add customer without password | Admin | none |
| A5 | OTP-only login for temp_password users | Customer, API | none |
| B1 | Merchant list city filter | Admin | none |
| B5 | Store form — website link | Admin, API | M2 |
| B6 | Store form — booking config | Admin | M2 |
| C3 | Card config — gender M/F split | Admin, API | M10 |
| C4 | Card config — profession from master data | Admin, API | M10 |
| C5 | Cards generate — cleanup parameters | Admin | none |
| D4 | Merchant dashboard coupon limit indicator | Merchant, API | none |
| E1 | Coupon list filters | Admin | none |
| E2 | Coupon form — note + per-user limit + auto-code | Admin | M5 |
| H1 | Pay-back points display on card | Customer, API | M3 |
| I1 | Remove all delete buttons globally | Admin | none |

### 🟢 Low (Minor Fixes)

| ID | Task | Apps |
|----|------|------|
| A3 | Remove Customer Type from UI | Admin |
| C2 | Remove card config delete button | Admin |
| I2 | Show Card ID read-only in customer profile | Admin |

---

## API Endpoints Summary (New Endpoints Required)

| Method | Path | Controller | Task |
|--------|------|------------|------|
| POST | `/api/customer/stores/{id}/call-log` | Customer/StoreController | B7 |
| POST | `/api/customer/stores/{id}/bookings` | Customer/BookingController | B8 |
| GET | `/api/customer/bookings` | Customer/BookingController | B8 |
| DELETE | `/api/customer/bookings/{id}` | Customer/BookingController | B8 |
| POST | `/api/merchant/bookings/{id}/confirm` | Merchant/BookingController | B8 |
| POST | `/api/merchant/bookings/{id}/reject` | Merchant/BookingController | B8 |
| POST | `/api/merchant/bookings/{id}/complete` | Merchant/BookingController | B8 |
| GET | `/api/merchant/stores/{id}/bookings` | Merchant/BookingController | B8 |
| POST | `/api/customer/store-coupons/{id}/grab` | Customer/StoreCouponController | D2 |
| POST | `/api/merchant/store-coupons/{id}/request-assignment` | Merchant/StoreCouponController | D3 |
| GET | `/api/customer/gift-coupons` | Customer/GiftCouponController | F2 |
| POST | `/api/customer/gift-coupons/{id}/accept` | Customer/GiftCouponController | F2 |
| POST | `/api/customer/gift-coupons/{id}/reject` | Customer/GiftCouponController | F2 |
| POST | `/admin/gift-coupons/preview` | Admin/GiftCouponsController | F1 |
| GET | `/admin/coupons/generate-code` | Admin/CouponsController | E2 |

---

## Files to Modify (Admin PHP MVC)

| File | Tasks |
|------|-------|
| `admin/controllers/MerchantsController.php` | B1, B2, B3, B4 |
| `admin/controllers/CustomersController.php` | A1, A2, A3 |
| `admin/controllers/CardConfigurationsController.php` | C1, C2, C3, C4 |
| `admin/controllers/CardsController.php` | C5, C6, I1 |
| `admin/controllers/StoreCouponsController.php` | D1, D3, I1 |
| `admin/controllers/CouponsController.php` | E1, E2, I1 |
| `admin/controllers/GiftCouponsController.php` | F1 |
| `admin/views/customers/index.php` | A1, A3 |
| `admin/views/customers/form.php` | A2, A3, I2 |
| `admin/views/merchants/index.php` | B1, B3 |
| `admin/views/merchants/form.php` | B2, B3, B4, B5, B6 |
| `admin/views/card_configurations/form.php` | C1, C2, C3, C4 |
| `admin/views/card_configurations/index.php` | C2 |
| `admin/views/cards/assign.php` | C6 |
| `admin/views/coupons/form.php` | E2 |
| `admin/views/gift_coupons/form.php` | F1 |
| `admin/views/store_coupons/form.php` | D1 |

## Files to Modify (API PHP)

| File | Tasks |
|------|-------|
| `api/controllers/Customer/StoreController.php` | B7 |
| `api/controllers/Customer/StoreCouponController.php` | D2 |
| `api/controllers/Customer/GiftCouponController.php` | F2 |
| `api/controllers/Merchant/StoreCouponController.php` | D3 |
| `api/controllers/Auth/` (login flow) | A5 |
| `api/controllers/Customer/ProfileController.php` | A4 |
| NEW: `api/controllers/Customer/BookingController.php` | B8 |
| NEW: `api/controllers/Merchant/BookingController.php` | B8 |

## Files to Modify (Customer React App)

| Area | Tasks |
|------|-------|
| Auth context / store | A4, A5, G1 |
| Login / onboarding flow | A4, A5 |
| Store detail page | B7, B8, D2, G1 |
| New: CitySelectPage | A4 |
| New: BookingFormPage + BookingListPage | B8 |
| Coupons / wallet section | D2 |
| Gift coupons section | F2 |
| Active card page | C1, H1 |
| Route guards | G1 |
| New: CardRequiredGuard component | G1 |

## Files to Modify (Merchant React App)

| Area | Tasks |
|------|-------|
| Store coupons section | D1, D3, D4 |
| New: Bookings section + pages | B8 |
| Store dashboard | D4 |

---

*End of Implementation Plan*
