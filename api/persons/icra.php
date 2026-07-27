<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

!defined("ROOT") ? define("ROOT", dirname(dirname(__DIR__))) : false;
require_once "../../Database/require.php";
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/PersonIcra.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/Model/ActivityLogModel.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

$Auths = new Auths();
$Persons = new Persons();
$PersonIcra = new PersonIcra();

// Yetki kontrolü (person_page_icra_info veya icra_files_list yetkisi var mı kontrol edilir)
if (!$Auths->Authorize('person_page_icra_info') && !$Auths->Authorize('icra_files_list')) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. Dosya İndirme Aksiyonu (GET)
if ($action == 'download') {
    $id_encrypted = $_GET['id'] ?? '';
    $id = Security::decrypt($id_encrypted);

    if (!$id) {
        die('Geçersiz Kimlik');
    }

    $icra_file = $PersonIcra->find($id);
    if (!$icra_file || $icra_file->deleted_at !== null) {
        die('İcra dosyası bulunamadı');
    }

    // Personel yetkisi ve firmasını kontrol et
    $person = $Persons->find($icra_file->person_id);
    if (!$person || $person->firm_id != $_SESSION['firm_id']) {
        die('Yetkisiz erişim');
    }

    $file_path = ROOT . "/" . $icra_file->belge_yolu;
    if (empty($icra_file->belge_yolu) || !file_exists($file_path)) {
        die('Fiziksel dosya bulunamadı.');
    }

    $original_name = $icra_file->belge_adi ?? 'belge.pdf';
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    
    $mimes = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain'
    ];
    $mime_type = $mimes[$ext] ?? 'application/octet-stream';

    $stored_content = file_get_contents($file_path);
    if (strlen($stored_content) < 28) {
        die('Dosya bozuk veya şifrelenemedi.');
    }

    // AES-256-GCM Decryption (ISO 27001)
    $iv = substr($stored_content, 0, 12);
    $tag = substr($stored_content, 12, 16);
    $encrypted_data = substr($stored_content, 28);
    $method = "AES-256-GCM";
    $key = hash('sha256', 'document-secret-key-iso-27001', true);
    
    $decrypted_data = openssl_decrypt($encrypted_data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);

    if ($decrypted_data === false) {
        die('Belge deşifre edilemedi.');
    }

    // Log download
    ActivityLogModel::log('icra', 'belge_indir', "İcra belgesi indirildi. Dosya No: {$icra_file->dosya_no}");

    ob_clean();
    header("Content-Type: " . $mime_type);
    header("Content-Disposition: attachment; filename=\"" . $original_name . "\"");
    header("Content-Length: " . strlen($decrypted_data));
    echo $decrypted_data;
    exit;
}

// Diğer AJAX aksiyonları (JSON çıktı verir)
header('Content-Type: application/json');

// 2. Listeleme Aksiyonu
if ($action == 'list') {
    $person_id_encrypted = $_POST['person_id'] ?? $_GET['person_id'] ?? '';
    $person_id = Security::decrypt($person_id_encrypted);

    if (!$person_id) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz personel kimliği']);
        exit;
    }

    $person = $Persons->find($person_id);
    if (!$person || $person->firm_id != $_SESSION['firm_id']) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Personel bulunamadı veya yetkiniz yok']);
        exit;
    }

    $files = $PersonIcra->getByPersonId($person_id);
    $stats = $PersonIcra->getStats($person_id);

    // Verileri formatla ve toplam kesintileri icra sırasına göre dağıt
    $remaining_deductions = $stats['total_deductions'];
    $formatted_files = [];
    foreach ($files as $f) {
        $toplam_borc = (float)$f->toplam_borc;
        $yapilan_kesinti = 0.0;
        
        if ($remaining_deductions > 0) {
            if ($remaining_deductions >= $toplam_borc) {
                $yapilan_kesinti = $toplam_borc;
                $remaining_deductions -= $toplam_borc;
            } else {
                $yapilan_kesinti = $remaining_deductions;
                $remaining_deductions = 0.0;
            }
        }
        
        $kalan_borc = max(0.0, $toplam_borc - $yapilan_kesinti);

        $formatted_files[] = [
            'id' => Security::encrypt($f->id),
            'person_id' => Security::encrypt($person_id),
            'raw_person_id' => (int)$person_id,
            'icra_sirasi' => (int)$f->icra_sirasi,
            'icra_dairesi' => htmlspecialchars($f->icra_dairesi, ENT_QUOTES, 'UTF-8'),
            'dosya_no' => htmlspecialchars($f->dosya_no, ENT_QUOTES, 'UTF-8'),
            'alacakli' => htmlspecialchars($f->alacakli, ENT_QUOTES, 'UTF-8'),
            'toplam_borc' => Helper::formattedMoney($toplam_borc),
            'toplam_borc_raw' => $toplam_borc,
            'kesinti_yontemi' => $f->kesinti_yontemi,
            'kesinti_orani' => htmlspecialchars($f->kesinti_orani ?? '', ENT_QUOTES, 'UTF-8'),
            'kesinti_tutari' => $f->kesinti_tutari ? Helper::formattedMoney($f->kesinti_tutari) : null,
            'kesinti_tutari_raw' => $f->kesinti_tutari ? (float)$f->kesinti_tutari : null,
            'yapilan_kesinti' => Helper::formattedMoney($yapilan_kesinti),
            'yapilan_kesinti_raw' => $yapilan_kesinti,
            'kalan_borc' => Helper::formattedMoney($kalan_borc),
            'kalan_borc_raw' => $kalan_borc,
            'durum' => $f->durum,
            'baslama_tarihi' => $f->baslama_tarihi,
            'baslama_tarihi_formatted' => !empty($f->baslama_tarihi) ? Date::dmY($f->baslama_tarihi) : '',
            'bitis_tarihi' => $f->bitis_tarihi,
            'bitis_tarihi_formatted' => !empty($f->bitis_tarihi) ? Date::dmY($f->bitis_tarihi) : '',
            'aciklama' => htmlspecialchars($f->aciklama ?? '', ENT_QUOTES, 'UTF-8'),
            'gelen_evrak' => htmlspecialchars($f->gelen_evrak ?? '', ENT_QUOTES, 'UTF-8'),
            'giden_evrak' => htmlspecialchars($f->giden_evrak ?? '', ENT_QUOTES, 'UTF-8'),
            'has_belge' => !empty($f->belge_yolu)
        ];
    }

    // Seçilebilecek icra dairelerini icra_daireleri tablosundan getir
    $admin_id = $_SESSION['user']->parent_id != 0 ? $_SESSION['user']->parent_id : $_SESSION['user']->id;
    $sql_defines = "SELECT DISTINCT daire_adi FROM icra_daireleri WHERE (admin_id = ? OR admin_id = 0) AND durum = 'Aktif' AND silinme_tarihi IS NULL ORDER BY daire_adi ASC";
    $q_defines = $Persons->getDb()->prepare($sql_defines);
    $q_defines->execute([$admin_id]);
    $defines = $q_defines->fetchAll(PDO::FETCH_COLUMN);

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'files' => $formatted_files,
        'stats' => [
            'total_files' => $stats['total_files'],
            'active_files' => $stats['active_files'],
            'total_debt' => Helper::formattedMoney($stats['total_debt']),
            'total_deductions' => Helper::formattedMoney($stats['total_deductions']),
            'remaining_debt' => Helper::formattedMoney($stats['remaining_debt'])
        ],
        'icra_kesintisi_aktif' => (int)($person->icra_kesintisi_aktif ?? 0),
        'defines' => $defines
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2.b Tüm Personellerin İcra Dosyaları (Firma Düzeyinde Listeleme)
if ($action == 'firm_list') {
    if (!$Auths->Authorize('icra_files_list') && !$Auths->Authorize('person_page_icra_info')) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
        exit;
    }

    $firm_id = $_SESSION['firm_id'] ?? 0;
    $status_filter = $_POST['status_filter'] ?? $_GET['status_filter'] ?? ['Kesilen'];

    $files = $PersonIcra->getFirmIcraFiles($firm_id, $status_filter);
    $stats = $PersonIcra->getFirmIcraStats($firm_id);

    $formatted_files = [];
    foreach ($files as $f) {
        $toplam_borc = (float)$f->toplam_borc;
        $yapilan_kesinti = (float)$f->yapilan_kesinti;
        $kalan_borc = (float)$f->kalan_borc;

        $formatted_files[] = [
            'id' => Security::encrypt($f->id),
            'person_id' => Security::encrypt($f->person_id),
            'raw_person_id' => (int)$f->person_id,
            'person_name' => htmlspecialchars($f->full_name ?? '', ENT_QUOTES, 'UTF-8'),
            'person_tc' => htmlspecialchars(Security::safeDecrypt($f->kimlik_no ?? ''), ENT_QUOTES, 'UTF-8'),
            'person_sicil' => htmlspecialchars($f->sigorta_no ?? '', ENT_QUOTES, 'UTF-8'),
            'person_departman' => htmlspecialchars($f->job ?? '', ENT_QUOTES, 'UTF-8'),
            'icra_sirasi' => (int)$f->icra_sirasi,
            'icra_dairesi' => htmlspecialchars($f->icra_dairesi, ENT_QUOTES, 'UTF-8'),
            'dosya_no' => htmlspecialchars($f->dosya_no, ENT_QUOTES, 'UTF-8'),
            'alacakli' => htmlspecialchars($f->alacakli, ENT_QUOTES, 'UTF-8'),
            'toplam_borc' => Helper::formattedMoney($toplam_borc),
            'toplam_borc_raw' => $toplam_borc,
            'kesinti_yontemi' => $f->kesinti_yontemi,
            'kesinti_orani' => htmlspecialchars($f->kesinti_orani ?? '', ENT_QUOTES, 'UTF-8'),
            'kesinti_tutari' => $f->kesinti_tutari ? Helper::formattedMoney($f->kesinti_tutari) : null,
            'kesinti_tutari_raw' => $f->kesinti_tutari ? (float)$f->kesinti_tutari : null,
            'yapilan_kesinti' => Helper::formattedMoney($yapilan_kesinti),
            'yapilan_kesinti_raw' => $yapilan_kesinti,
            'kalan_borc' => Helper::formattedMoney($kalan_borc),
            'kalan_borc_raw' => $kalan_borc,
            'durum' => htmlspecialchars($f->durum, ENT_QUOTES, 'UTF-8'),
            'baslama_tarihi' => $f->baslama_tarihi,
            'baslama_tarihi_formatted' => !empty($f->baslama_tarihi) ? Date::dmY($f->baslama_tarihi) : '',
            'bitis_tarihi' => $f->bitis_tarihi,
            'bitis_tarihi_formatted' => !empty($f->bitis_tarihi) ? Date::dmY($f->bitis_tarihi) : '',
            'aciklama' => htmlspecialchars($f->aciklama ?? '', ENT_QUOTES, 'UTF-8'),
            'gelen_evrak' => htmlspecialchars($f->gelen_evrak ?? '', ENT_QUOTES, 'UTF-8'),
            'giden_evrak' => htmlspecialchars($f->giden_evrak ?? '', ENT_QUOTES, 'UTF-8'),
            'has_belge' => !empty($f->belge_yolu),
            'icra_kesintisi_aktif' => (int)($f->icra_kesintisi_aktif ?? 0)
        ];
    }

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'files' => $formatted_files,
        'stats' => [
            'total_files' => $stats['total_files'],
            'active_files' => $stats['active_files'],
            'pending_files' => $stats['pending_files'],
            'finished_files' => $stats['finished_files'],
            'total_debt' => Helper::formattedMoney($stats['total_debt']),
            'total_deductions' => Helper::formattedMoney($stats['total_deductions']),
            'remaining_debt' => Helper::formattedMoney($stats['remaining_debt'])
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Kaydetme Aksiyonu (Ekle & Güncelle)
if ($action == 'save') {
    $id_encrypted = $_POST['id'] ?? '';
    $id = !empty($id_encrypted) ? Security::decrypt($id_encrypted) : 0;
    
    $person_id_encrypted = $_POST['person_id'] ?? '';
    $person_id = Security::decrypt($person_id_encrypted);

    if (!$person_id) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz personel kimliği']);
        exit;
    }

    $person = $Persons->find($person_id);
    if (!$person || $person->firm_id != $_SESSION['firm_id']) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Personel bulunamadı veya yetkiniz yok']);
        exit;
    }

    // Form girdileri
    $icra_sirasi = (int)($_POST['icra_sirasi'] ?? 1);
    $icra_dairesi = trim($_POST['icra_dairesi'] ?? '');
    $dosya_no = trim($_POST['dosya_no'] ?? '');
    $alacakli = trim($_POST['alacakli'] ?? '');
    $toplam_borc = Helper::formattedMoneyToNumber($_POST['toplam_borc'] ?? '0');
    $kesinti_yontemi = $_POST['kesinti_yontemi'] ?? 'oran';
    $kesinti_orani = ($kesinti_yontemi == 'oran') ? trim($_POST['kesinti_orani'] ?? '') : null;
    $kesinti_tutari = ($kesinti_yontemi == 'sabit') ? Helper::formattedMoneyToNumber($_POST['kesinti_tutari'] ?? '0') : null;
    $durum = $_POST['durum'] ?? 'Bekliyor';
    $baslama_tarihi = !empty($_POST['baslama_tarihi']) ? Date::ymd($_POST['baslama_tarihi']) : null;
    $bitis_tarihi = !empty($_POST['bitis_tarihi']) ? Date::ymd($_POST['bitis_tarihi']) : null;
    $aciklama = trim($_POST['aciklama'] ?? '');
    $gelen_evrak = trim($_POST['gelen_evrak'] ?? '');
    $giden_evrak = trim($_POST['giden_evrak'] ?? '');

    if (empty($icra_dairesi) || empty($dosya_no) || empty($alacakli) || $toplam_borc <= 0) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Lütfen zorunlu alanları doldurun ve geçerli borç tutarı girin.']);
        exit;
    }

    // İcra dairesi icra_daireleri tablosunda yoksa ekle
    $admin_id = $_SESSION['user']->parent_id != 0 ? $_SESSION['user']->parent_id : $_SESSION['user']->id;
    $sql_check_define = "SELECT id FROM icra_daireleri WHERE daire_adi = ? AND silinme_tarihi IS NULL";
    $q_check_define = $Persons->getDb()->prepare($sql_check_define);
    $q_check_define->execute([$icra_dairesi]);
    if ($q_check_define->rowCount() === 0) {
        $sql_add_define = "INSERT INTO icra_daireleri (admin_id, daire_adi, durum, kayit_tarihi) VALUES (?, ?, 'Aktif', NOW())";
        $q_add_define = $Persons->getDb()->prepare($sql_add_define);
        $q_add_define->execute([$admin_id, $icra_dairesi]);
    }

    // Mevcut kaydı bul (güncelleme durumunda)
    $existing = null;
    if ($id > 0) {
        $existing = $PersonIcra->find($id);
        if (!$existing || $existing->deleted_at !== null) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Güncellenecek kayıt bulunamadı']);
            exit;
        }
    }

    $belge_yolu = $existing->belge_yolu ?? null;
    $belge_adi = $existing->belge_adi ?? null;

    // Belge Yükleme Kontrolü
    if (isset($_FILES['belge_dosyasi']) && $_FILES['belge_dosyasi']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['belge_dosyasi'];
        $max_size = 5 * 1024 * 1024; // 5MB limit
        if ($file['size'] > $max_size) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Dosya boyutu çok büyük. Maksimum 5MB yüklenebilir.']);
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed_exts = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx'];
        if (!in_array(strtolower($ext), $allowed_exts)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Sadece PDF, Resim (PNG, JPG, JPEG) ve Doküman (DOC, DOCX, XLS, XLSX) dosyalarına izin verilir.']);
            exit;
        }

        // Klasörü hazırla
        $upload_dir = ROOT . "/uploads/person_documents/{$person_id}/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Eski fiziksel dosyayı sil
        if ($belge_yolu && file_exists(ROOT . "/" . $belge_yolu)) {
            @unlink(ROOT . "/" . $belge_yolu);
        }

        $new_filename = "icra_" . uniqid() . "." . $ext;
        $file_data = file_get_contents($file['tmp_name']);

        // AES-256-GCM Encryption (ISO 27001)
        $method = "AES-256-GCM";
        $key = hash('sha256', 'document-secret-key-iso-27001', true);
        $iv = openssl_random_pseudo_bytes(12);
        $tag = null;
        $encrypted_data = openssl_encrypt($file_data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
        $stored_content = $iv . $tag . $encrypted_data;

        if (file_put_contents($upload_dir . $new_filename, $stored_content) !== false) {
            $belge_yolu = "uploads/person_documents/{$person_id}/" . $new_filename;
            $belge_adi = $file['name'];
        } else {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Belge dosyası kaydedilemedi.']);
            exit;
        }
    }

    $data = [
        'id' => $id,
        'person_id' => $person_id,
        'firm_id' => $_SESSION['firm_id'],
        'icra_sirasi' => $icra_sirasi,
        'icra_dairesi' => $icra_dairesi,
        'dosya_no' => $dosya_no,
        'alacakli' => $alacakli,
        'toplam_borc' => $toplam_borc,
        'baslama_tarihi' => $baslama_tarihi,
        'bitis_tarihi' => $bitis_tarihi,
        'kesinti_yontemi' => $kesinti_yontemi,
        'kesinti_orani' => $kesinti_orani,
        'kesinti_tutari' => $kesinti_tutari,
        'durum' => $durum,
        'aciklama' => $aciklama,
        'gelen_evrak' => $gelen_evrak,
        'giden_evrak' => $giden_evrak,
        'belge_yolu' => $belge_yolu,
        'belge_adi' => $belge_adi
    ];

    try {
        $save_res = $PersonIcra->saveWithAttr($data);
        $log_action = ($id > 0) ? 'icra_dosya_guncelle' : 'icra_dosya_ekle';
        $log_desc = ($id > 0) ? "İcra dosyası güncellendi. Dosya No: {$dosya_no}" : "İcra dosyası eklendi. Dosya No: {$dosya_no}";
        ActivityLogModel::log('icra', $log_action, $log_desc);

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => ($id > 0) ? 'İcra dosyası başarıyla güncellendi.' : 'İcra dosyası başarıyla eklendi.',
            'id' => $save_res
        ]);
    } catch (\Throwable $e) {
        system_log_exception($e, ['operation' => 'icra_save']);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'İşlem kaydedilirken bir hata oluştu.']);
    }
    exit;
}

// 4. Silme Aksiyonu (Soft Delete)
if ($action == 'delete') {
    $id_encrypted = $_POST['id'] ?? '';
    $id = Security::decrypt($id_encrypted);

    if (!$id) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz dosya kimliği']);
        exit;
    }

    $icra_file = $PersonIcra->find($id);
    if (!$icra_file || $icra_file->deleted_at !== null) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Dosya bulunamadı']);
        exit;
    }

    // Personel yetkisi ve firmasını kontrol et
    $person = $Persons->find($icra_file->person_id);
    if (!$person || $person->firm_id != $_SESSION['firm_id']) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz işlem']);
        exit;
    }

    try {
        $PersonIcra->softDelete($id_encrypted);
        ActivityLogModel::log('icra', 'icra_dosya_sil', "İcra dosyası silindi (Soft Delete). Dosya No: {$icra_file->dosya_no}");

        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'İcra dosyası başarıyla silindi.']);
    } catch (\Throwable $e) {
        system_log_exception($e, ['operation' => 'icra_delete']);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Dosya silinirken bir hata oluştu.']);
    }
    exit;
}

// 5. Bordro Kesintisi Aktif/Pasif Yapma Aksiyonu
if ($action == 'toggle_payroll_deduction') {
    $person_id_encrypted = $_POST['person_id'] ?? '';
    $person_id = Security::decrypt($person_id_encrypted);
    $active = (int)($_POST['active'] ?? 0);

    if (!$person_id) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz personel kimliği']);
        exit;
    }

    $person = $Persons->find($person_id);
    if (!$person || $person->firm_id != $_SESSION['firm_id']) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Personel bulunamadı veya yetkiniz yok']);
        exit;
    }

    try {
        $sql = "UPDATE persons SET icra_kesintisi_aktif = ? WHERE id = ?";
        $query = $Persons->getDb()->prepare($sql);
        $query->execute([$active, $person_id]);

        $log_desc = $active ? "Personel için icra bordro kesintisi aktif edildi." : "Personel için icra bordro kesintisi pasif edildi.";
        ActivityLogModel::log('icra', 'icra_kesintisi_toggle', $log_desc . " Personel: {$person->full_name}");

        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Bordro kesinti ayarı başarıyla güncellendi.']);
    } catch (\Throwable $e) {
        system_log_exception($e, ['operation' => 'icra_toggle_deduction']);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Ayarlar güncellenirken bir hata oluştu.']);
    }
    exit;
}

// 6. Kesintiler Geçmişi Detay Aksiyonu
if ($action == 'deductions_history') {
    try {
        $person_id_param = $_POST['person_id'] ?? $_GET['person_id'] ?? '';
        $person_id = is_numeric($person_id_param) ? (int)$person_id_param : (!empty($person_id_param) ? (int)Security::decrypt($person_id_param) : 0);
        
        $file_id_param = $_POST['file_id'] ?? $_GET['file_id'] ?? '';
        $file_id = is_numeric($file_id_param) ? (int)$file_id_param : (!empty($file_id_param) ? (int)Security::decrypt($file_id_param) : 0);

        $dosya_no = null;

        if ($file_id > 0) {
            $icra_file = $PersonIcra->find($file_id);
            if ($icra_file) {
                $person_id = (int)$icra_file->person_id;
                $dosya_no = $icra_file->dosya_no;
            }
        } elseif ($person_id > 0) {
            $person_files = $PersonIcra->getByPersonId($person_id);
            if (!empty($person_files)) {
                $file_id = (int)$person_files[0]->id;
                $dosya_no = $person_files[0]->dosya_no;
            }
        }

        if (!$person_id) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Geçersiz personel veya dosya kimliği']);
            exit;
        }

        $person = $Persons->find($person_id);
        if (!$person || $person->firm_id != $_SESSION['firm_id']) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Personel bulunamadı veya yetkiniz yok']);
            exit;
        }

        $history = $PersonIcra->getDeductionsHistory($person_id, $dosya_no);

        $formatted = [];
        $total_sum = 0.0;

        foreach ($history as $h) {
            $tutar = (float)$h->tutar;
            $total_sum += $tutar;

            $formatted[] = [
                'id' => $h->id,
                'donem' => sprintf('%02d/%04d', (int)$h->ay, (int)$h->yil),
                'tutar' => Helper::formattedMoney($tutar),
                'tutar_raw' => $tutar,
                'turu' => htmlspecialchars($h->turu ?? 'İcra Kesintisi', ENT_QUOTES, 'UTF-8'),
                'aciklama' => htmlspecialchars($h->aciklama ?? '', ENT_QUOTES, 'UTF-8'),
                'created_at' => !empty($h->created_at) ? Date::dmYHis($h->created_at) : ''
            ];
        }

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'person_name' => htmlspecialchars($person->full_name, ENT_QUOTES, 'UTF-8'),
            'dosya_no' => htmlspecialchars($dosya_no ?? 'Tüm Dosyalar', ENT_QUOTES, 'UTF-8'),
            'file_id' => $file_id > 0 ? Security::encrypt($file_id) : '',
            'total_amount' => Helper::formattedMoney($total_sum),
            'history' => $formatted
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        system_log_exception($e, ['operation' => 'icra_deductions_history']);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Kesinti geçmişi yüklenirken bir hata oluştu.']);
    }
    exit;
}

// Tanımsız aksiyon
ob_clean();
echo json_encode(['status' => 'error', 'message' => 'Geçersiz aksiyon.']);
exit;
