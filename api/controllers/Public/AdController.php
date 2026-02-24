<?php
/**
 * Public Advertisement Controller
 *
 * GET /api/public/advertisements   — active ads (filtered by city / placement)
 */
class PublicAdController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /api/public/advertisements ───────────────────────────────────────
    public function index(array $query): never {
        $cityId    = !empty($query['city_id'])    ? (int)$query['city_id']       : null;
        $placement = !empty($query['placement'])  ? trim($query['placement'])    : null;
        $now       = date('Y-m-d H:i:s');

        $where  = ["a.status = 'active'", "a.start_date <= ?", "(a.end_date IS NULL OR a.end_date >= ?)"];
        $params = [$now, $now];

        if ($cityId) {
            $where[]  = "(a.city_id IS NULL OR a.city_id = ?)";
            $params[] = $cityId;
        }
        if ($placement) {
            $where[]  = "a.placement = ?";
            $params[] = $placement;
        }

        $whereSQL = implode(' AND ', $where);

        $ads = $this->db->query(
            "SELECT a.id, a.title, a.media_type, a.media_url, a.link_url,
                    a.display_duration, a.placement
             FROM advertisements a
             WHERE {$whereSQL}
             ORDER BY a.sort_order ASC, a.created_at DESC
             LIMIT 20",
            $params
        );

        foreach ($ads as &$ad) {
            $ad['media_url'] = imageUrl($ad['media_url']);
        }

        Response::success($ads);
    }
}
