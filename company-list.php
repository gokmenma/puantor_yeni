<?php
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: sign-in.php");
    exit();
}

require_once "Model/UserModel.php";
require_once "Model/MyFirmModel.php";
require_once "App/Helper/security.php";

$User = new UserModel();
$myFirmObj = new MyFirmModel();

$user_id = (int) $_SESSION['user']->id;
$email = $_SESSION['user']->email;
$is_superadmin = (int) ($_SESSION['user']->superadmin ?? 0) === 1;

// Güncel kullanıcı verisini çek ve session'ı senkronize et
$currentUser = $User->find($user_id);
if ($currentUser) {
    $_SESSION['user']->default_firm_id = (int) ($currentUser->default_firm_id ?? 0);
    $_SESSION['user']->firm_id = (int) ($currentUser->firm_id ?? 0);
    $defaultFirmId = (int) ($currentUser->default_firm_id ?? 0);
} else {
    $defaultFirmId = (int) ($_SESSION['user']->default_firm_id ?? 0);
}

// Superadmin ise doğrudan ana sayfaya yönlendir
if ($is_superadmin) {
    $_SESSION['firm_id'] = $_SESSION['user']->firm_id ?? 0;
    $rawReturn = $_GET['returnUrl'] ?? '';
    $returnUrl = !empty($rawReturn) ? urldecode($rawReturn) : '';
    if (empty($returnUrl) || strpos($returnUrl, 'company-list') !== false || strpos($returnUrl, 'sign-in') !== false || strpos($returnUrl, 'logout') !== false) {
        $redirectUri = '/anasayfa';
    } else {
        $redirectUri = $returnUrl;
    }
    header('Location: ' . $redirectUri);
    exit();
}

// Kullanıcının yetkili olduğu firmalar
$myFirms = $myFirmObj->getMyFirmByUserId();
$authorizedFirmIds = array_map(static function ($firm) {
    return (int) $firm->id;
}, $myFirms);

// AJAX İsteği: Varsayılan Firma Güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_default') {
    header('Content-Type: application/json; charset=utf-8');
    $targetFirmId = (int) ($_POST['firm_id'] ?? 0);

    if ($targetFirmId > 0 && in_array($targetFirmId, $authorizedFirmIds, true)) {
        $User->setDefaultFirm($user_id, $targetFirmId, $email);
        $_SESSION['user']->default_firm_id = $targetFirmId;
        echo json_encode([
            'success' => true,
            'message' => 'Varsayılan firma başarıyla kaydedildi. Bir sonraki girişinizde doğrudan bu firmayla açılacaktır.',
            'default_firm_id' => $targetFirmId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz veya yetkiniz olmayan firma seçimi.'
        ]);
    }
    exit();
}

// Normal POST İsteği: Firma Seçimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firm_id'])) {
    $selectedFirmId = (int) $_POST['firm_id'];
    $makeDefault = isset($_POST['make_default']) && $_POST['make_default'] === '1';

    if (in_array($selectedFirmId, $authorizedFirmIds, true)) {
        $_SESSION['firm_id'] = $selectedFirmId;

        if ($makeDefault) {
            $User->setDefaultFirm($user_id, $selectedFirmId, $email);
            $_SESSION['user']->default_firm_id = $selectedFirmId;
        }

        $rawReturn = $_GET['returnUrl'] ?? '';
        $returnUrl = !empty($rawReturn) ? urldecode($rawReturn) : '';
        if (empty($returnUrl) || strpos($returnUrl, 'company-list') !== false || strpos($returnUrl, 'sign-in') !== false || strpos($returnUrl, 'logout') !== false) {
            $redirectUri = '/anasayfa';
        } else {
            $redirectUri = $returnUrl;
        }
        header('Location: ' . $redirectUri);
        exit();
    }
}

// Otomatik Yönlendirme (Auto-Skip):
// Kullanıcı menüden kasıtlı olarak "Firma Değiştir" (switch=1 vb.) demediyse ve varsayılan firması varsa/tek firması varsa otomatik atla!
$isExplicitSwitch = isset($_GET['switch']) || isset($_GET['change']) || isset($_GET['select']);

if (!$isExplicitSwitch) {
    $rawReturn = $_GET['returnUrl'] ?? '';
    $returnUrl = !empty($rawReturn) ? urldecode($rawReturn) : '';
    $defaultDest = (empty($returnUrl) || strpos($returnUrl, 'company-list') !== false || strpos($returnUrl, 'sign-in') !== false || strpos($returnUrl, 'logout') !== false) 
        ? '/anasayfa' 
        : $returnUrl;

    if (count($myFirms) === 1) {
        $_SESSION['firm_id'] = (int) $myFirms[0]->id;
        header('Location: ' . $defaultDest);
        exit();
    }

    if ($defaultFirmId > 0 && in_array($defaultFirmId, $authorizedFirmIds, true)) {
        $_SESSION['firm_id'] = $defaultFirmId;
        header('Location: ' . $defaultDest);
        exit();
    }
}

function getFirmInitials($firmName)
{
    $words = preg_split('/\s+/u', trim((string) $firmName), -1, PREG_SPLIT_NO_EMPTY);
    if (empty($words)) {
        return 'F';
    }

    $firstLetter = static function ($word) {
        return function_exists('mb_substr') ? mb_substr($word, 0, 1, 'UTF-8') : substr($word, 0, 1);
    };

    $initials = $firstLetter($words[0]);
    if (count($words) > 1) {
        $initials .= $firstLetter($words[count($words) - 1]);
    }

    return function_exists('mb_strtoupper')
        ? mb_strtoupper($initials, 'UTF-8')
        : strtoupper($initials);
}

$userFullName = $_SESSION['user']->full_name ?? 'Kullanıcı';
$userEmail = $_SESSION['user']->email ?? '';
?>
<!doctype html>
<html lang="tr" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Çalışma Alanı Seçimi | Puantor</title>
    <link rel="icon" href="./static/favicon.ico" type="image/x-icon" />

    <!-- Google Fonts & Tabler Core -->
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

    <style>
        :root {
            --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica Neue, sans-serif;
            --page-bg: #eef3f8;
            --card-border-color: #dbe3ec;
        }

        [data-bs-theme="dark"] {
            --page-bg: #0f172a;
            --card-border-color: #334155;
        }

        body {
            background-color: var(--page-bg) !important;
            font-family: var(--tblr-font-sans-serif);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            color: var(--tblr-body-color);
        }

        /* Minimal Header */
        .workspace-header {
            background: var(--tblr-bg-surface);
            border-bottom: 1px solid var(--card-border-color);
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .workspace-logo {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            color: inherit;
        }

        .workspace-logo-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, #0054a6 0%, #206bc4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: 0 2px 6px rgba(0, 84, 166, 0.25);
        }

        .workspace-logo-text {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .workspace-logo-subtitle {
            font-size: 0.7rem;
            color: var(--tblr-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        /* Body Container */
        .workspace-main {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 2.5rem 1rem;
        }

        .workspace-content {
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
        }

        /* Hero Kicker & Titles */
        .workspace-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            background: rgba(0, 84, 166, 0.08);
            border: 1px solid rgba(0, 84, 166, 0.16);
            border-radius: 999px;
            color: #0054a6;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        [data-bs-theme="dark"] .workspace-kicker {
            background: rgba(66, 153, 225, 0.12);
            border-color: rgba(66, 153, 225, 0.24);
            color: #63b3ed;
        }

        /* Company Card Styles (Puantor Standart) */
        .company-select-card {
            background: var(--tblr-card-bg, #ffffff);
            border: 1px solid var(--card-border-color);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .company-select-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.11);
            border-color: rgba(0, 84, 166, 0.45);
        }

        .company-select-card.is-default {
            border-color: rgba(245, 159, 0, 0.55);
            box-shadow: 0 4px 14px rgba(245, 159, 0, 0.12);
        }

        .company-select-card.is-default::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: #f59f00;
        }

        .company-avatar-box {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: rgba(0, 84, 166, 0.1);
            color: #0054a6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            font-weight: 700;
            border: 1px solid rgba(0, 84, 166, 0.15);
            flex-shrink: 0;
        }

        [data-bs-theme="dark"] .company-avatar-box {
            background: rgba(66, 153, 225, 0.15);
            color: #63b3ed;
            border-color: rgba(66, 153, 225, 0.25);
        }

        .company-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--tblr-body-color);
            line-height: 1.3;
            margin-bottom: 0.25rem;
        }

        .company-desc {
            font-size: 0.825rem;
            color: var(--tblr-secondary);
            min-height: 2.4rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.45;
        }

        .company-meta-item {
            font-size: 0.775rem;
            color: var(--tblr-secondary);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .company-meta-item i {
            font-size: 0.95rem;
        }

        /* Action Buttons */
        .btn-enter-firm {
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.55rem 1rem;
            transition: all 0.15s ease;
        }

        .btn-enter-firm .ti-arrow-right {
            transition: transform 0.2s ease;
        }

        .company-select-card:hover .btn-enter-firm .ti-arrow-right {
            transform: translateX(4px);
        }

        .btn-star-default {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--tblr-secondary);
            border: 1px solid var(--card-border-color);
            background: var(--tblr-bg-surface);
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .btn-star-default:hover {
            color: #f59f00;
            border-color: #f59f00;
            background: rgba(245, 159, 0, 0.08);
            transform: scale(1.05);
        }

        .btn-star-default.active {
            color: #f59f00;
            border-color: rgba(245, 159, 0, 0.3);
            background: rgba(245, 159, 0, 0.12);
        }

        .info-pill-box {
            background: rgba(0, 84, 166, 0.04);
            border: 1px dashed rgba(0, 84, 166, 0.2);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            color: var(--tblr-secondary);
        }

        [data-bs-theme="dark"] .info-pill-box {
            background: rgba(66, 153, 225, 0.05);
            border-color: rgba(66, 153, 225, 0.2);
        }

        /* Footer */
        .workspace-footer {
            padding: 1rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--tblr-secondary);
            border-top: 1px solid var(--card-border-color);
            background: var(--tblr-bg-surface);
        }
    </style>
</head>
<body>

    <!-- Minimalist & Independent Header (No ERP Topbar / Global Search) -->
    <header class="workspace-header">
        <div class="container-xl d-flex align-items-center justify-content-between">
            <a href="/anasayfa" class="workspace-logo">
                <div class="workspace-logo-icon">
                    <i class="ti ti-layout-grid"></i>
                </div>
                <div>
                    <div class="workspace-logo-text">Puantor</div>
                    <div class="workspace-logo-subtitle">Çalışma Alanı Portalı</div>
                </div>
            </a>

            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle -->
                <button type="button" class="btn btn-icon btn-ghost-secondary rounded-circle" id="themeToggleBtn" title="Tema Değiştir" aria-label="Tema Değiştir">
                    <i class="ti ti-sun fs-2" id="themeIcon"></i>
                </button>

                <!-- User Profile & Logout -->
                <div class="d-flex align-items-center gap-2 ps-2 border-start border-secondary-subtle">
                    <div class="d-none d-sm-block text-end">
                        <div class="fw-bold fs-4 line-height-1 mb-0"><?php echo htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="text-secondary small"><?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-2 d-flex align-items-center gap-1 ms-1" title="Oturumu Kapat">
                        <i class="ti ti-logout"></i>
                        <span class="d-none d-md-inline">Çıkış</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="workspace-main">
        <div class="container-xl">
            <div class="workspace-content">
                
                <!-- Page Title Area -->
                <div class="text-center mb-4 pb-2">
                    <div class="workspace-kicker mb-2">
                        <i class="ti ti-building"></i>
                        Çalışma Alanı Seçimi
                    </div>
                    <h1 class="display-6 fw-bold mb-1">Hoş Geldiniz</h1>
                    <p class="text-secondary fs-3 mb-0">
                        Devam etmek istediğiniz firmayı seçin veya varsayılan çalışma alanınızı belirleyin.
                    </p>
                </div>

                <?php if (!empty($myFirms)): ?>
                    <div class="row row-cards g-3 justify-content-center">
                        <?php foreach ($myFirms as $myfirm): ?>
                            <?php
                            $firmId = (int) $myfirm->id;
                            $firmName = (string) ($myfirm->firm_name ?? 'İsimsiz Firma');
                            $firmDescription = trim((string) ($myfirm->description ?? ''));
                            $firmPhone = trim((string) ($myfirm->phone ?? ''));
                            $isDefault = ($firmId === $defaultFirmId);
                            ?>
                            <div class="col-12 col-md-6">
                                <div class="company-select-card <?php echo $isDefault ? 'is-default' : ''; ?>" id="firmCard-<?php echo $firmId; ?>">
                                    <div class="p-3 p-md-4 d-flex flex-column h-100">
                                        
                                        <!-- Card Top: Avatar & Badges / Default Action -->
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="company-avatar-box">
                                                    <?php echo htmlspecialchars(getFirmInitials($firmName), ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div>
                                                    <h2 class="company-title mb-0"><?php echo htmlspecialchars($firmName, ENT_QUOTES, 'UTF-8'); ?></h2>
                                                    <span class="company-meta-item">
                                                        <i class="ti ti-id"></i> ID: #<?php echo $firmId; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-2">
                                                <!-- Default Star / Indicator -->
                                                <button type="button" 
                                                        class="btn-star-default <?php echo $isDefault ? 'active' : ''; ?>" 
                                                        data-firm-id="<?php echo $firmId; ?>" 
                                                        data-firm-name="<?php echo htmlspecialchars($firmName, ENT_QUOTES, 'UTF-8'); ?>"
                                                        title="<?php echo $isDefault ? 'Şu anki varsayılan firma' : 'Varsayılan firma olarak ayarla'; ?>"
                                                        onclick="setDefaultFirm(<?php echo $firmId; ?>, '<?php echo htmlspecialchars(addslashes($firmName), ENT_QUOTES, 'UTF-8'); ?>')">
                                                    <i class="ti <?php echo $isDefault ? 'ti-star-filled' : 'ti-star'; ?>"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Card Body: Description & Details -->
                                        <div class="mb-3">
                                            <p class="company-desc mb-2">
                                                <?php echo htmlspecialchars($firmDescription !== '' ? $firmDescription : 'Puantor çalışma alanı ve puantaj yönetim sistemi.', ENT_QUOTES, 'UTF-8'); ?>
                                            </p>
                                            <div class="d-flex flex-wrap gap-3">
                                                <span class="company-meta-item">
                                                    <i class="ti ti-phone text-primary"></i>
                                                    <?php echo $firmPhone !== '' ? htmlspecialchars($firmPhone, ENT_QUOTES, 'UTF-8') : 'Telefon belirtilmemiş'; ?>
                                                </span>
                                                <span class="company-meta-item">
                                                    <span class="badge bg-green-lt d-inline-flex align-items-center gap-1 px-2 py-0-5">
                                                        <span class="status-dot status-dot-animated bg-success" style="width: 6px; height: 6px;"></span>
                                                        Aktif
                                                    </span>
                                                </span>
                                                <?php if ($isDefault): ?>
                                                    <span class="company-meta-item" id="defaultBadge-<?php echo $firmId; ?>">
                                                        <span class="badge bg-warning-lt text-warning fw-bold d-inline-flex align-items-center gap-1 px-2 py-0-5">
                                                            <i class="ti ti-star-filled" style="font-size: 10px;"></i>
                                                            Varsayılan
                                                        </span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="company-meta-item d-none" id="defaultBadge-<?php echo $firmId; ?>">
                                                        <span class="badge bg-warning-lt text-warning fw-bold d-inline-flex align-items-center gap-1 px-2 py-0-5">
                                                            <i class="ti ti-star-filled" style="font-size: 10px;"></i>
                                                            Varsayılan
                                                        </span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Card Footer: Select Button -->
                                        <div class="mt-auto pt-2">
                                            <form action="" method="post" class="m-0">
                                                <input type="hidden" name="firm_id" value="<?php echo $firmId; ?>">
                                                <button type="submit" class="btn btn-primary w-100 btn-enter-firm d-flex align-items-center justify-content-center gap-2">
                                                    <span>Firmaya Giriş Yap</span>
                                                    <i class="ti ti-arrow-right"></i>
                                                </button>
                                            </form>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Bottom Info Banner -->
                    <div class="info-pill-box text-center mt-4 d-flex align-items-center justify-content-center gap-2">
                        <i class="ti ti-shield-check text-primary fs-3"></i>
                        <span>
                            Yalnızca yetkili olduğunuz firmalar listelenir. Yıldız simgesi (<i class="ti ti-star text-warning"></i>) ile varsayılan olarak seçtiğiniz firma ile sonraki girişlerinizde doğrudan ana sayfaya yönlendirilirsiniz.
                        </span>
                    </div>

                <?php else: ?>
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body py-5">
                            <div class="avatar avatar-xl bg-primary-lt rounded-circle mb-3 mx-auto">
                                <i class="ti ti-building-off fs-1 text-primary"></i>
                            </div>
                            <h2 class="fw-bold mb-2">Firma Bulunamadı</h2>
                            <p class="text-secondary mb-3">Hesabınıza tanımlanmış aktif bir firma çalışma alanı bulunmuyor.</p>
                            <a href="logout.php" class="btn btn-outline-secondary">Çıkış Yap</a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="workspace-footer">
        <div class="container-xl">
            Puantor © <?php echo date('Y'); ?> <span class="mx-1">·</span> Güvenli Çalışma Alanı Girişi
        </div>
    </footer>

    <!-- Tabler & SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Tema Yönetimi
        (function() {
            var currentTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', currentTheme);
            updateThemeIcon(currentTheme);

            var toggleBtn = document.getElementById('themeToggleBtn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    var theme = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-bs-theme', theme);
                    localStorage.setItem('theme', theme);
                    updateThemeIcon(theme);
                });
            }

            function updateThemeIcon(theme) {
                var icon = document.getElementById('themeIcon');
                if (icon) {
                    if (theme === 'dark') {
                        icon.className = 'ti ti-moon fs-2 text-warning';
                    } else {
                        icon.className = 'ti ti-sun fs-2 text-warning';
                    }
                }
            }
        })();

        // Varsayılan Firma Ayarlama (AJAX)
        function setDefaultFirm(firmId, firmName) {
            var formData = new FormData();
            formData.append('action', 'set_default');
            formData.append('firm_id', firmId);

            fetch('company-list.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    // Tüm kartlardan varsayılan durumunu kaldır
                    document.querySelectorAll('.company-select-card').forEach(function(card) {
                        card.classList.remove('is-default');
                    });
                    document.querySelectorAll('.btn-star-default').forEach(function(btn) {
                        btn.classList.remove('active');
                        btn.querySelector('i').className = 'ti ti-star';
                        btn.setAttribute('title', 'Varsayılan firma olarak ayarla');
                    });
                    document.querySelectorAll('[id^="defaultBadge-"]').forEach(function(badge) {
                        badge.classList.add('d-none');
                    });

                    // Seçili kartı varsayılan yap
                    var activeCard = document.getElementById('firmCard-' + firmId);
                    if (activeCard) {
                        activeCard.classList.add('is-default');
                        var starBtn = activeCard.querySelector('.btn-star-default');
                        if (starBtn) {
                            starBtn.classList.add('active');
                            starBtn.querySelector('i').className = 'ti ti-star-filled';
                            starBtn.setAttribute('title', 'Şu anki varsayılan firma');
                        }
                        var defaultBadge = document.getElementById('defaultBadge-' + firmId);
                        if (defaultBadge) {
                            defaultBadge.classList.remove('d-none');
                        }
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Varsayılan Firma Güncellendi',
                        text: firmName + ' varsayılan çalışma alanınız olarak belirlendi. Sonraki girişlerinizde bu adımı otomatik olarak atlarsınız.',
                        timer: 2500,
                        showConfirmButton: false,
                        timerProgressBar: true
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: data.message || 'Varsayılan firma güncellenemedi.'
                    });
                }
            })
            .catch(function(err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Bir bağlantı hatası oluştu.'
                });
            });
        }
    </script>
</body>
</html>
