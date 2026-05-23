<?php

!defined("ROOT") ? define("ROOT", dirname(dirname(__DIR__))) : null;
require_once ROOT."/Database/require.php";
require_once ROOT. "/Model/AbonelikPaketleriModel.php";

use App\Helper\Security;
$Packages = new AbonelikPaketleriModel();

if (isset($_GET['action']) && $_GET['action'] == 'getPackage') {
    $id = Security::decrypt($_GET['id']);
    $pkg = $Packages->find($id);

    $package = null;
    if ($pkg) {
        $package = [
            'id' => $pkg->id,
            'name' => $pkg->ad,
            'price' => $pkg->fiyat,
            'days' => $pkg->sure,
            'money_unit' => 'TRY'
        ];
    }

    $res = [
        'status' => 'success',
        'message' => 'Paketler başarıyla getirildi',
        'data' => $package
    ];
    echo json_encode($res);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'buyPackage') {
    $encryptedId = $_POST['package_id'] ?? '';
    $package_id = Security::decrypt($encryptedId);
    $duration_type = $_POST['duration_type'] ?? 'monthly'; // 'monthly' or 'yearly'
    
    $user_id = $_SESSION["user"]->id;
    
    // Zaten onay bekleyen bir talep var mı kontrol et
    require_once ROOT . "/Model/KullaniciAbonelikleriModel.php";
    $KullaniciAbonelikleri = new KullaniciAbonelikleriModel();
    $sqlCheck = $KullaniciAbonelikleri->getDb()->prepare("SELECT COUNT(*) FROM kullanici_abonelikleri WHERE kullanici_id = ? AND durum = 'onay_bekliyor'");
    $sqlCheck->execute([$user_id]);
    if ($sqlCheck->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Zaten onay bekleyen bir paket satın alma talebiniz bulunmaktadır. Yeni bir talep oluşturamazsınız.']);
        exit();
    }
    
    // Fetch package details from abonelik_paketleri
    require_once ROOT . "/Model/AbonelikPaketleriModel.php";
    $AbonelikPaketleri = new AbonelikPaketleriModel();
    $pkg = $AbonelikPaketleri->find($package_id);
    
    if (!$pkg) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz paket seçimi.']);
        exit();
    }
    
    // Calculate price and days
    $price = (float)$pkg->fiyat;
    if ($duration_type === 'yearly') {
        $days = 365;
        $total_amount = $price * 10;
    } else {
        $days = (int)$pkg->sure;
        $total_amount = $price;
    }
    
    // Calculate dates
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$days days"));
    
    // Insert into kullanici_abonelikleri
    require_once ROOT . "/Model/KullaniciAbonelikleriModel.php";
    $KullaniciAbonelikleri = new KullaniciAbonelikleriModel();
    
    $subData = [
        "kullanici_id" => $user_id,
        "paket_id" => $package_id,
        "baslangic_tarihi" => $startDate,
        "bitis_tarihi" => $endDate,
        "durum" => "onay_bekliyor", // Awaiting approval
        "firma_hakki" => $pkg->firma_hakki,
        "alt_kullanici_hakki" => $pkg->alt_kullanici_hakki,
        "aciklama" => "Kullanıcı Talebi - Banka Havalesi (" . ($duration_type === 'yearly' ? 'Yıllık' : 'Aylık') . ")",
        "bildirim_goruldu" => 0,
        "user_bildirim_goruldu" => 0
    ];
    
    try {
        $encryptedSubId = $KullaniciAbonelikleri->saveWithAttr($subData);
        $subId = Security::safeDecrypt($encryptedSubId);
        
        if (!$subId) {
            throw new Exception("Abonelik kaydı oluşturulamadı.");
        }
        
        // Insert into odemeler
        require_once ROOT . "/Model/OdemelerModel.php";
        $Odemeler = new OdemelerModel();
        
        $payData = [
            "kullanici_id" => $user_id,
            "abonelik_id" => $subId,
            "tutar" => $total_amount,
            "odeme_tarihi" => date('Y-m-d H:i:s'),
            "odeme_yontemi" => "Banka Havalesi",
            "durum" => "beklemede" // Awaiting payment verification
        ];
        
        $Odemeler->saveWithAttr($payData);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Abonelik talebiniz başarıyla alındı. Ödemeniz onaylandıktan sonra aktif edilecektir.'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'İşlem sırasında bir hata oluştu: ' . $e->getMessage()
        ]);
    }
    exit();
}