<?php
class PlatformSetting extends Model {

    protected $table = 'platform_settings';

    // ── Run migration on first use ────────────────────────────────────────────
    private function ensureTable(): void {
        $sql = file_get_contents(ROOT_PATH . '/migrations/create_platform_settings.sql');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt) { try { $this->db->exec($stmt); } catch (Exception $e) {} }
        }
    }

    // ── Fetch all settings as key→value map ───────────────────────────────────
    public function getAll(): array {
        $this->ensureTable();
        $rows = $this->db->query("SELECT setting_key, setting_value, description FROM platform_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['setting_key']] = ['value' => $r['setting_value'], 'description' => $r['description']];
        }
        return $map;
    }

    // ── Get single setting ────────────────────────────────────────────────────
    public function getSetting($key, $default = null) {
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : $default;
    }

    // ── Upsert a single setting ───────────────────────────────────────────────
    public function set(string $key, $value): bool {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            "INSERT INTO platform_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        return $stmt->execute([$key, $value]);
    }

    // ── Save many settings at once ────────────────────────────────────────────
    public function saveMany(array $data): bool {
        $this->ensureTable();
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO platform_settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );
            foreach ($data as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
