### =========================================================
### Re-seed & Verify Script  
### =========================================================

$API = "http://dealmachan-api.local/api"

# ── Login ──────────────────────────────────────────────────
Write-Host "=== Login ===" -ForegroundColor Cyan
$loginBody = '{"email":"spice.garden@kochi.com","password":"password"}'
$loginResp = Invoke-RestMethod -Uri "$API/auth/merchant/login" -Method POST -ContentType "application/json" -Body $loginBody
$token = $loginResp.data.tokens.access_token
$headers = @{Authorization = "Bearer $token"}
Write-Host "  Logged in as: $($loginResp.data.merchant.business_name)" -ForegroundColor Green

# ── Create store coupons (now that controller exists) ──────
Write-Host "`n=== Create Store Coupons ===" -ForegroundColor Cyan
$storeCoupons = @(
    '{"store_id":2,"coupon_code":"SPICE20OFF","discount_type":"percentage","discount_value":20,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-06-30 23:59:59"}',
    '{"store_id":2,"coupon_code":"SPICE100","discount_type":"fixed","discount_value":100,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-05-31 23:59:59"}',
    '{"store_id":3,"coupon_code":"MGROAD15","discount_type":"percentage","discount_value":15,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-08-31 23:59:59"}',
    '{"store_id":3,"coupon_code":"MGROAD50","discount_type":"fixed","discount_value":50,"valid_from":"2026-02-20 00:00:00","valid_until":"2026-04-30 23:59:59"}'
)

$createdCouponIds = @()
foreach ($sc in $storeCoupons) {
    try {
        $r = Invoke-RestMethod -Uri "$API/merchants/store-coupons" -Method POST -Headers $headers -ContentType "application/json" -Body $sc
        $d = $r.data
        $createdCouponIds += $d.id
        Write-Host "  Created: $($d.coupon_code) (id=$($d.id), $($d.discount_type) $($d.discount_value))" -ForegroundColor Green
    } catch {
        $errBody = $_.ErrorDetails.Message
        Write-Host "  FAILED: $errBody" -ForegroundColor Red
    }
}

# ── Assign a store coupon to a customer ────────────────────
Write-Host "`n=== Assign Store Coupon to Customer ===" -ForegroundColor Cyan
if ($createdCouponIds.Count -ge 1) {
    # Get customer list first
    try {
        $custResp = Invoke-RestMethod -Uri "$API/merchants/customers" -Method GET -Headers $headers
        $customers = $custResp.data
        if ($customers.Count -gt 0) {
            $firstCustomerId = $customers[0].id
            $assignBody = "{`"customer_id`":$firstCustomerId}"
            $r = Invoke-RestMethod -Uri "$API/merchants/store-coupons/$($createdCouponIds[0])/assign" -Method POST -Headers $headers -ContentType "application/json" -Body $assignBody
            Write-Host "  Assigned coupon id=$($createdCouponIds[0]) to customer id=$firstCustomerId : $($r.message)" -ForegroundColor Green
        } else {
            Write-Host "  No customers found to assign coupon" -ForegroundColor Yellow
        }
    } catch {
        $errBody = $_.ErrorDetails.Message
        Write-Host "  Assign FAILED: $errBody" -ForegroundColor Red
    }
}

# ── Bulk assign a store coupon ─────────────────────────────
Write-Host "`n=== Bulk Assign Store Coupon ===" -ForegroundColor Cyan
if ($createdCouponIds.Count -ge 2) {
    try {
        $custResp = Invoke-RestMethod -Uri "$API/merchants/customers" -Method GET -Headers $headers
        $customers = $custResp.data
        if ($customers.Count -ge 2) {
            $ids = @($customers[0].id, $customers[1].id)
            $bulkBody = "{`"customer_ids`":[$($ids -join ',')]}"
            $r = Invoke-RestMethod -Uri "$API/merchants/store-coupons/$($createdCouponIds[1])/bulk-assign" -Method POST -Headers $headers -ContentType "application/json" -Body $bulkBody
            Write-Host "  Bulk assigned: $($r.data.assigned_count) customers : $($r.message)" -ForegroundColor Green
        }
    } catch {
        $errBody = $_.ErrorDetails.Message
        Write-Host "  Bulk assign FAILED: $errBody" -ForegroundColor Red
    }
}

# ── Seed some sales data for analytics ─────────────────────
Write-Host "`n=== Seed Sales Registry Data ===" -ForegroundColor Cyan
try {
    $custResp = Invoke-RestMethod -Uri "$API/merchants/customers" -Method GET -Headers $headers
    $customers = $custResp.data
    if ($customers.Count -gt 0) {
        $salesData = @(
            @{store_id=2; customer_id=$customers[0].id; amount=1500; payment_method="upi"},
            @{store_id=2; customer_id=$customers[0].id; amount=2200; payment_method="card"},
            @{store_id=3; customer_id=$customers[0].id; amount=800; payment_method="cash"},
            @{store_id=2; customer_id=$customers[1].id; amount=3500; payment_method="upi"}
        )
        foreach ($sale in $salesData) {
            try {
                $saleBody = $sale | ConvertTo-Json
                $r = Invoke-RestMethod -Uri "$API/merchants/sales-registry" -Method POST -Headers $headers -ContentType "application/json" -Body $saleBody
                Write-Host "  Sale: store=$($sale.store_id) customer=$($sale.customer_id) amt=$($sale.amount)" -ForegroundColor Green
            } catch {
                $errBody = $_.ErrorDetails.Message
                Write-Host "  Sale FAILED: $errBody" -ForegroundColor Red
            }
        }
    }
} catch {
    Write-Host "  Sales seed FAILED: $($_.Exception.Message)" -ForegroundColor Red
}

# ── Verify all endpoints ──────────────────────────────────
Write-Host "`n=== Verify All Endpoints ===" -ForegroundColor Cyan

# Re-login for fresh token
$loginResp2 = Invoke-RestMethod -Uri "$API/auth/merchant/login" -Method POST -ContentType "application/json" -Body $loginBody
$token2 = $loginResp2.data.tokens.access_token
$headers2 = @{Authorization = "Bearer $token2"}

# 1. Stores with hours
Write-Host "`n--- Stores ---"
try {
    $r = Invoke-RestMethod -Uri "$API/merchants/stores" -Method GET -Headers $headers2
    foreach ($s in $r.data) {
        $hasHours = if ($s.opening_hours) { "YES" } else { "NO" }
        Write-Host "  Store $($s.id): $($s.store_name) | hours=$hasHours | coupons=$($s.active_coupons)" -ForegroundColor White
    }
} catch { Write-Host "  FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }

# 2. Customers
Write-Host "`n--- Customers ---"
try {
    $r = Invoke-RestMethod -Uri "$API/merchants/customers" -Method GET -Headers $headers2
    Write-Host "  Total: $($r.meta.total)" -ForegroundColor White
    foreach ($c in $r.data) {
        Write-Host "  Customer $($c.id): $($c.name) | phone=$($c.phone)" -ForegroundColor White
    }
} catch { Write-Host "  FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }

# 3. Flash discounts
Write-Host "`n--- Flash Discounts ---"
try {
    $r = Invoke-RestMethod -Uri "$API/merchants/flash-discounts" -Method GET -Headers $headers2
    Write-Host "  Count: $($r.data.Count)" -ForegroundColor White
    foreach ($d in $r.data) {
        Write-Host "  FD $($d.id): $($d.title) | $($d.discount_percentage)% | $($d.status)" -ForegroundColor White
    }
} catch { Write-Host "  FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }

# 4. Store coupons
Write-Host "`n--- Store Coupons ---"
try {
    $r = Invoke-RestMethod -Uri "$API/merchants/store-coupons" -Method GET -Headers $headers2
    Write-Host "  Total: $($r.meta.total)" -ForegroundColor White
    foreach ($sc in $r.data) {
        $gifted = if ($sc.is_gifted -eq 1) { "ASSIGNED to $($sc.gifted_to_name)" } else { "available" }
        Write-Host "  SC $($sc.id): $($sc.coupon_code) | $($sc.discount_type)=$($sc.discount_value) | $gifted" -ForegroundColor White
    }
} catch { Write-Host "  FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }

# 5. Platform coupons 
Write-Host "`n--- Platform Coupons ---"
try {
    $r = Invoke-RestMethod -Uri "$API/merchants/coupons" -Method GET -Headers $headers2
    Write-Host "  Count: $($r.data.Count)" -ForegroundColor White
    foreach ($cp in $r.data) {
        Write-Host "  Coupon $($cp.id): $($cp.title) | $($cp.coupon_code) | $($cp.status)" -ForegroundColor White
    }
} catch { Write-Host "  FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }

# 6. Customer detail & analytics (if we have customers)
Write-Host "`n--- Customer Detail & Analytics ---"
try {
    $custResp = Invoke-RestMethod -Uri "$API/merchants/customers" -Method GET -Headers $headers2
    if ($custResp.data.Count -gt 0) {
        $cid = $custResp.data[0].id
        try {
            $detail = Invoke-RestMethod -Uri "$API/merchants/customers/$cid" -Method GET -Headers $headers2
            Write-Host "  Detail for $($detail.data.name): phone=$($detail.data.phone)" -ForegroundColor White
        } catch { Write-Host "  Detail FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }
        
        try {
            $analytics = Invoke-RestMethod -Uri "$API/merchants/customers/$cid/analytics" -Method GET -Headers $headers2
            $a = $analytics.data
            Write-Host "  Analytics: txns=$($a.total_transactions) value=$($a.total_transaction_value) highest=$($a.highest_transaction) coupon_assigns=$($a.total_coupon_assignments)" -ForegroundColor White
        } catch { Write-Host "  Analytics FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }
    }
} catch { Write-Host "  FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }

# 7. Customer lookup
Write-Host "`n--- Customer Lookup ---"
try {
    $custResp = Invoke-RestMethod -Uri "$API/merchants/customers" -Method GET -Headers $headers2
    if ($custResp.data.Count -gt 0) {
        $phone = $custResp.data[0].phone
        $lookup = Invoke-RestMethod -Uri "$API/merchants/redemption/customer-lookup?search=$phone" -Method GET -Headers $headers2
        Write-Host "  Lookup by $phone : found $($lookup.data.customer.name), items=$($lookup.data.redeemable_items.Count)" -ForegroundColor White
        foreach ($item in $lookup.data.redeemable_items) {
            Write-Host "    - [$($item.type)] $($item.title) ($($item.discount_type)=$($item.discount_value))" -ForegroundColor Gray
        }
    }
} catch { Write-Host "  Lookup FAILED: $($_.ErrorDetails.Message)" -ForegroundColor Red }

Write-Host "`n=== ALL DONE ===" -ForegroundColor Cyan
