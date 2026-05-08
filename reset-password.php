<?php
require_once "Database/require.php";
require_once "Model/UserModel.php";
require_once "Model/PasswordModel.php";

$PasswordModel = new PasswordModel();

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


<html lang="en">

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
  <link href="./dist/css/style.css?1726507346" rel="stylesheet">
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
    <div class="container container-normal py-4">
      <div class="row align-items-center g-4">
        <div class="col-lg">
          <div class="container-tight">
            <div class="text-center mb-4">
              <a href="." class="navbar-brand navbar-brand-autodark">
                <img src="./static/logo-ai.svg" height="120" alt="">
              </a>
            </div>

            <?php
            $token = $_GET['token'];
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


            <form class="card card-md" action="reset-password.php?token=<?php echo $token; ?>" method="post"
              autocomplete="off">
              <div class="card-body ">
                <div class="alert alert-info bg-white">
                  <h2 class="alert-title">Şifre Sıfırlama</h2>
                  <p class="text-secondary">Lütfen yeni şifrenizi girin. Şifrenizin en az 8 karakter uzunluğunda
                    olduğundan ve hem büyük hem de küçük harfler, rakamlar ve özel karakterler içerdiğinden emin olun.
                  </p>
                </div>
                <div class="mb-3">
                  <label class="form-label">Şifre</label>
                  <input type="password" class="form-control" name="password" value="<?php echo $password ?? ''; ?>"
                    placeholder="Yeni Şifre">
                </div>
                <div class="mb-3">
                  <label class="form-label">Şifre Tekrar</label>
                  <input type="password" class="form-control" name="password_repeat"
                    value="<?php echo $password_repeat ?? ''; ?>" placeholder="Şifre tekrar">
                </div>

                <div class="mb-2">
                    <label class="form-check">
                      <input type="checkbox" class="form-check-input" id="show-password" />
                      <span class="form-check-label">Şifreleri göster</span>
                    </label>
                  </div>

                <div class="form-footer">
                  <button type="submit" class="btn btn-primary w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                      class="icon icon-tabler icons-tabler-outline icon-tabler-exchange">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                      <path d="M5 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                      <path d="M19 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                      <path d="M19 8v5a5 5 0 0 1 -5 5h-3l3 -3m0 6l-3 -3" />
                      <path d="M5 16v-5a5 5 0 0 1 5 -5h3l-3 -3m0 6l3 -3" />
                    </svg>
                    Şifremi Değiştir
                  </button>
                </div>
              </div>
            </form>

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
    //Şifreleri göster
    $(document).ready(function () {
      $('#show-password').click(function () {
        if ($(this).is(':checked')) {
          $('input[type="password"]').attr('type', 'text');
        } else {
          $('input[type="text"]').attr('type', 'password');
        }
      });
    });
   
  </script>

</body>

</html>