<?php
require_once 'BaseModel.php';
require_once __DIR__ . '/ActivityLogModel.php';

class IzinHakedis extends Model
{
    protected $table = 'izin_hakedis';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * İş Kanunu Madde 9'a göre yıllık izin gün sayısını hesaplar.
     * Hafta içi/hafta sonu ayrımı yoktur; kanun takvim günü değil, hakediş gün sayısı verir.
     *
     * @param int $hizmet_yili  İşe girişten bu yana tam yıl sayısı
     * @param int|null $yas     Personelin yaşı (null ise yaş kuralı uygulanmaz)
     * @return int
     */
    public static function calcHakedisGun(int $hizmet_yili, ?int $yas = null): int
    {
        if ($hizmet_yili < 1) return 0;

        if ($hizmet_yili <= 5) {
            $gun = 14;
        } elseif ($hizmet_yili < 15) {
            $gun = 20;
        } else {
            $gun = 26;
        }

        if ($yas !== null && ($yas <= 18 || $yas >= 50)) {
            $gun = max($gun, 20);
        }

        return $gun;
    }

    /**
     * Personelin hizmet yılına göre eksik hakedişlerini otomatik oluşturur.
     * Sadece daha önce kaydedilmemiş yıllar için kayıt ekler.
     */
    public function hesaplaVeKaydet(int $personel_id, int $firma_id, string $job_start_date, ?string $birth_date = null): int
    {
        $baslangic = new DateTime($job_start_date);
        $bugun = new DateTime();
        $toplam_yil = (int) $baslangic->diff($bugun)->y;

        if ($toplam_yil < 1) return 0;

        $yas = null;
        if ($birth_date) {
            $yas = (int) (new DateTime($birth_date))->diff($bugun)->y;
        }

        $eklenen = 0;
        for ($yil = 1; $yil <= $toplam_yil; $yil++) {
            $mevcut = $this->getByPersonelVeYil($personel_id, $yil);
            if ($mevcut) continue;

            $hakedis_tarihi = clone $baslangic;
            $hakedis_tarihi->modify("+{$yil} year");

            $gun = self::calcHakedisGun($yil, $yas);
            if ($gun === 0) continue;

            $this->attributes = [
                'firma_id'        => $firma_id,
                'personel_id'     => $personel_id,
                'yil'             => $yil,
                'hakedis_tarihi'  => $hakedis_tarihi->format('Y-m-d'),
                'gun_sayisi'      => $gun,
                'tip'             => 'otomatik',
            ];
            $this->insert();
            $eklenen++;
        }

        return $eklenen;
    }

    public function getByPersonel(int $personel_id): array
    {
        $sql = $this->db->prepare(
            "SELECT h.*,
                    COALESCE((SELECT SUM(k.kullanilan_gun) FROM izin_kullanim k
                              JOIN izin_talepler t ON t.id = k.talep_id
                              WHERE k.hakedis_id = h.id AND t.durum = 'onaylandi'), 0) AS talep_kullanilan
             FROM {$this->table} h
             WHERE h.personel_id = ?
             ORDER BY h.yil ASC"
        );
        $sql->execute([$personel_id]);
        $list = $sql->fetchAll(PDO::FETCH_OBJ);

        require_once __DIR__ . '/IzinDevirKullanim.php';
        $devirModel = new IzinDevirKullanim();
        $firma_id = (int) ($list[0]->firma_id ?? 0);
        $rem_devir = $devirModel->getTotalDevirByPersonel($personel_id, $firma_id);

        foreach ($list as $h) {
            $talep_kullanilan = (int) ($h->talep_kullanilan ?? 0);
            $rem_cap = max(0, (int) $h->gun_sayisi - $talep_kullanilan);
            $devir_kullanilan = min($rem_devir, $rem_cap);
            $rem_devir -= $devir_kullanilan;

            $h->devir_kullanilan = $devir_kullanilan;
            $h->kullanilan_gun = $talep_kullanilan + $devir_kullanilan;
            $h->kalan_gun = max(0, (int) $h->gun_sayisi - $h->kullanilan_gun);
        }

        return $list;
    }

    public function getByPersonelVeYil(int $personel_id, int $yil): ?object
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE personel_id = ? AND yil = ?");
        $sql->execute([$personel_id, $yil]);
        return $sql->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * FIFO sırasına göre kalan bakiyesi olan hakedişleri döner.
     */
    public function getFifoHakedisler(int $personel_id): array
    {
        $sql = $this->db->prepare(
            "SELECT h.*,
                    COALESCE((SELECT SUM(k.kullanilan_gun) FROM izin_kullanim k
                              JOIN izin_talepler t ON t.id = k.talep_id
                              WHERE k.hakedis_id = h.id AND t.durum = 'onaylandi'), 0) AS talep_kullanilan
             FROM {$this->table} h
             WHERE h.personel_id = ?
             ORDER BY h.yil ASC"
        );
        $sql->execute([$personel_id]);
        $hakedisler = $sql->fetchAll(PDO::FETCH_OBJ);

        if (empty($hakedisler)) return [];

        require_once __DIR__ . '/IzinDevirKullanim.php';
        $devirModel = new IzinDevirKullanim();
        $firma_id = (int) ($hakedisler[0]->firma_id ?? 0);
        $rem_devir = $devirModel->getTotalDevirByPersonel($personel_id, $firma_id);

        $fifoAvailable = [];
        foreach ($hakedisler as $h) {
            $talep_kullanilan = (int) ($h->talep_kullanilan ?? 0);
            $rem_cap = max(0, (int) $h->gun_sayisi - $talep_kullanilan);
            $devir_kullanilan = min($rem_devir, $rem_cap);
            $rem_devir -= $devir_kullanilan;

            $h->kullanilan_gun = $talep_kullanilan + $devir_kullanilan;
            $h->kalan_gun = (int) $h->gun_sayisi - $h->kullanilan_gun;

            if ($h->kalan_gun > 0) {
                $fifoAvailable[] = $h;
            }
        }

        return $fifoAvailable;
    }

    /**
     * Personelin toplam kalan yıllık izin bakiyesini döner.
     */
    public function getTotalKalan(int $personel_id): int
    {
        $sql = $this->db->prepare(
            "SELECT
                COALESCE(SUM(h.gun_sayisi), 0) -
                COALESCE((SELECT SUM(k.kullanilan_gun) FROM izin_kullanim k
                          JOIN izin_talepler t ON t.id = k.talep_id
                          WHERE t.personel_id = ? AND t.durum = 'onaylandi'), 0) AS kalan
             FROM {$this->table} h WHERE h.personel_id = ?"
        );
        $sql->execute([$personel_id, $personel_id]);
        $kalan = (int) ($sql->fetch(PDO::FETCH_OBJ)->kalan ?? 0);

        require_once __DIR__ . '/IzinDevirKullanim.php';
        $devirModel = new IzinDevirKullanim();
        $sqlFirma = $this->db->prepare("SELECT firma_id FROM {$this->table} WHERE personel_id = ? LIMIT 1");
        $sqlFirma->execute([$personel_id]);
        $firma_id = (int) ($sqlFirma->fetch(PDO::FETCH_OBJ)->firma_id ?? 0);

        $totalDevir = $devirModel->getTotalDevirByPersonel($personel_id, $firma_id);

        return max(0, $kalan - $totalDevir);
    }

    /**
     * Hakedişi yaklaşan personeller (önümüzdeki 30 gün içinde yeni hakediş kazanacaklar).
     */
    public function getYaklasanHakedisler(int $firma_id): array
    {
        $sql = $this->db->prepare(
            "SELECT p.id AS personel_id, p.full_name, p.job_start_date, p.birth_date,
                    TIMESTAMPDIFF(YEAR, p.job_start_date, CURDATE()) + 1 AS gelecek_yil,
                    DATE_ADD(p.job_start_date, INTERVAL (TIMESTAMPDIFF(YEAR, p.job_start_date, CURDATE()) + 1) YEAR) AS hakedis_tarihi
             FROM persons p
             WHERE p.firm_id = ? AND p.deleted_at IS NULL
               AND DATE_ADD(p.job_start_date, INTERVAL (TIMESTAMPDIFF(YEAR, p.job_start_date, CURDATE()) + 1) YEAR)
                   BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY hakedis_tarihi ASC"
        );
        $sql->execute([$firma_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getByFirma(int $firma_id, int $personel_id = 0): array
    {
        $where = $personel_id ? 'AND h.personel_id = :pid' : '';
        $params = [':fid' => $firma_id];
        if ($personel_id) $params[':pid'] = $personel_id;

        $sql = $this->db->prepare(
            "SELECT h.*, p.full_name AS personel_adi,
                    COALESCE((SELECT SUM(k.kullanilan_gun) FROM izin_kullanim k
                              JOIN izin_talepler t ON t.id = k.talep_id
                              WHERE k.hakedis_id = h.id AND t.durum = 'onaylandi'), 0) AS talep_kullanilan
             FROM {$this->table} h
             JOIN persons p ON p.id = h.personel_id
             WHERE h.firma_id = :fid $where
             ORDER BY p.full_name ASC, h.yil ASC"
        );
        $sql->execute($params);
        $list = $sql->fetchAll(PDO::FETCH_OBJ);

        require_once __DIR__ . '/IzinDevirKullanim.php';
        $devirModel = new IzinDevirKullanim();
        $devirTotals = $devirModel->getTotalsByFirma($firma_id);

        $grouped = [];
        foreach ($list as $item) {
            $grouped[$item->personel_id][] = $item;
        }

        foreach ($grouped as $pid => $hakedisler) {
            $rem_devir = $devirTotals[$pid] ?? 0;
            foreach ($hakedisler as $h) {
                $talep_kullanilan = (int) ($h->talep_kullanilan ?? 0);
                $rem_cap = max(0, (int) $h->gun_sayisi - $talep_kullanilan);
                $devir_kullanilan = min($rem_devir, $rem_cap);
                $rem_devir -= $devir_kullanilan;

                $h->devir_kullanilan = $devir_kullanilan;
                $h->kullanilan_gun = $talep_kullanilan + $devir_kullanilan;
                $h->kalan_gun = max(0, (int) $h->gun_sayisi - $h->kullanilan_gun);
            }
        }

        return $list;
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        $action = isset($data['id']) && $data['id'] > 0 ? 'update' : 'add';
        ActivityLogModel::log('izin_hakedis', $action, "Personel ID {$data['personel_id']} için {$data['yil']}. yıl hakedişi " . ($action === 'add' ? 'eklendi' : 'güncellendi') . ".");
        return $id;
    }

    public function deleteById(int $id): bool
    {
        $hakedis = $this->find($id);
        if ($hakedis) {
            ActivityLogModel::log('izin_hakedis', 'delete', "Personel ID {$hakedis->personel_id} için {$hakedis->yil}. yıl hakedişi silindi.");
        }
        $sql = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $sql->execute([$id]);
        return $sql->rowCount() > 0;
    }
}
