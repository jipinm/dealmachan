# Merchant Application — Implementation Plan

**Date:** March 14, 2026  
**Scope:** Gap analysis and task-by-task implementation guide for the Merchant PWA (`merchant/src/`)  
**Reference apps:** Admin PHP app (`admin/`) · Database schema (`dealmachan.sql`)

---

## Analysis Summary

### What Is Fully Implemented ✅
| Area | Status |
|---|---|
| Auth (login / logout / forgot-password / reset-password / token-refresh) | ✅ |
| Home dashboard — KPIs, weekly redemption chart, store-scoped for store admin | ✅ |
| Stores — CRUD, gallery (upload / delete / reorder / set-cover) | ✅ |
| Store list — auto-redirects store admin to their assigned store | ✅ |
| Coupons — full CRUD, all 4 discount types (%, fixed, BOGO, add-on), banner image | ✅ |
| Scan & Redeem — camera QR, manual code entry, customer lookup with redeemable items | ✅ |
| Analytics — redemption chart, customer breakdown (new vs returning), top coupons | ✅ |
| Sales Registry — paginated list, record sale, CSV export | ✅ |
| Grievances — list, view, respond, resolve | ✅ |
| Reviews — list, view, reply to customer | ✅ |
| Messages — inbox, thread view, send message, mark read | ✅ |
| Notifications — infinite scroll, mark read, mark all read | ✅ |
| Profile — view, edit (business info + categories), logo upload | ✅ |
| Change Password | ✅ |
| Subscription — view current plan, request renewal/upgrade | ✅ |
| Labels — view assigned/available, request label | ✅ |
| Flash Discounts — CRUD, redeem; store-admin scoped (store locked) | ✅ |
| Customers — list, add, edit, detail with analytics | ✅ |
| Store Coupons — CRUD, assign to customer, redeem | ✅ |
| Image utility — `getImageUrl()` with `VITE_API_ORIGIN` | ✅ |
| RBAC — `isStoreAdmin()`, `scopedStoreId()`, `StoreAdminGuard` on routes | ✅ |

### Gaps Found

| # | Gap | Severity |
|---|---|---|
| G1 | Store Admin Management — merchant admin cannot create/manage store admins | 🔴 Critical |
| G2 | Merchant Banner Image — DB column exists, no upload UI or display | 🟡 Medium |
| G3 | Revenue Analytics tab — API call defined but never rendered | 🟡 Medium |
| G4 | Coupon form — store_id not locked for store admin | 🟠 High |
| G5 | Sales Registry — not scoped for store admin | 🟠 High |
| G6 | Coupon banner image unavailable on create (only edit) | 🟢 Low (UX) |

---

## Implementation Tasks

Tasks are grouped by feature. Each task lists the exact files to change and the required change.

---

## Group 1 — Store Admin Management (G1) 🔴

**Context:**  
The `merchant_store_users` DB table and the admin-side creation flow are fully implemented. Merchant admins need a self-service UI to list, create, and deactivate store admins for their own stores. A store admin has `access_scope = 'store'` and is bound to a single `store_id`.

**This entire group is merchant-admin-only. Store admins cannot see or access these pages.**

---

### Task 1.1 — Create `src/api/endpoints/storeAdmins.ts`

Create a new API module for store admin management.

**File:** `merchant/src/api/endpoints/storeAdmins.ts` *(new file)*

```typescript
import { apiClient } from '@/api/client'

export interface StoreAdmin {
  id: number           // merchant_store_users.id
  user_id: number
  store_id: number
  store_name: string | null
  name: string
  email: string
  phone: string | null
  access_scope: 'store'
  status: 'active' | 'inactive'
  created_at: string
  last_login: string | null
}

export interface CreateStoreAdminPayload {
  name: string
  email: string
  phone?: string
  password: string
  store_id: number
}

export interface UpdateStoreAdminPayload {
  name?: string
  phone?: string
  status?: 'active' | 'inactive'
}

type R<T> = { success: boolean; message: string; data: T }

export const storeAdminApi = {
  list: () =>
    apiClient.get<R<StoreAdmin[]>>('/merchants/store-admins'),

  create: (data: CreateStoreAdminPayload) =>
    apiClient.post<R<StoreAdmin>>('/merchants/store-admins', data),

  update: (id: number, data: UpdateStoreAdminPayload) =>
    apiClient.put<R<StoreAdmin>>(`/merchants/store-admins/${id}`, data),

  deactivate: (id: number) =>
    apiClient.put<R<{ id: number }>>(`/merchants/store-admins/${id}/deactivate`, {}),

  resetPassword: (id: number, new_password: string) =>
    apiClient.put<R<{ id: number }>>(`/merchants/store-admins/${id}/reset-password`, { new_password }),
}
```

---

### Task 1.2 — Create `StoreAdminListPage.tsx`

**File:** `merchant/src/pages/storeAdmins/StoreAdminListPage.tsx` *(new file)*

**Behavior:**
- Fetch `/merchants/store-admins` on mount.
- Display each store admin in a card: name, email, phone, assigned store name, status badge (Active / Inactive), last login.
- FAB button (`+`) → navigate to `/store-admins/new`.
- Tap a row → navigate to `/store-admins/:id/edit`.
- Show an empty state with a prompt to add the first store admin if the list is empty.
- Use `getImageUrl()` for any avatar (no image for admins — use initials avatar).
- Page is **only reachable by merchant admins** (enforced via route guard — see Task 1.4).

---

### Task 1.3 — Create `StoreAdminFormPage.tsx`

**File:** `merchant/src/pages/storeAdmins/StoreAdminFormPage.tsx` *(new file)*

**Behavior (create mode — `/store-admins/new`):**
- Fields: Full Name (`name`), Email (`email`), Phone (`phone`, optional), Store (`store_id` — dropdown populated from `storeApi.list()`), Password (`password`).
- On submit: call `storeAdminApi.create()`, redirect to `/store-admins` on success with a success toast.
- Validate: name required (min 2 chars), email format, phone 10-digit optional, password min 8 chars, store required.

**Behavior (edit mode — `/store-admins/:id/edit`):**
- Pre-load the admin record via `storeAdminApi.list()` (find by id from params).
- Editable fields: Full Name, Phone, Status toggle (Active / Inactive).
- Email and Store are **read-only** in edit mode.
- "Reset Password" section: new password + confirm fields. On submit: call `storeAdminApi.resetPassword()`.
- "Deactivate" button (danger, confirm dialog): call `storeAdminApi.deactivate()`.

---

### Task 1.4 — Register routes in `router.tsx`

**File:** `merchant/src/router.tsx`

Add lazy imports at the top:
```typescript
const StoreAdminListPage = lazy(() => import('@/pages/storeAdmins/StoreAdminListPage'))
const StoreAdminFormPage = lazy(() => import('@/pages/storeAdmins/StoreAdminFormPage'))
```

Add routes inside the protected children array, wrapped with `StoreAdminGuard` to block store admins:
```typescript
// Store Admins (merchant admin only)
{ path: '/store-admins',      element: wrap(<StoreAdminGuard><StoreAdminListPage /></StoreAdminGuard>) },
{ path: '/store-admins/new',  element: wrap(<StoreAdminGuard><StoreAdminFormPage /></StoreAdminGuard>) },
{ path: '/store-admins/:id/edit', element: wrap(<StoreAdminGuard><StoreAdminFormPage /></StoreAdminGuard>) },
```

---

### Task 1.5 — Add "Store Admins" entry to `MorePage.tsx`

**File:** `merchant/src/pages/more/MorePage.tsx`

In the `SECTIONS` array, under the `'Business'` section, add a new menu item:

```typescript
{
  icon: UserCog,          // import UserCog from 'lucide-react'
  label: 'Store Admins',
  description: 'Manage staff logins for your stores',
  to: '/store-admins',
  accent: 'bg-cyan-600',
  merchantOnly: true,     // hidden for store-admin users (already filtered below)
},
```

The existing `MorePage` already filters `merchantOnly` items when `isStoreAdmin` is true — verify that filter logic is applied to this item in the render loop. If the `merchantOnly` filter is not yet applied during render, add it:

```typescript
// Inside MenuRow render, before mapping items:
.filter(item => !item.merchantOnly || !isStoreAdmin)
```

---

## Group 2 — Merchant Banner Image (G2) 🟡

**Context:**  
The `merchants` table has a `banner_image varchar(500)` column. The admin app stores and displays this image. The merchant app currently has no mechanism to upload or display it.

---

### Task 2.1 — Add `uploadBanner` to `merchant.ts`

**File:** `merchant/src/api/endpoints/merchant.ts`

Add the following method to the `merchantApi` object after `uploadLogo`:

```typescript
uploadBanner: (file: File) => {
  const form = new FormData()
  form.append('banner', file)
  return apiClient.post<{ success: boolean; data: { banner_image: string } }>(
    '/merchants/profile/banner',
    form,
    { headers: { 'Content-Type': 'multipart/form-data' } },
  )
},

deleteBanner: () =>
  apiClient.delete<{ success: boolean }>('/merchants/profile/banner'),
```

Also update the `MerchantProfileDetail` interface to include the banner:
```typescript
banner_image: string | null  // add this field
```

---

### Task 2.2 — Display banner in `ProfilePage.tsx`

**File:** `merchant/src/pages/profile/ProfilePage.tsx`

In the hero section at the top of the profile card, display the banner image if it exists. It should appear as a full-width image behind/above the logo+name section:

- If `profile.banner_image` is set: render `<img src={getImageUrl(profile.banner_image)} />` as a cover/hero strip at the top of the card (similar to a cover photo), height ~120px, `object-cover`.
- If not set: show the existing gradient header (current behavior — no change).
- Add a small camera icon overlay on the banner area (for merchant admins only, not store admins) that triggers a hidden file input → calls `merchantApi.uploadBanner()` on change.

---

### Task 2.3 — Add banner upload to `EditProfilePage.tsx`

**File:** `merchant/src/pages/profile/EditProfilePage.tsx`

At the top of the edit form (above business name field), add a "Cover / Banner Image" section:
- Show current banner (if present) via `getImageUrl()`, `max-h-32`, `w-full object-cover rounded-xl`.
- An upload button: triggers hidden `<input type="file" accept="image/jpeg,image/png,image/webp">`.
- On file selected: call `merchantApi.uploadBanner(file)` as a mutation, invalidate `['merchant-profile']`, show toast.
- A "Remove" button (shown only when banner exists): call `merchantApi.deleteBanner()`.

---

## Group 3 — Revenue Analytics Tab (G3) 🟡

**Context:**  
`analyticsApi.revenue()` is fully defined in `analytics.ts` (returns `RevenueAnalytics` with `total_sales`, `total_revenue`, `by_store[]`). It is never called or displayed in `AnalyticsPage.tsx`.

---

### Task 3.1 — Add Revenue section to `AnalyticsPage.tsx`

**File:** `merchant/src/pages/analytics/AnalyticsPage.tsx`

After the existing "Top Coupons" section, add a new "Revenue by Store" section:

1. Add a query:
```typescript
const { data: revenueData, isLoading: revLoading } = useQuery({
  queryKey: ['analytics-revenue'],
  queryFn:  () => analyticsApi.revenue().then(r => r.data.data ?? null),
})
```

2. Render a section card titled "Revenue by Store":
   - Summary row: Total Sales count + Total Revenue (₹ formatted).
   - A horizontal bar list for each store in `revenueData.by_store[]` showing store name, sale count, and revenue.
   - If only one store (or store admin), show the single row without a breakdown header.
   - If no data: show "No revenue data yet" empty state.
   - Update the combined `isLoading` check to include `revLoading`.

---

## Group 4 — Coupon Form: Store Admin RBAC Fix (G4) 🟠

**Context:**  
`CouponFormPage` shows a store dropdown for all users. A store admin should:
1. Have `store_id` automatically set to their scoped store.
2. Not be able to change the store (field should be locked/display-only).

---

### Task 4.1 — Scope coupon store for store admin in `CouponFormPage.tsx`

**File:** `merchant/src/pages/coupons/CouponFormPage.tsx`

**Changes:**

1. Import `useAuthStore` at the top:
```typescript
import { useAuthStore } from '@/store/authStore'
```

2. Inside the component, read scope:
```typescript
const isStoreAdmin  = useAuthStore(s => s.isStoreAdmin())
const scopedStoreId = useAuthStore(s => s.scopedStoreId())
```

3. In the `defaultValues` for `useForm`, set `store_id` to `scopedStoreId` when store admin:
```typescript
defaultValues: {
  discount_type: 'percentage',
  store_id: isStoreAdmin && scopedStoreId ? scopedStoreId : undefined,
}
```

4. In the `useEffect` that resets the form for edit mode, do not override `store_id` from API if `isStoreAdmin` (keep the local scoped value).

5. In the store selector JSX (the `<select name="store_id">` block):
   - When `isStoreAdmin && scopedStoreId`: replace the `<select>` with a read-only display field showing the scoped store name, and a hidden `<input type="hidden" value={scopedStoreId} {...register('store_id')} />`.
   - When not a store admin: keep the current `<select>` with all stores.

---

## Group 5 — Sales Registry: Store Admin Scoping (G5) 🟠

**Context:**  
`SalesPage` fetches all sales without any store filter. The `RecordSaleModal` shows all stores in the dropdown. Store admins should only see their assigned store's sales and cannot record sales for other stores.

---

### Task 5.1 — Scope sales list query for store admin

**File:** `merchant/src/pages/analytics/SalesPage.tsx`

1. Import `useAuthStore` and read scope at the top of `SalesPage`:
```typescript
const isStoreAdmin  = useAuthStore(s => s.isStoreAdmin())
const scopedStoreId = useAuthStore(s => s.scopedStoreId())
```

2. Pass `store_id` to the list and summary queries:
```typescript
queryFn: () => salesApi.list({
  page,
  limit: 20,
  ...(isStoreAdmin && scopedStoreId ? { store_id: scopedStoreId } : {}),
})

// Summary query similarly:
queryFn: () => salesApi.summary({
  ...(isStoreAdmin && scopedStoreId ? { store_id: scopedStoreId } : {}),
})
```

3. Verify `salesApi.list()` and `salesApi.summary()` in `analytics.ts` accept a `store_id` param. If not, add it:
```typescript
list: (params?: { page?: number; limit?: number; store_id?: number; from?: string; to?: string }) => ...
summary: (params?: { from?: string; to?: string; store_id?: number }) => ...
```

---

### Task 5.2 — Pre-lock store in `RecordSaleModal` for store admin

**File:** `merchant/src/pages/analytics/SalesPage.tsx` (`RecordSaleModal` component)

1. Pass `isStoreAdmin` and `scopedStoreId` as props to `RecordSaleModal` (or read from store inside the component):
```typescript
const isStoreAdmin  = useAuthStore(s => s.isStoreAdmin())
const scopedStoreId = useAuthStore(s => s.scopedStoreId())
```

2. Set the default value of `store_id` in the form:
```typescript
defaultValues: {
  store_id: isStoreAdmin && scopedStoreId ? String(scopedStoreId) : '',
}
```

3. In the `storeApi.list()` query inside `RecordSaleModal`, skip fetching all stores when store admin — use only the scoped store:
```typescript
const { data: stores = [] } = useQuery({
  queryKey: ['stores'],
  queryFn:  () => storeApi.list().then(r => r.data.data),
  enabled: !isStoreAdmin,
})
const displayStores = isStoreAdmin && scopedStoreId
  ? [{ id: scopedStoreId, store_name: '(Your store)' }]
  : stores
```

4. Replace the store `<select>` with a read-only field when `isStoreAdmin`, similar to Task 4.1.

---

## Group 6 — Coupon Banner Image on Create (G6) 🟢

**Context:**  
`couponApi.uploadImage(id, file)` requires a coupon ID, so it requires the coupon to exist first. Currently the form's image section is only rendered when `isEdit` is true. This is functionally correct but the UX is slightly unexpected.

---

### Task 6.1 — Clarify banner image UX in `CouponFormPage.tsx`

**File:** `merchant/src/pages/coupons/CouponFormPage.tsx`

In the "Deal Image" section where `{isEdit && <banner image block>}` is rendered:

Add an informational note below the section when NOT in edit mode:

```tsx
{!isEdit && (
  <div className="bg-white rounded-2xl p-4 shadow-sm">
    <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Deal Image</p>
    <p className="text-xs text-gray-400">
      <span className="font-semibold text-gray-500">Save the coupon first</span>, then open it to upload a banner image.
    </p>
  </div>
)}
```

This removes user confusion without requiring a multi-step create-then-upload architecture.

---

## Implementation Order

Execute in this sequence to avoid unresolved dependencies:

```
Group 5 (Sales RBAC)      → no new files, lowest risk
Group 4 (Coupon RBAC)     → no new files, low risk
Group 6 (Coupon image UX) → no new files, trivial
Group 3 (Revenue tab)     → no new files, additive
Group 2 (Banner image)    → new API methods + UI additions to existing pages
Group 1 (Store Admins)    → new files + new routes (largest change)
```

---

## Already-Verified Items — No Action Required

| Item | Notes |
|---|---|
| Flash discount store-admin scoping | `FlashDiscountPage` already hides store selector and injects `scopedStoreId` |
| Store list → redirect store admin | `StoreListPage` does `navigate('/stores/:id', {replace: true})` for store admins |
| `StoreAdminGuard` on sensitive routes | Profile, labels, subscription, customers, store-create all guarded |
| Customer Detail analytics | Fully implemented with date-range filter |
| Coupon image (edit mode) | `couponApi.uploadImage` / `deleteImage` wired correctly |
| Image utility consistency | All image renders use `getImageUrl()` from `lib/imageUrl.ts` |
| Bottom tab bar | Home / Coupons / Scan / Analytics / More |
| Subscription plan gating | `minAccountType` filter on `MorePage` menu items |
| Token refresh | Axios interceptor handles 401 → refresh → retry correctly |

---

## Architecture Notes

### Image Management
All image display must use `getImageUrl(path, fallback?)` from `@/lib/imageUrl`. This converts relative API paths (e.g. `uploads/logos/x.jpg`) to a fully-qualified URL using `VITE_API_ORIGIN`. All new image upload endpoints must return a relative path in `uploads/...` format and be displayed through this utility.

Upload calls must use `FormData` with `Content-Type: multipart/form-data` (passed in Axios config headers). This is already the pattern used by `storeApi.uploadImages()` and `merchantApi.uploadLogo()`.

### Role-Based Access Pattern
- Read role: `const isStoreAdmin = useAuthStore(s => s.isStoreAdmin())`
- Read scoped store: `const scopedStoreId = useAuthStore(s => s.scopedStoreId())`
- Block route entirely: wrap with `<StoreAdminGuard>` in `router.tsx`
- Lock field in form: replace `<select>` with read-only display + hidden input for store admins
- Scope API queries: pass `store_id: scopedStoreId` in params when `isStoreAdmin && scopedStoreId`

### API Endpoint Pattern
All new merchant-side endpoints follow the base path `/merchants/...`. New API modules must import `apiClient` from `@/api/client` and define typed request/response interfaces in the same file.
