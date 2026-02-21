<?php
$db = new PDO('mysql:host=localhost;dbname=deal_machan;charset=utf8mb4', 'root', '');
$logos   = $db->query("SELECT COUNT(*) FROM merchants WHERE business_logo LIKE '/uploads/%'")->fetchColumn();
$gallery = $db->query("SELECT COUNT(*) FROM store_gallery WHERE image_url LIKE '/uploads/%'")->fetchColumn();
$ads     = $db->query("SELECT COUNT(*) FROM advertisements WHERE media_url LIKE '/uploads/%'")->fetchColumn();
echo "Merchant logos  (local path): $logos / 29\n";
echo "Gallery images  (local path): $gallery / 119\n";
echo "Advertisement   (local path): $ads / 10\n";

// Check for any still-NULL logos
$nullLogos = $db->query("SELECT id, business_name FROM merchants WHERE business_logo IS NULL OR business_logo = ''")->fetchAll(PDO::FETCH_ASSOC);
if ($nullLogos) {
    echo "\nMerchants still missing logo:\n";
    foreach ($nullLogos as $r) echo "  id={$r['id']} {$r['business_name']}\n";
} else {
    echo "\nAll merchants have a logo path.\n";
}
