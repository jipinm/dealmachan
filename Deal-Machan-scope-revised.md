# Deal Machan — Application Change Specification

---

## Global Notes

- A **Merchant** is an admin to all stores under their account.
- All data (coupons, flash discounts, store coupons, etc.) is associated with **Stores**, not directly with Merchants. Merchants are connected to this data only through their stores.
- **City** is the primary/base filter for all location-connected data.
- **No delete option** should exist anywhere in the system.

---

## 1. Customers

### 1.1 List & Filters
- Add a **City-based filter** to the customer list.
- Add filters for **Gender** and **Profession**.

### 1.2 Add Customer
- No password is required when adding a customer from the admin panel.

### 1.3 Customer Login Flow
1. Customer enters their **Mobile Number** or **Email ID**.
2. **If already registered:**
   - If a password exists → prompt for **Password**.
   - If no password exists → send **OTP** to Mobile/Email → validate OTP → prompt customer to **set a new password** → prompt for **City selection** (mandatory).
3. **Card Assignment during registration:**
   - **Option A – Input Card Number:** Customer enters a pre-printed card number. Validate the card (must be valid and issued by a Merchant or Admin). If valid, issue the card to this customer.
   - **Option B – Select from List:** An automated card number is generated and issued to the customer.

### 1.4 Removals & Other Changes
- Completely remove **"Account Settings"** and **Customer Type** from all logic and UI.
- Customer **login enable/disable** must be managed from the Customer List.
- Retain: Referral tracking, customer name, and city fields.

---

## 2. Merchants

### 2.1 Separation of Merchants & Stores
- **Merchants** and **Stores** must be managed as separate entities.
- Primary filters for the Merchant list: **City** and **Merchant Name**.

### 2.2 Add / Edit Merchant
- **Subscription Expiry Options** (set at time of adding; renewal resets to the newly selected period):
  - 1 Month
  - 3 Months
  - 6 Months
  - 1 Year *(Default)*
- **Labels:** Remove the hardcoded "Premium" and "Verified Partner" labels. Replace with separate **toggle options** for each (Premium, Verified Partner).
- **Bulk Store Coupon Limits** (configured at the Merchant level and applies to all stores under that merchant):
  - Maximum number of store coupons assignable at one time (*Coupon Limit*).
  - Maximum number of store coupon assignments allowed per calendar month (*Monthly Assignment Limit*).

### 2.3 Add / Edit Store
- Include **Latitude & Longitude** fields.
- Below the Address field, add **Email** and **Website Link** fields (displayed on the Store Profile).

### 2.4 Store Profile — Action Buttons
Two action buttons must appear on the Store Profile:

1. **Call** — Triggers a call to the store's registered number. This action must be captured/logged.
2. **Book Now** — Presents three inputs:
   - Date
   - Time
   - Number of Attendees

   **Confirmation Logic** (configured per store by the Store Admin):
   - **Confirmation Required:** Booking request is sent to the store. The store can **Accept**, **Deny**, or **send a message** to the customer.
   - **Confirmation Not Required:** Booking is automatically approved, and a confirmation message is sent to both the customer and the store.

---

## 3. Cards

### 3.1 Guest Access (Without a Valid Card)
Customers without a valid card can only access:
- Rating & Review
- Grievance / Complaint Registration
- Store Profile Visit
- Call Now
- Book Now
- All public pages

### 3.2 Card Configuration — Updates

#### Sub-Classifications
- **Gender:** Split into two distinct options — **Male** and **Female**.
- **Profession:** If *Profession* is selected as the card type, load the Profession list from Master Data. The selected profession is saved to the database for future use.

#### New Configuration Fields
| Field | Type | Description |
|---|---|---|
| **Pay Back Points** | Toggle (Enable/Disable) | If enabled, an additional numeric input appears. This value determines the points awarded per every ₹100 spent or per transaction. Points are displayed in the customer's Wallet. |
| **Lifetime Subscription** | Toggle | If enabled, the card validity is overridden — no renewal required. |
| **Gift Coupon Eligibility** | Toggle (Yes/No) | Only customers holding a card with this enabled are eligible to receive gifted coupons. |
| **Lucky Draw** | Toggle (Yes/No) | Only customers holding a card with this enabled are eligible for lucky draws. |
| **Contest** | Toggle (Yes/No) | Only customers holding a card with this enabled are eligible for contests. |

### 3.3 Generate Cards
- **Parameters (JSON):** Remove any parameters not directly connected to business logic.

### 3.4 Assign Cards
- Pre-printed cards (from a bulk-generated group) can be assigned to either a **Store** or an **Admin**.
- Assignment flow:
  1. Select Card Configuration
  2. Select Generated Card Group (bulk creation batch)
  3. Select one or more cards
  4. Assign to either:
     - **An Admin** (select one Admin), or
     - **A Store** (select one Store)

---

## 4. Store Coupons

### 4.1 List & Filters
- The "View All" list must be filterable by **Merchant → Store**.
- Store coupons are associated with individual stores, not with the merchant account directly.
- Remove all delete options.

### 4.2 Creating Store Coupons (Admin Only)
- There is **no option** for a store to add its own coupon.
- Only an **Admin** can create a store coupon by selecting a specific **Merchant → Store**.
- A store coupon uses the **Store's logo** as its image (no separate image upload needed).
- Required fields: **Title**, **Description**, **Code** (auto-generated), **Terms & Conditions**, **Validity/Usage**, **Discount Settings**.
- **No location settings** are needed — store coupons are scoped to the store and displayed only on that store's profile.
- Admin can configure whether **customer acceptance** is required. If required, the coupon appears as **"Grab Now"** for the customer.

### 4.3 Assigning Store Coupons
**Assigned by:** Store Admin

**Assignment Types:**

| Type | Description |
|---|---|
| **Single** | Immediately gift a store coupon to one customer after a transaction (e.g., after redeeming a store coupon, general coupon, or flash discount). |
| **Bulk** | Assign a store coupon to multiple customers at once. |

**Eligible customers for bulk assignment:**
- Customers added by the store.
- Customers who have completed a transaction with that store.
- Customers who have wishlisted the store.

### 4.4 Bulk Assignment Limits (Set at Merchant Level)
- **Coupon Limit:** Maximum number of coupons assignable in one bulk operation.
- **Monthly Assignment Limit:** Maximum number of assignments allowed from the start to the end of a calendar month.

> **If either limit is exceeded:**
> - All store coupons under that merchant go into an **Admin Approval** queue.
> - Coupons are issued to customers only after admin approval.
> - The store is notified that the assignment is **Pending Approval**.

### 4.5 Store Dashboard
The store dashboard must display:
- Current coupon assignment limit.
- Monthly assignment limit.
- Exceeded limit indicators.

---

## 5. Coupons

### 5.1 List & Filters
- Add filtering options: **Merchant → Store → Coupons**.
- Display a **separate count** for:
  - Total coupons per Merchant.
  - Coupons per individual Store.

### 5.2 Add Coupon
- All coupon codes must be **auto-generated**.
- No edit option is needed; only validate that the code doesn't already exist.
- Add an optional **Note** field (text area). The note is not mandatory but, if filled, must be displayed alongside the coupon details in the admin panel.
- Add the following fields and validate them at redemption:
  - **Usage Limit per User**
  - **Total Number of Available Coupons**

---

## 6. Gift Coupons

### 6.1 Add Gift Coupon
- Gift coupon creation flow: **Merchant → Store**.
- Only **existing, standard coupons** (not store coupons) can be gifted, and only by the Store Admin.

### 6.2 Recipient Selection
- Support **multiple customer selection** using a table-style list with the following filter options:
  - Privilege Card Segment
  - Club
  - Profession
  - Month (based on entries in the customer's "Important Days")
  - City
  - Area / Location
  - Gender

- After selecting recipients and coupon → **Gift**.
