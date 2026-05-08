<?php
error_reporting(0);
ini_set('display_errors', 0);

!defined("ROOT") ? define("ROOT", dirname(dirname(__DIR__))) : false;
require_once "../../Database/require.php";
require_once "../../Model/Persons.php";
require_once "../../Model/Auths.php";
require_once "../../App/Helper/helper.php";
require_once "../../App/Helper/security.php";

use App\Helper\Security;
use App\Helper\Helper;

$Auths = new Auths();
$Persons = new Persons();

// Yetki kontrolü (Personel ekleme/güncelleme yetkisi olanlar yönetebilir)
$Auths->hasPermissionReturn('personnel_add_update');
$Auths->checkFirmReturn();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$person_id_encrypted = $_POST['person_id'] ?? $_GET['person_id'] ?? '';
$person_id = Security::decrypt($person_id_encrypted);

if (!$person_id) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz Personel Kimliği']);
    exit;
}

// Personelin varlığını ve firmaya ait olduğunu doğrula
$person = $Persons->find($person_id);
if (!$person || $person->firm_id != $_SESSION['firm_id']) {
    echo json_encode(['status' => 'error', 'message' => 'Personel bulunamadı veya yetkiniz yok']);
    exit;
}

$upload_dir = dirname(dirname(__DIR__)) . "/uploads/person_documents/{$person_id}/";
$metadata_file = $upload_dir . "metadata.json";

// Standart Belge Tanımları
$standard_docs = [
    'kimlik' => 'Kimlik Fotokopisi / Nüfus Cüzdanı',
    'ikametgah' => 'Yerleşim Yeri Belgesi (İkametgah)',
    'ehliyet' => 'Sürücü Belgesi (Ehliyet)',
    'sgk' => 'SGK İşe Giriş Bildirgesi',
    'saglik' => 'Sağlık Raporu',
    'adli_sicil' => 'Adli Sicil Kaydı',
    'sozlesme' => 'İş Sözleşmesi'
];

// Metadata yükle veya oluştur
function loadMetadata($metadata_file, $standard_docs) {
    if (file_exists($metadata_file)) {
        $meta = json_decode(file_get_contents($metadata_file), true);
        if (!is_array($meta)) {
            $meta = [];
        }
    } else {
        $meta = [];
    }

    if (!isset($meta['standard_files'])) {
        $meta['standard_files'] = [];
    }
    if (!isset($meta['custom_files'])) {
        $meta['custom_files'] = [];
    }

    // Standart dosyaların eksik anahtarlarını tamamla
    foreach ($standard_docs as $key => $title) {
        if (!isset($meta['standard_files'][$key])) {
            $meta['standard_files'][$key] = null;
        }
    }

    return $meta;
}

function saveMetadata($metadata_file, $meta) {
    if (!is_dir(dirname($metadata_file))) {
        mkdir(dirname($metadata_file), 0777, true);
    }
    file_put_contents($metadata_file, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Fiziksel dosya kontrolleri ile metadata'yı senkronize et
function syncMetadataWithFiles($upload_dir, &$meta) {
    if (!is_dir($upload_dir)) return;

    // Standart dosyaları doğrula
    foreach ($meta['standard_files'] as $key => $data) {
        if ($data) {
            $file_path = $upload_dir . $data['filename'];
            if (!file_exists($file_path)) {
                $meta['standard_files'][$key] = null;
            }
        }
    }

    // Özel dosyaları doğrula
    $valid_custom = [];
    foreach ($meta['custom_files'] as $file) {
        $file_path = $upload_dir . $file['filename'];
        if (file_exists($file_path)) {
            $valid_custom[] = $file;
        }
    }
    $meta['custom_files'] = $valid_custom;
}

if ($action == 'list') {
    $meta = loadMetadata($metadata_file, $standard_docs);
    syncMetadataWithFiles($upload_dir, $meta);
    saveMetadata($metadata_file, $meta);

    echo json_encode([
        'status' => 'success',
        'standard_docs' => $standard_docs,
        'standard_files' => $meta['standard_files'],
        'custom_files' => $meta['custom_files']
    ]);
    exit;
}

if ($action == 'upload') {
    $doc_type = $_POST['doc_type'] ?? ''; // 'kimlik', 'ikametgah', etc. or 'custom'
    $doc_title = $_POST['doc_title'] ?? '';

    if (empty($doc_type)) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen belge türünü seçin.']);
        exit;
    }

    if ($doc_type == 'custom' && empty($doc_title)) {
        echo json_encode(['status' => 'error', 'message' => 'Özel belgeler için lütfen başlık girin.']);
        exit;
    }

    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] != UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Dosya yüklenirken bir hata oluştu.']);
        exit;
    }

    $file = $_FILES['document_file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed_exts = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

    if (!in_array(strtolower($ext), $allowed_exts)) {
        echo json_encode(['status' => 'error', 'message' => 'Sadece PDF, PNG, JPG, JPEG, DOC, DOCX, XLS, XLSX ve TXT dosyalarına izin verilir.']);
        exit;
    }

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $meta = loadMetadata($metadata_file, $standard_docs);

    if ($doc_type != 'custom') {
        // Eski standart belge varsa sil
        if (isset($meta['standard_files'][$doc_type]) && $meta['standard_files'][$doc_type]) {
            $old_file = $upload_dir . $meta['standard_files'][$doc_type]['filename'];
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }

        $new_filename = $doc_type . "_" . uniqid() . "." . $ext;
        $file_data = file_get_contents($file['tmp_name']);

        // AES-256-GCM Encryption (ISO 27001)
        $method = "AES-256-GCM";
        $key = hash('sha256', 'document-secret-key-iso-27001', true);
        $iv = openssl_random_pseudo_bytes(12);
        $tag = null;
        $encrypted_data = openssl_encrypt($file_data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
        $stored_content = $iv . $tag . $encrypted_data;

        if (file_put_contents($upload_dir . $new_filename, $stored_content) !== false) {
            $meta['standard_files'][$doc_type] = [
                'filename' => $new_filename,
                'original_name' => $file['name'],
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
            saveMetadata($metadata_file, $meta);
            echo json_encode(['status' => 'success', 'message' => 'Belge başarıyla yüklendi ve şifrelendi.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Dosya kaydedilemedi.']);
        }
    } else {
        $custom_id = uniqid();
        $new_filename = "custom_" . $custom_id . "_" . uniqid() . "." . $ext;
        $file_data = file_get_contents($file['tmp_name']);

        // AES-256-GCM Encryption (ISO 27001)
        $method = "AES-256-GCM";
        $key = hash('sha256', 'document-secret-key-iso-27001', true);
        $iv = openssl_random_pseudo_bytes(12);
        $tag = null;
        $encrypted_data = openssl_encrypt($file_data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
        $stored_content = $iv . $tag . $encrypted_data;

        if (file_put_contents($upload_dir . $new_filename, $stored_content) !== false) {
            $meta['custom_files'][] = [
                'id' => $custom_id,
                'title' => htmlspecialchars($doc_title),
                'filename' => $new_filename,
                'original_name' => $file['name'],
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
            saveMetadata($metadata_file, $meta);
            echo json_encode(['status' => 'success', 'message' => 'Özel belge başarıyla yüklendi ve şifrelendi.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Dosya kaydedilemedi.']);
        }
    }
    exit;
}

if ($action == 'delete') {
    $doc_type = $_POST['doc_type'] ?? '';
    $custom_id = $_POST['custom_id'] ?? '';

    if (empty($doc_type)) {
        echo json_encode(['status' => 'error', 'message' => 'Lütfen silinecek belge türünü belirtin.']);
        exit;
    }

    $meta = loadMetadata($metadata_file, $standard_docs);
    $file_to_delete = '';

    if ($doc_type != 'custom') {
        if (isset($meta['standard_files'][$doc_type]) && $meta['standard_files'][$doc_type]) {
            $file_to_delete = $upload_dir . $meta['standard_files'][$doc_type]['filename'];
            $meta['standard_files'][$doc_type] = null;
        }
    } else {
        $new_custom = [];
        foreach ($meta['custom_files'] as $c_file) {
            if ($c_file['id'] == $custom_id) {
                $file_to_delete = $upload_dir . $c_file['filename'];
            } else {
                $new_custom[] = $c_file;
            }
        }
        $meta['custom_files'] = $new_custom;
    }

    if ($file_to_delete && file_exists($file_to_delete)) {
        @unlink($file_to_delete);
    }

    saveMetadata($metadata_file, $meta);
    echo json_encode(['status' => 'success', 'message' => 'Belge başarıyla silindi.']);
    exit;
}

if ($action == 'download') {
    $doc_type = $_GET['doc_type'] ?? '';
    $custom_id = $_GET['custom_id'] ?? '';

    if (empty($doc_type)) {
        die('Geçersiz istek');
    }

    $meta = loadMetadata($metadata_file, $standard_docs);
    $file_info = null;

    if ($doc_type != 'custom') {
        if (isset($meta['standard_files'][$doc_type]) && $meta['standard_files'][$doc_type]) {
            $file_info = $meta['standard_files'][$doc_type];
        }
    } else {
        foreach ($meta['custom_files'] as $c_file) {
            if ($c_file['id'] == $custom_id) {
                $file_info = $c_file;
                break;
            }
        }
    }

    if (!$file_info) {
        die('Belge bulunamadı');
    }

    $file_path = $upload_dir . $file_info['filename'];

    if (!file_exists($file_path)) {
        die('Fiziksel dosya bulunamadı.');
    }

    $original_name = $file_info['original_name'];
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
        die('Dosya şifresi çözülemedi.');
    }

    // Bazı tarayıcıların dosyayı indirmek yerine doğrudan açmasını sağlamak için (Inline vs Attachment)
    $inline_mimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg', 'text/plain'];
    $disposition = in_array(strtolower($mime_type), $inline_mimes) ? 'inline' : 'attachment';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($original_name) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($decrypted_data));
    echo $decrypted_data;
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Bilinmeyen Eylem']);
exit;
