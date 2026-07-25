<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database/require.php';
require_once __DIR__ . '/../../Model/SettingsModel.php';

if (!isset($_SESSION['personel_id'], $_SESSION['firm_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Oturum süreniz dolmuş.']);
    exit;
}

$person_id = (int)$_SESSION['personel_id'];
$firm_id = (int)$_SESSION['firm_id'];
$advance_visible = (new SettingsModel())->getSettings('personnel_advance_request_visible')->set_value ?? 1;

if ((int)$advance_visible !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Avans talebi özelliği kullanıma kapalıdır.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action == 'list') {
    try {
        $query = $db->prepare("SELECT *, DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as created_at FROM personel_avans_talepleri WHERE person_id = ? ORDER BY id DESC");
        $query->execute([$person_id]);
        $list = $query->fetchAll(PDO::FETCH_OBJ);
        echo json_encode(['status' => 'success', 'list' => $list]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($action == 'create') {
    $tutar = (float)($_POST['tutar'] ?? 0);
    $aciklama = trim((string)($_POST['aciklama'] ?? ''));
    $hedef_ay = (int)($_POST['hedef_ay'] ?? 0);
    $hedef_yil = (int)($_POST['hedef_yil'] ?? 0);

    if ($tutar <= 0 || $hedef_ay < 1 || $hedef_ay > 12 || $hedef_yil < 2000 || $hedef_yil > 2100) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen tutar ve hedef dönemi kontrol edin.']);
        exit;
    }

    // Check for existing pending request
    $check = $db->prepare("SELECT id FROM personel_avans_talepleri WHERE person_id = ? AND durum = 0");
    $check->execute([$person_id]);
    if ($check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Hali hazırda bekleyen bir avans talebiniz bulunmaktadır. Yeni bir talep oluşturmadan önce mevcut talebinizin sonuçlanmasını beklemelisiniz.']);
        exit;
    }

    $query = $db->prepare("INSERT INTO personel_avans_talepleri (person_id, firm_id, tutar, aciklama, hedef_ay, hedef_yil) VALUES (?, ?, ?, ?, ?, ?)");
    try {
        $query->execute([$person_id, $firm_id, $tutar, $aciklama, $hedef_ay, $hedef_yil]);

        // Yöneticilere bildirim gönderilmesi ve loglama
        try {
            if (!defined('ROOT')) {
                define('ROOT', dirname(__DIR__, 2));
            }
            require_once ROOT . '/Model/DuyuruModel.php';
            require_once ROOT . '/Model/UserModel.php';
            require_once ROOT . '/Model/Persons.php';
            require_once ROOT . '/Model/ActivityLogModel.php';

            $Duyuru = new DuyuruModel();
            $User = new UserModel();
            $Persons = new Persons();

            $person = $Persons->find($person_id);
            $person_name = $person ? $person->full_name : 'Bilinmeyen Personel';

            $managers = $User->getManagersByFirm($firm_id);
            $manager_ids = array_map(function($m) { return $m->id; }, $managers);

            if (!empty($manager_ids)) {
                $monthNames = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
                $ay_adi = isset($monthNames[$hedef_ay - 1]) ? $monthNames[$hedef_ay - 1] : '';
                $donem = $ay_adi . ' ' . $hedef_yil;
                
                $duyuru_data = [
                    'baslik' => 'Yeni Avans Talebi',
                    'icerik' => htmlspecialchars($person_name) . " adlı personel, " . number_format($tutar, 2, ',', '.') . " TL tutarında avans talebi oluşturdu. (Dönem: " . htmlspecialchars($donem) . ")",
                    'kaynak_turu' => 'sistem',
                    'olusturan_id' => 0,
                    'hedef_tip' => 'bazi_kullanicilar',
                    'hedef_firma_id' => $firm_id,
                    'baslangic_tarihi' => date('Y-m-d'),
                    'bitis_tarihi' => date('Y-m-d', strtotime('+30 days')),
                    'oncelik' => 'normal'
                ];
                
                $duyuru_id = $Duyuru->ekle($duyuru_data);
                if ($duyuru_id) {
                    $Duyuru->setHedefler($duyuru_id, 'kullanici', $manager_ids);
                }

                if (file_exists(ROOT . '/vendor/autoload.php')) require_once ROOT . '/vendor/autoload.php';
                require_once ROOT . '/Service/WebPushSender.php';
                require_once ROOT . '/Service/PushBildirimService.php';
                $push_mesaj = $person_name . ' ' . number_format($tutar, 2, ',', '.') . ' TL tutarında avans talebi oluşturdu.';
                (new \Service\PushBildirimService($db))->yoneticilereGonder(
                    (int) $firm_id,
                    'Yeni Avans Talebi',
                    $push_mesaj,
                    ['url' => 'modules/more/advance_requests.php']
                );
            }

            // Aktivite günlüğü kaydı
            ActivityLogModel::log('avans_talebi', 'create', "Personel " . htmlspecialchars($person_name) . " yeni bir avans talebi oluşturdu. Tutar: " . number_format($tutar, 2, ',', '.') . " TL");

        } catch (Exception $logEx) {
            // Log/Bildirim hatası ana işlemi durdurmasın diye sessizce loglayalım
            error_log("Avans talebi bildirim/log hatası: " . $logEx->getMessage());
        }

        echo json_encode(['status' => 'success', 'message' => 'Talep başarıyla oluşturuldu.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
    }
} elseif ($action == 'update') {
    $id = $_POST['id'] ?? 0;
    $tutar = (float)($_POST['tutar'] ?? 0);
    $aciklama = trim((string)($_POST['aciklama'] ?? ''));
    $hedef_ay = (int)($_POST['hedef_ay'] ?? 0);
    $hedef_yil = (int)($_POST['hedef_yil'] ?? 0);

    if ($tutar <= 0 || $hedef_ay < 1 || $hedef_ay > 12 || $hedef_yil < 2000 || $hedef_yil > 2100) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen tutar ve hedef dönemi kontrol edin.']);
        exit;
    }

    // Check if the advance exists and is still pending (durum == 0)
    $check = $db->prepare("SELECT * FROM personel_avans_talepleri WHERE id = ? AND person_id = ?");
    $check->execute([$id, $person_id]);
    $advance = $check->fetch(PDO::FETCH_OBJ);

    if (!$advance) {
        echo json_encode(['status' => 'error', 'message' => 'Talep bulunamadı veya yetkiniz yok.']);
        exit;
    }

    if ($advance->durum != 0) {
        echo json_encode(['status' => 'error', 'message' => 'Onaylanmış veya reddedilmiş talepler güncellenemez.']);
        exit;
    }

    $query = $db->prepare("UPDATE personel_avans_talepleri SET tutar = ?, aciklama = ?, hedef_ay = ?, hedef_yil = ? WHERE id = ? AND person_id = ?");
    try {
        $query->execute([$tutar, $aciklama, $hedef_ay, $hedef_yil, $id, $person_id]);
        echo json_encode(['status' => 'success', 'message' => 'Talep başarıyla güncellendi.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
    }
} elseif ($action == 'delete') {
    $id = $_POST['id'] ?? 0;
    // Only pending advances can be deleted
    $check = $db->prepare("SELECT durum FROM personel_avans_talepleri WHERE id = ? AND person_id = ?");
    $check->execute([$id, $person_id]);
    $durum = $check->fetchColumn();

    if ($durum === false) {
        echo json_encode(['status' => 'error', 'message' => 'Talep bulunamadı.']);
        exit;
    }

    if ($durum != 0) {
        echo json_encode(['status' => 'error', 'message' => 'Onaylanmış veya reddedilmiş talepler silinemez.']);
        exit;
    }

    $query = $db->prepare("DELETE FROM personel_avans_talepleri WHERE id = ? AND person_id = ?");
    try {
        $query->execute([$id, $person_id]);
        echo json_encode(['status' => 'success', 'message' => 'Talep silindi.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
    }
}
