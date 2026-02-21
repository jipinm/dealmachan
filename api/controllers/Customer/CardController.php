<?php
/**
 * Customer Card Controller
 *
 * GET  /api/customers/card              — get assigned card
 * POST /api/customers/card/activate     — activate preprinted card by number
 */
class CardController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function getCustomer(array $user): array {
        $row = $this->db->queryOne(
            "SELECT id, name FROM customers c JOIN users u ON u.id = c.user_id WHERE u.id = ?",
            [$user['id']]
        );
        if (!$row) Response::notFound('Customer profile not found.');
        return $row;
    }

    // ── GET /api/customers/card ───────────────────────────────────────────────
    public function show(array $user): never {
        $customer = $this->getCustomer($user);

        $card = $this->db->queryOne(
            "SELECT cc.id, cc.card_number, cc.card_variant, cc.card_image,
                    cc.status, cc.generated_at, cc.activated_at
             FROM customer_cards cc
             WHERE cc.customer_id = ?
             ORDER BY cc.generated_at DESC
             LIMIT 1",
            [$customer['id']]
        );

        Response::success($card); // null if no card
    }

    // ── POST /api/customers/card/activate ────────────────────────────────────
    public function activate(array $user, array $body): never {
        $customer = $this->getCustomer($user);

        $cardNumber = trim($body['card_number'] ?? '');
        if (!$cardNumber) Response::error('Card number is required.', 400, 'MISSING_CARD_NUMBER');

        // Find the card in the DB (pre-printed, unassigned)
        $card = $this->db->queryOne(
            "SELECT id, status, customer_id FROM customer_cards WHERE card_number = ?",
            [$cardNumber]
        );

        if (!$card) Response::notFound('Card not found. Please check the number and try again.');

        if ($card['customer_id'] && (int)$card['customer_id'] !== (int)$customer['id']) {
            Response::error('This card is already assigned to another account.', 409, 'CARD_TAKEN');
        }

        if ($card['status'] === 'blocked') {
            Response::error('This card has been blocked. Please contact support.', 403, 'CARD_BLOCKED');
        }

        // Assign + activate
        $this->db->execute(
            "UPDATE customer_cards
             SET customer_id = ?, status = 'active', activated_at = NOW()
             WHERE id = ?",
            [$customer['id'], $card['id']]
        );

        // Fetch updated card
        $updated = $this->db->queryOne(
            "SELECT id, card_number, card_variant, card_image, status, generated_at, activated_at
             FROM customer_cards WHERE id = ?",
            [$card['id']]
        );

        Response::success($updated, 'Card activated successfully');
    }
}
