<?php

class Contest {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── Contests ────────────────────────────────────────────────────────────

    public function getAllWithDetails(array $filters = [], int $limit = 20, int $offset = 0): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'c.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'c.title LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT c.*,
                    a.name AS created_by_name,
                    COUNT(DISTINCT cp.id) AS participant_count,
                    COUNT(DISTINCT cw.id) AS winner_count
                FROM contests c
                LEFT JOIN admins a ON c.created_by_admin_id = a.id
                LEFT JOIN contest_participants cp ON cp.contest_id = c.id
                LEFT JOIN contest_winners cw ON cw.contest_id = c.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY c.id
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countWithDetails(array $filters = []): int {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'title LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT COUNT(*) FROM contests WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findWithDetails(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT c.*, a.name AS created_by_name,
                COUNT(DISTINCT cp.id) AS participant_count,
                COUNT(DISTINCT cw.id) AS winner_count
             FROM contests c
             LEFT JOIN admins a ON c.created_by_admin_id = a.id
             LEFT JOIN contest_participants cp ON cp.contest_id = c.id
             LEFT JOIN contest_winners cw ON cw.contest_id = c.id
             WHERE c.id = ?
             GROUP BY c.id"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getStats(): array {
        $row = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status='draft') AS draft,
                SUM(status='active') AS active,
                SUM(status='completed') AS completed,
                SUM(status='cancelled') AS cancelled,
                (SELECT COUNT(*) FROM contest_participants) AS total_participants,
                (SELECT COUNT(*) FROM contest_winners) AS total_winners
             FROM contests"
        )->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function createContest(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO contests (title, description, rules_json, start_date, end_date, status, created_by_admin_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['rules_json'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['status'] ?? 'draft',
            $data['created_by_admin_id'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateContest(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE contests SET title=?, description=?, rules_json=?, start_date=?, end_date=?, updated_at=NOW()
             WHERE id=?"
        );
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['rules_json'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $id,
        ]);
    }

    public function setStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE contests SET status=?, updated_at=NOW() WHERE id=?");
        return $stmt->execute([$status, $id]);
    }

    public function deleteContest(int $id): bool {
        // Only allow deletion of draft contests
        $stmt = $this->db->prepare("DELETE FROM contests WHERE id=? AND status='draft'");
        return $stmt->execute([$id]) && $stmt->rowCount() > 0;
    }

    // ─── Participants ─────────────────────────────────────────────────────────

    public function getParticipants(int $contestId): array {
        $stmt = $this->db->prepare(
            "SELECT cp.*, c.name AS customer_name, u.phone, u.email,
                    (SELECT id FROM contest_winners WHERE contest_id=cp.contest_id AND customer_id=cp.customer_id LIMIT 1) AS winner_id,
                    (SELECT position FROM contest_winners WHERE contest_id=cp.contest_id AND customer_id=cp.customer_id LIMIT 1) AS position
             FROM contest_participants cp
             JOIN customers c ON cp.customer_id = c.id
             JOIN users u ON c.user_id = u.id
             WHERE cp.contest_id = ?
             ORDER BY cp.participated_at ASC"
        );
        $stmt->execute([$contestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── Winners ─────────────────────────────────────────────────────────────

    public function getWinners(int $contestId): array {
        $stmt = $this->db->prepare(
            "SELECT cw.*, c.name AS customer_name, u.phone, u.email
             FROM contest_winners cw
             JOIN customers c ON cw.customer_id = c.id
             JOIN users u ON c.user_id = u.id
             WHERE cw.contest_id = ?
             ORDER BY cw.position ASC"
        );
        $stmt->execute([$contestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectWinner(int $contestId, int $customerId, int $position, string $prizeDetails): int {
        // Upsert: remove existing entry at this position
        $this->db->prepare("DELETE FROM contest_winners WHERE contest_id=? AND position=?")->execute([$contestId, $position]);
        $stmt = $this->db->prepare(
            "INSERT INTO contest_winners (contest_id, customer_id, position, prize_details)
             VALUES (?,?,?,?)"
        );
        $stmt->execute([$contestId, $customerId, $position, $prizeDetails]);
        return (int)$this->db->lastInsertId();
    }

    public function removeWinner(int $winnerId): bool {
        return $this->db->prepare("DELETE FROM contest_winners WHERE id=?")->execute([$winnerId]);
    }
}
