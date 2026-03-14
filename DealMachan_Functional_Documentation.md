# Deal Machan — Functional Documentation Report

**Document Version:** 1.0  
**Analysis Date:** March 11, 2026  
**Scope:** Full codebase functional analysis across all four platform applications

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Applications](#2-applications)
3. [User Types](#3-user-types) — Customer, Deal Maker Customer, Merchant, Merchant Store User, Super Admin, City Admin, Sales Admin, Promoter Admin, Partner Admin / Club Admin, Guest
4. [Functional Modules](#4-functional-modules) — 32 modules
5. [Module Relationships](#5-module-relationships)
6. [System Flows](#6-system-flows) — 20 end-to-end flows
7. [Entity Relationships](#7-entity-relationships)
8. [Master Data](#8-master-data)
9. [Cross-Application Interactions](#9-cross-application-interactions)
10. [Current Implementation Scope](#10-current-implementation-scope)

---

## 1. System Overview

Deal Machan is a deal, coupon, and loyalty platform that connects customers, merchants, and administrators within a structured multi-sided marketplace. The platform enables merchants to publish and manage discount offers, loyalty programs, and promotional campaigns, while customers discover offers, redeem coupons, and earn rewards. Administrators oversee all platform activity, approve content, manage users, and generate operational reports.

The platform operates through four distinct applications that work together:

- The **Customer Application** is the consumer-facing mobile interface where customers browse stores and deals, save and redeem coupons, manage loyalty cards, and engage with platform programs such as contests, surveys, and referrals.
- The **Merchant Application** is the business-facing mobile dashboard where merchants manage their stores, create promotional coupons, handle customer relationships, track performance analytics, and manage grievances.
- The **Admin Application** is a web-based management panel where administrators control all platform operations, including user approvals, content management, campaign management, reporting, and system configuration.
- The **Backend API** is the central data and business-logic layer that processes all requests from both the Customer and Merchant applications, enforces security and subscription access rules, and provides publicly accessible data for browsing.

These four applications interact through a shared database, with the API acting as the communication bridge between front-end applications and data storage. The Admin application has its own direct database access path for operational management functions.

---

## 2. Applications

### 2.1 Customer Application

The Customer Application is a mobile-optimised web application that serves as the primary consumer-facing interface. It supports both unauthenticated browsing and authenticated personal features.

**Operational Scope:**
- Public browsing of stores, merchants, deals, coupons, flash deals, categories, and blog content without requiring login.
- Authenticated access to a personal coupon wallet, wishlist, loyalty card, referral program, important dates tracker, activity history, contests, surveys, and grievance submission.
- Location-aware content filtering to show stores and offers relevant to the customer's city.
- OTP-based registration flow for new customer onboarding.
- A dedicated onboarding screen for new users to complete their profile after first login.

**Interaction with Other Applications:**
The Customer Application communicates exclusively with the Backend API using authenticated REST calls. It does not interact with the Admin or Merchant applications directly. Customer-submitted reviews, grievances, and contact enquiries are processed by the API and become visible to merchants and administrators through their respective interfaces.

---

### 2.2 Merchant Application

The Merchant Application is a mobile-optimised web application exclusively for registered business users (merchants). All features require authentication.

**Operational Scope:**
- Store management: create, edit, and manage physical store locations with category assignments, gallery images, and operating hours.
- Coupon management: create and manage promotional coupons of four types — percentage discount, fixed amount, Buy-X-Get-Y (BOGO), and free add-on offers.
- Store Coupon management: create store-specific coupons that can be gifted or assigned directly to individual customers, with individual and bulk assignment capabilities.
- Flash Discount management: time-limited percentage-discount promotions visible on the customer home screen.
- QR Code scanning redemption interface to mark coupons as redeemed at the point of sale.
- Sales registry: record and track manual sales transactions.
- Customer management: view the merchant's customer base, manage customer records, and look up customers during redemption.
- Analytics and performance dashboards showing redemptions, revenue, top coupons, and customer trends.
- Reviews management: view customer reviews and submit merchant replies.
- Grievances management: view and respond to customer complaints, with a threaded message system for resolution.
- Messaging: communicate with customers through an in-app messaging thread system.
- Notification management: receive and review platform notifications.
- Subscription and profile management.
- Feature access is controlled by the merchant's subscription plan.

**Interaction with Other Applications:**
The Merchant Application communicates with the Backend API for all data operations. Merchants do not interact with the Admin or Customer applications directly. Merchant-submitted content (coupons, reviews, profile changes) may require approval from administrators before becoming visible to customers.

---

### 2.3 Admin Application

The Admin Application is a full web-based management panel for platform administrators. It provides comprehensive control over all system operations.

**Operational Scope:**
- Merchant management: register, approve, reject, and manage merchant accounts and their stores.
- Customer management: view, manage, and manually create customer accounts.
- Coupon and campaign management: create, approve, and manage all coupons across the platform, including setting category and city targeting.
- Flash discount and gift coupon management.
- Contest management: create and manage participant contests with winner tracking.
- Survey management: create and assign surveys to the customer base.
- Mystery shopping task management: assign evaluation tasks to approved customers.
- Blog and CMS content management: publish blog posts and manage static informational pages.
- Advertisement management: manage promotional banners displayed on the customer home screen.
- Referral program management.
- Reports: generate detailed reports on customers, merchants, redemptions, and coupons with filterable date ranges.
- Audit log viewing: monitor all system actions for security and compliance.
- Notification broadcasting: send targeted notifications to all customers, all merchants, or specific users.
- Admin user management: manage the team of administrators and assign roles.
- Subscription management: manage merchant and customer subscription records.
- Master data management: configure cities, areas, locations, categories, sub-categories, tags, professions, labels, and other reference data.
- Platform settings management: configure system-wide settings.
- Grievance oversight: review and manage all customer grievances.
- Lead management: track merchant acquisition leads.
- Deal Maker program management: approve and manage customers who serve as community deal ambassadors.

**Interaction with Other Applications:**
The Admin Application accesses the database directly through server-side PHP controllers. It provides the configuration and governance layer that determines what content customers and merchants see. Approvals performed in the Admin application unlock content visibility in the Customer and Merchant applications.

---

### 2.4 Backend API

The Backend API is the centralised REST API that serves all requests from both the Customer and Merchant applications.

**Operational Scope:**
- Authentication and session management for both customers and merchants using JWT tokens with refresh token support.
- Public endpoints for browsing content without authentication (stores, merchants, coupons, flash deals, blog, categories, search, advertisements).
- Authenticated customer endpoints for wallet management, coupon saving and redemption, profile, loyalty cards, referrals, contests, surveys, grievances, important days, and notifications.
- Authenticated merchant endpoints for all merchant operations including store management, coupon management, store coupons, flash discounts, sales registry, customer management, analytics, reviews, grievances, messages, and notifications.
- Subscription-plan-based feature access control through a Feature Gate that restricts merchant features based on their plan tier.
- Store-level access scoping so that multi-user merchant accounts can be restricted to specific stores.
- Image upload and management for coupon banners, store galleries, and merchant logos.
- City and area-based filtering across all public content endpoints.

---

## 3. User Types

### 3.1 Customer

**Role:** End consumer of the platform, seeking deals and savings.

**Functional Responsibilities:**
- Register and manage a personal profile.
- Browse merchants, stores, deals, coupons, flash deals, and categories.
- Save coupons to a personal wallet and present them for redemption.
- Redeem coupons with merchants to receive discounts.
- Add merchants or stores to a personal wishlist (favourites).
- Submit reviews and ratings for merchants.
- Submit and track grievances related to merchant experiences.
- Participate in contests, surveys, and referral programs.
- Manage a loyalty card linked to their account.
- Track personal important days (e.g. birthdays, anniversaries) to receive relevant offers.
- Apply to become a Deal Maker.
- Accept and use gifted coupons from merchants.

**System Access:** Customer Application (full access) and public areas of the Backend API.

**Modules:** Authentication, Coupon Management, Store Coupon Management, Flash Discount Management, Review Management, Grievance Management, Loyalty Card System, Referral Program, Contest Management, Survey Management, Deal Maker Program, Notification System, Search, Wishlist / Favourites, Important Days Tracker, Activity History, Blog & CMS Content, Contact Enquiries

---

### 3.2 Deal Maker Customer

**Role:** A special customer tier with elevated platform responsibilities.

**Functional Responsibilities:** All standard customer capabilities, plus:
- Receive assigned merchant evaluation tasks (mystery shopping-adjacent tasks).
- Complete tasks and submit confirmations.
- Earn reward payments for completed tasks.
- Track earnings and task history.

**System Access:** Customer Application with additional Deal Maker-specific views.

**Modules:** All standard customer modules, plus: Deal Maker Program (task tracking and earnings), Mystery Shopping Management (as assigned shopper)

---

### 3.3 Merchant (Business Owner)

**Role:** The business partner who publishes offers and manages customer engagement.

**Functional Responsibilities:**
- Manage the business profile and logo.
- Create and manage store locations with category classifications and operating hours.
- Create and manage various coupon types (percentage, fixed, BOGO, add-on).
- Create and manage store-specific coupons and gift coupons for direct customer assignment.
- Publish flash discounts for time-sensitive promotions.
- Scan and redeem customer coupons at the point of sale using QR codes.
- Register sales transactions in the sales registry.
- View and respond to customer reviews.
- Manage and respond to customer grievances.
- Send and receive messages with customers.
- View analytics including redemption trends, revenue estimates, and customer behaviour.
- Manage the customer base created through the merchant's own channels.
- Manage custom customer labels (tags).
- Receive and review platform notifications.
- Manage subscription plan and view feature entitlements.

**System Access:** Merchant Application (full access), Backend API (authenticated endpoints for merchants). Feature access depends on subscription plan tier.

**Modules:** Authentication, Merchant Management (own profile), Store Management, Coupon Management, Store Coupon Management, Flash Discount Management, Analytics & Reporting, Review Management, Grievance Management, Messaging System, Notification System, Subscription Management, Subscription Feature Gate, Customer Management

---

### 3.4 Merchant Store User

**Role:** A staff member of a merchant business with restricted access.

**Functional Responsibilities:**
- Access is scoped to specific stores as defined by the merchant.
- Performs coupon redemptions and store-level operations within their designated store(s).

**System Access:** Merchant Application, restricted to assigned store scope via JWT token claims.

**Modules:** Authentication, Store Management (assigned stores only), Coupon Management (redemption only), Store Coupon Management (redemption only)

---

### 3.5 Super Admin

**Role:** The highest-level platform administrator with unrestricted access.

**Functional Responsibilities:**
- All platform management operations without restriction.
- Exclusive access to subscription management, audit log viewing, and admin user management.
- Broadcast notifications to all user types.
- Full access to all reports and settings.

**System Access:** Admin Application, all modules.

**Modules:** All modules

---

### 3.6 City Admin

**Role:** A regional administrator scoped to a specific city.

**Functional Responsibilities:**
- Manage merchants and customers within their assigned city.
- Approve and manage city-relevant content.
- View dashboards and reports filtered to their city.
- Broadcast notifications to users within their city.

**System Access:** Admin Application, filtered to city scope.

**Modules:** Merchant Management, Customer Management, Coupon Management, Flash Discount Management, Store Management, Review Management, Grievance Management, Analytics & Reporting, Notification System, Blog & CMS Content, Advertisement Management, Contest Management, Survey Management, Gift Coupons, Audit Log System, Lead Management, Master Data Management

---

### 3.7 Sales Admin

**Role:** An administrator focused on merchant acquisition and relationship management.

**Functional Responsibilities:**
- Manage merchant accounts.
- Track and manage merchant acquisition leads.

**System Access:** Admin Application, merchant and lead management modules.

**Modules:** Merchant Management, Customer Management, Lead Management

---

### 3.8 Promoter Admin

**Role:** An administrator responsible for community engagement and mystery shopping programs.

**Functional Responsibilities:**
- Assign and manage mystery shopping tasks.
- Oversee customer engagement programs.

**System Access:** Admin Application, mystery shopping module.

**Modules:** Mystery Shopping Management, Deal Maker Program, Contest Management

---

### 3.9 Partner Admin / Club Admin

**Role:** Specialist administrators for partnership and club-based programs.

**Functional Responsibilities:** Operational management within their specific domain.

**System Access:** Admin Application, relevant domain modules.

**Modules:** Domain-specific modules within their program scope

---

### 3.10 Guest (Unauthenticated Visitor)

**Role:** A visitor who accesses the platform without creating an account or logging in.

**Functional Responsibilities:**
- Browse stores, merchants, coupons, flash deals, and categories without authentication.
- View contest listings and blog content.
- View coupon offer details (coupon codes are hidden until login).
- Submit contact and general enquiry forms.
- Submit business sign-up interest forms.
- Register or log in to access personal platform features.

**System Access:** Public areas of the Backend API and Customer Application. All personal features (wallet, loyalty card, reviews, grievances, referrals) require login.

**Modules:** Search, Store Management (browse only), Coupon Management (browse and view only), Flash Discount Management (browse only), Blog & CMS Content, Advertisement Management, Contest Management (view only), Contact Enquiries

---

## 4. Functional Modules

### 4.1 Authentication & Session Management

**Purpose:** Manages secure access for all user types across all applications.

**Applications:** Customer Application, Merchant Application, Backend API.

**Functional Responsibilities:**
- Customer registration with phone/email and OTP verification.
- Customer login with email/phone and password.
- Merchant login with email and password, returning a JWT with subscription plan and store scope embedded.
- Password reset via email-based token flow for both user types.
- JWT access tokens with expiry and refresh token support for session persistence.
- Mandatory password reset enforcement on first login for customers registered by merchants or admins.
- Admin login with separate session-based authentication in the Admin Application.

**User Types:** Customer, Merchant, Merchant Store User, Super Admin, City Admin, Sales Admin, Promoter Admin, Partner Admin, Club Admin

**Key Entities:** `users`, `merchants`, `customers`, `admins`, `refresh_tokens`, `password_resets`

---

### 4.2 Merchant Management

**Purpose:** End-to-end lifecycle management of merchant accounts.

**Applications:** Admin Application, Backend API.

**Functional Responsibilities:**
- Create, view, edit, and deactivate merchant accounts.
- Manage merchant business profile details, logo, registration number, GST number.
- Assign subscription plans (Merchant Only, Coupon Merchant, Hybrid, Advanced) and subscription status.
- Approve or reject merchant profiles — only approved merchants appear in customer-facing listings.
- Assign labels, priority weights, and premium partner status for search visibility ordering.
- Manage the stores belonging to each merchant.
- View merchant activity and audit trails.

**User Types:** Super Admin, City Admin, Sales Admin

**Key Entities:** `merchants`, `users`, `stores`, `subscriptions`, `merchant_labels`, `merchant_tags`, `labels`

---

### 4.3 Store Management

**Purpose:** Management of physical store locations for each merchant.

**Applications:** Merchant Application, Admin Application, Backend API.

**Functional Responsibilities:**
- Create and manage store records including name, address, city, area, phone, email, description, and operating hours.
- Assign stores to categories and sub-categories through a pivot relationship.
- Upload and manage a store image gallery with cover image designation and display ordering.
- Store status can be set to active or inactive — only active stores appear in public listings.
- Multiple staff users can be assigned to specific stores with restricted access scopes.
- Public browsing endpoint allows customers to discover stores by location and category.

**User Types:** Merchant (create and manage), Merchant Store User (assigned stores), Super Admin, City Admin (manage), Customer and Guest (browse)

**Key Entities:** `stores`, `store_categories`, `store_gallery`, `cities`, `areas`, `locations`, `categories`, `sub_categories`, `merchant_store_users`

---

### 4.4 Coupon Management

**Purpose:** Creation and lifecycle management of merchant promotional coupons.

**Applications:** Merchant Application, Admin Application, Backend API, Customer Application.

**Functional Responsibilities:**
- Merchants create coupons of four types:
  - **Percentage discount:** A percentage off the purchase amount.
  - **Fixed amount discount:** A defined monetary amount reduction.
  - **Buy X Get Y (BOGO):** Customer buys a defined quantity and receives a free quantity.
  - **Free Add-on:** Customer receives a described complimentary item with purchase.
- Coupons have validity dates, usage limits, minimum purchase requirements, maximum discount caps, and optional banner images.
- Coupons require admin approval before becoming publicly visible.
- Admin-created coupons bypass the merchant workflow and are directly published.
- Categories and city targets can be assigned to coupons for targeted distribution.
- Bulk allotment allows a coupon to be pre-assigned to a group of recipients at once.
- Customers save coupons to their wallet and redeem them at the merchant's store.
- Coupon redemptions are recorded with store, customer, discount amount, and transaction value.
- Merchants can scan QR codes or manually enter codes to process redemptions.
- All redemption data feeds the analytics module.

**User Types:** Merchant (create and redeem), Customer (save and redeem), Guest (browse only), Super Admin, City Admin (approve and manage)

**Key Entities:** `coupons`, `coupon_categories`, `coupon_city_targets`, `coupon_tags`, `coupon_subscriptions`, `coupon_redemptions`, `coupon_bulk_allotments`, `coupon_allotment_items`

---

### 4.5 Store Coupon Management

**Purpose:** Merchant-issued store-level coupons that are assigned directly to specific customers.

**Applications:** Merchant Application, Customer Application, Backend API.

**Functional Responsibilities:**
- Merchants create store coupons tied to a specific store, with percentage or fixed discount.
- Coupons can be gifted to one customer directly or bulk-assigned to a list of customers.
- Gifted coupons appear in the customer's coupon wallet.
- Merchants can track which customers have received and redeemed each store coupon.
- Redemption is recorded when the customer presents the coupon at the merchant's store.
- Usage limits per customer can be configured to prevent overuse.

**User Types:** Merchant (create, assign, and redeem), Merchant Store User (redeem at store), Customer (receive and redeem)

**Key Entities:** `store_coupons`, `store_coupon_limits`, `customers`, `stores`, `merchants`

---

### 4.6 Flash Discount Management

**Purpose:** Short-duration, high-visibility promotional discounts with limited redemption windows.

**Applications:** Merchant Application (creation), Admin Application, Customer Application (browsing), Backend API.

**Functional Responsibilities:**
- Merchants create flash discounts with a percentage value, start and end datetime, and optional maximum redemption cap.
- Flash discounts optionally associated with a specific store.
- Active flash discounts appear prominently on the customer home screen.
- The system automatically marks flash discounts as expired once their end time passes.
- Publicly accessible end-point allows customers to browse all active flash discounts.

**User Types:** Merchant (create and manage), Customer (browse), Guest (browse), Super Admin, City Admin

**Key Entities:** `flash_discounts`, `stores`, `merchants`

---

### 4.7 Customer Management

**Purpose:** Management of customer accounts and profiles.

**Applications:** Admin Application, Merchant Application, Backend API.

**Functional Responsibilities:**
- Administrators can create, view, and manage customer accounts.
- Merchants can create customer records through the merchant app for customers who have not self-registered.
- Customer registration types are tracked (self-registration, merchant-created, admin-created, pre-printed card, auto-profile).
- Customer profiles include name, date of birth, gender, profession, city, and area.
- Subscription status tracking for customers with active or expired subscription status.
- Merchants can view the analytics of individual customers within their base.

**User Types:** Super Admin, City Admin, Sales Admin, Merchant

**Key Entities:** `customers`, `users`, `cities`, `areas`, `professions`, `job_titles`

---

### 4.8 Loyalty Card System

**Purpose:** A physical or digital loyalty card program linked to customer accounts.

**Applications:** Customer Application, Admin Application, Backend API.

**Functional Responsibilities:**
- Cards are generated and managed through the Admin Application.
- Cards can be pre-printed physical cards or digitally issued cards.
- Pre-printed cards require a two-step activation process:
  1. Customer submits the card number to request an authorisation code.
  2. A 6-digit time-limited auth code is generated and communicated to the card issuer (merchant or admin).
  3. Customer enters the auth code to complete activation.
- Each customer can have one active loyalty card at a time.
- Card details including variant, image, and custom parameters are accessible in the customer profile.

**User Types:** Customer (activate and view), Super Admin, City Admin (create and manage)

**Key Entities:** `cards`, `card_auth_codes`, `customers`, `users`

---

### 4.9 Analytics & Reporting

**Purpose:** Performance monitoring and business intelligence for merchants and administrators.

**Applications:** Merchant Application (merchant analytics), Admin Application (platform-wide reports), Backend API.

**Merchant Analytics Functional Capabilities:**
- Dashboard KPIs: total coupons, active coupons, total redemptions, today's redemptions, this month's redemptions, total unique customers, average review rating, and pending grievances.
- 7-day weekly bar chart of redemptions for the home dashboard.
- Detailed redemption history with date range filtering and coupon-level breakdowns.
- Customer analytics: unique customers, returning customers, and acquisition trends.
- Top performing coupons ranked by redemption volume.
- Revenue summary from recorded sales transactions.

**Admin Reports Functional Capabilities:**
- Summary statistics across the full platform.
- Monthly overview trends (6-month history).
- Customer reports: totals, growth trends, and customer list with date range filtering.
- Merchant reports: top merchants by redemption volume and full merchant list.
- Redemption reports: totals, trend charts, and top coupons by date range.

**User Types:** Merchant (own analytics), Super Admin, City Admin (platform-wide reports)

**Key Entities:** `coupon_redemptions`, `coupons`, `merchants`, `customers`, `sales_registry`, `reviews`, `stores`

---

### 4.10 Review Management

**Purpose:** Customer review and rating system for merchants.

**Applications:** Customer Application (submission), Merchant Application (management), Backend API.

**Functional Responsibilities:**
- Customers submit ratings and text reviews for merchants.
- Reviews are automatically set to approved status on submission.
- Merchants can view all reviews for their business sorted by recency.
- Merchants can submit a reply to any review.
- Average rating is calculated from all published reviews and used in merchant analytics.
- Administrators can manage all reviews including status changes.

**User Types:** Customer (submit), Merchant (reply), Super Admin, City Admin (manage)

**Key Entities:** `reviews`, `merchants`, `customers`, `users`

---

### 4.11 Grievance Management

**Purpose:** Customer complaint and dispute resolution system.

**Applications:** Customer Application (submission), Merchant Application (response), Admin Application (oversight), Backend API.

**Functional Responsibilities:**
- Customers submit grievances describing their complaint.
- Grievances are visible to the relevant merchant and to administrators.
- A threaded message system allows back-and-forth communication between the customer and the merchant within a grievance.
- Both customers and merchants can add messages to the thread.
- Merchants can resolve grievances, changing their status to resolved.
- Administrators oversee all grievances and can handle escalated issues.
- Grievance counts appear in the merchant analytics dashboard as a performance signal.

**User Types:** Customer (submit and follow up), Merchant (respond and resolve), Super Admin, City Admin (oversee all)

**Key Entities:** `grievances`, `grievance_messages`, `customers`, `merchants`, `coupons`

---

### 4.12 Messaging System

**Purpose:** Direct communication channel between merchants and customers.

**Applications:** Merchant Application, Admin Application (notification-adjacent), Backend API.

**Functional Responsibilities:**
- Merchants can initiate and manage message threads with customers.
- Messages are organised into conversation threads by recipient.
- Unread message tracking with mark-as-read capability.
- Notifications can be sent through the messaging infrastructure to customers, merchants, and admins.

**User Types:** Merchant (initiate and respond), Customer (receive and reply)

**Key Entities:** `messages`, `users`, `merchants`, `customers`

---

### 4.13 Notification System

**Purpose:** In-application notification delivery to all user types.

**Applications:** All applications (Admin broadcasts, merchant and customer receives), Backend API.

**Functional Responsibilities:**
- System generates notifications for events such as coupon approval, loyalty card activation requests, and grievance updates.
- Administrators can broadcast targeted notifications to: all admins, all customers, all merchants, or specific individuals.
- Notification types include: info, success, warning, error, promotion, coupon, flash discount, contest, referral.
- Merchants can view and mark notifications as read individually or all at once.
- Customers receive notifications through the customer application.

**User Types:** All authenticated users (receive), Super Admin, City Admin (broadcast)

**Key Entities:** `notifications`, `users`, `admins`

---

### 4.14 Contest Management

**Purpose:** Customer engagement through competitions with prizes.

**Applications:** Customer Application (participation), Admin Application (management), Backend API.

**Functional Responsibilities:**
- Administrators create contests with title, description, start and end dates.
- Active contests are publicly visible (no login required to view).
- Authenticated customers can enter contests.
- Entry data can be captured (entry submissions).
- Winners are recorded with position and prize details.
- Customers can view their contest entries and check if they have won.

**User Types:** Customer (participate), Guest (view listings), Super Admin, City Admin (create and manage)

**Key Entities:** `contests`, `contest_participants`, `contest_winners`, `customers`

---

### 4.15 Survey Management

**Purpose:** Data collection from the customer base.

**Applications:** Customer Application (participation), Admin Application (creation), Backend API.

**Functional Responsibilities:**
- Administrators create surveys with configurable questions (stored as structured JSON).
- Surveys have defined active periods and status controls.
- Active surveys are accessible to authenticated customers.
- Customers submit responses to surveys.
- Each customer can submit responses only once per survey.
- Submission history is accessible to customers to see their completed surveys.

**User Types:** Customer (complete surveys), Super Admin, City Admin (create and review results)

**Key Entities:** `surveys`, `survey_responses`, `customers`

---

### 4.16 Referral Program

**Purpose:** Customer acquisition incentive through peer referrals.

**Applications:** Customer Application, Backend API.

**Functional Responsibilities:**
- Each customer receives a unique referral code at registration.
- Customers share their referral code to invite others to join the platform.
- Referral relationships are tracked with status: pending, completed, rewarded.
- Reward amounts are tracked per successful referral.
- Customers view their referral statistics and history in the Customer Application.

**User Types:** Customer (share and track rewards)

**Key Entities:** `referrals`, `customers`, `users`

---

### 4.17 Deal Maker Program

**Purpose:** A community ambassador program where approved customers earn rewards for promoting and evaluating merchants.

**Applications:** Customer Application, Admin Application, Backend API.

**Functional Responsibilities:**
- Customers apply to become Deal Makers by submitting a motivation statement, city, and experience.
- Applications are reviewed and approved or rejected by administrators.
- Approved Deal Makers receive assigned tasks from admins.
- Tasks include descriptions and optional checklists.
- Deal Makers work on tasks, progress them through assigned → in-progress → completed states.
- Reward amounts are tracked per task, with payment status (pending, paid).
- Deal Maker earnings history is viewable in the Customer Application.

**User Types:** Customer (apply and complete tasks), Super Admin, Promoter Admin (approve applications and assign tasks)

**Key Entities:** `dealmaker_applications`, `dealmaker_tasks`, `customers`, `users`

---

### 4.18 Mystery Shopping Management

**Purpose:** Admin-driven quality evaluation of merchants through undercover customer tasks.

**Applications:** Admin Application.

**Functional Responsibilities:**
- Administrators assign mystery shopping tasks to selected customers (shoppers) for specific merchants or stores.
- Tasks include descriptions, checklists in JSON format, and optional payment amounts.
- Task statuses are tracked through the lifecycle.
- The system records assignment, payment status, and completion.
- Stats and overviews are available to promoter admins and super admins.

**User Types:** Super Admin, Promoter Admin (assign and manage), Customer (receive tasks as shopper)

**Key Entities:** `mystery_shopping_tasks`, `customers`, `merchants`, `stores`

---

### 4.19 Blog & CMS Content

**Purpose:** Public informational content to support SEO, education, and brand communication.

**Applications:** Customer Application (browsing), Admin Application (management), Backend API.

**Functional Responsibilities:**
- Admins create and publish blog posts with title, content, category, featured image, and tags.
- Published posts are publicly accessible through the Customer Application.
- CMS pages handle static content (about, contact, terms, etc.) with slug-based URL routing.
- Both blog posts and CMS pages are fully manageable through the Admin Application.

**User Types:** Customer (browse), Guest (browse), Super Admin, City Admin (create and manage)

**Key Entities:** `blog_posts`, `cms_pages`, `tags`

---

### 4.20 Advertisement Management

**Purpose:** Promotional banner management for the customer home screen.

**Applications:** Customer Application (display), Admin Application (creation), Backend API.

**Functional Responsibilities:**
- Administrators create advertisement entries with title, media (image or video), display duration, and optional link URL.
- Active advertisements within their scheduled date range are returned by the home feed API.
- Advertisements appear as a carousel or banner on the customer home screen.

**User Types:** Guest and Customer (view on home screen), Super Admin, City Admin (create and manage)

**Key Entities:** `advertisements`

---

### 4.21 Subscription Management

**Purpose:** Management of subscription plans and status for merchants and customers.

**Applications:** Admin Application.

**Functional Responsibilities:**
- Administrators manage subscription records for both merchants and customers.
- Subscription plan tiers for merchants determine which features are accessible in the Merchant Application.
- The four merchant plan tiers are: Merchant Only, Coupon Merchant, Hybrid, and Advanced.
- Subscription status (trial, active, expired) and expiry dates are managed.
- Merchants can themselves request subscription renewal or upgrade through the Merchant Application.
- Customer subscription status controls premium customer benefits.

**User Types:** Super Admin (manage all), Merchant (self-service renewal request)

**Key Entities:** `subscriptions`, `merchants`, `customers`

---

### 4.22 Subscription Feature Gate

**Purpose:** Plan-based access control restricting merchant features to their subscription tier.

**Application:** Backend API.

**Feature Availability by Plan:**

| Feature | Merchant Only | Coupon Merchant | Hybrid | Advanced |
|---|---|---|---|---|
| Profile & Stores | ✓ | ✓ | ✓ | ✓ |
| Coupons | — | ✓ | ✓ | ✓ |
| Store Coupons | — | ✓ | ✓ | ✓ |
| Analytics | — | — | ✓ | ✓ |
| Reviews | — | — | ✓ | ✓ |
| Grievances | — | — | ✓ | ✓ |
| Messages | — | — | ✓ | ✓ |
| Loyalty Cards | — | — | — | ✓ |
| Flash Discounts | — | — | — | ✓ |
| Bulk Allotment | — | — | — | ✓ |
| Customer Management | — | — | — | ✓ |
| Sales Registry | — | — | — | ✓ |

When a merchant attempts to access a feature blocked by their plan, the API returns a 403 response.

**User Types:** Merchant, Merchant Store User (access restrictions applied)

**Key Entities:** `merchants` (subscription_plan field), JWT token payload (plan embedded at login)

---

### 4.23 Search

**Purpose:** Cross-entity discovery allowing customers to find relevant content.

**Applications:** Customer Application, Backend API.

**Functional Responsibilities:**
- A unified search endpoint accepts a keyword query.
- Search results can include merchants, stores, coupons, and blog posts.
- Results are returned in a structured format for display across search result sections.

**User Types:** Guest, Customer

**Key Entities:** `merchants`, `stores`, `coupons`, `blog_posts`

---

### 4.24 Wishlist / Favourites

**Purpose:** Customer bookmarking of preferred merchants or stores.

**Applications:** Customer Application, Backend API.

**Functional Responsibilities:**
- Customers can mark merchants as favourites.
- Favourites are tracked per customer.
- The wishlist page displays all saved merchants for quick access.

**User Types:** Customer

**Key Entities:** `customer_favourite_merchants`, `merchants`, `customers`

---

### 4.25 Important Days Tracker

**Purpose:** Helps customers receive timely offers around their personal important dates.

**Applications:** Customer Application, Backend API.

**Functional Responsibilities:**
- Customers create records of important days (e.g., birthday, anniversary, milestone dates).
- Each important day has a label (day type), a date, and optional merchant tagging.
- The system can use important day data to personalise deal suggestions.
- Day types are configured as master data by administrators.

**User Types:** Customer

**Key Entities:** `customer_important_days`, `day_types`, `customers`, `merchants`

---

### 4.26 Activity History

**Purpose:** A personal log of the customer's platform interactions.

**Applications:** Customer Application.

**Functional Responsibilities:**
- Displays the history of the customer's coupon saves, redemptions, and platform activity.

**User Types:** Customer

**Key Entities:** `coupon_subscriptions`, `coupon_redemptions`, `customers`

---

### 4.27 Audit Log System

**Purpose:** Security and compliance monitoring of all significant admin actions.

**Applications:** Admin Application.

**Functional Responsibilities:**
- Every significant operation performed through the Admin Application (create, update, delete, approve, reject) is recorded with actor, action type, affected entity, and timestamp.
- Super admins can view, filter, and inspect audit log entries.
- Filters include user type, action, table name, date range, and IP address.

**User Types:** Super Admin

**Key Entities:** `audit_logs`, `admins`, `users`

---

### 4.28 Admin Team Management

**Purpose:** Management of the administrative team.

**Applications:** Admin Application.

**Functional Responsibilities:**
- Super admins create, edit, deactivate, and delete admin accounts.
- Admin types: Super Admin, City Admin, Sales Admin, Promoter Admin, Partner Admin, Club Admin.
- City-scoped admins are linked to a specific city.

**User Types:** Super Admin

**Key Entities:** `admins`, `cities`

---

### 4.29 Lead Management

**Purpose:** Merchant acquisition pipeline tracking.

**Applications:** Admin Application.

**Functional Responsibilities:**
- Sales admins and super admins log and manage merchant acquisition leads.
- Tracks lead status through the sales pipeline.

**User Types:** Super Admin, Sales Admin

**Key Entities:** `merchant_leads`, `merchants`, `cities`

---

### 4.30 Platform Settings

**Purpose:** System-wide configuration management.

**Applications:** Admin Application.

**Functional Responsibilities:**
- Super admins manage key-value platform settings stored in the `platform_settings` table.
- Settings control platform behaviour such as default values and feature toggles.

**User Types:** Super Admin

**Key Entities:** `platform_settings`

---

### 4.31 Contact Enquiries

**Purpose:** Captures contact form submissions from visitors and business sign-up interest forms from potential merchants, for follow-up by the admin team.

**Applications:** Admin Application (management), Backend API (public submission endpoints).

**Functional Responsibilities:**
- Visitors and customers submit general contact messages through a contact form in the Customer Application.
- Potential merchants submit business sign-up interest forms to register their interest in joining the platform.
- Enquiries are stored and visible in the Admin Application with status tracking (new, read, responded).
- Opening an enquiry detail in the Admin Application automatically marks it as read.
- Admin can filter enquiries by status to manage unread and pending items.
- Business sign-up enquiries are flagged as a separate type, allowing the sales team to prioritise merchant acquisition follow-ups.
- Status counts are shown on the enquiries list for at-a-glance unread tracking.

**User Types:** Guest and Customer (submit), Super Admin, City Admin, Sales Admin (view and manage)

**Key Entities:** `contact_enquiries`

---

### 4.32 Gift Coupons (Admin-Initiated)

**Purpose:** Allows administrators to send existing platform coupons directly to specific customers as personalised gifts.

**Applications:** Admin Application (creation and management), Customer Application (receive and accept/reject), Backend API (customer-facing acceptance endpoints).

**Functional Responsibilities:**
- Admins select an active platform coupon and assign it as a gift to a specific customer.
- Gifts can optionally require customer acceptance before the coupon is added to the wallet.
- Gift coupons have optional expiry dates independent of the source coupon's own validity.
- Customers view received gift coupons in the Customer Application and can accept or reject them.
- Accepted gift coupons are added to the customer's wallet and become redeemable.
- Rejected or expired gift coupons are tracked in the gift record.
- Admin can view all gift coupons with filters for acceptance status, customer, coupon, and date range.
- Summary statistics display total gifts sent and acceptance counts.

**User Types:** Super Admin, City Admin (create and manage gifts), Customer (receive, accept, or reject)

**Key Entities:** `gift_coupons`, `coupons`, `customers`, `admins`

---

## 5. Module Relationships

### Merchant → Store
A Merchant must have at least one Store to offer coupons, flash discounts, and store coupons. Every coupon, store coupon, and flash discount can be associated with a specific store within the merchant's portfolio.

### Store → Category
Stores are classified using a many-to-many pivot relationship with categories and sub-categories. This classification drives store discovery by category in the Customer Application.

### Coupon → Merchant / Store
Every coupon belongs to a Merchant. Optionally, a coupon can be attributed to a specific Store. Coupon searches and home-feed filtering leverage both the merchant's location (via stores) and the coupon's content.

### Coupon → Category / City Targets
Coupons have targeted category assignments and city targeting. This allows platform-wide (admin) coupons to be surfaced only to relevant cities and in relevant category contexts.

### Coupon → Redemption → Customer
When a coupon is redeemed, a redemption record links the coupon, the customer, the store, the discount applied, and the transaction value. This record drives all merchant analytics.

### Coupon → Subscription (Customer Saves)
Customers save coupons to their wallet through a subscription record, separate from redemption. This allows merchants and admins to see demand (save counts) before redemption occurs.

### Store Coupon → Gift → Customer
Store coupons are linked directly to specific customer recipients. The gift chain flows: Merchant creates store coupon → assigns to customer → customer sees in wallet → redeems at store.

### Review → Merchant
Reviews belong to a Merchant and are submitted by Customers. Merchant analytics aggregates the review average rating.

### Grievance → Merchant / Customer → Thread Messages
Each grievance links a Customer, a Merchant, and an optional Coupon as context. The grievance_messages table stores the threaded conversation.

### Analytics → Coupon Redemptions
Merchant analytics are computed entirely from the coupon_redemptions table. Filtering by merchant_id across redemptions provides all KPI data.

### Notifications → Messaging Infrastructure
Notifications (in-app alerts) are delivered through the same Message model that handles inter-party message threads. Different notification types allow the UI to render them with appropriate styling.

### Subscription Plan → Feature Gate
The merchant's `subscription_plan` value is embedded in the JWT on login. Every protected API endpoint checks the plan against the Feature Gate to determine whether access is permitted before processing the request.

### Contest / Survey / Referral → Customer
These engagement modules all link participation records back to individual customers, allowing customers to track their own activity within each program.

### Deal Maker → Tasks → Rewards
Customers approved as Deal Makers receive tasks assigned by admins. Completed tasks generate reward records linked to the Deal Maker's customer profile.

### Home Feed → Advertisements, Flash Discounts, Merchants, Coupons, Tags
The customer home screen is assembled from five independent modules. Active Advertisements come from the Advertisement Management module, live Flash Discounts from the Flash Discount module, featured merchants (ranked by active coupon count) from Merchant Management, top coupons (sorted by save count) from Coupon Management, and featured tags from the master data tag registry. All five modules contribute distinct data to a single home-feed API response.

### Contact Enquiries → Lead Management
Business sign-up enquiries submitted through the Contact Enquiries module are the entry point for the merchant acquisition pipeline. Sales admins can use enquiry details to follow up and create merchant acquisition leads in the Lead Management module.

### Gift Coupons → Coupon Management → Customer Wallet
The Gift Coupon module depends on the Coupon Management module for the source coupon. When a gift is accepted by the customer, the gifted coupon enters the customer's wallet alongside saved and allotted coupons, making it redeemable through the same redemption flow.

### Search → Merchants, Stores, Coupons, Blog
The Search module performs cross-entity queries simultaneously against Merchant Management, Store Management, Coupon Management, and Blog & CMS Content, consolidating results into a unified response for the Customer Application.

### Coupon Approval → Notification System
When an admin approves or rejects a merchant's coupon, the Notification System receives a trigger to deliver an in-app notification to the merchant, informing them of the decision.

### Loyalty Card Activation → Notification System
When a customer requests a loyalty card activation code, the Notification System sends a notification to the card issuer (merchant or admin) carrying the generated auth code for relay to the customer.

### Grievance Update → Notification System
When a new message is added to a grievance thread (by either a merchant or a customer), the Notification System can trigger an alert to the other party, prompting them to review the update.

---

## 6. System Flows

### 6.1 Customer Registration and Onboarding Flow

1. New visitor opens the Customer Application and navigates to the registration page.
2. Customer submits name, email or phone, and a password.
3. The Backend API creates a user record and sends an OTP to the provided contact.
4. Customer enters the OTP on the verification screen, confirming their identity.
5. Upon successful verification, the customer is given a JWT and logged in.
6. First-time customers are directed to the onboarding screen to complete their profile (profession, date of birth, city).
7. On completion, the customer gains full access to the authenticated Customer Application.

---

### 6.2 Merchant Registration and Approval Flow

1. A business owner visits the "Business Signup" page in the Customer Application and submits an interest form.
2. A leads record is created in the Admin Application for the sales team to follow up.
3. A Sales Admin or Super Admin creates the merchant account through the Admin Application.
4. The merchant receives login credentials.
5. The Admin sets the merchant's profile status to "pending" by default.
6. Once the merchant's business profile is verified, an Admin approve the profile.
7. With "approved" status, the merchant's stores and coupons become visible to customers.
8. The merchant's subscription plan determines which features are available.

---

### 6.3 Coupon Creation and Publication Flow

1. Merchant logs in to the Merchant Application and navigates to Coupons → New.
2. Merchant fills in the coupon form: title, discount type (percentage/fixed/BOGO/add-on), value, validity dates, usage limit, and optional banner image.
3. Merchant submits the form; the API creates a coupon with `approval_status = pending`.
4. The coupon appears in the Admin Application's pending coupons list.
5. An Admin reviews and approves the coupon (or rejects it with a reason).
6. Upon approval, the coupon becomes publicly visible to customers and appears in the home feed and search results.
7. Admin-created coupons skip the approval step and are immediately active.

---

### 6.4 Customer Coupon Discovery and Save Flow

1. Customer browses the Platform (home screen, search, merchant detail, or category explore page).
2. Customer views a coupon's detail page showing offer details, validity, and terms.
3. If not logged in, the coupon code is hidden. Once logged in, the code is revealed.
4. Customer taps "Save" to add the coupon to their wallet.
5. The API creates a `coupon_subscriptions` record linking the customer to the coupon.
6. The coupon appears in the customer's wallet under "Saved" coupons.

---

### 6.5 Coupon Redemption Flow (QR Code Method)

1. Customer visits a merchant's store and opens the Customer Application.
2. Customer navigates to their Wallet and selects the coupon.
3. The coupon detail shows the coupon code or a scannable QR representation.
4. The merchant opens the Merchant Application and navigates to the Scan Redemption screen.
5. Merchant scans the customer's QR code.
6. The API validates the coupon: checks it belongs to the customer's saved wallet, is not expired, is not already redeemed, and has not exceeded its usage limit.
7. On validation success, a `coupon_redemptions` record is created with the merchant, store, customer, discount amount, and timestamp.
8. The coupon is marked as used in the customer's wallet.
9. The redemption data immediately feeds the merchant's analytics dashboard.

---

### 6.6 Store Coupon Gift and Redemption Flow

1. Merchant creates a store coupon in the Merchant Application (tied to a specific store, with code, discount type, validity, and usage limit).
2. Merchant navigates to the Assign page for the coupon.
3. Merchant looks up the customer (by name, phone, or ID) and assigns the coupon.
4. The API creates a `store_coupon` record with the customer linked as the recipient.
5. The coupon appears in the customer's wallet.
6. When the customer visits the store, the merchant redeems the coupon through the Merchant Application by entering the customer identifier.
7. The redemption is recorded and the coupon is marked used.

---

### 6.7 Merchant Analytics Flow

1. Every coupon redemption record is immediately written to the `coupon_redemptions` table with a timestamp.
2. When a merchant opens the analytics section in the Merchant Application, the API aggregates redemption data.
3. The dashboard returns KPI counts, a 7-day chart, top coupon rankings, and revenue estimates.
4. Merchants can drill into the redemptions list with date-range filtering.
5. Sales registry entries also contribute to the revenue summary.

---

### 6.8 Grievance Submission and Resolution Flow

1. Customer identifies a problem with a merchant interaction and submits a grievance through the Customer Application.
2. The grievance is created in the database linked to the customer and merchant.
3. The grievance appears in the Merchant Application's grievances list and in the Admin Application.
4. The merchant views the grievance detail and adds a response message to the thread.
5. The customer sees the merchant's response in their grievance thread.
6. Further messages can be exchanged until the issue is resolved.
7. When resolved, the merchant marks the grievance as resolved.
8. The pending grievance count in the merchant's analytics dashboard decreases.

---

### 6.9 Loyalty Card Activation Flow

1. Admin creates a loyalty card record in the Admin Application and assigns it to a merchant or admin issuer.
2. A pre-printed physical card is issued to the customer by the merchant.
3. Customer opens the Customer Application and navigates to their card section.
4. Customer enters the card number and submits a request for an activation code.
5. The API generates a 6-digit time-limited (30-minute) auth code and stores it.
6. A notification is sent to the card issuer (merchant or admin) carrying the auth code.
7. The merchant communicates the code to the customer in person.
8. Customer enters the auth code in the Customer Application.
9. The API validates the code against the stored record (checking expiry and usage status).
10. On success, the card is activated and linked to the customer's account.
11. The activated card is visible in the customer's profile.

---

### 6.10 Referral Program Flow

1. Each customer account is automatically assigned a unique referral code.
2. Customer shares their referral code with friends (via link or manual sharing).
3. New user registers using the referral code during sign-up.
4. A referral record is created linking the referrer (existing customer) to the referee (new customer).
5. The referral status begins as "pending" and progresses to "completed" when the referee meets the completion criteria.
6. When the referral is rewarded, the reward amount is recorded in the referral record.
7. The referring customer can view their referral statistics and reward history in the Referral page.

---

### 6.11 Contest Participation Flow

1. Admin creates a contest in the Admin Application with dates, description, and prize structure.
2. Active contests appear in the Customer Application's contests section (publicly visible without login).
3. Authenticated customer views the contest detail and taps "Participate".
4. The API records the customer's entry in `contest_participants`.
5. After the contest ends, admin records winners with position and prize details.
6. Customers can view their contest entries and whether they have won.

---

### 6.12 Survey Response Flow

1. Admin creates a survey in the Admin Application with a question set in JSON format and an active period.
2. Active surveys appear in the Customer Application for authenticated customers.
3. Customer opens the survey detail page to see the questions.
4. Customer completes the questions and submits responses.
5. The API stores the response JSON in `survey_responses` linked to the customer and survey.
6. The customer's survey is marked as completed; they cannot re-submit the same survey.
7. Completed surveys are listed in the customer's survey history.

---

### 6.13 Deal Maker Application Flow

1. Customer opens the Deal Maker section in the Customer Application and submits an application with a motivation statement.
2. The API creates a `dealmaker_applications` record with status "pending".
3. Admin reviews the application in the Admin Application (DealMakers module).
4. Admin approves or rejects the application.
5. Upon approval, the customer's `is_dealmaker` flag is set to true.
6. Admin assigns tasks to the approved Deal Maker.
7. Deal Maker views tasks in the Customer Application and updates task progress.
8. Upon task completion, admin verifies and marks the reward as paid.
9. Deal Maker tracks earnings in the Customer Application.

---

### 6.14 Manual Coupon Redemption Flow

1. Customer visits a merchant's store without presenting a QR code.
2. Customer provides their name or phone number to the merchant at the point of sale.
3. Merchant opens the Merchant Application and navigates to the Redemption Lookup section.
4. Merchant searches for the customer by name, phone, or customer ID.
5. The system returns the list of active coupons the customer has saved that are valid for this merchant.
6. Merchant selects the applicable coupon and confirms the redemption.
7. The API validates the coupon against the same rules as a QR scan: expiry, usage limits, and merchant eligibility.
8. A `coupon_redemptions` record is created and the coupon is marked as used in the customer's wallet.

---

### 6.15 Admin Gift Coupon Delivery Flow

1. Admin opens the Gift Coupons module in the Admin Application.
2. Admin selects an active platform coupon and chooses a target customer.
3. Admin optionally sets a gift expiry date and configures whether customer acceptance is required.
4. Admin saves the gift; a `gift_coupons` record is created.
5. The gifted coupon appears in the customer's gift coupons inbox in the Customer Application.
6. If acceptance is required, the customer reviews the offer and accepts or rejects it.
7. On acceptance, the coupon is added to the customer's wallet and becomes redeemable.
8. On rejection or expiry, the gift record is marked accordingly.

---

### 6.16 Contact and Business Enquiry Form Flow

1. A visitor or customer fills in the Contact form or the Business Sign-Up form in the Customer Application.
2. On submission, the Backend API stores the enquiry in the `contact_enquiries` table, flagging the enquiry type (general contact or business sign-up).
3. The enquiry appears in the Admin Application's Contact Enquiries module.
4. An admin opens the enquiry detail, which automatically marks it as read.
5. For business sign-up enquiries, a Sales Admin follows up with the prospective merchant, creates a lead in the Lead Management module, and eventually onboards them as a merchant through Merchant Management.

---

### 6.17 Flash Discount Creation and Customer Discovery Flow

1. Merchant logs in to the Merchant Application and opens the Flash Discounts section.
2. Merchant creates a flash discount with a percentage value, start and end datetime, optional maximum redemptions cap, and an optional store link.
3. If the start datetime has already passed, the flash discount is immediately live.
4. The Backend API's public home endpoint evaluates the current datetime against all flash discount records and returns only those with an active window.
5. Active flash discounts appear prominently in the Flash Deals section on the customer home screen and on the dedicated Flash Deals listing page.
6. Once the `valid_until` timestamp is reached, the flash discount no longer appears in any public listing without requiring manual action.

---

### 6.18 Coupon Bulk Allotment Flow

1. An Admin creates a bulk allotment for an existing coupon in the Admin Application.
2. Admin selects the target coupon and supplies the list of customer recipients.
3. The system creates a `coupon_bulk_allotments` parent record and an individual `coupon_allotment_items` record for each recipient.
4. Each allotment item links the coupon to a specific customer, equivalent to a directed wallet save.
5. The allotted coupons appear in each respective customer's wallet without requiring the customer to discover or save the coupon manually.
6. Customers redeem allotted coupons through the normal wallet redemption flow.

---

### 6.19 Mystery Shopping Task Assignment and Completion Flow

1. A Promoter Admin or Super Admin opens the Mystery Shopping module in the Admin Application.
2. Admin selects a customer-shopper and identifies a target merchant (and optionally a specific store) to be evaluated.
3. Admin creates the task with a description, evaluation checklist (stored as JSON), and optional payment amount.
4. The assigned customer-shopper sees the task in their Deal Maker section in the Customer Application.
5. Customer visits the merchant's store undercover and performs the assigned evaluation.
6. Customer marks the task as completed in the Customer Application.
7. Admin reviews the submission, marks the task as verified, and records the payment status once the reward has been delivered.

---

### 6.20 Admin Notification Broadcast Flow

1. A Super Admin or City Admin opens the Notifications module in the Admin Application.
2. Admin selects a target audience: all customers, all merchants, all admins, or a specific individual user.
3. Admin composes the notification with a title, message body, and notification type.
4. On submission, the Admin Application bulk-inserts a notification record for each matching recipient into the `notifications` table.
5. The Backend API immediately makes the new notifications available when any targeted customer or merchant queries their notifications feed.
6. The notification appears as unread in the recipient's notification list, with visual styling matched to its type (info, promotion, contest, etc.).

---

## 7. Entity Relationships

### 7.1 Core Entities

**User**
The foundational identity record for all platform participants. A user account stores login credentials (email, phone, password) and a user type (customer, merchant). Both customer profiles and merchant profiles are linked to a user record through a one-to-one relationship. Admin accounts have a separate `admins` table but are associated with a user.

**Merchant**
Represents a registered business. Every merchant links to one user account. A merchant can have multiple stores. Merchants have a subscription plan and status that determine their operational scope and feature access. Merchants can be associated with labels, tags, and a priority weight affecting their search position.

**Customer**
Represents a registered consumer. Each customer links to one user account. Customers have a type classification (standard, premium, deal maker), subscription status, referral code, city assignment, and optional loyalty card linkage. Customers can have multiple saved coupons, redemption records, reviews, grievances, referrals, contest entries, and survey responses.

**Store**
A physical location owned by a Merchant. Stores belong to one merchant, are associated with a city and optionally an area, and carry operating hours, contact information, and gallery images. Store-category assignments are managed through a `store_categories` pivot table.

**Coupon**
A promotional offer created by a merchant or an admin. Coupons link to a merchant and optionally a store. They have a discount type, value, validity window, usage limit, approval status, and banner image. Coupons are linked to categories through `coupon_categories`, to cities through `coupon_city_targets`, and to tags through `coupon_tags`. Customer saves are recorded in `coupon_subscriptions` and redemptions in `coupon_redemptions`.

**Store Coupon**
A merchant-issued coupon assigned directly to a specific customer. Unlike general coupons, store coupons are targeted and gifted rather than publicly browsable. They are linked to a store, a merchant, the recipient customer, and track assignment and redemption state.

**Flash Discount**
A time-sensitive, store-level percentage discount offer. Flash discounts auto-expire once their end datetime passes. They are independently browsable on the customer home screen.

---

### 7.2 Engagement Entities

**Review** — Links a customer to a merchant. Contains rating, text, and merchant reply. Auto-approved on submission.

**Grievance** — Links a customer to a merchant, optionally referencing a coupon. Has an open/resolved status. The `grievance_messages` table stores the threaded correspondence.

**Message** — Powers in-app direct messaging between merchants and customers, and the admin notification system.

**Notification** — A targeted in-app alert delivered to a user (customer, merchant, or admin) with a type classification for display styling.

**Contest / Contest Participants / Contest Winners** — Manages platform competitions and their entries and outcomes.

**Survey / Survey Responses** — Manages question sets and their collected responses per customer.

**Referral** — Records peer-to-peer customer acquisition events with reward tracking.

---

### 7.3 Loyalty Entities

**Card** — A loyalty card record with card number, variant, status (unassigned, activated, blocked), preprinted flag, and customer assignment.

**Card Auth Codes** — Time-limited 6-digit codes generated for pre-printed card activation. Each code is consumed on successful use.

---

### 7.4 Program Entities

**Dealmaker Application** — Records a customer's application to join the Deal Maker program with motivation, status, and review trail.

**Dealmaker Tasks** — Individual tasks assigned to approved Deal Makers, with description, checklist, progress status, and reward tracking.

**Mystery Shopping Tasks** — Admin-assigned evaluation tasks given to selected customer-shoppers for merchant quality assessment.

---

### 7.5 Commercial Entities

**Coupon Redemption** — The transaction record created when a customer redeems a coupon. Links coupon, customer, store, merchant, discount amount applied, and transaction value. The primary data source for all analytics.

**Sales Registry** — Manual transaction entries logged by merchants to record sales not tied to a specific coupon redemption.

**Coupon Bulk Allotment / Allotment Items** — Supports pre-assignment of a coupon to multiple recipients simultaneously. The allotment groups the batch and allotment items record individual assignments.

**Gift Coupon** — Records a coupon gifted from one customer to another, with acceptance status.

**Subscription** — Records subscription plans and periods for both merchant and customer accounts.

---

### 7.6 Administrative Entities

**Audit Log** — Immutable record of every significant admin action with actor, action type, affected entity ID and table, timestamp, and IP address.

**Lead / Merchant Leads** — Merchant acquisition pipeline records.

**Advertisement** — Promotional banner configured by admins, with scheduling, media type, and link destination.

**Blog Post** — Editorial content published by admins with slug-based public URLs.

**CMS Page** — Static informational content managed by admins with slug routing.

**Platform Settings** — Key-value configuration store for system-wide behaviour settings.

---

### 7.7 Structural Entities

**City** — A top-level geographic unit. Stores and customers are associated with cities.

**Area** — A sub-unit within a city. Stores are associated with areas for more precise location data.

**Location** — An optional finer location reference available for stores.

**Category / Sub-Category** — Hierarchical classification system for stores and coupons.

**Tag** — A free-form label associated with merchants and coupons for flexible discovery.

**Label** — Admin-defined merchant classification labels for internal organisation and display.

**Profession / Job Title / Day Type** — Reference data tables for customer profile enrichment and important day tracking.

---

## 8. Master Data

Master or reference data is centrally managed by the Admin Application through the Master Data management module and is consumed across all applications.

| Master Data | Managed In | Consumed By |
|---|---|---|
| Cities | Admin Application | Store creation, customer profiles, API city filtering, public endpoints |
| Areas | Admin Application | Store creation, customer profiles, location display |
| Locations | Admin Application | Store creation for precise address |
| Categories | Admin Application | Store classification, coupon targeting, category browsing |
| Sub-categories | Admin Application | Store classification under parent category |
| Tags | Admin Application | Merchant tagging for featured home feed, coupon tagging for discovery |
| Labels | Admin Application | Merchant classification for internal admin organisation |
| Day Types | Admin Application | Customer important day tracker |
| Professions | Admin Application | Customer profile enrichment |
| Job Titles | Admin Application | Customer profile enrichment |
| Platform Settings | Admin Application | System-wide configuration consumed by API and admin functions |

Master data is read by the Backend API via public endpoints (cities, areas, categories, sub-categories) and is embedded directly into Admin Application views for form selectors. The Merchant Application fetches cities, areas, and categories from the public API when creating or editing stores.

---

## 9. Cross-Application Interactions

### Customer Application ↔ Backend API

Every interaction from the Customer Application travels as an HTTP request to the Backend API. The application sends and receives JSON. Authentication is maintained through JWT access tokens stored client-side, with refresh tokens allowing session continuity. The Customer Application has no direct knowledge of the Admin or Merchant applications.

- Browsing operations call public endpoints (no auth required).
- Saving coupons, submitting reviews, entering contests, and other personal actions call authenticated customer endpoints.
- The API returns location-aware content when a city ID is provided in public requests.

### Merchant Application ↔ Backend API

Marketing management, store configuration, coupon publishing, redemption scanning, analytics retrieval, customer management, and all other merchant actions are processed through the Backend API. The merchant JWT carries the subscription plan and store access scope, which the API uses for every request to enforce feature gates and store-level restrictions.

### Admin Application ↔ Database (Direct)

The Admin Application operates through server-side rendered PHP controllers that interact with the database directly. It does not call the Backend API. Admin actions (approvals, coupon creation, user management, configuration) are written directly to the database and immediately affect what the API serves to Customer and Merchant applications.

### Admin Application → Merchant Application (Indirect)

When an admin approves a merchant profile, the merchant's `profile_status` is changed in the database. The next time a merchant logs in through the Merchant Application or the Backend API checks the merchant's profile, the change is reflected. Similarly, coupon approvals, subscription changes, and notification broadcasts all flow from Admin to end-user applications via the shared database.

### Merchant Application → Customer Application (Indirect via API)

Merchants publish content (coupons, stores, flash discounts) through the Merchant Application via the Backend API. This content is then served by the same Backend API to customers browsing through the Customer Application. The two front-end applications never communicate directly — all interaction is mediated through the Backend API and the underlying database.

### Notifications Flow (Admin → All)

When an Admin broadcasts a notification, the Admin Application bulk-inserts notification records into the `notifications` table for each target recipient. The Backend API surfaces unread notifications to customers and merchants when they next check their notifications feed.

---

## 10. Current Implementation Scope

The following functional capabilities are confirmed as fully implemented in the current codebase:

### Authentication
- Customer registration with OTP verification ✓
- Customer login with JWT issuance ✓
- Customer password reset via token ✓
- Merchant login with JWT (including subscription plan and store scope in payload) ✓
- Merchant password reset ✓
- JWT refresh token management ✓
- Admin session-based authentication ✓
- Mandatory password reset on first login for merchant/admin-created customers ✓

### Platform Discovery
- Public home feed (advertisements, flash discounts, featured merchants, top coupons, tags) ✓
- Public store browsing with city filter ✓
- Public merchant browsing with reviews and gallery ✓
- Public coupon browsing ✓
- Public flash discount browsing ✓
- Unified search across merchants, stores, coupons, and blog ✓
- Category and sub-category public endpoints ✓

### Store Management
- Store CRUD in Merchant Application with city/area/category assignment ✓
- Store gallery management (upload, delete, cover image, reorder) ✓
- Store categories stored in pivot table (store_categories) ✓
- Store CRUD in Admin Application as part of merchant profile ✓

### Coupon Management
- All four discount types: percentage, fixed, BOGO, add-on ✓
- Coupon approval workflow (merchant creates → admin approves) ✓
- Coupon category and city targeting in Admin Application ✓
- Customer coupon wallet with save and remove ✓
- QR code scan redemption from Merchant Application ✓
- Manual coupon code redemption ✓
- Coupon redemption history for customers ✓
- Coupon image (banner) upload and management ✓

### Store Coupons
- Store coupon CRUD ✓
- Individual customer assignment (gift) ✓
- Bulk assignment to multiple customers ✓
- Redemption at store ✓

### Flash Discounts
- Merchant creation and management ✓
- Auto-expiry on past valid_until datetime ✓
- Public browsing endpoint ✓

### Analytics
- Merchant analytics dashboard (KPIs + weekly chart) ✓
- Redemptions list with filter ✓
- Customer analytics ✓
- Top coupons ranking ✓
- Revenue summary ✓
- Admin platform-wide reports (customers, merchants, redemptions) ✓

### Reviews
- Customer review submission (auto-approved) ✓
- Merchant review listing and reply ✓
- Admin review management ✓

### Grievances
- Customer grievance submission ✓
- Threaded messaging on grievances ✓
- Merchant resolution ✓
- Admin oversight ✓

### Loyalty Cards
- Card generation and management in Admin Application ✓
- Pre-printed card two-step activation with auth codes ✓
- Customer card view ✓

### Referral Program
- Unique referral code per customer ✓
- Referral tracking with status and rewards ✓
- Customer referral statistics and history ✓

### Contests
- Admin contest creation and management ✓
- Public contest listing ✓
- Customer participation and entry recording ✓
- Winner recording ✓

### Surveys
- Admin survey creation with JSON question sets ✓
- Customer survey listing and submission ✓
- Submission history per customer ✓

### Deal Maker Program
- Customer application submission and tracking ✓
- Admin approval workflow ✓
- Task assignment and progress tracking ✓
- Earnings and reward record ✓

### Mystery Shopping
- Admin task assignment to shoppers ✓
- Task lifecycle management with checklists ✓
- Payment tracking ✓

### Notifications
- In-app notifications for all user types ✓
- Admin broadcast to customers, merchants, or admins ✓
- Mark as read (individual and all) ✓

### Messaging
- Merchant-to-customer message threads ✓
- Unread tracking ✓

### Subscription Management
- Plan assignment and status management in Admin Application ✓
- Feature Gate enforcement in API based on subscription plan ✓
- Merchant subscription renewal request through Merchant Application ✓

### Blog & CMS
- Blog post management in Admin Application ✓
- Public blog listing and detail pages ✓
- CMS pages with slug routing ✓

### Advertisements
- Ad management in Admin Application ✓
- Active ad serving in public home feed ✓

### Important Days Tracker
- Customer important day creation and management ✓
- Day types as configurable master data ✓

### Audit & Security
- Audit log capture for all significant admin actions ✓
- Audit log viewer with filtering for super admins ✓
- CSRF protection on all admin form submissions ✓
- Role-based access control with admin type enforcement ✓
- City-scoped access for city admins ✓
- Store-scoped JWT claims for merchant store users ✓

### Contact Enquiries
- Public contact form submission endpoint ✓
- Business sign-up interest form submission endpoint ✓
- Admin enquiry list with status filtering ✓
- Auto-mark-as-read on detail view ✓
- Status counts for unread tracking ✓

### Gift Coupons
- Admin gift coupon creation with optional acceptance requirement ✓
- Customer gift inbox (view received gifts) ✓
- Customer accept and reject endpoints ✓
- Gift coupon admin list with filters and stats ✓

### Master Data Management
- Cities, areas, locations management ✓
- Categories and sub-categories management ✓
- Tags, labels, professions, job titles, day types management ✓
- Platform settings management ✓

---

*End of Functional Documentation Report*
