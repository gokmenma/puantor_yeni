<?php
ob_start();
require_once 'Database/require.php';
require_once 'Model/UserModel.php';
require_once 'Model/RolesModel.php';
require_once 'Model/Auths.php';
require_once 'Model/RoleAuthsModel.php';
require_once 'Model/Company.php';
require_once 'App/Helper/security.php';
require_once 'Model/Cases.php';


use Database\Db;
use App\Helper\Security;


$db = new Db();

$User = new UserModel();
$company = new Company();
$Roles = new Roles();
$Auths = new Auths();
$RoleAuths = new RoleAuthsModel();

function alertdanger($message, $type = "danger", $title = "Hata!")
{
    $isDanger = $type === "danger";
    $bg = $isDanger ? "#fef2f2" : "#f0fdf4";
    $border = $isDanger ? "#fca5a5" : "#86efac";
    $color = $isDanger ? "#991b1b" : "#166534";
    $icon = $isDanger ? "ti-alert-triangle" : "ti-circle-check";
    echo '<div class="alert alert-' . $type . ' d-flex align-items-center gap-2 py-2 px-3 mb-3 text-sm animate-fade-in" role="alert" style="border-radius: 14px; background-color: ' . $bg . '; border: 1px solid ' . $border . '; color: ' . $color . ';">
            <i class="ti ' . $icon . '" style="font-size: 1.15rem; flex-shrink: 0;"></i>
            <div>
                <strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong>
                <div>' . $message . '</div>
            </div>
        </div>';

}
?>
<html lang="tr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Puantor | Puantaj Takip Uygulaması</title>
    <meta name="msapplication-TileColor" content="#066fd1">
    <meta name="theme-color" content="#066fd1">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">

    <!-- CSS files -->
    <link href="./dist/css/tabler.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/tabler-flags.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/tabler-payments.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/tabler-vendors.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/style.css?1726507346" rel="stylesheet">
    <link href="./dist/css/demo.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/tabler-icons.min.css?1726507346" rel="stylesheet">
    <?php if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1' && $_SERVER['HTTP_HOST'] !== 'localhost'): ?>
    <script src="https://www.google.com/recaptcha/api.js?hl=tr" async defer></script>
    <?php endif; ?>
    <link rel="icon" href="./static/favicon.ico" type="image/x-icon" />


    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
            --mobile-primary: #206bc4;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
            background-color: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Blob Backgrounds */
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
            opacity: 0.6;
        }
        .bg-blob-primary {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(32, 107, 196, 0.2) 0%, rgba(32, 107, 196, 0) 70%);
            top: -100px;
            left: -100px;
        }
        .bg-blob-indigo {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%);
            bottom: -100px;
            right: -100px;
        }

        /* Container */
        .login-container {
            width: 100%;
            max-width: 460px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 28px;
            padding: 3rem 2.25rem 2.5rem;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02);
            z-index: 10;
            position: relative;
            animation: fadeIn 0.4s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .login-subtitle {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Inputs & Form */
        .form-control {
            font-size: 0.95rem;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.25s ease;
            color: #1e293b;
        }
        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--mobile-primary);
            box-shadow: 0 0 0 4px rgba(32, 107, 196, 0.12);
        }
        .input-icon-addon-right {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            display: flex;
            align-items: center;
            cursor: pointer;
            pointer-events: auto;
            font-size: 1.2rem;
            transition: color 0.2s ease;
            z-index: 10;
        }
        .input-icon-addon-right:hover {
            color: var(--mobile-primary);
        }

        /* Button */
        .btn-primary {
            border-radius: 14px;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            background: linear-gradient(135deg, #206bc4 0%, #1a569d 100%);
            border: none;
            box-shadow: 0 8px 24px rgba(32, 107, 196, 0.2);
            transition: all 0.25s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2475d7 0%, #1d61b3 100%);
            box-shadow: 0 10px 28px rgba(32, 107, 196, 0.3);
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: translateY(1px) scale(0.98);
            box-shadow: 0 4px 12px rgba(32, 107, 196, 0.15);
        }
        .btn-primary:disabled {
            opacity: 0.55;
            box-shadow: none;
            transform: none;
        }

        /* Footer Link */
        .hover-underline {
            transition: color 0.2s ease;
            font-weight: 500;
        }
        .hover-underline:hover {
            color: var(--mobile-primary) !important;
            text-decoration: underline !important;
        }

        @media (max-width: 480px) {
            body {
                background-color: #ffffff;
                padding: 0;
                align-items: stretch;
            }
            .bg-blob {
                display: none;
            }
            .login-container {
                max-width: 100%;
                min-height: 100dvh;
                min-height: 100vh;
                border-radius: 0;
                border: none;
                box-shadow: none;
                background: #ffffff;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 2rem 1.75rem;
            }
        }
    </style>


</head>

<body>
    <script>
        setTimeout(function () {
            $('.alert-danger').each(function () {
                $(this).fadeOut(500, function () {
                    $(this).remove();
                });
            });
        }, 8000);
    </script>
    <script src="./dist/js/demo-theme.min.js?1726507346"></script>

    <div class="bg-blob bg-blob-primary"></div>
    <div class="bg-blob bg-blob-indigo"></div>

    <div class="login-container">
        <div class="text-center mb-1">
            <div class="mb-3 d-flex justify-content-center align-items-center" style="height: 80px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.03));">
                <img src="./static/Logo-ai.svg" style="height: 100px; width: auto; max-width: 100%;" alt="Puantor Logo">
            </div>
            <p class="login-subtitle">Yeni hesap oluşturun</p>
        </div>

        <div class="login-middle-form">
            <?php

            if (isset($_POST['action']) && $_POST['action'] == 'saveUser') {
                $recaptchaSecret = '6LfHuWYqAAAAAI4GfJIXZxpeoQGKDFN-Tr24766z';
                $recaptchaResponse = $_POST['g-recaptcha-response'];


                $isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || $_SERVER['HTTP_HOST'] === 'localhost';

                if ($isLocal) {
                    $responseKeys = ["success" => true];
                } else {
                    // reCAPTCHA doğrulama isteği
                    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
                    $responseKeys = json_decode($response, true);
                }



                $full_name = preg_replace('/\s+/', ' ', trim($_POST['full_name']));
                $company_name = preg_replace('/\s+/', ' ', trim($_POST['company_name']));
                $email = preg_replace('/\s+/', ' ', trim($_POST['email']));
                $password = preg_replace('/\s+/', ' ', trim($_POST['password']));

                //Ad Soyad alanı boş bırakıldıysa hata mesajı verilir
                if (empty($full_name)) {
                    echo alertdanger('Ad Soyad alanı boş bırakılamaz.');
                    //ad soyad 3 karakterden az ise hata mesajı verilir
                } elseif (strlen($full_name) < 3) {
                    echo alertdanger('Ad Soyad en az 3 karakter olmalıdır.');

                    //firma adı alanı boş bırakıldıysa hata mesajı verilir
                } elseif (empty($company_name)) {
                    echo alertdanger('Firma adı boş bırakılamaz.');

                    //firma adı 3 karakterden az ise hata mesajı verilir
                } elseif (strlen($company_name) < 3) {
                    echo alertdanger('Firma adı en az 3 karakter olmalıdır.');

                    //email alanı boş bırakıldıysa hata mesajı verilir
                } elseif (empty($email)) {
                    echo alertdanger('Email alanı boş bırakılamaz.');

                    //şifre alanı boş bırakıldıysa hata mesajı verilir
                } elseif (empty($password)) {
                    echo alertdanger('Şifre alanı boş bırakılamaz.');

                    //şifre alanı en az 6 karakter olmalıdır
                } elseif (strlen($password) < 6) {
                    echo alertdanger('Şifre en az 6 karakter olmalıdır.');

                    //şifre alanında büyük harf, küçük harf ve rakam olmalıdır
                } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                    echo alertdanger('Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.');


                    //email adresi geçerli bir email adresi olup olmadığı kontrol edilir
                } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo alertdanger('Geçerli bir email adresi giriniz.');

                    //şartlar ve koşullar kabul edilmediyse hata mesajı verilir
                } else if (!isset($_POST['terms_of_service'])) {
                    echo alertdanger('Şartlar ve koşulları kabul etmelisiniz.');

                    //Tüm kontrollerden geçildiyse kullanıcı kaydı yapılır
                } else if (intval($responseKeys["success"]) !== 1) {
                    echo alertdanger('Lütfen reCAPTCHA doğrulamasını yapınız.');

                    //Email ile daha önce kayıt olunmuşsa hata mesajı verilir
                } else if ($User->isEmailExists($email)) {
                    echo alertdanger('Bu email adresi ile daha önce kayıt olunmuş.');

                    //Tüm kontrollerden geçildiyse kullanıcı kaydı yapılır


                } else {


                    $data = [
                        'id' => 0,
                        'full_name' => Security::escape($_POST['full_name']),
                        'email' => Security::escape($_POST['email']),
                        'status' => 0,
                        'user_roles' => 1,
                        'is_main_user' => 1,
                        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    ];
                    try {
                        $db->beginTransaction();

                        //Kullanıcı kaydı yapılır
                        $lastInsertUserId = $User->saveWithAttr($data);

                        //Girdiği firma adı ile yeni bir firma kaydedilir
                        $data = [
                            'firm_name' => Security::escape($_POST['company_name']),
                            'user_id' => Security::decrypt($lastInsertUserId),
                        ];
                        $lastInsertFirmId = $company->saveMyFirms($data);

                        //Firmaya Admin isimli bir Kullanıcı grubu atanır
                        $data = [
                            "id" => 0,
                            "firm_id" => Security::decrypt($lastInsertFirmId),
                            "roleName" => 'Admin',
                            "main_role" => 1
                        ];
                        $lastInsertRoleId = $Roles->saveWithAttr($data);

                        //Kaydedilen Yetki grubuna tüm yetkiler atanır

                        //yetki tablosundaki süperadmin olmayan tüm id'ler alınır
                        $authsIds = $Auths->getNonSuperadminAuthIds();
                        //id'leri aralarında virgül olacak şekilde birleştirilir
                        $auths = implode(',', $authsIds);
                        //oluşturulan yetki grubuna yetkiler atanır
                        $data = [
                            "role_id" => Security::decrypt($lastInsertRoleId),
                            "auth_ids" => $auths
                        ];
                        $RoleAuths->saveWithAttr($data);


                        //kaydedilen firma ve role kullanıcıya atanır
                        $data = [
                            "id" => Security::decrypt($lastInsertUserId),
                            'firm_id' => Security::decrypt($lastInsertFirmId),
                            'user_roles' => Security::decrypt($lastInsertRoleId)
                        ];
                        //Kullanıcı GÜncellenir
                        $User->saveWithAttr($data);

                        // 15 Günlük Deneme Paketi tanımla
                        require_once 'Model/AbonelikPaketleriModel.php';
                        require_once 'Model/KullaniciAbonelikleriModel.php';

                        $AbonelikPaketleri = new AbonelikPaketleriModel();
                        $KullaniciAbonelikleri = new KullaniciAbonelikleriModel();

                        $allPkgs = $AbonelikPaketleri->getPackages();
                        $trialPkg = null;
                        foreach ($allPkgs as $p) {
                            if ($p->sure == 15 && $p->fiyat <= 0) {
                                $trialPkg = $p;
                                break;
                            }
                        }

                        $trialPkgId = $trialPkg ? $trialPkg->id : 7;
                        $trialFirmaHakki = $trialPkg ? $trialPkg->firma_hakki : 1;
                        $trialKullaniciHakki = $trialPkg ? $trialPkg->alt_kullanici_hakki : 10;

                        $subData = [
                            "kullanici_id" => Security::decrypt($lastInsertUserId),
                            "paket_id" => $trialPkgId,
                            "baslangic_tarihi" => date('Y-m-d'),
                            "bitis_tarihi" => date('Y-m-d', strtotime('+15 days')),
                            "durum" => "aktif",
                            "firma_hakki" => $trialFirmaHakki,
                            "alt_kullanici_hakki" => $trialKullaniciHakki,
                            "aciklama" => "Sisteme Kayıt - 15 Günlük Deneme Paketi",
                            "bildirim_goruldu" => 0,
                            "user_bildirim_goruldu" => 0
                        ];
                        $KullaniciAbonelikleri->saveWithAttr($subData);

                        //Varsayılan Nakit Kasa eklenir
                        $cases = new Cases();
                        $data = [
                            "account_id" => Security::decrypt($lastInsertUserId),
                            "firm_id" => Security::decrypt($lastInsertFirmId),
                            "start_budget" => 0.00,
                            "case_name" => 'TL KASASI',
                            "bank_name" => 'Nakit',
                            "case_money_unit" => 1,
                            "isDefault" => 1,
                            "created_at" => date('Y-m-d H:i:s')
                        ];
                        $cases->saveWithAttr($data);

                        //Kayıttan sonra kullanıcıya mail gönderilir

                        //Şuan ki zamanı token olarak oluştur
                        $token = (Security::encrypt(time() + 3600));

                        // $token = urlencode(bin2hex(random_bytes(32)));
                        $activate_link = "http://puantor.com.tr/register-activate.php?email=".($email)."&token=" . $token;

                        // Token ve e-posta adresini veritabanına kaydetme
                        $data = [
                            'id' => Security::decrypt($lastInsertUserId),
                            'activate_token' => ($token),
                        ];
                        $User->setActivateToken($data);

                        //**********EPOSTA GÖNDERME ALANI */
                        // mail şablonunu dahil etme

                        ob_start();
                        include 'register-success-email.php';
                        $content = ob_get_clean();


                        try {
                            //mail sınıfı ve ayarlarını dahil etme
                            require_once "mail-settings.php";

                            // Alıcılar
                            $mail->setFrom('bilgi@puantor.com.tr', 'Puantor');
                            $mail->addAddress($email);
                            $mail->isHTML(true);

                            // E-posta konusu ve içeriği
                            $mail->Subject = 'Aktivasyon Bağlantısı';
                            $mail->Body = $content;
                            $mail->AltBody = strip_tags($content);
                            //Karakter seti
                            $mail->CharSet = 'UTF-8';

                            // PNG dosyasını e-postaya ekleyin
                            $mail->AddEmbeddedImage('static/png/activation.png', 'activation');

                            $mail->send();
                            echo alertdanger('Aktivasyon bağlantısı e-posta adresinize gönderildi.', "info", "Başarılı!");
                        } catch (Exception $e) {
                            error_log($e->getMessage());
                            echo alertdanger('E-posta gönderilemedi, lütfen daha sonra tekrar deneyiniz.');
                        }
                        //**********EPOSTA GÖNDERME ALANI */

                        $db->commit();
                        header('Location: register-success.php');
                    } catch (PDOException $exh) {
                        if ($exh->errorInfo[1] == 1062) {
                            $db->rollBack();
                            echo alertdanger('Bu email adresi ile daha önce kayıt olunmuş.');
                        }
                    }
                }
            }



            ?>
            <form action="#" method="post" autocomplete="off" novalidate="">
                <input type="hidden" name="action" class="form-control" value="saveUser">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" autocomplete="on" name="full_name" id="floatingFullName"
                        value="<?php echo htmlspecialchars($full_name ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Adınız Soyadınız" required>
                    <label for="floatingFullName">Ad Soyad</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" name="company_name" class="form-control" id="floatingCompanyName"
                        value="<?php echo htmlspecialchars($company_name ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Firma adını giriniz!" required>
                    <label for="floatingCompanyName">Firma Adı</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="floatingEmail"
                        value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Email adresinizi giriniz!" required>
                    <label for="floatingEmail">Email Adresi</label>
                </div>
                <div class="form-floating mb-3 position-relative">
                    <input type="password" id="floatingPassword" name="password" class="form-control"
                        value="<?php echo htmlspecialchars($password ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="••••••••" autocomplete="off" required>
                    <label for="floatingPassword">Şifre</label>
                    <span class="input-icon-addon-right" id="togglePasswordBtn">
                        <i class="ti ti-eye" id="togglePasswordIcon"></i>
                    </span>
                </div>
                <div class="mb-3">
                    <label class="form-check" style="cursor: pointer;">
                        <?php
                        //Eğer post ile gelen terms_of_service varsa checked yap
                        if (isset($_POST['terms_of_service'])) {
                            $checked = 'checked';
                        } else {
                            $checked = '';
                        }
                        ?>
                        <input type="checkbox" name="terms_of_service" class="form-check-input" style="cursor: pointer;" <?php echo $checked; ?>>
                        <span class="form-check-label text-xs text-secondary" style="font-weight: 500;">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modal-scrollable" tabindex="-1"
                                class="text-primary text-decoration-none hover-underline" style="font-weight:600;">Üyelik Sözleşmesi ve Kişisel
                                Verilerin İşlenmesine İlişkin Aydınlatma ve Rıza Metni</a>'ni' okudum ve kabul
                            ediyorum.
                        </span>
                    </label>
                </div>
                <?php if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1' && $_SERVER['HTTP_HOST'] !== 'localhost'): ?>
                <div class="g-recaptcha mb-3 d-flex justify-content-center" data-sitekey="6LfHuWYqAAAAAMPWjmbVJVLDRi7_IAeY0of0REAk"
                    data-callback="enableSubmitButton"></div>
                <?php endif; ?>
                <button type="submit" id="submitButton" disabled="disabled" class="btn btn-primary w-100 mb-3">Hesap
                    Oluştur</button>
            </form>
        </div>

        <div class="text-center text-secondary text-xs">
            Zaten hesabınız var mı? <a href="./sign-in.php" class="text-primary text-decoration-none hover-underline" style="font-weight: 600;" tabindex="-1">Giriş Yap</a>
        </div>
    </div>

    <!-- Üyelik Sözleşmesi Modal -->
    <div class="modal modal-blur fade" id="modal-scrollable" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hizmet Sözleşmesi ve KVKK Aydınlatma Metni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h2>HİZMET SÖZLEŞMESİ</h2>
                    <p style="color:#888;font-size:.85rem;">Yürürlük: <?php echo date('d.m.Y'); ?> &nbsp;|&nbsp; Tam metin için: <a href="./kullanici-sozlesmesi.php" target="_blank">kullanici-sozlesmesi.php</a></p>

                    <h3>1. Taraflar</h3>
                    <p>İşbu Sözleşme, <strong>PUANTOR</strong> (www.puantor.com.tr — puantaj, maaş ve personel yönetimi yazılımı hizmet sağlayıcısı) ile platforma kayıt olan <strong>Kullanıcı</strong> arasında akdedilmiştir. Platforma kayıt olmak bu sözleşmenin tüm koşullarını kabul etmek anlamına gelir.</p>

                    <h3>2. Hizmetin Kapsamı</h3>
                    <p>PUANTOR; çalışan puantaj takibi, maaş bordrosu hesaplama, personel yönetimi, proje/görev takibi, avans talepleri ve raporlama modüllerini bulut tabanlı SaaS (yazılım hizmeti) olarak sunar. İlk kayıtta <strong>15 günlük ücretsiz deneme</strong> hakkı tanınır; sonrasında ücretli abonelik planlarından biri seçilmelidir.</p>

                    <h3>3. Kullanıcı Yükümlülükleri</h3>
                    <ul>
                        <li>Kayıt bilgileri doğru, güncel ve eksiksiz olmalıdır; Kullanıcı 18 yaşını doldurmuş olmalıdır.</li>
                        <li>Kullanıcı adı ve şifre gizli tutulmalı, üçüncü kişilerle paylaşılmamalıdır.</li>
                        <li>Platform yürürlükteki Türk mevzuatına uygun biçimde kullanılmalıdır.</li>
                        <li>Çalışan kişisel verilerinin (maaş, TC kimlik no vb.) işlenmesinde KVKK'ya uyum münhasıran Kullanıcı'nın sorumluluğundadır.</li>
                        <li>Platforma zarar verebilecek kötü amaçlı yazılım, bot veya yetkisiz erişim araçları kullanılamaz.</li>
                        <li>Platform yazılımı kopyalanamaz, tersine mühendislikle çözülemez, başkasına devredilemez.</li>
                    </ul>

                    <h3>4. Abonelik ve Ödeme</h3>
                    <ul>
                        <li>Abonelikler seçilen paket süresiyle sınırlıdır; otomatik yenilenmez.</li>
                        <li>Fiyatlar www.puantor.com.tr adresinde TL cinsinden ilan edilir.</li>
                        <li>İptal halinde mevcut abonelik dönemi sonunda erişim sona erer; kalan süre iade edilmez.</li>
                    </ul>

                    <h3>5. PUANTOR'un Sorumlulukları</h3>
                    <ul>
                        <li>Platform kesintisiz çalışması için makul teknik önlemler alınır.</li>
                        <li>Kullanıcı verileri düzenli yedeklenir; ancak Kullanıcı da kritik verilerin yedeğini almalıdır.</li>
                        <li>PUANTOR; bordro hesaplamalarının yasal uygunluğu konusunda sorumluluk taşımaz — platform bir araçtır, nihai doğrulama yükümlülüğü Kullanıcı'ya aittir.</li>
                        <li>Sözleşme ihlali veya ödeme yapılmaması halinde PUANTOR hesabı askıya alabilir.</li>
                    </ul>

                    <h3>6. Fikri Mülkiyet</h3>
                    <p>Platform yazılımı, tasarımı ve markaları PUANTOR'a aittir. Kullanıcı, yüklediği verilerin sahibidir; PUANTOR bu verileri yalnızca hizmet sunumu amacıyla kullanır.</p>

                    <h3>7. Fesih ve Veri Silme</h3>
                    <p>Kullanıcı hesabını istediği zaman kapatabilir. Hesap kapanmasından itibaren <strong>30 gün</strong> içinde veriler kalıcı olarak silinir; bu süre içinde veri ihracı talep edilebilir. PUANTOR, sözleşme ihlali durumunda hesabı önceden bildirimde bulunmaksızın kapatabilir.</p>

                    <h3>8. Uygulanacak Hukuk</h3>
                    <p>Bu Sözleşme Türkiye Cumhuriyeti hukukuna tabidir. Uyuşmazlıklarda <strong>İstanbul Mahkemeleri ve İcra Daireleri</strong> yetkilidir.</p>

                    <hr>
                    <h2>KİŞİSEL VERİLERİN KORUNMASI — KVKK AYDINLATMA METNİ</h2>

                    <h3>Veri Sorumlusu</h3>
                    <p>PUANTOR, 6698 sayılı KVKK uyarınca veri sorumlusudur. İletişim: <a href="mailto:info@puantor.com.tr">info@puantor.com.tr</a></p>

                    <h3>İşlenen Veriler ve Amaçları</h3>
                    <ul>
                        <li><strong>Ad, soyad, e-posta, telefon:</strong> Üyelik ve kimlik doğrulama — sözleşmenin ifası.</li>
                        <li><strong>Firma bilgileri:</strong> Hesap yönetimi ve faturalama — sözleşmenin ifası.</li>
                        <li><strong>Ödeme/fatura bilgileri:</strong> Yasal yükümlülüklerin yerine getirilmesi.</li>
                        <li><strong>IP adresi ve giriş logları:</strong> Güvenlik ve yetkisiz erişim tespiti — meşru menfaat.</li>
                        <li><strong>Kullanım verileri:</strong> Hizmet kalitesinin iyileştirilmesi — meşru menfaat.</li>
                    </ul>

                    <h3>Verileriniz Üzerindeki Haklarınız (KVKK md. 11)</h3>
                    <ul>
                        <li>Verilerinizin işlenip işlenmediğini öğrenme,</li>
                        <li>İşlenen veriler hakkında bilgi ve açıklama talep etme,</li>
                        <li>Yanlış/eksik verilerin düzeltilmesini ve silinmesini isteme,</li>
                        <li>Otomatik sistemler aracılığıyla aleyhinize sonuç doğuran işlemlere itiraz etme,</li>
                        <li>Kanuna aykırı işleme nedeniyle tazminat talep etme.</li>
                    </ul>
                    <p>Taleplerinizi <a href="mailto:info@puantor.com.tr">info@puantor.com.tr</a> adresine gönderin; PUANTOR en geç <strong>30 gün</strong> içinde yanıt verir.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Okudum</button>
                </div>
            </div>
        </div>
    </div>

    <script src="./dist/js/tabler.min.js?1692870487" defer></script>
    <script src="./dist/js/demo.min.js?1692870487" defer></script>
    <script src="./dist/js/jquery.3.7.1.min.js"></script>
    <script>

        //inpuları isimlendir
        let full_name = $('input[name="full_name"]');
        let company_name = $('input[name="company_name"]');
        let email = $('input[name="email"]');
        let password = $('input[name="password"]');
        let terms_of_service = $('input[name="terms_of_service"]');
        let submitButton = $('#submitButton');

        //formda tüm alanlar doldurulduğunda buton aktif edilir
        $('input').on('input', function () {
            enableSubmitButton();
        });
        function enableSubmitButton() {
            let isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
            let recaptchaOk = isLocal ? true : (typeof grecaptcha !== 'undefined' && grecaptcha.getResponse());

            //tüm alanlar doldurulduysa ve recaptcha doğrulandıysa buton aktif edilir
            if (full_name.val() && company_name.val() && email.val() && password.val() && terms_of_service.is(':checked') && recaptchaOk) {
                submitButton.removeAttr('disabled');
            } else {
                submitButton.attr('disabled', 'disabled');
            }
        }
    </script>
    <script>
        //Şifre gösterme
        $(document).ready(function () {
            const toggleBtn = document.getElementById('togglePasswordBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const passwordInput = document.getElementById('floatingPassword');
                    const icon = document.getElementById('togglePasswordIcon');
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('ti-eye');
                        icon.classList.add('ti-eye-off');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('ti-eye-off');
                        icon.classList.add('ti-eye');
                    }
                });
            }
        });

    </script>

</body>

</html>
