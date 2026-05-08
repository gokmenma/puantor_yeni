<?php
define("ROOT", $_SERVER["DOCUMENT_ROOT"]);
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/UserModel.php';
require_once ROOT . '/Model/PasswordModel.php';



$PasswordModel = new PasswordModel();
$Users = new UserModel();





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
  <title>Şifre Sıfırlama || Puantor | Puantaj Kayıt Programı.</title>

  <!-- CSS files -->
  <link href="./dist/css/tabler.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/tabler-flags.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/tabler-payments.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/tabler-vendors.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/demo.min.css?1726507346" rel="stylesheet">
  <link rel="icon" href="./static/favicon.ico" type="image/x-icon" />
  <link href="./dist/css/style.css" rel="stylesheet" />
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
      <div class="row align-items-center g-4">
        <div class="col-lg">
          <div class="container-tight">
            <div class="text-center mb-4">
              <a href="." class="navbar-brand navbar-brand-autodark">
                <img src="./static/logo-ai.svg" height="120" alt="">
              </a>
            </div>
            <?php

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
              $email = $_POST['email'];
              $user = $Users->getUserByEmail($email);
              // E-posta adresi kontrolü
              if (empty($email)) {
                echo alertdanger('Email adresi boş bırakılamaz');
              } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo alertdanger('Geçersiz e-posta adresi');

              } elseif (!$user) {
                echo alertdanger('Bu e-posta adresi ile kayıtlı bir hesap bulunamadı.');
              } else {

                //1 saat geçerli olan token oluşturma
                $token = bin2hex(random_bytes(32));
                $resetLink = "http://puantor.com.tr/reset-password.php?token=" . $token;

                // Token ve e-posta adresini veritabanına kaydetme
                $PasswordModel->setPasswordReset($email, $token);

                ob_start();
                include 'forgot-password-email.php';
                $content = ob_get_clean();


                try {

                  require_once "mail-settings.php";

                  // Alıcılar
                  $mail->setFrom('sifre@puantor.com.tr', 'Puantor');
                  $mail->addAddress($email);
                  $mail->isHTML(true);

                  $mail->Subject = 'Şifre Sıfırlama';
                  $mail->Body = $content;
                  $mail->AltBody = strip_tags($content);
                  //Karakter seti
                  $mail->CharSet = 'UTF-8';

                  // PNG dosyasını e-postaya ekleyin
                  $mail->AddEmbeddedImage('static/png/lock.png', 'lock-icon');

                  $mail->send();
                  echo alertdanger('Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.', "info", "Başarılı!");
                } catch (Exception $e) {
                  echo "E-posta gönderilemedi. Hata: {$mail->ErrorInfo}";
                }

              }
            }


            ?>
            <form class="card card-md" action="forgot-password.php" method="post" autocomplete="off" novalidate="">
              <div class="card-body">
                <h2 class="card-title text-center mb-4">Şifre Sıfırlama</h2>
                <p class="text-secondary mb-4">Email adresini girin. Şifre sıfırlama maile adresinize gönderilecektir.
                </p>
                <div class="mb-3">
                  <label class="form-label">Email Adresi</label>
                  <input type="email" name="email" class="form-control" value="<?php echo $email ?? ''; ?>"
                    placeholder="Email adresiniz">
                </div>
                <div class="form-footer">
                  <button type="submit" class="btn btn-primary w-100">
                    <!-- Download SVG icon from http://tabler-icons.io/i/mail -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                      class="icon">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                      <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"></path>
                      <path d="M3 7l9 6l9 -6"></path>
                    </svg>
                    Şifremi Gönder
                  </button>
                </div>
              </div>
            </form>
            <div class="text-center text-secondary mt-3">
              Neyse! Beni <a href="./sign-in.php">giriş </a> ekranına gönder.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Libs JS -->
  <!-- Tabler Core -->
  <script src="./dist/js/tabler.min.js?1726507346" defer=""></script>
  <script src="./dist/js/demo.min.js?1726507346" defer=""></script>
  <script src="./dist/js/jquery.3.7.1.min.js"></script>
  <script>
    setTimeout(function () {
      $('.alert-danger, .alert-info').each(function () {
        $(this).fadeOut(500, function () {
          $(this).remove();
        });
      });
    }, 8000);
  </script>

</body>

</html>