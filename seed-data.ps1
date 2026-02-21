### =========================================================
### Seed Data Script for Merchant App New Implementations
### =========================================================

$API = "http://dealmachan-api.local/api"

# ── Step 1: Login ──────────────────────────────────────────
Write-Host "`n=== Step 1: Login as Spice Garden merchant ===" -ForegroundColor Cyan
$loginBody = '{"email":"spice.garden@kochi.com","password":"password"}'
$loginResp = Invoke-RestMethod -Uri "$API/auth/merchant/login" -Method POST -ContentType "application/json" -Body $loginBody
$token = $loginResp.data.tokens.access_token
$headers = @{Authorization = "Bearer $token"}
Write-Host "  Logged in as: $($loginResp.data.merchant.business_name) (merchant_id=$($loginResp.data.merchant.id))" -ForegroundColor Green

# ── Step 2: Update stores with opening hours ───────────────
Write-Host "`n=== Step 2: Update stores with opening hours ===" -ForegroundColor Cyan

$store2Hours = @'
{
    "opening_hours": {
        "Monday":    {"open": "09:00", "close": "22:00"},
        "Tuesday":   {"open": "09:00", "close": "22:00"},
        "Wednesday": {"open": "09:00", "close": "22:00"},
        "Thursday":  {"open": "09:00", "close": "22:00"},
        "Friday":    {"open": "09:00", "close": "23:00"},
        "Saturday":  {"open": "10:00", "close": "23:00"},
        "Sunday":    {"open": "10:00", "close": "21:00"}
    },
    "description": "Authentic Kerala cuisine in the heart of Ernakulam. Specializing in traditional spice-rich dishes and fresh seafood."
}
'@
try {
    $r = Invoke-RestMethod -Uri "$API/merchants/stores/2" -Method PUT -Headers $headers -ContentType "application/json" -Body $store2Hours
    Write-Host "  Store 2 (Ernakulam): $($r.message)" -ForegroundColor Green
} catch {
    Write-Host "  Store 2 FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

$store3Hours = @'
{
    "opening_hours": {
        "Monday":    {"open": "10:00", "close": "22:30"},
        "Tuesday":   {"open": "10:00", "close": "22:30"},
        "Wednesday": {"open": "10:00", "close": "22:30"},
        "Thursday":  {"open": "10:00", "close": "22:30"},
        "Friday":    {"open": "10:00", "close": "23:30"},
        "Saturday":  {"open": "09:00", "close": "23:30"},
        "Sunday":    {"open": "closed", "close": "closed"}
    },
    "description": "Premium dining experience on MG Road. Enjoy our signature Kerala thali and weekend brunch specials."
}
'@
try {
    $r = Invoke-RestMethod -Uri "$API/merchants/stores/3" -Method PUT -Headers $headers -ContentType "application/json" -Body $store3Hours
    Write-Host "  Store 3 (MG Road): $($r.message)" -ForegroundColor Green
} catch {
    Write-Host "  Store 3 FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

# ── Step 3: Create customers ──────────────────────────────
Write-Host "`n=== Step 3: Create customers for this merchant ===" -ForegroundColor Cyan

$customers = @(
    '{"name":"Anil Kumar","phone":"9876500001","email":"anil.kumar@test.com"}',
    '{"name":"Lakshmi Nair","phone":"9876500002","email":"lakshmi.nair@test.com"}',
    '{"name":"Rajesh Pillai","phone":"9876500003","email":"rajesh.pillai@test.com"}',
    '{"name":"Sneha Mohan","phone":"9876500004","email":"sneha.mohan@test.com"}',
    '{"name":"Vijay Kumar","phone":"9876500005","email":"vijay.kumar@test.com"}'
)

$createdCustomers = @()
foreach ($c in $customers) {
    try {
        $r = Invoke-RestMethod -Uri "$API/merchants/customers" -Method POST -Headers $headers -ContentType "application/json" -Body $c
        $cust = $r.data
        $createdCustomers += $cust.id
        Write-Host "  Created customer: $($cust.name) (id=$($cust.id))" -ForegroundColor Green
    } catch {
        $errBody = $_.ErrorDetails.Message
        Write-Host "  Customer SKIPPED: $errBody" -ForegroundColor Yellow
    }
}

# ── Step 4: Create flash discounts ────────────────────────
Write-Host "`n=== Step 4: Create flash discounts ===" -ForegroundColor Cyan

$flashDiscounts = @(
    '{"title":"Weekend Lunch Bonanza","description":"Flat 25% off on all weekend lunch combos. Valid Saturday and Sunday 12PM-3PM.","discount_percentage":25,"store_id":2,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-03-31 23:59:59","max_redemptions":100}',
    '{"title":"Happy Hour Special","description":"Get 40% off on selected appetizers and beverages between 4PM-7PM every day.","discount_percentage":40,"store_id":2,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-04-30 23:59:59","max_redemptions":200}',
    '{"title":"First Visit Discount","description":"New customers get 15% off on their first order at MG Road branch.","discount_percentage":15,"store_id":3,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-06-30 23:59:59","max_redemptions":500}',
    '{"title":"Monsoon Special","description":"Enjoy traditional Kerala snacks with 30% discount during rainy evenings.","discount_percentage":30,"store_id":2,"valid_from":"2026-06-01 00:00:00","valid_until":"2026-09-30 23:59:59","max_redemptions":150}'
)

foreach ($fd in $flashDiscounts) {
    try {
        $r = Invoke-RestMethod -Uri "$API/merchants/flash-discounts" -Method POST -Headers $headers -ContentType "application/json" -Body $fd
        $d = $r.data
        Write-Host "  Created flash discount: $($d.title) (id=$($d.id), $($d.discount_percentage)% off)" -ForegroundColor Green
    } catch {
        $errBody = $_.ErrorDetails.Message
        Write-Host "  Flash discount FAILED: $errBody" -ForegroundColor Red
    }
}

# ── Step 5: Create store coupons ──────────────────────────
Write-Host "`n=== Step 5: Create store coupons ===" -ForegroundColor Cyan
# Note: StoreCouponController.php may not exist yet - testing the endpoint
$storeCoupons = @(
    '{"store_id":2,"coupon_code":"SPICE20OFF","discount_type":"percentage","discount_value":20,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-06-30 23:59:59"}',
    '{"store_id":2,"coupon_code":"SPICE100","discount_type":"fixed","discount_value":100,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-05-31 23:59:59"}',
    '{"store_id":3,"coupon_code":"MGROAD15","discount_type":"percentage","discount_value":15,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-08-31 23:59:59"}',
    '{"store_id":3,"coupon_code":"MGROAD50","discount_type":"fixed","discount_value":50,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-04-30 23:59:59"}'
)

foreach ($sc in $storeCoupons) {
    try {
        $r = Invoke-RestMethod -Uri "$API/merchants/store-coupons" -Method POST -Headers $headers -ContentType "application/json" -Body $sc
        $d = $r.data
        Write-Host "  Created store coupon: $($d.coupon_code) (id=$($d.id))" -ForegroundColor Green
    } catch {
        $errBody = $_.ErrorDetails.Message
        Write-Host "  Store coupon FAILED: $errBody" -ForegroundColor Red
    }
}

# ── Step 6: Create platform coupons ───────────────────────
Write-Host "`n=== Step 6: Create platform coupons ===" -ForegroundColor Cyan
$platformCoupons = @(
    '{"title":"Welcome Offer - 10% Off","description":"Get 10% off on your first order at Spice Garden","coupon_code":"WELCOME10","discount_type":"percentage","discount_value":10,"store_id":2,"valid_from":"2026-02-20","valid_until":"2026-12-31","max_usage":100}',
    '{"title":"Festival Special - Rs.200 Off","description":"Flat Rs.200 off on orders above Rs.1000 during festival season","coupon_code":"FEST200","discount_type":"fixed","discount_value":200,"store_id":2,"valid_from":"2026-03-01","valid_until":"2026-04-30","max_usage":50}',
    '{"title":"MG Road Exclusive - 12% Off","description":"Exclusive discount for MG Road branch customers","coupon_code":"MGEXCL12","discount_type":"percentage","discount_value":12,"store_id":3,"valid_from":"2026-02-20","valid_until":"2026-07-31","max_usage":200}'
)

foreach ($pc in $platformCoupons) {
    try {
        $r = Invoke-RestMethod -Uri "$API/merchants/coupons" -Method POST -Headers $headers -ContentType "application/json" -Body $pc
        $d = $r.data
        Write-Host "  Created coupon: $($d.title) (id=$($d.id), code=$($d.coupon_code))" -ForegroundColor Green
    } catch {
        $errBody = $_.ErrorDetails.Message
        Write-Host "  Coupon FAILED: $errBody" -ForegroundColor Red
    }
}

# ── Step 7: Verify data ──────────────────────────────────
Write-Host "`n=== Step 7: Verify seeded data ===" -ForegroundColor Cyan

# Re-login in case token expired
$loginResp2 = Invoke-RestMethod -Uri "$API/auth/merchant/login" -Method POST -ContentType "application/json" -Body $loginBody
$token2 = $loginResp2.data.tokens.access_token
$headers2 = @{Authorization = "Bearer $token2"}

try {
    $stores = Invoke-RestMethod -Uri "$API/merchants/stores" -Method GET -Headers $headers2
    Write-Host "  Stores: $($stores.data.Count)" -ForegroundColor Green
    foreach ($s in $stores.data) {
        $hasHours = if ($s.opening_hours) { "YES" } else { "NO" }
        Write-Host "    - $($s.store_name) (id=$($s.id), hours=$hasHours, coupons=$($s.active_coupons))" -ForegroundColor White
    }
} catch {
    Write-Host "  Stores verify FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

try {
    $fd = Invoke-RestMethod -Uri "$API/merchants/flash-discounts" -Method GET -Headers $headers2
    Write-Host "  Flash discounts: $($fd.data.Count)" -ForegroundColor Green
    foreach ($d in $fd.data) {
        Write-Host "    - $($d.title) ($($d.discount_percentage)% off, status=$($d.status))" -ForegroundColor White
    }
} catch {
    Write-Host "  Flash discounts verify FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

try {
    $cust = Invoke-RestMethod -Uri "$API/merchants/customers" -Method GET -Headers $headers2
    Write-Host "  Customers: $($cust.data.Count) (total=$($cust.meta.total))" -ForegroundColor Green
    foreach ($c in $cust.data) {
        Write-Host "    - $($c.name) (id=$($c.id), phone=$($c.phone))" -ForegroundColor White
    }
} catch {
    Write-Host "  Customers verify FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

try {
    $coupons = Invoke-RestMethod -Uri "$API/merchants/coupons" -Method GET -Headers $headers2
    Write-Host "  Platform coupons: $($coupons.data.Count)" -ForegroundColor Green
    foreach ($cp in $coupons.data) {
        Write-Host "    - $($cp.title) (code=$($cp.coupon_code), $($cp.discount_type)=$($cp.discount_value))" -ForegroundColor White
    }
} catch {
    Write-Host "  Coupons verify FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

try {
    $sc = Invoke-RestMethod -Uri "$API/merchants/store-coupons" -Method GET -Headers $headers2
    Write-Host "  Store coupons: $($sc.data.Count)" -ForegroundColor Green
    foreach ($s in $sc.data) {
        Write-Host "    - $($s.coupon_code) ($($s.discount_type)=$($s.discount_value))" -ForegroundColor White
    }
} catch {
    $errBody = $_.ErrorDetails.Message
    Write-Host "  Store coupons verify FAILED: $errBody" -ForegroundColor Red
}

Write-Host "`n=== Seed Data Complete ===" -ForegroundColor Cyan
