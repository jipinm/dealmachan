<?php
/**
 * Merchant Store Controller
 *
 * All routes are authenticated — uses AuthMiddleware::user() internally.
 *
 * GET    /merchants/stores
 * POST   /merchants/stores
 * GET    /merchants/stores/:id
 * PUT    /merchants/stores/:id
 * DELETE /merchants/stores/:id
 * POST   /merchants/stores/:id/gallery
 * DELETE /merchants/stores/:id/gallery/:imageId
 * PUT    /merchants/stores/:id/gallery/:imageId   ← set as cover
 */
class StoreController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/stores ─────────────────────────────────────────────────
    public function index(array $params = []): never {
        $merchant = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $status = isset($params['status']) && in_array($params['status'], ['active', 'inactive'])
            ? $params['status'] : null;

        $where = "s.merchant_id = ? AND s.deleted_at IS NULL";
        $binds = [$merchantId];
        if ($status) {
            $where .= " AND s.status = ?";
            $binds[] = $status;
        }

        $stores = $this->db->query(
            "SELECT s.*,
                    c.city_name,
                    a.area_name,
                    COUNT(DISTINCT CASE WHEN cp.status='active' AND (cp.valid_until IS NULL OR cp.valid_until >= CURDATE()) THEN cp.id END) AS active_coupons,
                    COUNT(DISTINCT g.id)  AS gallery_count,
                    (SELECT g2.image_url FROM store_gallery g2 WHERE g2.store_id = s.id AND g2.is_cover = 1 LIMIT 1) AS cover_image
             FROM stores s
             LEFT JOIN cities c ON c.id = s.city_id
             LEFT JOIN areas  a ON a.id = s.area_id
             LEFT JOIN coupons cp ON cp.store_id = s.id
             LEFT JOIN store_gallery g ON g.store_id = s.id
             WHERE {$where}
             GROUP BY s.id
             ORDER BY s.created_at DESC",
            $binds
        );

        foreach ($stores as &$st) {
            $st['active_coupons'] = (int)$st['active_coupons'];
            $st['gallery_count']  = (int)$st['gallery_count'];
            $st['opening_hours']  = $st['opening_hours'] ? json_decode($st['opening_hours'], true) : null;
        }

        Response::success($stores);
    }

    // ── POST /merchants/stores ────────────────────────────────────────────────
    public function store(array $body): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $v = new Validator($body);
        $v->required('store_name', 'Store Name')
          ->required('address',    'Address')
          ->required('city_id',    'City');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $hours = isset($body['opening_hours']) ? json_encode($body['opening_hours']) : null;

        $this->db->execute(
            "INSERT INTO stores (merchant_id, store_name, address, city_id, area_id, location_id,
                                  phone, email, latitude, longitude, opening_hours, description, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [
                $merchantId,
                trim($body['store_name']),
                trim($body['address']),
                (int)$body['city_id'],
                !empty($body['area_id'])     ? (int)$body['area_id']     : null,
                !empty($body['location_id']) ? (int)$body['location_id'] : null,
                !empty($body['phone'])       ? trim($body['phone'])      : null,
                !empty($body['email'])       ? trim($body['email'])      : null,
                !empty($body['latitude'])    ? (float)$body['latitude']  : null,
                !empty($body['longitude'])   ? (float)$body['longitude'] : null,
                $hours,
                !empty($body['description']) ? trim($body['description']) : null,
            ]
        );

        $storeId = $this->db->lastInsertId();
        $newStore = $this->fetchStoreDetail($merchantId, $storeId);

        Response::success($newStore, 'Store created successfully', 201);
    }

    // ── GET /merchants/stores/:id ─────────────────────────────────────────────
    public function show(int $id): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $store = $this->fetchStoreDetail($merchantId, $id);
        if (!$store) {
            Response::notFound('Store not found');
        }

        Response::success($store);
    }

    // ── PUT /merchants/stores/:id ─────────────────────────────────────────────
    public function update(int $id, array $body): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $existing = $this->db->queryOne(
            "SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL",
            [$id, $merchantId]
        );
        if (!$existing) {
            Response::notFound('Store not found');
        }

        $allowed = ['store_name', 'address', 'city_id', 'area_id', 'location_id',
                    'phone', 'email', 'latitude', 'longitude', 'description', 'status'];

        $sets   = [];
        $values = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $sets[]   = "{$field} = ?";
                $values[] = $body[$field] === '' ? null : $body[$field];
            }
        }

        if (isset($body['opening_hours'])) {
            $sets[]   = "opening_hours = ?";
            $values[] = json_encode($body['opening_hours']);
        }

        if (empty($sets)) {
            Response::error('No fields to update.', 400, 'NOTHING_TO_UPDATE');
        }

        $values[] = $id;
        $this->db->execute("UPDATE stores SET " . implode(', ', $sets) . " WHERE id = ?", $values);

        Response::success($this->fetchStoreDetail($merchantId, $id), 'Store updated');
    }

    // ── DELETE /merchants/stores/:id ──────────────────────────────────────────
    public function destroy(int $id): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $existing = $this->db->queryOne(
            "SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL",
            [$id, $merchantId]
        );
        if (!$existing) {
            Response::notFound('Store not found');
        }

        $this->db->execute(
            "UPDATE stores SET deleted_at = NOW(), status = 'inactive' WHERE id = ?",
            [$id]
        );

        Response::success(null, 'Store deleted');
    }

    // ── POST /merchants/stores/:id/gallery ────────────────────────────────────
    public function uploadGallery(int $storeId): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $store = $this->db->queryOne(
            "SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL",
            [$storeId, $merchantId]
        );
        if (!$store) {
            Response::notFound('Store not found');
        }

        if (empty($_FILES['images']) && empty($_FILES['image'])) {
            Response::error('No images uploaded.', 400, 'NO_FILES');
        }

        // Normalise single or multiple file uploads
        $files = [];
        $src   = !empty($_FILES['images']) ? $_FILES['images'] : $_FILES['image'];

        if (is_array($src['name'])) {
            $count = count($src['name']);
            for ($i = 0; $i < $count; $i++) {
                $files[] = [
                    'name'     => $src['name'][$i],
                    'tmp_name' => $src['tmp_name'][$i],
                    'size'     => $src['size'][$i],
                    'type'     => $src['type'][$i],
                    'error'    => $src['error'][$i],
                ];
            }
        } else {
            $files[] = $src;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $maxSize      = 5 * 1024 * 1024; // 5MB
        $uploadDir    = __DIR__ . '/../../public/uploads/gallery/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Check if this store already has a cover image
        $hasCover = (bool)$this->db->queryOne(
            "SELECT id FROM store_gallery WHERE store_id = ? AND is_cover = 1",
            [$storeId]
        );

        $saved = [];
        foreach ($files as $idx => $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) continue;
            if ($file['size'] > $maxSize)          continue;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimes)) continue;

            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'store_' . $storeId . '_' . uniqid() . '.' . strtolower($ext);
            $dest     = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $isCover = (!$hasCover && $idx === 0) ? 1 : 0;
                $this->db->execute(
                    "INSERT INTO store_gallery (store_id, merchant_id, image_url, is_cover, display_order, created_at)
                     VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(g.display_order),0)+1 FROM store_gallery g WHERE g.store_id = ?), NOW())",
                    [$storeId, $merchantId, '/uploads/gallery/' . $filename, $isCover, $storeId]
                );
                $saved[] = [
                    'id'        => (int)$this->db->lastInsertId(),
                    'image_url' => '/uploads/gallery/' . $filename,
                    'is_cover'  => (bool)$isCover,
                ];
                if ($isCover) $hasCover = true;
            }
        }

        if (empty($saved)) {
            Response::error('No valid images were uploaded.', 422, 'UPLOAD_FAILED');
        }

        Response::success($saved, count($saved) . ' image(s) uploaded');
    }

    // ── DELETE /merchants/stores/:id/gallery/:imageId ─────────────────────────
    public function deleteGalleryImage(int $storeId, int $imageId): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $image = $this->db->queryOne(
            "SELECT g.* FROM store_gallery g
             JOIN stores s ON s.id = g.store_id
             WHERE g.id = ? AND g.store_id = ? AND s.merchant_id = ?",
            [$imageId, $storeId, $merchantId]
        );
        if (!$image) {
            Response::notFound('Image not found');
        }

        // Delete physical file
        $filePath = __DIR__ . '/../../public' . $image['image_url'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $this->db->execute("DELETE FROM store_gallery WHERE id = ?", [$imageId]);

        // If we deleted the cover, promote the next image
        if ($image['is_cover']) {
            $this->db->execute(
                "UPDATE store_gallery SET is_cover = 1 WHERE store_id = ? ORDER BY display_order ASC LIMIT 1",
                [$storeId]
            );
        }

        Response::success(null, 'Image deleted');
    }

    // ── PUT /merchants/stores/:id/gallery/:imageId ────────────────────────────
    public function setCoverImage(int $storeId, int $imageId): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $image = $this->db->queryOne(
            "SELECT g.id FROM store_gallery g
             JOIN stores s ON s.id = g.store_id
             WHERE g.id = ? AND g.store_id = ? AND s.merchant_id = ?",
            [$imageId, $storeId, $merchantId]
        );
        if (!$image) {
            Response::notFound('Image not found');
        }

        // Unset all covers for this store, then set the selected one
        $this->db->execute("UPDATE store_gallery SET is_cover = 0 WHERE store_id = ?", [$storeId]);
        $this->db->execute("UPDATE store_gallery SET is_cover = 1 WHERE id = ?",       [$imageId]);

        Response::success(null, 'Cover image updated');
    }

    // ── PUT /merchants/stores/:id/gallery/reorder ─────────────────────────────
    public function reorderGallery(int $storeId, array $body): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $store = $this->db->queryOne(
            "SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL",
            [$storeId, $merchantId]
        );
        if (!$store) Response::notFound('Store not found');

        if (empty($body['image_ids']) || !is_array($body['image_ids'])) {
            Response::error('image_ids array is required', 400);
        }

        $imageIds = array_map('intval', $body['image_ids']);

        foreach ($imageIds as $order => $imageId) {
            $this->db->execute(
                "UPDATE store_gallery SET display_order = ? WHERE id = ? AND store_id = ?",
                [$order, $imageId, $storeId]
            );
        }

        Response::success(null, 'Gallery reordered');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function fetchStoreDetail(int $merchantId, int $storeId): ?array {
        $store = $this->db->queryOne(
            "SELECT s.*,
                    c.city_name,
                    a.area_name,
                    COUNT(DISTINCT CASE WHEN cp.status='active' AND (cp.valid_until IS NULL OR cp.valid_until >= CURDATE()) THEN cp.id END) AS active_coupons
             FROM stores s
             LEFT JOIN cities c ON c.id = s.city_id
             LEFT JOIN areas  a ON a.id = s.area_id
             LEFT JOIN coupons cp ON cp.store_id = s.id
             WHERE s.id = ? AND s.merchant_id = ? AND s.deleted_at IS NULL
             GROUP BY s.id",
            [$storeId, $merchantId]
        );

        if (!$store) return null;

        $store['active_coupons'] = (int)$store['active_coupons'];
        $store['opening_hours']  = $store['opening_hours'] ? json_decode($store['opening_hours'], true) : null;

        // Gallery
        $store['gallery'] = $this->db->query(
            "SELECT id, image_url, caption, is_cover, display_order
             FROM store_gallery WHERE store_id = ? ORDER BY is_cover DESC, display_order ASC",
            [$storeId]
        );
        foreach ($store['gallery'] as &$img) {
            $img['is_cover'] = (bool)$img['is_cover'];
        }

        return $store;
    }
}
