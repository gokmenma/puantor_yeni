<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

use App\Helper\Security;

try {
    require_once ROOT . '/Database/require.php';
    require_once ROOT . '/Model/Auths.php';
    require_once ROOT . '/App/Helper/security.php';
    require_once ROOT . '/Model/IzinHakedis.php';
    require_once ROOT . '/Model/Persons.php';

    $Auths = new Auths();
    if (!isset($_SESSION['user'])) {
        ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.'], JSON_UNESCAPED_UNICODE); exit;
    }

    $action    = $_REQUEST['action'] ?? '';
    $firma_id  = (int) ($_SESSION['firm_id'] ?? 0);
    $user_id   = is_object($_SESSION['user']) ? (int)($_SESSION['user']->id ?? 0) : (int)$_SESSION['user'];
    $model     = new IzinHakedis();

    if ($action === 'list') {
        $enc_pid     = $_REQUEST['personel_id'] ?? '';
        $personel_id = $enc_pid ? (int) Security::safeDecrypt($enc_pid) : 0;

        if ($personel_id) {
            $persons = new Persons();
            $p = $persons->find($personel_id);
            if (!$p || (int) $p->firm_id !== $firma_id) throw new Exception('Personel bulunamadı.');
            $model->hesaplaVeKaydet($personel_id, $firma_id, $p->job_start_date, $p->birth_date ?? null);
        }

        $liste = $model->getByFirma($firma_id, $personel_id);

        ob_clean();
        echo json_encode(['status' => 'success', 'list' => $liste], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'add') {
        $personel_id = (int) Security::safeDecrypt($_POST['personel_id'] ?? '');
        $gun_sayisi  = (int) ($_POST['gun_sayisi'] ?? 0);
        $yil         = (int) ($_POST['yil'] ?? 0);
        $aciklama    = trim($_POST['aciklama'] ?? '');

        if (!$personel_id || $gun_sayisi <= 0 || $yil <= 0) throw new Exception('Eksik veya geçersiz veri.');

        $mevcut = $model->getByPersonelVeYil($personel_id, $yil);
        if ($mevcut) throw new Exception("Bu personel için {$yil}. yıl hakedişi zaten mevcut.");

        $persons = new Persons();
        $p = $persons->find($personel_id);
        if (!$p || (int) $p->firm_id !== $firma_id) throw new Exception('Personel bulunamadı.');

        $baslangic = new DateTime($p->job_start_date);
        $hakedis_tarihi = clone $baslangic;
        if ($yil > 1000) {
            $hakedis_tarihi->setDate($yil, (int)$baslangic->format('m'), (int)$baslangic->format('d'));
        } else {
            $hakedis_tarihi->modify("+{$yil} year");
        }

        $id = $model->saveWithAttr([
            'firma_id'       => $firma_id,
            'personel_id'    => $personel_id,
            'yil'            => $yil,
            'hakedis_tarihi' => $hakedis_tarihi->format('Y-m-d'),
            'gun_sayisi'     => $gun_sayisi,
            'tip'            => 'manuel',
            'aciklama'       => $aciklama,
            'olusturan_id'   => $user_id,
        ]);

        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Hakediş eklendi.', 'id' => $id], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update') {
        $id         = (int) Security::safeDecrypt($_POST['id'] ?? '');
        $gun_sayisi = (int) ($_POST['gun_sayisi'] ?? 0);
        $aciklama   = trim($_POST['aciklama'] ?? '');

        if (!$id || $gun_sayisi <= 0) throw new Exception('Eksik veri.');

        $kayit = $model->find($id);
        if (!$kayit || (int) $kayit->firma_id !== $firma_id) throw new Exception('Kayıt bulunamadı.');

        $model->saveWithAttr([
            'id'          => $id,
            'gun_sayisi'  => $gun_sayisi,
            'aciklama'    => $aciklama,
            'tip'         => 'manuel',
            'olusturan_id'=> $user_id,
        ]);

        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Hakediş güncellendi.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) Security::safeDecrypt($_POST['id'] ?? '');
        if (!$id) throw new Exception('Geçersiz ID.');

        $kayit = $model->find($id);
        if (!$kayit || (int) $kayit->firma_id !== $firma_id) throw new Exception('Kayıt bulunamadı.');

        $model->deleteById($id);

        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Hakediş silindi.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'calculate_all') {
        $persons = new Persons();
        $tumPersonel = $persons->getPersonsByFirm($firma_id);
        
        $toplam_hesaplanan = 0;
        foreach ($tumPersonel as $p) {
            if ($p->job_start_date) {
                $toplam_hesaplanan += $model->hesaplaVeKaydet(
                    (int)$p->id,
                    $firma_id,
                    $p->job_start_date,
                    $p->birth_date ?? null
                );
            }
        }

        require_once ROOT . '/Model/ActivityLogModel.php';
        ActivityLogModel::log('izin_hakedis', 'calculate_all', "Tüm personeller için yıllık izin hakedişleri hesaplandı. Toplam {$toplam_hesaplanan} yeni hakediş oluşturuldu.");

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => "Hesaplama tamamlandı. {$toplam_hesaplanan} adet yeni yıllık izin hakedişi oluşturuldu."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'bulk_add') {
        $records = json_decode(file_get_contents('php://input'), true);
        if (!is_array($records) || empty($records)) throw new Exception('Veri bulunamadı.');

        $persons = new Persons();
        $tumPersonel = $persons->getPersonsByFirm($firma_id);
        $adMap = [];
        $tcMap = [];
        foreach ($tumPersonel as $pr) {
            $adMap[mb_strtolower(trim($pr->full_name))] = $pr;
            $tcDecrypted = \App\Helper\Security::safeDecrypt($pr->kimlik_no ?? '');
            if ($tcDecrypted) $tcMap[trim($tcDecrypted)] = $pr;
        }

        $sonuclar = [];
        foreach ($records as $i => $row) {
            $satir    = $i + 2;
            $ad       = trim($row['personel_adi'] ?? '');
            $tc       = trim($row['tc_no'] ?? '');
            $yil      = (int) ($row['yil'] ?? 0);
            $gun      = (int) ($row['gun_sayisi'] ?? 0);
            $aciklama = trim($row['aciklama'] ?? '');

            if ((!$ad && !$tc) || $yil <= 0 || $gun <= 0) {
                $sonuclar[] = ['satir' => $satir, 'status' => 'error', 'message' => "Satır {$satir}: Eksik veya geçersiz veri."];
                continue;
            }

            $p = ($tc && isset($tcMap[$tc])) ? $tcMap[$tc] : ($adMap[mb_strtolower($ad)] ?? null);
            if (!$p) {
                $sonuclar[] = ['satir' => $satir, 'status' => 'error', 'message' => "Satır {$satir}: '{$ad}' isimli personel bulunamadı."];
                continue;
            }

            $mevcut = $model->getByPersonelVeYil((int) $p->id, $yil);
            if ($mevcut) {
                $sonuclar[] = ['satir' => $satir, 'status' => 'skip', 'message' => "Satır {$satir}: {$ad} için {$yil}. yıl zaten mevcut, atlandı."];
                continue;
            }

            $baslangic = new DateTime($p->job_start_date);
            $hakedis_tarihi = clone $baslangic;
            if ($yil > 1000) {
                $hakedis_tarihi->setDate($yil, (int)$baslangic->format('m'), (int)$baslangic->format('d'));
            } else {
                $hakedis_tarihi->modify("+{$yil} year");
            }

            $model->saveWithAttr([
                'firma_id'       => $firma_id,
                'personel_id'    => (int) $p->id,
                'yil'            => $yil,
                'hakedis_tarihi' => $hakedis_tarihi->format('Y-m-d'),
                'gun_sayisi'     => $gun,
                'tip'            => 'manuel',
                'aciklama'       => $aciklama,
                'olusturan_id'   => is_object($_SESSION['user'] ?? null) ? $_SESSION['user']->id : (int)($_SESSION['user'] ?? 0),
            ]);

            $sonuclar[] = ['satir' => $satir, 'status' => 'success', 'message' => "Satır {$satir}: {$ad} – {$yil}. yıl eklendi."];
        }

        ob_clean();
        echo json_encode(['status' => 'success', 'sonuclar' => $sonuclar], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (\Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
