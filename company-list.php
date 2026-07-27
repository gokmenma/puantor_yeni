<?php
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: sign-in.php");
    exit();
}

$user_id = $_SESSION['user']->id;
$email = $_SESSION['user']->email;
$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;

if ($is_superadmin) {
    $_SESSION['firm_id'] = $_SESSION['user']->firm_id ?? 0;
    $rawReturn = $_GET['returnUrl'] ?? '';
    $returnUrl = !empty($rawReturn) ? urldecode($rawReturn) : '';
    if (empty($returnUrl) || strpos($returnUrl, 'company-list') !== false || strpos($returnUrl, 'sign-in') !== false || strpos($returnUrl, 'logout') !== false) {
        $redirectUri = 'index.php?p=home';
    } else {
        $redirectUri = $returnUrl;
    }
    header('Location: ' . $redirectUri);
    exit();
}

require_once "Model/MyFirmModel.php";
$myFirmObj = new MyFirmModel();

$myFirms = $myFirmObj->getMyFirmByUserId();

$defaultFirmId = (int)($_SESSION['user']->default_firm_id ?? 0);
$isExplicitSwitch = isset($_GET['switch']) || isset($_GET['change']) || isset($_GET['select']);

if ($defaultFirmId > 0 && !$isExplicitSwitch) {
    $authorizedFirmIds = array_map(static function ($firm) {
        return (int) $firm->id;
    }, $myFirms);

    if (in_array($defaultFirmId, $authorizedFirmIds, true)) {
        $_SESSION['firm_id'] = $defaultFirmId;
        $redirectUri = isset($_GET['returnUrl']) && !empty($_GET['returnUrl']) ? $_GET['returnUrl'] : '/index.php?p=home';
        header('Location: ' . $redirectUri);
        exit();
    }
}

if(count($myFirms) == 1){
    $_SESSION['firm_id'] = $myFirms[0]->id;
    $redirectUri = isset($_GET['returnUrl']) && !empty($_GET['returnUrl']) ? $_GET['returnUrl'] : '/index.php?p=home';
    header('Location: ' . $redirectUri);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firm_id'])) {
    $selectedFirmId = (int) $_POST['firm_id'];
    $authorizedFirmIds = array_map(static function ($firm) {
        return (int) $firm->id;
    }, $myFirms);

    if (in_array($selectedFirmId, $authorizedFirmIds, true)) {
        $_SESSION['firm_id'] = $selectedFirmId;
        $redirectUri = isset($_GET['returnUrl']) && !empty($_GET['returnUrl'])
            ? $_GET['returnUrl']
            : '/index.php?p=home';
        header('Location: ' . $redirectUri);
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
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Firma Listesi | Puantor - Puantaj Takip Sistemi
    </title>
    <!-- CSS files -->
    <link href="./dist/css/tabler.min.css?1692870487" rel="stylesheet" />
    <link href="./dist/css/demo.min.css?1692870487" rel="stylesheet" />
    <link href="./dist/css/style.css?1692870487" rel="stylesheet" />
  <link rel="icon" href="./static/favicon.ico" type="image/x-icon" />

    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        .company-selection-page {
            position: relative;
            overflow: hidden;
            min-height: calc(100vh - 57px);
        }

        .company-selection-page::before,
        .company-selection-page::after {
            position: absolute;
            z-index: 0;
            width: 28rem;
            height: 28rem;
            border-radius: 50%;
            content: "";
            pointer-events: none;
            filter: blur(1px);
            opacity: .55;
        }

        .company-selection-page::before {
            top: -16rem;
            left: -12rem;
            background: radial-gradient(circle, rgba(var(--tblr-primary-rgb), .16), transparent 70%);
        }

        .company-selection-page::after {
            right: -13rem;
            bottom: -17rem;
            background: radial-gradient(circle, rgba(var(--tblr-azure-rgb), .14), transparent 70%);
        }

        .company-selection-content {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1080px;
            margin: 0 auto;
        }

        .selection-kicker {
            display: inline-flex;
            gap: .5rem;
            align-items: center;
            padding: .35rem .7rem;
            color: var(--tblr-primary);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: rgba(var(--tblr-primary-rgb), .08);
            border: 1px solid rgba(var(--tblr-primary-rgb), .14);
            border-radius: 999px;
        }

        .selection-kicker-dot {
            width: .45rem;
            height: .45rem;
            background: var(--tblr-green);
            border-radius: 50%;
            box-shadow: 0 0 0 .2rem rgba(var(--tblr-green-rgb), .12);
        }

        .company-card {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 210px;
            padding: 0;
            overflow: hidden;
            color: inherit;
            font: inherit;
            text-align: left;
            background: var(--tblr-card-bg);
            border: var(--tblr-border-width) solid var(--tblr-border-color);
            border-radius: var(--tblr-border-radius-lg);
            box-shadow: 0 .25rem .75rem rgba(24, 36, 51, .04);
            cursor: pointer;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .company-card::before {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--tblr-primary), var(--tblr-azure));
            content: "";
            opacity: 0;
            transition: opacity .2s ease;
        }

        .company-card:hover,
        .company-card:focus-visible {
            color: inherit;
            text-decoration: none;
            border-color: rgba(var(--tblr-primary-rgb), .45);
            box-shadow: 0 .75rem 2rem rgba(24, 36, 51, .1);
            transform: translateY(-4px);
        }

        .company-card:hover::before,
        .company-card:focus-visible::before {
            opacity: 1;
        }

        .company-card:focus-visible {
            outline: .2rem solid rgba(var(--tblr-primary-rgb), .18);
            outline-offset: 3px;
        }

        .company-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            color: var(--tblr-primary);
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -.02em;
            background: rgba(var(--tblr-primary-rgb), .1);
            border: 1px solid rgba(var(--tblr-primary-rgb), .14);
            border-radius: 1rem;
        }

        .company-status {
            display: inline-flex;
            gap: .4rem;
            align-items: center;
        }

        .company-status-dot {
            width: .45rem;
            height: .45rem;
            background: var(--tblr-green);
            border-radius: 50%;
        }

        .company-description {
            min-height: 2.5rem;
        }

        .company-action {
            display: inline-flex;
            gap: .45rem;
            align-items: center;
            color: var(--tblr-primary);
            font-weight: 600;
        }

        .company-action .icon {
            transition: transform .2s ease;
        }

        .company-card:hover .company-action .icon {
            transform: translateX(3px);
        }

        .selection-note {
            background: rgba(var(--tblr-primary-rgb), .04);
            border: 1px dashed rgba(var(--tblr-primary-rgb), .2);
            border-radius: var(--tblr-border-radius-lg);
        }

        @media (max-width: 767.98px) {
            .company-selection-page {
                min-height: calc(100vh - 49px);
            }

            .company-card {
                min-height: 185px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .company-card,
            .company-action .icon {
                transition: none;
            }
        }
    </style>
</head>

<body>
    <script src="./dist/js/demo-theme.min.js?1692870487"></script>
    <div class="page">
        <?php include_once "inc/topbar.php" ?>

        <div class="page-wrapper company-selection-page">
            <div class="page-body d-flex align-items-center py-4 py-md-6">
                <div class="container-xl">
                    <main class="company-selection-content">
                        <header class="text-center mb-4 mb-md-5">
                            <div class="selection-kicker mb-3">
                                <span class="selection-kicker-dot"></span>
                                Çalışma alanı seçimi
                            </div>
                            <h1 class="display-6 fw-bold mb-2">Hoş geldiniz</h1>
                            <p class="text-secondary fs-3 mb-0">
                                Devam etmek istediğiniz firmayı seçin.
                            </p>
                        </header>

                        <?php if (!empty($myFirms)): ?>
                            <div class="row row-cards justify-content-center">
                                <?php foreach ($myFirms as $myfirm): ?>
                                    <?php
                                    $firmName = (string) ($myfirm->firm_name ?? 'İsimsiz Firma');
                                    $firmDescription = trim((string) ($myfirm->description ?? ''));
                                    $firmPhone = trim((string) ($myfirm->phone ?? ''));
                                    $isCardDefault = ((int)$myfirm->id === $defaultFirmId);
                                    ?>
                                    <div class="col-12 col-md-6">
                                        <form action="" method="post" class="h-100">
                                            <input type="hidden" name="firm_id" value="<?php echo (int) $myfirm->id; ?>">
                                            <button type="submit" class="company-card <?php echo $isCardDefault ? 'border-primary' : ''; ?>" aria-label="<?php echo htmlspecialchars($firmName, ENT_QUOTES, 'UTF-8'); ?> firmasını seç">
                                                <span class="card-body d-flex flex-column h-100 p-4">
                                                    <span class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                                        <span class="company-avatar">
                                                            <?php echo htmlspecialchars(getFirmInitials($firmName), ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                        <span class="d-flex align-items-center gap-2">
                                                            <?php if ($isCardDefault): ?>
                                                                <span class="badge bg-amber-lt text-amber company-status fw-bold">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z"/></svg>
                                                                    Varsayılan
                                                                </span>
                                                            <?php endif; ?>
                                                            <span class="badge bg-green-lt company-status">
                                                                <span class="company-status-dot"></span>
                                                                Aktif
                                                            </span>
                                                        </span>
                                                    </span>


                                                    <span class="d-block mb-3">
                                                        <span class="h2 d-block mb-2"><?php echo htmlspecialchars($firmName, ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <span class="text-secondary d-block company-description">
                                                            <?php echo htmlspecialchars($firmDescription !== '' ? $firmDescription : 'Puantor çalışma alanı', ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    </span>

                                                    <span class="d-flex align-items-center justify-content-between gap-3 mt-auto">
                                                        <span class="text-secondary small">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                <?php if ($firmPhone !== ''): ?>
                                                                    <path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/>
                                                                <?php else: ?>
                                                                    <path d="M3 21l18 0"/>
                                                                    <path d="M9 8l1 0"/>
                                                                    <path d="M9 12l1 0"/>
                                                                    <path d="M9 16l1 0"/>
                                                                    <path d="M14 8l1 0"/>
                                                                    <path d="M14 12l1 0"/>
                                                                    <path d="M14 16l1 0"/>
                                                                    <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/>
                                                                <?php endif; ?>
                                                            </svg>
                                                            <?php echo $firmPhone !== '' ? htmlspecialchars($firmPhone, ENT_QUOTES, 'UTF-8') : 'Firma hesabı'; ?>
                                                        </span>
                                                        <span class="company-action">
                                                            Seç
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                <path d="M5 12l14 0"/>
                                                                <path d="M15 8l4 4"/>
                                                                <path d="M15 16l4 -4"/>
                                                            </svg>
                                                        </span>
                                                    </span>
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="selection-note d-flex align-items-center justify-content-center gap-2 text-secondary small mt-4 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3l8 4v5c0 5 -3.5 7.5 -8 9c-4.5 -1.5 -8 -4 -8 -9v-5l8 -4"/>
                                    <path d="M9 12l2 2l4 -4"/>
                                </svg>
                                Yalnızca yetkili olduğunuz firmalar listelenir.
                            </div>
                        <?php else: ?>
                            <div class="card text-center shadow-sm">
                                <div class="card-body py-6">
                                    <span class="avatar avatar-xl bg-primary-lt mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M3 21l18 0"/>
                                            <path d="M9 8l1 0m-1 4l1 0m-1 4l1 0m4 -8l1 0m-1 4l1 0m-1 4l1 0"/>
                                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/>
                                        </svg>
                                    </span>
                                    <h2 class="mb-2">Firma bulunamadı</h2>
                                    <p class="text-secondary mb-0">Hesabınıza tanımlı aktif bir firma bulunmuyor.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </main>
                </div>
            </div>

            <footer class="footer footer-transparent py-3">
                <div class="container-xl">
                    <div class="text-center text-secondary small">
                        Puantor <span class="mx-1">·</span> Güvenli firma seçimi
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="./dist/js/tabler.min.js?1692870487" defer></script>
    <script src="./dist/js/demo.min.js?1692870487" defer></script>
</body>

</html>
