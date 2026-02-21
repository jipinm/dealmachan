<?php
/**
 * Retry failed images with alternative URLs.
 * DB paths are already correct — we just need the files on disk.
 */

define('LOGO_DIR',    __DIR__ . '/api/uploads/logos');
define('GALLERY_DIR', __DIR__ . '/api/public/uploads/gallery');
define('ADS_DIR',     __DIR__ . '/api/uploads/ads');

function downloadImage(string $url, string $dest): bool {
    if (file_exists($dest) && filesize($dest) > 5000) {
        echo "  SKIP: " . basename($dest) . "\n";
        return true;
    }
    $ctx  = stream_context_create(['http' => [
        'header'  => "User-Agent: Mozilla/5.0\r\n",
        'timeout' => 30,
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 5000) {
        echo "  FAIL: " . basename($dest) . "\n";
        return false;
    }
    file_put_contents($dest, $data);
    $kb = round(strlen($data) / 1024, 1);
    echo "  OK {$kb}KB : " . basename($dest) . "\n";
    return true;
}

// Picsum gives reliable, royalty-free photos seeded by a unique value.
// Format: https://picsum.photos/seed/{seed}/800/600
// Seeds are chosen to match context.

echo "\nRetrying failed logos...\n";
$failedLogos = [
    // id => [alternative Unsplash ID, or picsum fallback]
    10 => ['file'=>'merchant_10_logo.jpg', 'url'=>'https://picsum.photos/seed/jewellery-kochi/400/400'],         // Golden Jewels
    17 => ['file'=>'merchant_17_logo.jpg', 'url'=>'https://picsum.photos/seed/rubber-kottayam/400/400'],         // Kottayam Rubber
    21 => ['file'=>'merchant_21_logo.jpg', 'url'=>'https://picsum.photos/seed/autoparts-kochi/400/400'],         // Kochi Auto Parts
    25 => ['file'=>'merchant_25_logo.jpg', 'url'=>'https://picsum.photos/seed/tea-munnar/400/400'],              // Munnar Tea House
];
foreach ($failedLogos as $item) {
    downloadImage($item['url'], LOGO_DIR . '/' . $item['file']);
}

echo "\nRetrying failed gallery images...\n";
$failedGallery = [
    11  => 'https://picsum.photos/seed/sweets-shop-11/800/600',
    23  => 'https://picsum.photos/seed/electronics-23/800/600',
    28  => 'https://picsum.photos/seed/jewels-28/800/600',
    36  => 'https://picsum.photos/seed/rubber-warehouse-36/800/600',
    40  => 'https://picsum.photos/seed/goldpalace-swaraj/800/600',
    42  => 'https://picsum.photos/seed/jewellery-42/800/600',
    43  => 'https://picsum.photos/seed/jewellery-entrance-43/800/600',
    55  => 'https://picsum.photos/seed/autoparts-exterior/800/600',
    56  => 'https://picsum.photos/seed/workshop-interior/800/600',
    68  => 'https://picsum.photos/seed/opticals-68/800/600',
    69  => 'https://picsum.photos/seed/eyewear-69/800/600',
    72  => 'https://picsum.photos/seed/opticals-72/800/600',
    73  => 'https://picsum.photos/seed/teahouse-73/800/600',
    83  => 'https://picsum.photos/seed/backwaters-83/800/600',
    85  => 'https://picsum.photos/seed/houseboat-85/800/600',
    86  => 'https://picsum.photos/seed/canal-view-86/800/600',
    87  => 'https://picsum.photos/seed/spiceshop-87/800/600',
    89  => 'https://picsum.photos/seed/spices-display-89/800/600',
    115 => 'https://picsum.photos/seed/jewels-thrissur-115/800/600',
    116 => 'https://picsum.photos/seed/jewels-display-116/800/600',
];
foreach ($failedGallery as $id => $url) {
    downloadImage($url, GALLERY_DIR . '/gallery_' . $id . '.jpg');
}

echo "\nRetrying failed advertisement images...\n";
$failedAds = [
    6  => 'https://picsum.photos/seed/jewellery-festival-ad/1200/400',
    12 => 'https://picsum.photos/seed/christmas-carnival-ad/1200/400',
];
foreach ($failedAds as $id => $url) {
    downloadImage($url, ADS_DIR . '/ad_' . $id . '.jpg');
}

// Final count
$logoCount    = count(glob(LOGO_DIR    . '/*.jpg'));
$galleryCount = count(glob(GALLERY_DIR . '/*.jpg'));
$adsCount     = count(glob(ADS_DIR     . '/*.jpg'));
echo "\nDone!\n";
echo "  Logos   : $logoCount / 29\n";
echo "  Gallery : $galleryCount / 119\n";
echo "  Ads     : $adsCount / 10\n";
