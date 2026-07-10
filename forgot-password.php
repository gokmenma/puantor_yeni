<?php
define("ROOT", $_SERVER["DOCUMENT_ROOT"]);
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/UserModel.php';
require_once ROOT . '/Model/PasswordModel.php';



$PasswordModel = new PasswordModel();
$Users = new UserModel();




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
  <title>Şifre Sıfırlama || Puantor | Puantaj Kayıt Programı.</title>

  <!-- CSS files -->
  <link href="./dist/css/tabler.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/tabler-flags.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/tabler-payments.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/tabler-vendors.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/demo.min.css?1726507346" rel="stylesheet">
  <link href="./dist/css/tabler-icons.min.css?1726507346" rel="stylesheet">
  <link rel="icon" href="./static/favicon.ico" type="image/x-icon" />
  <link href="./dist/css/style.css" rel="stylesheet" />
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
      max-width: 420px;
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
  <script src="./dist/js/demo-theme.min.js?1726507346"></script>

  <div class="bg-blob bg-blob-primary"></div>
  <div class="bg-blob bg-blob-indigo"></div>

  <div class="login-container">
    <div class="text-center mb-1">
      <div class="mb-3 d-flex justify-content-center align-items-center" style="height: 80px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.03));">
        <img src="./static/Logo-ai.svg" style="height: 100px; width: auto; max-width: 100%;" alt="Puantor Logo">
      </div>
      <p class="login-subtitle">Şifre sıfırlama bağlantısı gönderelim</p>
    </div>

    <div class="login-middle-form">
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
            error_log($e->getMessage());
            echo alertdanger('E-posta gönderilemedi, lütfen daha sonra tekrar deneyiniz.');
          }

        }
      }


      ?>
      <form action="forgot-password.php" method="post" autocomplete="off" novalidate="">
        <p class="login-subtitle mb-3">Email adresini girin. Şifre sıfırlama bağlantısı mail adresinize gönderilecektir.</p>
        <div class="form-floating mb-3">
          <input type="email" name="email" class="form-control" id="floatingEmail" value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Email adresiniz" required>
          <label for="floatingEmail">Email Adresi</label>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3">
          <i class="ti ti-mail me-1"></i>
          Şifremi Gönder
        </button>
      </form>
    </div>

    <div class="text-center text-secondary text-xs">
      Neyse! Beni <a href="./sign-in.php" class="text-primary text-decoration-none hover-underline" style="font-weight: 600;">giriş</a> ekranına gönder.
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
