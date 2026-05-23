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
        
        // Send email notifications to superadmins
        try {
            $stmt = $KullaniciAbonelikleri->getDb()->prepare("SELECT email, full_name FROM users WHERE superadmin = 1 AND deleted_at IS NULL");
            $stmt->execute();
            $superadmins = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            if (!empty($superadmins)) {
                require_once ROOT . "/mail-settings.php";
                
                // Fetch buyer name & email
                $buyer_name = $_SESSION["user"]->full_name;
                $buyer_email = $_SESSION["user"]->email;
                $pkg_name = $pkg->ad;
                $period = $duration_type === 'yearly' ? 'Yıllık' : 'Aylık';
                $amount = number_format($total_amount, 2, ',', '.') . " TRY";
                $date_str = date('d.m.Y H:i:s');
                
                // HTML body
                $body = "
                <!DOCTYPE html>
                <html lang='tr'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Yeni Paket Satın Alma Talebi</title>
                </head>
                <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f7f7f7; padding: 20px;'>
                    <table width='100%' cellpadding='0' cellspacing='0' style='max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 6px; border: 1px solid #e0e0e0; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);'>
                        <tr>
                            <td style='padding: 20px; background-color: #2c3e50; color: #ffffff; text-align: center; border-top-left-radius: 6px; border-top-right-radius: 6px;'>
                                <h2 style='margin: 0; font-size: 20px;'>Yeni Paket Satın Alma Talebi Alındı</h2>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 20px;'>
                                <p>Merhaba,</p>
                                <p>Sistemde yeni bir paket satın alma talebi oluşturulmuştur. Ayrıntılar aşağıdadır:</p>
                                <hr style='border: 1px solid #e0e0e0; margin: 20px 0;'>
                                <h3 style='color: #2c3e50; margin: 0;'>Müşteri Bilgileri</h3>
                                <p style='margin-top: 8px;'>
                                    <strong>Ad Soyad:</strong> {$buyer_name}<br>
                                    <strong>E-posta:</strong> {$buyer_email}<br>
                                </p>
                                <h3 style='color: #2c3e50; margin: 0;'>Paket Bilgileri</h3>
                                <p style='margin-top: 8px;'>
                                    <strong>Paket Adı:</strong> {$pkg_name}<br>
                                    <strong>Süre:</strong> {$period}<br>
                                    <strong>Toplam Tutar:</strong> {$amount}<br>
                                    <strong>Tarih:</strong> {$date_str}<br>
                                </p>
                                <hr style='border: 1px solid #e0e0e0; margin: 20px 0;'>
                                <p>Lütfen ödemeyi kontrol edip yönetici panelinden onaylayınız.</p>
                                <p>Saygılarımızla,<br><strong>Puantör Ekibi</strong></p>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; background-color: #f0f0f0; text-align: center; font-size: 12px; color: #888; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;'>
                                © Puantör. Tüm hakları saklıdır.
                            </td>
                        </tr>
                    </table>
                </body>
                </html>";
                
                foreach ($superadmins as $admin) {
                    try {
                        $mail->clearAddresses();
                        $mail->clearReplyTos();
                        $mail->clearAttachments();
                        
                        $mail->setFrom('sifre@puantor.com.tr', 'Puantör Abonelik Sistemi');
                        $mail->addAddress($admin->email, $admin->full_name);
                        $mail->isHTML(true);
                        $mail->Subject = 'Yeni Paket Satın Alma Talebi: ' . $pkg_name;
                        $mail->Body = $body;
                        $mail->AltBody = strip_tags($body);
                        $mail->CharSet = 'UTF-8';
                        
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("E-posta gönderim hatası (Admin: " . $admin->email . "): " . $mail->ErrorInfo);
                    }
                }
            }
        } catch (Exception $mailEx) {
            error_log("Superadmin e-posta listesi çekilirken veya e-posta gönderilirken hata oluştu: " . $mailEx->getMessage());
        }
        
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