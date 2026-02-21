# Merchant Application — Functional Specification

> **Source:** Legacy PHP codebase (`OLD-Merchant-application-PHP-code`)  
> **Purpose:** Complete feature documentation for verifying the new merchant application implementation  
> **Access:** ALL pages require merchant login (no public pages)

---

## Table of Contents

1. [Authentication & Session Management](#1-authentication--session-management)
2. [Dashboard](#2-dashboard)
3. [Merchant Units/Stores Management](#3-merchant-unitsstores-management)
4. [Platform Coupons](#4-platform-coupons)
5. [Store Coupons](#5-store-coupons)
6. [Coupon Redemption](#6-coupon-redemption)
7. [Customer Management](#7-customer-management)
8. [Messaging (Complaints & Suggestions)](#8-messaging-complaints--suggestions)
9. [Access Control Matrix](#9-access-control-matrix)
10. [Database Tables Reference](#10-database-tables-reference)
11. [Status Codes Reference](#11-status-codes-reference)

---

## 1. Authentication & Session Management

### 1.1 Login

| Field | Details |
|-------|---------|
| **Feature Name** | Merchant Login |
| **Description** | Authenticate merchant users to access the dashboard and all features |
| **Who Can Use** | Any user with valid merchant credentials |
| **Source File** | `login.php` |

**Inputs:**
- `userid` — Merchant login ID (text)
- `password` — Password (text)

**Processing Logic:**
1. Validate that both `userid` and `password` are provided
2. Hash the entered password using MD5: `md5($password)`
3. Query `tbl_merchant_login` table: match `merchant_userid` = input userid AND `merchant_password` = MD5 hash AND `user_status` = 1 (active)
4. If match found:
   - Set session variables: `merchant_login_id`, `merchant_name`, `merchant_company_id`, `account_type_id`
   - Query `tbl_merchant_login_access` to check for unit-level access
   - If unit-level access record exists: set `company_unit_id` in session
   - Redirect to `index.php` (dashboard)
5. If no match: display "Invalid user name or password" error

**Session Variables Set:**
- `$_SESSION['merchant_login_id']` — Merchant login record ID
- `$_SESSION['merchant_name']` — Display name
- `$_SESSION['merchant_company_id']` — Company/business ID
- `$_SESSION['account_type_id']` — Access level (determines feature availability)
- `$_SESSION['company_unit_id']` — Unit ID (if unit-level login, from `tbl_merchant_login_access`)

**Database Impact:**
- Read from `tbl_merchant_login` (credentials check)
- Read from `tbl_merchant_login_access` (unit-level access check)

**Conditions/Edge Cases:**
- User must have `user_status = 1` (active) to login
- Unit-level logins receive restricted access (only see their unit's data)
- Password stored as unsalted MD5 hash (legacy — must be upgraded in new app)

### 1.2 Session Guard

| Field | Details |
|-------|---------|
| **Description** | Every page includes a session check at the top |
| **Source File** | Every PHP page |

**Processing Logic:**
1. Check if `$_SESSION['merchant_login_id']` is set and not empty
2. If not set: redirect to `login.php` with `header('Location: login.php')`
3. If set: allow page to continue loading

### 1.3 Logout

| Field | Details |
|-------|---------|
| **Feature Name** | Logout |
| **Source File** | `logout.php` |

**Processing Logic:**
1. Call `session_destroy()`
2. Redirect to `login.php`

---

## 2. Dashboard

| Field | Details |
|-------|---------|
| **Feature Name** | Dashboard / Home |
| **Description** | Simple welcome landing page after login |
| **Who Can Use** | All authenticated merchants |
| **Source File** | `index.php` |

**Display Elements:**
- Welcome message: "Welcome, [merchant_name]"
- Navigation sidebar with links to all features (based on `account_type_id`)

**Inputs:** None  
**Processing Logic:** Display static welcome content  
**Database Impact:** None (read session only)

---

## 3. Merchant Units/Stores Management

### 3.1 List Units

| Field | Details |
|-------|---------|
| **Feature Name** | My Units / Stores List |
| **Description** | View all merchant business units/store locations |
| **Who Can Use** | All authenticated merchants |
| **Source File** | `my-units.php` |

**Processing Logic:**
1. Query `tbl_merchant_company_units` filtered by `merchant_company_id` from session
2. For unit-level logins (session has `company_unit_id`): additionally filter by that unit ID
3. Display list of units with name, address, cover photo

**Output:**
- Grid/list of unit cards showing: cover photo, unit name, address
- Each card links to unit detail/edit page

**Database Impact:**
- Read from `tbl_merchant_company_units`

### 3.2 View Unit Detail

| Field | Details |
|-------|---------|
| **Feature Name** | Unit Detail View |
| **Description** | Detailed view of a single merchant unit with gallery and working hours |
| **Who Can Use** | All authenticated merchants (own units only) |
| **Source File** | `my-unit.php` |

**Inputs:**
- `id` (GET parameter) — Unit ID

**Processing Logic:**
1. Query unit details from `tbl_merchant_company_units` where `company_unit_id` = id AND `merchant_company_id` = session company ID
2. Query gallery images from `tbl_merchant_company_unit_images` where `company_unit_id` = id
3. Query working hours from `tbl_mc_unt_working_hours` where `company_unit_id` = id

**Output:**
- Unit cover photo
- Unit details: name, description, address, latitude, longitude, contact phone, contact email
- Working hours table (7 days, open/close times)
- Photo gallery grid
- Links to: Edit Unit, Edit Working Hours, Manage Gallery

**Database Impact:**
- Read from `tbl_merchant_company_units`
- Read from `tbl_merchant_company_unit_images`
- Read from `tbl_mc_unt_working_hours`

### 3.3 Edit Unit Information

| Field | Details |
|-------|---------|
| **Feature Name** | Edit Unit Details |
| **Description** | Update unit name, description, address, contact info, and cover photo |
| **Who Can Use** | All authenticated merchants (own units only) |
| **Source File** | `edit-my-unit.php`, `update-unit.php` |

**Inputs:**
- `unit_name` — Store/unit name (text, required)
- `unit_description` — Description (textarea)
- `unit_address` — Full address (textarea)
- `latitude` — GPS latitude (text)
- `longitude` — GPS longitude (text)
- `unit_contactno` — Contact phone (text)
- `unit_email` — Contact email (text)
- `cover_photo` — Cover image (file upload, optional)

**Processing Logic:**
1. Validate required fields (unit_name)
2. If cover photo uploaded:
   - Save file to `repository/profile/` directory
   - Generate unique filename
3. UPDATE `tbl_merchant_company_units` with new values
4. Redirect back to unit detail page

**Database Impact:**
- UPDATE `tbl_merchant_company_units` — columns: `unit_name`, `unit_description`, `unit_address`, `latitude`, `longitude`, `unit_contactno`, `unit_email`, `cover_photo`

### 3.4 Edit Working Hours

| Field | Details |
|-------|---------|
| **Feature Name** | Edit Unit Working Hours |
| **Description** | Set open/close times for each day of the week |
| **Who Can Use** | All authenticated merchants (own units only) |
| **Source File** | `edit-working-hours.php`, `update-working-hours.php` |

**Inputs:**
- For each day (Monday through Sunday):
  - `is_open` — Whether the unit is open on this day (checkbox)
  - `open_time` — Opening time (30-minute interval dropdown: 00:00, 00:30, 01:00 ... 23:30)
  - `close_time` — Closing time (same format)

**Processing Logic:**
1. Load current working hours for the unit
2. On submit: DELETE existing records for this unit from `tbl_mc_unt_working_hours`
3. INSERT new records for each day (7 records)

**Database Impact:**
- DELETE from `tbl_mc_unt_working_hours` WHERE `company_unit_id` = unit_id
- INSERT into `tbl_mc_unt_working_hours` — columns: `company_unit_id`, `day_name`, `is_open`, `open_time`, `close_time`

### 3.5 Gallery Management

| Field | Details |
|-------|---------|
| **Feature Name** | Unit Photo Gallery |
| **Description** | Upload, reorder, and delete unit photos |
| **Who Can Use** | All authenticated merchants (own units only) |
| **Source File** | `upload-gallery.php`, `gallery.php`, `delete-gallery-image.php`, `reorder-gallery.php` |

**Upload Inputs:**
- Multiple image files via Dropzone.js drag-and-drop interface

**Processing Logic:**
1. **Upload:** Accept image files via AJAX (Dropzone.js), save to `repository/gallery/` directory, INSERT record into `tbl_merchant_company_unit_images` with auto-incrementing `image_order`
2. **Reorder:** Receive updated order via AJAX, UPDATE `image_order` column for each image
3. **Delete:** Receive image ID via AJAX, DELETE record from `tbl_merchant_company_unit_images`, remove file from disk

**Database Impact:**
- INSERT into `tbl_merchant_company_unit_images` — columns: `company_unit_id`, `image_name`, `image_order`
- UPDATE `tbl_merchant_company_unit_images` SET `image_order`
- DELETE from `tbl_merchant_company_unit_images` WHERE `image_id` = id

---

## 4. Platform Coupons

### 4.1 Generate Platform Coupon

| Field | Details |
|-------|---------|
| **Feature Name** | Generate/Create Platform Coupon |
| **Description** | Create a new coupon for public distribution through the platform |
| **Who Can Use** | All authenticated merchants |
| **Source File** | `generate-coupon.php`, `save-coupon.php` |

**Inputs:**
- `coupon_category_id` — Coupon category (dropdown from `coupons_categories`)
- `coupon_sub_category_id` — Sub-category (dynamic dropdown from `cpn_coupons_sub_categories`, loaded via AJAX based on selected category)
- `merchant_unit_ids[]` — Target units (multi-select checkboxes from merchant's units)
- `coupon_image` — Coupon image (file upload, required)
- `coupon_title` — Title (text, required)
- `coupon_description` — Description (textarea)
- `coupon_terms_condition` — Terms & conditions (textarea)
- `expiry_to_subscribe` — Last date to subscribe (date picker)
- `expiry_to_redeem` — Last date to redeem (date picker)
- `redeem_type` — Redemption type: "Single" or "Multiple" (radio buttons)
- `issued_to_public` — Number of coupons for public subscription (number)
- `issued_to_vault` — Number of coupons for vault/gifting (number)

**Processing Logic:**
1. Validate all required fields
2. Upload coupon image to `repository/coupons/` directory
3. Generate coupon code: `'C' + (1000 + last_insert_id)` — e.g., C1001, C1002
4. Convert `merchant_unit_ids[]` array to comma-separated string
5. INSERT into `cpn_coupons` table with `coupon_status = -1` (pending admin approval)
6. After INSERT, UPDATE the record to set `coupon_code` using the generated ID

**Database Impact:**
- INSERT into `cpn_coupons` — columns: `coupon_uuid`, `coupon_merchant_id`, `coupon_category_id`, `coupon_sub_category_id`, `merchant_unit_ids`, `coupon_image`, `coupon_title`, `coupon_description`, `coupon_code`, `coupon_terms_condition`, `expiry_to_subscribe`, `expiry_to_redeem`, `redeem_type`, `issued_to_public`, `issued_to_vault`, `coupon_status`, `coupon_cdate`
- UPDATE `cpn_coupons` SET `coupon_code` WHERE `coupon_id` = last_insert_id

**Conditions/Edge Cases:**
- New coupons are always created with `coupon_status = -1` (pending approval)
- Admin must approve (status = 1) before the coupon is visible to customers
- `coupon_uuid` is generated using `md5(uniqid(rand(), true))`

### 4.2 List Platform Coupons

| Field | Details |
|-------|---------|
| **Feature Name** | My Coupons List |
| **Description** | View all coupons created by this merchant |
| **Who Can Use** | All authenticated merchants |
| **Source File** | `coupons.php` |

**Processing Logic:**
1. Query `cpn_coupons` WHERE `coupon_merchant_id` = session company ID
2. Order by creation date descending

**Output:**
- Table/grid with columns: coupon image, title, code, category, status badge, subscribe count, redeem count, expiry dates
- Status badges: Pending Approval (-1), Approved (1), Rejected (0)
- Links to: View Detail, Edit

### 4.3 Edit Platform Coupon

| Field | Details |
|-------|---------|
| **Feature Name** | Edit Coupon |
| **Description** | Modify an existing platform coupon |
| **Who Can Use** | All authenticated merchants (own coupons only) |
| **Source File** | `edit-coupon.php`, `update-coupon.php` |

**Inputs:** Same as Generate (section 4.1), pre-populated with existing values

**Processing Logic:**
1. Load existing coupon data
2. Validate ownership (coupon's `coupon_merchant_id` = session company ID)
3. If new image uploaded: replace old image
4. UPDATE `cpn_coupons` with new values
5. Reset `coupon_status = -1` (requires re-approval after edit)

**Conditions/Edge Cases:**
- Editing a coupon resets its status to pending approval (-1)
- Only the owning merchant can edit their coupons

---

## 5. Store Coupons

### 5.1 Generate Store Coupon

| Field | Details |
|-------|---------|
| **Feature Name** | Generate Store Coupon |
| **Description** | Create merchant-specific coupons for direct assignment to individual customers |
| **Who Can Use** | Merchants with `account_type_id >= 3` |
| **Source File** | `generate-store-coupon.php`, `save-store-coupon.php` |

**Inputs:**
- `coupon_title` — Title (text, required)
- `coupon_terms_condition` — Terms & conditions (textarea)
- `expiry_to_redeem` — Expiry date (date picker)
- `merchant_unit_ids[]` — Target units (multi-select from merchant's units)

**Processing Logic:**
1. Validate required fields
2. Generate unique code: `'SC' + (1000 + last_insert_id)`
3. INSERT into `cpn_store_coupons` table

**Database Impact:**
- INSERT into `cpn_store_coupons` — columns: `store_coupon_uuid`, `coupon_merchant_id`, `merchant_unit_ids`, `coupon_title`, `coupon_code`, `coupon_terms_condition`, `expiry_to_redeem`, `coupon_cdate`

### 5.2 Assign Store Coupon to Customer

| Field | Details |
|-------|---------|
| **Feature Name** | Assign Store Coupon |
| **Description** | Assign a store coupon to a specific customer |
| **Who Can Use** | Merchants with `account_type_id >= 3` |
| **Source File** | `assign-store-coupon.php`, `save-store-coupon-assignment.php` |

**Inputs:**
- `store_coupon_id` — Selected store coupon (dropdown)
- `customer_id` — Target customer (from customer list or search)

**Processing Logic:**
1. Check allotment: query total assigned count for this coupon
2. INSERT into `cpn_store_coupons_assignment` with `assignment_status = -1` (pending admin approval)

**Database Impact:**
- INSERT into `cpn_store_coupons_assignment` — columns: `store_coupon_id`, `customer_id`, `assignment_date`, `assignment_status`

### 5.3 Bulk Assign Store Coupons

| Field | Details |
|-------|---------|
| **Feature Name** | Bulk Assign Store Coupons |
| **Description** | Assign multiple store coupons to multiple customers (up to 10 at a time) |
| **Who Can Use** | Merchants with `account_type_id >= 3` |
| **Source File** | `bulk-assign-store-coupon.php` |

**Inputs:**
- `coupon_ids[]` — Multiple store coupons (multi-select)
- `customer_ids[]` — Multiple customers (multi-select, max 10)

**Processing Logic:**
1. For each customer-coupon combination: INSERT into `cpn_store_coupons_assignment`
2. Each assignment starts with status `-1` (pending approval)

**Conditions/Edge Cases:**
- Maximum 10 customers per bulk operation
- Allotment throttle check per coupon

### 5.4 List Store Coupons & Assignments

| Field | Details |
|-------|---------|
| **Feature Name** | Store Coupons List |
| **Description** | View store coupons and their assignment status |
| **Who Can Use** | Merchants with `account_type_id >= 3` |
| **Source File** | `store-coupons.php` |

**Output:**
- List of store coupons with: title, code, expiry date, total assignments
- Assignment status tracking: Pending (-1), Rejected (0), Approved (1), Redeemed (2)

---

## 6. Coupon Redemption

### 6.1 Search Customer for Redemption

| Field | Details |
|-------|---------|
| **Feature Name** | Redeem Coupon — Customer Search |
| **Description** | Search for a customer by mobile number or loyalty card number to view their redeemable coupons |
| **Who Can Use** | All authenticated merchants |
| **Source File** | `redeem-coupon.php` |

**Inputs:**
- `search_input` — Mobile number or loyalty card number (text)

**Processing Logic:**
1. Search `tbl_customers` for matching `customer_mobile` or loyalty card number
2. If found: display customer details and their redeemable coupons (3 categories)
3. If not found: display "No customer found" message

**Output — Three Coupon Sections:**

#### A. Subscribed Platform Coupons
- Query `cpn_coupon_subscribe` WHERE `customer_id` = found customer AND `subscribe_status = 0` (active)
- JOIN with `cpn_coupons` for coupon details
- Filter: only show coupons valid at the merchant's unit(s)
- Display: coupon image, title, code, subscribe date, redeem deadline

#### B. Assigned Store Coupons
- Query `cpn_store_coupons_assignment` WHERE `customer_id` = found customer AND `assignment_status = 1` (approved)
- JOIN with `cpn_store_coupons` for coupon details
- Filter: only show coupons from this merchant
- Display: coupon title, terms, assignment date

#### C. Flash Discounts
- Query `flash_discount` filtered by merchant's unit(s) AND customer's loyalty card classification
- Display: discount description, image, valid period

### 6.2 Redeem Platform Coupon

| Field | Details |
|-------|---------|
| **Feature Name** | Redeem Platform Coupon |
| **Description** | Mark a customer's subscribed platform coupon as redeemed |
| **Who Can Use** | All authenticated merchants |
| **Source File** | `redeem-platform-coupon.php` |

**Inputs:**
- `subscribe_id` — Subscription record ID
- `transaction_value` — Transaction amount (number, optional)
- `redeem_unit_id` — Unit where redemption occurs (auto-set from session if unit-level login)

**Processing Logic:**
1. UPDATE `cpn_coupon_subscribe` SET `subscribe_status = 1`, `redeem_date = NOW()`, `redeem_unit_id`, `transaction_value`
2. Return success/error response

**Database Impact:**
- UPDATE `cpn_coupon_subscribe` — columns: `subscribe_status` (0→1), `redeem_date`, `redeem_unit_id`, `transaction_value`

### 6.3 Redeem Flash Discount

| Field | Details |
|-------|---------|
| **Feature Name** | Redeem Flash Discount |
| **Source File** | `redeem-flash-discount.php` |

**Inputs:**
- `discount_id` — Flash discount ID
- `customer_id` — Customer ID
- `transaction_value` — Transaction amount (optional)

**Processing Logic:**
1. INSERT into `flash_discount_redeem` — columns: `discount_id`, `customer_id`, `redeem_date`, `transaction_value`, `unit_id`

### 6.4 Redeem Store Coupon

| Field | Details |
|-------|---------|
| **Feature Name** | Redeem Store Coupon |
| **Source File** | `redeem-store-coupon.php` |

**Inputs:**
- `assignment_id` — Store coupon assignment ID

**Processing Logic:**
1. UPDATE `cpn_store_coupons_assignment` SET `assignment_status = 2` (redeemed), `redeem_date = NOW()`

---

## 7. Customer Management

### 7.1 List Customers

| Field | Details |
|-------|---------|
| **Feature Name** | Customer List with Analytics |
| **Description** | View all customers associated with this merchant, with transaction analytics |
| **Who Can Use** | Merchants with `account_type_id >= 3` |
| **Source File** | `customers.php` |

**Filters:**
- Date range (from date, to date) — filters transaction analytics period
- Sort by: Name, Total Transactions, Highest Transaction, Last Visit, Response Rate

**Output per Customer:**
- Customer name, mobile, loyalty card number
- Analytics:
  - Total coupon assignments count
  - Total transaction count
  - Total transaction value
  - Highest single transaction
  - Last visit date
  - Average frequency (days between visits)
  - Response rate (redeemed / assigned percentage)

**Database Impact:**
- Read from `tbl_customers`
- Aggregated reads from `cpn_coupon_subscribe`, `cpn_store_coupons_assignment`

### 7.2 Add New Customer

| Field | Details |
|-------|---------|
| **Feature Name** | Add Customer |
| **Description** | Register a new customer account and assign a private loyalty card |
| **Who Can Use** | Merchants with `account_type_id == 4` only |
| **Source File** | `add-customer.php`, `save-customer.php` |

**Inputs:**
- `first_name` — Customer first name (text, required)
- `last_name` — Customer last name (text)
- `customer_mobile` — Mobile number (10 digits, required, unique)
- `customer_email` — Email address (text)

**Processing Logic:**
1. Validate required fields and mobile number uniqueness
2. Generate a random password (6 characters)
3. Hash password with MD5
4. INSERT into `tbl_customers` with `sms_otp_status = 1` (pre-verified), `temp_password = 1` (forces password change on first login)
5. Assign a private loyalty card:
   - Query next available card number from `z_loyalty_card_number_private`
   - Link to customer
6. Send SMS to customer with their login credentials (mobile + generated password)
   - SMS API: `https://alertin.co.in/api/push.json`
   - Method: CURL GET with query parameters
   - Parameters: `apikey`, `sender`, `mobileno`, `text` (DLT template)

**Database Impact:**
- INSERT into `tbl_customers` — columns: `customer_uuid`, `first_name`, `last_name`, `customer_mobile`, `customer_email`, `customer_password`, `sms_otp_status`, `temp_password`, `customer_cdate`
- UPDATE `z_loyalty_card_number_private` — assign card to customer
- SMS sent via external API

**Conditions/Edge Cases:**
- Mobile number must be unique across all customers
- Loyalty card assigned from private pool (merchant-specific cards)
- Customer will be forced to change password on first login (`temp_password = 1`)

### 7.3 Edit Customer

| Field | Details |
|-------|---------|
| **Feature Name** | Edit Customer Details |
| **Who Can Use** | Merchants with `account_type_id >= 3` |
| **Source File** | `edit-customer.php`, `update-customer.php` |

**Inputs:** Same as Add Customer (section 7.2), pre-populated

**Processing Logic:**
1. Load existing customer data
2. UPDATE `tbl_customers` with modified fields

---

## 8. Messaging (Complaints & Suggestions)

### 8.1 View Inbox

| Field | Details |
|-------|---------|
| **Feature Name** | Messages / Complaints Inbox |
| **Description** | View customer complaints and suggestions submitted against this merchant's units |
| **Who Can Use** | All authenticated merchants (unit-level logins see only their unit's messages) |
| **Source File** | `messages.php` |

**Processing Logic:**
1. Query `tbl_merchant_unit_complaints` WHERE `merchant_company_id` = session company ID
2. For unit-level logins: additionally filter by `company_unit_id`
3. Order by date descending

**Output:**
- List of messages with: customer name, subject, date, status (replied/pending)

### 8.2 View Message Detail & Reply

| Field | Details |
|-------|---------|
| **Feature Name** | View & Reply to Complaint |
| **Source File** | `message-detail.php`, `reply-complaint.php` |

**Inputs:**
- `complaint_id` — Complaint record ID
- `reply_message` — Merchant's response (textarea)

**Processing Logic:**
1. Load complaint details
2. On reply: UPDATE `tbl_merchant_unit_complaints` SET `merchant_reply` = reply text, `reply_date = NOW()`, `complaint_status = 'replied'`

**Database Impact:**
- UPDATE `tbl_merchant_unit_complaints` — columns: `merchant_reply`, `reply_date`, `complaint_status`

---

## 9. Access Control Matrix

| Feature | Any account_type | account_type >= 3 | account_type == 4 |
|---------|:-:|:-:|:-:|
| Login / Logout | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |
| View Units | ✅ | ✅ | ✅ |
| Edit Unit Details | ✅ | ✅ | ✅ |
| Edit Working Hours | ✅ | ✅ | ✅ |
| Gallery Management | ✅ | ✅ | ✅ |
| Platform Coupons (CRUD) | ✅ | ✅ | ✅ |
| Redeem Coupons | ✅ | ✅ | ✅ |
| Store Coupons (CRUD) | ❌ | ✅ | ✅ |
| Customer List & Analytics | ❌ | ✅ | ✅ |
| View/Reply Messages | ✅ | ✅ | ✅ |
| Add New Customer | ❌ | ❌ | ✅ |

**Note:** Unit-level logins (those with records in `tbl_merchant_login_access`) have an additional constraint — they can only see/manage data for their specific unit.

---

## 10. Database Tables Reference

| Table | Purpose |
|-------|---------|
| `tbl_merchant_login` | Merchant user accounts (credentials, status, account_type) |
| `tbl_merchant_login_access` | Unit-level access mapping (which login can access which unit) |
| `tbl_merchant_company` | Merchant company/business master data |
| `tbl_merchant_company_units` | Individual store locations/units |
| `tbl_merchant_company_unit_images` | Unit photo gallery images |
| `tbl_mc_unt_working_hours` | Unit working hours (per day) |
| `tbl_merchant_company_unit_categories` | Unit-category mapping |
| `tbl_merchant_company_unit_sub_categories` | Unit-subcategory mapping |
| `coupons_categories` | Master coupon categories |
| `cpn_coupons_sub_categories` | Coupon sub-categories |
| `cpn_coupons` | Platform coupons |
| `cpn_coupon_subscribe` | Customer coupon subscriptions |
| `cpn_coupon_unit` | Coupon-to-unit mapping |
| `cpn_store_coupons` | Merchant store coupons |
| `cpn_store_coupons_assignment` | Store coupon assignments to customers |
| `flash_discount` | Flash discount offers |
| `flash_discount_redeem` | Flash discount redemption records |
| `tbl_customers` | Customer accounts |
| `tbl_unit_favorite` | Customer favorite units |
| `tbl_merchant_unit_complaints` | Customer complaints/suggestions per unit |
| `z_loyalty_cards` | Loyalty card types/tiers |
| `z_loyalty_card_number_private` | Private loyalty card number pool (merchant-assigned) |

---

## 11. Status Codes Reference

### Coupon Status (`cpn_coupons.coupon_status`)
| Value | Meaning |
|-------|---------|
| `-1` | Pending admin approval |
| `0` | Rejected by admin |
| `1` | Approved / Active |

### Coupon Subscribe Status (`cpn_coupon_subscribe.subscribe_status`)
| Value | Meaning |
|-------|---------|
| `0` | Active (subscribed, not yet redeemed) |
| `1` | Redeemed |
| `-1` | Expired (auto-set when `redeem_last_date < NOW()`) |

### Store Coupon Assignment Status (`cpn_store_coupons_assignment.assignment_status`)
| Value | Meaning |
|-------|---------|
| `-1` | Pending admin approval |
| `0` | Rejected |
| `1` | Approved (ready to redeem) |
| `2` | Redeemed |

### Merchant User Status (`tbl_merchant_login.user_status`)
| Value | Meaning |
|-------|---------|
| `1` | Active |
| `0` | Inactive / Blocked |

---

## Appendix: Key Business Rules

1. **Coupon Code Generation:** Platform coupon codes follow pattern `C` + `(1000 + coupon_id)`. Store coupon codes follow pattern `SC` + `(1000 + store_coupon_id)`.

2. **Coupon Approval Workflow:** All new coupons and edits require admin approval (status = -1). Editing an approved coupon resets status to pending.

3. **Unit-Level Login Restriction:** Merchants with unit-level access (`tbl_merchant_login_access`) can only view/manage data for their assigned unit, including units list, coupons (filtered by unit), messages (filtered by unit), and redemptions.

4. **Customer Password Generation:** When a merchant adds a customer, a random 6-character password is generated, hashed with MD5, and sent via SMS.

5. **SMS Integration:** Uses `alertin.co.in` API with DLT-registered templates for sending passwords and OTPs.

6. **Loyalty Card Assignment (Private):** Merchants with `account_type == 4` can assign loyalty cards from `z_loyalty_card_number_private` table, which contains pre-generated sequential card numbers specific to the merchant.

7. **Transaction Analytics:** Customer analytics (transaction count, values, frequency) are calculated dynamically from `cpn_coupon_subscribe` redemption records, filtered by optional date range.

8. **Flash Discount Filtering:** Flash discounts shown during redemption are filtered by the customer's loyalty card classification (`classification_id`), ensuring customers only see discounts matching their loyalty tier.
