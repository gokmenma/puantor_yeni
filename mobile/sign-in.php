<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

define("ROOT", dirname(__DIR__));
require_once ROOT . "/Database/db.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/Model/PasswordModel.php";
require_once ROOT . "/App/Helper/security.php";

$User = new UserModel();

// Beni Hatırla Kontrolü (Cookie)
if ((!isset($_SESSION['user']) || empty($_SESSION['user'])) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $cookie_user = $User->getUserBySessionToken($token);
    if ($cookie_user && $cookie_user->status == 1) {
        $_SESSION['user'] = $cookie_user;
        $_SESSION['firm_id'] = $cookie_user->firm_id;
    }
}

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success_message = "";
$view = $_GET['view'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $view = 'login';

        if (empty($email) || empty($password)) {
            $error = "Lütfen tüm alanları doldurun.";
        } else {
            $user = $User->getUserByEmail($email);
            if ($user && password_verify($password, $user->password)) {
                if ($user->status == 1) {
                    $_SESSION['user'] = $user;
                    $_SESSION['firm_id'] = $user->firm_id; // Varsayılan firmayı ata
                    
                    // Beni Hatırla seçildiyse
                    if (isset($_POST['remember'])) {
                        $token = bin2hex(random_bytes(32));
                        $User->setToken($user->id, $token);
                        setcookie('remember_me', $token, time() + 30 * 24 * 3600, '/');
                    }
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Hesabınız henüz aktif değil.";
                }
            } else {
                $error = "Hatalı email adresi veya şifre.";
            }
        }
    } elseif (isset($_POST['forgot'])) {
        $email = $_POST['email'] ?? '';
        $view = 'forgot';

        if (empty($email)) {
            $error = "Email adresi boş bırakılamaz.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Geçersiz e-posta adresi.";
        } else {
            $user = $User->getUserByEmail($email);
            if (!$user) {
                $error = "Bu e-posta adresi ile kayıtlı bir hesap bulunamadı.";
            } else {
                $PasswordModel = new PasswordModel();
                $token = bin2hex(random_bytes(32));
                
                $PasswordModel->setPasswordReset($email, $token);
                
                ob_start();
                include ROOT . '/forgot-password-email.php';
                $content = ob_get_clean();

                try {
                    require_once ROOT . "/mail-settings.php";

                    $mail->setFrom('sifre@puantor.com.tr', 'Puantor');
                    $mail->addAddress($email);
                    $mail->isHTML(true);

                    $mail->Subject = 'Şifre Sıfırlama';
                    $mail->Body = $content;
                    $mail->AltBody = strip_tags($content);
                    $mail->CharSet = 'UTF-8';

                    if (file_exists(ROOT . '/static/png/lock.png')) {
                        $mail->AddEmbeddedImage(ROOT . '/static/png/lock.png', 'lock-icon');
                    }

                    $mail->send();
                    $success_message = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
                } catch (Exception $e) {
                    $error = "E-posta gönderilemedi. Hata: {$mail->ErrorInfo}";
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no" />
    <title>Puantor Mobil | Giriş Yap</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#206bc4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Puantor">
    <link rel="apple-touch-icon" href="../static/png/icon-192x192.png">
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registered', reg))
                    .catch(err => console.log('Service Worker registration failed', err));
            });
        }
    </script>

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

        /* Logo & Headings */
        .avatar-logo {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, rgba(32, 107, 196, 0.12) 0%, rgba(32, 107, 196, 0.04) 100%);
            color: var(--mobile-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 20px rgba(32, 107, 196, 0.08);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .avatar-logo:hover {
            transform: scale(1.08) rotate(3deg);
        }

        .login-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.5px;
        }
        .login-subtitle {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Inputs & Form */
        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .input-icon {
            position: relative;
        }
        .form-control {
            font-size: 0.95rem;
            border-radius: 14px;
            padding: 0.8rem 1rem 0.8rem 2.75rem;
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
        .input-icon-addon-left {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            display: flex;
            align-items: center;
            pointer-events: none;
            font-size: 1.2rem;
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

        /* Mobile Responsive Adapting */
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
            .login-middle-form {
                margin: 2.5rem 0 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-blob bg-blob-primary"></div>
    <div class="bg-blob bg-blob-indigo"></div>

    <div class="login-container">
        <div>
            <div class="text-center mb-1">
                <div class="mb-3 d-flex justify-content-center align-items-center" style="height: 80px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.03));">
                    <?php 
                    $logoPath = ROOT . '/static/Logo-ai.svg';
                    if (file_exists($logoPath)) {
                        $svg = file_get_contents($logoPath);
                        $svg = str_replace('<svg ', '<svg style="height: 100px; width: auto; max-width: 100%;" ', $svg);
                        echo $svg;
                    } else {
                        echo '<img src="../static/Logo-ai.svg" style="height: 100px; max-width: 100%;" alt="Puantor Logo">';
                    }
                    ?>
                </div>
                <?php if ($view === 'forgot'): ?>
                    <h1 class="login-title mb-1">Şifre Sıfırlama</h1>
                    <p class="login-subtitle">Lütfen hesabınıza ait e-posta adresini girin</p>
                <?php else: ?>
                    <p class="login-subtitle">Yönetici hesabı ile giriş yapın</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="login-middle-form">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 py-2.5 px-3 mb-3 text-sm animate-fade-in" style="border-radius: 14px; background-color: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;">
                    <i class="ti ti-alert-triangle" style="font-size: 1.15rem; flex-shrink: 0;"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 py-2.5 px-3 mb-3 text-sm animate-fade-in" style="border-radius: 14px; background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;">
                    <i class="ti ti-circle-check" style="font-size: 1.15rem; flex-shrink: 0;"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($view === 'forgot'): ?>
                <form method="POST" action="">
                    <div class="form-floating mb-4">
                        <input type="email" name="email" class="form-control" id="floatingEmailForgot" placeholder="ad@sirket.com" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        <label for="floatingEmailForgot">Email Adresi</label>
                    </div>
                    <button type="submit" name="forgot" class="btn btn-primary w-100 mb-3">
                        <i class="ti ti-mail me-2"></i>Bağlantı Gönder
                    </button>
                    <div class="text-center">
                        <a href="?view=login" class="text-xs text-secondary text-decoration-none hover-underline d-inline-flex align-items-center gap-1" style="font-weight: 600;">
                            <i class="ti ti-arrow-left"></i> Giriş Ekranına Dön
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="ad@sirket.com" required autocomplete="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        <label for="floatingEmail">Email Adresi</label>
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
                        <a href="?view=forgot" class="text-xs text-primary text-decoration-none hover-underline" style="font-weight: 600;">Şifremi Unuttum</a>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100 mb-3">Giriş Yap</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="text-center ">
            <a href="../index.php" class="text-secondary d-inline-flex align-items-center gap-1 text-xs text-decoration-none hover-underline">
                <i class="ti ti-device-laptop" style="font-size: 1rem;"></i>
                Masaüstü Sürüme Dön
            </a>
        </div>
    </div>

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
