<?php
require_once CORE_PATH . '/Auth.php';

class DashboardController extends Controller {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
        
        // Require authentication for all dashboard actions
        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to access the dashboard.';
            $this->redirect('auth/login');
            return;
        }
    }
    
    /**
     * Dashboard index page
     */
    public function index() {
        $current_user = $this->auth->getCurrentUser();

        // Read trend period from GET param (7, 30, or 90 days); default 30
        $allowed_periods = [7, 30, 90];
        $trend_period = isset($_GET['trend_period']) ? (int)$_GET['trend_period'] : 30;
        if (!in_array($trend_period, $allowed_periods)) {
            $trend_period = 30;
        }
        
        // Get comprehensive statistics based on user role
        $stats = $this->getDashboardStatistics($current_user);
        $recent_activities = $this->getRecentActivities($current_user);
        $chart_data = $this->getChartData($current_user, $trend_period);
        
        $data = [
            'title' => 'Dashboard - Deal Machan Admin',
            'current_user' => $current_user,
            'stats' => $stats,
            'recent_activities' => $recent_activities,
            'chart_data' => $chart_data,
            'trend_period' => $trend_period,
        ];
        
        $this->loadView('dashboard/index', $data);
    }
    
    /**
     * Get dashboard statistics based on user role and permissions
     */
    private function getDashboardStatistics($current_user) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $admin_type = $current_user['admin_type'];
            $city_id = $current_user['city_id'];
            
            $stats = [
                'total_customers' => 0,
                'total_merchants' => 0,
                'active_coupons' => 0,
                'total_redemptions' => 0,
                'today_customers' => 0,
                'today_merchants' => 0,
                'today_redemptions' => 0,
                'week_customers' => 0,
                'week_merchants' => 0,
                'month_customers' => 0,
                'pending_merchants' => 0,
                'blocked_customers' => 0
            ];
            
            // Build city filter for City Admins
            // City filter works via stores -> areas -> cities join
            $cityFilter = '';
            $cityParam = [];
            $custCityFilter = '';
            $merCityFilter = '';
            if ($admin_type === 'city_admin' && $city_id) {
                // Customers created by this city's admin
                $custCityFilter = ' AND c.created_by_admin_id IN (SELECT id FROM admins WHERE city_id = ?)';
                // Merchants via their stores -> areas -> city
                $merCityFilter = ' AND EXISTS (SELECT 1 FROM stores s JOIN areas a ON s.area_id = a.id WHERE s.merchant_id = m.id AND a.city_id = ?)';
                $cityParam = [$city_id];
            }
            
            // Customer statistics
            if ($admin_type === 'super_admin' || $admin_type === 'city_admin') {
                // Total customers
                $sql = "SELECT COUNT(*) as count FROM customers c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE u.user_type = 'customer'" . $custCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['total_customers'] = $stmt->fetch()['count'];
                
                // Today's customers
                $sql = "SELECT COUNT(*) as count FROM customers c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE u.user_type = 'customer' AND DATE(c.created_at) = CURDATE()" . $custCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['today_customers'] = $stmt->fetch()['count'];
                
                // Week customers
                $sql = "SELECT COUNT(*) as count FROM customers c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE u.user_type = 'customer' AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" . $custCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['week_customers'] = $stmt->fetch()['count'];
                
                // Month customers
                $sql = "SELECT COUNT(*) as count FROM customers c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE u.user_type = 'customer' AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" . $custCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['month_customers'] = $stmt->fetch()['count'];
                
                // Blocked customers
                $sql = "SELECT COUNT(*) as count FROM customers c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE u.user_type = 'customer' AND u.status = 'blocked'" . $custCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['blocked_customers'] = $stmt->fetch()['count'];
            }
            
            // Merchant statistics
            if ($admin_type === 'super_admin' || $admin_type === 'city_admin' || $admin_type === 'sales_admin') {
                // Total merchants
                $sql = "SELECT COUNT(*) as count FROM merchants m 
                        JOIN users u ON m.user_id = u.id 
                        WHERE u.user_type = 'merchant'" . $merCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['total_merchants'] = $stmt->fetch()['count'];
                
                // Today's merchants
                $sql = "SELECT COUNT(*) as count FROM merchants m 
                        JOIN users u ON m.user_id = u.id 
                        WHERE u.user_type = 'merchant' AND DATE(m.created_at) = CURDATE()" . $merCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['today_merchants'] = $stmt->fetch()['count'];
                
                // Week merchants
                $sql = "SELECT COUNT(*) as count FROM merchants m 
                        JOIN users u ON m.user_id = u.id 
                        WHERE u.user_type = 'merchant' AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" . $merCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['week_merchants'] = $stmt->fetch()['count'];
                
                // Pending merchants (use profile_status on merchants table)
                $sql = "SELECT COUNT(*) as count FROM merchants m 
                        JOIN users u ON m.user_id = u.id 
                        WHERE u.user_type = 'merchant' AND m.profile_status = 'pending'" . $merCityFilter;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($cityParam);
                $stats['pending_merchants'] = $stmt->fetch()['count'];
            }
            
            // Coupon statistics (all admin types can see)
            $couponCityFilter = '';
            $couponCityParam = [];
            if ($admin_type === 'city_admin' && $city_id) {
                $couponCityFilter = ' AND EXISTS (SELECT 1 FROM merchants m2 JOIN stores s2 ON s2.merchant_id = m2.id JOIN areas a2 ON s2.area_id = a2.id WHERE m2.id = c.merchant_id AND a2.city_id = ?)';
                $couponCityParam = [$city_id];
            }
            
            // Active coupons
            $sql = "SELECT COUNT(*) as count FROM coupons c WHERE c.status = 'active' AND c.valid_until >= CURDATE()" . $couponCityFilter;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($couponCityParam);
            $stats['active_coupons'] = $stmt->fetch()['count'];
            
            // Redemption statistics (coupon_redemptions has no status column &mdash; count all)
            $sql = "SELECT COUNT(*) as count FROM coupon_redemptions cr 
                    JOIN coupons c ON cr.coupon_id = c.id" . (empty($couponCityFilter) ? '' : ' WHERE 1=1 ' . $couponCityFilter);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($couponCityParam);
            $stats['total_redemptions'] = $stmt->fetch()['count'];
            
            // Today's redemptions
            $sql = "SELECT COUNT(*) as count FROM coupon_redemptions cr 
                    JOIN coupons c ON cr.coupon_id = c.id 
                    WHERE DATE(cr.redeemed_at) = CURDATE()" . $couponCityFilter;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($couponCityParam);
            $stats['today_redemptions'] = $stmt->fetch()['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Dashboard statistics error: " . $e->getMessage());
            return [
                'total_customers' => 0,
                'total_merchants' => 0,
                'active_coupons' => 0,
                'total_redemptions' => 0,
                'today_customers' => 0,
                'today_merchants' => 0,
                'today_redemptions' => 0,
                'week_customers' => 0,
                'week_merchants' => 0,
                'month_customers' => 0,
                'pending_merchants' => 0,
                'blocked_customers' => 0
            ];
        }
    }
    
    /**
     * Get recent activities for the dashboard
     */
    private function getRecentActivities($current_user) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $admin_type = $current_user['admin_type'];
            $city_id = $current_user['city_id'];
            $activities = [];
            
            // City filter for role-based access
            $cityFilter = '';
            $cityParam = [];
            if ($admin_type === 'city_admin' && $city_id) {
                $cityFilter = ' AND city_id = ?';
                $cityParam = [$city_id];
            }
            
            // Recent customer registrations
            $custCityFilter2 = '';
            $merCityFilter2 = '';
            $couponCityFilter2 = '';
            $act_cityParam = [];
            if ($admin_type === 'city_admin' && $city_id) {
                $custCityFilter2 = ' AND c.created_by_admin_id IN (SELECT id FROM admins WHERE city_id = ?)';
                $merCityFilter2 = ' AND EXISTS (SELECT 1 FROM stores s JOIN areas a ON s.area_id = a.id WHERE s.merchant_id = m.id AND a.city_id = ?)';
                $couponCityFilter2 = ' AND EXISTS (SELECT 1 FROM merchants m2 JOIN stores s2 ON s2.merchant_id = m2.id JOIN areas a2 ON s2.area_id = a2.id WHERE m2.id = c.merchant_id AND a2.city_id = ?)';
                $act_cityParam = [$city_id];
            }
            if ($admin_type === 'super_admin' || $admin_type === 'city_admin') {
                $sql = "SELECT c.name, u.email, c.created_at, 'customer' as type
                        FROM customers c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE u.user_type = 'customer'" . $custCityFilter2 . "
                        ORDER BY c.created_at DESC LIMIT 5";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($act_cityParam);
                $customers = $stmt->fetchAll();
                
                foreach ($customers as $customer) {
                    $activities[] = [
                        'type' => 'customer_registration',
                        'icon' => 'fas fa-user-plus',
                        'color' => 'success',
                        'description' => "New customer: {$customer['name']} ({$customer['email']})",
                        'created_at' => $customer['created_at']
                    ];
                }
            }
            
            // Recent merchant registrations
            if ($admin_type === 'super_admin' || $admin_type === 'city_admin' || $admin_type === 'sales_admin') {
                $sql = "SELECT m.business_name, u.email, m.created_at
                        FROM merchants m 
                        JOIN users u ON m.user_id = u.id 
                        WHERE u.user_type = 'merchant'" . $merCityFilter2 . "
                        ORDER BY m.created_at DESC LIMIT 5";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($act_cityParam);
                $merchants = $stmt->fetchAll();
                
                foreach ($merchants as $merchant) {
                    $activities[] = [
                        'type' => 'merchant_registration',
                        'icon' => 'fas fa-store',
                        'color' => 'info',
                        'description' => "New merchant: {$merchant['business_name']} ({$merchant['email']})",
                        'created_at' => $merchant['created_at']
                    ];
                }
            }
            
            // Recent coupon creations
            $sql = "SELECT c.title, c.created_at, m.business_name
                    FROM coupons c 
                    JOIN merchants m ON c.merchant_id = m.id
                    WHERE c.status = 'active'" . $couponCityFilter2 . "
                    ORDER BY c.created_at DESC LIMIT 3";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($act_cityParam);
            $coupons = $stmt->fetchAll();
            
            foreach ($coupons as $coupon) {
                $activities[] = [
                    'type' => 'coupon_created',
                    'icon' => 'fas fa-ticket-alt',
                    'color' => 'warning',
                    'description' => "New coupon: {$coupon['title']} by {$coupon['business_name']}",
                    'created_at' => $coupon['created_at']
                ];
            }
            
            // Recent redemptions (no status column in coupon_redemptions)
            $sql = "SELECT c.title, cr.redeemed_at, cu.name as customer_name
                    FROM coupon_redemptions cr
                    JOIN coupons c ON cr.coupon_id = c.id
                    JOIN customers cu ON cr.customer_id = cu.id" .
                    (empty($couponCityFilter2) ? '' : ' WHERE 1=1 ' . $couponCityFilter2) . "
                    ORDER BY cr.redeemed_at DESC LIMIT 3";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($act_cityParam);
            $redemptions = $stmt->fetchAll();
            
            foreach ($redemptions as $redemption) {
                $activities[] = [
                    'type' => 'coupon_redeemed',
                    'icon' => 'fas fa-check-circle',
                    'color' => 'success',
                    'description' => "Coupon redeemed: {$redemption['title']} by {$redemption['customer_name']}",
                    'created_at' => $redemption['redeemed_at']
                ];
            }
            
            // Sort all activities by time
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return array_slice($activities, 0, 10);
            
        } catch (Exception $e) {
            error_log("Recent activities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get chart data for dashboard visualizations
     */
    private function getChartData($current_user, $trend_period = 30) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $admin_type = $current_user['admin_type'];
            $city_id = $current_user['city_id'];
            
            $chartCityFilter = '';
            $chartCustParam = [];
            $chartCouponFilter = '';
            $chartCouponParam = [];
            if ($admin_type === 'city_admin' && $city_id) {
                $chartCityFilter = ' AND c.created_by_admin_id IN (SELECT id FROM admins WHERE city_id = ?)';
                $chartCustParam = [$city_id];
                $chartCouponFilter = ' AND EXISTS (SELECT 1 FROM merchants m2 JOIN stores s2 ON s2.merchant_id = m2.id JOIN areas a2 ON s2.area_id = a2.id WHERE m2.id = c.merchant_id AND a2.city_id = ?)';
                $chartCouponParam = [$city_id];
            }
            
            // Customer registration trend &mdash; full range with zeros for missing days
            $customer_trend = [];
            if ($admin_type === 'super_admin' || $admin_type === 'city_admin') {
                $sql = "SELECT DATE(c.created_at) as date, COUNT(*) as count
                        FROM customers c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE u.user_type = 'customer' 
                        AND c.created_at >= DATE_SUB(NOW(), INTERVAL {$trend_period} DAY)" . $chartCityFilter . "
                        GROUP BY DATE(c.created_at)
                        ORDER BY date";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($chartCustParam);
                $db_rows = $stmt->fetchAll();

                // Index DB results by date string
                $db_map = [];
                foreach ($db_rows as $row) {
                    $db_map[$row['date']] = (int)$row['count'];
                }

                // Build complete date series, filling gaps with 0
                for ($i = $trend_period - 1; $i >= 0; $i--) {
                    $d = date('Y-m-d', strtotime("-{$i} days"));
                    $customer_trend[] = [
                        'date'  => date('M j', strtotime($d)),
                        'count' => $db_map[$d] ?? 0,
                    ];
                }
            }
            
            // Coupon distribution by discount_type (no category column exists)
            $coupon_categories = [];
            $sql = "SELECT c.discount_type as category, COUNT(*) as count
                    FROM coupons c 
                    WHERE c.status = 'active'" . $chartCouponFilter . "
                    GROUP BY c.discount_type
                    ORDER BY count DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($chartCouponParam);
            $coupon_categories = $stmt->fetchAll();
            
            // Top performing merchants by redemption count
            $topMerchantCityFilter = '';
            $topMerchantParam = [];
            if ($admin_type === 'city_admin' && $city_id) {
                $topMerchantCityFilter = ' JOIN stores s ON s.merchant_id = m.id JOIN areas a ON s.area_id = a.id AND a.city_id = ?' ;
                $topMerchantParam = [$city_id];
            }
            $sql = "SELECT m.business_name, m.id, COUNT(cr.id) as total_redemptions
                    FROM merchants m
                    LEFT JOIN coupons c ON c.merchant_id = m.id
                    LEFT JOIN coupon_redemptions cr ON cr.coupon_id = c.id
                    WHERE m.profile_status = 'approved'
                    GROUP BY m.id, m.business_name
                    ORDER BY total_redemptions DESC
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([]);
            $top_merchants = $stmt->fetchAll();
            
            return [
                'customer_trends' => $customer_trend,
                'coupon_categories' => $coupon_categories,
                'top_merchants' => $top_merchants
            ];
            
        } catch (Exception $e) {
            error_log("Chart data error: " . $e->getMessage());
            return [
                'customer_trends' => [],
                'coupon_categories' => [],
                'top_merchants' => []
            ];
        }
    }
}
?>