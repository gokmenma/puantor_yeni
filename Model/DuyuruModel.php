<?php
require_once ROOT . '/Model/BaseModel.php';

class DuyuruModel extends Model
{
    protected $table = 'duyurular';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getDuyurular($kullanici_id, $firma_id, $is_superadmin = false, $is_main_user = false, $kaynak_filter = 'duyuru')
    {
        $kaynak_sql = "d.kaynak_turu = 'duyuru'";
        if ($kaynak_filter === 'bildirim') {
            $kaynak_sql = "d.kaynak_turu IN ('sistem', 'kullanici')";
        } elseif ($kaynak_filter === 'all') {
            $kaynak_sql = "d.kaynak_turu IN ('duyuru', 'sistem', 'kullanici')";
        }

        if ($is_superadmin) {
            $sql = "SELECT d.*,
                        u.full_name as olusturan_adi,
                        (SELECT COUNT(*) FROM duyuru_okundu WHERE duyuru_id = d.id) as okunma_sayisi,
                        NULL as okundu_at
                    FROM duyurular d
                    LEFT JOIN users u ON u.id = d.olusturan_id
                    WHERE d.is_active = 1
                      AND {$kaynak_sql}
                    ORDER BY FIELD(d.oncelik,'acil','onemli','normal'), d.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $global_kosul = $is_main_user
                ? "(d.hedef_firma_id IS NULL AND d.hedef_tip = 'aboneler')"
                : "0";
            $sql = "SELECT d.*,
                        u.full_name as olusturan_adi,
                        (SELECT COUNT(*) FROM duyuru_okundu WHERE duyuru_id = d.id) as okunma_sayisi,
                        do2.okundu_at
                    FROM duyurular d
                    LEFT JOIN users u ON u.id = d.olusturan_id
                    LEFT JOIN duyuru_okundu do2 ON do2.duyuru_id = d.id AND do2.kullanici_id = :kullanici_id
                    WHERE d.is_active = 1
                      AND {$kaynak_sql}
                      AND (
                            (d.hedef_firma_id = :firma_id AND d.hedef_tip NOT IN ('aboneler', 'bazi_firmalar', 'bazi_kullanicilar', 'bazi_personeller'))
                            OR $global_kosul
                            OR (d.hedef_tip = 'bazi_firmalar' AND EXISTS (
                                SELECT 1 FROM duyuru_hedefler dh 
                                WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'firma' AND dh.hedef_id = :firma_id
                            ))
                            OR (d.hedef_tip = 'bazi_kullanicilar' AND EXISTS (
                                SELECT 1 FROM duyuru_hedefler dh 
                                WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'kullanici' AND dh.hedef_id = :kullanici_id
                            ))
                            OR (d.hedef_tip = 'bazi_personeller' AND EXISTS (
                                SELECT 1 FROM duyuru_hedefler dh 
                                JOIN persons p ON p.id = dh.hedef_id
                                JOIN users cu ON cu.email = p.email OR (p.phone IS NOT NULL AND cu.phone = p.phone)
                                WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'personel' AND cu.id = :kullanici_id
                            ))
                          )
                      AND (d.baslangic_tarihi IS NULL OR d.baslangic_tarihi <= CURDATE())
                      AND (d.bitis_tarihi IS NULL OR d.bitis_tarihi >= CURDATE())
                    ORDER BY FIELD(d.oncelik,'acil','onemli','normal'), d.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':kullanici_id' => $kullanici_id, ':firma_id' => $firma_id]);
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getBildirimler($kullanici_id, $firma_id, $is_superadmin = false, $is_main_user = false)
    {
        return $this->getDuyurular($kullanici_id, $firma_id, $is_superadmin, $is_main_user, 'bildirim');
    }

    public function getOkunmamisSayisi($kullanici_id, $firma_id, $is_main_user = false, $kaynak_filter = 'duyuru')
    {
        $kaynak_sql = "d.kaynak_turu = 'duyuru'";
        if ($kaynak_filter === 'bildirim') {
            $kaynak_sql = "d.kaynak_turu IN ('sistem', 'kullanici')";
        } elseif ($kaynak_filter === 'all') {
            $kaynak_sql = "d.kaynak_turu IN ('duyuru', 'sistem', 'kullanici')";
        }

        $global_kosul = $is_main_user
            ? "(d.hedef_firma_id IS NULL AND d.hedef_tip = 'aboneler')"
            : "0";
        $sql = "SELECT COUNT(*) as sayi
                FROM duyurular d
                LEFT JOIN duyuru_okundu do2 ON do2.duyuru_id = d.id AND do2.kullanici_id = :kullanici_id
                WHERE d.is_active = 1
                  AND {$kaynak_sql}
                  AND do2.id IS NULL
                  AND (
                        (d.hedef_firma_id = :firma_id AND d.hedef_tip NOT IN ('aboneler', 'bazi_firmalar', 'bazi_kullanicilar', 'bazi_personeller'))
                        OR $global_kosul
                        OR (d.hedef_tip = 'bazi_firmalar' AND EXISTS (
                            SELECT 1 FROM duyuru_hedefler dh 
                            WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'firma' AND dh.hedef_id = :firma_id
                        ))
                        OR (d.hedef_tip = 'bazi_kullanicilar' AND EXISTS (
                            SELECT 1 FROM duyuru_hedefler dh 
                            WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'kullanici' AND dh.hedef_id = :kullanici_id
                        ))
                        OR (d.hedef_tip = 'bazi_personeller' AND EXISTS (
                            SELECT 1 FROM duyuru_hedefler dh 
                            JOIN persons p ON p.id = dh.hedef_id
                            JOIN users cu ON cu.email = p.email OR (p.phone IS NOT NULL AND cu.phone = p.phone)
                            WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'personel' AND cu.id = :kullanici_id
                        ))
                      )
                  AND (d.baslangic_tarihi IS NULL OR d.baslangic_tarihi <= CURDATE())
                  AND (d.bitis_tarihi IS NULL OR d.bitis_tarihi >= CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':kullanici_id' => $kullanici_id, ':firma_id' => $firma_id]);
        return (int) $stmt->fetch(PDO::FETCH_OBJ)->sayi;
    }

    public function getOkunmamisBildirimSayisi($kullanici_id, $firma_id, $is_main_user = false)
    {
        return $this->getOkunmamisSayisi($kullanici_id, $firma_id, $is_main_user, 'bildirim');
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM duyurular WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function ekle($data)
    {
        $sql = "INSERT INTO duyurular
                    (baslik, icerik, kaynak_turu, olusturan_id, hedef_tip, hedef_firma_id, baslangic_tarihi, bitis_tarihi, oncelik, is_active)
                VALUES
                    (:baslik, :icerik, :kaynak_turu, :olusturan_id, :hedef_tip, :hedef_firma_id, :baslangic_tarihi, :bitis_tarihi, :oncelik, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':baslik'           => $data['baslik'],
            ':icerik'           => $data['icerik'],
            ':kaynak_turu'      => $data['kaynak_turu'] ?? 'duyuru',
            ':olusturan_id'     => $data['olusturan_id'],
            ':hedef_tip'        => $data['hedef_tip'],
            ':hedef_firma_id'   => $data['hedef_firma_id'] ?? null,
            ':baslangic_tarihi' => $data['baslangic_tarihi'] ?: null,
            ':bitis_tarihi'     => $data['bitis_tarihi'] ?: null,
            ':oncelik'          => $data['oncelik'] ?? 'normal',
        ]);
        return $this->db->lastInsertId();
    }

    public function guncelle($id, $data)
    {
        $sql = "UPDATE duyurular SET
                    baslik = :baslik,
                    icerik = :icerik,
                    hedef_tip = :hedef_tip,
                    hedef_firma_id = :hedef_firma_id,
                    baslangic_tarihi = :baslangic_tarihi,
                    bitis_tarihi = :bitis_tarihi,
                    oncelik = :oncelik,
                    is_active = :is_active
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'               => $id,
            ':baslik'           => $data['baslik'],
            ':icerik'           => $data['icerik'],
            ':hedef_tip'        => $data['hedef_tip'],
            ':hedef_firma_id'   => $data['hedef_firma_id'] ?? null,
            ':baslangic_tarihi' => $data['baslangic_tarihi'] ?: null,
            ':bitis_tarihi'     => $data['bitis_tarihi'] ?: null,
            ':oncelik'          => $data['oncelik'] ?? 'normal',
            ':is_active'        => $data['is_active'] ?? 1,
        ]);
    }

    public function sil($id)
    {
        $stmt = $this->db->prepare("DELETE FROM duyurular WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function okunduIsaretle($duyuru_id, $kullanici_id)
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO duyuru_okundu (duyuru_id, kullanici_id) VALUES (:duyuru_id, :kullanici_id)"
        );
        return $stmt->execute([':duyuru_id' => $duyuru_id, ':kullanici_id' => $kullanici_id]);
    }

    public function tumunuOkunduIsaretle($kullanici_id, $firma_id, $kaynak_filter = 'duyuru')
    {
        $kaynak_sql = "d.kaynak_turu = 'duyuru'";
        if ($kaynak_filter === 'bildirim') {
            $kaynak_sql = "d.kaynak_turu IN ('sistem', 'kullanici')";
        } elseif ($kaynak_filter === 'all') {
            $kaynak_sql = "d.kaynak_turu IN ('duyuru', 'sistem', 'kullanici')";
        }

        $sql = "INSERT IGNORE INTO duyuru_okundu (duyuru_id, kullanici_id)
                SELECT d.id, :kullanici_id
                FROM duyurular d
                WHERE d.is_active = 1
                  AND {$kaynak_sql}
                  AND (
                        (d.hedef_firma_id = :firma_id AND d.hedef_tip NOT IN ('aboneler', 'bazi_firmalar', 'bazi_kullanicilar', 'bazi_personeller'))
                        OR (d.hedef_firma_id IS NULL AND d.hedef_tip = 'aboneler')
                        OR (d.hedef_tip = 'bazi_firmalar' AND EXISTS (
                            SELECT 1 FROM duyuru_hedefler dh 
                            WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'firma' AND dh.hedef_id = :firma_id
                        ))
                        OR (d.hedef_tip = 'bazi_kullanicilar' AND EXISTS (
                            SELECT 1 FROM duyuru_hedefler dh 
                            WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'kullanici' AND dh.hedef_id = :kullanici_id
                        ))
                        OR (d.hedef_tip = 'bazi_personeller' AND EXISTS (
                            SELECT 1 FROM duyuru_hedefler dh 
                            JOIN persons p ON p.id = dh.hedef_id
                            JOIN users cu ON cu.email = p.email OR (p.phone IS NOT NULL AND cu.phone = p.phone)
                            WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'personel' AND cu.id = :kullanici_id
                        ))
                      )
                  AND (d.baslangic_tarihi IS NULL OR d.baslangic_tarihi <= CURDATE())
                  AND (d.bitis_tarihi IS NULL OR d.bitis_tarihi >= CURDATE())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':kullanici_id' => $kullanici_id, ':firma_id' => $firma_id]);
    }

    public function getSistemBildirimleri(?int $firma_id, int $limit = 100): array
    {
        $scope = $firma_id === null
            ? ''
            : "AND (
                    d.hedef_firma_id = :firma_id
                    OR d.hedef_tip = 'aboneler'
                    OR (d.hedef_tip = 'bazi_firmalar' AND EXISTS (
                        SELECT 1 FROM duyuru_hedefler dh
                        WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'firma' AND dh.hedef_id = :firma_id
                    ))
                    OR (d.hedef_tip = 'bazi_kullanicilar' AND EXISTS (
                        SELECT 1
                        FROM duyuru_hedefler dh
                        JOIN users hu ON hu.id = dh.hedef_id
                        WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'kullanici' AND hu.firm_id = :firma_id
                    ))
                    OR (d.hedef_tip = 'bazi_personeller' AND EXISTS (
                        SELECT 1
                        FROM duyuru_hedefler dh
                        JOIN persons hp ON hp.id = dh.hedef_id
                        WHERE dh.duyuru_id = d.id AND dh.hedef_tip = 'personel' AND hp.firm_id = :firma_id
                    ))
                )";
        $sql = "SELECT d.*, u.full_name AS olusturan_adi
                FROM duyurular d
                LEFT JOIN users u ON u.id = d.olusturan_id
                WHERE d.kaynak_turu = 'sistem'
                  {$scope}
                ORDER BY d.created_at DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        if ($firma_id !== null) {
            $stmt->bindValue(':firma_id', $firma_id, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getOkunmaDetayi($duyuru_id)
    {
        $sql = "SELECT u.full_name, u.email, do2.okundu_at
                FROM duyuru_okundu do2
                JOIN users u ON u.id = do2.kullanici_id
                WHERE do2.duyuru_id = :duyuru_id
                ORDER BY do2.okundu_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':duyuru_id' => $duyuru_id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function setHedefler($duyuru_id, $hedef_tip, $hedef_ids)
    {
        $stmt = $this->db->prepare("DELETE FROM duyuru_hedefler WHERE duyuru_id = :duyuru_id");
        $stmt->execute([':duyuru_id' => $duyuru_id]);

        if (empty($hedef_ids)) {
            return;
        }

        $sql = "INSERT INTO duyuru_hedefler (duyuru_id, hedef_tip, hedef_id) VALUES (:duyuru_id, :hedef_tip, :hedef_id)";
        $stmt = $this->db->prepare($sql);
        foreach ($hedef_ids as $id) {
            $stmt->execute([
                ':duyuru_id' => $duyuru_id,
                ':hedef_tip' => $hedef_tip,
                ':hedef_id'  => $id
            ]);
        }
    }

    public function getHedefler($duyuru_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM duyuru_hedefler WHERE duyuru_id = :duyuru_id");
        $stmt->execute([':duyuru_id' => $duyuru_id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
