<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/App/Helper/session_security.php';
puantorStartSecureSession();
require_once ROOT . '/Model/UserModel.php';
require_once ROOT . '/Service/LoginSecurityService.php';

$pending = $_SESSION['pending_mobile_superadmin_2fa'] ?? null;
if (!$pending) {
    header('Location: sign-in.php');
    exit;
}

$User = new UserModel();
$security = new \Service\LoginSecurityService($User->getDb());
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    $code = preg_replace('/\D+/', '', (string) ($_POST['code'] ?? ''));

    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrf)) {
        $error = 'Güvenlik doğrulaması başarısız.';
    } elseif (time() > (int) $pending['expires_at'] || (int) $pending['attempts'] >= 5) {
        $security->event((int) $pending['user_id'], 'mobile_superadmin_2fa_expired', 'Mobil 2FA süresi veya deneme limiti aşıldı.');
        unset($_SESSION['pending_mobile_superadmin_2fa']);
        $error = 'Kodun süresi doldu. Yeniden giriş yapın.';
    } elseif (strlen($code) !== 6 || !password_verify($code, (string) $pending['code_hash'])) {
        $_SESSION['pending_mobile_superadmin_2fa']['attempts']++;
        $pending = $_SESSION['pending_mobile_superadmin_2fa'];
        $security->event((int) $pending['user_id'], 'mobile_superadmin_2fa_failed', 'Hatalı mobil 2FA kodu girildi.');
        $error = 'Doğrulama kodu hatalı.';
    } else {
        $user = $User->find((int) $pending['user_id']);
        if (!$user || (int) ($user->superadmin ?? 0) !== 1 || (int) $user->status !== 1) {
            unset($_SESSION['pending_mobile_superadmin_2fa']);
            $error = 'Hesap doğrulanamadı.';
        } else {
            session_regenerate_id(true);
            unset($_SESSION['pending_mobile_superadmin_2fa']);
            $_SESSION['user'] = $user;
            $_SESSION['firm_id'] = (int) ($user->firm_id ?? 0);
            $_SESSION['full_name'] = $user->full_name;
            $_SESSION['user_role'] = $user->user_roles;
            $_SESSION['last_activity_at'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['log_id'] = $User->loginLog($user->id);
            $security->clearFailures((string) $user->email, (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
            $security->event((int) $user->id, 'mobile_superadmin_login_success', 'Superadmin mobil 2FA ile giriş yaptı.');
            header('Location: index.php?route=dashboard');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Mobil Giriş Doğrulama | Puantor</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
  <style>
    :root{--tblr-font-sans-serif:'Inter Var',-apple-system,BlinkMacSystemFont,sans-serif;--mobile-primary:#206bc4}
    body{min-height:100vh;margin:0;padding:1rem;display:flex;align-items:center;justify-content:center;background:#f4f6f9;font-family:var(--tblr-font-sans-serif);position:relative;overflow:hidden}
    .bg-blob{position:absolute;border-radius:50%;filter:blur(80px);z-index:1;opacity:.6}
    .bg-blob-primary{width:350px;height:350px;background:radial-gradient(circle,rgba(32,107,196,.2) 0%,rgba(32,107,196,0) 70%);top:-100px;left:-100px}
    .bg-blob-indigo{width:350px;height:350px;background:radial-gradient(circle,rgba(99,102,241,.15) 0%,rgba(99,102,241,0) 70%);right:-100px;bottom:-100px}
    .login-container{width:100%;max-width:400px;padding:3rem 2.25rem 2.5rem;background:rgba(255,255,255,.9);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.8);border-radius:28px;box-shadow:0 20px 40px -15px rgba(0,0,0,.05),0 1px 3px rgba(0,0,0,.02);position:relative;z-index:10;animation:fadeIn .4s ease-out}
    @keyframes fadeIn{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
    .brand-logo{height:80px;display:flex;align-items:center;justify-content:center;margin-bottom:.75rem;filter:drop-shadow(0 4px 12px rgba(0,0,0,.03))}
    .brand-logo svg,.brand-logo img{height:100px;width:auto;max-width:100%}
    .login-title{font-size:1.6rem;font-weight:700;color:#1e293b;letter-spacing:-.5px}
    .login-subtitle{font-size:.875rem;line-height:1.55;color:#64748b}
    .login-middle-form{margin:2.5rem 0 1.5rem}
    .form-label{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:.5rem}
    .otp-group{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.5rem}
    .otp-input{width:100%;height:54px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;color:#1e293b;font-size:1.25rem;font-weight:700;text-align:center;padding:0;caret-color:var(--mobile-primary);transition:all .25s}
    .otp-input:focus{background:#fff;border-color:var(--mobile-primary);box-shadow:0 0 0 4px rgba(32,107,196,.12);outline:0}
    .otp-input.is-filled{background:rgba(32,107,196,.06);border-color:rgba(32,107,196,.35)}
    .otp-input.is-invalid{border-color:#d63939;box-shadow:0 0 0 4px rgba(214,57,57,.1)}
    .btn-primary{border:0;border-radius:14px;padding:.85rem 1.5rem;font-size:.95rem;font-weight:600;background:linear-gradient(135deg,#206bc4 0%,#1a569d 100%);box-shadow:0 8px 24px rgba(32,107,196,.2)}
    .back-link{font-size:.8rem;font-weight:600;text-decoration:none}
    .alert{border-radius:14px}
    @media(max-width:480px){
      body{padding:0;align-items:stretch;background:#fff}
      .bg-blob{display:none}
      .login-container{max-width:100%;min-height:100dvh;border:0;border-radius:0;box-shadow:none;background:#fff;padding:2rem 1.75rem;display:flex;flex-direction:column;justify-content:center}
      .otp-group{gap:.35rem}
      .otp-input{height:50px;border-radius:12px;font-size:1.1rem}
    }
  </style>
</head>
<body>
  <div class="bg-blob bg-blob-primary"></div>
  <div class="bg-blob bg-blob-indigo"></div>
  <div class="login-container">
    <div class="text-center">
      <div class="brand-logo">
        <?php
        $logoPath = ROOT . '/static/Logo-ai.svg';
        if (file_exists($logoPath)) {
            $svg = file_get_contents($logoPath);
            $svg = str_replace('<svg ', '<svg style="height:100px;width:auto;max-width:100%;" ', $svg);
            echo $svg;
        } else {
            echo '<img src="../static/Logo-ai.svg" alt="Puantor">';
        }
        ?>
      </div>
      <h1 class="login-title mb-1">Sistem Yöneticisi Doğrulaması</h1>
      <p class="login-subtitle mb-0">E-posta adresinize gönderilen 6 haneli kodu girin.<br>Kod 5 dakika geçerlidir.</p>
    </div>
    <div class="login-middle-form">
      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 mb-3">
          <i class="ti ti-alert-triangle"></i><span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $_SESSION['csrf_token']) ?>">
        <label for="otpDigit1" class="form-label">Doğrulama kodu</label>
        <input type="hidden" id="verificationCode" name="code">
        <div class="otp-group mb-3" id="otpGroup" role="group" aria-label="6 haneli doğrulama kodu">
          <?php for ($digit = 1; $digit <= 6; $digit++): ?>
            <input
              type="text"
              id="otpDigit<?= $digit ?>"
              class="otp-input"
              inputmode="numeric"
              pattern="[0-9]"
              maxlength="1"
              aria-label="<?= $digit ?>. hane"
              <?= $digit === 1 ? 'autocomplete="one-time-code" autofocus' : 'autocomplete="off"' ?>
              required
            >
          <?php endfor; ?>
        </div>
        <button class="btn btn-primary w-100" type="submit"><i class="ti ti-lock-check me-2"></i>Güvenli giriş yap</button>
      </form>
    </div>
    <div class="text-center">
      <a href="sign-in.php" class="back-link text-primary"><i class="ti ti-arrow-left me-1"></i>Giriş ekranına dön</a>
    </div>
  </div>
  <script>
    const otpGroup = document.getElementById('otpGroup');
    const otpInputs = Array.from(otpGroup.querySelectorAll('.otp-input'));
    const codeInput = document.getElementById('verificationCode');
    const verificationForm = otpGroup.closest('form');

    function syncOtpCode() {
      codeInput.value = otpInputs.map(input => input.value).join('');
      otpInputs.forEach(input => {
        input.classList.toggle('is-filled', input.value !== '');
        input.classList.remove('is-invalid');
      });
    }

    function fillOtp(value) {
      const digits = value.replace(/\D/g, '').slice(0, otpInputs.length);
      otpInputs.forEach((input, index) => input.value = digits[index] || '');
      syncOtpCode();
      const targetIndex = Math.min(digits.length, otpInputs.length - 1);
      otpInputs[targetIndex].focus();
      otpInputs[targetIndex].select();
    }

    otpInputs.forEach((input, index) => {
      input.addEventListener('input', function () {
        const digits = this.value.replace(/\D/g, '');
        if (digits.length > 1) {
          fillOtp(digits);
          return;
        }
        this.value = digits;
        syncOtpCode();
        if (digits && index < otpInputs.length - 1) otpInputs[index + 1].focus();
      });

      input.addEventListener('keydown', function (event) {
        if (event.key === 'Backspace' && !this.value && index > 0) {
          otpInputs[index - 1].value = '';
          otpInputs[index - 1].focus();
          syncOtpCode();
        } else if (event.key === 'ArrowLeft' && index > 0) {
          event.preventDefault();
          otpInputs[index - 1].focus();
        } else if (event.key === 'ArrowRight' && index < otpInputs.length - 1) {
          event.preventDefault();
          otpInputs[index + 1].focus();
        }
      });

      input.addEventListener('focus', function () { this.select(); });
    });

    otpGroup.addEventListener('paste', function (event) {
      event.preventDefault();
      fillOtp(event.clipboardData.getData('text'));
    });

    verificationForm.addEventListener('submit', function (event) {
      syncOtpCode();
      if (codeInput.value.length !== otpInputs.length) {
        event.preventDefault();
        const firstEmpty = otpInputs.find(input => !input.value) || otpInputs[0];
        firstEmpty.classList.add('is-invalid');
        firstEmpty.focus();
      }
    });
  </script>
</body>
</html>
