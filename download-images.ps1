# ============================================================
# Deal Machan - Image Downloader & DB Updater
# Downloads Unsplash images to local storage and updates DB
# ============================================================

$ErrorActionPreference = 'Stop'

# ── Paths ────────────────────────────────────────────────────
$logoDir   = "e:\DealMachan\api\uploads\logos"
$galleryDir= "e:\DealMachan\api\public\uploads\gallery"
$adsDir    = "e:\DealMachan\api\uploads\ads"

# ── DB credentials ───────────────────────────────────────────
$dbHost = "localhost"
$dbName = "deal_machan"
$dbUser = "root"
$dbPass = ""

# ── Helpers ──────────────────────────────────────────────────
function Ensure-Dir($path) {
    if (-not (Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
        Write-Host "  Created dir: $path" -ForegroundColor DarkGray
    }
}

function Download-Image($url, $dest) {
    if (Test-Path $dest) {
        Write-Host "  SKIP (exists): $(Split-Path $dest -Leaf)" -ForegroundColor DarkGray
        return $true
    }
    try {
        $wc = New-Object System.Net.WebClient
        $wc.Headers.Add("User-Agent", "Mozilla/5.0")
        $wc.DownloadFile($url, $dest)
        $size = [math]::Round((Get-Item $dest).Length / 1KB, 1)
        Write-Host "  OK $($size)KB : $(Split-Path $dest -Leaf)" -ForegroundColor Green
        return $true
    } catch {
        Write-Host "  FAIL: $(Split-Path $dest -Leaf) — $_" -ForegroundColor Red
        return $false
    }
}

function Run-SQL($sql) {
    $args = @("-h$dbHost", "-u$dbUser", $dbName, "-e", $sql)
    if ($dbPass -ne "") { $args = @("-h$dbHost", "-u$dbUser", "-p$dbPass", $dbName, "-e", $sql) }
    & mysql @args 2>&1
}

# ── Create directories ────────────────────────────────────────
Write-Host "`n[1/4] Creating upload directories..." -ForegroundColor Cyan
Ensure-Dir $logoDir
Ensure-Dir $galleryDir
Ensure-Dir $adsDir

# ============================================================
# MERCHANT LOGOS
# filename: merchant_{id}_logo.jpg
# DB path:  /uploads/logos/merchant_{id}_logo.jpg
# ============================================================
Write-Host "`n[2/4] Downloading merchant logos..." -ForegroundColor Cyan

$logos = @(
    @{ id=1;  url='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&h=400&fit=crop&q=80' },
    @{ id=2;  url='https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=400&h=400&fit=crop&q=80' },
    @{ id=3;  url='https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=400&h=400&fit=crop&q=80' },
    @{ id=4;  url='https://images.unsplash.com/photo-1603532648955-039310d9ed75?w=400&h=400&fit=crop&q=80' },
    @{ id=5;  url='https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&h=400&fit=crop&q=80' },
    @{ id=6;  url='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=400&h=400&fit=crop&q=80' },
    @{ id=7;  url='https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=400&h=400&fit=crop&q=80' },
    @{ id=8;  url='https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=400&fit=crop&q=80' },
    @{ id=9;  url='https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=400&h=400&fit=crop&q=80' },
    @{ id=10; url='https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=400&h=400&fit=crop&q=80' },
    @{ id=11; url='https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=400&h=400&fit=crop&q=80' },
    @{ id=12; url='https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400&h=400&fit=crop&q=80' },
    @{ id=13; url='https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=400&h=400&fit=crop&q=80' },
    @{ id=14; url='https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&h=400&fit=crop&q=80' },
    @{ id=15; url='https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop&q=80' },
    @{ id=16; url='https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=400&h=400&fit=crop&q=80' },
    @{ id=17; url='https://images.unsplash.com/photo-1581094488379-6a10bef2b27b?w=400&h=400&fit=crop&q=80' },
    @{ id=18; url='https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=400&h=400&fit=crop&q=80' },
    @{ id=19; url='https://images.unsplash.com/photo-1493770348161-369560ae357d?w=400&h=400&fit=crop&q=80' },
    @{ id=20; url='https://images.unsplash.com/photo-1534482421-64566f976cfa?w=400&h=400&fit=crop&q=80' },
    @{ id=21; url='https://images.unsplash.com/photo-1617886903355-9354bb57100a?w=400&h=400&fit=crop&q=80' },
    @{ id=22; url='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=400&h=400&fit=crop&q=80' },
    @{ id=23; url='https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=400&fit=crop&q=80' },
    @{ id=24; url='https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=400&h=400&fit=crop&q=80' },
    @{ id=25; url='https://images.unsplash.com/photo-1564890369478-c89ca3d9caf6?w=400&h=400&fit=crop&q=80' },
    @{ id=26; url='https://images.unsplash.com/photo-1562259929-b4e1fd3aef09?w=400&h=400&fit=crop&q=80' },
    @{ id=27; url='https://images.unsplash.com/photo-1578469645742-46cae010e5d4?w=400&h=400&fit=crop&q=80' },
    @{ id=28; url='https://images.unsplash.com/photo-1506368249639-73a05d6f6488?w=400&h=400&fit=crop&q=80' },
    @{ id=31; url='https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400&h=400&fit=crop&q=80' }
)

$logoSql = "UPDATE merchants SET business_logo = CASE id`n"
foreach ($item in $logos) {
    $filename = "merchant_$($item.id)_logo.jpg"
    $dest     = Join-Path $logoDir $filename
    Download-Image $item.url $dest | Out-Null
    $logoSql += "  WHEN $($item.id) THEN '/uploads/logos/$filename'`n"
}
$logoSql += "  ELSE business_logo END WHERE id IN ($($logos.id -join ','));"

# ============================================================
# STORE GALLERY
# filename: gallery_{id}.jpg
# DB path:  /uploads/gallery/gallery_{id}.jpg
# ============================================================
Write-Host "`n[3/4] Downloading gallery images..." -ForegroundColor Cyan

$gallery = @(
    # Store 1 - Test Restaurant
    @{ id=1;   url='https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=800&h=600&fit=crop&q=80' },
    @{ id=2;   url='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&q=80' },
    # Store 2 - Spice Garden Ernakulam
    @{ id=3;   url='https://images.unsplash.com/photo-1505253758473-96b7015fcd40?w=800&h=600&fit=crop&q=80' },
    @{ id=4;   url='https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=600&fit=crop&q=80' },
    @{ id=5;   url='https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&h=600&fit=crop&q=80' },
    # Store 3 - Spice Garden MG Road
    @{ id=6;   url='https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&h=600&fit=crop&q=80' },
    @{ id=7;   url='https://images.unsplash.com/photo-1590846406792-0adc7f938f1d?w=800&h=600&fit=crop&q=80' },
    @{ id=8;   url='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&h=600&fit=crop&q=80' },
    # Store 4 - Royal Textiles Pattom
    @{ id=9;   url='https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=800&h=600&fit=crop&q=80' },
    @{ id=10;  url='https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=800&h=600&fit=crop&q=80' },
    # Store 5 - Kerala Sweets Palayam
    @{ id=11;  url='https://images.unsplash.com/photo-1606914707708-5efb2d9c8571?w=800&h=600&fit=crop&q=80' },
    @{ id=12;  url='https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=800&h=600&fit=crop&q=80' },
    @{ id=13;  url='https://images.unsplash.com/photo-1603532648955-039310d9ed75?w=800&h=600&fit=crop&q=80' },
    # Store 6 - FitZone Kakkanad
    @{ id=14;  url='https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=600&fit=crop&q=80' },
    @{ id=15;  url='https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&h=600&fit=crop&q=80' },
    # Store 7 - MedPlus Thampanoor
    @{ id=16;  url='https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=800&h=600&fit=crop&q=80' },
    @{ id=17;  url='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&h=600&fit=crop&q=80' },
    @{ id=18;  url='https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=800&h=600&fit=crop&q=80' },
    # Store 8 - Cafe Mocha Vyttila
    @{ id=19;  url='https://images.unsplash.com/photo-1445116572660-236099ec97a0?w=800&h=600&fit=crop&q=80' },
    @{ id=20;  url='https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=800&h=600&fit=crop&q=80' },
    # Store 9 - Star Electronics Ulloor
    @{ id=21;  url='https://images.unsplash.com/photo-1551808525-51a94da548ce?w=800&h=600&fit=crop&q=80' },
    @{ id=22;  url='https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&h=600&fit=crop&q=80' },
    @{ id=23;  url='https://images.unsplash.com/photo-1540292898424-00c3a81cff60?w=800&h=600&fit=crop&q=80' },
    # Store 10 - Malabar Biriyani Mavoor
    @{ id=24;  url='https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&h=600&fit=crop&q=80' },
    @{ id=25;  url='https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=800&h=600&fit=crop&q=80' },
    # Store 11 - Malabar Biriyani Hilite
    @{ id=26;  url='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&q=80' },
    @{ id=27;  url='https://images.unsplash.com/photo-1633945274405-b6c8069047b0?w=800&h=600&fit=crop&q=80' },
    # Store 12 - Golden Jewels Marine Drive
    @{ id=28;  url='https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=800&h=600&fit=crop&q=80' },
    @{ id=29;  url='https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&h=600&fit=crop&q=80' },
    # Store 13 - Paradise Resort Alleppey
    @{ id=30;  url='https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=800&h=600&fit=crop&q=80' },
    @{ id=31;  url='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop&q=80' },
    # Store 14 - Sunrise Bakers Beach Road
    @{ id=32;  url='https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=800&h=600&fit=crop&q=80' },
    @{ id=33;  url='https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&h=600&fit=crop&q=80' },
    # Store 15 - Sunrise Bakers Ramanattukara
    @{ id=34;  url='https://images.unsplash.com/photo-1517433670267-08bbd4be890f?w=800&h=600&fit=crop&q=80' },
    @{ id=35;  url='https://images.unsplash.com/photo-1486427944299-d1955d23e34d?w=800&h=600&fit=crop&q=80' },
    # Store 16 - Kottayam Rubber Baker Junction
    @{ id=36;  url='https://images.unsplash.com/photo-1581094488379-6a10bef2b27b?w=800&h=600&fit=crop&q=80' },
    @{ id=37;  url='https://images.unsplash.com/photo-1553440569-bcc63803a83d?w=800&h=600&fit=crop&q=80' },
    # Store 17 - Kottayam Rubber MC Road
    @{ id=38;  url='https://images.unsplash.com/photo-1565793298595-6a879b1d9492?w=800&h=600&fit=crop&q=80' },
    @{ id=39;  url='https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?w=800&h=600&fit=crop&q=80' },
    # Store 18 - Thrissur Gold Swaraj
    @{ id=40;  url='https://images.unsplash.com/photo-1574602579010-7e71fcfb59b0?w=800&h=600&fit=crop&q=80' },
    @{ id=41;  url='https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&h=600&fit=crop&q=80' },
    @{ id=42;  url='https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=800&h=600&fit=crop&q=80' },
    # Store 19 - Thrissur Gold East Fort
    @{ id=43;  url='https://images.unsplash.com/photo-1601121141461-9d6647bef0a1?w=800&h=600&fit=crop&q=80' },
    @{ id=44;  url='https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=800&h=600&fit=crop&q=80' },
    # Store 20 - Calicut Heritage Cafe Mavoor
    @{ id=45;  url='https://images.unsplash.com/photo-1445116572660-236099ec97a0?w=800&h=600&fit=crop&q=80' },
    @{ id=46;  url='https://images.unsplash.com/photo-1493770348161-369560ae357d?w=800&h=600&fit=crop&q=80' },
    @{ id=47;  url='https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&h=600&fit=crop&q=80' },
    # Store 21 - Calicut Heritage Cafe Palayam
    @{ id=48;  url='https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=800&h=600&fit=crop&q=80' },
    @{ id=49;  url='https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=800&h=600&fit=crop&q=80' },
    @{ id=50;  url='https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=800&h=600&fit=crop&q=80' },
    # Store 22 - Kollam Sea Foods Chinnakada
    @{ id=51;  url='https://images.unsplash.com/photo-1534482421-64566f976cfa?w=800&h=600&fit=crop&q=80' },
    @{ id=52;  url='https://images.unsplash.com/photo-1559410545-0bdcd187e0a6?w=800&h=600&fit=crop&q=80' },
    # Store 23 - Kollam Sea Foods Asramam
    @{ id=53;  url='https://images.unsplash.com/photo-1615361200141-f45040f367be?w=800&h=600&fit=crop&q=80' },
    @{ id=54;  url='https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=800&h=600&fit=crop&q=80' },
    # Store 24 - Kochi Auto Parts Kakkanad
    @{ id=55;  url='https://images.unsplash.com/photo-1617886903355-9354bb57100a?w=800&h=600&fit=crop&q=80' },
    @{ id=56;  url='https://images.unsplash.com/photo-1567054810512-2e7f12a519b1?w=800&h=600&fit=crop&q=80' },
    # Store 25 - Kochi Auto Parts Edappally
    @{ id=57;  url='https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop&q=80' },
    @{ id=58;  url='https://images.unsplash.com/photo-1530046339160-ce3e530c7d2f?w=800&h=600&fit=crop&q=80' },
    # Store 26 - Palace Furniture Marine Drive
    @{ id=59;  url='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800&h=600&fit=crop&q=80' },
    @{ id=60;  url='https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=600&fit=crop&q=80' },
    @{ id=61;  url='https://images.unsplash.com/photo-1540518614846-7eded433c457?w=800&h=600&fit=crop&q=80' },
    # Store 27 - Palace Furniture Kaloor
    @{ id=62;  url='https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=800&h=600&fit=crop&q=80' },
    @{ id=63;  url='https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?w=800&h=600&fit=crop&q=80' },
    @{ id=64;  url='https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?w=800&h=600&fit=crop&q=80' },
    # Store 28 - Green Valley Pattom
    @{ id=65;  url='https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&h=600&fit=crop&q=80' },
    @{ id=66;  url='https://images.unsplash.com/photo-1540420773420-3366772f4999?w=800&h=600&fit=crop&q=80' },
    # Store 29 - Trivandrum Opticals Kowdiar
    @{ id=67;  url='https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=800&h=600&fit=crop&q=80' },
    @{ id=68;  url='https://images.unsplash.com/photo-1516514179904-cb19c7b40ee0?w=800&h=600&fit=crop&q=80' },
    @{ id=69;  url='https://images.unsplash.com/photo-1587816002578-eedddad17f39?w=800&h=600&fit=crop&q=80' },
    # Store 30 - Trivandrum Opticals Thampanoor
    @{ id=70;  url='https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&h=600&fit=crop&q=80' },
    @{ id=71;  url='https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=800&h=600&fit=crop&q=80' },
    @{ id=72;  url='https://images.unsplash.com/photo-1516514179904-cb19c7b40ee0?w=800&h=600&fit=crop&q=80' },
    # Store 31 - Munnar Tea House MG Road
    @{ id=73;  url='https://images.unsplash.com/photo-1564890369478-c89ca3d9caf6?w=800&h=600&fit=crop&q=80' },
    @{ id=74;  url='https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=800&h=600&fit=crop&q=80' },
    @{ id=75;  url='https://images.unsplash.com/photo-1558160074-4d7d8bdf4256?w=800&h=600&fit=crop&q=80' },
    # Store 32 - Munnar Tea House Ernakulam
    @{ id=76;  url='https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=800&h=600&fit=crop&q=80' },
    @{ id=77;  url='https://images.unsplash.com/photo-1546877625-cb8c71916608?w=800&h=600&fit=crop&q=80' },
    @{ id=78;  url='https://images.unsplash.com/photo-1563911892437-1feda0179e1b?w=800&h=600&fit=crop&q=80' },
    # Store 33 - Tattoo Art Studio
    @{ id=79;  url='https://images.unsplash.com/photo-1598371839696-5c5bb00bdc28?w=800&h=600&fit=crop&q=80' },
    @{ id=80;  url='https://images.unsplash.com/photo-1562259929-b4e1fd3aef09?w=800&h=600&fit=crop&q=80' },
    # Store 34 - Alappuzha Boats Mullakkal
    @{ id=81;  url='https://images.unsplash.com/photo-1578469645742-46cae010e5d4?w=800&h=600&fit=crop&q=80' },
    @{ id=82;  url='https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=800&h=600&fit=crop&q=80' },
    @{ id=83;  url='https://images.unsplash.com/photo-1525450022823-0afe02dfb0e2?w=800&h=600&fit=crop&q=80' },
    # Store 35 - Alappuzha Boats Iron Bridge
    @{ id=84;  url='https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=800&h=600&fit=crop&q=80' },
    @{ id=85;  url='https://images.unsplash.com/photo-1566233440040-b65bab89a18d?w=800&h=600&fit=crop&q=80' },
    @{ id=86;  url='https://images.unsplash.com/photo-1565799557186-bdfcdb7fed78?w=800&h=600&fit=crop&q=80' },
    # Store 36 - Kerala Spices Ernakulam
    @{ id=87;  url='https://images.unsplash.com/photo-1607830543990-25b40d7e7ee8?w=800&h=600&fit=crop&q=80' },
    @{ id=88;  url='https://images.unsplash.com/photo-1506368249639-73a05d6f6488?w=800&h=600&fit=crop&q=80' },
    @{ id=89;  url='https://images.unsplash.com/photo-1599909631678-940fed21d1ee?w=800&h=600&fit=crop&q=80' },
    # Store 37 - Kerala Spices Mavoor Road
    @{ id=90;  url='https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=800&h=600&fit=crop&q=80' },
    @{ id=91;  url='https://images.unsplash.com/photo-1532336414038-cf19250c5757?w=800&h=600&fit=crop&q=80' },
    # Store 38 - Royal Textiles MG Road
    @{ id=92;  url='https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=800&h=600&fit=crop&q=80' },
    @{ id=93;  url='https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&h=600&fit=crop&q=80' },
    @{ id=94;  url='https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=800&h=600&fit=crop&q=80' },
    # Store 39 - Kerala Sweets TVM
    @{ id=95;  url='https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&h=600&fit=crop&q=80' },
    @{ id=96;  url='https://images.unsplash.com/photo-1603532648955-039310d9ed75?w=800&h=600&fit=crop&q=80' },
    @{ id=97;  url='https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=800&h=600&fit=crop&q=80' },
    # Store 40 - Kerala Sweets Kochi
    @{ id=98;  url='https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&h=600&fit=crop&q=80' },
    @{ id=99;  url='https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=800&h=600&fit=crop&q=80' },
    @{ id=100; url='https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800&h=600&fit=crop&q=80' },
    # Store 41 - FitZone Pattom
    @{ id=101; url='https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=600&fit=crop&q=80' },
    @{ id=102; url='https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&h=600&fit=crop&q=80' },
    # Store 42 - MedPlus MG Road Kochi
    @{ id=103; url='https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=800&h=600&fit=crop&q=80' },
    @{ id=104; url='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&h=600&fit=crop&q=80' },
    # Store 43 - Cafe Mocha Marine Drive
    @{ id=105; url='https://images.unsplash.com/photo-1525610553991-2bede1a236e2?w=800&h=600&fit=crop&q=80' },
    @{ id=106; url='https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&h=600&fit=crop&q=80' },
    # Store 44 - Star Electronics Kakkanad
    @{ id=107; url='https://images.unsplash.com/photo-1468495244123-6c6c332eeece?w=800&h=600&fit=crop&q=80' },
    @{ id=108; url='https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&h=600&fit=crop&q=80' },
    # Store 45 - Star Electronics Thrissur
    @{ id=109; url='https://images.unsplash.com/photo-1498049794561-7780e7231661?w=800&h=600&fit=crop&q=80' },
    @{ id=110; url='https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?w=800&h=600&fit=crop&q=80' },
    @{ id=111; url='https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&h=600&fit=crop&q=80' },
    # Store 46 - Malabar Biriyani Beach Road
    @{ id=112; url='https://images.unsplash.com/photo-1552566626-52f8b828add9?w=800&h=600&fit=crop&q=80' },
    @{ id=113; url='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&q=80' },
    @{ id=114; url='https://images.unsplash.com/photo-1633945274405-b6c8069047b0?w=800&h=600&fit=crop&q=80' },
    # Store 47 - Golden Jewels Thrissur
    @{ id=115; url='https://images.unsplash.com/photo-1601121141461-9d6647bef0a1?w=800&h=600&fit=crop&q=80' },
    @{ id=116; url='https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=800&h=600&fit=crop&q=80' },
    @{ id=117; url='https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&h=600&fit=crop&q=80' },
    # Store 48 - Sunrise Bakers Palayam
    @{ id=118; url='https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=800&h=600&fit=crop&q=80' },
    @{ id=119; url='https://images.unsplash.com/photo-1486427944299-d1955d23e34d?w=800&h=600&fit=crop&q=80' }
)

$gallerySql = "UPDATE store_gallery SET image_url = CASE id`n"
foreach ($item in $gallery) {
    $filename = "gallery_$($item.id).jpg"
    $dest     = Join-Path $galleryDir $filename
    Download-Image $item.url $dest | Out-Null
    $gallerySql += "  WHEN $($item.id) THEN '/uploads/gallery/$filename'`n"
}
$gallerySql += "  ELSE image_url END WHERE id BETWEEN 1 AND 119;"

# ============================================================
# ADVERTISEMENTS
# filename: ad_{id}.jpg
# DB path:  /uploads/ads/ad_{id}.jpg
# ============================================================
Write-Host "`n[4/4] Downloading advertisement images..." -ForegroundColor Cyan

$ads = @(
    @{ id=1;  url='https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&h=400&fit=crop&q=80' },
    @{ id=2;  url='https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=400&fit=crop&q=80' },
    @{ id=4;  url='https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=1200&h=400&fit=crop&q=80' },
    @{ id=5;  url='https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&h=400&fit=crop&q=80' },
    @{ id=6;  url='https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=1200&h=400&fit=crop&q=80' },
    @{ id=7;  url='https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1200&h=400&fit=crop&q=80' },
    @{ id=8;  url='https://images.unsplash.com/photo-1556742111-a301076d9d18?w=1200&h=400&fit=crop&q=80' },
    @{ id=10; url='https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=1200&h=400&fit=crop&q=80' },
    @{ id=11; url='https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&h=400&fit=crop&q=80' },
    @{ id=12; url='https://images.unsplash.com/photo-1511895426328-dc8714191011?w=1200&h=400&fit=crop&q=80' }
)

$adIds  = $ads.id -join ','
$adSql  = "UPDATE advertisements SET media_url = CASE id`n"
foreach ($item in $ads) {
    $filename = "ad_$($item.id).jpg"
    $dest     = Join-Path $adsDir $filename
    Download-Image $item.url $dest | Out-Null
    $adSql += "  WHEN $($item.id) THEN '/uploads/ads/$filename'`n"
}
$adSql += "  ELSE media_url END WHERE id IN ($adIds);"

# ============================================================
# UPDATE DATABASE
# ============================================================
Write-Host "`n[DB] Updating merchant logos..." -ForegroundColor Cyan
Run-SQL $logoSql

Write-Host "[DB] Updating store gallery..." -ForegroundColor Cyan
Run-SQL $gallerySql

Write-Host "[DB] Updating advertisements..." -ForegroundColor Cyan
Run-SQL $adSql

Write-Host "`nDone! Summary:" -ForegroundColor Green
Write-Host "  Logos    : $(Get-ChildItem $logoDir   -File | Measure-Object | Select-Object -Exp Count) files in $logoDir"
Write-Host "  Gallery  : $(Get-ChildItem $galleryDir -File | Measure-Object | Select-Object -Exp Count) files in $galleryDir"
Write-Host "  Ads      : $(Get-ChildItem $adsDir     -File | Measure-Object | Select-Object -Exp Count) files in $adsDir"
