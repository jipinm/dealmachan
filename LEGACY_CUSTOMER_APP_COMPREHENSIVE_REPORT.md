# DealMachan Legacy Customer Application — Comprehensive PHP Analysis Report

> **Source Directory:** `OLD-Customer-application-PHP-code/`  
> **Total PHP Files Analyzed:** 50+  
> **Database:** MySQL `mpclub` (user: `mpclubuser`)  
> **Base URL:** `https://dealmachan.com/`  
> **Frontend URL:** `https://frontend.dealmachan.com/`

---

## TABLE OF CONTENTS

1. [Authentication & User Management](#1-authentication--user-management)
2. [Homepage & Navigation](#2-homepage--navigation)
3. [Coupon System](#3-coupon-system)
4. [Flash Discounts](#4-flash-discounts)
5. [Merchant/Business Directory](#5-merchantbusiness-directory)
6. [Loyalty Card System](#6-loyalty-card-system)
7. [Profile Management](#7-profile-management)
8. [Offer Zone (My Offers)](#8-offer-zone-my-offers)
9. [Wishlist / Favorites](#9-wishlist--favorites)
10. [Reviews & Ratings](#10-reviews--ratings)
11. [Complaints & Suggestions](#11-complaints--suggestions)
12. [Important Days](#12-important-days)
13. [Business Sign-Up](#13-business-sign-up)
14. [CMS Pages & Blog](#14-cms-pages--blog)
15. [Contact & Communication](#15-contact--communication)
16. [Location Management](#16-location-management)
17. [Infrastructure & Shared Components](#17-infrastructure--shared-components)
18. [Database Schema Summary](#18-database-schema-summary)
19. [Security Concerns](#19-security-concerns)
20. [Unused / Test Files](#20-unused--test-files)

---

## 1. AUTHENTICATION & USER MANAGEMENT

### 1.1 Login Flow

**Files:** `login.php`, `mobilecheck.php`, `otpverification.php`, `popup.php`  
**Access:** PUBLIC (AJAX endpoints)

#### Multi-Step Login Process:

**Step 1 — Mobile/Card Number Check (`mobilecheck.php`)**
- **Input:** `$_POST['signin_loginid']` (mobile number or loyalty card number)
- **Validation:** Must not be empty (returns `'mandatory'`)
- **Processing:**
  - Calls `$obj->checkMobileOrCardNumberExistInCustomers($signin_loginid)`
  - Query: `SELECT * FROM tbl_customers WHERE (mobile_no='$val' OR loyalty_card_no='$val') AND status='1'`
  - If no match → returns `'nomobile'`
  - If match found and `sms_otp_status == '0'` (unverified):
    - Generates 4-digit OTP: `rand(1000, 9999)`
    - UPDATEs `tbl_customers SET sms_otp='$otp'`
    - Sends OTP via SMS (Messaging class → alertin.co.in API)
    - Returns `'unverified'` (UI shows OTP field)
  - If match found and `sms_otp_status == '1'` (verified):
    - Returns `'verified'` (UI shows password field)

**Step 2a — OTP Verification (`otpverification.php`)**
- **Input:** `$_POST['signin_loginid']`, `$_POST['signin_otp']`
- **Validation:** OTP must not be empty
- **Processing:**
  - Queries `tbl_customers` by mobile/card number
  - Compares submitted OTP with stored `sms_otp` field
  - If match: UPDATEs `sms_otp_status='1'`, returns `'verified'`
  - If no match: returns `'unverified'`
- **Database Impact:** UPDATEs `tbl_customers.sms_otp_status` to `'1'`

**Step 2b — Password Login (`login.php`)**
- **Input:** `$_POST['signin_loginid']`, `$_POST['signin_pass']`
- **Validation:** Password must not be empty
- **Processing:**
  - Calls `$obj->loginUser($signin_loginid, md5($signin_pass))`
  - Query: `SELECT * FROM tbl_customers WHERE (mobile_no='$val' OR loyalty_card_no='$val') AND customer_login_pwd='$md5pass' AND status='1'`
  - If match → sets session variables:
    - `$_SESSION['customer_id']` = customer_id
    - `$_SESSION['first_name']` = first_name
    - `$_SESSION['mobile_no']` = mobile_no
    - `$_SESSION['loyalty_card_no']` = loyalty_card_no
    - `$_SESSION['loyalty_card_id']` = loyalty_card_id
  - Returns `'success'` or `'coupon_details'` (if `$_SESSION['coupon_page']` is set)
  - If `temp_password == '1'`, returns `'mandatory'` (force password change)
  - If no match → returns `'invalid'`

**Login Modal UI (`popup.php`)**
- Reusable modal component included on most pages
- Contains three tabs: Login, Register, Reset Password
- Login form shows mobile/card field first, then conditionally OTP or password
- Password field uses `type="text"` (NOT `type="password"` — security concern)

---

### 1.2 Registration

**File:** `register.php`  
**Access:** PUBLIC (AJAX endpoint)

- **Inputs:** `$_POST['signup_name']`, `$_POST['signup_mobile']`, `$_POST['signup_password']`
- **Validations:**
  1. Name must not be empty → returns `'signup_name'`
  2. Mobile must be exactly 10 digits: `/^[0-9]{10,10}$/i` → returns `'signup_mobile'`
  3. Mobile must not exist in `tbl_customers` → returns `'signup_mobile_exist'`
  4. Password must be 6-12 characters → returns `'signup_password'`
- **Processing:**
  - Generates UUID: `md5(uniqid(rand(), true))`
  - Hashes password: `md5($signup_password)`
  - **HARDCODED OTP:** Sets `sms_otp='1234'` and `sms_otp_status='1'` (auto-verified!)
  - INSERTs into `tbl_customers` (customer_uuid, customer_login_id=mobile, customer_login_pwd, first_name, mobile_no, sms_otp, sms_otp_status, created_date, updated_date, status='1')
  - Auto-logs-in by setting all session variables
  - Returns `'success'` or `'coupon_details'`

---

### 1.3 Password Reset

**File:** `getnewpassword.php`  
**Access:** PUBLIC (AJAX endpoint)

- **Input:** `$_POST['signin_loginid']`
- **Validation:** Must not be empty, must match existing customer
- **Processing:**
  - Generates random 6-char password from `[a-zA-Z0-9]`
  - UPDATEs `tbl_customers` SET `customer_login_pwd=md5($newpass)`, `temp_password='1'`
  - Sends new password to customer's mobile via SMS (Messaging class)
  - Returns `'success'`
- **Business Rule:** Sets `temp_password='1'` → forces password change on next login

---

### 1.4 Mandatory Password Change

**File:** `check-mandatory-password-reset.php` (include), `set-new-password.php` (page), `set-password.php` (handler)  
**Access:** AUTHENTICATED

**check-mandatory-password-reset.php:**
- Included on authenticated pages
- Queries `tbl_customers` for current user
- If `temp_password == '1'` → `header('Location: set-new-password.php')` redirect

**set-new-password.php:**
- AUTHENTICATED page (requires login + loyalty card)
- Shows form: New Password, Re-enter Password

**set-password.php:**
- AUTHENTICATED (AJAX POST handler)
- **Validations:**
  1. New password not empty
  2. New password 6-12 characters
  3. New password matches re-entered password
- **Processing:** UPDATEs `tbl_customers SET customer_login_pwd=md5($new_pass), temp_password='0'`

---

### 1.5 Change Password (Voluntary)

**Files:** `change-password.php` (page), `password.php` (handler)  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**change-password.php:**
- Shows form: Old Password, New Password, Re-enter Password
- Requires loyalty card enrollment

**password.php:**
- AUTHENTICATED (AJAX POST handler)
- **Validations:**
  1. All fields required
  2. Current password must match (md5 comparison against DB)
  3. New password 6-12 characters
  4. New password matches re-entered
- **Processing:** UPDATEs `tbl_customers SET customer_login_pwd=md5($new_pass)`

---

### 1.6 Logout

**File:** `logout.php`  
**Access:** AUTHENTICATED

- Unsets all session variables: `customer_id`, `first_name`, `mobile_no`, `loyalty_card_no`, `loyalty_card_id`
- Redirects to BASE_URL

---

## 2. HOMEPAGE & NAVIGATION

### 2.1 Homepage

**File:** `index.php` (757 lines)  
**Access:** PUBLIC

**Components:**
1. **Hero Slider Carousel:** Loads from `z_sliders` table (WHERE `status='1'`), displays images with links via owl-carousel
2. **Coupon Categories Grid:** First 8 categories from `coupons_categories` (WHERE `parent_category_id=0 AND category_status=1`), displayed as image cards. Clicking opens split-screen overlay.
3. **Split-Screen Overlay:** Category click shows full-screen overlay with 3 navigation options:
   - Coupons → `coupons.php?category=URL`
   - Flash Discounts → `flash-discounts.php?category=URL`
   - Merchants → `merchants.php?category=URL`
4. **Latest Blogs Carousel:** First 4 blogs from `z_blogs` (WHERE `view_in_home='1'`), owl-carousel
5. **Partners Section:** Partners from `z_premium_partners` (WHERE `status='1'`) loaded via Common.class.php
6. **Inline Login/Register/Reset Forms:** Duplicated JavaScript for the login flow

**Database Tables:** `z_sliders`, `coupons_categories`, `z_blogs`, `z_premium_partners`, `z_business_profile`, `z_seo`, `location`

---

### 2.2 Header / Navigation

**File:** `header.php`  
**Access:** PUBLIC (included on all pages)

**Menu Items (Conditional):**
- **Always visible:** HOME, ABOUT US, BLOG, CONTACT US
- **Logged-in users additionally see:** MY ACCOUNT, MY OFFERS, MY COMPLAINTS & SUGGESTIONS, WISHLIST, IMPORTANT DAYS, LOGOUT
- **Non-logged-in users see:** LOGIN, REGISTER

**Data Loaded:** Company info from `z_business_profile`, logo, social links

---

### 2.3 Footer

**Files:** `footer.php`, `footer-section.php`  
**Access:** PUBLIC (includes)

**footer.php:**
- Company info (address, phone, email from `z_business_profile`)
- Useful links: Home, About, Blog, Contact
- Pages: Terms & Conditions, Privacy Policy, Refund Policy, Business Sign Up
- First 4 coupon categories with links
- Newsletter signup form (no backend handler found)
- Social media links (Facebook, Twitter, YouTube, Instagram from `z_business_profile`)
- Copyright notice

**footer-section.php:**
- Static promotional banner section (hardcoded "receive a gift", "free shipping", "support" banners)
- Appears to be placeholder/template content not customized for DealMachan

---

### 2.4 Popup / Modal

**File:** `popup.php` (130 lines)  
**Access:** PUBLIC (include)

- Reusable authentication modal included on most pages
- Three sections: Login (#cd-login), Register (#cd-signup), Reset Password (#cd-reset-password)
- Login form: Mobile/Card Number → OTP (conditional) → Password (conditional) → Submit buttons shown/hidden per step
- Register form: Name, Mobile Number (maxlength=10), Password
- Reset Password form: Mobile/Card Number → "Get a new password" button

---

### 2.5 Preloader

**File:** `preloader.php`  
**Access:** PUBLIC (include)

- CSS animation preloader with 4 spinning circles
- No PHP logic

---

## 3. COUPON SYSTEM

### 3.1 Coupon Categories

**File:** `coupon-categories.php`  
**Access:** PUBLIC

- Displays ALL coupon categories (unlike homepage which shows first 8)
- Data: `coupons_categories` WHERE `parent_category_id=0 AND category_status=1`
- Each category links to `coupons.php?category=URL`
- No pagination

---

### 3.2 Coupon Listing

**File:** `coupons.php`  
**Access:** PUBLIC (LOCATION REQUIRED)

**Two Entry Modes:**
1. **By Category URL:** `?category=<category_url>`
2. **By Merchant Unit URL:** `?merchant-unit=<unit_name_url>`

**Location Requirement:**
- If no `$_SESSION['location_id']` set, redirects to `select-location.php?target=<base64_encoded_return_url>`

**Filters (Category mode):**
- Sub-category checkboxes (from `coupons_categories` WHERE `parent_category_id=<cat_id>`)
- Area checkboxes (from `area` WHERE `location_id=<session_location_id>`)
- Search keyword text input

**Query Construction (`getAllCouponsWithFilters`):**
```sql
SELECT DISTINCT cpn_coupons.*, tbl_merchant_company.merchant_company_name 
FROM cpn_coupons 
INNER JOIN cpn_coupons_location ON cpn_coupons.coupon_id = cpn_coupons_location.coupon_id 
INNER JOIN tbl_merchant_company ON cpn_coupons.coupon_merchant_id = tbl_merchant_company.merchant_company_id 
WHERE cpn_coupons_location.location_id = '$location_id' 
  AND coupon_cat_id = '$catid' 
  AND coupon_status = '1'
  [+ optional sub_category filter via cpn_coupons_sub_categories join]
  [+ optional area filter via cpn_coupon_unit join]
  [+ optional keyword LIKE on coupon_title]
```

**Favorites Feature:**
- If logged in, shows heart icon per coupon
- Favorite status checked via `getOneFavoriteUnit()` against `tbl_unit_favorite`
- Toggle via `add-to-favorites.php` / `remove-from-favorites.php`

**Display:** Coupon card with image, title, merchant name, favorite heart

---

### 3.3 Coupon Detail

**File:** `coupon-details.php`  
**Access:** PUBLIC (subscribe requires login)

**Input:** `$_GET['coupon']` (coupon_code)

**Data Loaded:**
- Coupon from `cpn_coupons` via `getOneCouponWithCouponCode($coupon_code)`
- Joined with `tbl_merchant_company` for merchant name

**Display:**
- Coupon image, title, code, description (HTML), terms & conditions
- Merchant name, redeem units (from `cpn_coupon_unit` joined with `tbl_merchant_company_units`)
- Redeem last date (`expiry_to_redeem` formatted)

**Subscription Logic:**
- If NOT logged in: Shows inline Login/Register forms
- If logged in and NOT subscribed: Shows "Get this offer now" button → calls `subscribe.php`
- If logged in and already subscribed: Shows "Already subscribed" message
- Sets `$_SESSION['coupon_page'] = 'coupon_details'` for post-login redirect

---

### 3.4 Coupon Subscription

**File:** `subscribe.php`  
**Access:** AUTHENTICATED (AJAX endpoint)

**Input:** `$_POST['coupon_id']`

**Business Rules (checked in order):**
1. **Must have loyalty card:** `$_SESSION['loyalty_card_no']` must not be empty → returns `'loyaltycard'`
2. **Maximum live subscriptions check:** `myTotalActiveCouponSubscription()` counts active subs (subscribe_status=0). If `>= allowed_max_subs_live` → returns `'max_sub'`
3. **Monthly subscription limit:** `myTotalActiveCouponSubscriptionMonth()` counts subs this month. If `>= allowed_subs_per_month` → returns `'max_month'`
4. **Subscription date not expired:** `expiry_to_subscribe` >= current date → returns `'expired'`
5. **Single redeem check:** If `redeem_type == 'Single'`, checks if customer already redeemed this coupon (subscribe_status=1) → returns `'redeemed'`
6. **Public issue limit:** `couponSubCount()` counts total subscriptions for this coupon. If `>= issued_to_public` → returns `'max_limit'`

**On Success:**
- INSERTs into `cpn_coupon_subscribe`:
  - `coupon_subscribe_uuid` = new UUID
  - `customer_id` = session customer_id
  - `coupon_id` = POST coupon_id
  - `subscribe_date` = now()
  - `subscribe_status` = 0 (Active)
  - `m_business_id` = coupon's merchant_company_id
  - `m_business_unit_ids` = coupon's merchant_unit_ids
  - `redeem_last_date` = coupon's expiry_to_redeem
- Returns `'success'`

---

## 4. FLASH DISCOUNTS

### 4.1 Flash Discount Listing

**File:** `flash-discounts.php`  
**Access:** PUBLIC (LOCATION REQUIRED)

**Two Entry Modes:**
1. **By Category URL:** `?category=<category_url>`
2. **By Merchant Unit URL:** `?merchant-unit=<unit_name_url>`

**Location Requirement:** Same as coupons — redirects to `select-location.php` if no location set

**KEY BUSINESS RULE — Loyalty Card Classification Filter:**
- If user is logged in AND has a loyalty card:
  - Loads loyalty card details via `getOneLoyaltyCardDetails($loyalty_card_id)`
  - Gets `classification_id` from the card
  - Uses `getAllFlashDiscountsWithCatidAndClassification()` which adds filter: `AND classification_id='$classification_id'`
- If NOT logged in or no loyalty card:
  - Uses `getAllFlashDiscountsWithCatid()` (no classification filter)
  - This means non-logged-in users see ALL flash discounts regardless of classification

**Query Structure:**
```sql
SELECT DISTINCT flash_discount.*, tbl_merchant_company.merchant_company_name, 
       tbl_merchant_company_units.unit_name, tbl_merchant_company_units.unit_address 
FROM flash_discount 
INNER JOIN tbl_merchant_company ON flash_discount.company_id = tbl_merchant_company.merchant_company_id 
INNER JOIN tbl_merchant_company_units ON flash_discount.unit_id = tbl_merchant_company_units.company_unit_id 
WHERE flash_discount.category_id = '$catid' 
  AND flash_discount.unit_id IN (SELECT company_unit_id FROM tbl_merchant_company_units WHERE unit_location_id='$unit_location_id')
  AND flash_discount.discount_status = '1'
  [+ optional classification_id filter]
  [+ optional sub_category filter]
  [+ optional area filter]
  [+ optional keyword search]
```

**Filters:** Sub-category, Area, Search keyword (same pattern as coupons)

---

### 4.2 Flash Discount Detail

**File:** `flash-discount-details.php`  
**Access:** PUBLIC

**Input:** `$_GET['id']` (flash discount ID)

**Data Loaded:** `getOneFlashDiscountWithId($id)` — joins flash_discount with tbl_merchant_company and tbl_merchant_company_units

**Display:**
- Discount image
- Discount description (HTML content)
- Unit name, unit address
- Merchant company name
- No subscribe/claim action — view only

---

## 5. MERCHANT/BUSINESS DIRECTORY

### 5.1 Merchant Listing

**File:** `merchants.php`  
**Access:** PUBLIC (LOCATION REQUIRED)

**Input:** `$_GET['category']` (category URL)

**Location Requirement:** Redirects to `select-location.php` if no `$_SESSION['location_id']`

**Query (`getMerchants`):**
```sql
SELECT com.company_unit_id, com.unit_name, com.unit_name_url, com.unit_address, 
       com.cover_photo, com.merchant_company_id 
FROM tbl_merchant_company_units AS com 
INNER JOIN area AS ar ON com.unit_location_area_id = ar.area_id 
WHERE location_id = '$location_id' AND unit_status = '1' 
  AND (company_unit_id IN (SELECT company_unit_id FROM tbl_merchant_company_unit_categories WHERE coupons_category_id='$catid') 
    OR company_unit_id IN (SELECT company_unit_id FROM tbl_merchant_company_unit_subcategories WHERE coupons_subcategory_id='$catid'))
  [+ optional search LIKE on unit_name, unit_address]
  [+ optional area filter]
```

**Filters:** Area checkboxes, Search keyword

**Features:**
- Favorite heart icon for logged-in users
- Each merchant links to `review.php?merchant-unit=<url>` for detail page
- Add/remove favorites via AJAX

---

### 5.2 Merchant Detail / Review Page

**File:** `review.php` (1052 lines)  
**Access:** PUBLIC (review submission requires login)

**Input:** `$_GET['merchant-unit']` (unit_name_url)

**Data Loaded:**
- Unit details: `getOneMerchantUnitWithUrl($merchant_unit)` from `tbl_merchant_company_units`
- Gallery by merchant: `getUnitGallery($unit_id)` from `tbl_merchant_company_unit_images` (LIMIT 10)
- Gallery by customers: `getUnitGalleryFromCustomers($unit_id)` from `tbl_review_images` (LIMIT 10)
- Review rating items: `getReviewItems($unit_id)` from `tbl_review_items` (WHERE status='1')
- Overall rating: `getReviewOfUnit($unit_id)` — averages 3 service ratings across all reviews
- Favorite status (if logged in): `getOneFavoriteUnit()`

**Display Sections:**
1. **Cover Photo Header** with unit name overlay
2. **Unit Details Panel:**
   - Logo, name, address
   - Star rating (1-5, averaged from tbl_reviews)
   - Working hours (listing_working_time, listing_working_time2)
   - Phone numbers, emails, website
   - Description
   - Links: Coupons by unit, Flash Discounts by unit
3. **Google Maps Embed** using `unit_loc_latitude` and `unit_loc_longitude`
4. **Gallery Tabs:**
   - "By merchant unit" — from `tbl_merchant_company_unit_images`
   - "By customers" — from `tbl_review_images`
5. **Complaint Form** (visible to all):
   - Fields: Name (pre-filled if logged in), Mobile (pre-filled), Type dropdown (Need a resolution / Feedback only), Comments textarea, Attachment file upload
   - Submits to `complaint-submit.php`
6. **Review/Rating Form** (requires login):
   - Dynamic rating items (1-5 stars each) from `tbl_review_items`
   - Review content textarea
   - Multiple image upload
   - Submits to `review-submit.php`
7. **Inline Login/Register forms** for unauthenticated users wanting to review

**Rating Calculation:**
```sql
SELECT COUNT(id) AS total,
       TRUNCATE(((SUM(service1_rating)/COUNT(id)) + (SUM(service2_rating)/COUNT(id)) + 
                  (SUM(service3_rating)/COUNT(id))) / 3, 1) AS our_rating 
FROM tbl_reviews WHERE unit_id='$unit_id'
```

---

## 6. LOYALTY CARD SYSTEM

### 6.1 Loyalty Card Selection

**File:** `loyalty-cards.php`  
**Access:** AUTHENTICATED (no loyalty card needed — this IS the enrollment page)

**Data Loaded:** `getAllActiveLoyaltyCards($location_id)`:
```sql
SELECT id, card_title, image1, image2, allowed_subs_per_month, allowed_max_subs_live 
FROM z_loyalty_cards 
WHERE status='1' AND available_for_public='1' 
  AND id IN (SELECT loyalty_card_id FROM z_loyalty_cards_area WHERE location_id='$curr_location_id')
```

**Display:** Each card shows:
- Card title
- Front/back images
- Monthly subscription limit
- Max live subscriptions
- "Choose this Card" button → calls `selectloyaltycard.php`

**Business Rule:** Cards are filtered by location — only cards available in the customer's current location are shown.

---

### 6.2 Loyalty Card Assignment

**File:** `selectloyaltycard.php`  
**Access:** AUTHENTICATED (AJAX endpoint)

**Input:** `$_POST['card_id']`

**Processing:**
1. Gets card details: `getOneLoyaltyCardDetails($card_id)` (including `start_number`)
2. Gets latest card number sequence: `getLatestLoyaltyCardNumber($start_number)`
3. Calculates new number: `$start_number + $end_number` (sequential)
4. INSERTs into `z_loyalty_card_number`:
   - `start_number`, `end_number` (incremented), `card_id`, `customer_id`, `assigned_on=now()`
5. UPDATEs `tbl_customers`:
   - `loyalty_enrolled = '1'`
   - `loyalty_card_no = $new_card_number`
   - `loyalty_join_date = now()`
   - `loyalty_card_id = $card_id`
6. Updates session: `$_SESSION['loyalty_card_no']`, `$_SESSION['loyalty_card_id']`
7. Returns `'success'`

**Business Rule:** Sequential card number generation from a configured start number per card type.

---

### 6.3 Loyalty Card Display

**File:** `loyalty-card.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

- Simple display page showing loyalty card information
- Appears to have hardcoded card number reference (placeholder/demo page)

---

### 6.4 My Account (Card Dashboard)

**File:** `my-account.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**Display:**
- **Loyalty Card Widget:** Front/back card images (from `z_loyalty_cards`), tap-to-reveal card number animation
- **Loyalty Card Partners Carousel:** From `z_loyalty_cards_partners` joined with `z_premium_partners`
- **Coupon Categories Grid** with split-screen navigation (same as homepage)
- Quick links: Profile, Offer Zone

---

## 7. PROFILE MANAGEMENT

### 7.1 View Profile

**File:** `profile.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**Data Loaded:** `getProfile($customer_id)` — SELECT * FROM `tbl_customers`

**Display Fields:**
- Profile image
- First name, Last name
- Email, Mobile number
- Date of birth, Gender
- Job category (resolved from `job_category` table)
- Occupation
- Residing city, Address, Pincode
- Preferred location (resolved from `location` table)
- Links to Edit Profile and Change Password

---

### 7.2 Edit Profile

**File:** `edit-profile.php` (page), `userupdate.php` (handler)  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**edit-profile.php Form Fields:**
- Profile image file upload
- First name (required), Last name
- Email, Mobile number (required)
- Date of birth (date picker), Gender (dropdown: Male/Female/Other)
- Job category (dropdown from `job_category` table)
- Occupation
- Residing city, Address, Pincode
- Preferred location (dropdown from `location` table)

**userupdate.php Handler:**
- **File Upload Validation:** Allowed types: `jpg, png, jpeg, pdf, doc, docx`
- **Validations:**
  1. First name required → returns `'first_name'`
  2. Mobile number required → returns `'mobile'`
  3. Mobile not used by another account → calls `checkMobilenumberExistInAnotherAccount($mobile, $customer_id)`
  4. If mobile changed and exists in another account → returns `'mobile_used'`
- **Processing:** UPDATEs `tbl_customers` SET all profile fields including `profile_is_updated='1'`
- **Edge Case:** If no file uploaded, keeps existing profile_img

**Area Loading (`loadarea.php`):**
- AUTHENTICATED AJAX endpoint
- Input: `$_POST['location_id']`
- Returns JSON array of areas for the given location (for the edit-profile form's dynamic area dropdown)

---

## 8. OFFER ZONE (MY OFFERS)

### 8.1 My Subscribed Coupons

**File:** `offer-zone.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**Auto-Expiry Logic (on page load):**
```sql
UPDATE cpn_coupon_subscribe 
SET subscribe_status='-1' 
WHERE customer_id='$cid' AND subscribe_status='0' AND redeem_last_date < CURDATE()
```
This automatically expires all active subscriptions past their redeem date.

**Sort Options (dropdown):**
- All Active
- Active (subscribe_status=0)
- Redeemed (subscribe_status=1)
- Expired (subscribe_status=-1)
- Gifted → links to `gifted-offer-zone.php`
- Store → links to `store-offer.php`

**Data Loaded:** `getMyCoupons($customer_id, $sort)`:
```sql
SELECT cpn_coupon_subscribe.*, cpn_coupons.*, tbl_merchant_company.merchant_company_name 
FROM cpn_coupon_subscribe 
INNER JOIN cpn_coupons ON cpn_coupon_subscribe.coupon_id = cpn_coupons.coupon_id 
INNER JOIN tbl_merchant_company ON cpn_coupons.coupon_merchant_id = tbl_merchant_company.merchant_company_id 
WHERE cpn_coupon_subscribe.customer_id = '$customer_id'
[+ status filter based on sort]
ORDER BY coupon_subscribe_id DESC
```

**Each Coupon Displays:**
- Coupon image, title, merchant name
- Redeem unit name (resolved from `tbl_merchant_company_units`)
- Redeem last date
- Redeemed date & unit (if redeemed)
- Status badge: Active (blue), Redeemed (green), Expired (red)

**Gifted Coupons Slider:** At top of page, shows gifted coupons from `cpn_gifting` table (WHERE `allocated_to` FIND_IN_SET customer_id AND `gifting_type='normal'`)

---

### 8.2 Gifted Coupons

**File:** `gifted-offer-zone.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**Data Loaded:** `getMyGiftedCoupons($customer_id, $sort)`:
```sql
SELECT cpn_gifting.*, cpn_coupons.*, tbl_merchant_company.merchant_company_name 
FROM cpn_gifting 
INNER JOIN cpn_coupons ON cpn_gifting.coupon_id = cpn_coupons.coupon_id 
INNER JOIN tbl_merchant_company ON cpn_coupons.coupon_merchant_id = tbl_merchant_company.merchant_company_id 
WHERE FIND_IN_SET('$customer_id', cpn_gifting.allocated_to)
  AND gifting_type = 'normal'
ORDER BY gifting_id DESC
```

**Display:** Same format as offer-zone.php. Includes same sort dropdown and gifted coupons slider.

---

### 8.3 Store Coupons

**File:** `store-offer.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**Data Loaded:** `getMyStoreCoupons($customer_id)`:
```sql
SELECT cpn_store_coupons_assignment.*, cpn_store_coupons.*, tbl_merchant_company.merchant_company_name 
FROM cpn_store_coupons_assignment 
INNER JOIN cpn_store_coupons ON cpn_store_coupons_assignment.store_coupon_id = cpn_store_coupons.store_coupon_id 
INNER JOIN tbl_merchant_company ON cpn_store_coupons.store_coupon_merchant_id = tbl_merchant_company.merchant_company_id 
WHERE cpn_store_coupons_assignment.customer_id = '$customer_id' 
  AND assign_status = '1' 
  AND cpn_store_coupons.expiry_to_redeem >= CURDATE()
```

**Display:** Same card format — coupon image, title, merchant, expiry date. Includes gifted coupons slider (buffer time type) at top.

---

## 9. WISHLIST / FAVORITES

### 9.1 Wishlist Page

**File:** `wishlist.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**Data Loaded:** `getMyWishlist($customer_id)`:
```sql
SELECT tbl_unit_favorite.*, tbl_merchant_company_units.unit_name, 
       tbl_merchant_company_units.unit_name_url, tbl_merchant_company_units.cover_photo 
FROM tbl_unit_favorite 
INNER JOIN tbl_merchant_company_units ON tbl_unit_favorite.unit_id = tbl_merchant_company_units.company_unit_id 
WHERE tbl_unit_favorite.customer_id = '$customer_id'
```

**Display:** Each favorite shows unit cover photo, name. Remove button calls `remove-from-favorites.php`.

---

### 9.2 Add to Favorites

**File:** `add-to-favorites.php`  
**Access:** AUTHENTICATED (AJAX endpoint)

**Inputs:** `$_POST['unit_ids']` (comma-separated unit IDs), `$_POST['merchant_id']`

**Processing:**
- Splits `unit_ids` by comma
- For each unit_id:
  - Checks if already favorited: `getOneFavoriteUnit($customer_id, $unit_id)`
  - If not: INSERTs into `tbl_unit_favorite` (customer_id, unit_id, merchant_id)
- Returns `'success'`

**Edge Case:** Handles multiple units per merchant (comma-separated IDs)

---

### 9.3 Remove from Favorites

**File:** `remove-from-favorites.php`  
**Access:** AUTHENTICATED (AJAX endpoint)

**Input:** `$_POST['unit_id']`

**Processing:** `DELETE FROM tbl_unit_favorite WHERE customer_id='$customer_id' AND unit_id='$unit_id'`

---

## 10. REVIEWS & RATINGS

### 10.1 Review Submission

**File:** `review-submit.php`  
**Access:** AUTHENTICATED (AJAX endpoint)

**Inputs:**
- `$_POST['unit_id']` — merchant unit being reviewed
- `$_POST['content']` — review text (required)
- `$_POST['rating_1']`, `$_POST['rating_2']`, `$_POST['rating_3']` — three service ratings (1-5)
- `$_FILES['attachments']` — multiple image files

**Validation:** Content must not be empty → returns `'content'`

**Business Rule — Update or Insert:**
- Checks `getOneratingWithUnitAndCustomer($unit_id, $customer_id)`:
  - If review exists → UPDATEs `tbl_reviews` (overwrites previous review)
  - If no review → INSERTs into `tbl_reviews`

**Table: `tbl_reviews`**
- Fields: id, unit_id, customer_id, service1_rating, service2_rating, service3_rating, review, review_on (unix timestamp)

**Image Upload:**
- After review is saved, processes `$_FILES['attachments']` array
- Allowed types: JPG, PNG, JPEG
- Filename prefix: `date("Ymdhis_")`
- Upload directory: `frontend/backend/repository/profile/`
- INSERTs into `tbl_review_images`: id, review_id, unit_id, image

**Edge Case:** A customer can overwrite their review for the same unit — all previous ratings are replaced. However, old review images are NOT deleted when updating.

---

### 10.2 Review Display (within review.php)

**Dynamic Rating Items:**
- Loaded from `tbl_review_items` WHERE `merchant_company_unit_id = $unit_id AND review_item_status='1'`
- Ordered by `review_item_number ASC`
- Each item gets a 1-5 star rating widget
- Three rating fields mapped to `service1_rating`, `service2_rating`, `service3_rating` (hardcoded to 3 items max)

**Overall Rating Calculation:**
- Average of 3 service ratings across all reviews for the unit
- Formula: `((avg_service1 + avg_service2 + avg_service3) / 3)` truncated to 1 decimal
- Capped at 5.0 in PHP code

---

## 11. COMPLAINTS & SUGGESTIONS

### 11.1 Complaint Submission

**File:** `complaint-submit.php`  
**Access:** PUBLIC (but auto-creates customer account if mobile doesn't exist!)

**Inputs:**
- `$_POST['r_name']` — complainant name (required)
- `$_POST['mobile']` — mobile number (required, 10-digit regex)
- `$_POST['description']` — complaint text (required)
- `$_POST['c_type']` — complaint type (Need a resolution / Feedback only)
- `$_POST['unit_id']` — merchant unit ID
- `$_FILES['attachment']` — optional image

**Validations:**
1. Name required → returns `'r_name'`
2. Mobile must be 10 digits: `/^[0-9]{10,10}$/i` → returns `'mobile'`
3. Description required → returns `'description'`
4. File type must be image (jpg/png/jpeg) → returns `'filetype'`

**CRITICAL BUSINESS RULE — Auto Account Creation:**
- Checks if mobile exists in `tbl_customers`
- If EXISTS → uses that customer_id
- If NOT EXISTS → auto-creates a new customer account:
  - Generates UUID and random 6-char password (md5 hashed)
  - INSERTs into `tbl_customers` with name, mobile, random password
  - Does NOT send password to user and does NOT auto-login
  - Customer won't know their password!

**Database Impact:** INSERTs into `tbl_merchant_unit_complaints`:
- Fields: id, customer_id, cname, mobile, unit_id, complaint_type, complaint, image, complaint_on (unix timestamp)

---

### 11.2 My Complaints & Suggestions

**File:** `my-complaints-and-suggestions.php` (489 lines)  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**Data Loaded:** `getMyComplaints($customer_id)`:
```sql
SELECT muc.*, mu.unit_name, mu.unit_address 
FROM tbl_merchant_unit_complaints AS muc 
INNER JOIN tbl_merchant_company_units AS mu ON muc.unit_id = mu.company_unit_id 
WHERE customer_id = '$customer_id' AND archive = '1' 
ORDER BY id DESC
```

**Display per complaint:**
- Complaint type (Need a resolution / Feedback only)
- Complaint text
- Attached image
- Complaint date (formatted from unix timestamp)
- Target merchant unit name
- Reply from merchant (if any): reply text, reply timestamp, replying unit name
- **Archive button** → calls `archive-complaints.php`

**Includes:** Full inline login/register/reset JavaScript (duplicated from other pages)

---

### 11.3 Archive Complaint

**File:** `archive-complaints.php`  
**Access:** AUTHENTICATED (AJAX endpoint)

**Input:** `$_POST['curr_id']` — complaint ID

**Processing:** `UPDATE tbl_merchant_unit_complaints SET archive='0' WHERE id='$curr_id' AND customer_id='$customer_id'`

**Business Rule:** Setting `archive='0'` hides the complaint from the listing (which filters `archive='1'`). The `AND customer_id` ensures a user can only archive their own complaints.

---

## 12. IMPORTANT DAYS

### 12.1 Important Days Page

**File:** `important-days.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED

**Data Loaded:** `getImportantDays($customer_id)`:
```sql
SELECT * FROM tbl_important_days WHERE customer_id = '$customer_id'
```

**Display:**
- Add form: Event dropdown (Birthday / Anniversary / Others) + Day (1-31) + Month (January-December)
- If "Others" selected: text input for custom event name
- Existing days table: Event, Day, Month
- No edit or delete functionality

---

### 12.2 Important Days Submit

**File:** `important-days-submit.php`  
**Access:** AUTHENTICATED + LOYALTY CARD REQUIRED (AJAX POST)

**Inputs:**
- `$_POST['occassion']` — event type
- `$_POST['others']` — custom event name (if "Others" selected)
- `$_POST['day']` — day number
- `$_POST['month']` — month name

**Validation:**
- If occasion is "Others" and others field is empty → returns `'others'`
- If "Others", replaces occasion value with the custom text

**Processing:** INSERTs into `tbl_important_days`:
- Fields: id, customer_id, occassion, day, month
- Uses `addslashes(trim())` for input sanitization

**NOTE:** No validation against duplicate entries. No year field. No delete capability.

---

## 13. BUSINESS SIGN-UP

### 13.1 Business Sign-Up Page

**File:** `business-sign-up.php` (526 lines)  
**Access:** PUBLIC

**Data Loaded:** Page content from `z_page_contents` (page_url='business-sign-up')

**Form Fields:**
- Name (required)
- Organization
- Business category
- Email
- Phone number (required, 10-digit validation)
- Message textarea

**Includes:** Full inline login/register/reset JavaScript + business signup form handler

---

### 13.2 Business Sign-Up Handler

**File:** `businesssignup.php`  
**Access:** PUBLIC (AJAX POST)

**Inputs:** `$_POST['Name']`, `$_POST['Organization']`, `$_POST['Business_category']`, `$_POST['Email']`, `$_POST['Phone_number']`, `$_POST['Message']`

**Validations:**
1. Name required → returns `'Name'`
2. Phone 10 digits: `/^[0-9]{10,10}$/i` → returns `'Phone_number'`
3. Phone not already signed up: `checkEntryExistWithMobile($Phone_number)` → returns `'signup_mobile_exist'`

**Processing:** INSERTs into `z_business_signup`:
- Fields: id, Name, Organization, Business_category, Email, Phone_number, Message, Status (DEFAULT), Assigned_admin='1'

---

## 14. CMS PAGES & BLOG

### 14.1 Generic CMS Page

**File:** `page.php`  
**Access:** PUBLIC

**Inputs:** `$_GET['page']` (page URL slug), `$_GET['id']` (SEO ID)

**Data Loaded:**
- Page content: `getPageData2($page)` from `z_page_contents WHERE page_url='$page'`
- SEO data: `getPageSeo($id)` from `z_seo`

**Usage:** Used for terms-and-conditions, privacy-policy, refund-policy pages

---

### 14.2 About Page

**File:** `about.php`  
**Access:** PUBLIC

- Loads page content from `z_page_contents WHERE id=4`
- Static CMS content display

---

### 14.3 Blog Listing

**File:** `blog.php`  
**Access:** PUBLIC

**Data Loaded:** `getBlogs()`:
```sql
SELECT * FROM z_blogs WHERE view_in_home = '1' ORDER BY blog_id DESC
```

**Display:** Blog cards with image, title, excerpt, date link to detail

---

### 14.4 Blog Detail

**File:** `blog-details.php`  
**Access:** PUBLIC

**Input:** `$_GET['blog']` (blog page_url)

**Data Loaded:** `getOneBlogWithUrl($blog)` from `z_blogs WHERE page_url='$url'`

**Display:** Full blog content (HTML), title, featured image, SEO fields from blog record

---

## 15. CONTACT & COMMUNICATION

### 15.1 Contact Page

**File:** `contact.php`  
**Access:** PUBLIC

**Display:**
- Contact form: Name, Phone, Email, Subject, Message
- Google Maps embed with company location
- Company address, phone, email from `z_business_profile`

---

### 15.2 Contact Form Handler

**File:** `contact-email.php`  
**Access:** PUBLIC (AJAX POST)

**Inputs:** `$_POST['phone']`, `$_POST['name']`, `$_POST['email']`, `$_POST['subject']`, `$_POST['message']`

**Validations:**
1. Phone required → returns `'phone'`
2. Name required → returns `'name'`
3. Email required → returns `'email'`
4. Subject required → returns `'subject'`
5. Message required → returns `'message'`

**Processing:**
- Sends HTML email using PHP `mail()` function
- TO: `$common_data['email_u']` (company email from `z_business_profile`)
- CC: `$common_data['email_i']` (company info email)
- FROM: submitted email address
- Content: HTML formatted with all submitted fields

---

### 15.3 SMS Messaging

**File:** `Messaging.php`  
**Access:** (internal class, not directly accessible)

**Class: `Messaging`**
- **API:** alertin.co.in SMS gateway
- **Credentials:** Hardcoded API key, sender ID "DMACHN", entity ID

**Methods:**
1. `sendsms($phonenumber, $smstext, $templateid)` — sends SMS via HTTP GET to alertin.co.in API
2. `otp_sms_to_user($mobile, $otp)` — formats OTP message with template ID `1207166387979498020`
3. `new_password_sms_to_user($mobile, $password)` — formats password SMS with template ID `1207166387994296501`

---

## 16. LOCATION MANAGEMENT

### 16.1 Location Selection Page

**File:** `select-location.php`  
**Access:** PUBLIC

**Input:** `$_GET['target']` — base64-encoded redirect URL (where to go after selecting location)

**Data Loaded:** `getLocations()` — all locations from `location` table

**Display:** All locations as clickable tags/badges. Clicking submits to `select-location-submit.php`

---

### 16.2 Location Selection Handler

**File:** `select-location-submit.php`  
**Access:** PUBLIC (AJAX)

**Input:** `$_POST['location_id']`

**Processing:**
- Loads location record from `location` table
- Sets `$_SESSION['location_id']` = location_id
- Sets `$_SESSION['location_name']` = location_name

---

### 16.3 Dual Location System (IMPORTANT)

The application has **two separate location session mechanisms** used by different classes:

**System 1 — `Common.class.php`:**
- Uses `$_SESSION['listing_location']` with prefix convention:
  - `L` prefix = Location ID (e.g., `L5`)
  - `A` prefix = Area ID (e.g., `A12`)
- Set via `config.php` from `$_GET['location']`
- Used in: coupon listings (Common class), category queries

**System 2 — `Mysqloperations.php`:**
- Uses `$_SESSION['location_id']` and `$_SESSION['location_name']`
- Set via `select-location-submit.php`
- Used in: coupons.php, flash-discounts.php, merchants.php, loyalty-cards.php

**Potential Issue:** These two systems are independent and may become inconsistent if one is set but not the other.

---

## 17. INFRASTRUCTURE & SHARED COMPONENTS

### 17.1 Configuration

**File:** `config.php`  
**Access:** (include, loaded by all pages)

**Operations:**
1. Starts session: `session_start()`
2. Initializes `$_SESSION['listing_location']` from `$_GET['location']` parameter
3. Sets `$_SESSION['coupon_page']` based on current script filename
4. Defines constants:
   - `BASE_URL` = `'https://dealmachan.com/'`
   - `SITE_URL` = `'https://frontend.dealmachan.com/'`
5. Error reporting: `error_reporting(0)` in production (errors suppressed)

---

### 17.2 Common.class.php (1042 lines)

**Database Access:** Uses global `$DB` variable (ADOdb-style ORM)

**Key Methods:**
| Method | Purpose | Table(s) |
|--------|---------|----------|
| `get_coupons_listing()` | List coupons by category + location | cpn_coupons, coupons_categories |
| `get_coupon_categories()` | Get category tree | coupons_categories |
| `get_tags_by_categories()` | Tags for a category | tbl_listing_tags |
| `get_sub_categories()` | Subcategories | coupons_categories |
| `get_customer_gallery()` | Customer-uploaded gallery | tbl_customer_files |
| `insert_customer_file()` | Save uploaded file record | tbl_customer_files |
| `my_business_reviews()` | Reviews for a business | tbl_customer_reviews |
| `get_my_business_avg()` | Average rating | tbl_customer_reviews |
| `get_ma_rating()` | Rating for sub-item | tbl_customer_ratings |
| `get_review_items()` | Review criteria | tbl_review_items |
| `getFavoriteStories()` | Favorite stories | tbl_favorite_stories |
| `getFormInputs()` / `getFormInputsSub()` | Mystery shopper form | tbl_mystery_form |
| `get_my_tags()` | Tags for business | tbl_listing_tags |
| `get_site_images()` | Site images (home/partner/footer) | tbl_site_images |
| `get_all_listing_types()` | Business listing types | tbl_listing_types |
| `getActiveLocationData()` | Active locations | location |
| `myGalleryItems()` | Gallery items | tbl_mc_unt_gallery |
| `get_my_business_unit_details_with_id()` | Unit by ID | tbl_merchant_company_units |
| `get_my_business_unit_details_with_uuid()` | Unit by UUID | tbl_merchant_company_units |
| `get_basic_listing()` / `get_basic_listing_type_wise()` | Business listings with pagination | tbl_merchant_company_units |
| `get_main_locations()` | Main locations | location |
| `get_loactions_all_area_by_loc_id()` | Areas by location | area |
| `get_categories()` | All categories | category |
| `toUrl()` | String to URL slug | (utility) |
| `paginationUser()` | Generate pagination HTML | (utility) |
| `pagination()` / `pagination1()` | Admin pagination variants | (utility) |
| `getTime24H()` / `getTime()` | Current time in timezone | (utility) |
| `generate_random_letters()` | Random digits string | (utility) |
| `nextAutoIncrementId()` | Next auto-increment | (SHOW TABLE STATUS) |
| `uuid()` / `uuidDb()` | UUID generation | (utility / MySQL UUID()) |
| `notificationMessages()` | Format alert messages | (utility) |
| `getIP()` | Client IP address | (utility) |
| `getPagingLimit()` | Paging limit (hardcoded 10) | (utility) |

---

### 17.3 Mysqloperations.php (911 lines)

**Database Access:** Direct mysqli connection

**Connection:** `new mysqli("localhost", "mpclubuser", "<password>", "mpclub")`

**Core DB Methods:**
| Method | Description |
|--------|-------------|
| `selectAllData($qry)` | Execute query, return all rows as assoc array |
| `selectOneRow($qry)` | Execute query, return single row  |
| `executeQuery($qry)` | Execute UPDATE/INSERT, return true/false |
| `executeQueryWithPk($qry)` | Execute INSERT, return insert_id |
| `insertData($qry)` | Execute INSERT/UPDATE, return true/false |

**Key Business Methods:**
| Method | Table(s) | Used By |
|--------|----------|---------|
| `getSliders()` | z_sliders | index.php |
| `getLoyaltyCardPartners()` | z_loyalty_cards_partners, z_premium_partners | my-account.php |
| `getCommonData()` | z_business_profile | All pages |
| `getPageData()` / `getPageData2()` | z_page_contents | page.php, about.php |
| `getPageSeo()` | z_seo | All pages |
| `checkAccountExistWithMobile()` | tbl_customers | N/A |
| `loginUser()` | tbl_customers | login.php |
| `checkMobileOrCardNumberExistInCustomers()` | tbl_customers | mobilecheck.php |
| `getBlogs()` / `getOneBlog()` / `getOneBlogWithUrl()` | z_blogs | blog.php, blog-details.php |
| `getLatestBlogs()` | z_blogs | index.php |
| `getMyWishlist()` | tbl_unit_favorite, tbl_merchant_company_units | wishlist.php |
| `getImportantDays()` | tbl_important_days | important-days.php |
| `getAllCouponsWithFilters()` | cpn_coupons, cpn_coupons_location, etc. | coupons.php |
| `getAllFlashDiscountsWithCatid()` | flash_discount, tbl_merchant_company, tbl_merchant_company_units | flash-discounts.php |
| `getAllFlashDiscountsWithCatidAndClassification()` | Same + classification filter | flash-discounts.php |
| `getOneCouponWithCouponCode()` | cpn_coupons | coupon-details.php |
| `getOneFlashDiscountWithId()` | flash_discount | flash-discount-details.php |
| `getCouponCategories()` / `getAllCouponCategories()` | coupons_categories | Multiple pages |
| `getCouponSubCategoriesByCatId()` | coupons_categories | coupons.php, flash-discounts.php |
| `getAreasByLocationId()` | area | coupons.php, flash-discounts.php |
| `getLocations()` / `getActiveLocationData()` | location | Multiple pages |
| `getCouponsList()` | cpn_coupons | N/A |
| `getOneCoupon()` | cpn_coupons | subscribe.php |
| `myCouponCount()` | cpn_coupon_subscribe | subscribe.php |
| `myTotalActiveCouponSubscription()` | cpn_coupon_subscribe | subscribe.php |
| `myTotalActiveCouponSubscriptionMonth()` | cpn_coupon_subscribe | subscribe.php |
| `couponSubCount()` | cpn_coupon_subscribe | subscribe.php |
| `getProfile()` | tbl_customers | profile.php |
| `getJobCategories()` | job_category | edit-profile.php |
| `loadAreas()` | area | loadarea.php |
| `checkMobilenumberExistInAnotherAccount()` | tbl_customers | userupdate.php |
| `updateUserData()` | tbl_customers | userupdate.php |
| `getMyCoupons()` | cpn_coupon_subscribe, cpn_coupons | offer-zone.php |
| `getMyStoreCoupons()` | cpn_store_coupons_assignment, cpn_store_coupons | store-offer.php |
| `getMyGiftedCoupons()` | cpn_gifting, cpn_coupons | gifted-offer-zone.php |
| `getMyCouponsRedeemUnit()` | tbl_merchant_company_units | offer-zone.php |
| `getAllActiveLoyaltyCards()` | z_loyalty_cards, z_loyalty_cards_area | loyalty-cards.php |
| `getOneLoyaltyCardDetails()` | z_loyalty_cards | selectloyaltycard.php |
| `getLatestLoyaltyCardNumber()` | z_loyalty_card_number | selectloyaltycard.php |
| `getOneMerchantUnitWithUrl()` | tbl_merchant_company_units | review.php |
| `getUnitGallery()` | tbl_merchant_company_unit_images | review.php |
| `getUnitGalleryFromCustomers()` | tbl_review_images | review.php |
| `getReviewItems()` | tbl_review_items | review.php |
| `getReviewOfUnit()` | tbl_reviews | review.php |
| `getOneratingWithUnitAndCustomer()` | tbl_reviews | review-submit.php |
| `getMyComplaints()` | tbl_merchant_unit_complaints, tbl_merchant_company_units | my-complaints-and-suggestions.php |
| `getMerchants()` | tbl_merchant_company_units, area | merchants.php |
| `getOneLocationWithLocationId()` | location | select-location-submit.php |
| `getOneFavoriteUnit()` | tbl_unit_favorite | add-to-favorites.php |
| `checkEntryExistWithMobile()` | z_business_signup | businesssignup.php |

---

### 17.4 .htaccess

- HTTP to HTTPS redirect only: `RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`

---

### 17.5 Subdirectories (Frontend Assets Only)

| Directory | Contents | Purpose |
|-----------|----------|---------|
| `best-buy/` | HTML templates, CSS, JS | E-commerce template (unused) |
| `heroslider/` | HTML, CSS, JS, README | Hero slider component |
| `popup/` | HTML, CSS, JS, SCSS | Login/register modal styling |
| `quick-view/` | HTML, CSS, images | Quick view overlay styling |
| `review/` | CSS, JS, fonts | Review page styling framework |
| `vertical-tabs/` | HTML, JS | Vertical tabs component |
| `photo-gallery/` | JS, CSS | Photo gallery component |

None of these directories contain PHP files — they are all frontend asset libraries/templates.

---

## 18. DATABASE SCHEMA SUMMARY

### Core Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `tbl_customers` | Customer accounts | customer_id, customer_uuid, customer_login_id, customer_login_pwd (MD5), first_name, last_name, email_id, mobile_no, date_of_birth, gender, occupation, residing_city, address, pincode, profile_img, sms_otp, sms_otp_status, loyalty_enrolled, loyalty_card_no, loyalty_card_id, loyalty_join_date, temp_password, profile_is_updated, preferred_location_id, job_category_id, status |
| `location` | Geographic locations | location_id, location_name |
| `area` | Areas within locations | area_id, location_id, area_name |
| `job_category` | Job categories for profiles | (standard id/name) |

### Coupon Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `cpn_coupons` | Coupon definitions | coupon_id, coupon_code, coupon_title, coupon_desc, coupon_image, coupon_terms_condition, coupon_cat_id, coupon_merchant_id, merchant_unit_ids, expiry_to_subscribe, expiry_to_redeem, issued_to_public, redeem_type (Single/Multiple), coupon_status, Meta fields |
| `cpn_coupon_subscribe` | Customer subscriptions | coupon_subscribe_id, coupon_subscribe_uuid, customer_id, coupon_id, subscribe_date, subscribe_status (0=Active, 1=Redeemed, -1=Expired), m_business_id, m_business_unit_ids, redeem_last_date, r_unit, r_date |
| `cpn_coupons_location` | Coupon-location mapping | coupon_id, location_id |
| `cpn_coupon_unit` | Coupon-unit mapping | coupon_id, unit_id, area_id |
| `cpn_coupons_sub_categories` | Coupon sub-category mapping | coupon_id, sub_category_id |
| `coupons_categories` | Category hierarchy | category_id, category_name, category_url, parent_category_id (0=parent), category_status |
| `cpn_gifting` | Gifted coupons | gifting_id, coupon_id, allocated_to (CSV of customer_ids), gifting_type (normal/buffertime) |
| `cpn_store_coupons` | Store-specific coupons | store_coupon_id, store_coupon_merchant_id, expiry_to_redeem |
| `cpn_store_coupons_assignment` | Store coupon assignments | store_coupon_id, customer_id, assign_status |

### Flash Discount Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `flash_discount` | Flash discounts | discount_id, company_id, unit_id, category_id, classification_id, discount_description, discount_image, discount_status |

### Merchant Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `tbl_merchant_company` | Merchant companies | merchant_company_id, merchant_company_name |
| `tbl_merchant_company_units` | Business units | company_unit_id, merchant_company_id, unit_name, unit_name_url, unit_address, unit_logo, cover_photo, unit_location_id, unit_location_area_id, unit_category_id, unit_sub_category_id, listing_type_ids, listing_tag_ids, unit_contact_nos, unit_contact_emails, unit_website, unit_description, listing_working_time/2, unit_loc_latitude, unit_loc_longitude, unit_status |
| `tbl_merchant_company_unit_images` | Unit gallery images | id, company_unit_id, image |
| `tbl_merchant_company_unit_categories` | Unit category mapping | company_unit_id, coupons_category_id |
| `tbl_merchant_company_unit_subcategories` | Unit subcategory mapping | company_unit_id, coupons_subcategory_id |

### Review & Complaint Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `tbl_reviews` | Customer reviews | id, unit_id, customer_id, service1_rating, service2_rating, service3_rating, review, review_on |
| `tbl_review_items` | Rating criteria per unit | id, merchant_company_unit_id, review_item_name, review_item_number, review_item_status |
| `tbl_review_images` | Review attachments | id, review_id, unit_id, image |
| `tbl_merchant_unit_complaints` | Complaints | id, customer_id, cname, mobile, unit_id, complaint_type, complaint, image, complaint_on, reply, reply_time, archive |

### Loyalty Card Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `z_loyalty_cards` | Card types | id, card_title, image1, image2, allowed_subs_per_month, allowed_max_subs_live, classification_id, start_number, available_for_public, status |
| `z_loyalty_cards_area` | Card-location availability | loyalty_card_id, location_id |
| `z_loyalty_cards_partners` | Card partner brands | (linking to z_premium_partners) |
| `z_loyalty_card_number` | Card number sequence | id, start_number, end_number, card_id, customer_id, assigned_on |

### Engagement Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `tbl_unit_favorite` | Wishlist/favorites | customer_id, unit_id, merchant_id |
| `tbl_important_days` | Customer events | id, customer_id, occassion, day, month |
| `z_business_signup` | Business registration | id, Name, Organization, Business_category, Email, Phone_number, Message, Status, Assigned_admin |

### CMS & Site Config Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `z_business_profile` | Company settings | companyname, email_u, email_i, telephone, address, logo, social links |
| `z_page_contents` | CMS pages | id, page, content, page_url |
| `z_seo` | SEO metadata | id, Page_title, Meta_description, Meta_keywords, Meta_author |
| `z_sliders` | Homepage sliders | id, image, link, status |
| `z_blogs` | Blog posts | blog_id, title, content, featured_image, page_url, view_in_home, Meta fields |
| `z_premium_partners` | Partner brands | id, name, logo, status |

### Other Referenced Tables

| Table | Purpose |
|-------|---------|
| `category` / `subcategory` | Business directory categories |
| `tbl_listing_types` / `tbl_listing_tags` | Business listing metadata |
| `tbl_site_images` | Site images (home/partner/footer sections) |
| `tbl_mc_unt_gallery` | Merchant unit gallery (Common.class.php) |
| `tbl_customer_files` | Customer-uploaded files |
| `tbl_mystery_form` | Mystery shopper forms |
| `tbl_favorite_stories` | Favorite stories feature |
| `tbl_customer_reviews` | Customer reviews (Common.class.php variant) |
| `tbl_customer_ratings` | Per-item ratings (Common.class.php variant) |

---

## 19. SECURITY CONCERNS

### Critical Issues

1. **SQL Injection Vulnerability (SEVERE):** Almost ALL user inputs are directly concatenated into SQL queries without parameterized queries or proper escaping. Every POST/GET parameter goes straight into SQL strings. Only `addslashes()` is used in a few places (important-days-submit.php).

2. **MD5 Password Hashing (INSECURE):** All passwords are hashed with MD5, which is cryptographically broken and easily reversible via rainbow tables. Should use `password_hash()` with bcrypt/argon2.

3. **Hardcoded OTP in Registration:** `register.php` sets OTP to `'1234'` and marks it as verified (`sms_otp_status='1'`), bypassing SMS verification entirely for new registrations.

4. **Password Field as `type="text"`:** Login forms in `popup.php` use `type="text"` for password input, exposing passwords on screen.

5. **No CSRF Protection:** No CSRF tokens on any forms. All AJAX endpoints can be called from any domain.

6. **Hardcoded Database Credentials:** MySQL username and password are hardcoded in `Mysqloperations.php`.

7. **SMS API Key Exposure:** API key for alertin.co.in SMS gateway is hardcoded in `Messaging.php`.

8. **Auto-Account Creation in Complaints:** `complaint-submit.php` creates customer accounts with random passwords that are never communicated to the user.

9. **No Rate Limiting:** No rate limiting on login attempts, OTP verification, or password reset — vulnerable to brute force attacks.

10. **File Upload Vulnerabilities:** File uploads validate extension only (not MIME type), and filenames use date prefix + original name without sanitization for special characters.

11. **Error Reporting Suppressed:** `error_reporting(0)` hides all errors, making debugging impossible and potentially masking security issues.

12. **Session Fixation:** No `session_regenerate_id()` after login — vulnerable to session fixation attacks.

---

## 20. UNUSED / TEST FILES

### test.php
- **Purpose:** Test/development version of `review.php`
- **Content:** 983 lines — appears to be a full copy of the review/merchant detail page
- **Status:** Duplicate/unused — should be removed in production

### he.php
- **Purpose:** Test/development version of `page.php`
- **Content:** 242 lines — generic CMS page with same structure as `page.php`
- **Status:** Duplicate/unused — should be removed in production

### demo.php
- **Purpose:** Test/development version of `coupon-details.php`
- **Content:** 311 lines — shows coupon details without subscription functionality
- **Status:** Simplified coupon view, likely for demo purposes — should be removed in production

---

## ACCESS CONTROL SUMMARY

### PUBLIC Pages (No Authentication Required)
| File | Notes |
|------|-------|
| `index.php` | Homepage |
| `about.php` | About page |
| `contact.php` / `contact-email.php` | Contact form |
| `page.php` | Generic CMS page |
| `blog.php` / `blog-details.php` | Blog |
| `coupon-categories.php` | Category listing |
| `select-location.php` / `select-location-submit.php` | Location picker |
| `business-sign-up.php` / `businesssignup.php` | Business registration |
| `coupon-details.php` | View coupon (subscribe needs login) |
| `flash-discount-details.php` | View flash discount |
| `review.php` | View merchant (review needs login) |
| `complaint-submit.php` | Submit complaint (auto-creates account) |

### PUBLIC + LOCATION REQUIRED
| File | Notes |
|------|-------|
| `coupons.php` | Redirects to select-location if no location |
| `flash-discounts.php` | Same redirect behavior |
| `merchants.php` | Same redirect behavior |

### PUBLIC AJAX Endpoints
| File | Notes |
|------|-------|
| `login.php` | POST login handler |
| `register.php` | POST registration handler |
| `mobilecheck.php` | POST mobile check |
| `otpverification.php` | POST OTP verify |
| `getnewpassword.php` | POST password reset |

### AUTHENTICATED (Login Required)
| File | Additional Requirement |
|------|----------------------|
| `logout.php` | None |
| `loyalty-cards.php` | None (this IS the enrollment page) |
| `selectloyaltycard.php` | None (AJAX) |
| `set-new-password.php` / `set-password.php` | None |
| `loadarea.php` | None (AJAX) |
| `archive-complaints.php` | None (AJAX) |

### AUTHENTICATED + LOYALTY CARD REQUIRED
| File | Notes |
|------|-------|
| `my-account.php` | Dashboard |
| `profile.php` / `edit-profile.php` / `userupdate.php` | Profile management |
| `change-password.php` / `password.php` | Password change |
| `offer-zone.php` | My subscribed coupons |
| `gifted-offer-zone.php` | Gifted coupons |
| `store-offer.php` | Store coupons |
| `wishlist.php` | Favorites |
| `important-days.php` / `important-days-submit.php` | Important days |
| `my-complaints-and-suggestions.php` | My complaints |
| `subscribe.php` | AJAX coupon subscription |
| `add-to-favorites.php` | AJAX add favorite |
| `remove-from-favorites.php` | AJAX remove favorite |

---

## DUPLICATED CODE PATTERNS

The following JavaScript code blocks are duplicated across **nearly every page** with only minor variations:

1. **Login flow JS** (mobilecheck → OTP → password → login.php): Duplicated in ~15 files
2. **Registration form JS** (register.php call): Duplicated in ~15 files  
3. **Password reset JS** (getnewpassword.php call): Duplicated in ~15 files

Pages containing full duplicate login/register/reset JS:
- `index.php`, `coupon-details.php`, `coupons.php`, `flash-discounts.php`, `merchants.php`, `review.php`, `my-complaints-and-suggestions.php`, `business-sign-up.php`, `offer-zone.php`, `gifted-offer-zone.php`, `store-offer.php`, `important-days.php`, `wishlist.php`

**Recommendation:** Extract into a single shared JS file.

---

*Report generated from exhaustive analysis of all PHP files in `OLD-Customer-application-PHP-code/`*
