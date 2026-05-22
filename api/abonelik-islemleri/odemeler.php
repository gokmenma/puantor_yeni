<?php
require_once "../../Database/require.php";
require_once "../../Model/OdemelerModel.php";
require_once "../../Model/AbonelikPaketleriModel.php";
require_once "../../Model/KullaniciAbonelikleriModel.php";
require_once "../../App/Helper/security.php";

use App\Helper\Security;

$odemelerModel = new OdemelerModel();
$paketModel = new AbonelikPaketleriModel();
$abonelikModel = new KullaniciAbonelikleriModel();

if (isset($_POST["action"]) && $_POST["action"] == "updatePaymentStatus") {
    $encryptedId = $_POST["id"] ?? "";
    $status = $_POST["status"] ?? "";

    // Validate status
    if (!in_array($status, ['basarili', 'basarisiz', 'beklemede'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Geçersiz durum değeri."
        ]);
        exit();
    }

    try {
        if ($odemelerModel->updateStatus($encryptedId, $status)) {
            $statusStr = '';
            switch ($status) {
                case 'basarili': $statusStr = 'Başarılı'; break;
                case 'basarisiz': $statusStr = 'Başarısız'; break;
                case 'beklemede': $statusStr = 'Beklemede'; break;
            }
            $status = "success";
            $message = "Ödeme durumu başarıyla '{$statusStr}' olarak güncellendi.";
        } else {
            $status = "error";
            $message = "Ödeme durumu güncellenemedi veya kayıt bulunamadı.";
        }
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit();
}

if (isset($_POST["action"]) && $_POST["action"] == "addManualSale") {
    try {
        $encryptedKullaniciId = $_POST["kullanici_id"] ?? "";
        $encryptedPaketId = $_POST["paket_id"] ?? "";
        $firma_hakki = $_POST["firma_hakki"] ?? 0;
        $alt_kullanici_hakki = $_POST["alt_kullanici_hakki"] ?? 0;
        $baslangic_tarihi = $_POST["baslangic_tarihi"] ?? "";
        $bitis_tarihi = $_POST["bitis_tarihi"] ?? "";

        if (!$encryptedKullaniciId || !$encryptedPaketId || !$baslangic_tarihi || !$bitis_tarihi) {
            throw new Exception("Lütfen gerekli tüm alanları doldurun.");
        }

        $kullanici_id = Security::safeDecrypt($encryptedKullaniciId);
        $paket_id = Security::safeDecrypt($encryptedPaketId);

        if (!$kullanici_id || !$paket_id) {
            throw new Exception("Geçersiz kullanıcı veya paket seçimi.");
        }

        // Fetch package details for secure price lookup
        $pkg = $paketModel->find($paket_id);
        if (!$pkg) {
            throw new Exception("Seçilen paket sistemde bulunamadı.");
        }

        // Parse and format dates
        $startDateObj = DateTime::createFromFormat('d.m.Y', $baslangic_tarihi);
        $endDateObj = DateTime::createFromFormat('d.m.Y', $bitis_tarihi);
        if (!$startDateObj || !$endDateObj) {
            throw new Exception("Geçersiz tarih formatı.");
        }

        $startDateFormatted = $startDateObj->format('Y-m-d');
        $endDateFormatted = $endDateObj->format('Y-m-d');

        // Create user subscription
        $subData = [
            "kullanici_id" => $kullanici_id,
            "paket_id" => $paket_id,
            "baslangic_tarihi" => $startDateFormatted,
            "bitis_tarihi" => $endDateFormatted,
            "durum" => "aktif",
            "firma_hakki" => $firma_hakki,
            "alt_kullanici_hakki" => $alt_kullanici_hakki,
            "aciklama" => "Manuel Satış - Yönetici",
            "bildirim_goruldu" => 0,
            "user_bildirim_goruldu" => 0
        ];

        $encryptedSubId = $abonelikModel->saveWithAttr($subData);
        $subId = Security::safeDecrypt($encryptedSubId);

        if (!$subId) {
            throw new Exception("Abonelik kaydı oluşturulamadı.");
        }

        // Create payment record
        $payData = [
            "kullanici_id" => $kullanici_id,
            "abonelik_id" => $subId,
            "tutar" => $pkg->fiyat,
            "odeme_tarihi" => date('Y-m-d H:i:s'),
            "odeme_yontemi" => "Manuel Satış",
            "durum" => "basarili"
        ];

        $odemelerModel->saveWithAttr($payData);

        echo json_encode([
            "status" => "success",
            "message" => "Paket satışı başarıyla gerçekleştirildi ve ödeme geçmişine eklendi."
        ]);
        exit();

    } catch (Exception $ex) {
        echo json_encode([
            "status" => "error",
            "message" => $ex->getMessage()
        ]);
        exit();
    }
}

if (isset($_POST["action"]) && $_POST["action"] == "deletePayment") {
    $encryptedId = $_POST["id"] ?? "";
    try {
        $paymentId = Security::safeDecrypt($encryptedId);
        if (!$paymentId) {
            throw new Exception("Geçersiz işlem ID.");
        }
        
        $payment = $odemelerModel->find($paymentId);
        if ($payment) {
            // Delete associated subscription if exists
            if (!empty($payment->abonelik_id)) {
                $sql = $odemelerModel->getDb()->prepare("DELETE FROM kullanici_abonelikleri WHERE id = ?");
                $sql->execute([$payment->abonelik_id]);
            }
            // Delete payment
            $odemelerModel->delete($encryptedId);
            
            $status = "success";
            $message = "Satın alma işlemi ve ilişkili abonelik başarıyla silindi.";
        } else {
            $status = "error";
            $message = "Kayıt bulunamadı.";
        }
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit();
}
