<?php
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

function alertdanger($message)
{
    echo '<div class="alert alert-danger bg-white text-start font-weight-600" role="alert">
            <div class="d-flex">
                <div>
                    
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                </div>
                    <div>
                        <h4 class="alert-title">Hata!</h4>
                    <div class="text-secondary">' . $message . '</div>
                </div>
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
    <?php if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1' && $_SERVER['HTTP_HOST'] !== 'localhost'): ?>
    <script src="https://www.google.com/recaptcha/api.js?hl=tr" async defer></script>
    <?php endif; ?>
    <link rel="icon" href="./static/favicon.ico" type="image/x-icon" />


    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
    </style>


</head>

<body class=" d-flex flex-column">
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
    <div class="page page-center register">
        <div class="container container-tight py-4">
            <div class="text-center mb-1">
                <a href="." class="navbar-brand navbar-brand-autodark">
                    <img src="./static/Logo-ai.svg" height="120" alt=""></a>
                </a>


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
                                echo "E-posta gönderilemedi. Hata: {$mail->ErrorInfo}";
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




            </div>
            <form class="card card-md" action="#" method="post" autocomplete="off" novalidate="">
                <input type="hidden" name="action" class="form-control" value="saveUser">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Yeni Hesap Oluştur</h2>
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" autocomplete="on" name="full_name"
                            value="<?php echo $full_name ?? '' ?>" placeholder="Adınız Soyadınız">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Firma Adı</label>
                        <input type="text" name="company_name" class="form-control"
                            value="<?php echo $company_name ?? '' ?>" placeholder="Firma adını giriniz!">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Adresi</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $email ?? '' ?>"
                            placeholder="Email adresinizi giriniz!">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <div class="input-group input-group-flat">
                            <input type="password" name="password" class="form-control"
                                value="<?php echo $password ?? '' ?>" placeholder="Password" autocomplete="off">
                            <span class="input-group-text">
                                <a href="#" class="link-secondary" data-bs-toggle="tooltip" aria-label="Şifreyi göster"
                                    data-bs-original-title="Şifreyi göster">
                                    <!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="icon">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                                        <path
                                            d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6">
                                        </path>
                                    </svg>
                                </a>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <?php
                            //Eğer post ile gelen terms_of_service varsa checked yap
                            if (isset($_POST['terms_of_service'])) {
                                $checked = 'checked';
                            } else {
                                $checked = '';
                            }
                            ?>
                            <input type="checkbox" name="terms_of_service" class="form-check-input" <?php echo $checked; ?>>
                            <span class="form-check-label"><a href="#" data-bs-toggle="modal"
                                    data-bs-target="#modal-scrollable" tabindex="-1">Üyelik Sözleşmesi ve Kişisel
                                    Verilerin İşlenmesine İlişkin Aydınlatma ve Rıza Metni</a>'ni' okudum ve kabul
                                ediyorum.</span>
                        </label>
                    </div>
                    <?php if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1' && $_SERVER['HTTP_HOST'] !== 'localhost'): ?>
                    <div class="g-recaptcha" data-sitekey="6LfHuWYqAAAAAMPWjmbVJVLDRi7_IAeY0of0REAk"
                        data-callback="enableSubmitButton"></div>
                    <?php endif; ?>
                    <div class="form-footer">
                        <button type="submit" id="submitButton" disabled="disabled" class="btn btn-primary w-100">Hesap
                            Oluştur</button>
                    </div>
                </div>
            </form>
            <div class="text-center text-secondary mt-3">
                Zaten Hesabım var <a href="./sign-in.php" tabindex="-1">Giriş Yap</a>
            </div>
        </div>
    </div>
    <!-- Libs JS -->
    <!-- Tabler Core -->


    <!-- Üyelik Sözleşmesi Modal -->
    <div class="modal modal-blur fade" id="modal-scrollable" tabindex="-1" aria-hidden="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hizmet Sözleşmesi ve KVKK Aydınlatma Metni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class=”modal-body”>
                    <h2>HİZMET SÖZLEŞMESİ</h2>
                    <p style=”color:#888;font-size:.85rem;”>Yürürlük: <?php echo date(‘d.m.Y’); ?> &nbsp;|&nbsp; Tam metin için: <a href=”./kullanici-sozlesmesi.php” target=”_blank”>kullanici-sozlesmesi.php</a></p>

                    <h3>1. Taraflar</h3>
                    <p>İşbu Sözleşme, <strong>PUANTOR</strong> (www.puantor.com.tr — puantaj, maaş ve personel yönetimi yazılımı hizmet sağlayıcısı) ile platforma kayıt olan <strong>Kullanıcı</strong> arasında akdedilmiştir. Platforma kayıt olmak bu sözleşmenin tüm koşullarını kabul etmek anlamına gelir.</p>

                    <h3>2. Hizmetin Kapsamı</h3>
                    <p>PUANTOR; çalışan puantaj takibi, maaş bordrosu hesaplama, personel yönetimi, proje/görev takibi, avans talepleri ve raporlama modüllerini bulut tabanlı SaaS (yazılım hizmeti) olarak sunar. İlk kayıtta <strong>15 günlük ücretsiz deneme</strong> hakkı tanınır; sonrasında ücretli abonelik planlarından biri seçilmelidir.</p>

                    <h3>3. Kullanıcı Yükümlülükleri</h3>
                    <ul>
                        <li>Kayıt bilgileri doğru, güncel ve eksiksiz olmalıdır; Kullanıcı 18 yaşını doldurmuş olmalıdır.</li>
                        <li>Kullanıcı adı ve şifre gizli tutulmalı, üçüncü kişilerle paylaşılmamalıdır.</li>
                        <li>Platform yürürlükteki Türk mevzuatına uygun biçimde kullanılmalıdır.</li>
                        <li>Çalışan kişisel verilerinin (maaş, TC kimlik no vb.) işlenmesinde KVKK’ya uyum münhasıran Kullanıcı’nın sorumluluğundadır.</li>
                        <li>Platforma zarar verebilecek kötü amaçlı yazılım, bot veya yetkisiz erişim araçları kullanılamaz.</li>
                        <li>Platform yazılımı kopyalanamaz, tersine mühendislikle çözülemez, başkasına devredilemez.</li>
                    </ul>

                    <h3>4. Abonelik ve Ödeme</h3>
                    <ul>
                        <li>Abonelikler seçilen paket süresiyle sınırlıdır; otomatik yenilenmez.</li>
                        <li>Fiyatlar www.puantor.com.tr adresinde TL cinsinden ilan edilir.</li>
                        <li>İptal halinde mevcut abonelik dönemi sonunda erişim sona erer; kalan süre iade edilmez.</li>
                    </ul>

                    <h3>5. PUANTOR’un Sorumlulukları</h3>
                    <ul>
                        <li>Platform kesintisiz çalışması için makul teknik önlemler alınır.</li>
                        <li>Kullanıcı verileri düzenli yedeklenir; ancak Kullanıcı da kritik verilerin yedeğini almalıdır.</li>
                        <li>PUANTOR; bordro hesaplamalarının yasal uygunluğu konusunda sorumluluk taşımaz — platform bir araçtır, nihai doğrulama yükümlülüğü Kullanıcı’ya aittir.</li>
                        <li>Sözleşme ihlali veya ödeme yapılmaması halinde PUANTOR hesabı askıya alabilir.</li>
                    </ul>

                    <h3>6. Fikri Mülkiyet</h3>
                    <p>Platform yazılımı, tasarımı ve markaları PUANTOR’a aittir. Kullanıcı, yüklediği verilerin sahibidir; PUANTOR bu verileri yalnızca hizmet sunumu amacıyla kullanır.</p>

                    <h3>7. Fesih ve Veri Silme</h3>
                    <p>Kullanıcı hesabını istediği zaman kapatabilir. Hesap kapanmasından itibaren <strong>30 gün</strong> içinde veriler kalıcı olarak silinir; bu süre içinde veri ihracı talep edilebilir. PUANTOR, sözleşme ihlali durumunda hesabı önceden bildirimde bulunmaksızın kapatabilir.</p>

                    <h3>8. Uygulanacak Hukuk</h3>
                    <p>Bu Sözleşme Türkiye Cumhuriyeti hukukuna tabidir. Uyuşmazlıklarda <strong>İstanbul Mahkemeleri ve İcra Daireleri</strong> yetkilidir.</p>

                    <hr>
                    <h2>KİŞİSEL VERİLERİN KORUNMASI — KVKK AYDINLATMA METNİ</h2>

                    <h3>Veri Sorumlusu</h3>
                    <p>PUANTOR, 6698 sayılı KVKK uyarınca veri sorumlusudur. İletişim: <a href=”mailto:info@puantor.com.tr”>info@puantor.com.tr</a></p>

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
                    <p>Taleplerinizi <a href=”mailto:info@puantor.com.tr”>info@puantor.com.tr</a> adresine gönderin; PUANTOR en geç <strong>30 gün</strong> içinde yanıt verir.</p>
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
            $('[data-bs-toggle="tooltip"]').tooltip();
            $('.input-group-text').on('click', function () {
                let input = $(this).prev();
                if (input.attr('type') == 'password') {
                    input.attr('type', 'text');
                    //tooltip texti değiştir
                    $(this).find("a").attr('data-bs-original-title', 'Şifreyi gizle').attr('aria-label', 'Şifreyi gizle');
                } else {
                    input.attr('type', 'password');
                    //tooltip texti değiştir
                    $(this).find("a").attr('data-bs-original-title', 'Şifreyi göster').attr('aria-label', 'Şifreyi göster');
                }
            });
        });

    </script>

</body>

</html>