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
                    <img src="./static/logo-ai.svg" height="120" alt=""></a>
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
                
                            //yetki tablosundaki tüm id'ler alınır
                            $auths = $Auths->all();
                            //id'leri aralarında virgül olacak şekilde birleştirilir
                            $auths = implode(',', array_column($auths, 'id'));
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
                    <h5 class="modal-title">Üyelik sözleşmesi ve KVK'ya ilişkin aydınlatma metni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h2>ÜYELİK SÖZLEŞMESİ</h2>

                    <h3>1. Taraflar</h3>
                    <p>İşbu Sözleşme, www.puantor.com.tr internet sitesinin faaliyetlerini yürüten [puantor.com.tr]
                        (Bundan
                        böyle “PUANTOR” olarak anılacaktır) ve www.puantor.com.tr internet sitesine üye olan internet
                        kullanıcısı ("Üye") arasında akdedilmiştir.</p>

                    <h3>2. Sözleşmenin Konusu</h3>
                    <p>İşbu Sözleşme’nin konusu, Üyenin www.puantor.com.tr internet sitesinden faydalanma şartlarının
                        belirlenmesidir.</p>

                    <h3>3. Tarafların Hak ve Yükümlülükleri</h3>
                    <ol>
                        <li>Üyelik statüsünün kazanılması için, Üye olmak isteyen kullanıcının, web sitesinde bulunan
                            işbu Üyelik Sözleşmesi'ni onaylayarak, burada talep edilen bilgileri doğru ve güncel
                            bilgilerle doldurması gerekmektedir. Üye olmak isteyen kullanıcının 18 (on sekiz) yaşını
                            doldurmuş olması aranacaktır.</li>
                        <li>Üye, verdiği kişisel bilgilerin doğru olduğunu, PUANTOR’un bu bilgilerin gerçeğe aykırılığı
                            nedeniyle uğrayacağı zararları tazmin edeceğini beyan eder.</li>
                        <li>Üye, kendisine verilen şifreyi başka kişilerle paylaşmamayı taahhüt eder. Şifre
                            kullanımından kaynaklanan sorumluluk tamamen üyeye aittir.</li>
                        <li>Üye, siteyi yasal mevzuata uygun olarak kullanmayı ve başkalarını rahatsız edici
                            davranışlardan kaçınmayı kabul eder.</li>
                        <li>PUANTOR, üye verilerinin güvenliği için gerekli önlemleri alır, ancak üyenin bu verilerin
                            korunması konusunda da dikkatli olmasını bekler.</li>
                        <li>Üye, diğer kullanıcıların verilerine izinsiz ulaşmamayı ve bu verileri kullanmamayı kabul
                            eder.</li>
                        <li>Üyelik sözleşmesinin ihlali durumunda PUANTOR, üyenin üyeliğini iptal etme hakkına sahiptir.
                        </li>
                        <li>PUANTOR, her zaman tek taraflı olarak üyelikleri sonlandırma hakkını saklı tutar.</li>
                        <li>www.puantor.com.tr internet sitesi yazılım ve tasarımı PUANTOR’a aittir. Bu içeriklerin
                            izinsiz kullanımı yasaktır.</li>
                        <li>Üye, web sitesi üzerinde herhangi bir otomatik program veya sistem kullanmamayı taahhüt
                            eder.</li>
                    </ol>

                    <h3>4. Sözleşmenin Feshi</h3>
                    <p>Üye, üyeliğini iptal edebilir. PUANTOR, üyenin sözleşme hükümlerini ihlal etmesi durumunda
                        üyeliği iptal edebilir. Üyelik iptal edildikten sonra, üyenin bilgileri 15 takvim günü
                        içerisinde silinecektir.</p>

                    <h3>5. İhtilafların Halli</h3>
                    <p>İhtilaf durumunda TC Mahkemeleri ve İcra Daireleri yetkilidir.</p>

                    <h3>6. Yürürlük</h3>
                    <p>Üyenin, üyelik kaydı yapması, sözleşme şartlarını kabul ettiği anlamına gelir. İşbu Sözleşme,
                        üyenin üye olması anında yürürlüğe girmiştir.</p>

                    <h2>KİŞİSEL VERİLERİN İŞLENMESİNE İLİŞKİN AYDINLATMA VE RIZA METNİ</h2>

                    <h3>1. Aydınlatma Metninin Amacı ve PUANTOR’un Veri Sorumlusu Konumu:</h3>
                    <p>PUANTOR, kişisel verilerin korunmasına ilişkin yükümlülüklerini yerine getirmek amacıyla
                        aşağıdaki açıklamaları sunar. Bu metin, güncellemeler doğrultusunda değiştirilebilir.</p>

                    <h3>2. Kişisel Verilerin İşlenme Amacı:</h3>
                    <p>Kişisel verileriniz, aşağıdaki amaçlarla işlenmektedir:</p>
                    <ul>
                        <li>Kimlik bilgilerinizi teyit etmek,</li>
                        <li>İletişim bilgilerini kaydetmek,</li>
                        <li>Üyelerle iletişime geçmek ve gerekli bilgilendirmeleri yapmak,</li>
                        <li>Yasal yükümlülükleri yerine getirmek.</li>
                    </ul>

                    <h3>3. Kişisel Verilerin Toplanma Yöntemi:</h3>
                    <p>Kişisel verileriniz, web sitemiz üzerinden rızanız ile toplanmakta ve yukarıda belirtilen
                        amaçlarla işlenmektedir.</p>

                    <h3>4. Kişisel Veri Sahibi Olarak Haklarınız:</h3>
                    <p>KVKK’nın 11. maddesi uyarınca, kişisel veri sahipleri:</p>
                    <ul>
                        <li>Kişisel verilerin işlenip işlenmediğini öğrenme,</li>
                        <li>İşlenen veriler hakkında bilgi talep etme,</li>
                        <li>Yanlış veya eksik verilerin düzeltilmesini isteme,</li>
                        <li>Verilerin silinmesini isteme,</li>
                        <li>Yasal yollara başvurma hakkına sahiptir.</li>
                    </ul>

                    <p>Taleplerinizi <a href="mailto:info@puantor.com.tr">info@puantor.com.tr</a> adresine
                        iletebilirsiniz. PUANTOR, taleplerinizi 30 gün içinde değerlendirecektir.</p>
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