-- ============================================================
-- Deal Machan - Image Seed Patch
-- Source: Unsplash (https://unsplash.com) - free to use
-- URL format: https://images.unsplash.com/photo-{id}?w={W}&h={H}&fit=crop&q=80
-- ============================================================

-- ============================================================
-- 1. MERCHANT BUSINESS LOGOS
-- All logos were NULL — populated with contextual Unsplash images
-- ============================================================

UPDATE `merchants` SET `business_logo` = CASE `id`
    -- Test Restaurant
    WHEN 1  THEN 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&h=400&fit=crop&q=80'
    -- Spice Garden Restaurant (Indian cuisine)
    WHEN 2  THEN 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=400&h=400&fit=crop&q=80'
    -- Royal Textiles & Sarees
    WHEN 3  THEN 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=400&h=400&fit=crop&q=80'
    -- Kerala Sweets Palace
    WHEN 4  THEN 'https://images.unsplash.com/photo-1603532648955-039310d9ed75?w=400&h=400&fit=crop&q=80'
    -- FitZone Gym & Spa
    WHEN 5  THEN 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&h=400&fit=crop&q=80'
    -- MedPlus Pharmacy
    WHEN 6  THEN 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=400&h=400&fit=crop&q=80'
    -- Cafe Mocha
    WHEN 7  THEN 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=400&h=400&fit=crop&q=80'
    -- Star Electronics Hub
    WHEN 8  THEN 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&h=400&fit=crop&q=80'
    -- Malabar Biriyani House
    WHEN 9  THEN 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=400&h=400&fit=crop&q=80'
    -- Golden Jewels Kochi
    WHEN 10 THEN 'https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=400&h=400&fit=crop&q=80'
    -- Ayur Wellness Centre
    WHEN 11 THEN 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=400&h=400&fit=crop&q=80'
    -- Surf Salon & Beauty
    WHEN 12 THEN 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400&h=400&fit=crop&q=80'
    -- Paradise Backwater Resort
    WHEN 13 THEN 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=400&h=400&fit=crop&q=80'
    -- Sunrise Bakers
    WHEN 14 THEN 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&h=400&fit=crop&q=80'
    -- TechMart Mobiles & Gadgets
    WHEN 15 THEN 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=400&fit=crop&q=80'
    -- Kerala Books & Stationery
    WHEN 16 THEN 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=400&h=400&fit=crop&q=80'
    -- Kottayam Rubber Traders
    WHEN 17 THEN 'https://images.unsplash.com/photo-1581094488379-6a10bef2b27b?w=400&h=400&fit=crop&q=80'
    -- Thrissur Gold Palace
    WHEN 18 THEN 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=400&h=400&fit=crop&q=80'
    -- Calicut Heritage Cafe
    WHEN 19 THEN 'https://images.unsplash.com/photo-1493770348161-369560ae357d?w=400&h=400&fit=crop&q=80'
    -- Kollam Sea Foods
    WHEN 20 THEN 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=400&h=400&fit=crop&q=80'
    -- Kochi Auto Parts World
    WHEN 21 THEN 'https://images.unsplash.com/photo-1617886903355-9354bb57100a?w=400&h=400&fit=crop&q=80'
    -- Palace Furniture Kochi
    WHEN 22 THEN 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=400&h=400&fit=crop&q=80'
    -- Green Valley Organics
    WHEN 23 THEN 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=400&fit=crop&q=80'
    -- Trivandrum Opticals
    WHEN 24 THEN 'https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=400&h=400&fit=crop&q=80'
    -- Munnar Tea House
    WHEN 25 THEN 'https://images.unsplash.com/photo-1564890369478-c89ca3d9caf6?w=400&h=400&fit=crop&q=80'
    -- Tattoo Art Studio Kochi
    WHEN 26 THEN 'https://images.unsplash.com/photo-1562259929-b4e1fd3aef09?w=400&h=400&fit=crop&q=80'
    -- Alappuzha Houseboat Tours
    WHEN 27 THEN 'https://images.unsplash.com/photo-1578469645742-46cae010e5d4?w=400&h=400&fit=crop&q=80'
    -- Kerala Heritage Spices
    WHEN 28 THEN 'https://images.unsplash.com/photo-1506368249639-73a05d6f6488?w=400&h=400&fit=crop&q=80'
    -- Demo Merchant
    WHEN 31 THEN 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400&h=400&fit=crop&q=80'
    ELSE `business_logo`
END
WHERE `id` IN (1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,31);

-- ============================================================
-- 2. STORE GALLERY IMAGES
-- All 119 rows replaced from placehold.co to real Unsplash images
-- Images are contextually matched to each store category
-- Each store's storefront / interior / product display use
-- different shots from the same category
-- ============================================================

UPDATE `store_gallery` SET `image_url` = CASE `id`

    -- ── Store 1: Test Restaurant - Andheri ──────────────────────
    WHEN 1   THEN 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 2   THEN 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&q=80'  -- interior

    -- ── Store 2: Spice Garden - Ernakulam ───────────────────────
    WHEN 3   THEN 'https://images.unsplash.com/photo-1505253758473-96b7015fcd40?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 4   THEN 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=600&fit=crop&q=80'  -- interior
    WHEN 5   THEN 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&h=600&fit=crop&q=80'  -- product

    -- ── Store 3: Spice Garden - MG Road ─────────────────────────
    WHEN 6   THEN 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 7   THEN 'https://images.unsplash.com/photo-1590846406792-0adc7f938f1d?w=800&h=600&fit=crop&q=80'  -- interior
    WHEN 8   THEN 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&h=600&fit=crop&q=80'   -- product (Kerala food spread)

    -- ── Store 4: Royal Textiles - Pattom ────────────────────────
    WHEN 9   THEN 'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 10  THEN 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=800&h=600&fit=crop&q=80'  -- interior

    -- ── Store 5: Kerala Sweets - Palayam ────────────────────────
    WHEN 11  THEN 'https://images.unsplash.com/photo-1606914707708-5efb2d9c8571?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 12  THEN 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=800&h=600&fit=crop&q=80'  -- interior
    WHEN 13  THEN 'https://images.unsplash.com/photo-1603532648955-039310d9ed75?w=800&h=600&fit=crop&q=80'  -- product (Indian sweets)

    -- ── Store 6: FitZone - Kakkanad ─────────────────────────────
    WHEN 14  THEN 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 15  THEN 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&h=600&fit=crop&q=80'  -- interior

    -- ── Store 7: MedPlus - Thampanoor ───────────────────────────
    WHEN 16  THEN 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 17  THEN 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&h=600&fit=crop&q=80'  -- interior
    WHEN 18  THEN 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=800&h=600&fit=crop&q=80'  -- product

    -- ── Store 8: Cafe Mocha - Vyttila ───────────────────────────
    WHEN 19  THEN 'https://images.unsplash.com/photo-1445116572660-236099ec97a0?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 20  THEN 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=800&h=600&fit=crop&q=80'  -- interior

    -- ── Store 9: Star Electronics - Ulloor ──────────────────────
    WHEN 21  THEN 'https://images.unsplash.com/photo-1551808525-51a94da548ce?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 22  THEN 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&h=600&fit=crop&q=80'  -- interior
    WHEN 23  THEN 'https://images.unsplash.com/photo-1540292898424-00c3a81cff60?w=800&h=600&fit=crop&q=80'  -- product

    -- ── Store 10: Malabar Biriyani - Mavoor ─────────────────────
    WHEN 24  THEN 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 25  THEN 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=800&h=600&fit=crop&q=80'  -- product (biryani)

    -- ── Store 11: Malabar Biriyani - Hilite ─────────────────────
    WHEN 26  THEN 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 27  THEN 'https://images.unsplash.com/photo-1633945274405-b6c8069047b0?w=800&h=600&fit=crop&q=80'  -- product (biryani close-up)

    -- ── Store 12: Golden Jewels - Marine Drive ──────────────────
    WHEN 28  THEN 'https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 29  THEN 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&h=600&fit=crop&q=80'  -- product (jewelry)

    -- ── Store 13: Paradise Resort - Alleppey ────────────────────
    WHEN 30  THEN 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 31  THEN 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop&q=80'  -- interior (pool)

    -- ── Store 14: Sunrise Bakers - Beach Road ───────────────────
    WHEN 32  THEN 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 33  THEN 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&h=600&fit=crop&q=80'  -- interior

    -- ── Store 15: Sunrise Bakers - Ramanattukara ────────────────
    WHEN 34  THEN 'https://images.unsplash.com/photo-1517433670267-08bbd4be890f?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 35  THEN 'https://images.unsplash.com/photo-1486427944299-d1955d23e34d?w=800&h=600&fit=crop&q=80'  -- product (bread/cake)

    -- ── Store 16: Kottayam Rubber - Baker Junction ──────────────
    WHEN 36  THEN 'https://images.unsplash.com/photo-1581094488379-6a10bef2b27b?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 37  THEN 'https://images.unsplash.com/photo-1553440569-bcc63803a83d?w=800&h=600&fit=crop&q=80'  -- interior (warehouse)

    -- ── Store 17: Kottayam Rubber - MC Road ─────────────────────
    WHEN 38  THEN 'https://images.unsplash.com/photo-1565793298595-6a879b1d9492?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 39  THEN 'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?w=800&h=600&fit=crop&q=80'  -- interior (industrial)

    -- ── Store 18: Thrissur Gold Palace - Swaraj ─────────────────
    WHEN 40  THEN 'https://images.unsplash.com/photo-1574602579010-7e71fcfb59b0?w=800&h=600&fit=crop&q=80'  -- storefront (jeweller)
    WHEN 41  THEN 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&h=600&fit=crop&q=80'  -- interior (display)
    WHEN 42  THEN 'https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=800&h=600&fit=crop&q=80'  -- product (gold jewelry)

    -- ── Store 19: Thrissur Gold Palace - East Fort ──────────────
    WHEN 43  THEN 'https://images.unsplash.com/photo-1601121141461-9d6647bef0a1?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 44  THEN 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=800&h=600&fit=crop&q=80'  -- product (jewellery)

    -- ── Store 20: Calicut Heritage Cafe - Mavoor ────────────────
    WHEN 45  THEN 'https://images.unsplash.com/photo-1445116572660-236099ec97a0?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 46  THEN 'https://images.unsplash.com/photo-1493770348161-369560ae357d?w=800&h=600&fit=crop&q=80'  -- interior (traditional cafe)
    WHEN 47  THEN 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&h=600&fit=crop&q=80'  -- product (coffee/tea)

    -- ── Store 21: Calicut Heritage Cafe - Palayam ───────────────
    WHEN 48  THEN 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 49  THEN 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=800&h=600&fit=crop&q=80'  -- interior
    WHEN 50  THEN 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=800&h=600&fit=crop&q=80'  -- product (tea cup)

    -- ── Store 22: Kollam Sea Foods - Chinnakada ─────────────────
    WHEN 51  THEN 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 52  THEN 'https://images.unsplash.com/photo-1559410545-0bdcd187e0a6?w=800&h=600&fit=crop&q=80'  -- product (seafood)

    -- ── Store 23: Kollam Sea Foods - Asramam ────────────────────
    WHEN 53  THEN 'https://images.unsplash.com/photo-1615361200141-f45040f367be?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 54  THEN 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=800&h=600&fit=crop&q=80'  -- product (fish market)

    -- ── Store 24: Kochi Auto Parts - Kakkanad ───────────────────
    WHEN 55  THEN 'https://images.unsplash.com/photo-1617886903355-9354bb57100a?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 56  THEN 'https://images.unsplash.com/photo-1567054810512-2e7f12a519b1?w=800&h=600&fit=crop&q=80'  -- interior (workshop)

    -- ── Store 25: Kochi Auto Parts - Edappally ──────────────────
    WHEN 57  THEN 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 58  THEN 'https://images.unsplash.com/photo-1530046339160-ce3e530c7d2f?w=800&h=600&fit=crop&q=80'  -- interior (parts shelf)

    -- ── Store 26: Palace Furniture - Marine Drive ───────────────
    WHEN 59  THEN 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800&h=600&fit=crop&q=80'  -- storefront (showroom)
    WHEN 60  THEN 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&h=600&fit=crop&q=80'  -- interior (living room)
    WHEN 61  THEN 'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=800&h=600&fit=crop&q=80'  -- product (bedroom)

    -- ── Store 27: Palace Furniture - Kaloor ─────────────────────
    WHEN 62  THEN 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 63  THEN 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?w=800&h=600&fit=crop&q=80'  -- interior (sofa)
    WHEN 64  THEN 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?w=800&h=600&fit=crop&q=80'  -- product (dining set)

    -- ── Store 28: Green Valley - Pattom ─────────────────────────
    WHEN 65  THEN 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 66  THEN 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=800&h=600&fit=crop&q=80'  -- product (vegetables)

    -- ── Store 29: Trivandrum Opticals - Kowdiar ─────────────────
    WHEN 67  THEN 'https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 68  THEN 'https://images.unsplash.com/photo-1516514179904-cb19c7b40ee0?w=800&h=600&fit=crop&q=80'  -- interior (eye clinic)
    WHEN 69  THEN 'https://images.unsplash.com/photo-1587816002578-eedddad17f39?w=800&h=600&fit=crop&q=80'  -- product (glasses)

    -- ── Store 30: Trivandrum Opticals - Thampanoor ──────────────
    WHEN 70  THEN 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 71  THEN 'https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=800&h=600&fit=crop&q=80'  -- interior
    WHEN 72  THEN 'https://images.unsplash.com/photo-1516514179904-cb19c7b40ee0?w=800&h=600&fit=crop&q=80'  -- product

    -- ── Store 31: Munnar Tea House - MG Road ────────────────────
    WHEN 73  THEN 'https://images.unsplash.com/photo-1564890369478-c89ca3d9caf6?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 74  THEN 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=800&h=600&fit=crop&q=80'  -- interior (tea brewing)
    WHEN 75  THEN 'https://images.unsplash.com/photo-1558160074-4d7d8bdf4256?w=800&h=600&fit=crop&q=80'  -- product (tea plantation)

    -- ── Store 32: Munnar Tea House - Ernakulam ──────────────────
    WHEN 76  THEN 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 77  THEN 'https://images.unsplash.com/photo-1546877625-cb8c71916608?w=800&h=600&fit=crop&q=80'  -- interior (tea cups)
    WHEN 78  THEN 'https://images.unsplash.com/photo-1563911892437-1feda0179e1b?w=800&h=600&fit=crop&q=80'  -- product (tea leaves)

    -- ── Store 33: Tattoo Art Studio - Vyttila ───────────────────
    WHEN 79  THEN 'https://images.unsplash.com/photo-1598371839696-5c5bb00bdc28?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 80  THEN 'https://images.unsplash.com/photo-1562259929-b4e1fd3aef09?w=800&h=600&fit=crop&q=80'  -- interior/art

    -- ── Store 34: Alappuzha Boats - Mullakkal ───────────────────
    WHEN 81  THEN 'https://images.unsplash.com/photo-1578469645742-46cae010e5d4?w=800&h=600&fit=crop&q=80'  -- storefront (waterfront)
    WHEN 82  THEN 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=800&h=600&fit=crop&q=80'  -- interior (houseboat deck)
    WHEN 83  THEN 'https://images.unsplash.com/photo-1525450022823-0afe02dfb0e2?w=800&h=600&fit=crop&q=80'  -- product (backwaters)

    -- ── Store 35: Alappuzha Boats - Iron Bridge ─────────────────
    WHEN 84  THEN 'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=800&h=600&fit=crop&q=80'  -- storefront (jetty)
    WHEN 85  THEN 'https://images.unsplash.com/photo-1566233440040-b65bab89a18d?w=800&h=600&fit=crop&q=80'  -- interior (boat cabin)
    WHEN 86  THEN 'https://images.unsplash.com/photo-1565799557186-bdfcdb7fed78?w=800&h=600&fit=crop&q=80'  -- product (canal view)

    -- ── Store 36: Kerala Spices - Ernakulam ─────────────────────
    WHEN 87  THEN 'https://images.unsplash.com/photo-1607830543990-25b40d7e7ee8?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 88  THEN 'https://images.unsplash.com/photo-1506368249639-73a05d6f6488?w=800&h=600&fit=crop&q=80'  -- interior (spice display)
    WHEN 89  THEN 'https://images.unsplash.com/photo-1599909631678-940fed21d1ee?w=800&h=600&fit=crop&q=80'  -- product (spices)

    -- ── Store 37: Kerala Spices - Mavoor Road ───────────────────
    WHEN 90  THEN 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 91  THEN 'https://images.unsplash.com/photo-1532336414038-cf19250c5757?w=800&h=600&fit=crop&q=80'  -- product (pepper/cardamom)

    -- ── Store 38: Royal Textiles - MG Road ──────────────────────
    WHEN 92  THEN 'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 93  THEN 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&h=600&fit=crop&q=80'  -- interior (clothing)
    WHEN 94  THEN 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=800&h=600&fit=crop&q=80'  -- product (saree/fabric)

    -- ── Store 39: Kerala Sweets - TVM ───────────────────────────
    WHEN 95  THEN 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 96  THEN 'https://images.unsplash.com/photo-1603532648955-039310d9ed75?w=800&h=600&fit=crop&q=80'  -- interior (sweet display)
    WHEN 97  THEN 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=800&h=600&fit=crop&q=80'  -- product (mithai)

    -- ── Store 40: Kerala Sweets - Kochi ─────────────────────────
    WHEN 98  THEN 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 99  THEN 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=800&h=600&fit=crop&q=80'  -- interior
    WHEN 100 THEN 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800&h=600&fit=crop&q=80'  -- product (sweets)

    -- ── Store 41: FitZone - Pattom ──────────────────────────────
    WHEN 101 THEN 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&h=600&fit=crop&q=80'  -- storefront (gym exterior)
    WHEN 102 THEN 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&h=600&fit=crop&q=80'  -- interior

    -- ── Store 42: MedPlus - MG Road Kochi ───────────────────────
    WHEN 103 THEN 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 104 THEN 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&h=600&fit=crop&q=80'  -- interior

    -- ── Store 43: Cafe Mocha - Marine Drive ─────────────────────
    WHEN 105 THEN 'https://images.unsplash.com/photo-1525610553991-2bede1a236e2?w=800&h=600&fit=crop&q=80'  -- storefront (cafe window)
    WHEN 106 THEN 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&h=600&fit=crop&q=80'  -- product (coffee)

    -- ── Store 44: Star Electronics - Kakkanad ───────────────────
    WHEN 107 THEN 'https://images.unsplash.com/photo-1468495244123-6c6c332eeece?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 108 THEN 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&h=600&fit=crop&q=80'  -- interior

    -- ── Store 45: Star Electronics - Thrissur ───────────────────
    WHEN 109 THEN 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 110 THEN 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?w=800&h=600&fit=crop&q=80'  -- interior (phone store)
    WHEN 111 THEN 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&h=600&fit=crop&q=80'  -- product (smartphone)

    -- ── Store 46: Malabar Biriyani - Beach Road ──────────────────
    WHEN 112 THEN 'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 113 THEN 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop&q=80'  -- interior (dining)
    WHEN 114 THEN 'https://images.unsplash.com/photo-1633945274405-b6c8069047b0?w=800&h=600&fit=crop&q=80'  -- product (biryani)

    -- ── Store 47: Golden Jewels - Thrissur ──────────────────────
    WHEN 115 THEN 'https://images.unsplash.com/photo-1601121141461-9d6647bef0a1?w=800&h=600&fit=crop&q=80'  -- storefront
    WHEN 116 THEN 'https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=800&h=600&fit=crop&q=80'  -- interior (jewelry display)
    WHEN 117 THEN 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&h=600&fit=crop&q=80'  -- product (gold pieces)

    -- ── Store 48: Sunrise Bakers - Palayam ──────────────────────
    WHEN 118 THEN 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=800&h=600&fit=crop&q=80'  -- storefront (cake shop)
    WHEN 119 THEN 'https://images.unsplash.com/photo-1486427944299-d1955d23e34d?w=800&h=600&fit=crop&q=80'  -- product (bread/pastry)

    ELSE `image_url`
END
WHERE `id` BETWEEN 1 AND 119;

-- ============================================================
-- 3. ADVERTISEMENT MEDIA URLs
-- Update from local paths to real Unsplash images
-- ============================================================

UPDATE `advertisements` SET `media_url` = CASE `id`
    WHEN 1  THEN 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&h=400&fit=crop&q=80'  -- Onam Bumper Sale (festive shopping)
    WHEN 2  THEN 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=400&fit=crop&q=80'  -- New Partner Welcome (handshake/business)
    WHEN 4  THEN 'https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=1200&h=400&fit=crop&q=80'  -- TVM Deals (city deals)
    WHEN 5  THEN 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&h=400&fit=crop&q=80'  -- Kochi Restaurant Week
    WHEN 6  THEN 'https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=1200&h=400&fit=crop&q=80'  -- Gold Jewellery Festival
    WHEN 7  THEN 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1200&h=400&fit=crop&q=80'  -- Fitness Club Promo
    WHEN 8  THEN 'https://images.unsplash.com/photo-1556742111-a301076d9d18?w=1200&h=400&fit=crop&q=80'  -- Referral Earn Now (rewards)
    WHEN 10 THEN 'https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=1200&h=400&fit=crop&q=80'  -- Monsoon Flash Sales
    WHEN 11 THEN 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&h=400&fit=crop&q=80'  -- Vishu Special Offers (festive)
    WHEN 12 THEN 'https://images.unsplash.com/photo-1511895426328-dc8714191011?w=1200&h=400&fit=crop&q=80'  -- Christmas Carnival
    ELSE `media_url`
END
WHERE `id` IN (1,2,4,5,6,7,8,10,11,12);
