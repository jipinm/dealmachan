# Customer Application — Implementation Plan

**Prepared for:** AI Code Agent  
**Scope:** Full analysis of Customer, Merchant, Admin, API, and Database  
**Goal:** Correct all incorrect implementations and complete all missing features in the Customer React application  

---

## Analysis Summary

The customer app is a React 18 + TypeScript PWA (Vite) using React Router v6, TanStack Query, Zustand, and Tailwind CSS. It has 48 routes (18 public, 28 protected).

**Primary finding:** The `/stores` browse page and `/stores/:id` detail page both call **merchant** API endpoints instead of **store** API endpoints. This violates the core business logic — stores are the operational entities linked to locations, coupons, and flash discounts; merchants are organisational parent records. The correct store endpoints (`GET /public/stores`, `GET /public/stores/:id`) already exist and are correctly implemented in the PHP API.

Secondary findings: several navigation links use merchant IDs on store routes, no store-scoped review submission endpoint exists, and the TypeScript types for the store detail API response are mismatched with what the server actually returns.

---

## Files to be modified

| File | Type of change |
|------|----------------|
| `customer/src/api/endpoints/public.ts` | Interface additions + type fix for `getStore` |
| `customer/src/pages/stores/StoresPage.tsx` | Full rewrite — switch from merchants to stores |
| `customer/src/pages/stores/StoreDetailPage.tsx` | Full rewrite — switch from merchant to store API |
| `customer/src/pages/deals/DealDetailPage.tsx` | Fix merchant navigation link |
| `customer/src/pages/home/HomePage.tsx` | Fix featured merchants navigation link |
| `api/controllers/Public/StoreBrowseController.php` | Add `submitReview()` method |
| `api/index.php` | Register `POST /public/stores/:id/reviews` route |

---

## Module 1 — TypeScript Type Fixes (`customer/src/api/endpoints/public.ts`)

These must be done first because all other modules depend on them.

### Task 1.1 — Extend `PublicStore` interface

The current `PublicStore` interface only covers the fields needed by the browse list. Add the fields returned by the list endpoint that are not yet typed:

```ts
// In the existing PublicStore interface, add:
store_image: string | null
is_premium: boolean
phone: string | null         // returned by index, needed for card display
```

**Location:** Find the `export interface PublicStore` block and insert the three new fields.

### Task 1.2 — Add `PublicStoreDetail` interface

The `/public/stores/:id` endpoint returns much richer data than the list. Add a detail interface:

```ts
export interface PublicStoreDetail extends PublicStore {
  email: string | null
  latitude: number | null
  longitude: number | null
  opening_hours: OpeningHoursEntry[]
  description: string | null
  city_id: number | null
  area_id: number | null
}
```

**Location:** Place this immediately after the `PublicStore` interface.

### Task 1.3 — Add `StoreReview` interface

```ts
export interface StoreReview {
  id: number
  rating: number
  review_text: string | null
  created_at: string
  customer_name: string | null
}
```

**Location:** Place after `PublicStoreDetail`.

### Task 1.4 — Fix the `getStore` return type

The current type in `publicApi.getStore` is incorrect. The server at `GET /public/stores/:id` returns:

```json
{ "data": { "store": { ...store fields... }, "coupons": [...], "reviews": [...] } }
```

but the current TS type says `data` is a flat `PublicStore & { coupons, reviews }`.

**Change this:**
```ts
getStore: (id: number) =>
    apiClient.get<{ data: PublicStore & { coupons: TopCoupon[]; reviews: unknown[] } }>(`/public/stores/${id}`),
```

**To this:**
```ts
getStore: (id: number) =>
    apiClient.get<{ data: { store: PublicStoreDetail; coupons: TopCoupon[]; reviews: StoreReview[] } }>(`/public/stores/${id}`),
```

### Task 1.5 — Add `submitStoreReview` to `publicApi`

Add this method at the end of the `publicApi` object, immediately before the closing `}`:

```ts
/** Submit a review for a store (requires auth) */
submitStoreReview: (storeId: number, body: { rating: number; review_text?: string }) =>
    apiClient.post<{ data: { message: string } }>(`/public/stores/${storeId}/reviews`, body),
```

---

## Module 2 — Fix `StoresPage.tsx`

**File:** `customer/src/pages/stores/StoresPage.tsx`

This page must be rewritten to list **stores** (via `publicApi.getStores()`) instead of merchants. The existing `MerchantCard` component and all merchant-specific data references must be replaced.

### Task 2.1 — Replace imports

Remove the import of `PublicMerchant`. Add `PublicStore` to the import from `public.ts`. Remove the `search: q` and `has_coupons: false` params (the store API does not support them).

**Change:**
```ts
import { publicApi, type PublicMerchant } from '@/api/endpoints/public'
```
**To:**
```ts
import { publicApi, type PublicStore } from '@/api/endpoints/public'
```

### Task 2.2 — Replace `MerchantCard` with `StoreCard`

Remove the entire `MerchantCard` function and replace it with a `StoreCard` function that uses `PublicStore` fields:

```tsx
function StoreCard({ store }: { store: PublicStore }) {
  return (
    <Link to={`/stores/${store.id}/${slugify(store.store_name)}`} className="card card-hover group block overflow-hidden">
      <div className="relative h-36 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
        {(store.store_image || store.business_logo) ? (
          <img
            src={imgSrc(store.store_image ?? store.business_logo)}
            alt={store.store_name}
            loading="lazy"
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <Store size={40} className="text-slate-300" />
          </div>
        )}
        {store.is_premium && (
          <span className="absolute top-2 left-2 badge-new">✨ Premium</span>
        )}
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-slate-800 text-sm mb-1 line-clamp-1">{store.store_name}</h3>
        <p className="text-xs text-slate-400 mb-1.5 truncate">{store.business_name}</p>
        <div className="flex items-center gap-1 mb-2">
          <Star size={12} className="text-yellow-400 fill-yellow-400" />
          <span className="text-xs font-semibold text-slate-700">{store.avg_rating != null ? Number(store.avg_rating).toFixed(1) : '–'}</span>
          <span className="text-xs text-slate-400">({store.total_reviews ?? 0})</span>
        </div>
        {(store.area_name || store.city_name) && (
          <div className="flex items-center gap-1 text-xs text-slate-500 mb-2">
            <MapPin size={11} />
            <span className="truncate">{store.area_name ?? store.city_name}</span>
          </div>
        )}
        {store.active_coupons_count > 0 && (
          <div className="flex items-center gap-1 text-xs text-brand-600 font-semibold">
            <Tag size={11} />
            {store.active_coupons_count} deal{store.active_coupons_count > 1 ? 's' : ''}
          </div>
        )}
      </div>
    </Link>
  )
}
```

### Task 2.3 — Update the `useInfiniteQuery` call

**Change:**
```ts
queryKey: ['stores-inf', cityId, areaId, q, categoryId],
queryFn: ({ pageParam = 1 }) =>
  publicApi.getMerchants({ city_id: cityId ?? undefined, area_id: areaId ?? undefined, q, search: q, category_id: categoryId, page: pageParam as number, per_page: 12, has_coupons: false })
    .then((r) => r.data),
```

**To:**
```ts
queryKey: ['stores-inf', cityId, areaId, q, categoryId],
queryFn: ({ pageParam = 1 }) =>
  publicApi.getStores({ city_id: cityId ?? undefined, area_id: areaId ?? undefined, q, category_id: categoryId, page: pageParam as number, per_page: 12 })
    .then((r) => r.data),
```

### Task 2.4 — Update data extraction and pagination

**Change:**
```ts
const merchants: PublicMerchant[] = data?.pages.flatMap((p: any) => p?.data?.data ?? []) ?? []
```

**To:**
```ts
const stores: PublicStore[] = data?.pages.flatMap((p: any) => p?.data?.data ?? []) ?? []
```

The `getNextPageParam` logic `lastPage?.data?.pagination?.pages` is already correct for the `getStores` response shape — no change needed.

### Task 2.5 — Update the render section

Replace all occurrences of `merchant` with the corrected `store` variable and replace `<MerchantCard merchant={merchant} />` with `<StoreCard store={store} />` in the JSX grid rendering.

Also, in the loading skeleton default state copy at the bottom of the page, change "merchant" references to "store".

### Task 2.6 — Fix SEO title

**Change:**
```tsx
<title>Merchant Directory | Deal Machan</title>
```

**To:**
```tsx
<title>Store Directory | Deal Machan</title>
```

Also update the heading and paragraph:

```tsx
<h1 className="section-heading mb-2">Browse Stores</h1>
<p className="text-slate-500 text-sm mb-5">Discover local stores and their exclusive offers</p>
```

---

## Module 3 — Fix `StoreDetailPage.tsx`

**File:** `customer/src/pages/stores/StoreDetailPage.tsx`

This page must be fully rewritten to use `publicApi.getStore(id)` instead of `publicApi.getMerchant(id)`. The API response shape is different and the rendered content must reflect a single store (not a merchant with multiple stores).

### Task 3.1 — Replace imports

**Change:**
```ts
import { publicApi, PublicMerchant } from '@/api/endpoints/public'
```

**To:**
```ts
import { publicApi, type PublicStoreDetail, type StoreReview } from '@/api/endpoints/public'
```

### Task 3.2 — Fix the favourite state `merchantId`

The page currently uses `Number(id)` as a merchant ID for favourites. After the fix, `id` is a store ID. The favourite must use the store's `merchant_id` field. Since the store data is required first, initialise a `merchantIdForFav` state variable.

Replace the top of the component:
```ts
const merchantId = Number(id)
const { data: favData } = useQuery({
    queryKey: ['fav-check', merchantId],
    queryFn: () => favouritesApi.check(merchantId).then((r) => r.is_favourite ?? false),
    enabled: !!id && isAuthenticated,
```

With a derived value that depends on the loaded store data. The simplest approach: keep the `favData` query but enable it only once `store` is loaded, and use `store.merchant_id` as the key:

```ts
// After the store query (see Task 3.3), add:
const merchantIdForFav = store?.merchant_id ?? 0

const { data: favData } = useQuery({
    queryKey: ['fav-check', merchantIdForFav],
    queryFn: () => favouritesApi.check(merchantIdForFav).then((r) => r.is_favourite ?? false),
    enabled: !!store && isAuthenticated,
    staleTime: 60_000,
})
```

Update the `favMutation` to use `merchantIdForFav` for `add`/`remove` calls, and `queryClient.invalidateQueries({ queryKey: ['fav-check', merchantIdForFav] })` on success.

### Task 3.3 — Replace the main data query

**Remove** the three separate queries for `merchant`, `coupons`, and `reviewsData`.

**Replace** with a single query:

```ts
const { data: storeDetail, isLoading } = useQuery({
    queryKey: ['store', id],
    queryFn: () =>
        publicApi.getStore(Number(id)).then((r) => {
            const payload = (r.data as any).data
            // API returns { store: {...}, coupons: [...], reviews: [...] }
            return payload as { store: PublicStoreDetail; coupons: any[]; reviews: StoreReview[] }
        }),
    enabled: !!id,
})

const store = storeDetail?.store
const coupons = storeDetail?.coupons ?? []
const reviews = storeDetail?.reviews ?? []
```

### Task 3.4 — Fix the `reviewMutation`

**Change:**
```ts
const reviewMutation = useMutation({
    mutationFn: (vars: { rating: number; review_text?: string }) =>
        publicApi.submitReview(Number(id), vars),
    onSuccess: () => {
        setReviewSubmitted(true)
        setRating(0)
        setReviewText('')
        queryClient.invalidateQueries({ queryKey: ['merchant-reviews', id] })
    },
})
```

**To:**
```ts
const reviewMutation = useMutation({
    mutationFn: (vars: { rating: number; review_text?: string }) =>
        publicApi.submitStoreReview(Number(id), vars),
    onSuccess: () => {
        setReviewSubmitted(true)
        setRating(0)
        setReviewText('')
        queryClient.invalidateQueries({ queryKey: ['store', id] })
    },
})
```

### Task 3.5 — Rewrite the rendered JSX

The current template renders `merchant.business_name` in the sidebar and a "Store Locations" section listing `merchant.stores[]` in a grid. After the fix:

- The sidebar becomes a **store info panel** showing: store name, store image, address, city/area, phone, email, categories, opening hours summary
- A **merchant context row** is added (below the sidebar or above the tabs) linking to `/merchants/${store.merchant_id}` with the merchant's logo and business name
- The "Store Locations" section is **removed** (this page is now a single store — not a merchant with multiple stores)
- Coupons and reviews sections remain but draw from the single-query `storeDetail` data

**Sidebar rewrite template:**
```tsx
<aside>
  <div className="card p-6 mb-5 sticky top-24">
    {/* Store image */}
    <div className="w-full h-32 rounded-2xl bg-slate-100 overflow-hidden mb-4">
      {store.store_image || store.business_logo ? (
        <img
          src={imgSrc(store.store_image ?? store.business_logo)}
          alt={store.store_name}
          className="w-full h-full object-cover"
        />
      ) : (
        <div className="w-full h-full flex items-center justify-center">
          <Store size={36} className="text-slate-300" />
        </div>
      )}
    </div>

    {/* Store name + favourite */}
    <div className="flex items-center justify-between mb-2">
      <h1 className="font-heading font-bold text-xl text-slate-800 flex-1">{store.store_name}</h1>
      <button
        onClick={() => favMutation.mutate()}
        disabled={favMutation.isPending}
        aria-label={isFavourited ? 'Remove from favourites' : 'Add to favourites'}
        className="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 transition-colors hover:bg-rose-50"
      >
        {favMutation.isPending
          ? <HeartLoader size={16} className="animate-spin text-slate-400" />
          : <Heart size={18} className={isFavourited ? 'fill-rose-500 text-rose-500' : 'text-slate-300 hover:text-rose-400'} />
        }
      </button>
    </div>

    {/* Rating */}
    <div className="flex items-center gap-1.5 mb-3">
      <RatingStars rating={store.avg_rating ?? 0} size={15} showValue reviewCount={store.total_reviews ?? 0} />
    </div>

    {/* Address */}
    {store.address && (
      <div className="flex items-start gap-2 text-sm text-slate-500 mb-2">
        <MapPin size={14} className="flex-shrink-0 mt-0.5 text-slate-400" />
        <span className="leading-relaxed">
          {store.address}
          {store.area_name ? `, ${store.area_name}` : ''}
          {store.city_name ? `, ${store.city_name}` : ''}
        </span>
      </div>
    )}

    {/* Phone */}
    {store.phone && (
      <a href={`tel:${store.phone}`} className="flex items-center gap-2 text-sm text-brand-600 hover:underline mb-2">
        <Phone size={13} /> {store.phone}
      </a>
    )}

    {/* Email */}
    {store.email && (
      <a href={`mailto:${store.email}`} className="flex items-center gap-2 text-sm text-brand-600 hover:underline mb-2">
        <Mail size={13} /> {store.email}
      </a>
    )}

    {/* Directions */}
    {store.latitude && store.longitude && (
      <a
        href={`https://www.google.com/maps/dir/?api=1&destination=${store.latitude},${store.longitude}`}
        target="_blank"
        rel="noopener noreferrer"
        className="flex items-center gap-2 text-sm text-emerald-600 hover:underline mb-2"
      >
        <Navigation size={13} /> Get Directions
      </a>
    )}

    {/* Opening hours */}
    {store.opening_hours?.length > 0 && (
      <div className="mt-3">
        <TodayHours hours={store.opening_hours} />
        <button
          onClick={() => setShowHours(showHours === store.id ? null : store.id)}
          className="mt-1 text-[11px] text-slate-400 hover:text-brand-600 underline"
        >
          {showHours === store.id ? 'Hide hours' : 'View all hours'}
        </button>
        {showHours === store.id && <OpeningHoursGrid hours={store.opening_hours} />}
      </div>
    )}

    {/* Categories */}
    {store.categories?.length > 0 && (
      <div className="flex flex-wrap gap-1.5 mt-4">
        {store.categories.map((cat) => (
          <span key={cat.id} className="px-2.5 py-1 bg-brand-50 text-brand-700 text-xs font-medium rounded-full">
            {cat.name}
          </span>
        ))}
      </div>
    )}

    {/* Merchant attribution */}
    <div className="mt-4 pt-4 border-t border-slate-100">
      <Link
        to={`/merchants/${store.merchant_id}`}
        className="flex items-center gap-2 text-sm text-slate-500 hover:text-brand-600 transition-colors group"
      >
        <div className="w-8 h-8 rounded-full bg-slate-100 overflow-hidden flex-shrink-0">
          {store.business_logo ? (
            <img src={imgSrc(store.business_logo)} alt={store.business_name} className="w-full h-full object-cover" />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-slate-400 text-xs font-bold">{store.business_name?.charAt(0)}</div>
          )}
        </div>
        <span className="truncate group-hover:underline">{store.business_name}</span>
        <ChevronRight size={13} className="flex-shrink-0 ml-auto" />
      </Link>
    </div>
  </div>
</aside>
```

**Remove** the entire `{/* Store locations */}` section from `<main>` since it no longer applies.

Update breadcrumb to use `store.store_name` instead of `merchant.business_name`.

Update page `<title>` to `{store.store_name} | Deal Machan`.

Update `<meta name="description">` to reference store name and location.

### Task 3.6 — Remove unused state

Remove `const [showHours, setShowHours] = useState<number | null>(null)` — replace with a simpler boolean `const [showHours, setShowHours] = useState(false)` since there is now only one store.

Update `setShowHours` calls accordingly.

---

## Module 4 — Fix Navigation Links in Other Pages

### Task 4.1 — Fix `DealDetailPage.tsx`

**File:** `customer/src/pages/deals/DealDetailPage.tsx`, line 305

The "merchant row" inside coupon detail links to `/stores/${merchant.id}` — this uses a merchant ID to navigate to a store route. A merchant ID is not a valid store ID.

**Change:**
```tsx
to={`/stores/${merchant.id}/${slugify(merchant.business_name)}`}
```

**To:**
```tsx
to={`/merchants/${merchant.id}`}
```

This navigates to the merchant detail page at `/merchants/:id`, which is the correct route for displaying a merchant with all their stores and offers.

### Task 4.2 — Fix `HomePage.tsx`

**File:** `customer/src/pages/home/HomePage.tsx`, line 130

The "Featured Stores" section iterates over `featured_merchants` from the home feed and links each merchant card to `/stores/${merchant.id}/${slugify(merchant.business_name)}`. A merchant's ID is not a store ID.

**Change:**
```tsx
<Link to={`/stores/${merchant.id}/${slugify(merchant.business_name)}`} ...>
```

**To:**
```tsx
<Link to={`/merchants/${merchant.id}`} ...>
```

This navigates to the merchant detail page which shows the merchant's profile, stores, coupons, gallery, and reviews — which is the appropriate destination when clicking a featured merchant.

Also update the section heading to be `"Featured Merchants"` (or keep "Featured Stores" but ensure it is clear these link to merchants) for UX clarity.

---

## Module 5 — Store Review Submission (Backend + Frontend)

The store detail page currently shows reviews that are store-scoped (`WHERE r.store_id = ? AND r.status = 'approved'`). There is no API endpoint to submit a store-scoped review. This module adds that endpoint.

### Task 5.1 — Add `submitReview()` to `StoreBrowseController.php`

**File:** `api/controllers/Public/StoreBrowseController.php`

Add the following method to the `StoreBrowseController` class, after the `coupons()` method:

```php
// ── POST /api/public/stores/:id/reviews ──────────────────────────────────
public function submitReview(int $storeId, array $body): never {
    $auth = CustomerMiddleware::required();

    // Validate store exists
    $store = $this->db->queryOne(
        "SELECT s.id FROM stores s WHERE s.id = ? AND s.status = 'active' AND s.deleted_at IS NULL",
        [$storeId]
    );
    if (!$store) Response::notFound('Store not found.');

    $rating     = isset($body['rating']) ? (int)$body['rating'] : 0;
    $reviewText = isset($body['review_text']) ? trim((string)$body['review_text']) : null;

    if ($rating < 1 || $rating > 5) {
        Response::error('Rating must be between 1 and 5.', 422);
    }

    // Prevent duplicate reviews from the same customer for this store
    $existing = $this->db->queryOne(
        "SELECT id FROM reviews WHERE store_id = ? AND customer_id = ?",
        [$storeId, $auth['id']]
    );
    if ($existing) {
        Response::error('You have already submitted a review for this store.', 409);
    }

    $this->db->execute(
        "INSERT INTO reviews (store_id, customer_id, rating, review_text, status, created_at)
         VALUES (?, ?, ?, ?, 'pending', NOW())",
        [$storeId, $auth['id'], $rating, $reviewText ?: null]
    );

    Response::success(['message' => 'Review submitted. It will appear after admin approval.']);
}
```

**Note:** The `reviews` table already has a `store_id` column (confirmed in `StoreBrowseController::show` which queries `WHERE r.store_id = ?`). The `status` column value `'pending'` will put it in the admin review queue — matching the merchant review workflow.

### Task 5.2 — Register the route in `api/index.php`

**File:** `api/index.php`

Find the existing store routes block (around line 269–280):

```php
if (matchRoute('GET', 'public/stores', $path)) { ... }
if (matchRoute('GET', 'public/stores/:id/coupons', $path)) { ... }
if (matchRoute('GET', 'public/stores/:id', $path)) { ... }
```

**Add immediately after the last store route:**

```php
if (matchRoute('POST', 'public/stores/:id/reviews', $path)) {
    CustomerMiddleware::required();
    (new StoreBrowseController())->submitReview((int)param('id'), json_decode(file_get_contents('php://input'), true) ?? []);
}
```

---

## Module 6 — Minor Fixes

### Task 6.1 — Fix `SearchPage.tsx` merchant navigation links

**File:** `customer/src/pages/search/SearchPage.tsx`

In the search results, the "Stores" filter tab (which shows `results.merchants`) renders merchant cards. Find the merchant card links in the JSX (likely a `Link to=...` pointing to `/stores/${m.id}` or similar). Correct these to link to `/merchants/${m.id}` to match the actual entity type.

The `stores` filter tab (showing `results.stores`, a `SearchStore[]` from the unified search) shows store-level results that can link to `/stores/${s.id}/${slugify(s.name)}` — this is already correct or may need a `slugify(s.name)` addition.

### Task 6.2 — (Optional) Rename "Stores" tab in SearchPage to "Merchants"

In `SearchPage.tsx`, line 97:

```ts
{ id: 'merchants', label: 'Stores',    count: merchants.length },
```

Consider changing the label:

```ts
{ id: 'merchants', label: 'Merchants', count: merchants.length },
```

And line 98:

```ts
{ id: 'stores',    label: 'Locations', count: stores.length   },
```

Change to:

```ts
{ id: 'stores',    label: 'Stores',    count: stores.length   },
```

This makes the distinction clear: "Merchants" shows merchant-level search results; "Stores" shows store-location-level search results.

---

## Module 7 — Image Consistency Audit

### Task 7.1 — Verify all image displays use `getImageUrl()`

The `getImageUrl()` function from `@/lib/imageUrl` is the platform-wide image URL resolver. All `<img src=...>` values for server-hosted images must pass the path through this function.

Do a project-wide search in `customer/src/` for any `src={...}` attributes that use raw URL strings obtained from API responses without calling `getImageUrl()`. Pay special attention to:

- `store.store_image` — new field, must use `imgSrc()` / `getImageUrl()`
- `coupon.banner_image` — check all coupon card components
- `store.business_logo` — check all store card components
- Flash deal `banner_image` — check `FlashDealsPage.tsx` and `FlashDealDetailPage.tsx`

The `imgSrc()` local helper functions in each page file already wrap `getImageUrl()`, so images loaded through those are compliant. The concern is any direct `img src={...}` that bypasses the helper.

### Task 7.2 — `StoresPage.tsx` — display store image with fallback to business logo

The new `StoreCard` component (from Module 2, Task 2.2) already handles this pattern:

```tsx
src={imgSrc(store.store_image ?? store.business_logo)}
```

Ensure this pattern is preserved in the final implementation.

---

## Module 8 — Missing Features (Lower Priority)

These items are identified as incomplete or missing but do not break existing functionality. Implement them after Modules 1–7 are complete and verified.

### Task 8.1 — Contest Participation — Card Requirement Warning

**File:** `customer/src/pages/contests/ContestDetailPage.tsx`

The contest participation API (`POST /customers/contests/:id/participate`) requires the customer to have an active loyalty card. Currently the frontend shows a "Participate" button without checking whether the customer has an active card.

**Implementation:**

1. When the contest detail page loads and the user is authenticated, also call `customersApi.getMyCard()` (or the equivalent — check `customer/src/api/endpoints/` for the cards endpoint).
2. Before showing the "Participate" button, check if the user has an active card.
3. If no active card exists, disable the button and show a notice: _"You need an active Deal Machan card to participate. [Get a card →]"_ with a link to `/loyalty/cards`.

### Task 8.2 — Gift Coupons — Show Accepted Gifts in Wallet

**File:** `customer/src/pages/coupons/CouponWalletPage.tsx`

The wallet's `gifts` tab currently shows only `pending` gift coupons with Accept/Reject actions. Accepted gifts should also appear with a "Use" option (similar to saved coupons).

**Implementation:**

1. Review the API response from `GET /customers/coupons/wallet` — check whether `gifts` array includes accepted gift coupons (status = `'accepted'`) or only pending ones.
2. If the API already returns accepted gifts, update the wallet filter in the `gifts` tab to show both `pending` (with Accept/Reject) and `accepted` (with a "Use" / QR code button) gift coupons.
3. If the API only returns pending gifts, add `status` as a query parameter or add a second API call to `GET /customers/coupons/history` filtered by gift type and `accepted` status.

### Task 8.3 — Store Coupon Detail — Store Context Display

**File:** `customer/src/pages/coupons/StoreCouponPage.tsx`

Store coupons (subscribed via `GET /customers/store-coupons`) may not display the associated store name and address. Check the API response shape and ensure each store coupon card shows at minimum:
- Store name
- Store address / area
- Coupon code (on claim)

### Task 8.4 — `CouponDetailPage.tsx` — Stores List

**File:** `customer/src/pages/coupons/CouponDetailPage.tsx`

The coupon detail from `GET /public/coupons/:id` returns `{ coupon, merchant, stores[] }`. If the `stores[]` array is populated, it should be displayed on the coupon detail page showing where the coupon can be redeemed. Each store in the list should link to `/stores/${store.id}`.

Check whether this is already implemented. If not:

1. Add a "Where to use" section below the coupon details that renders a list of linked stores.
2. Each store entry shows: store name, address, area/city, phone (with `tel:` link).
3. If `stores` is empty, do not render the section.

---

## Implementation Order

1. **Module 1** (types) — foundation, do this first before any page edits
2. **Module 5** (backend review endpoint) — can be done in parallel with Module 1
3. **Module 2** (StoresPage) — depends on Module 1 types
4. **Module 3** (StoreDetailPage) — depends on Modules 1 and 5
5. **Module 4** (DealDetailPage, HomePage links) — standalone, no dependencies
6. **Module 6** (SearchPage) — standalone
7. **Module 7** (image audit) — do as a review pass after main page work
8. **Module 8** (lower priority features) — after all critical fixes verified

---

## Validation Checklist

After implementing all modules, verify the following:

| Check | How to verify |
|-------|---------------|
| `/stores` page lists actual stores, not merchants | Browse the page and confirm store names shown |
| `/stores/:id` page loads a single store | Navigate from a store card, confirm store name in header matches clicked card |
| Store detail page shows store image, address, phone, opening hours | Check sidebar on a store with full data |
| Store detail "Merchant" row links to `/merchants/:id` | Click the merchant row, confirm URL and page matches the merchant |
| Coupon detail "Merchant" row links to `/merchants/:id` | Open any deal, click the merchant row |
| Home page featured merchant cards link to `/merchants/:id` | Click a featured card from home page |
| Review submission on store detail calls `/public/stores/:id/reviews` | Submit a review (login required), check network tab |
| Store review appears after admin approval | Log into admin, approve the review, check store detail |
| No TypeScript errors | Run `npx tsc --noEmit` in the `customer/` directory |

---

## Notes for the Implementing Agent

- Do not modify the `/merchants` route tree (`MerchantListPage`, `MerchantDetailPage`). These pages correctly call merchant API endpoints and must remain unchanged.
- The `ExplorePage.tsx` stores tab already correctly calls `publicApi.getStores()` — do not touch it.
- The `StoreCard` component in `customer/src/components/ui/StoreCard.tsx` (used by ExplorePage) should be reviewed. If it already has a suitable design, reuse it in `StoresPage.tsx` instead of creating a new local `StoreCard` function. Adapt if needed.
- The `opening_hours` field from `GET /public/stores/:id` is always returned as a parsed JSON array `[{ day, open, close, closed }]` by the PHP controller (`StoreBrowseController::show` calls `json_decode()`). The `TodayHours` and `OpeningHoursGrid` components in `StoreDetailPage.tsx` are already written to handle this format — no changes needed there.
- The `reviews` table `status` column uses `'pending'` for newly submitted reviews that require admin approval before appearing. This matches the merchant review workflow.
- The `CustomerMiddleware::required()` call inside `StoreBrowseController::submitReview()` ensures the duplicate route-level middleware call in `api/index.php` is redundant but harmless. Keep the `api/index.php` middleware call as a defence-in-depth measure.
