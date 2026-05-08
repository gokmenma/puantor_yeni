<?php
require_once "Database/require.php";
require_once "Model/UserModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;
$User = new UserModel();


function alertdanger($message, $type = "danger", $title = "Hata!")
{
    echo '<div class="alert alert-' . $type . ' bg-white text-start font-weight-600" role="alert">
            <div class="d-flex">
                <div>
                    
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                </div>
                    <div>
                        <h4 class="alert-title">' . $title . '</h4>
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
    <title>Aktivasyon | Puantor - Puantaj Takip Sistemi</title>

    <!-- CSS files -->
    <link href="./dist/css/tabler.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/tabler-flags.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/tabler-payments.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/tabler-vendors.min.css?1726507346" rel="stylesheet">
    <link href="./dist/css/demo.min.css?1726507346" rel="stylesheet">
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
    <script src="./dist/js/demo-theme.min.js?1726507346"></script>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="." class="navbar-brand navbar-brand-autodark">
                    <img src="./static/logo-ai.svg" height="120" alt=""></a>
                </a>
            </div>
            <div class="text-center">
                <div class="my-5">
                    <?php
                    $token_renegate = false;
                    if (isset($_POST["action"]) && $_POST["action"] == 'token_renegate') {
                        $email = $_POST["email"];
                        $user = $User->checkToken($email);
                        if (empty($user)) {
                            echo alertdanger("Kullanıcı Bulunamadı");
                        } else {
                            $token = (Security::encrypt(time() + 3600));
                            
                            $data = [
                                'id' => $user->id,
                                'activate_token' => $token,
                                'status' => 0
                            ];

                            $User->setActivateToken($data);
                            //Tekrar mail gönder
                    
                            $activate_link = "http://puantor.com.tr/register-activate.php?email=" . ($email) . "&token=" . $token;


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
                    

                            // echo alertdanger("Yeni Token Oluşturuldu ve Mail Gönderildi", "success", "Başarılı!");
                        }
                    } else {
                        $token = $_GET['token'];
                        $email = ($_GET['email']);
                        $user = $User->checkToken($email);
                        $token = (Security::decrypt($token));

                        if (empty($user)) {
                            echo alertdanger("Kullanıcı Bulunamadı");
                        } elseif ($token < time() || $user->activate_token != urlencode($_GET['token'])) {
                            echo alertdanger("Geçersiz Token!");
                            $token_renegate = true;
                            //Token boş ise mesaj ver
                        } elseif (empty($token)) {
                            echo alertdanger("Token bilgisi boş");
                        } elseif (empty($email)) {
                            echo alertdanger("Email bilgisi boş");
                        } elseif ($user->status == 1) {
                            echo alertdanger("Kullanıcı zaten aktif");
                        } else {
                            $User->ActivateUser($email);
                            echo alertdanger("Hesabınız başarı ile aktifleştirildi!", "success", "Başarılı!");
                        }
                    }

                    ?>
                </div>

                <div class="align-center">
                    <?php if ($token_renegate == true) { ?>
                        <form action="register-activate.php" method="post">
                            <input type="hidden" name="email" value="<?php echo $email; ?>">
                            <input type="hidden" name="action" value="token_renegate">
                            <button type="submit" class="btn btn-primary w-100">
                                Tekrar Token Oluştur
                            </button>
                        </form>
                    <?php } else {
                        echo '<a href="sign-in.php" class="btn btn-primary w-100">
                                 Giriş Sayfasına Git
                              </a>';
                    } ?>

                </div>

            </div>
        </div>
    </div>
    <!-- Libs JS -->
    <!-- Tabler Core -->
    <script src="./dist/js/tabler.min.js?1726507346" defer=""></script>
    <script src="./dist/js/demo.min.js?1726507346" defer=""></script>

</body>

</html>