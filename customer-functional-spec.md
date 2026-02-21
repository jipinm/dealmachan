# Customer Application — Functional Specification

> **Source:** Legacy PHP codebase (`OLD-Customer-application-PHP-code`)  
> **Purpose:** Complete feature documentation for verifying the new customer application implementation  
> **Critical Note:** This application has both **PUBLIC** (no login required) and **AUTHENTICATED** (login required) pages

---

## Table of Contents

1. [Access Control Summary](#1-access-control-summary)
2. [Public Pages (No Login Required)](#2-public-pages-no-login-required)
   - 2.1 [Homepage](#21-homepage)
   - 2.2 [Coupon Categories](#22-coupon-categories)
   - 2.3 [Coupon Listing](#23-coupon-listing)
   - 2.4 [Coupon Details](#24-coupon-details)
   - 2.5 [Flash Discount Listing](#25-flash-discount-listing)
   - 2.6 [Flash Discount Details](#26-flash-discount-details)
   - 2.7 [Merchant Directory](#27-merchant-directory)
   - 2.8 [Merchant Unit Detail / Review Page](#28-merchant-unit-detail--review-page)
   - 2.9 [Blog Listing](#29-blog-listing)
   - 2.10 [Blog Details](#210-blog-details)
   - 2.11 [About Page](#211-about-page)
   - 2.12 [Contact Page](#212-contact-page)
   - 2.13 [Generic CMS Page](#213-generic-cms-page)
   - 2.14 [Location Selector](#214-location-selector)
   - 2.15 [Business Sign-Up (Merchant Registration)](#215-business-sign-up-merchant-registration)
3. [Authentication System](#3-authentication-system)
   - 3.1 [Login Flow (3-Step)](#31-login-flow-3-step)
   - 3.2 [Registration](#32-registration)
   - 3.3 [Password Reset](#33-password-reset)
   - 3.4 [Logout](#34-logout)
   - 3.5 [Mandatory Password Reset Check](#35-mandatory-password-reset-check)
4. [Authenticated Pages (Login Required)](#4-authenticated-pages-login-required)
   - 4.1 [My Account (Dashboard)](#41-my-account-dashboard)
   - 4.2 [Loyalty Card Selection](#42-loyalty-card-selection)
   - 4.3 [Profile View](#43-profile-view)
   - 4.4 [Edit Profile](#44-edit-profile)
   - 4.5 [Change Password](#45-change-password)
   - 4.6 [Set New Password (Mandatory Reset)](#46-set-new-password-mandatory-reset)
   - 4.7 [Offer Zone (My Subscribed Coupons)](#47-offer-zone-my-subscribed-coupons)
   - 4.8 [Gifted Offer Zone](#48-gifted-offer-zone)
   - 4.9 [Store Coupons](#49-store-coupons)
   - 4.10 [Coupon Subscription](#410-coupon-subscription)
   - 4.11 [Wishlist (Favorite Merchants)](#411-wishlist-favorite-merchants)
   - 4.12 [Important Days](#412-important-days)
   - 4.13 [Complaints & Suggestions](#413-complaints--suggestions)
5. [Global UI Components](#5-global-ui-components)
   - 5.1 [Header / Navigation](#51-header--navigation)
   - 5.2 [Login/Register/Reset Popup Modal](#52-loginregisterreset-popup-modal)
   - 5.3 [Footer](#53-footer)
6. [Database Tables Reference](#6-database-tables-reference)
7. [Key Business Rules](#7-key-business-rules)

---

## 1. Access Control Summary

### PUBLIC Pages (No Login Required)
| Page | Source File | Requires Location? |
|------|-----------|:---:|
| Homepage | `index.php` | No |
| Coupon Categories | `coupon-categories.php` | No |
| Coupon Listing | `coupons.php` | Yes (by category) / No (by merchant-unit) |
| Coupon Details | `coupon-details.php` | No |
| Flash Discount Listing | `flash-discounts.php` | Yes (by category) / No (by merchant-unit) |
| Flash Discount Details | `flash-discount-details.php` | No |
| Merchant Directory | `merchants.php` | Yes |
| Merchant Unit Detail / Review | `review.php` | No |
| Blog Listing | `blog.php` | No |
| Blog Detail | `blog-details.php` | No |
| About Page | `about.php` | No |
| Contact Page | `contact.php` | No |
| Generic CMS Page | `page.php` | No |
| Location Selector | `select-location.php` | N/A |
| Business Sign-Up | `business-sign-up.php` | No |

### AUTHENTICATED Pages (Login Required)
| Page | Source File | Requires Loyalty Card? |
|------|-----------|:---:|
| My Account (Dashboard) | `my-account.php` | Yes (redirects if none) |
| Loyalty Card Selection | `loyalty-cards.php` | N/A (this is where you select one) |
| Profile View | `profile.php` | Yes |
| Edit Profile | `edit-profile.php` | No |
| Change Password | `change-password.php` | No |
| Set New Password | `set-new-password.php` | No |
| Offer Zone (My Coupons) | `offer-zone.php` | Yes |
| Gifted Offer Zone | `gifted-offer-zone.php` | Yes |
| Store Coupons | `store-offer.php` | Yes |
| Wishlist | `wishlist.php` | No |
| Important Days | `important-days.php` | No |
| Complaints & Suggestions | `my-complaints-and-suggestions.php` | No |

### Hybrid Behavior (Public with enhanced features when logged in)
- **Coupon listing / Flash discount listing / Merchant directory**: Show favorite heart icon only when logged in
- **Coupon details**: "Get this offer now" button triggers subscribe if logged in, shows login/register form if not
- **Review page**: Rate & review form works with or without login (auto-creates account if needed)
- **Complaint form on review page**: Auto-creates customer account if mobile doesn't exist

---

## 2. Public Pages (No Login Required)

### 2.1 Homepage

| Field | Details |
|-------|---------|
| **Feature Name** | Homepage |
| **Description** | Main landing page with hero sliders, coupon categories, latest blogs, and partners |
| **Who Can Use** | Everyone (public) |
| **Source File** | `index.php` |

**Sections Displayed:**

#### A. Hero Slider
- Data source: `z_sliders` table
- Displays: Full-width banner images with optional link URLs
- Carousel behavior: Auto-rotate, clickable

#### B. Coupon Categories Grid
- Data source: `coupons_categories` table, filtered by `status = 1`
- Displays: Category icon/image + category name in a grid
- Click behavior: Opens a **split view** popup with 3 tabs:
  - **Coupons** → links to `coupons.php?category={url_slug}`
  - **Flash Discounts** → links to `flash-discounts.php?category={url_slug}`
  - **Merchants** → links to `merchants.php?category={url_slug}`
- This split view is the primary navigation for browse functionality

#### C. Latest Blogs Carousel
- Data source: `z_blogs` table, ordered by date, limited count
- Displays: Blog thumbnail, title, date
- Links to: `blog-details.php?blog={url_slug}`

#### D. Partners Carousel
- Data source: `z_partners` table
- Displays: Partner logos in an Owl Carousel slider

**Database Impact:**
- Read: `z_sliders`, `coupons_categories`, `z_blogs`, `z_partners`, `z_common_data`

### 2.2 Coupon Categories

| Field | Details |
|-------|---------|
| **Feature Name** | All Coupon Categories |
| **Description** | Full grid view of all available coupon categories |
| **Who Can Use** | Everyone (public) |
| **Source File** | `coupon-categories.php` |

**Processing Logic:**
1. Query all active categories from `coupons_categories` WHERE `status = 1`
2. Display as a responsive grid with category image + name

**Click Behavior:** Same split-view popup as homepage (Coupons / Flash Discounts / Merchants)

### 2.3 Coupon Listing

| Field | Details |
|-------|---------|
| **Feature Name** | Browse Coupons |
| **Description** | List all available platform coupons, filterable by category, sub-category, area, and keywords |
| **Who Can Use** | Everyone (public). Favorite hearts shown only when logged in. |
| **Source File** | `coupons.php` |

**Two Access Modes:**

#### Mode A: By Category (requires location)
- URL: `coupons.php?category={category_url_slug}`
- If no location in session → redirect to `select-location.php?target={encoded_return_url}`
- Retrieves coupons filtered by: category, location, optional sub-category, optional area, optional keyword search
- Shows "Refine Search" link that reveals filter panel

#### Mode B: By Merchant Unit (no location required)
- URL: `coupons.php?merchant-unit={unit_url_slug}`
- Shows all coupons for the specified merchant unit
- No location redirect needed

**Filter Panel (Mode A only):**
- Sub-category checkboxes (loaded from `cpn_coupons_sub_categories` for selected category)
- Area checkboxes (loaded from areas for selected location)
- Keywords text input
- Filter button → reloads page with query parameters

**Coupon Card Display:**
- Coupon image (from `repository/coupons/`)
- Coupon title
- Quick-view link → opens modal with coupon code, title, terms
- Favorite heart icon (only if logged in, red if already favorited)
- "View details" link → `coupon-details.php`

**Processing Logic for favorites:**
1. Check if `$_SESSION['customer_id']` is set
2. If yes: query `tbl_unit_favorite` for each coupon's unit to check if favorited
3. Display heart in red if favorited, gray if not

**Database Impact:**
- Read: `cpn_coupons`, `coupons_categories`, `cpn_coupons_sub_categories`, areas, `tbl_unit_favorite` (if logged in)

### 2.4 Coupon Details

| Field | Details |
|-------|---------|
| **Feature Name** | Coupon Detail Page |
| **Description** | Full detail view of a single coupon with subscribe functionality |
| **Who Can Use** | Everyone can view. Only logged-in customers can subscribe. |
| **Source File** | `coupon-details.php` |

**URL Parameters:**
- `coupon` — Coupon code
- `id` — Coupon ID

**Display Elements:**
- Coupon image (full width)
- Coupon code
- Coupon title
- Coupon description
- Terms & conditions
- Subscribe deadline date
- Redeem deadline date
- "Get this offer now" button (triggers subscription)
- Favorite heart icon (only if logged in)
- Back button

**Behavior based on auth state:**

| State | Behavior |
|-------|----------|
| **Logged in** | "Get this offer now" button calls `subscribe.php` via AJAX |
| **Not logged in** | Shows inline Login form + Register form + Reset Password form directly on the page |

**The inline forms on coupon-details** are the same Login/Register/Reset forms as the global popup, but embedded directly in the page. On successful login from this page, the response is `'coupon_details'` which triggers a page reload (so user can then subscribe).

**Database Impact:**
- Read: `cpn_coupons` (single coupon by code+id), `tbl_unit_favorite` (if logged in)
- On subscribe: see Section 4.10

### 2.5 Flash Discount Listing

| Field | Details |
|-------|---------|
| **Feature Name** | Browse Flash Discounts |
| **Description** | List flash discounts, filterable like coupons |
| **Who Can Use** | Everyone (public). Favorites shown only when logged in. |
| **Source File** | `flash-discounts.php` |

**Two Access Modes:** Same as coupons (by category with location, or by merchant-unit without)

**Key Difference from Coupons:**
- If customer is logged in AND has a loyalty card: flash discounts are filtered by loyalty card classification (`classification_id`), meaning customers see only discounts matching their loyalty tier
- If not logged in: all flash discounts shown (no classification filter)

**Filter Panel:** Same as coupons — sub-category, area, keywords

**Flash Discount Card Display:**
- Discount image (from `repository/discount/`)
- Discount description
- Favorite heart icon (if logged in)
- "View details" link

**Database Impact:**
- Read: `flash_discount`, `coupons_categories`, areas, `tbl_unit_favorite` (if logged in), `z_loyalty_cards` (if logged in)

### 2.6 Flash Discount Details

| Field | Details |
|-------|---------|
| **Feature Name** | Flash Discount Detail Page |
| **Description** | View a single flash discount with merchant info |
| **Who Can Use** | Everyone (public) |
| **Source File** | `flash-discount-details.php` |

**URL Parameters:**
- `id` — Flash discount ID

**Display Elements:**
- Flash discount image
- Discount description
- Merchant unit name
- Merchant unit address
- Merchant company name
- Favorite heart icon (if logged in)
- Back button

**Note:** Flash discounts do NOT have a "Get this offer" / subscribe button. They are redeemed by the merchant at the point of sale.

### 2.7 Merchant Directory

| Field | Details |
|-------|---------|
| **Feature Name** | Merchant / Store Listing |
| **Description** | Browse merchant units by category, with area and keyword filters |
| **Who Can Use** | Everyone (public). Favorites shown only when logged in. |
| **Source File** | `merchants.php` |

**Requires Location:** Yes. Redirects to `select-location.php` if no location in session.

**URL Parameters:**
- `category` — Category URL slug (required)
- `area` — Area ID (optional filter)
- `search` — Keyword search (optional)

**Filter Panel:**
- Area checkboxes
- Keywords text input
- Filter button

**Merchant Card Display:**
- Cover photo (from `repository/profile/`)
- Unit name (link to `review.php?merchant-unit={url_slug}`)
- Unit address
- Favorite heart icon (if logged in)
- "View details" link

### 2.8 Merchant Unit Detail / Review Page

| Field | Details |
|-------|---------|
| **Feature Name** | Merchant Unit Detail with Reviews, Gallery & Complaints |
| **Description** | Comprehensive merchant unit page with ratings, reviews, photo galleries, contact info, and complaint form |
| **Who Can Use** | Everyone (public) can view. Reviews/complaints can be submitted without login (auto-creates account). |
| **Source File** | `review.php`, `review-submit.php`, `complaint-submit.php` |

**URL Parameters:**
- `merchant-unit` — Unit URL slug

**Display Sections:**

#### A. Unit Header
- Cover photo
- Company logo
- Unit name
- Unit address
- Working hours (from `tbl_mc_unt_working_hours`)
- Unit description
- Average star rating + total review count

#### B. Tabs
1. **Merchant Gallery** — Images from `tbl_merchant_company_unit_images`
2. **Customer Gallery** — Images from `tbl_review_images` (uploaded by customers in reviews)

#### C. Rate & Review Form
- **3 Rating Criteria** (star ratings 1-5 for each):
  - `rating_1` — First criterion (e.g., Quality)
  - `rating_2` — Second criterion (e.g., Service)
  - `rating_3` — Third criterion (e.g., Ambience)
- `txt_review` — Written review (textarea)
- Image uploads — Multiple images (file input, stored in `repository/review/`)
- `txt_review_name` — Reviewer name (text)
- `txt_review_mobile` — Reviewer mobile (text)
- Submit button

**Review Submit Processing Logic (`review-submit.php`):**
1. Validate required fields (name, mobile, at least one rating)
2. Check if review already exists for this mobile + unit: `SELECT FROM tbl_reviews WHERE mobile AND unit_id`
3. If exists: UPDATE the existing review record
4. If new: INSERT new review record
5. Upload review images (if any) to `repository/review/`, INSERT into `tbl_review_images`
6. Response: `'success'` or `'error_type'`

#### D. Complaint & Suggestion Form
- `txt_subject` — Subject (text, required)
- `txt_complaint` — Message body (textarea, required)
- `txt_complaint_name` — Name (text, required)
- `txt_complaint_mobile` — Mobile (text, required)
- Submit button

**Complaint Submit Processing Logic (`complaint-submit.php`):**
1. Validate required fields
2. Check if mobile exists in `tbl_customers`:
   - If YES: use existing `customer_id`
   - If NO: **Auto-create customer account** — generate random password, hash with MD5, INSERT into `tbl_customers`, send password via SMS
3. INSERT complaint into `tbl_merchant_unit_complaints` — columns: `customer_id`, `unit_id`, `merchant_company_id`, `complaint_subject`, `complaint_message`, `complaint_date`, `complaint_status`

#### E. Contact Info & Map
- Contact phone + email
- Google Maps embed using latitude/longitude
- Links to: Coupons for this unit, Flash Discounts for this unit

**Database Impact:**
- Read: `tbl_merchant_company_units`, `tbl_merchant_company_unit_images`, `tbl_mc_unt_working_hours`, `tbl_reviews`, `tbl_review_images`
- Write (reviews): INSERT/UPDATE `tbl_reviews`, INSERT `tbl_review_images`
- Write (complaints): INSERT `tbl_merchant_unit_complaints`, possible INSERT `tbl_customers`

### 2.9 Blog Listing

| Field | Details |
|-------|---------|
| **Feature Name** | Blog Listing |
| **Source File** | `blog.php` |

**Processing Logic:**
1. Query all blogs from `z_blogs` ordered by date descending
2. Display as card grid: thumbnail image, heading, date, excerpt

**Card Display:** Image, heading, date, "Read more" link → `blog-details.php?blog={url_slug}`

### 2.10 Blog Details

| Field | Details |
|-------|---------|
| **Feature Name** | Blog Detail Page |
| **Source File** | `blog-details.php` |

**URL Parameters:**
- `blog` — Blog URL slug

**Display Elements:** Full blog image, heading, date, full content (HTML from DB)

**Database Impact:** Read `z_blogs` WHERE `blog_heading_url` = slug

### 2.11 About Page

| Field | Details |
|-------|---------|
| **Feature Name** | About Us |
| **Source File** | `about.php` |

**Processing Logic:**
1. Query `z_page_contents` for about page content
2. Render HTML content from database

### 2.12 Contact Page

| Field | Details |
|-------|---------|
| **Feature Name** | Contact Us Form |
| **Source File** | `contact.php`, `contact-email.php` |

**Inputs:**
- `txt_name` — Name (text, required)
- `txt_mobile` — Mobile number (text, required)
- `txt_subject` — Subject (text, required)
- `txt_message` — Message (textarea, required)

**Processing Logic (`contact-email.php`):**
1. Validate all fields
2. Send email using PHP `mail()` function to site admin email (from `z_common_data`)
3. Return success/error response

**Note:** This sends an email, does NOT store in database.

### 2.13 Generic CMS Page

| Field | Details |
|-------|---------|
| **Feature Name** | Generic Content Page |
| **Source File** | `page.php` |

**URL Parameters:**
- `page` — Page identifier

**Processing Logic:** Query `z_page_contents` by page identifier, render HTML content

### 2.14 Location Selector

| Field | Details |
|-------|---------|
| **Feature Name** | Select Location |
| **Description** | Set the customer's browsing location (required for category-based coupon/merchant browsing) |
| **Who Can Use** | Everyone (public) |
| **Source File** | `select-location.php`, `select-location-submit.php` |

**Inputs:**
- `location_id` — Selected location (dropdown populated from `z_locations` table)

**Processing Logic:**
1. Display available locations in a dropdown
2. On submit (AJAX POST to `select-location-submit.php`):
   - Set `$_SESSION['location_id']` = selected location ID
   - Set `$_SESSION['location_name']` = selected location name
3. If `target` GET parameter exists (base64 encoded return URL): redirect back to original page
4. Otherwise: redirect to homepage

**Session Variables Set:**
- `$_SESSION['location_id']` — Current browsing location ID
- `$_SESSION['location_name']` — Current browsing location name

**Database Impact:** Read `z_locations`

**Conditions/Edge Cases:**
- Pages that require location (coupons by category, flash discounts by category, merchants) redirect here with `target` parameter encoding the return URL
- Location persists in session until changed or session expires

### 2.15 Business Sign-Up (Merchant Registration)

| Field | Details |
|-------|---------|
| **Feature Name** | Business/Merchant Registration Form |
| **Description** | Allows businesses to request to join the platform |
| **Who Can Use** | Everyone (public) |
| **Source File** | `business-sign-up.php`, `businesssignup.php` |

**Inputs:**
- `txt_name` — Contact person name (text, required)
- `txt_orgname` — Organization name (text, required)
- `ddl_category` — Business category (dropdown)
- `txt_email` — Email address (text, required)
- `txt_phone` — Phone number (text, required)
- `txt_message` — Additional message (textarea)

**Processing Logic (`businesssignup.php`):**
1. Validate required fields
2. INSERT into `z_business_signup` table
3. Return success response

**Database Impact:**
- INSERT into `z_business_signup` — columns: `contact_name`, `org_name`, `category`, `email`, `phone`, `message`, `signup_date`

---

## 3. Authentication System

### 3.1 Login Flow (3-Step)

| Field | Details |
|-------|---------|
| **Feature Name** | Customer Login |
| **Description** | 3-step login: mobile check → OTP verification (if needed) → password authentication |
| **Who Can Use** | Registered customers |
| **Source Files** | `mobilecheck.php`, `otpverification.php`, `login.php` |

**The login form appears in two places:**
1. Global popup modal (accessible from header on every page)
2. Inline on `coupon-details.php` page

#### Step 1: Mobile/Card Number Check (`mobilecheck.php`)

**Input:**
- `signin_loginid` — Mobile number or loyalty card number (text)

**Processing Logic:**
1. If empty: return `'mandatory'`
2. Query `tbl_customers` WHERE `customer_mobile` = input OR loyalty card number = input
3. If no match: return `'nomobile'`
4. If found AND `sms_otp_status = 0` (not verified):
   - Generate 4-digit random OTP: `rand(1000, 9999)`
   - UPDATE `tbl_customers` SET `sms_otp = {otp}`
   - Send OTP via SMS (alertin.co.in API)
   - Return `'unverified'` (show OTP field)
5. If found AND `sms_otp_status = 1` (already verified):
   - Return `'verified'` (show password field)

**UI State Transitions:**
| Response | Action |
|----------|--------|
| `'mandatory'` | Show error: "Enter the mobile or loyalty card number" |
| `'nomobile'` | Show error: "No records exists in database..." |
| `'unverified'` | Hide mobile field, show OTP input field |
| `'verified'` | Hide mobile field, show password input field |

#### Step 2: OTP Verification (`otpverification.php`)

**Inputs:**
- `signin_loginid` — Mobile/card number (carried from step 1)
- `signin_otp` — OTP entered by user (text)

**Processing Logic:**
1. If OTP empty: return `'mandatory'`
2. Query `tbl_customers` WHERE `customer_mobile` = loginid AND `sms_otp` = entered OTP
3. If no match: return `'unverified'` (invalid OTP)
4. If match:
   - UPDATE `tbl_customers` SET `sms_otp_status = 1` (mark verified)
   - Return `'verified'` (show password field)

#### Step 3: Password Authentication (`login.php`)

**Inputs:**
- `signin_loginid` — Mobile/card number
- `signin_pass` — Password

**Processing Logic:**
1. If password empty: return `'mandatory'`
2. Hash password: `md5($password)`
3. Query `tbl_customers` WHERE `customer_mobile` = loginid AND `customer_password` = MD5 hash
4. If no match: return `'invalid'`
5. If match:
   - Set session variables (see below)
   - If form submitted from coupon details page: return `'coupon_details'` (page reload)
   - Otherwise: return `'success'` (redirect to `my-account.php`)

**Session Variables Set on Login:**
- `$_SESSION['customer_id']` — Customer ID
- `$_SESSION['customer_uuid']` — Customer UUID
- `$_SESSION['customer_name']` — Display name (first_name)
- `$_SESSION['customer_mobile']` — Mobile number
- `$_SESSION['loyalty_card_id']` — Loyalty card type ID
- `$_SESSION['loyalty_card_no']` — Loyalty card number (may be empty if none selected)

**SMS OTP Details:**
- Provider: `alertin.co.in`
- API URL: `https://alertin.co.in/api/push.json`
- Method: CURL GET with query string parameters
- Parameters: `apikey`, `sender` (6 chars), `mobileno`, `text` (DLT template with OTP), `type=1`
- OTP format: 4-digit random number

### 3.2 Registration

| Field | Details |
|-------|---------|
| **Feature Name** | Customer Registration |
| **Source File** | `register.php` |

**Inputs:**
- `signup_name` — Full name (text, required)
- `signup_mobile` — Mobile number (10 digits, required, unique)
- `signup_password` — Password (6-12 characters, required)
- `signup_photo` — Profile photo (file upload, optional)

**Processing Logic:**
1. Validate name not empty: return `'signup_name'` if empty
2. Validate mobile is 10 digits: return `'signup_mobile'` if invalid
3. Check mobile uniqueness in `tbl_customers`: return `'signup_mobile_exist'` if exists
4. Validate password length 6-12: return `'signup_password'` if invalid
5. If photo uploaded: save to `repository/customers/` with unique filename
6. Hash password: `md5($password)`
7. INSERT into `tbl_customers`
8. Set session variables (auto-login after registration)
9. If submitted from coupon details page (`submit_from == 'coupon_details'`): return `'coupon_details'`
10. Otherwise: return `'success'` → redirect to `my-account.php`

**Database Impact:**
- INSERT into `tbl_customers` — columns: `customer_uuid`, `first_name`, `customer_mobile`, `customer_password`, `customer_photo`, `sms_otp_status` (=0, needs verification), `customer_cdate`

### 3.3 Password Reset

| Field | Details |
|-------|---------|
| **Feature Name** | Forgot Password / Get New Password |
| **Source File** | `getnewpassword.php` |

**Inputs:**
- `signin_loginid` — Mobile number or loyalty card number

**Processing Logic:**
1. If empty: return `'mandatory'`
2. Query `tbl_customers` by mobile or card number
3. If no match: return `'nomobile'`
4. If found:
   - Generate random 6-character password
   - Hash with MD5
   - UPDATE `tbl_customers` SET `customer_password` = hashed password, `temp_password = 1`
   - Send new password via SMS
   - Return `'success'`

**Database Impact:**
- UPDATE `tbl_customers` — columns: `customer_password`, `temp_password` (=1)

**User sees:** "A new password is generated & sent to your mobile number."

### 3.4 Logout

| Field | Details |
|-------|---------|
| **Source File** | `logout.php` |

**Processing Logic:**
1. `session_destroy()`
2. Redirect to homepage (`BASE_URL`)

### 3.5 Mandatory Password Reset Check

| Field | Details |
|-------|---------|
| **Feature Name** | Forced Password Change |
| **Description** | If customer has `temp_password = 1`, they are forced to set a new password |
| **Source File** | `check-mandatory-password-reset.php` |

**Processing Logic:**
1. This file is `include()`-ed at the top of authenticated pages
2. Query `tbl_customers` WHERE `customer_id` = session customer ID
3. If `temp_password == 1`: redirect to `set-new-password.php`

**Included on:** `offer-zone.php` (and potentially other key authenticated pages)

---

## 4. Authenticated Pages (Login Required)

All pages in this section check `$_SESSION['customer_id']` at the top. If not set, they redirect to the homepage (`BASE_URL`).

### 4.1 My Account (Dashboard)

| Field | Details |
|-------|---------|
| **Feature Name** | Customer Dashboard / My Account |
| **Description** | Main authenticated landing page showing loyalty card and category navigation |
| **Who Can Use** | Authenticated customers with a loyalty card |
| **Source File** | `my-account.php` |

**Auth Guard:**
1. Check `$_SESSION['customer_id']` → redirect to homepage if not set
2. Check `$_SESSION['loyalty_card_no']` → redirect to `loyalty-cards.php` if empty
3. Include `check-mandatory-password-reset.php`

**Display Elements:**

#### A. Loyalty Card Display
- Front side: card design image, customer name, card number (hidden by default)
- Back side: card design back image, partner logos
- **Tap-to-reveal:** Card number is hidden with asterisks (`****`), tap/click reveals actual number
- Card number format: sequential number from `z_loyalty_card_number` table

#### B. Partner Logos
- Data from: loyalty card's associated partners
- Display: Logo grid below the card

#### C. Category Grid
- Same coupon categories grid as homepage
- Click behavior: Same split-view popup (Coupons / Flash Discounts / Merchants)

### 4.2 Loyalty Card Selection

| Field | Details |
|-------|---------|
| **Feature Name** | Select Loyalty Card |
| **Description** | Choose a loyalty card type from available options |
| **Who Can Use** | Authenticated customers without a loyalty card assigned |
| **Source File** | `loyalty-cards.php`, `selectloyaltycard.php` |

**Processing Logic:**
1. Query available loyalty card types from `z_loyalty_cards` WHERE `status = 1`
2. Display each card with: card image, title/name, subscription limits info

**Card Display per Type:**
- Card front image
- Card title/name
- `allowed_subs_per_month` — Monthly subscription limit
- `allowed_max_subs_live` — Maximum concurrent active subscriptions
- Select button

**Select Card Processing (`selectloyaltycard.php`):**
1. Receive selected `loyalty_card_id`
2. Get next available card number from `z_loyalty_card_number` table:
   - Query for an unassigned number for this card type
   - UPDATE that record to assign to this customer
3. UPDATE `tbl_customers` SET `loyalty_card_id`, `loyalty_card_no`
4. Set session: `$_SESSION['loyalty_card_id']`, `$_SESSION['loyalty_card_no']`
5. Redirect to `my-account.php`

**Database Impact:**
- Read: `z_loyalty_cards`
- UPDATE: `z_loyalty_card_number` (assign number to customer)
- UPDATE: `tbl_customers` — columns: `loyalty_card_id`, `loyalty_card_no`

**Conditions/Edge Cases:**
- Card numbers are pre-generated and sequential in `z_loyalty_card_number`
- Each card number can only be assigned once
- Once assigned, the card type determines subscription limits

### 4.3 Profile View

| Field | Details |
|-------|---------|
| **Feature Name** | View Profile |
| **Who Can Use** | Authenticated customers with loyalty card |
| **Source File** | `profile.php` |

**Auth Guard:** Requires login + loyalty card

**Display Fields:**
- Profile photo
- First name
- Last name
- Email
- Mobile number
- Date of birth
- Gender
- Job category / Profession
- Occupation
- Residing city
- Full address
- PIN code
- Location

**Links:** Edit Profile, Change Password

### 4.4 Edit Profile

| Field | Details |
|-------|---------|
| **Feature Name** | Edit Profile |
| **Source File** | `edit-profile.php`, `userupdate.php`, `loadarea.php` |

**Inputs:**
- `profile_photo` — Profile image (file upload, optional)
- `first_name` — First name (text)
- `last_name` — Last name (text)
- `customer_email` — Email (text)
- `customer_mobile` — Mobile (read-only, displayed but not editable)
- `dob` — Date of birth (date picker)
- `gender` — Gender (radio: Male/Female)
- `job_category` — Job category/Profession (dropdown from `z_professions` table)
- `occupation` — Occupation (text)
- `residing_city` — City (text)
- `full_address` — Address (textarea)
- `pincode` — PIN code (text)
- `location_id` — Location (dropdown from `z_locations`)
- `area_id` — Area (dropdown, dynamically loaded based on location via AJAX to `loadarea.php`)

**Dynamic Area Loading (`loadarea.php`):**
- Triggered on location dropdown change
- AJAX POST with `location_id`
- Returns HTML `<option>` elements for areas matching that location
- Source: areas table filtered by `location_id`

**Update Processing (`userupdate.php`):**
1. If photo uploaded: save to `repository/customers/`, delete old photo
2. UPDATE `tbl_customers` with all fields
3. Update session variable `$_SESSION['customer_name']` if name changed
4. Return success/error

**Database Impact:**
- UPDATE `tbl_customers` — all profile columns

### 4.5 Change Password

| Field | Details |
|-------|---------|
| **Feature Name** | Change Password |
| **Source File** | `change-password.php`, `password.php` |

**Inputs:**
- `old_password` — Current password (text, required)
- `new_password` — New password (text, 6-12 chars, required)
- `confirm_password` — Confirm new password (text, must match)

**Processing Logic (`password.php`):**
1. Validate all fields present
2. Verify old password: `md5($old_password)` matches stored hash
3. Verify new and confirm match
4. Verify new password length 6-12
5. UPDATE `tbl_customers` SET `customer_password = md5($new_password)`
6. Return `'success'` or error code

### 4.6 Set New Password (Mandatory Reset)

| Field | Details |
|-------|---------|
| **Feature Name** | Set New Password (forced change) |
| **Description** | Required when customer has `temp_password = 1` (e.g., after merchant-created account or password reset) |
| **Source File** | `set-new-password.php`, `set-password.php` |

**Inputs:**
- `new_password` — New password (text, 6-12 chars)
- `confirm_password` — Confirm (must match)

**Note:** No old password required (since it was auto-generated)

**Processing Logic (`set-password.php`):**
1. Validate new password length and match
2. UPDATE `tbl_customers` SET `customer_password = md5($new_password)`, `temp_password = 0`
3. Redirect to `my-account.php`

**Database Impact:**
- UPDATE `tbl_customers` — columns: `customer_password`, `temp_password` (→ 0)

### 4.7 Offer Zone (My Subscribed Coupons)

| Field | Details |
|-------|---------|
| **Feature Name** | My Coupons / Offer Zone |
| **Description** | View all subscribed platform coupons with filtering by status |
| **Who Can Use** | Authenticated customers with loyalty card |
| **Source File** | `offer-zone.php` |

**Auth Guard:** Login + loyalty card + mandatory password reset check

**Auto-Expiry Logic (runs on page load):**
```sql
UPDATE cpn_coupon_subscribe SET subscribe_status='-1'
WHERE subscribe_status != '-1' AND redeem_last_date < NOW()
AND customer_id = {customer_id}
```
This automatically expires any coupons past their redemption deadline.

**Sort/Filter Dropdown:**
| Option | Value | Description |
|--------|-------|-------------|
| All Coupons | `sortBy=All` | Show all subscribed coupons |
| Active Coupons | `sortBy=Active` | Only `subscribe_status = 0` |
| Redeemed Coupons | `sortBy=Redeemed` | Only `subscribe_status = 1` |
| Expired Coupons | `sortBy=Expired` | Only `subscribe_status = -1` |
| Gifted Coupons | → `gifted-offer-zone.php` | Navigate to gifted coupons page |
| Store Coupons | → `store-offer.php` | Navigate to store coupons page |

**Gifted Coupons Banner:**
- If customer has any gifted/assigned coupons (from `getMyGiftedCoupons("buffer",...)`): display a carousel banner at the top showing them

**Coupon Card Display:**
- Coupon image
- Coupon title (links to `coupon-details.php`)
- Redeem units list (with checkmark ✅ on the unit where it was redeemed)
- Redeem last date
- Redeemed date (if redeemed)
- Status badge: Active (green), Redeemed (yellow/warning), Expired (red/danger)
- "View details" link

**Status Badges:**
| `subscribe_status` | Badge |
|----|------|
| `0` | ✅ Active (green) |
| `1` | ⚠️ Redeemed (yellow) |
| `-1` | ❌ Expired (red) |

### 4.8 Gifted Offer Zone

| Field | Details |
|-------|---------|
| **Feature Name** | Gifted / Assigned Coupons |
| **Description** | View coupons that were assigned to this customer by merchants (platform coupon gifting) |
| **Who Can Use** | Authenticated customers with loyalty card |
| **Source File** | `gifted-offer-zone.php` |

**Auth Guard:** Login + loyalty card required

**Same dropdown navigation as Offer Zone** (shared between offer-zone, gifted-offer-zone, store-offer)

**Gifted Coupons Banner:** Same carousel at top if gifted coupons exist

**Coupon Card Display:**
- Coupon image
- Coupon title
- Redeem units list
- Redeem last date
- "View details" link

**Key Difference from Offer Zone:** These are coupons from `cpn_coupon_subscribe` where the coupon was assigned/gifted (from vault) rather than self-subscribed

### 4.9 Store Coupons

| Field | Details |
|-------|---------|
| **Feature Name** | My Store Coupons |
| **Description** | View store coupons assigned to this customer by merchants |
| **Who Can Use** | Authenticated customers with loyalty card |
| **Source File** | `store-offer.php` |

**Auth Guard:** Login + loyalty card required

**Processing Logic:**
1. Query `cpn_store_coupons_assignment` JOIN `cpn_store_coupons` WHERE `customer_id` = current customer AND expiry date >= today

**Card Display:**
- Coupon title
- Terms & conditions

**Same dropdown navigation** as Offer Zone and Gifted Offer Zone

### 4.10 Coupon Subscription

| Field | Details |
|-------|---------|
| **Feature Name** | Subscribe to a Coupon |
| **Description** | Customer subscribes to a platform coupon to later redeem at a merchant |
| **Who Can Use** | Authenticated customers with loyalty card |
| **Source File** | `subscribe.php` |

**Input:**
- `coupon_id` — ID of the coupon to subscribe to (POST, from "Get this offer now" button)

**Processing Logic (AJAX, returns JSON):**

1. **Auth Check:** If not logged in → return error with login/register links

2. **Loyalty Card Check:** If customer has no loyalty card → return error: "There is NO Loyalty card associated with your account. Please add a Loyalty card from this Link."

3. **Live Subscription Limit Check:**
   - Count customer's active subscriptions (`subscribe_status != '-1'`)
   - Compare against `loyalty_card.allowed_max_subs_live`
   - If exceeded → return error: "Your maximum live coupon subscriptions as per your loyalty card has been exceeded."

4. **Monthly Subscription Limit Check:**
   - Count customer's subscriptions this month
   - Compare against `loyalty_card.allowed_subs_per_month`
   - If exceeded → return error: "Your maximum monthly coupon subscriptions as per your loyalty card has been exceeded."

5. **Coupon Expiry Check:**
   - Get coupon's `expiry_to_subscribe` date
   - If today > expiry → return error: "Expired. Your chosen offer / coupon expired to subscribe."

6. **Single Redeem Type Check:**
   - If coupon `redeem_type == 'Single'`:
     - Check if customer already subscribed to this coupon
     - If yes → return error: "Already redeemed. Not available to subscribe."

7. **Availability Check:**
   - Count total subscriptions for this coupon (`subCount`)
   - Compare against `issued_to_public`
   - If `subCount >= issued_to_public` → return error: "Not Available."

8. **Success — Create Subscription:**
   - Generate UUID: `md5(uniqid(rand(), true))`
   - INSERT into `cpn_coupon_subscribe`:
     - `coupon_subscribe_uuid` = generated UUID
     - `customer_id` = session customer ID
     - `coupon_id` = posted coupon ID
     - `subscribe_date` = today
     - `subscribe_status` = 0 (active)
     - `m_business_id` = coupon's merchant ID
     - `m_business_unit_ids` = coupon's unit IDs
     - `redeem_last_date` = coupon's `expiry_to_redeem`
   - Return success: "Your chosen offer / coupon subscribed successfully."

**Response Format:** JSON `{ msg_type: 'success'|'error', msg: '...' }`

### 4.11 Wishlist (Favorite Merchants)

| Field | Details |
|-------|---------|
| **Feature Name** | My Favorites / Wishlist |
| **Description** | View and manage favorite merchant units |
| **Who Can Use** | Authenticated customers |
| **Source File** | `wishlist.php`, `add-to-favorites.php`, `remove-from-favorites.php` |

**Auth Guard:** Login required

**Display:**
- List of favorited merchant units
- Each entry shows: unit cover photo, unit name, unit address
- Remove button (trash icon)

**Add to Favorites (`add-to-favorites.php` — AJAX):**
- Called from coupon listing, merchant listing, and detail pages
- Input: `unit_ids` (unit ID), `merchant_id` (company ID)
- Processing:
  1. Check if already favorited: query `tbl_unit_favorite` WHERE `customer_id` AND `unit_id`
  2. If not exists: INSERT into `tbl_unit_favorite`
  3. If already exists: no action (idempotent)

**Remove from Favorites (`remove-from-favorites.php` — AJAX):**
- Called from wishlist page
- Input: `unit_ids` (unit ID)
- Processing: DELETE from `tbl_unit_favorite` WHERE `customer_id` AND `unit_id`

**Database Impact:**
- Read: `tbl_unit_favorite` JOIN `tbl_merchant_company_units`
- INSERT/DELETE: `tbl_unit_favorite` — columns: `customer_id`, `unit_id`, `merchant_id`

**Note:** Favorites are per merchant UNIT, not per merchant company

### 4.12 Important Days

| Field | Details |
|-------|---------|
| **Feature Name** | Important Days / Events |
| **Description** | Record personal events (birthdays, anniversaries) for potential targeted offers |
| **Who Can Use** | Authenticated customers |
| **Source File** | `important-days.php`, `important-days-submit.php` |

**Auth Guard:** Login required

**Display:**
- List of previously added important days
- Add new event form

**Add Event Form Inputs:**
- `event_type` — Type dropdown: Birthday, Anniversary, Others
- `specify` — Custom event name (text, shown only when "Others" selected)
- `event_day` — Day of month (dropdown: 1-31)
- `event_month` — Month name (dropdown: January-December)

**Submit Processing (`important-days-submit.php`):**
1. Validate required fields
2. INSERT into customer events table
3. Return success/error

**Database Impact:**
- INSERT: customer important days table — columns: `customer_id`, `event_type`, `event_specify`, `event_day`, `event_month`
- Read: existing events for this customer

### 4.13 Complaints & Suggestions

| Field | Details |
|-------|---------|
| **Feature Name** | My Complaints & Suggestions |
| **Description** | View submitted complaints and merchant replies, archive old complaints |
| **Who Can Use** | Authenticated customers |
| **Source File** | `my-complaints-and-suggestions.php`, `archive-complaints.php` |

**Auth Guard:** Login required

**Display:**
- List of customer's submitted complaints from `tbl_merchant_unit_complaints`
- Each entry shows:
  - Subject
  - Customer's message
  - Merchant's reply (if any)
  - Reply date
  - Status
  - Archive button

**Archive Processing (`archive-complaints.php`):**
- Input: `complaint_id`
- Processing: UPDATE `tbl_merchant_unit_complaints` SET `archive_status = 1` (or similar flag)
- Archived complaints no longer appear in the list

---

## 5. Global UI Components

### 5.1 Header / Navigation

| Field | Details |
|-------|---------|
| **Source File** | `header.php` |

**Elements:**
- Site logo (links to homepage)
- Main navigation menu:
  - Home
  - My Account (shown always, links to `my-account.php`)
  - Location selector display (current location name)
  - Login/Register link (if not logged in) / Customer name + Logout (if logged in)

**Navigation Menu Items (from header):**
| Menu Item | Link | Visibility |
|-----------|------|:---:|
| Home | `index.php` | Always |
| Coupons | `coupon-categories.php` | Always |
| My Account | `my-account.php` | Always |
| Offer Zone | `offer-zone.php` | Logged in |
| Wishlist | `wishlist.php` | Logged in |
| Profile | `profile.php` | Logged in |
| Important Days | `important-days.php` | Logged in |
| Complaints | `my-complaints-and-suggestions.php` | Logged in |
| Blog | `blog.php` | Always |
| About | `about.php` | Always |
| Contact | `contact.php` | Always |
| Login | Opens popup | Not logged in |
| Logout | `logout.php` | Logged in |

**Common Data:** Header loads `z_common_data` for site name, logo, contact info used across all pages.

### 5.2 Login/Register/Reset Popup Modal

| Field | Details |
|-------|---------|
| **Source File** | `popup.php` |
| **Included On** | Every page (both public and authenticated) |

**Three Tabs in Modal:**

1. **Login Tab** — 3-step flow (Section 3.1)
2. **Register Tab** — Registration form (Section 3.2)
3. **Reset Password Tab** — Password reset form (Section 3.3)

**JavaScript for all forms** is duplicated in every page's inline scripts (identical login/register/OTP/password-reset AJAX handlers on each page).

### 5.3 Footer

| Field | Details |
|-------|---------|
| **Source File** | `footer-section.php`, `footer.php` (separate files) |

**`footer-section.php` Elements:**
- Site description
- Quick links (About, Contact, Privacy Policy, Terms & Conditions)
- Contact info (from `z_common_data`)
- Social media links

**`footer.php` Elements:**
- JavaScript library includes (jQuery, Bootstrap, Owl Carousel, etc.)
- Copyright notice

---

## 6. Database Tables Reference

| Table | Purpose | Used In |
|-------|---------|---------|
| `tbl_customers` | Customer accounts, profiles, authentication | Authentication, Profile, all authenticated pages |
| `z_loyalty_cards` | Loyalty card types/tiers with subscription limits | Card selection, subscription limits |
| `z_loyalty_card_number` | Pre-generated sequential card numbers (public pool) | Card assignment |
| `z_loyalty_card_number_private` | Private card numbers (merchant-assigned) | Merchant-created customers |
| `cpn_coupons` | Platform coupons master data | Coupon listings, details, subscribe |
| `cpn_coupon_subscribe` | Customer coupon subscriptions | Subscribe, offer zone, redemption |
| `cpn_coupons_sub_categories` | Coupon sub-categories | Coupon/flash discount filters |
| `coupons_categories` | Coupon categories | Category grids, navigation |
| `cpn_store_coupons` | Merchant store coupons | Store offer page |
| `cpn_store_coupons_assignment` | Store coupon assignments to customers | Store offer page |
| `flash_discount` | Flash discount offers | Flash discount listing/details |
| `flash_discount_redeem` | Flash discount redemption records | Merchant-side redemption |
| `tbl_merchant_company` | Merchant businesses | Coupon/merchant displays |
| `tbl_merchant_company_units` | Individual merchant locations/stores | Merchants page, reviews, favorites |
| `tbl_merchant_company_unit_images` | Unit photo gallery | Review page gallery |
| `tbl_mc_unt_working_hours` | Unit working hours | Review page |
| `tbl_unit_favorite` | Customer favorite units | Wishlist, favorites hearts |
| `tbl_merchant_unit_complaints` | Complaints & suggestions | Review page, complaints page |
| `tbl_reviews` | Customer reviews/ratings per unit | Review page |
| `tbl_review_images` | Review photo uploads | Review page customer gallery |
| `z_sliders` | Homepage banner sliders | Homepage |
| `z_blogs` | Blog posts | Blog listing/details |
| `z_partners` | Partner logos | Homepage, loyalty card display |
| `z_common_data` | Site configuration (name, logo, emails, etc.) | Header, footer, all pages |
| `z_page_contents` | CMS page content | About, generic pages |
| `z_locations` | Available locations | Location selector |
| `z_areas` | Areas within locations | Coupon/merchant/profile filters |
| `z_professions` | Job categories/professions | Profile edit |
| `z_business_signup` | Merchant registration requests | Business sign-up |
| `z_seo` | Page SEO metadata | Various pages |

---

## 7. Key Business Rules

### 7.1 Location-Based Browsing
- Coupons by category, flash discounts by category, and merchant directory **require** a location to be set in the session
- If no location: user is redirected to `select-location.php` with the return URL encoded in a `target` parameter
- Browsing by specific merchant-unit (direct link) does NOT require location
- Location persists in session until changed or session expires

### 7.2 Loyalty Card System
- Customers must select a loyalty card before accessing most authenticated features
- Pages that require loyalty card: My Account, Profile, Offer Zone, Gifted Offer Zone, Store Coupons
- Pages that do NOT require loyalty card: Edit Profile, Change Password, Wishlist, Important Days, Complaints
- Card numbers are pre-generated sequentially in `z_loyalty_card_number` table
- Each card type defines subscription limits: `allowed_subs_per_month` and `allowed_max_subs_live`
- Card number display uses tap-to-reveal pattern (hidden with asterisks by default)

### 7.3 Coupon Subscription Limits
- **Per month limit:** `allowed_subs_per_month` from loyalty card type
- **Concurrent live limit:** `allowed_max_subs_live` from loyalty card type
- **Per coupon limit:** If coupon `redeem_type == 'Single'`, customer can only subscribe once
- **Public availability limit:** Total subscriptions cannot exceed `issued_to_public` count
- **Date limit:** Cannot subscribe after `expiry_to_subscribe` date

### 7.4 Coupon Auto-Expiry
- On loading the Offer Zone page: UPDATE query automatically sets `subscribe_status = -1` for any subscription where `redeem_last_date < NOW()` and status is not already expired
- This ensures expired coupons are properly flagged in real-time

### 7.5 Flash Discount Classification Filtering
- When a logged-in customer browses flash discounts: results are filtered by their loyalty card's `classification_id`
- When not logged in: all flash discounts are shown (no classification filter)
- This ensures customers see only discounts matching their loyalty tier

### 7.6 Review Insert vs Update
- Reviews are identified by combination of `mobile` + `unit_id`
- If a review already exists for that combination: UPDATE (replace ratings, review text)
- If no existing review: INSERT new record
- Review images are always INSERT (accumulated, not replaced)

### 7.7 Auto-Account Creation
- **On complaint submission** (`complaint-submit.php`): If the mobile number doesn't exist in `tbl_customers`, a new account is automatically created with a generated password sent via SMS
- **On registration from coupon details**: After successful registration, user is auto-logged-in and can immediately subscribe

### 7.8 Mandatory Password Reset
- When `temp_password = 1` flag is set on a customer record (e.g., merchant-created account, admin password reset):
  - `check-mandatory-password-reset.php` (included on key authenticated pages) detects this and redirects to `set-new-password.php`
  - The set-new-password form does NOT require the old password
  - After setting new password: `temp_password` is set to `0`

### 7.9 Favorites Scope
- Favorites are per **merchant unit** (not per merchant company)
- Stored in `tbl_unit_favorite` with both `unit_id` and `merchant_id`
- Favorite hearts appear on: coupon listings, flash discount listings, merchant directory, coupon details, flash discount details
- Hearts are only shown when the customer is logged in
- Adding a favorite is idempotent (no duplicate entries)

### 7.10 Offer Zone Navigation
- The Offer Zone, Gifted Offer Zone, and Store Offer pages share a common dropdown navigator:
  - All Coupons → `offer-zone.php?sortBy=All`
  - Active Coupons → `offer-zone.php?sortBy=Active`
  - Redeemed Coupons → `offer-zone.php?sortBy=Redeemed`
  - Expired Coupons → `offer-zone.php?sortBy=Expired`
  - Gifted Coupons → `gifted-offer-zone.php?sortBy=normal`
  - Store Coupons → `store-offer.php`
- This dropdown appears on all three pages, with the current page's option pre-selected

### 7.11 Coupon Status Values
| `subscribe_status` | Meaning |
|----|------|
| `0` | Active (subscribed, awaiting redemption) |
| `1` | Redeemed |
| `-1` | Expired |

### 7.12 Authentication Responses & UI Mapping
| Response Code | Source | UI Action |
|---------------|--------|-----------|
| `'mandatory'` | mobilecheck/otp/login | Show field validation error |
| `'nomobile'` | mobilecheck | Show "No records found" |
| `'unverified'` | mobilecheck | Show OTP input field |
| `'verified'` | mobilecheck/otp | Show password input field |
| `'invalid'` | login | Show "Invalid password" |
| `'success'` | login/register | Redirect to `my-account.php` |
| `'coupon_details'` | login/register | Reload current page (for subscribe) |
