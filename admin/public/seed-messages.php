<?php
/**
 * Seed Messages & Notifications
 * Run once: http://dealmachan-admin.local/seed-messages.php
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';

$db  = Database::getInstance()->getConnection();
$now = date('Y-m-d H:i:s');

// ---------------------------------------------------------------
// 1. Fetch admin IDs
// ---------------------------------------------------------------
$admins = $db->query("SELECT id, name, admin_type FROM admins ORDER BY id LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
if (count($admins) < 2) {
    die("<b>Error:</b> Need at least 2 admins in the DB. Run setup-admin first.");
}

$superAdmin  = null;
$otherAdmins = [];
foreach ($admins as $a) {
    if ($a['admin_type'] === 'super_admin' && !$superAdmin) $superAdmin = $a;
    else $otherAdmins[] = $a;
}
if (!$superAdmin) $superAdmin = $admins[0];
if (empty($otherAdmins)) $otherAdmins = array_slice($admins, 1);

$aId = $superAdmin['id']; // main recipient

// ---------------------------------------------------------------
// 2. Fetch a merchant & customer ID (optional, won't fail)
// ---------------------------------------------------------------
$merchant = $db->query("SELECT id, business_name FROM merchants WHERE profile_status='approved' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$customer = $db->query("SELECT id, name FROM customers LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// ---------------------------------------------------------------
// 3. Seed Messages
// ---------------------------------------------------------------
echo "<h3>Seeding Messages...</h3>";

$messages = [];

// Admin → Super Admin (unread)
$messages[] = [
    'sender_id'         => $otherAdmins[0]['id'] ?? $aId,
    'sender_type'       => 'admin',
    'receiver_id'       => $aId,
    'receiver_type'     => 'admin',
    'subject'           => 'Monthly Report Ready',
    'message_text'      => "Hi,\n\nThe monthly performance report for October is now ready. Please review the attached summary and let me know if any changes are needed before it goes to management.\n\nRegards,\n" . ($otherAdmins[0]['name'] ?? 'Admin'),
    'parent_message_id' => null,
    'read_status'       => 0,
    'read_at'           => null,
    'sent_at'           => date('Y-m-d H:i:s', strtotime('-2 hours')),
];

// Admin → Super Admin (unread)
$messages[] = [
    'sender_id'         => $otherAdmins[0]['id'] ?? $aId,
    'sender_type'       => 'admin',
    'receiver_id'       => $aId,
    'receiver_type'     => 'admin',
    'subject'           => 'New Merchant Approval Request',
    'message_text'      => "Two new merchants submitted their profiles today. They are awaiting approval.\n\n1. Spice Garden Restaurant &ndash; Koramangala\n2. TechZone Mobile Store &ndash; Indiranagar\n\nPlease review and approve at your earliest convenience.",
    'parent_message_id' => null,
    'read_status'       => 0,
    'read_at'           => null,
    'sent_at'           => date('Y-m-d H:i:s', strtotime('-5 hours')),
];

// Admin → Super Admin (read)
$messages[] = [
    'sender_id'         => $otherAdmins[0]['id'] ?? $aId,
    'sender_type'       => 'admin',
    'receiver_id'       => $aId,
    'receiver_type'     => 'admin',
    'subject'           => 'System Maintenance Schedule',
    'message_text'      => "Planned maintenance window: Sunday 2:00 AM &ndash; 4:00 AM IST.\nAll services will be temporarily unavailable. Users have been notified.",
    'parent_message_id' => null,
    'read_status'       => 1,
    'read_at'           => date('Y-m-d H:i:s', strtotime('-1 day')),
    'sent_at'           => date('Y-m-d H:i:s', strtotime('-1 day -1 hour')),
];

// If second admin exists
if (isset($otherAdmins[1])) {
    $messages[] = [
        'sender_id'         => $otherAdmins[1]['id'],
        'sender_type'       => 'admin',
        'receiver_id'       => $aId,
        'receiver_type'     => 'admin',
        'subject'           => 'Contest Winner Verification',
        'message_text'      => "The winners for 'Weekend Deal Hunt' contest have been verified. All 3 winners have valid entries. Ready to distribute rewards.",
        'parent_message_id' => null,
        'read_status'       => 0,
        'read_at'           => null,
        'sent_at'           => date('Y-m-d H:i:s', strtotime('-3 hours')),
    ];
}

// Merchant → Super Admin
if ($merchant) {
    $messages[] = [
        'sender_id'         => $merchant['id'],
        'sender_type'       => 'merchant',
        'receiver_id'       => $aId,
        'receiver_type'     => 'admin',
        'subject'           => 'Coupon Campaign Query',
        'message_text'      => "Hello,\n\nI would like to create a special coupon campaign for the upcoming festival season. Could you please guide me on the maximum discount allowed and how to set up the campaign?\n\nThank you,\n" . $merchant['business_name'],
        'parent_message_id' => null,
        'read_status'       => 0,
        'read_at'           => null,
        'sent_at'           => date('Y-m-d H:i:s', strtotime('-30 minutes')),
    ];
}

// Customer → Super Admin
if ($customer) {
    $messages[] = [
        'sender_id'         => $customer['id'],
        'sender_type'       => 'customer',
        'receiver_id'       => $aId,
        'receiver_type'     => 'admin',
        'subject'           => 'Unable to redeem coupon',
        'message_text'      => "Hi Support,\n\nI am trying to redeem a coupon at Spice Garden but the app keeps showing 'already used'. I have not used this coupon before. Coupon code: SG-FEST-2024. Please help.",
        'parent_message_id' => null,
        'read_status'       => 1,
        'read_at'           => date('Y-m-d H:i:s', strtotime('-2 days +30 minutes')),
        'sent_at'           => date('Y-m-d H:i:s', strtotime('-2 days')),
    ];
}

// Super Admin → another admin (sent)
$messages[] = [
    'sender_id'         => $aId,
    'sender_type'       => 'admin',
    'receiver_id'       => $otherAdmins[0]['id'] ?? $aId,
    'receiver_type'     => 'admin',
    'subject'           => 'Q4 Target Review',
    'message_text'      => "Hi,\n\nPlease prepare a summary of Q4 targets vs actuals for the upcoming board meeting. Focus on merchant acquisition, coupon redemptions, and customer growth.\n\nThanks",
    'parent_message_id' => null,
    'read_status'       => 1,
    'read_at'           => date('Y-m-d H:i:s', strtotime('-4 hours')),
    'sent_at'           => date('Y-m-d H:i:s', strtotime('-6 hours')),
];

$stmt = $db->prepare("
    INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, subject, message_text, parent_message_id, read_status, read_at, sent_at)
    VALUES (:sender_id, :sender_type, :receiver_id, :receiver_type, :subject, :message_text, :parent_message_id, :read_status, :read_at, :sent_at)
");

$insertedMsgIds = [];
foreach ($messages as $m) {
    $stmt->execute($m);
    $insertedMsgIds[] = $db->lastInsertId();
    echo "✅ Message inserted: <em>" . htmlspecialchars($m['subject']) . "</em><br>";
}

// Add a reply thread to the first message
if (!empty($insertedMsgIds[0])) {
    $rootId = $insertedMsgIds[0];
    $db->prepare("
        INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, subject, message_text, parent_message_id, read_status, read_at, sent_at)
        VALUES (:sid, 'admin', :rid, 'admin', NULL, :msg, :parent, 1, :read_at, :sent_at)
    ")->execute([
        ':sid'     => $aId,
        ':rid'     => $otherAdmins[0]['id'] ?? $aId,
        ':msg'     => "Thanks for sending over the report. I'll review it today and get back to you with feedback.",
        ':parent'  => $rootId,
        ':read_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ':sent_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
    ]);
    echo "✅ Reply inserted for message ID {$rootId}<br>";
}

// ---------------------------------------------------------------
// 4. Seed Notifications
// ---------------------------------------------------------------
echo "<br><h3>Seeding Notifications...</h3>";

$notifications = [
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'success',
        'title'             => 'Contest Created Successfully',
        'message'           => 'The contest "Weekend Deal Hunt" has been created and is now active.',
        'action_url'        => BASE_URL . 'contests',
        'read_status'       => 1,
        'read_at'           => date('Y-m-d H:i:s', strtotime('-3 days +1 hour')),
        'created_at'        => date('Y-m-d H:i:s', strtotime('-3 days')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'info',
        'title'             => 'New Merchant Registration',
        'message'           => '3 new merchants have registered and are awaiting profile approval.',
        'action_url'        => BASE_URL . 'merchants',
        'read_status'       => 0,
        'read_at'           => null,
        'created_at'        => date('Y-m-d H:i:s', strtotime('-1 hour')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'warning',
        'title'             => 'Coupon Expiry Alert',
        'message'           => '12 coupons are expiring in the next 48 hours. Review and extend if needed.',
        'action_url'        => BASE_URL . 'coupons',
        'read_status'       => 0,
        'read_at'           => null,
        'created_at'        => date('Y-m-d H:i:s', strtotime('-2 hours')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'error',
        'title'             => 'Payment Gateway Issue',
        'message'           => 'Payment gateway returned error for 2 transactions. Manual review required.',
        'action_url'        => null,
        'read_status'       => 0,
        'read_at'           => null,
        'created_at'        => date('Y-m-d H:i:s', strtotime('-45 minutes')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'success',
        'title'             => 'Mystery Shopping Task Completed',
        'message'           => 'Task #3 "Evaluate Loyalty Program" has been completed and report submitted.',
        'action_url'        => BASE_URL . 'mystery-shopping',
        'read_status'       => 1,
        'read_at'           => date('Y-m-d H:i:s', strtotime('-2 days +2 hours')),
        'created_at'        => date('Y-m-d H:i:s', strtotime('-2 days')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'info',
        'title'             => 'Survey Response Milestone',
        'message'           => 'Survey "Customer Satisfaction Q4" has received 100+ responses.',
        'action_url'        => BASE_URL . 'surveys',
        'read_status'       => 1,
        'read_at'           => date('Y-m-d H:i:s', strtotime('-4 days +3 hours')),
        'created_at'        => date('Y-m-d H:i:s', strtotime('-4 days')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'warning',
        'title'             => 'Admin Session Expired',
        'message'           => 'A city admin session was force-expired due to inactivity. Check audit log.',
        'action_url'        => null,
        'read_status'       => 1,
        'read_at'           => date('Y-m-d H:i:s', strtotime('-5 days +1 hour')),
        'created_at'        => date('Y-m-d H:i:s', strtotime('-5 days')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'info',
        'title'             => 'Loyalty Card Batch Issued',
        'message'           => '500 new loyalty cards have been issued to customers in Bangalore South.',
        'action_url'        => BASE_URL . 'cards',
        'read_status'       => 0,
        'read_at'           => null,
        'created_at'        => date('Y-m-d H:i:s', strtotime('-30 minutes')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'success',
        'title'             => 'Contest Winners Announced',
        'message'           => 'Winners for "Best Dressed Festive Season" contest have been marked. Prizes pending distribution.',
        'action_url'        => BASE_URL . 'contests',
        'read_status'       => 1,
        'read_at'           => date('Y-m-d H:i:s', strtotime('-1 day +30 minutes')),
        'created_at'        => date('Y-m-d H:i:s', strtotime('-1 day')),
    ],
    [
        'user_id'           => $aId,
        'user_type'         => 'admin',
        'notification_type' => 'info',
        'title'             => 'System Health Check',
        'message'           => 'Automated system health check completed. All services running normally.',
        'action_url'        => null,
        'read_status'       => 0,
        'read_at'           => null,
        'created_at'        => date('Y-m-d H:i:s', strtotime('-10 minutes')),
    ],
];

// Broadcast to other admins (first 3 notifications go to them too)
$broadcastNotifs = array_slice($notifications, 1, 3);

$nStmt = $db->prepare("
    INSERT INTO notifications (user_id, user_type, notification_type, title, message, action_url, read_status, read_at, created_at)
    VALUES (:user_id, :user_type, :notification_type, :title, :message, :action_url, :read_status, :read_at, :created_at)
");

foreach ($notifications as $n) {
    $nStmt->execute($n);
    echo "✅ Notification: <em>" . htmlspecialchars($n['title']) . "</em> → " . ucfirst($n['notification_type']) . "<br>";
}

// Broadcast some notifications to other admins too
foreach ($otherAdmins as $oa) {
    foreach ($broadcastNotifs as $bn) {
        $bn['user_id']     = $oa['id'];
        $bn['read_status'] = 0;
        $bn['read_at']     = null;
        $nStmt->execute($bn);
    }
}
echo "✅ Broadcast " . count($broadcastNotifs) . " notifications to " . count($otherAdmins) . " other admin(s)<br>";

// ---------------------------------------------------------------
// Summary
// ---------------------------------------------------------------
$msgCount   = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$notifCount = $db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();

echo "<br><hr><h3>✅ Seeding Complete</h3>";
echo "<p>Messages in DB: <strong>{$msgCount}</strong><br>";
echo "Notifications in DB: <strong>{$notifCount}</strong></p>";
echo "<a href='" . BASE_URL . "messages/inbox'>→ Open Inbox</a> &nbsp;|&nbsp; ";
echo "<a href='" . BASE_URL . "notifications'>→ Open Notifications</a>";
