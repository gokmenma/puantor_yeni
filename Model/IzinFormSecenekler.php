<?php
require_once 'BaseModel.php';
require_once __DIR__ . '/SettingsModel.php';

class IzinFormSecenekler extends Model
{
    protected $table = 'izin_form_secenekler';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Belirli bir firmaya ve tipe ait seçenekleri getirir.
     */
    public function getOptions(int $firma_id, string $tip): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE firma_id = ? AND tip = ? ORDER BY deger ASC"
        );
        $sql->execute([$firma_id, $tip]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Yeni bir seçenek ekler (eğer daha önce eklenmemişse).
     */
    public function addOption(int $firma_id, string $tip, string $deger): int
    {
        $deger = trim($deger);
        if ($deger === '') {
            return 0;
        }

        // Çakışma kontrolü
        $sql = $this->db->prepare(
            "SELECT id FROM {$this->table} WHERE firma_id = ? AND tip = ? AND LOWER(deger) = LOWER(?)"
        );
        $sql->execute([$firma_id, $tip, $deger]);
        $existing = $sql->fetch(PDO::FETCH_OBJ);

        if ($existing) {
            return (int) $existing->id;
        }

        $sqlInsert = $this->db->prepare(
            "INSERT INTO {$this->table} (firma_id, tip, deger) VALUES (?, ?, ?)"
        );
        $sqlInsert->execute([$firma_id, $tip, $deger]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Bir seçeneği siler.
     */
    public function deleteOption(int $id): bool
    {
        $sql = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $sql->execute([$id]);
    }

    /**
     * Firmanın son kullanılan imza seçimlerini getirir.
     */
    public function getLastSelections(int $firma_id): array
    {
        $settingsMdl = new SettingsModel();
        
        // SettingsModel normalde getSettings() fonksiyonunda firm_id'yi $_SESSION['firm_id']'den alır.
        // Ama biz yine de garanti olsun diye kendimiz sorgulayabiliriz ya da oradan çağırabiliriz.
        // Kendimiz direkt sorgulayalım, daha bağımsız olur.
        $keys = [
            'izin_form_onaylayan_unvan_1', 'izin_form_onaylayan_isim_1',
            'izin_form_onaylayan_unvan_2', 'izin_form_onaylayan_isim_2',
            'izin_form_onaylayan_unvan_3', 'izin_form_onaylayan_isim_3'
        ];
        
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $sql = $this->db->prepare(
            "SELECT set_name, set_value FROM settings WHERE firm_id = ? AND set_name IN ($placeholders)"
        );
        
        $params = array_merge([$firma_id], $keys);
        $sql->execute($params);
        $rows = $sql->fetchAll(PDO::FETCH_OBJ);
        
        $selections = [];
        // Varsayılan boş değerler
        foreach ($keys as $k) {
            $selections[$k] = '';
        }
        
        foreach ($rows as $row) {
            $selections[$row->set_name] = $row->set_value;
        }
        
        return $selections;
    }

    /**
     * Firmanın son kullanılan imza seçimlerini kaydeder.
     */
    public function saveLastSelections(int $firma_id, array $selections): bool
    {
        $settingsMdl = new SettingsModel();
        foreach ($selections as $key => $val) {
            if (strpos($key, 'izin_form_onaylayan_') === 0) {
                // SettingsModel::upsertSetting firm_id'yi session'dan alır, o yüzden direct db query kullanalım
                $sqlCheck = $this->db->prepare(
                    "SELECT id FROM settings WHERE firm_id = ? AND set_name = ?"
                );
                $sqlCheck->execute([$firma_id, $key]);
                $existing = $sqlCheck->fetch(PDO::FETCH_OBJ);
                
                if ($existing) {
                    $sqlUpdate = $this->db->prepare(
                        "UPDATE settings SET set_value = ? WHERE id = ?"
                    );
                    $sqlUpdate->execute([$val, $existing->id]);
                } else {
                    $sqlInsert = $this->db->prepare(
                        "INSERT INTO settings (firm_id, user_id, set_name, set_value) VALUES (?, 0, ?, ?)"
                    );
                    $sqlInsert->execute([$firma_id, $key, $val]);
                }
            }
        }
        return true;
    }
}
