<?php
require_once 'BaseModel.php';
require_once __DIR__ . '/IzinHakedis.php';
require_once __DIR__ . '/ActivityLogModel.php';

class IzinTalep extends Model
{
    protected $table = 'izin_talepler';

    private IzinHakedis $hakedisModel;

    public function __construct()
    {
        parent::__construct($this->table);
        $this->hakedisModel = new IzinHakedis();
    }

    /**
     * Verilen tarih aralığındaki iş günü sayısını hesaplar.
     * Cumartesi, Pazar ve resmi tatiller çalışma günü sayılmaz.
     */
    public function calcIsGunu(string $baslangic, string $bitis): int
    {
        $tatiller = $this->getResmiTatilDates($baslangic, $bitis);

        $current = new DateTime($baslangic);
        $end = new DateTime($bitis);
        $gun = 0;

        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('N');
            $dateStr = $current->format('Y-m-d');

            if ($dayOfWeek < 6 && !in_array($dateStr, $tatiller)) {
                $gun++;
            }
            $current->modify('+1 day');
        }

        return $gun;
    }

    private function getResmiTatilDates(string $baslangic, string $bitis): array
    {
        $sql = $this->db->prepare(
            "SELECT tarih FROM resmi_tatiller WHERE tarih BETWEEN ? AND ?"
        );
        $sql->execute([$baslangic, $bitis]);
        return array_column($sql->fetchAll(PDO::FETCH_OBJ), 'tarih');
    }

    /**
     * Aynı kişi için tarih aralığı çakışması kontrolü.
     * izin_talepler'de onaylı/beklemede kayıtlar ve puantaj'da rapor türleri kontrol edilir.
     *
     * @return array  Çakışan kayıtlar, boş dizi ise çakışma yok
     */
    public function checkCakisma(int $personel_id, string $baslangic, string $bitis, int $exclude_id = 0): array
    {
        $cakismalar = [];

        $sql = $this->db->prepare(
            "SELECT t.*, it.ad AS tur_adi
             FROM {$this->table} t
             JOIN izin_turler it ON it.id = t.tur_id
             WHERE t.personel_id = ?
               AND t.durum IN ('beklemede','onaylandi')
               AND t.id != ?
               AND t.baslangic_tarihi <= ? AND t.bitis_tarihi >= ?"
        );
        $sql->execute([$personel_id, $exclude_id, $bitis, $baslangic]);
        $cakismalar = $sql->fetchAll(PDO::FETCH_OBJ);

        $rapor_puantaj_ids = [46, 56, 57];
        $placeholders = implode(',', array_fill(0, count($rapor_puantaj_ids), '?'));
        $params = array_merge([$personel_id, $baslangic, $bitis], $rapor_puantaj_ids);
        $sql2 = $this->db->prepare(
            "SELECT p.gun, pt.PuantajAdi
             FROM puantaj p
             JOIN puantajturu pt ON pt.id = p.puantaj_id
             WHERE p.person = ?
               AND p.gun BETWEEN ? AND ?
               AND p.puantaj_id IN ($placeholders)
             LIMIT 5"
        );
        $sql2->execute($params);
        $raporlar = $sql2->fetchAll(PDO::FETCH_OBJ);

        foreach ($raporlar as $r) {
            $cakismalar[] = (object)[
                'tur_adi'          => $r->PuantajAdi,
                'baslangic_tarihi' => $r->gun,
                'bitis_tarihi'     => $r->gun,
                'kaynak'           => 'puantaj',
            ];
        }

        return $cakismalar;
    }

    /**
     * Yeni izin talebi oluşturur (sadece yıllık izin için bakiye ve çakışma kontrolü yapar).
     *
     * @return array ['success' => bool, 'message' => string, 'id' => encrypted_id|null]
     */
    public function olustur(array $data): array
    {
        $gun_sayisi = $this->calcIsGunu($data['baslangic_tarihi'], $data['bitis_tarihi']);
        if ($gun_sayisi <= 0) {
            return ['success' => false, 'message' => 'Seçilen tarih aralığında iş günü bulunmuyor.'];
        }

        $cakismalar = $this->checkCakisma(
            (int) $data['personel_id'],
            $data['baslangic_tarihi'],
            $data['bitis_tarihi']
        );
        if (!empty($cakismalar)) {
            $ilk = $cakismalar[0];
            return ['success' => false, 'message' => "Seçilen tarihler '{$ilk->tur_adi}' kaydıyla çakışıyor ({$ilk->baslangic_tarihi})."];
        }

        require_once __DIR__ . '/IzinTur.php';
        $turModel = new IzinTur();
        $tur = $turModel->find((int) $data['tur_id']);

        if ($tur && $tur->kod === 'yillik') {
            $kalan = $this->hakedisModel->getTotalKalan((int) $data['personel_id']);
            if ($kalan < $gun_sayisi) {
                return ['success' => false, 'message' => "Yeterli yıllık izin bakiyeniz yok. Kalan: {$kalan} gün, Talep: {$gun_sayisi} gün."];
            }
        }

        $this->attributes = [
            'firma_id'        => $data['firma_id'],
            'personel_id'     => $data['personel_id'],
            'tur_id'          => $data['tur_id'],
            'baslangic_tarihi'=> $data['baslangic_tarihi'],
            'bitis_tarihi'    => $data['bitis_tarihi'],
            'gun_sayisi'      => $gun_sayisi,
            'durum'           => 'beklemede',
            'aciklama'        => $data['aciklama'] ?? null,
            'olusturan_id'    => $data['olusturan_id'] ?? null,
        ];
        $enc_id = $this->insert();

        ActivityLogModel::log('izin', 'add', "Personel ID {$data['personel_id']} için {$gun_sayisi} günlük izin talebi oluşturuldu ({$data['baslangic_tarihi']} - {$data['bitis_tarihi']}).");

        return ['success' => true, 'message' => 'İzin talebi oluşturuldu.', 'id' => $enc_id];
    }

    /**
     * Beklemedeki izin talebini günceller.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function guncelle(int $talep_id, array $data): array
    {
        $talep = $this->find($talep_id);
        if (!$talep) {
            return ['success' => false, 'message' => 'Talep bulunamadı.'];
        }
        if ($talep->durum !== 'beklemede') {
            return ['success' => false, 'message' => 'Sadece beklemedeki talepler güncellenebilir.'];
        }
        if ((int) $talep->personel_id !== (int) $data['personel_id']) {
            return ['success' => false, 'message' => 'Yetkisiz işlem.'];
        }

        $gun_sayisi = $this->calcIsGunu($data['baslangic_tarihi'], $data['bitis_tarihi']);
        if ($gun_sayisi <= 0) {
            return ['success' => false, 'message' => 'Seçilen tarih aralığında iş günü bulunmuyor.'];
        }

        $cakismalar = $this->checkCakisma(
            (int) $data['personel_id'],
            $data['baslangic_tarihi'],
            $data['bitis_tarihi'],
            $talep_id
        );
        if (!empty($cakismalar)) {
            $ilk = $cakismalar[0];
            return ['success' => false, 'message' => "Seçilen tarihler '{$ilk->tur_adi}' kaydıyla çakışıyor ({$ilk->baslangic_tarihi})."];
        }

        require_once __DIR__ . '/IzinTur.php';
        $turModel = new IzinTur();
        $tur = $turModel->find((int) $data['tur_id']);

        if ($tur && $tur->kod === 'yillik') {
            $kalan = $this->hakedisModel->getTotalKalan((int) $data['personel_id']);
            if ($kalan < $gun_sayisi) {
                return ['success' => false, 'message' => "Yeterli yıllık izin bakiyeniz yok. Kalan: {$kalan} gün, Talep: {$gun_sayisi} gün."];
            }
        }

        $sql = $this->db->prepare(
            "UPDATE {$this->table} 
             SET tur_id = ?, baslangic_tarihi = ?, bitis_tarihi = ?, gun_sayisi = ?, aciklama = ? 
             WHERE id = ?"
        );
        $sql->execute([
            $data['tur_id'],
            $data['baslangic_tarihi'],
            $data['bitis_tarihi'],
            $gun_sayisi,
            $data['aciklama'] ?? null,
            $talep_id
        ]);

        ActivityLogModel::log('izin', 'edit', "Talep ID {$talep_id} güncellendi. Yeni süre: {$gun_sayisi} gün ({$data['baslangic_tarihi']} - {$data['bitis_tarihi']}).");

        return ['success' => true, 'message' => 'İzin talebi güncellendi.'];
    }

    /**
     * İzin talebini onaylar: FIFO hakedişten düşer ve puantaja yazar.
     */
    public function onayla(int $talep_id, int $onaylayan_id): array
    {
        $talep = $this->find($talep_id);
        if (!$talep) return ['success' => false, 'message' => 'Talep bulunamadı.'];
        if ($talep->durum !== 'beklemede') return ['success' => false, 'message' => 'Sadece beklemedeki talepler onaylanabilir.'];

        require_once __DIR__ . '/IzinTur.php';
        $turModel = new IzinTur();
        $tur = $turModel->find((int) $talep->tur_id);

        if ($tur && $tur->kod === 'yillik') {
            $result = $this->fifoTuket((int) $talep->personel_id, $talep_id, (int) $talep->gun_sayisi);
            if (!$result['success']) return $result;
        }

        $sql = $this->db->prepare(
            "UPDATE {$this->table} SET durum='onaylandi', onaylayan_id=?, onay_tarihi=NOW() WHERE id=?"
        );
        $sql->execute([$onaylayan_id, $talep_id]);

        if ($tur && $tur->puantaj_turu_id) {
            $this->puantajaYaz($talep, (int) $tur->puantaj_turu_id);
        }

        ActivityLogModel::log('izin', 'approve', "Talep ID {$talep_id} onaylandı. Personel ID {$talep->personel_id}, {$talep->gun_sayisi} gün.");

        $this->bildirimGonder(
            (int) $talep->personel_id,
            (int) $talep->firma_id,
            'İzin Talebiniz Onaylandı',
            "{$talep->baslangic_tarihi} – {$talep->bitis_tarihi} tarihleri arası {$talep->gun_sayisi} günlük izin talebiniz onaylandı.",
            $onaylayan_id
        );

        return ['success' => true, 'message' => 'İzin talebi onaylandı.'];
    }

    public function kismiOnayla(int $talep_id, int $onaylayan_id, string $yeni_bitis): array
    {
        $talep = $this->find($talep_id);
        if (!$talep) return ['success' => false, 'message' => 'Talep bulunamadı.'];
        if ($talep->durum !== 'beklemede') return ['success' => false, 'message' => 'Sadece beklemedeki talepler onaylanabilir.'];
        if ($yeni_bitis < $talep->baslangic_tarihi) return ['success' => false, 'message' => 'Bitiş tarihi başlangıçtan önce olamaz.'];
        if ($yeni_bitis > $talep->bitis_tarihi) return ['success' => false, 'message' => 'Kısmi onay tarihi orijinal bitiş tarihini geçemez.'];

        $yeni_gun = $this->calcIsGunu($talep->baslangic_tarihi, $yeni_bitis);
        if ($yeni_gun <= 0) return ['success' => false, 'message' => 'Seçilen tarih aralığında iş günü bulunmuyor.'];

        require_once __DIR__ . '/IzinTur.php';
        $tur = (new IzinTur())->find((int) $talep->tur_id);

        if ($tur && $tur->kod === 'yillik') {
            $kalan = $this->hakedisModel->getTotalKalan((int) $talep->personel_id);
            if ($kalan < $yeni_gun) {
                return ['success' => false, 'message' => "Yeterli yıllık izin bakiyesi yok. Kalan: {$kalan} gün, Onaylanacak: {$yeni_gun} gün."];
            }
            $result = $this->fifoTuket((int) $talep->personel_id, $talep_id, $yeni_gun);
            if (!$result['success']) return $result;
        }

        $sql = $this->db->prepare(
            "UPDATE {$this->table} SET durum='onaylandi', onaylayan_id=?, onay_tarihi=NOW(), bitis_tarihi=?, gun_sayisi=? WHERE id=?"
        );
        $sql->execute([$onaylayan_id, $yeni_bitis, $yeni_gun, $talep_id]);

        if ($tur && $tur->puantaj_turu_id) {
            $this->puantajaYaz($this->find($talep_id), (int) $tur->puantaj_turu_id);
        }

        ActivityLogModel::log('izin', 'partial_approve', "Talep ID {$talep_id} kısmi onaylandı. {$yeni_gun} gün (orijinal: {$talep->gun_sayisi} gün).");

        $this->bildirimGonder(
            (int) $talep->personel_id,
            (int) $talep->firma_id,
            'İzin Talebiniz Kısmi Onaylandı',
            "{$talep->baslangic_tarihi} – {$yeni_bitis} tarihleri arası {$yeni_gun} günlük izin talebiniz onaylandı (talep: {$talep->gun_sayisi} gün).",
            $onaylayan_id
        );

        return ['success' => true, 'message' => "İzin talebi {$yeni_gun} gün olarak kısmi onaylandı."];
    }

    /**
     * İzin talebini reddeder ve varsa puantaj kayıtlarını siler.
     */
    public function reddet(int $talep_id, int $onaylayan_id, string $not = ''): array
    {
        $talep = $this->find($talep_id);
        if (!$talep) return ['success' => false, 'message' => 'Talep bulunamadı.'];
        if (!in_array($talep->durum, ['beklemede', 'onaylandi'])) {
            return ['success' => false, 'message' => 'Bu talep reddedilemez.'];
        }

        if ($talep->durum === 'onaylandi') {
            $this->fifoGeriAl($talep_id);
            $this->puantajdanSil($talep);
        }

        $sql = $this->db->prepare(
            "UPDATE {$this->table} SET durum='reddedildi', onaylayan_id=?, onay_tarihi=NOW(), yonetici_notu=? WHERE id=?"
        );
        $sql->execute([$onaylayan_id, $not, $talep_id]);

        ActivityLogModel::log('izin', 'reject', "Talep ID {$talep_id} reddedildi. Not: {$not}");

        $mesaj = "{$talep->baslangic_tarihi} – {$talep->bitis_tarihi} tarihleri arası izin talebiniz reddedildi." . ($not ? " Not: {$not}" : '');
        $this->bildirimGonder(
            (int) $talep->personel_id,
            (int) $talep->firma_id,
            'İzin Talebiniz Reddedildi',
            $mesaj,
            $onaylayan_id
        );

        return ['success' => true, 'message' => 'İzin talebi reddedildi.'];
    }

    /**
     * Personelin talebini iptal eder (sadece beklemedekiler).
     */
    public function iptalEt(int $talep_id, int $personel_id): array
    {
        $talep = $this->find($talep_id);
        if (!$talep || (int) $talep->personel_id !== $personel_id) {
            return ['success' => false, 'message' => 'Talep bulunamadı.'];
        }
        if ($talep->durum !== 'beklemede') {
            return ['success' => false, 'message' => 'Sadece beklemedeki talepler iptal edilebilir.'];
        }

        $sql = $this->db->prepare("UPDATE {$this->table} SET durum='iptal' WHERE id=?");
        $sql->execute([$talep_id]);

        ActivityLogModel::log('izin', 'cancel', "Personel ID {$personel_id}, Talep ID {$talep_id} iptal etti.");

        return ['success' => true, 'message' => 'İzin talebi iptal edildi.'];
    }

    /**
     * İzin talebini siler.
     * Onaylı ise FIFO kullanımını geri alır ve puantajdan siler.
     */
    public function sil(int $talep_id): array
    {
        $talep = $this->find($talep_id);
        if (!$talep) {
            return ['success' => false, 'message' => 'Talep bulunamadı.'];
        }

        if (!in_array($talep->durum, ['onaylandi', 'reddedildi'])) {
            return ['success' => false, 'message' => 'Sadece onaylı veya reddedilmiş izin talepleri silinebilir.'];
        }

        // Eğer onaylı ise FIFO kullanımını ve puantaj kayıtlarını temizle
        if ($talep->durum === 'onaylandi') {
            $this->fifoGeriAl($talep_id);
            $this->puantajdanSil($talep);
        }

        $sql = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $sql->execute([$talep_id]);

        ActivityLogModel::log('izin', 'delete', "Talep ID {$talep_id} silindi. Personel ID {$talep->personel_id}, {$talep->gun_sayisi} gün.");

        return ['success' => true, 'message' => 'İzin talebi silindi.'];
    }

    private function fifoTuket(int $personel_id, int $talep_id, int $gerekli_gun): array
    {
        $hakedisler = $this->hakedisModel->getFifoHakedisler($personel_id);
        $kalan = $gerekli_gun;

        foreach ($hakedisler as $h) {
            if ($kalan <= 0) break;
            $dusulebilir = min($kalan, (int) $h->kalan_gun);
            $sql = $this->db->prepare(
                "INSERT INTO izin_kullanim (talep_id, hakedis_id, kullanilan_gun) VALUES (?, ?, ?)"
            );
            $sql->execute([$talep_id, $h->id, $dusulebilir]);
            $kalan -= $dusulebilir;
        }

        if ($kalan > 0) {
            $this->fifoGeriAl($talep_id);
            return ['success' => false, 'message' => 'Yeterli izin bakiyesi bulunamadı.'];
        }

        return ['success' => true];
    }

    private function fifoGeriAl(int $talep_id): void
    {
        $sql = $this->db->prepare("DELETE FROM izin_kullanim WHERE talep_id = ?");
        $sql->execute([$talep_id]);
    }

    private function puantajaYaz(object $talep, int $puantaj_turu_id): void
    {
        $tatiller = $this->getResmiTatilDates($talep->baslangic_tarihi, $talep->bitis_tarihi);
        $current = new DateTime($talep->baslangic_tarihi);
        $end = new DateTime($talep->bitis_tarihi);

        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('N');
            $dateStr = $current->format('Y-m-d');

            if ($dayOfWeek < 6 && !in_array($dateStr, $tatiller)) {
                $mevcut = $this->db->prepare("SELECT id FROM puantaj WHERE person = ? AND (gun = ? OR gun = ?) AND (project_id = 0 OR project_id IS NULL) LIMIT 1");
                $mevcut->execute([$talep->personel_id, $dateStr, str_replace('-', '', $dateStr)]);
                if (!$mevcut->fetch()) {
                    $ins = $this->db->prepare(
                        "INSERT INTO puantaj (person, gun, puantaj_id, company_id, saat, tutar, project_id)
                         SELECT ?, ?, ?, firm_id, 0, 0, 0 FROM persons WHERE id = ? LIMIT 1"
                    );
                    $ins->execute([$talep->personel_id, $dateStr, $puantaj_turu_id, $talep->personel_id]);
                }
            }
            $current->modify('+1 day');
        }
    }

    private function puantajdanSil(object $talep): void
    {
        $sql = $this->db->prepare(
            "DELETE p FROM puantaj p
             JOIN puantajturu pt ON pt.id = p.puantaj_id
             WHERE p.person = ?
               AND p.gun BETWEEN ? AND ?
               AND pt.Turu = 'Ücretli İzin'"
        );
        $sql->execute([$talep->personel_id, $talep->baslangic_tarihi, $talep->bitis_tarihi]);
    }

    private function bildirimGonder(int $personel_id, int $firma_id, string $baslik, string $icerik, int $olusturan_id): void
    {
        try {
            require_once __DIR__ . '/DuyuruModel.php';
            $Duyuru = new DuyuruModel();
            $duyuru_id = $Duyuru->ekle([
                'baslik'          => $baslik,
                'icerik'          => $icerik,
                'olusturan_id'    => $olusturan_id,
                'hedef_tip'       => 'bazi_personeller',
                'hedef_firma_id'  => $firma_id,
                'baslangic_tarihi'=> null,
                'bitis_tarihi'    => null,
                'oncelik'         => 'normal',
            ]);
            if ($duyuru_id) {
                $ins = $this->db->prepare("INSERT INTO duyuru_hedefler (duyuru_id, hedef_tip, hedef_id) VALUES (?, 'personel', ?)");
                $ins->execute([$duyuru_id, $personel_id]);
            }
        } catch (\Throwable $e) {}
    }

    public function getByPersonel(int $personel_id, string $durum = ''): array
    {
        $where = $durum ? "AND t.durum = ?" : '';
        $params = $durum ? [$personel_id, $durum] : [$personel_id];
        $sql = $this->db->prepare(
            "SELECT t.*, it.ad AS tur_adi, it.ucretli
             FROM {$this->table} t
             JOIN izin_turler it ON it.id = t.tur_id
             WHERE t.personel_id = ? $where
             ORDER BY t.olusturma_tarihi DESC"
        );
        $sql->execute($params);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getByFirma(int $firma_id, array $filters = []): array
    {
        $where = 'WHERE t.firma_id = ?';
        $params = [$firma_id];

        if (!empty($filters['durum'])) {
            $where .= ' AND t.durum = ?';
            $params[] = $filters['durum'];
        }
        if (!empty($filters['personel_id'])) {
            $where .= ' AND t.personel_id = ?';
            $params[] = $filters['personel_id'];
        }
        if (!empty($filters['baslangic'])) {
            $where .= ' AND t.bitis_tarihi >= ?';
            $params[] = $filters['baslangic'];
        }
        if (!empty($filters['bitis'])) {
            $where .= ' AND t.baslangic_tarihi <= ?';
            $params[] = $filters['bitis'];
        }

        $sql = $this->db->prepare(
            "SELECT t.*, it.ad AS tur_adi, p.full_name AS personel_adi, u.full_name AS onaylayan_adi
             FROM {$this->table} t
             JOIN izin_turler it ON it.id = t.tur_id
             JOIN persons p ON p.id = t.personel_id
             LEFT JOIN users u ON u.id = t.onaylayan_id
             $where
             ORDER BY t.olusturma_tarihi DESC"
        );
        $sql->execute($params);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Bugün izinli olan personelleri döner.
     */
    public function getBugunIzinliler(int $firma_id): array
    {
        $sql = $this->db->prepare(
            "SELECT t.*, p.full_name AS personel_adi, it.ad AS tur_adi
             FROM {$this->table} t
             JOIN persons p ON p.id = t.personel_id
             JOIN izin_turler it ON it.id = t.tur_id
             WHERE t.firma_id = ? AND t.durum = 'onaylandi'
               AND t.baslangic_tarihi <= CURDATE() AND t.bitis_tarihi >= CURDATE()
             ORDER BY p.full_name"
        );
        $sql->execute([$firma_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getBekleyenSayisi(int $firma_id): int
    {
        $sql = $this->db->prepare(
            "SELECT COUNT(*) AS sayi FROM {$this->table} WHERE firma_id = ? AND durum = 'beklemede'"
        );
        $sql->execute([$firma_id]);
        return (int) ($sql->fetch(PDO::FETCH_OBJ)->sayi ?? 0);
    }

    public function getBuAyKullanilanGun(int $firma_id): int
    {
        $sql = $this->db->prepare(
            "SELECT COALESCE(SUM(gun_sayisi), 0) AS toplam
             FROM {$this->table}
             WHERE firma_id = ? AND durum = 'onaylandi'
               AND MONTH(baslangic_tarihi) = MONTH(CURDATE())
               AND YEAR(baslangic_tarihi) = YEAR(CURDATE())"
        );
        $sql->execute([$firma_id]);
        return (int) ($sql->fetch(PDO::FETCH_OBJ)->toplam ?? 0);
    }

    public function getEnCokKullananlar(int $firma_id, int $limit = 5): array
    {
        $sql = $this->db->prepare(
            "SELECT t.personel_id, p.full_name, SUM(t.gun_sayisi) AS toplam_gun
             FROM {$this->table} t
             JOIN persons p ON p.id = t.personel_id
             WHERE t.firma_id = :firma_id AND t.durum = 'onaylandi'
               AND YEAR(t.baslangic_tarihi) = YEAR(CURDATE())
             GROUP BY t.personel_id, p.full_name
             ORDER BY toplam_gun DESC
             LIMIT :limit"
        );
        $sql->bindValue(':firma_id', $firma_id, PDO::PARAM_INT);
        $sql->bindValue(':limit', $limit, PDO::PARAM_INT);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Onaylı izin olan günlerde puantaj kilitli mi kontrolü.
     * Puantaj sayfasına entegrasyon için kullanılır.
     */
    public function getOnayliIzinGunleriToplu(array $person_ids, string $baslangic, string $bitis): array
    {
        if (empty($person_ids)) return [];

        $placeholders = implode(',', array_fill(0, count($person_ids), '?'));
        $params = array_merge($person_ids, [$bitis, $baslangic]);

        $sql = $this->db->prepare(
            "SELECT t.id AS talep_id, t.personel_id, t.baslangic_tarihi, t.bitis_tarihi, t.gun_sayisi,
                    COALESCE(pt.id, 0)                    AS puantaj_turu_id,
                    COALESCE(pt.PuantajKod, 'İZ')         AS kod,
                    COALESCE(pt.ArkaPlanRengi, '#d4edda') AS arkaplan,
                    COALESCE(pt.FontRengi, '#155724')      AS font,
                    COALESCE(pt.Turu, '')                  AS turu
             FROM {$this->table} t
             JOIN izin_turler it ON it.id = t.tur_id
             LEFT JOIN puantajturu pt ON pt.id = it.puantaj_turu_id
             WHERE t.personel_id IN ($placeholders)
               AND t.durum = 'onaylandi'
               AND t.baslangic_tarihi <= ? AND t.bitis_tarihi >= ?"
        );
        $sql->execute($params);
        $kayitlar = $sql->fetchAll(PDO::FETCH_OBJ);

        $tatiller = [];
        if (!empty($kayitlar)) {
            $tatiller = $this->getResmiTatilDates($baslangic, $bitis);
        }

        $result = [];
        foreach ($kayitlar as $k) {
            $current = new DateTime($k->baslangic_tarihi);
            $end     = new DateTime($k->bitis_tarihi);
            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');
                if (!in_array($dateStr, $tatiller)) {
                    $result[$k->personel_id][$dateStr] = (object) [
                        'talep_id'        => (int) $k->talep_id,
                        'gun_sayisi'      => (int) $k->gun_sayisi,
                        'puantaj_turu_id' => (int) $k->puantaj_turu_id,
                        'kod'             => $k->kod,
                        'arkaplan'        => $k->arkaplan,
                        'font'            => $k->font,
                        'turu'            => $k->turu,
                    ];
                }
                $current->modify('+1 day');
            }
        }

        return $result;
    }
}
