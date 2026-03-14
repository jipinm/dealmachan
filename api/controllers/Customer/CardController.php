<?php
/**
 * Customer Card Controller
 *
 * GET  /api/customers/card                      — get assigned card(s)
 * POST /api/customers/card/request-auth-code    — request auth code (pre-printed card step 1)
 * POST /api/customers/card/activate             — activate card (pre-printed: requires auth_code)
 */
class CardController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function getCustomer(array $user): array {
        $row = $this->db->queryOne(
            "SELECT c.id, c.name FROM customers c WHERE c.user_id = ?",
            [$user['id']]
        );
        if (!$row) Response::notFound('Customer profile not found.');
        return $row;
    }

    // ── GET /api/customers/card ───────────────────────────────────────────────
    public function show(array $user): never {
        $customer = $this->getCustomer($user);

        $card = $this->db->queryOne(
            "SELECT c.*, cc.name AS config_name, cc.classification, cc.features_html,
                    cc.max_live_coupons, cc.coupon_authorization
             FROM cards c
             LEFT JOIN card_configurations cc ON cc.id = c.card_configuration_id
             WHERE c.assigned_to_customer_id = ? AND c.status = 'activated'
             LIMIT 1",
            [$customer['id']]
        );

        if ($card) {
            if (!empty($card['parameters_json'])) {
                $card['parameters'] = json_decode($card['parameters_json'], true);
                unset($card['parameters_json']);
            }

            // Attach partner merchants if configuration is linked
            $card['partners'] = $card['card_configuration_id']
                ? ($this->db->query(
                    "SELECT id, partner_type, partner_image, url
                     FROM card_config_partners
                     WHERE config_id = ?
                     ORDER BY FIELD(partner_type, 'premium', 'normal'), sort_order",
                    [$card['card_configuration_id']]
                ) ?? [])
                : [];

            // Expiry status
            if (!empty($card['expiry_date'])) {
                $daysLeft = (int)((strtotime($card['expiry_date']) - time()) / 86400);
                $card['expiry_status']  = $daysLeft < 0 ? 'expired'
                    : ($daysLeft <= 30 ? 'expiring_soon' : 'active');
                $card['days_remaining'] = max(0, $daysLeft);
            } else {
                $card['expiry_status']  = 'active';
                $card['days_remaining'] = null;
            }
        }

        Response::success($card); // null if no card yet
    }

    // ── POST /api/customers/card/request-auth-code ───────────────────────────
    // Step 1 of pre-printed card activation.
    // Customer provides card_number → system generates a time-limited auth code
    // and queues an in-app notification to the original card issuer (merchant/admin).
    public function requestAuthCode(array $user, array $body): never {
        $customer = $this->getCustomer($user);

        $cardNumber = trim($body['card_number'] ?? '');
        if (!$cardNumber) Response::error('Card number is required.', 400, 'MISSING_CARD_NUMBER');

        $card = $this->db->queryOne(
            "SELECT id, status, is_preprinted, assigned_to_customer_id,
                    assigned_to_merchant_id, assigned_to_admin_id
             FROM cards WHERE card_number = ?",
            [$cardNumber]
        );

        if (!$card) Response::notFound('Card not found. Please check the number and try again.');

        if (!$card['is_preprinted']) {
            Response::error('This card does not require an authorization code.', 400, 'NOT_PREPRINTED');
        }

        if ($card['assigned_to_customer_id'] && (int)$card['assigned_to_customer_id'] !== (int)$customer['id']) {
            Response::error('This card is already assigned to another account.', 409, 'CARD_TAKEN');
        }

        if ($card['status'] === 'blocked') {
            Response::error('This card has been blocked. Please contact support.', 403, 'CARD_BLOCKED');
        }

        if ($card['status'] === 'activated') {
            Response::error('This card is already activated.', 409, 'ALREADY_ACTIVATED');
        }

        // Invalidate old unused codes for this card + customer
        $this->db->execute(
            "UPDATE card_auth_codes SET used = 1 WHERE card_id = ? AND customer_id = ? AND used = 0",
            [$card['id'], $customer['id']]
        );

        // Generate a new 6-digit code; valid for 30 minutes
        $code      = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $this->db->execute(
            "INSERT INTO card_auth_codes (card_id, customer_id, auth_code, expires_at, used)
             VALUES (?, ?, ?, ?, 0)",
            [$card['id'], $customer['id'], $code, $expiresAt]
        );

        // Queue notification to card issuer (merchant or admin) so they can relay the code
        // Issuer type determines notification target
        if ($card['assigned_to_merchant_id']) {
            $this->db->execute(
                "INSERT INTO notifications (notifiable_type, notifiable_id, type, title, body, data_json)
                 VALUES ('merchant', ?, 'card_auth_request',
                         'Card Activation Request',
                         'A customer is requesting to activate card #. Please share auth code.',
                         ?)",
                [
                    $card['assigned_to_merchant_id'],
                    json_encode(['card_number' => $cardNumber, 'auth_code' => $code, 'expires_at' => $expiresAt])
                ]
            );
        }

        Response::success([
            'message'    => 'Authorization code sent to the card issuer. Ask them for the code to proceed.',
            'expires_at' => $expiresAt,
        ]);
    }

    // ── POST /api/customers/card/activate ────────────────────────────────────
    // For pre-printed cards: requires card_number + auth_code.
    // For digital (non-preprinted) cards assigned to this customer: card_number only.
    public function activate(array $user, array $body): never {
        $customer = $this->getCustomer($user);

        $cardNumber = trim($body['card_number'] ?? '');
        if (!$cardNumber) Response::error('Card number is required.', 400, 'MISSING_CARD_NUMBER');

        $card = $this->db->queryOne(
            "SELECT id, status, is_preprinted, assigned_to_customer_id,
                    card_number, card_variant, card_image
             FROM cards WHERE card_number = ?",
            [$cardNumber]
        );

        if (!$card) Response::notFound('Card not found. Please check the number and try again.');

        if ($card['assigned_to_customer_id'] && (int)$card['assigned_to_customer_id'] !== (int)$customer['id']) {
            Response::error('This card is already assigned to another account.', 409, 'CARD_TAKEN');
        }

        if ($card['status'] === 'blocked') {
            Response::error('This card has been blocked. Please contact support.', 403, 'CARD_BLOCKED');
        }

        if ($card['status'] === 'activated') {
            Response::success(['message' => 'Card is already activated.']);
        }

        // Pre-printed cards require auth code validation
        if ($card['is_preprinted']) {
            $authCode = trim($body['auth_code'] ?? '');
            if (!$authCode) Response::error('Authorization code is required for pre-printed cards.', 400, 'MISSING_AUTH_CODE');

            $codeRow = $this->db->queryOne(
                "SELECT id FROM card_auth_codes
                 WHERE card_id = ? AND customer_id = ? AND auth_code = ?
                   AND used = 0 AND expires_at > NOW()
                 ORDER BY created_at DESC LIMIT 1",
                [$card['id'], $customer['id'], $authCode]
            );

            if (!$codeRow) {
                Response::error('Invalid or expired authorization code.', 403, 'INVALID_AUTH_CODE');
            }

            // Mark code as used
            $this->db->execute(
                "UPDATE card_auth_codes SET used = 1 WHERE id = ?",
                [$codeRow['id']]
            );
        }

        // Assign and activate
        $this->db->execute(
            "UPDATE cards
             SET assigned_to_customer_id = ?, status = 'activated', activated_at = NOW()
             WHERE id = ?",
            [$customer['id'], $card['id']]
        );

        $updated = $this->db->queryOne(
            "SELECT id, card_number, card_variant, card_image, status, generated_at, activated_at
             FROM cards WHERE id = ?",
            [$card['id']]
        );

        Response::success($updated, 'Card activated successfully.');
    }

    // ── POST /api/customers/card/select ──────────────────────────────────────
    // Method 1: customer self-selects a publicly available card configuration.
    public function selectCard(array $user, array $body): never {
        $customer = $this->getCustomer($user);

        $configId = isset($body['configuration_id']) ? (int)$body['configuration_id'] : 0;
        if (!$configId) { Response::error('configuration_id is required', 400); }

        // 1. Load configuration
        $config = $this->db->queryOne(
            "SELECT * FROM card_configurations WHERE id = ? AND status = 'active' AND is_publicly_selectable = 1",
            [$configId]
        );
        if (!$config) { Response::error('Card configuration not found or not available.', 404); }

        // 2. Check customer does not already have an active card
        $existing = $this->db->queryOne(
            "SELECT id FROM cards WHERE assigned_to_customer_id = ? AND status = 'activated'
             AND (expiry_date IS NULL OR expiry_date >= CURDATE()) LIMIT 1",
            [$customer['id']]
        );
        if ($existing) { Response::error('You already have an active loyalty card.', 409); }

        // 3. Check monthly_maximum
        if ($config['monthly_maximum']) {
            $thisMonth = $this->db->queryOne(
                "SELECT COUNT(*) AS cnt FROM cards
                 WHERE card_configuration_id = ? AND status = 'activated'
                 AND YEAR(activated_at) = YEAR(CURDATE()) AND MONTH(activated_at) = MONTH(CURDATE())",
                [$configId]
            );
            if ((int)$thisMonth['cnt'] >= (int)$config['monthly_maximum']) {
                Response::error('This card is no longer available for this month.', 409);
            }
        }

        // 4. Check city availability
        $customerCityId = $user['city_id'] ?? null;
        if ($customerCityId) {
            $cityRow   = $this->db->queryOne(
                "SELECT config_id FROM card_config_cities WHERE config_id = ? AND city_id = ?",
                [$configId, $customerCityId]
            );
            $allCities = $this->db->queryOne(
                "SELECT COUNT(*) AS cnt FROM card_config_cities WHERE config_id = ?",
                [$configId]
            );
            if ((int)$allCities['cnt'] > 0 && !$cityRow) {
                Response::error('This card is not available in your city.', 403);
            }
        }

        // 5. Reject paid cards (payment integration out of scope)
        if ((float)$config['price'] > 0) {
            Response::error('This card requires a payment. Online payment is not yet available.', 402);
        }

        // 6. Generate card number (prefix from classification + sequential)
        $prefix   = 'DM' . strtoupper(substr($config['classification'], 0, 3));
        $lastCard = $this->db->queryOne(
            "SELECT card_number FROM cards WHERE card_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );
        $nextNum    = $lastCard ? ((int)substr($lastCard['card_number'], strlen($prefix)) + 1) : 1;
        $cardNumber = $prefix . str_pad((string)$nextNum, 8, '0', STR_PAD_LEFT);

        // 7. Create card record
        $expiryDate = date('Y-m-d', strtotime('+' . (int)$config['validity_days'] . ' days'));
        $this->db->execute(
            "INSERT INTO cards (card_variant, card_number, card_image, card_configuration_id, is_preprinted,
              assigned_to_customer_id, status, generated_at, activated_at, expiry_date)
             VALUES (?, ?, ?, ?, 0, ?, 'activated', NOW(), NOW(), ?)",
            [
                $config['classification'],
                $cardNumber,
                $config['card_image_front'],
                $configId,
                $customer['id'],
                $expiryDate,
            ]
        );

        Response::success([
            'card_number'    => $cardNumber,
            'classification' => $config['classification'],
            'config_name'    => $config['name'],
            'expiry_date'    => $expiryDate,
            'message'        => 'Card activated successfully.',
        ], 201);
    }
}
