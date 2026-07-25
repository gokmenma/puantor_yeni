<?php
define("ROOT", __DIR__);
require_once 'configs/require.php';
require_once 'Model/UserModel.php';
require_once 'App/Helper/security.php';
require_once 'Model/SettingsModel.php';
require_once 'App/Helper/date.php';
require_once 'Model/LoginLogsModel.php';
require_once 'Service/LoginSecurityService.php';
require_once 'Service/MailGonderimService.php';

use Service\LoginSecurityService;
use Service\MailGonderimService;

$Settings = new SettingsModel();
$User = new UserModel();
$loginSecurity = new LoginSecurityService($User->getDb());
$clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function safeLoginReturnUrl(string $value, string $fallback): string
{
    $value = urldecode($value);
    if ($value === '' || preg_match('#^(?:https?:)?//#i', $value) || str_contains($value, "\r") || str_contains($value, "\n")) {
        return $fallback;
    }
    return $value;
}

use App\Helper\Security;
use App\Helper\Date;

$error = "";

// Beni Hatırla Kontrolü (Cookie)
if ((!isset($_SESSION['user']) || empty($_SESSION['user'])) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $cookie_user = $User->getUserBySessionToken($token);
    if ($cookie_user && (int) ($cookie_user->superadmin ?? 0) === 1) {
        puantorExpireCookie('remember_me');
        $User->setToken($cookie_user->id, bin2hex(random_bytes(32)));
        $loginSecurity->event((int) $cookie_user->id, 'superadmin_remember_rejected', 'Superadmin kalıcı oturum girişimi reddedildi.');
    } elseif ($cookie_user && $cookie_user->status == 1) {
        session_regenerate_id(true);
        $_SESSION['user'] = $cookie_user;
        $_SESSION['firm_id'] = $cookie_user->firm_id;
        $_SESSION['full_name'] = $cookie_user->full_name;
        $_SESSION['user_role'] = $cookie_user->user_roles;
        $_SESSION["log_id"] = $User->loginLog($cookie_user->id);
        
        $is_superadmin = ($cookie_user->superadmin ?? 0) == 1;
        if ($is_superadmin) {
            $_SESSION['firm_id'] = $cookie_user->firm_id ?? 0;
            $rawReturn = $_GET['returnUrl'] ?? '';
            $returnUrl = safeLoginReturnUrl($rawReturn, '');
            if (empty($returnUrl) || strpos($returnUrl, 'company-list') !== false || strpos($returnUrl, 'sign-in') !== false || strpos($returnUrl, 'logout') !== false) {
                $redirectUri = 'index.php?p=home';
            } else {
                $redirectUri = $returnUrl;
            }
            header("Location: {$redirectUri}");
        } else {
            $rawReturn = $_GET['returnUrl'] ?? '';
            $returnUrl = safeLoginReturnUrl($rawReturn, 'company-list.php');
            header("Location: {$returnUrl}");
        }
        exit();
    }
}

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    $is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
    if ($is_superadmin) {
        $_SESSION['firm_id'] = $_SESSION['user']->firm_id ?? 0;
        $rawReturn = $_GET['returnUrl'] ?? '';
        $returnUrl = safeLoginReturnUrl($rawReturn, '');
        if (empty($returnUrl) || strpos($returnUrl, 'company-list') !== false || strpos($returnUrl, 'sign-in') !== false || strpos($returnUrl, 'logout') !== false) {
            $redirectUri = 'index.php?p=home';
        } else {
            $redirectUri = $returnUrl;
        }
        header("Location: {$redirectUri}");
    } elseif (!hash_equals((string) $_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyin.';
    } elseif ($loginSecurity->isBlocked($email, $clientIp)) {
        $error = 'Çok fazla hatalı giriş denemesi yapıldı. 15 dakika sonra tekrar deneyin.';
        $loginSecurity->event(null, 'login_rate_limited', 'Giriş deneme sınırı uygulandı.');
    } else {
        header("Location: company-list.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitForm'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $error = 'E-posta, telefon veya kullanıcı adı boş bırakılamaz';
    } elseif (empty($password)) {
        $error = 'Şifre boş bırakılamaz';
    } else {
        $user = $User->getUserByLoginField($email);
        if (!$user) {
            $loginSecurity->record($email, $clientIp, false);
            $error = 'Giriş bilgileri hatalı veya hesap kullanıma kapalı.';
        } elseif (isset($user) && $user->status == 0) {
            $loginSecurity->record($email, $clientIp, false);
            $error = 'Giriş bilgileri hatalı veya hesap kullanıma kapalı.';
        } else {
            $verified = password_verify($password, $user->password);
            $demo_date = $user->created_at;

            if ($verified) {
                if ((int) ($user->superadmin ?? 0) === 1) {
                    $code = (string) random_int(100000, 999999);
                    try {
                        $mailer = (new MailGonderimService(new SettingsModel()))->createMailer('info');
                        $mailer->addAddress($user->email, $user->full_name);
                        $mailer->Subject = 'Puantor giriş doğrulama kodu';
                        $mailer->Body = '<p>Giriş doğrulama kodunuz:</p><p style="font-size:28px;font-weight:bold;letter-spacing:4px">' . $code . '</p><p>Kod 5 dakika geçerlidir.</p>';
                        $mailer->AltBody = "Giriş doğrulama kodunuz: {$code}. Kod 5 dakika geçerlidir.";
                        $mailer->send();
                        session_regenerate_id(true);
                        $_SESSION['pending_superadmin_2fa'] = [
                            'user_id' => (int) $user->id,
                            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                            'expires_at' => time() + 300,
                            'attempts' => 0,
                        ];
                        $loginSecurity->event((int) $user->id, 'superadmin_2fa_sent', 'Superadmin 2FA kodu gönderildi.');
                        header('Location: superadmin-verify.php');
                        exit;
                    } catch (Throwable $e) {
                        error_log('Superadmin 2FA mail error: ' . $e->getMessage());
                        $loginSecurity->event((int) $user->id, 'superadmin_2fa_delivery_failed', '2FA kodu gönderilemedi.');
                        $error = 'Doğrulama kodu gönderilemedi. SMTP ayarlarını kontrol edin.';
                        goto login_processing_complete;
                    }
                }
                // Deneme süresi kontrolü
                $days = Date::getDateDiff($demo_date);
                $has_active_sub = false;
                if (($user->superadmin ?? 0) != 1) {
                    $owner_id = ($user->parent_id == 0) ? $user->id : $user->parent_id;
                    $owner_user = ($owner_id == $user->id) ? $user : $User->find($owner_id);
                    if ($owner_user && ($owner_user->superadmin ?? 0) == 1) {
                        $has_active_sub = true;
                    } else {
                        $dbCheck = $User->getDb();
                        $stmtCheck = $dbCheck->prepare("SELECT COUNT(*) FROM kullanici_abonelikleri WHERE kullanici_id = ? AND durum = 'aktif' AND bitis_tarihi >= ?");
                        $stmtCheck->execute([$owner_id, date('Y-m-d')]);
                        $has_active_sub = $stmtCheck->fetchColumn() > 0;
                    }
                }
                if ($days >= 15 && $user->user_type == 1 && !$has_active_sub && ($user->superadmin ?? 0) != 1) {
                    $error = 'Deneme süreniz dolmuştur. Lütfen iletişime geçiniz.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user'] = $user;
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $_SESSION['full_name'] = $user->full_name;
                    $_SESSION['user_role'] = $user->user_roles;

                    // Beni Hatırla
                    if (isset($_POST['remember']) && (int) ($user->superadmin ?? 0) !== 1) {
                        $token = bin2hex(random_bytes(32));
                        $User->setToken($user->id, $token);
                        setcookie('remember_me', $token, [
                            'expires' => time() + 30 * 24 * 3600, 'path' => '/',
                            'secure' => puantorIsHttps(), 'httponly' => true, 'samesite' => 'Lax',
                        ]);
                    }
                    $_SESSION['last_activity_at'] = time();
                    $loginSecurity->clearFailures($email, $clientIp);

                    $_SESSION["log_id"] = $User->loginLog($user->id);
                    $LoginLogs = new LoginLogsModel();

                    // Mail gönderme seçeneği kontrolü
                    $send_email_on_login = $Settings->getSettingIdByUserAndAction($user->id, "loginde_mail_gonder")->set_value ?? 0;
                    if ($send_email_on_login == 1) {
                        $emailAddress = $user->parent_id == 0 ? $user->email : $User->find($user->id);
                        try {
                            require_once "mail-settings.php";
                            $body = 'Merhaba ' . $user->full_name . ',<br><br>
                                Bu e-mail, hesabınıza giriş yapıldığını bildirmek amacıyla gönderilmiştir. 
                                Kayıtlı mail adresiniz ile www.puantor.com.tr müşteri hesabınıza giriş yapılmıştır. <br><br>
                                Giriş Zamanı: ' . date("Y-m-d H:i:s") . '<br>
                                Giriş yapan IP Adresi: ' . $_SERVER['REMOTE_ADDR'] . '<br>
                                Giriş yapan Kullanıcı: ' . $emailAddress . '<br><br>
                                Eğer bu işlem bilginiz dışındaysa, lütfen en kısa sürede bizimle iletişime geçiniz: 0507 943 27 23<br><br>
                                İyi Çalışmalar,<br><br>
                                www.puantor.com.tr';

                            $mail->setFrom('bilgi@puantor.com.tr', 'Puantor');
                            $mail->addAddress($emailAddress);
                            $mail->isHTML(true);
                            $mail->Subject = 'Hesabınıza giriş yapıldı';
                            $mail->Body = $body;
                            $mail->AltBody = strip_tags($body);
                            $mail->CharSet = 'UTF-8';
                            $mail->send();
                        } catch (Exception $e) {
                            // Hata loglanabilir
                        }
                    }

                    $is_superadmin = ($user->superadmin ?? 0) == 1;
                    if ($is_superadmin) {
                        $_SESSION['firm_id'] = $user->firm_id ?? 0;
                        $rawReturn = $_GET['returnUrl'] ?? '';
                        $returnUrl = safeLoginReturnUrl($rawReturn, '');
                        if (empty($returnUrl) || strpos($returnUrl, 'company-list') !== false || strpos($returnUrl, 'sign-in') !== false || strpos($returnUrl, 'logout') !== false) {
                            $redirectUri = 'index.php?p=home';
                        } else {
                            $redirectUri = $returnUrl;
                        }
                        header("Location: {$redirectUri}");
                        exit();
                    } else {
                        $returnUrl = isset($_GET['returnUrl']) && !empty($_GET['returnUrl']) ? urlencode($_GET['returnUrl']) : '';
                        header("Location: company-list.php?returnUrl={$returnUrl}");
                        exit();
                    }
                }
            } else {
                $loginSecurity->record($email, $clientIp, false);
                $error = 'Hatalı şifre, e-posta veya telefon';
            }
        }
    }
}
login_processing_complete:
?><!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Oturum Aç | Puantor - Puantaj Takip Sistemi</title>
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
  
  <style>
    :root {
      --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, sans-serif;
      --mobile-primary: #206bc4;
    }

    body {
      background-color: #f4f6f9;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 1rem;
      font-family: var(--tblr-font-sans-serif);
      position: relative;
      overflow: hidden;
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
      max-width: 400px;
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
  <div class="bg-blob bg-blob-primary"></div>
  <div class="bg-blob bg-blob-indigo"></div>

  <div class="login-container">
    <div class="text-center mb-1">
      <div class="mb-3 d-flex justify-content-center align-items-center" style="height: 80px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.03));">
        <?php 
        $logoPath = ROOT . '/static/Logo-ai.svg';
        if (file_exists($logoPath)) {
            $svg = file_get_contents($logoPath);
            $svg = str_replace('<svg ', '<svg style="height: 100px; width: auto; max-width: 100%;" ', $svg);
            echo $svg;
        } else {
            echo '<img src="./static/Logo-ai.svg" style="height: 100px; max-width: 100%;" alt="Puantor Logo">';
        }
        ?>
      </div>
      <p class="login-subtitle">Yönetici hesabı ile giriş yapın</p>
    </div>

    <div class="login-middle-form">
      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2.5 px-3 mb-3 text-sm animate-fade-in" style="border-radius: 14px; background-color: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;">
          <i class="ti ti-alert-triangle" style="font-size: 1.15rem; flex-shrink: 0;"></i>
          <span><?php echo $error; ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-floating mb-3">
          <input type="text" name="email" class="form-control" id="floatingEmail" placeholder="E-posta, telefon veya kullanıcı adı" required autocomplete="username" value="<?php echo htmlspecialchars($email ?? ''); ?>">
          <label for="floatingEmail">E-posta, Telefon veya Kullanıcı Adı</label>
        </div>
        <div class="form-floating mb-3 position-relative">
          <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
          <label for="password">Şifre</label>
          <span class="input-icon-addon-right" id="togglePasswordBtn" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; z-index: 10;">
            <i class="ti ti-eye" id="togglePasswordIcon"></i>
          </span>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-4">
          <label class="form-check m-0" style="cursor: pointer;">
            <input type="checkbox" name="remember" class="form-check-input" id="rememberMe" style="cursor: pointer;">
            <span class="form-check-label text-xs text-secondary" style="font-weight: 500; user-select: none;">Beni Hatırla</span>
          </label>
          <a href="./forgot-password.php" class="text-xs text-primary text-decoration-none hover-underline" style="font-weight: 600;">Şifremi Unuttum</a>
        </div>
        <button type="submit" name="submitForm" class="btn btn-primary w-100 mb-3">Giriş Yap</button>
      </form>
    </div>

    <div class="text-center mb-3">
      <a href="mobile/sign-in.php" class="text-secondary d-inline-flex align-items-center gap-1 text-xs text-decoration-none hover-underline">
        <i class="ti ti-device-mobile" style="font-size: 1rem;"></i>
        Mobil Sürüme Git
      </a>
    </div>

    <div class="text-center text-secondary text-xs">
      Henüz hesabınız yok mu? <a href="./register.php" class="text-primary text-decoration-none hover-underline" style="font-weight: 600;">Kayıt Ol</a>
    </div>
  </div>

  <script src="./dist/js/jquery.3.7.1.min.js"></script>
  <script>
    const toggleBtn = document.getElementById('togglePasswordBtn');
    if (toggleBtn) {
      toggleBtn.addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
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
  </script>
</body>
</html>
