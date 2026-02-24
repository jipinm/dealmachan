<?php
/**
 * Deal Machan – Blog Image Downloader & DB Updater
 * Downloads Unsplash images for blog posts and updates the database.
 *
 * Run: php download-blog-images.php
 */

define('BLOG_DIR', __DIR__ . '/api/uploads/blog');

define('DB_HOST', 'localhost');
define('DB_NAME', 'deal_machan');
define('DB_USER', 'root');
define('DB_PASS', '');

// ── Helpers ──────────────────────────────────────────────────────────────────
function ensureDir(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "  Created dir: $path\n";
    }
}

function downloadImage(string $url, string $dest): bool {
    if (file_exists($dest)) {
        echo "  SKIP (exists): " . basename($dest) . "\n";
        return true;
    }
    $ctx  = stream_context_create(['http' => [
        'header'  => "User-Agent: Mozilla/5.0 (compatible; DealMachan/1.0)\r\n",
        'timeout' => 30,
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        echo "  FAIL: " . basename($dest) . " — $url\n";
        return false;
    }
    file_put_contents($dest, $data);
    $kb = round(filesize($dest) / 1024, 1);
    echo "  OK {$kb}KB : " . basename($dest) . "\n";
    return true;
}

function getDB(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

// ── Create upload directory ───────────────────────────────────────────────────
echo "\n[1/2] Creating blog upload directory...\n";
ensureDir(BLOG_DIR);

// ── Blog post images ──────────────────────────────────────────────────────────
// Each image is chosen to match the blog post topic.
// IDs match blog_posts table.
echo "\n[2/2] Downloading blog images...\n";

$blogImages = [
    // 1 - Top 10 Deals in Kochi This Week  (shopping / city)
    ['id' => 1,  'url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=800&h=420&fit=crop&q=80'],
    // 2 - How DealMachan Loyalty Cards Work  (membership / card)
    ['id' => 2,  'url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&h=420&fit=crop&q=80'],
    // 3 - Thiruvananthapuram Restaurant Guide  (restaurant / dining)
    ['id' => 3,  'url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=420&fit=crop&q=80'],
    // 4 - Onam Special Offers  (festive sweets / celebration)
    ['id' => 4,  'url' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800&h=420&fit=crop&q=80'],
    // 5 - New Partners Joining DealMachan  (business / handshake)
    ['id' => 5,  'url' => 'https://images.unsplash.com/photo-1521737852567-6949f3f9f2b5?w=800&h=420&fit=crop&q=80'],
    // 6 - Health and Wellness Deals  (fitness / gym)
    ['id' => 6,  'url' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=420&fit=crop&q=80'],
    // 7 - Travel Kerala with DealMachan  (Kerala backwaters / nature)
    ['id' => 7,  'url' => 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2?w=800&h=420&fit=crop&q=80'],
    // 8 - Jewellery Shopping Guide  (gold / jewellery)
    ['id' => 8,  'url' => 'https://images.unsplash.com/photo-1573408301185-9519f94cb5ea?w=800&h=420&fit=crop&q=80'],
    // 9 - DealMachan Referral Program  (reward / earning)
    ['id' => 9,  'url' => 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800&h=420&fit=crop&q=80'],
    // 10 - Best Electronics Deals  (electronics / gadgets)
    ['id' => 10, 'url' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=800&h=420&fit=crop&q=80'],
    // 11 - Upcoming DealMachan Events  (event / conference)
    ['id' => 11, 'url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&h=420&fit=crop&q=80'],
    // 12 - How Merchants Can Join  (store / retail)
    ['id' => 12, 'url' => 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800&h=420&fit=crop&q=80'],
    // 13 - Mystery Shopping Program  (incognito shopper)
    ['id' => 13, 'url' => 'https://images.unsplash.com/photo-1556742111-a301076d9d18?w=800&h=420&fit=crop&q=80'],
    // 14 - Kerala Food Festival  (food / street food)
    ['id' => 14, 'url' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&h=420&fit=crop&q=80'],
    // 15 - Student Discount Program  (campus / students)
    ['id' => 15, 'url' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&h=420&fit=crop&q=80'],
];

$dbUpdates = [];
foreach ($blogImages as $item) {
    $filename = "blog_{$item['id']}.jpg";
    $dest     = BLOG_DIR . '/' . $filename;
    downloadImage($item['url'], $dest);
    $dbUpdates[] = ['id' => $item['id'], 'value' => '/uploads/blog/' . $filename];
}

// ── Update database ───────────────────────────────────────────────────────────
echo "\n[DB] Updating blog_posts.featured_image...\n";

$db   = getDB();
$when = '';
foreach ($dbUpdates as $row) {
    $when .= " WHEN " . (int)$row['id'] . " THEN " . $db->quote($row['value']);
}
$ids    = implode(',', array_column($dbUpdates, 'id'));
$sql    = "UPDATE `blog_posts` SET `featured_image` = CASE `id` $when ELSE `featured_image` END WHERE `id` IN ($ids)";
$affected = $db->exec($sql);
echo "  Updated $affected row(s) in blog_posts.featured_image\n";

// ── Summary ───────────────────────────────────────────────────────────────────
$count = count(glob(BLOG_DIR . '/*.jpg'));
echo "\nDone! $count blog images in " . BLOG_DIR . "\n";
