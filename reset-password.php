<?php
require_once "Database/require.php";
require_once "Model/UserModel.php";
require_once "Model/PasswordModel.php";

$PasswordModel = new PasswordModel();

$User = new UserModel();

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
  <link href="./dist/css/style.css?1726507346" rel="stylesheet">
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
      <p class="login-subtitle">Yeni şifrenizi belirleyin</p>
    </div>

    <div class="login-middle-form">
      <?php
      $token = $_GET['token'] ?? '';
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];
        $password_repeat = $_POST['password_repeat'];
        if (empty($password) || empty($password_repeat)) {
          alertdanger("Lütfen tüm alanları doldurun.");
        } elseif ($password != $password_repeat) {

          alertdanger("Şifreler uyuşmuyor.");
        } elseif (strlen($password) < 8) {
          alertdanger("Şifreniz en az 8 karakter olmalıdır.");
          //En az bir rakam, bir büyük harf, bir küçük harf ve bir özel karakter içermelidir.
        } elseif (!preg_match("#[0-9]+#", $password)) {
          alertdanger("Şifreniz en az bir rakam içermelidir.");
        } elseif (!preg_match("#[A-Z]+#", $password)) {
          alertdanger("Şifreniz en az bir büyük harf içermelidir.");
        } else {
          //Token kontrolü
          $user = $PasswordModel->getPasswordReset($token);
          if (empty($user)) {
            alertdanger("Geçersiz token.");
          } else {
            //Şifreyi hashle
            $new_password = password_hash($password, PASSWORD_DEFAULT);
            //Kullanıcı şifresini güncelle
            $User->updateUserPassword($user->email, $new_password);

            $password = $password_repeat = '';

            alertdanger("Şifreniz başarıyla değiştirildi. Yönlendiriliyorsunuz...", "info", "Başarılı!");
            header("refresh:2;url=sign-in.php");
          }

        }

      }
      ?>
      <p class="login-subtitle mb-3">Lütfen yeni şifrenizi girin. Şifrenizin en az 8 karakter uzunluğunda olduğundan ve hem büyük hem de küçük harfler, rakamlar ve özel karakterler içerdiğinden emin olun.</p>

      <form action="reset-password.php?token=<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>" method="post"
        autocomplete="off">
        <div class="form-floating mb-3 position-relative">
          <input type="password" class="form-control" name="password" id="floatingPassword" value="<?php echo htmlspecialchars($password ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Yeni Şifre" required>
          <label for="floatingPassword">Şifre</label>
        </div>
        <div class="form-floating mb-3 position-relative">
          <input type="password" class="form-control" name="password_repeat" id="floatingPasswordRepeat"
            value="<?php echo htmlspecialchars($password_repeat ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Şifre tekrar" required>
          <label for="floatingPasswordRepeat">Şifre Tekrar</label>
          <span class="input-icon-addon-right" id="togglePasswordBtn">
            <i class="ti ti-eye" id="togglePasswordIcon"></i>
          </span>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">
          <i class="ti ti-refresh me-1"></i>
          Şifremi Değiştir
        </button>
      </form>

    </div>
  </div>
  <!-- Libs JS -->
  <!-- Tabler Core -->
  <script src="./dist/js/tabler.min.js?1726507346" defer=""></script>
  <script src="./dist/js/demo.min.js?1726507346" defer=""></script>
  <script src="./dist/js/jquery.3.7.1.min.js"></script>

  <script>
    //Şifreleri göster
    $(document).ready(function () {
      const toggleBtn = document.getElementById('togglePasswordBtn');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
          const icon = document.getElementById('togglePasswordIcon');
          const isPassword = $('#floatingPassword').attr('type') === 'password';
          $('#floatingPassword, #floatingPasswordRepeat').attr('type', isPassword ? 'text' : 'password');
          icon.classList.toggle('ti-eye', !isPassword);
          icon.classList.toggle('ti-eye-off', isPassword);
        });
      }
    });

  </script>

</body>

</html>
