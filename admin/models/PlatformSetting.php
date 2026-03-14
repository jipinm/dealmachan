<?php
class PlatformSetting extends Model {

    protected $table = 'platform_settings';

    // ── Fetch all settings as key→value map ───────────────────────────────────
    public function getAll(): array {
        $rows = $this->db->query("SELECT setting_key, setting_value, description FROM platform_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['setting_key']] = ['value' => $r['setting_value'], 'description' => $r['description']];
        }
        return $map;
    }

    // ── Get single setting ────────────────────────────────────────────────────
    public function getSetting($key, $default = null) {
        $stmt = $this->db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : $default;
    }

    // ── Upsert a single setting ───────────────────────────────────────────────
    public function set(string $key, $value): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO platform_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        return $stmt->execute([$key, $value]);
    }

    // ── Save many settings at once ────────────────────────────────────────────
    public function saveMany(array $data): bool {
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
