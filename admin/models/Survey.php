<?php
class Survey extends Model {
    protected $table = 'surveys';

    // ─── LIST ──────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        $sql = "SELECT s.*,
                       a.name AS created_by_name,
                       (SELECT COUNT(*) FROM survey_responses sr WHERE sr.survey_id = s.id) AS response_count
                FROM {$this->table} s
                LEFT JOIN admins a ON s.created_by_admin_id = a.id
                WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $sql .= " ORDER BY s.created_at DESC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table} s WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function applyFilters(&$sql, &$params, $filters) {
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
            $params[] = $like; $params[] = $like;
        }
    }

    // ─── SINGLE ───────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $stmt = $this->db->prepare(
            "SELECT s.*, a.name AS created_by_name,
                    (SELECT COUNT(*) FROM survey_responses sr WHERE sr.survey_id = s.id) AS response_count
             FROM {$this->table} s
             LEFT JOIN admins a ON s.created_by_admin_id = a.id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ─── STATS ────────────────────────────────────────────────────────────────

    public function getStats() {
        return $this->db->query(
            "SELECT
               COUNT(*)                         AS total,
               SUM(status = 'draft')            AS draft,
               SUM(status = 'active')           AS active,
               SUM(status = 'closed')           AS closed,
               (SELECT COUNT(*) FROM survey_responses) AS total_responses,
               SUM(DATE(created_at) = CURDATE()) AS created_today
             FROM {$this->table}"
        )->fetch();
    }

    // ─── CREATE / UPDATE / DELETE ─────────────────────────────────────────────

    public function createSurvey($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
             (title, description, questions_json, status, created_by_admin_id, active_from, active_until)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['title'],
            $data['description'] ?: null,
            $data['questions_json'],
            $data['status'],
            $data['created_by_admin_id'],
            $data['active_from'] ?: null,
            $data['active_until'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

    public function updateSurvey($id, $data) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET title = ?, description = ?, questions_json = ?, status = ?,
                 active_from = ?, active_until = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['title'],
            $data['description'] ?: null,
            $data['questions_json'],
            $data['status'],
            $data['active_from'] ?: null,
            $data['active_until'] ?: null,
            $id,
        ]);
    }

    public function deleteSurvey($id) {
        // Only draft surveys can be deleted
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE id = ? AND status = 'draft'"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function setStatus($id, $status) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = ? WHERE id = ?"
        );
        return $stmt->execute([$status, $id]);
    }

    // ─── RESPONSES ────────────────────────────────────────────────────────────

    public function getResponses($surveyId, $filters = []) {
        $sql = "SELECT sr.id, sr.survey_id, sr.responses_json, sr.submitted_at,
                       c.name AS customer_name, u.phone AS customer_phone
                FROM survey_responses sr
                JOIN customers c ON sr.customer_id = c.id
                JOIN users     u ON c.user_id = u.id
                WHERE sr.survey_id = ?";
        $params = [$surveyId];
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR u.phone LIKE ?)";
            $params[] = $like; $params[] = $like;
        }
        $sql .= " ORDER BY sr.submitted_at DESC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countResponses($surveyId, $filters = []) {
        $sql = "SELECT COUNT(*) FROM survey_responses sr
                JOIN customers c ON sr.customer_id = c.id
                JOIN users     u ON c.user_id = u.id
                WHERE sr.survey_id = ?";
        $params = [$surveyId];
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR u.phone LIKE ?)";
            $params[] = $like; $params[] = $like;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Aggregate answers per question for analytics.
     * Returns [ question_id => [ 'question'=>..., 'type'=>..., 'answers'=>[...] ] ]
     */
    public function getResponseAnalytics($surveyId) {
        $survey = $this->findWithDetails($surveyId);
        if (!$survey) return [];

        $questions = json_decode($survey['questions_json'], true) ?? [];
        $responses = $this->db->prepare(
            "SELECT responses_json FROM survey_responses WHERE survey_id = ?"
        );
        $responses->execute([$surveyId]);
        $rawResponses = $responses->fetchAll(\PDO::FETCH_COLUMN);

        $analytics = [];
        foreach ($questions as $q) {
            $analytics[$q['id']] = [
                'question' => $q['question'],
                'type'     => $q['type'],
                'answers'  => [],
            ];
        }

        foreach ($rawResponses as $raw) {
            $data = json_decode($raw, true) ?? [];
            foreach ($data as $qId => $answer) {
                if (isset($analytics[$qId])) {
                    if (is_array($answer)) {
                        foreach ($answer as $val) {
                            $analytics[$qId]['answers'][] = $val;
                        }
                    } else {
                        $analytics[$qId]['answers'][] = $answer;
                    }
                }
            }
        }

        // Count frequencies for radio/checkbox/rating/select
        foreach ($analytics as &$q) {
            if (in_array($q['type'], ['radio', 'checkbox', 'rating', 'select'])) {
                $q['frequency'] = array_count_values(array_map('strval', $q['answers']));
                arsort($q['frequency']);
            }
        }
        return $analytics;
    }
}
