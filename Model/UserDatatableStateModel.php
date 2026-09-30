<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/ActivityLogModel.php';

class UserDatatableStateModel extends Model
{
    protected $table = 'user_datatable_states';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Kullanıcının belirli bir tablo için kaydettiği durumunu getirir.
     *
     * @param int $userId
     * @param string $tableKey
     * @return array|null
     */
    public function getState(int $userId, string $tableKey): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT state_data FROM {$this->table} WHERE user_id = ? AND table_key = ? LIMIT 1");
            $stmt->execute([$userId, $tableKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['state_data'])) {
                $decoded = json_decode($row['state_data'], true);
                return is_array($decoded) ? $decoded : null;
            }
        } catch (\Throwable $e) {
            system_log_exception($e, ['operation' => 'get_datatable_state', 'user_id' => $userId, 'table_key' => $tableKey]);
        }
        return null;
    }

    /**
     * Kullanıcının tüm tablo durumlarını getirir (sayfa açılışlarında cache/preload amaçlı)
     *
     * @param int $userId
     * @return array
     */
    public function getAllStatesForUser(int $userId): array
    {
        $result = [];
        try {
            $stmt = $this->db->prepare("SELECT table_key, state_data FROM {$this->table} WHERE user_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                if (!empty($row['table_key']) && !empty($row['state_data'])) {
                    $decoded = json_decode($row['state_data'], true);
                    if (is_array($decoded)) {
                        $result[$row['table_key']] = $decoded;
                    }
                }
            }
        } catch (\Throwable $e) {
            system_log_exception($e, ['operation' => 'get_all_datatable_states', 'user_id' => $userId]);
        }
        return $result;
    }

    /**
     * Kullanıcı tablo durumunu kaydeder (Upsert)
     *
     * @param int $userId
     * @param int|null $firmId
     * @param string $tableKey
     * @param array|string $stateData
     * @return bool
     */
    public function saveState(int $userId, ?int $firmId, string $tableKey, $stateData): bool
    {
        try {
            $jsonString = is_string($stateData) ? $stateData : json_encode($stateData, JSON_UNESCAPED_UNICODE);

            $stmt = $this->db->prepare("
                INSERT INTO {$this->table} (user_id, firm_id, table_key, state_data, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    firm_id = VALUES(firm_id),
                    state_data = VALUES(state_data),
                    updated_at = NOW()
            ");

            return $stmt->execute([$userId, $firmId, $tableKey, $jsonString]);
        } catch (\Throwable $e) {
            system_log_exception($e, ['operation' => 'save_datatable_state', 'user_id' => $userId, 'table_key' => $tableKey]);
            return false;
        }
    }

    /**
     * Kullanıcının tablo durumunu sıfırlar / siler
     *
     * @param int $userId
     * @param string $tableKey
     * @return bool
     */
    public function resetState(int $userId, string $tableKey): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ? AND table_key = ?");
            $result = $stmt->execute([$userId, $tableKey]);

            ActivityLogModel::log(
                'datatable_settings',
                'reset',
                "Kullanıcı '{$tableKey}' tablosu için özelleştirilmiş görünümü sıfırladı."
            );

            return $result;
        } catch (\Throwable $e) {
            system_log_exception($e, ['operation' => 'reset_datatable_state', 'user_id' => $userId, 'table_key' => $tableKey]);
            return false;
        }
    }
}
