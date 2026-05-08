<?php
define("ROOT", $_SERVER["DOCUMENT_ROOT"]);
require_once 'configs/require.php';
require_once 'Model/UserModel.php';
require_once 'App/Helper/security.php';
require_once 'Model/SettingsModel.php';
require_once 'App/Helper/date.php';
require_once 'Model/LoginLogsModel.php';

$Settings = new SettingsModel();

$User = new UserModel();

use App\Helper\Date;
use App\Helper\Security;
// if ($_POST && isset($_POST['submitForm'])) {
//   $email = $_POST['email'];
//   $password = MD5($_POST['password']);
//   echo $password;
// };

?>



<!doctype html>

<html lang="tr">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Oturum Aç | Puantor - Puantaj Takip Sistemi</title>
  <!-- CSS files -->
  <link href="./dist/css/tabler.min.css?1692870487" rel="stylesheet" />
  <link href="./dist/css/demo.min.css?1692870487" rel="stylesheet" />
  <link href="./dist/css/style.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
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


<script>
  setTimeout(function () {
    $('.alert-danger').each(function () {
      $(this).fadeOut(500, function () {
        $(this).remove();
      });
    });
  }, 10000);
</script>

<body class=" d-flex flex-column">
  <script src="./dist/js/demo-theme.min.js?1692870487"></script>
  <div class="page page-center">
    <div class="container container-normal py-4">
      <div class="row align-items-center g-4">
        <div class="col-lg">
          <div class="container-tight">
            <div class="text-center mb-4">
              <a href="." class="navbar-brand navbar-brand-autodark">
                <img src="./static/logo-ai.svg" height="120" alt=""></a>
            </div>
            <?php
            if ($_POST && isset($_POST['submitForm'])) {
              $email = $_POST['email'];
              $password = $_POST['password'];


              // Email adresi boş ise
              if (empty($email)) {
                echo alertdanger('Email adresi boş bırakılamaz');
              } elseif (empty($password)) {
                echo alertdanger('Şifre boş bırakılamaz');
              } else {
                $user = $User->getUserByEmail($email);
                // Kullanıcı bulunamadıysa
                if (!$user) {
                  echo alertdanger('Kullanıcı bulunamadı');
                  // Kullanıcı aktif değilse
                } else if (isset($user) && $user->status == 0) {
                  echo alertdanger('Hesabınız henüz aktif değil');
                } else {
                  $verified = password_verify($password, $user->password);
                  $demo_date = $user->created_at;

                  if ($verified) {


                    // Kullanıcının hesap açma tarininden itibaren 15 gün geçmişse giriş yapmasına izin verme
                    $days = Date::getDateDiff($demo_date);
                    if ($days >= 15 && $user->user_type == 1) {
                      echo alertdanger('Deneme süreniz dolmuştur. Lütfen iletişime geçiniz.');
                    } else {

                      $_SESSION['user'] = $user;
                      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                      $_SESSION['full_name'] = $user->full_name;
                      $_SESSION['user_role'] = $user->user_roles;
                      $User->setToken($user->id, $_SESSION['csrf_token']);

                      //Giriş işlemleri kayıt altına alınıyor
                      $_SESSION["log_id"] = $User->loginLog($user->id);

                      // giriş bilgileri panel kayıt ediiliyor
                      $LoginLogs = new LoginLogsModel();
                      //$logs = $LoginLogs->panelLoginLog($user);



                      //Eğer ayarlarda mail gönderme seçeneği açıksa
                      $send_email_on_login = $Settings->getSettingIdByUserAndAction($user->id, "loginde_mail_gonder")->set_value ?? 0;
                      if ($send_email_on_login == 1) {

                        //mail gönderilecek kullanıcının mail adresini al
                        $email = $user->parent_id == 0 ? $user->email : $User->find($user->id);

                        //Kullanıcıya email gönder
                        try {

                          require_once "mail-settings.php";

                          $body = 'Merhaba ' . $user->full_name . ',<br><br>

                                Bu e-mail, hesabınıza giriş yapıldığını bildirmek amacıyla gönderilmiştir. 
                                Kayıtlı mail adresiniz ile www.puantor.com.tr müşteri hesabınıza giriş yapılmıştır. <br><br>

                                Giriş Zamanı: ' . date("Y-m-d H:i:s") . '<br>
                                Giriş yapan IP Adresi: ' . $_SERVER['REMOTE_ADDR'] . '<br>
                                Giriş yapan Kullanıcı: ' . $email . '<br>

                                Eğer bu işlem bilginiz dışındaysa, lütfen en kısa sürede bizimle iletişime geçiniz: 0507 943 27 23<br><br>

                                İyi Çalışmalar,<br><br>
                                www.puantor.com.tr';

                          // Alıcılar
                          $mail->setFrom('bilgi@puantor.com.tr', 'Puantor');
                          $mail->addAddress($email);
                          $mail->isHTML(true);

                          $mail->Subject = 'Hesabınıza giriş yapıldı';
                          $mail->Body = $body;
                          $mail->AltBody = strip_tags($body);
                          //Karakter seti
                          $mail->CharSet = 'UTF-8';

                          $mail->send();
                        } catch (Exception $e) {
                          echo "E-posta gönderilemedi. Hata: {$mail->ErrorInfo}";
                        }
                      }

                      // returnUrl parametresini kontrol edin ve varsayılan değeri ayarlayın
                      $returnUrl = isset($_GET['returnUrl']) && !empty($_GET['returnUrl']) ? urlencode($_GET['returnUrl']) : '';
                      header("Location: company-list.php?returnUrl={$returnUrl}");

                      exit();
                    }

                  } else {
                    echo alertdanger('Hatalı şifre veya email adresi');
                  }
                }
              }
            }
            ?>
            <div class="card card-md">
              <div class="card-body">
                <h2 class="h2 text-center mb-4">Oturum Aç</h2>
                <form method="POST" action="#" autocomplete="off">

                  <div class="mb-3">
                    <label class="form-label">Email Adresi</label>
                    <input type="email" class="form-control" name="email" value="<?php echo $email ?? '' ?>"
                      placeholder="Email adresinizi girin" autocomplete="off">
                  </div>
                  <div class="mb-2">
                    <label class="form-label">
                      Şifre
                      <span class="form-label-description">
                        <a href="./forgot-password.php">Şifremi Unuttum</a>
                      </span>
                    </label>
                    <div class="input-group input-group-flat">
                      <input type="password" class="form-control" name="password" placeholder="Şifrenizi giriniz"
                        autocomplete="off">
                      <span class="input-group-text">
                        <a href="#" class="link-secondary show-pass" data-bs-toggle="tooltip">
                          <!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                          <i class="ti ti-eye icon ms-2"></i>
                        </a>
                      </span>
                    </div>
                  </div>
                  <div class="mb-2">
                    <label class="form-check">
                      <input type="checkbox" class="form-check-input" />
                      <span class="form-check-label">Beni Hatırla</span>
                    </label>
                  </div>
                  <div class="form-footer">
                    <button type="submit" name="submitForm" class="btn btn-primary w-100">Giriş
                      Yap</button>
                  </div>
                </form>
              </div>


            </div>
            <div class="text-center text-secondary mt-3">
              Henüz hesabınız yok mu? <a href="./register.php" tabindex="-1">Kayıt Ol</a>
            </div>
          </div>
        </div>
        <!-- <div class="col-lg d-none d-lg-block">
          
          <img src="./static/illustrations/undraw_sign_in_e6hj.svg" height="300" class="d-block mx-auto" alt="">
        </div> -->
      </div>
    </div>
  </div>
  <!-- Libs JS -->
  <!-- Tabler Core -->
  <script src="./dist/js/tabler.min.js?1692870487" defer></script>
  <script src="./dist/js/demo.min.js?1692870487" defer></script>
  <script src="./dist/js/jquery.3.7.1.min.js"></script>

  <script>
    $(document).on('click', '.show-pass', function () {
      var input = $(this).closest('.input-group').find('input');
      var icon = $(this).find('i');
      var placeholder = $(this).attr('title');
      if (input.attr('type') == 'password') {
        input.attr('type', 'text');
        icon.removeClass('ti-eye').addClass('ti-eye-off');
      } else {
        input.attr('type', 'password');
        icon.removeClass('ti-eye-off').addClass('ti-eye');

      }

    });
  </script>
</body>

</html>