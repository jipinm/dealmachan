# DealMachan Admin Application — Final Consolidated Task List
> **Author:** Analysis of `ADMIN-GAP-ANALYSIS.md` + Merchant Application codebase + Database schema  
> **Date:** February 24, 2026  
> **Purpose:** Definitive task list for completing the Admin application  
>
> This document cross-references what the **Merchant App creates/manages** with what the **Admin App must oversee/control**.  
> Features already fully implemented in the admin are excluded.

---

## Methodology

The merchant application (`e:\DealMachan\merchant\`) was fully analyzed including all API endpoint definitions, page components, and data types. Each merchant-side feature was mapped to:
1. The database table(s) it writes to
2. What platform-level oversight an admin must have over that data
3. Whether that admin oversight exists today

The findings were cross-checked against `ADMIN-GAP-ANALYSIS.md` to avoid duplicating already-completed work.

---

## Merchant Application — Complete Feature Inventory

The merchant app provides the following capabilities (all confirmed from source code):

| # | Feature | Source File(s) | Data Written To |
|---|---|---|---|
| M1 | Create / edit / delete coupons | `coupons.ts`, `CouponFormPage.tsx` | `coupons` |
| M2 | Create / edit / delete flash discounts | `flashDiscounts.ts`, `FlashDiscountPage.tsx` | `flash_discounts` |
| M3 | Create / edit / delete store coupons | `storeCoupons.ts`, `StoreCouponFormPage.tsx` | `store_coupons` |
| M4 | Assign store coupons (single + bulk) to customers | `storeCoupons.ts`, `StoreCouponAssignPage.tsx` | `store_coupons` (is_gifted, gifted_to_customer_id) |
| M5 | Redeem platform coupons via QR / manual / phone | `ScanRedeemPage.tsx`, `coupons.ts` | `coupon_redemptions` |
| M6 | Redeem flash discounts via customer phone | `ScanRedeemPage.tsx`, `flashDiscounts.ts` | `flash_discounts` (current_redemptions) |
| M7 | Redeem store coupons via customer phone | `ScanRedeemPage.tsx`, `storeCoupons.ts` | `store_coupons` (is_redeemed) |
| M8 | Manage stores (CRUD) | `stores.ts`, `StoreFormPage.tsx` | `stores` |
| M9 | Manage store gallery (upload, reorder, set cover, delete) | `stores.ts`, `GalleryPage.tsx` | `store_gallery` |
| M10 | Create / view / update their customers | `customers.ts`, `CustomerDetailPage.tsx` | `customers`, `users` |
| M11 | View customer analytics (frequency, spend, response rate) | `customers.ts` | `sales_registry`, `coupon_redemptions` |
| M12 | Record sales transactions | `analytics.ts (salesApi)`, `SalesPage.tsx` | `sales_registry` |
| M13 | View analytics (redemptions, revenue, top coupons, customers by city) | `analytics.ts`, `AnalyticsPage.tsx` | Read-only |
| M14 | View and respond to grievances (respond + resolve) | `grievances.ts`, `GrievanceDetailPage.tsx` | `grievances` |
| M15 | View and reply to reviews | `reviews.ts`, `ReviewDetailPage.tsx` | `reviews` (merchant_reply, merchant_reply_at) |
| M16 | Send / receive messages with admin | `messages.ts`, `MessageListPage.tsx` | `messages` |
| M17 | View notifications | `merchant.ts`, `NotificationsPage.tsx` | `notifications` (read) |
| M18 | Edit own profile, upload logo | `merchant.ts`, `EditProfilePage.tsx` | `merchants`, `users` |
| M19 | View own subscription, request renewal / upgrade | `merchant.ts`, `SubscriptionPage.tsx` | `subscriptions` |
| M20 | View own assigned labels | `labels.ts`, `LabelsPage.tsx` | `merchant_labels` (read) |

---

## Consolidated Missing & Incomplete Admin Features

Items in this section are either:
- **Confirmed missing** in `ADMIN-GAP-ANALYSIS.md`, with new detail added from merchant app analysis
- **Newly identified** through merchant app analysis (not covered in the prior gap analysis)

Items are marked: 🔴 High priority · 🟡 Medium priority · 🟢 Low priority

---

### 1. Customer Grievance Management
**Tables:** `grievances`  
**Source of data:** Customer app (customer files a grievance); Merchant app responds (`respond`, `resolve`)  
**Priority:** 🔴 High

**Current state in Merchant App:**  
Merchant sees all grievances raised against their stores. They can:
- List by status (open / in_progress / resolved / closed)
- View detail with customer contact info, store name, priority
- Post a resolution note (`/respond`)
- Mark as resolved (`/resolve`)

**What is missing in Admin:**  
Admin has **no controller, no view, no API** for grievances.

| Missing Admin Capability | Why Needed |
|---|---|
| List all grievances platform-wide (all merchants, all statuses) | Oversight — admin must see unresolved complaints across the platform |
| Filter by priority (urgent, high, medium, low) | Triage unresolved urgent complaints quickly |
| View detail with merchant response note | Admin needs full picture before escalating |
| Override status (escalate, forcibly close) | When merchant fails to resolve, admin must intervene |
| Reassign to a different admin for follow-up | City admin or sales admin may need to handle |
| Dashboard KPI: open grievances count | Appears in merchant home page — must appear in admin dashboard too |

---

### 2. Merchant Review Moderation
**Tables:** `reviews`  
**Source of data:** Customer app (customer writes review); Merchant app can view and `reply`  
**Priority:** 🔴 High

**Current state in Merchant App:**  
Merchant sees only their own reviews. They can list by `rating` and `status`. They can post a `merchant_reply`. They **cannot approve or reject** — that is an admin-only action.

**What is missing in Admin:**  
60 reviews exist in the database. All are in `pending` status. No admin screen exists.

| Missing Admin Capability | Why Needed |
|---|---|
| List all reviews platform-wide (filter by merchant, rating, status) | Central moderation queue |
| View review with full detail including merchant's reply text | Admin needs merchant's response before deciding to approve |
| Approve review | Transitions review to publicly visible on merchant profile |
| Reject review | Removes spam/abusive content |
| Delete review | Hard delete for policy violations |
| Bulk approve / reject | Clearing the approval queue efficiently |

---

### 3. Flash Discount — Create & Edit
**Tables:** `flash_discounts`  
**Source of data:** Merchant app — merchants create and manage all flash discounts  
**Priority:** 🔴 High

**Current state in Merchant App:**  
Merchants have full CRUD: `create`, `update`, `delete`, `list`, `redeem`. The flash discount's `status` can be set to active/inactive/expired. Redemptions increment `current_redemptions`.

**What is missing in Admin:**  
The admin `FlashDiscountsController` has only `index`, `detail`, `toggle`, `delete`. There is **no `add()` or `edit()`** method.

| Missing Admin Capability | Why Needed |
|---|---|
| Create a flash discount on behalf of a merchant | Admin-initiated platform promotions |
| Edit title, description, discount %, validity, max redemptions | Correct merchant errors; adjust platform events |
| View per-flash-discount redemption breakdown | The table only stores `current_redemptions` as a count — admin needs to see which customers redeemed to cross-check with `coupon_redemptions` or `sales_registry` |

---

### 4. Subscription & Billing Management
**Tables:** `subscriptions`  
**Source of data:** Merchant app — merchant can view subscription, request `renew` and `upgrade`  
**Priority:** 🔴 High

**Current state in Merchant App:**  
Merchant can see current plan, expiry, auto_renew status. They can call `POST /merchants/subscription/renew` and `POST /merchants/subscription/upgrade`. These actions must be approved or provisioned by someone — but there is no admin queue/approval screen.

**What is missing in Admin:**  

| Missing Admin Capability | Why Needed |
|---|---|
| List all subscriptions (merchant + customer, any status) | Billing oversight |
| Filter by expiry date (expiring in 7/30 days) | Proactive renewal reminders |
| Create subscription for a user manually | Admin-provisioned subscriptions (e.g., comped plans) |
| Renew / extend subscription with payment recording | Process renewal requests from merchants |
| Cancel / expire subscription | Terminate non-paying accounts |
| Approve pending upgrade requests | When merchant requests `upgrade`, admin must act |
| Revenue report from subscriptions | See total subscription revenue vs. plan breakdowns |

---

### 5. Sales Registry — Admin Browser
**Tables:** `sales_registry`  
**Source of data:** Merchant app — merchants create sale records via `POST /merchants/sales-registry`  
**Priority:** 🔴 High

**Current state in Merchant App:**  
Merchants record every transaction (transaction amount, store, payment method, coupon used). The API has: `list` (paginated, filterable), `summary` (date-range summary with daily breakdown), `record` (create new sale), and `export` (CSV download).

**What is missing in Admin:**  
The admin `ReportsController.redemptions()` partially touches redemption data, but there is no cross-merchant sales registry view. Admins cannot:

| Missing Admin Capability | Why Needed |
|---|---|
| Browse all sales across all merchants (with merchant/store filter) | Platform-level transaction audit |
| Filter by date range, payment method, merchant, city | Operational queries |
| View coupon linkage per transaction (which coupon drove the sale) | Marketing ROI analysis |
| Export CSV of all sales | Finance/accounting requirement |
| Aggregate sales summary by merchant | Identify top performers, commissions |
| Aggregate sales summary by city/area | Geographic expansion decisions |

---

### 6. Advertisement / Banner Management
**Tables:** `advertisements`  
**Source of data:** Admin-created (created_by_admin_id FK to admins) — no merchant-side feature  
**Priority:** 🔴 High

**Current state in Merchant App:**  
No advertisement management in merchant app. Ads are purely admin-created content displayed in customer/merchant apps.

**What is missing in Admin:**  
No controller, no view, no route. 13 ads exist in the database (all manually seeded).

| Missing Admin Capability | Why Needed |
|---|---|
| List all advertisements with status/date filters | Campaign management |
| Create advertisement (image or video upload, link URL, scheduling) | New campaigns |
| Edit advertisement details and date range | Campaign adjustments |
| Toggle active / inactive | Turn campaigns on/off |
| Delete advertisement | Remove expired content |
| Preview advertisement (show media in admin) | Visual confirmation before launch |

---

### 7. Blog / CMS Module
**Tables:** `blog_posts`  
**Source of data:** Admin-authored (author_id FK to admins) — no merchant-side feature  
**Priority:** 🟡 Medium

**Current state in Merchant App:**  
No blog management in merchant app.

**What is missing in Admin:**  
No controller, no view, no route. 15 posts exist (published, draft, archived).

| Missing Admin Capability | Why Needed |
|---|---|
| List posts by status (draft / published / archived) | Editorial workflow |
| Create new post with rich-text content, featured image | Content creation |
| Edit post, update status | Publishing workflow |
| Auto-generate slug from title | SEO requirement |
| Publish / Archive post | Lifecycle management |
| Delete post | Remove outdated content |

---

### 8. Audit Log Viewer
**Tables:** `audit_logs`  
**Source of data:** System-generated on every create/update/delete action  
**Priority:** 🔴 High

**Current state in Merchant App:**  
No audit log exposure in merchant app.

**What is missing in Admin:**  
No controller, no view, no route. 60 audit entries exist covering admin and customer actions.

| Missing Admin Capability | Why Needed |
|---|---|
| List logs with filters (user_type, action, table_name, date range) | Security auditing |
| Detail view showing old_values JSON vs new_values JSON diff | Change investigation |
| Filter by a specific record (e.g., all changes to merchant #5) | Targeted auditing |
| Export logs to CSV | Compliance / security reporting |

---

### 9. Gift Coupon Management
**Tables:** `gift_coupons`  
**Source of data:** Admin-initiated (admin_id FK to admins) — no merchant-side equivalent  
**Priority:** 🟡 Medium

**Current state in Merchant App:**  
Not applicable. Only admins gift coupons to customers via this table.

**What is missing in Admin:**  
No controller, no view. 22 gift records exist.

| Missing Admin Capability | Why Needed |
|---|---|
| List all gifted coupons (filter by customer, coupon, status) | Oversight of gifting program |
| Gift a coupon to a specific customer | Core functionality |
| View acceptance status (pending / accepted / rejected) | Track whether customer claimed the gift |
| Revoke a pending gift | Cancel before customer accepts |
| Expiry monitoring (gifts expiring soon) | Proactive management |

---

### 10. Store Gallery — Admin Oversight
**Tables:** `store_gallery`  
**Source of data:** Merchant app — merchants upload, reorder, set cover via `GalleryPage.tsx`  
**Priority:** 🟢 Low

**Current state in Merchant App:**  
Full gallery management: upload images (multipart), set cover, reorder (`PUT .../gallery/reorder`), delete individual images. 119 images exist.

**What is missing in Admin:**  
No dedicated gallery screen in admin. Admins cannot:

| Missing Admin Capability | Why Needed |
|---|---|
| View gallery images per store within the merchant/store edit screen | Content moderation and onboarding assistance |
| Delete inappropriate images | Content policy enforcement |
| Set cover image on behalf of merchant | Manual onboarding support |
| Upload images for a merchant during initial setup | Onboarding workflow |

---

### 11. Referral Tracking & Reward Management
**Tables:** `referrals`  
**Source of data:** System-generated when a referred customer registers  
**Priority:** 🟡 Medium

**Current state in Merchant App:**  
Not applicable — referrals are customer-to-customer.

**What is missing in Admin:**  
No controller, no view. 32 referral records with statuses (pending/completed/rewarded).

| Missing Admin Capability | Why Needed |
|---|---|
| List all referrals (filter by referrer, status, date) | Program oversight |
| View referral chain (who referred whom) | Visual relationship map |
| Manually mark reward as given | For edge cases not auto-triggered |
| Override referral status | Fraud investigation or correction |
| Referral program analytics (conversion rate, total rewards paid) | Program ROI measurement |

---

### 12. Platform-Wide Analytics & Reports
**Tables:** `sales_registry`, `coupon_redemptions`, `flash_discounts`, `subscriptions`, `referrals`, `surveys`, `survey_responses`, `contests`, `contest_participants`, `dealmaker_tasks`, `mystery_shopping_tasks`, `cards`  
**Source of data:** Merchant app, customer app, system  
**Priority:** 🔴 High

**Current state in Merchant App:**  
Merchants have rich analytics scoped to their own business:
- `.dashboard()` — KPIs, weekly redemption chart
- `.redemptions(period)` — daily redemption with discount totals
- `.customers()` — by city, new vs returning
- `.topCoupons()` — highest redemption coupons
- `.revenue(from, to)` — total sales + revenue by store
- `salesApi.summary()` — daily breakdown with payment method split

**What is missing in Admin:**  
The admin `ReportsController` only has 4 methods (dashboard, customers, merchants, redemptions). The following platform-aggregated reports are all absent:

| Missing Report | Source Tables | Priority |
|---|---|---|
| Revenue Report (total GMV, by merchant, by city, date-range) | `sales_registry`, `merchants`, `stores` | 🔴 High |
| Subscription Revenue Report (revenue by plan type, renewal rate) | `subscriptions` | 🔴 High |
| Flash Discount Performance (redemptions per discount, merchant breakdown) | `flash_discounts`, `sales_registry` | 🟡 Medium |
| Coupon ROI Report (redemption count, total discount value, top coupons) | `coupons`, `coupon_redemptions` | 🟡 Medium |
| Referral Funnel Report (referred, registered, rewarded counts) | `referrals`, `customers` | 🟡 Medium |
| Survey Analytics (completion rate, question-level aggregation) | `surveys`, `survey_responses` | 🟢 Low |
| Contest Participation Report (entries per contest, prize allocation) | `contests`, `contest_participants`, `contest_winners` | 🟢 Low |
| Dealmaker Productivity Report (tasks assigned vs completed, rewards paid) | `dealmaker_tasks`, `customers` | 🟡 Medium |
| Mystery Shopping Completion Report (assigned vs verified vs paid) | `mystery_shopping_tasks` | 🟢 Low |
| Card Issuance & Activation Report (generated vs activated vs blocked) | `cards` | 🟢 Low |

---

### 13. Merchant Subscription Renewal Request Queue
**Tables:** `subscriptions`  
**Source of data:** Merchant app — `POST /merchants/subscription/renew` and `POST /merchants/subscription/upgrade`  
**Priority:** 🔴 High

**Current state in Merchant App:**  
Merchant can request renewal or upgrade. These API calls exist but there is no admin queue to see incoming requests and act on them.

**What is missing in Admin:**  
This is a specific workflow gap within Subscription Management (item #4 above). Called out separately because it involves a request-and-approve pattern:

| Missing Admin Capability | Why Needed |
|---|---|
| Pending subscription renewal/upgrade request queue | Admin must provision/approve renewal or upgrade |
| Approve request → create new subscription record | Business process completion |
| Reject request with reason note | Communicate back to merchant |
| Notification to merchant on approval/rejection | Close the loop |

---

### 14. Broadcast Notification System
**Tables:** `notifications`  
**Source of data:** Admin-generated  
**Priority:** 🟡 Medium

**Current state in Merchant App:**  
Not applicable — merchants are recipients of broadcasts, not senders.

**What is missing in Admin:**  
`BroadcastsController` exists but has only a stub `index()`. No view folder, no compose/send methods.

| Missing Admin Capability | Why Needed |
|---|---|
| Compose broadcast (title, message, notification_type) | Platform announcements |
| Target selection: all / all customers / all merchants / specific city / specific admin type | Segmented messaging |
| Send broadcast (inserts rows into `notifications` for all targets) | Core functionality |
| Broadcast history list | Audit what was sent |
| Schedule broadcast for future date/time | Marketing campaigns |

---

### 15. Job Titles — Master Data CRUD
**Tables:** `job_titles`  
**Source of data:** Admin-managed reference data  
**Priority:** 🟢 Low

**Current state in Merchant App:**  
Not applicable.

**What is missing in Admin:**  
`MasterDataController` manages `professions` but has no `jobTitles()` method despite `job_titles` being a child of `professions` (18 rows exist).

| Missing Admin Capability | Why Needed |
|---|---|
| List job titles grouped by profession | Manage reference data |
| Add new job title linked to a profession | New professions require job titles |
| Edit / delete job title | Correctness of customer registration data |
| Toggle active/inactive | Control which titles appear in customer app |

---

### 16. Store Coupon Gifting & Bulk Assignment Visibility
**Tables:** `store_coupons`  
**Source of data:** Merchant app — `storeCouponApi.assign()` and `storeCouponApi.bulkAssign()`  
**Priority:** 🟡 Medium

**Current state in Merchant App:**  
Merchants can gift a store coupon to a single customer (`/assign`) or to multiple customers at once (`/bulk-assign`). The coupon record is updated with `is_gifted = 1` and `gifted_to_customer_id`.

**Current state in Admin:**  
`StoreCouponsController` has `index`, `detail`, `toggle`, `delete` — but the detail view likely does not expose gifting history or bulk assignment count.

| Missing Admin Capability | Why Needed |
|---|---|
| View gifted store coupons (filter by is_gifted = 1) | Oversight of gifting programs |
| See assignment history per store coupon | Audit bulk assignments |
| View which customer a gifted coupon was assigned to | Customer relationship context |
| Revoke an assigned (unissued) coupon | Correction capability |

---

### 17. Customer Profile — Related Data Sub-Views
**Tables:** `customer_important_days`, `referrals`, `subscriptions`, `coupon_subscriptions`, `reviews`, `grievances`  
**Source of data:** Customer app writes `customer_important_days`; system generates referrals; customer app writes coupon saves  
**Priority:** 🟡 Medium

**Current state in Admin:**  
`CustomersController.profile()` shows core customer data. None of the related tables are surfaced in the profile view.

| Missing Admin Capability | Why Needed |
|---|---|
| Important days tab (birthday, anniversary, custom events) | Personalization audit; key for targeted campaigns |
| Subscription history tab (current plan, expiry, history) | Account status at a glance |
| Referral record (who did this customer refer? who referred them?) | Customer value analysis |
| Saved coupons (`coupon_subscriptions`) | See what the customer is interested in |
| Transaction history from `sales_registry` | Full purchase history view |
| Grievances filed by this customer | Support context |
| Reviews written by this customer | Engagement context |

---

### 18. System-Wide Settings
**Tables:** None (application config) / could be a `settings` table if created  
**Source of data:** Admin-configured  
**Priority:** 🟡 Medium

**Current state in Admin:**  
`SettingsController` only has `profile()` (personal profile) and `preferences()` (UI preferences).

| Missing Admin Capability | Why Needed |
|---|---|
| Referral reward configuration (reward amount per successful referral) | Currently hardcoded or absent |
| Coupon approval policy (auto-approve if discount < X%, or always manual) | Workflow configuration |
| Subscription plan definitions (plan names, prices, features, duration) | Referenced by merchant subscription upgrade |
| Card generation defaults (prefix, variant options) | Used by CardsController generate |
| Platform maintenance mode toggle | Planned downtime |
| Email/SMS notification gateway configuration | Integration credentials |
| Default flash discount limits (max % allowed by merchants) | Policy enforcement |

---

## Summary Table — All Missing / Incomplete Admin Features

| # | Feature / Module | Related Table(s) | Data Source | Priority | Status in ADMIN-GAP-ANALYSIS.md |
|---|---|---|---|---|---|
| 1 | Grievance Management | `grievances` | Customer app + Merchant app (responds) | 🔴 High | Confirmed missing (B4) |
| 2 | Review Moderation | `reviews` | Customer app + Merchant app (replies) | 🔴 High | Confirmed missing (B5) |
| 3 | Flash Discount Create & Edit | `flash_discounts` | Merchant app | 🔴 High | Confirmed partial (A8) |
| 4 | Subscription & Billing Management | `subscriptions` | Merchant app (requests) + system | 🔴 High | Confirmed missing (B8) |
| 5 | Sales Registry Browser | `sales_registry` | Merchant app | 🔴 High | Confirmed partial (B9) |
| 6 | Advertisement Management | `advertisements` | Admin-created | 🔴 High | Confirmed missing (B1) |
| 7 | Audit Log Viewer | `audit_logs` | System-generated | 🔴 High | Confirmed missing (B3) |
| 8 | Merchant Subscription Renewal Queue | `subscriptions` | Merchant app (requests) | 🔴 High | NEW — not in prior analysis |
| 9 | Platform-Wide Revenue Report | `sales_registry`, `subscriptions` | Merchant app | 🔴 High | NEW — not in prior analysis (partially noted) |
| 10 | Blog / CMS Module | `blog_posts` | Admin-authored | 🟡 Medium | Confirmed missing (B2) |
| 11 | Gift Coupon Management | `gift_coupons` | Admin-initiated | 🟡 Medium | Confirmed missing (B7) |
| 12 | Referral Tracking | `referrals` | System-generated | 🟡 Medium | Confirmed missing (B6) |
| 13 | Broadcast Notification System | `notifications` | Admin-generated | 🟡 Medium | Confirmed stub (A14) |
| 14 | Coupon ROI / Redemption Reports | `coupons`, `coupon_redemptions` | System | 🟡 Medium | Confirmed partial (A12) |
| 15 | Flash Discount Performance Report | `flash_discounts` | Merchant app | 🟡 Medium | NEW — identified from merchant analytics |
| 16 | Dealmaker Productivity Report | `dealmaker_tasks` | System | 🟡 Medium | Confirmed partial (A12) |
| 17 | Referral Funnel Report | `referrals`, `customers` | System | 🟡 Medium | Confirmed partial (A12) |
| 18 | Store Coupon Gifting Visibility | `store_coupons` | Merchant app | 🟡 Medium | NEW — identified from merchant app bulk-assign |
| 19 | Customer Profile Sub-Views | `customer_important_days`, `referrals`, `subscriptions`, `coupon_subscriptions` | Customer/system | 🟡 Medium | Confirmed partial (A5) |
| 20 | System-Wide Settings | Config | Admin | 🟡 Medium | Confirmed partial (A19) |
| 21 | Store Gallery Management | `store_gallery` | Merchant app | 🟢 Low | Confirmed missing (B10) |
| 22 | Job Titles Master Data | `job_titles` | Admin-managed | 🟢 Low | Confirmed missing (B11) |
| 23 | Survey Response Analytics & Export | `survey_responses` | Customer app | 🟢 Low | Confirmed partial (A11) |
| 24 | Contest Participant Report | `contest_participants` | Customer app | 🟢 Low | Confirmed partial (A7) |
| 25 | Mystery Shopping Completion Report | `mystery_shopping_tasks` | System | 🟢 Low | Confirmed partial (A12) |
| 26 | Card Issuance Report | `cards` | System | 🟢 Low | NEW — identified from full table review |

---

## Prioritized Implementation Plan

### Phase 1 — Critical Operations (must-have for day-to-day platform management)

These block actual business operations today:

| Order | Task | Module | Effort |
|---|---|---|---|
| 1.1 | Grievance Management Module | New `GrievancesController` + views | Medium |
| 1.2 | Review Moderation Module | New `ReviewsController` + views | Small |
| 1.3 | Advertisement Management | New `AdvertisementsController` + views | Medium |
| 1.4 | Audit Log Viewer | New `AuditLogsController` + views (read-only) | Small |
| 1.5 | Flash Discount — Add `add()` and `edit()` | Extend `FlashDiscountsController` | Small |
| 1.6 | Sales Registry Browser | New section or extend `ReportsController` + view | Medium |
| 1.7 | Revenue Report (cross-merchant) | Extend `ReportsController` | Medium |
| 1.8 | Subscription Management + Renewal Queue | New `SubscriptionsController` + views | Medium-Large |

---

### Phase 2 — Important Business Features

| Order | Task | Module | Effort |
|---|---|---|---|
| 2.1 | Blog / CMS Module | New `BlogController` + views | Medium |
| 2.2 | Gift Coupon Management | New `GiftCouponsController` + views | Medium |
| 2.3 | Referral Tracking & Reward Management | New `ReferralsController` + views | Medium |
| 2.4 | Broadcast Notification System (full impl.) | Complete `BroadcastsController` + create view folder | Medium |
| 2.5 | Platform Reports — Coupon ROI, Flash, Dealmaker, Referral | Extend `ReportsController` | Medium |
| 2.6 | Store Coupon Gifting Visibility | Extend `StoreCouponsController.detail()` and index filters | Small |
| 2.7 | Customer Profile Sub-Views | Extend `CustomersController.profile()` view | Small-Medium |
| 2.8 | Merchant Coupon Redemption Detail view | Extend `CouponsController.detail()` | Small |

---

### Phase 3 — Enhancements & Polish

| Order | Task | Module | Effort |
|---|---|---|---|
| 3.1 | Job Titles in Master Data | Extend `MasterDataController` | Small |
| 3.2 | Store Gallery Admin View | Add gallery tab inside merchant store view | Small |
| 3.3 | Contest Participant List/Export | Extend `ContestsController` | Small |
| 3.4 | Survey Response Analytics & Export | Extend `SurveysController.responses()` | Small-Medium |
| 3.5 | System-Wide Settings | Extend `SettingsController` | Medium |
| 3.6 | Mystery Shopping, Card Issuance Reports | Extend `ReportsController` | Small |

---

## Notes on Data Ownership

The following clarifications apply when building admin features:

| Data Domain | Merchant App Role | Admin App Role |
|---|---|---|
| Coupons | Creates, edits, deletes merchant coupons | Approves/rejects, creates platform-wide admin coupons, monitors all |
| Flash Discounts | Creates, edits, deletes merchant flash deals | Should be able to create platform deals; currently can only view/toggle/delete |
| Store Coupons | Creates, edits, assigns, bulk-assigns, redeems | Views, toggles, deletes; needs gifting visibility |
| Sales | Records individual transactions | Views all transactions cross-merchant; generates revenue reports |
| Reviews | Can reply to reviews (but NOT approve/reject) | Sole authority on approve/reject |
| Grievances | Can respond and resolve | Must be able to escalate, override, or take over resolution |
| Subscription | Requests renewal/upgrade | Provisions, approves, and manages all subscription records |
| Store Gallery | Manages gallery images per store | Should be able to moderate (delete, override cover) |
| Notifications | Receives notifications | Sends notifications (broadcast) and manages notification templates |

---

*Document generated by cross-analysis of `merchant/src/api/endpoints/*.ts`, `merchant/src/pages/**/*.tsx`, `database-latest-tables-and-data.sql`, and `admin/controllers/*.php`.*
