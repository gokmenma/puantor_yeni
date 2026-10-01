<?php

require_once ROOT . '/Model/MailIslemleriModel.php';
require_once ROOT . '/Model/SettingsModel.php';

if ((int) ($_SESSION['user']->superadmin ?? 0) !== 1) {
    echo '<div class="container-xl py-5"><div class="alert alert-danger"><i class="ti ti-lock me-2"></i>Bu sayfaya erişim yetkiniz yok.</div></div>';
    return;
}

$mailModel = new MailIslemleriModel();
$systemUsers = $mailModel->getSystemUsers();
$settingsModel = new SettingsModel();
$smtpHost = (string) ($settingsModel->getSystemSetting('smtp_host') ?? 'mail.puantor.com.tr');
$smtpPort = (int) ($settingsModel->getSystemSetting('smtp_port') ?? 465);
$infoEmail = (string) ($settingsModel->getSystemSetting('smtp_info_username') ?? 'bilgi@puantor.com.tr');
$supportEmail = (string) ($settingsModel->getSystemSetting('smtp_support_username') ?? 'destek@puantor.com.tr');
$serverReady = $smtpHost !== '' && $smtpPort > 0;
$infoReady = $serverReady && filter_var($infoEmail, FILTER_VALIDATE_EMAIL) !== false;
$supportReady = $serverReady && filter_var($supportEmail, FILTER_VALIDATE_EMAIL) !== false;
$csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
$giftTemplatePath = ROOT . '/mail-templates/1-ay-hediye-abonelik.html';
$giftTemplateHtml = is_file($giftTemplatePath) ? (string) file_get_contents($giftTemplatePath) : '';
$giftStartDate = date('d.m.Y');
$giftEndDate = date('d.m.Y', strtotime('+1 month'));
$requestHost = preg_replace('/[^a-zA-Z0-9.:-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'www.puantor.com.tr'));
$requestScheme = function_exists('puantorIsHttps') && puantorIsHttps() ? 'https' : 'http';
$requestBaseUrl = $requestScheme . '://' . $requestHost;
$giftTemplateHtml = str_replace(
    [
        '{{BASLANGIC_TARIHI}}',
        '{{BITIS_TARIHI}}',
        'https://www.puantor.com.tr/sign-in.php',
    ],
    [
        $giftStartDate,
        $giftEndDate,
        $requestBaseUrl . '/sign-in.php',
    ],
    $giftTemplateHtml
);
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'mail-summary-collapsed',
            localStorage.getItem('puantor_mail_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
/* Mail Sayfası Standart Stilleri */
#mailIslemleriPage {
    position: relative;
}

html.mail-summary-collapsed #mailSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}

#mailSummaryCards {
    transition: max-height 0.28s ease, opacity 0.22s ease, transform 0.22s ease, margin-bottom 0.22s ease;
    max-height: 320px;
    overflow: hidden;
}

.mail-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .06) !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.mail-summary-card:hover {
    box-shadow: 0 4px 12px rgba(15, 23, 42, .09) !important;
}

.mail-header-action,
.mail-header-icon-action {
    height: 32px !important;
    min-height: 32px !important;
    padding: 0 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    border-radius: 6px !important;
    line-height: 1 !important;
}
.mail-header-icon-action {
    width: 32px !important;
    padding: 0 !important;
}
.mail-header-icon-action i {
    font-size: 18px !important;
}

/* Tablo Kartı */
.mail-table-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, .07) !important;
    overflow: hidden;
}

.mail-table-card .card-header {
    border-bottom: none !important;
    padding: 10px 14px;
}

.btn-card-header-add {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    background: rgba(32, 107, 196, 0.1);
    color: #206bc4;
    text-decoration: none;
    transition: all 0.15s ease;
}
.btn-card-header-add:hover {
    background: #206bc4;
    color: #fff;
}

.mail-search-wrap {
    position: relative;
    height: 32px !important;
}
#mail-fast-search, #inboxSearch {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 28px 4px 32px !important;
    font-size: 12.5px;
    border-radius: 6px;
    line-height: 1.25;
}
.mail-search-clear {
    position: absolute;
    top: 50%;
    right: 6px;
    z-index: 3;
    display: inline-flex;
    width: 20px;
    height: 20px;
    padding: 0;
    align-items: center;
    justify-content: center;
    transform: translateY(-50%);
    border: 0;
    border-radius: 50%;
    color: #64748b;
    background: #f1f5f9;
    cursor: pointer;
}
.mail-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.mail-summary-toggle {
    width: 32px !important;
    height: 32px !important;
    min-height: 32px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 6px !important;
}
.mail-summary-toggle i {
    transition: transform 0.2s ease;
}
html.mail-summary-collapsed .mail-summary-toggle i {
    transform: rotate(180deg);
}

/* Tablo Kenar Boşluğu & Bütünleşik Çerçeve */
.mail-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px 8px !important;
    overflow-x: auto !important;
}

table#mailHistoryTable,
table#inboxTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}

table#mailHistoryTable thead th,
table#inboxTable thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 11.5px !important;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}
table#mailHistoryTable thead th:last-child,
table#inboxTable thead th:last-child {
    border-right: none !important;
}

table#mailHistoryTable tbody td,
table#inboxTable tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#mailHistoryTable tbody td:last-child,
table#inboxTable tbody td:last-child {
    border-right: none !important;
}
table#mailHistoryTable tbody tr:last-child td,
table#inboxTable tbody tr:last-child td {
    border-bottom: none !important;
}
table#mailHistoryTable tbody tr:hover td,
table#inboxTable tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Aksiyon Butonları */
.mail-action-btn {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.mail-action-btn i {
    font-size: 13px !important;
}

/* Gelen Kutusu Özel */
#inboxTable .inbox-message-row {
    cursor: pointer;
}
#inboxTable .inbox-message-row.is-unread td {
    background: rgba(32, 107, 196, .06) !important;
    font-weight: 600;
}
#inboxTable .inbox-action-cell {
    position: relative;
    min-width: 120px;
}
#inboxTable .inbox-row-actions {
    position: absolute;
    top: 50%;
    right: 0.5rem;
    display: flex;
    gap: 2px;
    padding: 2px;
    background: #ffffff;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-50%);
    transition: opacity .15s ease;
}
#inboxTable .inbox-message-row:hover .inbox-row-actions,
#inboxTable .inbox-message-row:focus-within .inbox-row-actions {
    opacity: 1;
    pointer-events: auto;
}
#inboxTable .inbox-message-row:hover .inbox-message-size,
#inboxTable .inbox-message-row:focus-within .inbox-message-size {
    visibility: hidden;
}
#inboxTable .inbox-row-action {
    width: 28px;
    height: 28px;
    min-width: 28px;
    padding: 0;
    color: #475569;
    background: transparent;
    border: 0;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
#inboxTable .inbox-row-action:hover {
    color: #1e293b;
    background: #e2e8f0;
}

/* Modallar */
.mail-modal .modal-content {
    border-radius: 14px !important;
    border: 0 !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    overflow: hidden;
}
.mail-modal .modal-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 1.5rem;
}
.mail-modal .modal-footer {
    border-top: 1px solid #e2e8f0;
    padding: 0.75rem 1.5rem;
    background: #f8fafc;
}
#mailDetailBodyFrame, #inboxMessageFrame {
    width: 100%;
    min-height: 380px;
    border: 0;
    background: #ffffff;
}

/* Sağ Tık Bağlam Menüsü */
.custom-context-menu {
    position: fixed;
    z-index: 1060;
    background: #ffffff;
    border: 1px solid #dbe3ec;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14);
    padding: 4px;
    min-width: 200px;
    display: none;
}
.custom-context-menu .cm-header {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}
.custom-context-menu .cm-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    font-size: 12.5px;
    color: #1e293b;
    text-decoration: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.12s ease;
}
.custom-context-menu .cm-item:hover {
    background: #f1f5f9;
    color: #206bc4;
}
.custom-context-menu .cm-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 4px 0;
}
.context-menu-active td {
    background-color: rgba(32, 107, 196, 0.08) !important;
}

/* Nav Pills Özel */
.mail-nav-pills .nav-link {
    font-size: 13px;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 8px;
    color: #64748b;
    transition: all 0.15s ease;
}
.mail-nav-pills .nav-link.active {
    background: #206bc4;
    color: #ffffff;
}
</style>

<div class="container-xl mt-1" id="mailIslemleriPage">

    <!-- Page Header -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-mail-forward" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Mail İşlemleri
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Toplu ve bireysel e-posta gönderimi, IMAP gelen kutusu ve gönderim geçmişi yönetimi
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Sütunlar Butonu -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon mail-header-icon-action" id="mailColvisDropdownBtn" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="mailColvisMenu" style="min-width: 200px; max-height: 350px; overflow-y: auto;">
                            <!-- Dinamik Sütun Listesi -->
                        </div>
                    </div>

                    <!-- Birincil Aksiyon: Yeni Mail Gönder -->
                    <button type="button" class="btn btn-sm btn-dark shadow-sm mail-header-action" data-bs-toggle="modal" data-bs-target="#mailComposeModal">
                        <i class="ti ti-plus me-1"></i> Yeni Mail Gönder
                    </button>

                    <!-- İşlemler Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle mail-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0)" id="btnRefreshAll" class="dropdown-item">
                                <i class="ti ti-refresh icon me-2 text-primary"></i> Sayfayı Yenile
                            </a>
                            <a href="javascript:void(0)" id="btnOpenGiftCompose" class="dropdown-item">
                                <i class="ti ti-gift icon me-2 text-warning"></i> Hediye Şablonu ile Mail Gönder
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/ayarlar?view=system&tab=smtp" class="dropdown-item">
                                <i class="ti ti-server-cog icon me-2 text-secondary"></i> SMTP & IMAP Ayarları
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP Uyarı Kutusu -->
    <?php if (!$infoReady && !$supportReady): ?>
        <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
            <i class="ti ti-alert-triangle fs-2 me-2"></i>
            <div class="flex-fill">Mail sunucusu veya gönderen hesapları henüz yapılandırılmamış. SMTP ekranından ayarları kontrol edin.</div>
            <a href="/ayarlar?view=system&tab=smtp" class="btn btn-warning btn-sm ms-2">SMTP Ayarları</a>
        </div>
    <?php endif; ?>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="mailSummaryCards">
        <!-- Kart 1: Toplam Gönderim -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border mail-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM GÖNDERİM</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-mail-forward" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" id="mailStatTotal" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        —
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-top-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">Tüm Kayıtlar</span>
                        <span class="badge bg-secondary-lt fw-semibold" style="font-size: 10px; padding: 3px 8px;">Toplam</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Başarılı Teslimat -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border mail-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BAŞARILI TESLİMAT</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-circle-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-success" id="mailStatSuccess" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        —
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-top-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">İletilen Alıcılar</span>
                        <span class="badge bg-success-lt fw-semibold" style="font-size: 10px; padding: 3px 8px;">Teslim Edildi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Başarısız / Hata -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border mail-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BAŞARISIZ / HATA</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-alert-circle" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-danger" id="mailStatFailed" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        —
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-top-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">Ulaşılamayan Alıcı</span>
                        <span class="badge bg-danger-lt fw-semibold" style="font-size: 10px; padding: 3px 8px;">Hata</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Bugün Alıcı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border mail-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BUGÜN ALICI</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-calendar" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-info" id="mailStatToday" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        —
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-top-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">Bugünkü Trafik</span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px; padding: 3px 8px;"><?= date('d.m.Y') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Görünüm Sekmeleri (Nav Pills) -->
    <div class="card card-sm mb-3 border-0 bg-transparent shadow-none">
        <div class="card-body p-0">
            <ul class="nav nav-pills mail-nav-pills gap-2" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active shadow-sm" id="sent-mails-tab" data-bs-toggle="tab" data-bs-target="#sent-mails-pane" type="button" role="tab">
                        <i class="ti ti-send me-1"></i> Gönderilenler (Geçmiş)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link shadow-sm" id="inbox-mails-tab" data-bs-toggle="tab" data-bs-target="#inbox-mails-pane" type="button" role="tab">
                        <i class="ti ti-inbox me-1"></i> Gelen Kutusu (IMAP)
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab İçerikleri -->
    <div class="tab-content">
        <!-- SEKME 1: Gönderilenler Geçmişi -->
        <div class="tab-pane fade show active" id="sent-mails-pane" role="tabpanel" aria-labelledby="sent-mails-tab">
            <div class="card mail-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(32, 107, 196, 0.08); color: #206bc4;">
                            <i class="ti ti-list" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Gönderim Geçmişi</h4>
                                <a href="javascript:void(0)" class="btn-card-header-add" data-bs-toggle="modal" data-bs-target="#mailComposeModal" title="Yeni Mail Gönder">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Gönderilen mailleri ve alıcı bazındaki sonuçları izleyin.</p>
                        </div>
                    </div>

                    <!-- Sağ Arama ve Toggle Butonları -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <div class="input-icon mail-search-wrap" style="min-width: 200px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="mail-fast-search" class="form-control form-control-sm" placeholder="Gönderimlerde ara..." autocomplete="off">
                            <button type="button" id="mail-search-clear" class="mail-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleMailSummary" class="btn btn-sm btn-outline-secondary btn-icon mail-summary-toggle" title="Özet kartlarını gizle/göster" aria-label="Özet kartlarını gizle veya göster">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Tablo Kapsayıcısı -->
                <div class="table-responsive mail-table-area">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="mailHistoryTable">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Gönderen</th>
                                <th>Alıcı Türü</th>
                                <th>Konu</th>
                                <th>Sonuç</th>
                                <th>Durum</th>
                                <th class="text-end" style="width: 70px;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SEKME 2: Gelen Kutusu (IMAP) -->
        <div class="tab-pane fade" id="inbox-mails-pane" role="tabpanel" aria-labelledby="inbox-mails-tab">
            <div class="card mail-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(32, 107, 196, 0.08); color: #206bc4;">
                            <i class="ti ti-inbox" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Gelen Kutusu</h4>
                            <p class="text-muted mb-0 font-11" id="inboxAccountLabel" style="font-size: 11.5px; line-height: 1.2;">IMAP üzerinden gelen mailler</p>
                        </div>
                    </div>

                    <!-- Sağ Kontroller -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <select class="form-select form-select-sm" id="inboxAccount" style="width: auto; min-width: 220px; height: 32px;" aria-label="Gelen kutusu hesabı">
                            <option value="info">Bilgilendirme — <?php echo htmlspecialchars($infoEmail, ENT_QUOTES, 'UTF-8'); ?></option>
                            <option value="support">Destek — <?php echo htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                        <div class="input-icon mail-search-wrap" style="min-width: 180px;">
                            <span class="input-icon-addon"><i class="ti ti-search text-muted"></i></span>
                            <input type="search" class="form-control form-control-sm" id="inboxSearch" autocomplete="off" placeholder="Gelen kutusunda ara...">
                            <button type="button" id="inbox-search-clear" class="mail-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" id="inboxRefresh" title="Yenile" style="width: 32px; height: 32px;">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>

                <!-- Gelen Kutusu Tablosu -->
                <div class="table-responsive mail-table-area">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="inboxTable">
                        <thead>
                            <tr>
                                <th style="width: 36px; text-align: center;"></th>
                                <th>Gönderen</th>
                                <th>Konu</th>
                                <th>Tarih</th>
                                <th class="text-end" style="width: 130px;">Boyut</th>
                            </tr>
                        </thead>
                        <tbody id="inboxRows">
                            <tr><td colspan="5" class="text-center text-secondary py-5">Gelen Kutusu sekmesini açtığınızda mailler yüklenecek.</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Gelen Kutusu Sayfalama -->
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-between py-2 px-3 bg-transparent border-0">
                    <div class="text-secondary small font-12" id="inboxPaginationInfo">—</div>
                    <div class="btn-list">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="inboxPrevious" disabled><i class="ti ti-chevron-left me-1"></i>Önceki</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="inboxNext" disabled>Sonraki<i class="ti ti-chevron-right ms-1"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODALLAR ==================== -->

<!-- MODAL 1: Yeni Mail Gönder -->
<div class="modal modal-blur fade mail-modal" id="mailComposeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary">
                        <i class="ti ti-mail-forward" style="font-size: 20px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.15rem;">Yeni Mail Gönder</h4>
                        <div class="text-secondary small">Kullanıcılara veya harici adreslere güvenli e-posta iletimi</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <!-- Modal Sekmeleri -->
            <div class="bg-light-subtle border-bottom px-4 pt-2">
                <ul class="nav nav-tabs nav-tabs-alt border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-compose-general" data-bs-toggle="tab" data-bs-target="#compose-general-pane" type="button" role="tab">
                            <i class="ti ti-mail me-1"></i> Mesaj & Alıcılar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-compose-templates" data-bs-toggle="tab" data-bs-target="#compose-templates-pane" type="button" role="tab">
                            <i class="ti ti-template me-1"></i> Hazır Şablonlar
                        </button>
                    </li>
                </ul>
            </div>

            <form id="mailComposeForm" class="d-flex flex-column flex-grow-1 overflow-hidden">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="send">

                <div class="modal-body p-4 bg-white overflow-y-auto">
                    <div class="tab-content">
                        <!-- TAB 1: Genel Mesaj & Alıcılar -->
                        <div class="tab-pane fade show active" id="compose-general-pane" role="tabpanel">
                            <div class="alert alert-info d-flex align-items-center py-2 px-3 mb-3">
                                <i class="ti ti-info-circle fs-3 me-2"></i>
                                <div class="small">Her alıcıya ayrı mail gönderilir. Alıcılar birbirlerinin e-posta adreslerini göremez ve iletim raporları bağımsız kaydedilir.</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label required">Gönderen Hesabı</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-server"></i></span>
                                        <select class="form-select" name="gonderen_hesabi" id="mailSenderAccount" required>
                                            <option value="info" <?php echo $infoReady ? '' : 'disabled'; ?>>Bilgilendirme — <?php echo htmlspecialchars($infoReady ? $infoEmail : 'Yapılandırılmamış', ENT_QUOTES, 'UTF-8'); ?></option>
                                            <option value="support" <?php echo $supportReady ? '' : 'disabled'; ?>>Destek — <?php echo htmlspecialchars($supportReady ? $supportEmail : 'Yapılandırılmamış', ENT_QUOTES, 'UTF-8'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Alıcı Hedef Türü</label>
                                    <div class="form-selectgroup w-100">
                                        <label class="form-selectgroup-item flex-fill">
                                            <input type="radio" name="alici_turu" value="secili" class="form-selectgroup-input" checked>
                                            <span class="form-selectgroup-label text-center py-1.5"><i class="ti ti-user-check me-1"></i>Seçili Kullanıcılar</span>
                                        </label>
                                        <label class="form-selectgroup-item flex-fill">
                                            <input type="radio" name="alici_turu" value="tumu" class="form-selectgroup-input">
                                            <span class="form-selectgroup-label text-center py-1.5"><i class="ti ti-users me-1"></i>Tümü (<?php echo count($systemUsers); ?>)</span>
                                        </label>
                                        <label class="form-selectgroup-item flex-fill">
                                            <input type="radio" name="alici_turu" value="harici" class="form-selectgroup-input">
                                            <span class="form-selectgroup-label text-center py-1.5"><i class="ti ti-world me-1"></i>Harici</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Seçili Kullanıcılar Alanı -->
                            <div class="mb-3" id="selectedUsersArea">
                                <label class="form-label required">Sistem Kullanıcıları</label>
                                <select class="form-select" name="kullanici_ids[]" id="mailSystemUsers" multiple style="width:100%">
                                    <?php foreach ($systemUsers as $systemUser): ?>
                                        <option value="<?php echo (int) $systemUser->id; ?>">
                                            <?php
                                            $optionText = $systemUser->full_name . ' — ' . $systemUser->email;
                                            if (!empty($systemUser->firm_name)) {
                                                $optionText .= ' (' . $systemUser->firm_name . ')';
                                            }
                                            echo htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Harici E-postalar Alanı -->
                            <div class="mb-3 d-none" id="externalEmailsArea">
                                <label class="form-label required">Harici E-posta Adresleri</label>
                                <textarea class="form-control" name="harici_emailler" rows="2" autocomplete="off" placeholder="ornek@firma.com, diger@firma.com"></textarea>
                                <div class="form-hint" style="font-size: 11.5px;">Adresleri virgül, noktalı virgül veya yeni satır ile ayırabilirsiniz.</div>
                            </div>

                            <!-- Konu Alanı -->
                            <div class="mb-3">
                                <label class="form-label required">E-posta Konusu</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-heading"></i></span>
                                    <input type="text" class="form-control" name="konu" maxlength="255" autocomplete="off" required placeholder="Mail konusu girin...">
                                </div>
                            </div>

                            <!-- İçerik Summernote -->
                            <div>
                                <label class="form-label required mb-2">Mesaj İçeriği</label>
                                <textarea class="form-control" name="icerik" id="mailBody" rows="8"></textarea>
                            </div>
                        </div>

                        <!-- TAB 2: Hazır Şablonlar -->
                        <div class="tab-pane fade" id="compose-templates-pane" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border border-primary-subtle bg-light-subtle h-100">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span class="avatar bg-primary-lt text-primary rounded-2"><i class="ti ti-gift"></i></span>
                                                <div>
                                                    <h4 class="card-title mb-0 fw-bold">1 Aylık Hediye Abonelik</h4>
                                                    <span class="text-muted small">Promosyon & Tanıtım Şablonu</span>
                                                </div>
                                            </div>
                                            <p class="text-secondary small mb-3">
                                                Başlangıç: <strong><?= $giftStartDate ?></strong>, Bitiş: <strong><?= $giftEndDate ?></strong> tarihleriyle formatlanmış hazır HTML e-posta tasarımı.
                                            </p>
                                            <button type="button" class="btn btn-sm btn-primary w-100" id="btnLoadGiftTemplate">
                                                <i class="ti ti-file-import me-1"></i> Bu Şablonu Yükle ve Kullan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top">
                    <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold" id="mailSendButton">
                        <i class="ti ti-send me-1"></i> Maili Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: Gönderim Detayı -->
<div class="modal modal-blur fade mail-modal" id="mailDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md rounded-3 bg-info-lt text-info">
                        <i class="ti ti-file-text" style="font-size: 20px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.15rem;">Gönderim Detayı</h4>
                        <div class="text-secondary small" id="mailDetailSummary">Yükleniyor...</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <div class="bg-light-subtle border-bottom px-4 pt-2">
                <ul class="nav nav-tabs nav-tabs-alt border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-detail-recipients" data-bs-toggle="tab" data-bs-target="#detail-recipients-pane" type="button" role="tab">
                            <i class="ti ti-users me-1"></i> Alıcılar & İletim Durumu
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-detail-body" data-bs-toggle="tab" data-bs-target="#detail-body-pane" type="button" role="tab">
                            <i class="ti ti-mail me-1"></i> Gönderilen Mail İçeriği
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-0 bg-white">
                <div class="tab-content">
                    <!-- Tab: Alıcılar -->
                    <div class="tab-pane fade show active" id="detail-recipients-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Alıcı</th>
                                        <th>E-posta</th>
                                        <th>Durum</th>
                                        <th>Gönderilme Tarihi</th>
                                    </tr>
                                </thead>
                                <tbody id="mailDetailRecipients">
                                    <tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab: İçerik iframe -->
                    <div class="tab-pane fade" id="detail-body-pane" role="tabpanel">
                        <iframe id="mailDetailBodyFrame" sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" title="Gönderilen mail içeriği"></iframe>
                    </div>
                </div>
            </div>

            <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top">
                <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-outline-primary px-3 fw-semibold ms-auto" id="btnReuseMailContent">
                    <i class="ti ti-copy me-1"></i> Bu İçeriği Yeniden Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: Gelen Kutusu Mesaj Detayı -->
<div class="modal modal-blur fade mail-modal" id="inboxMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <div class="avatar avatar-md rounded-3 bg-azure-lt text-azure">
                        <i class="ti ti-mail-opened" style="font-size: 20px;"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="modal-title fw-bold text-dark mb-0 text-truncate" id="inboxMessageSubject" style="font-size: 1.15rem;">Mail Detayı</h4>
                        <div class="text-secondary small text-truncate" id="inboxMessageMeta">Yükleniyor...</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <div class="modal-body p-0 bg-white">
                <div id="inboxMessageLoading" class="text-center py-5">
                    <span class="spinner-border spinner-border-sm me-2"></span>Mail yükleniyor...
                </div>
                <div id="inboxMessageContent" class="d-none">
                    <div class="border-bottom px-4 py-2.5 bg-light-subtle d-flex flex-wrap gap-3 small">
                        <div><span class="text-secondary fw-semibold">Kimden:</span> <span id="inboxMessageFrom" class="fw-bold text-dark"></span></div>
                        <div><span class="text-secondary fw-semibold">Kime:</span> <span id="inboxMessageTo"></span></div>
                        <div class="ms-auto"><span class="text-secondary fw-semibold">Tarih:</span> <span id="inboxMessageDate"></span></div>
                    </div>
                    <iframe id="inboxMessageFrame" sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" title="Mail içeriği"></iframe>
                    <div class="border-top px-4 py-3 bg-light-subtle d-none" id="inboxAttachments"></div>
                </div>
            </div>

            <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="inboxToggleSeen"><i class="ti ti-mail me-1"></i>Okunmadı Yap</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="inboxDelete"><i class="ti ti-trash me-1"></i>Sil</button>
                <button type="button" class="btn btn-primary btn-sm ms-auto" id="inboxReply"><i class="ti ti-arrow-back-up me-1"></i>Yanıtla</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== SAĞ TIK BAĞLAM MENÜSÜ ==================== -->
<div id="mailContextMenu" class="custom-context-menu">
    <div class="cm-header" id="cmSubjectTitle">Mail İşlemi</div>
    <a href="javascript:void(0)" class="cm-item" id="cmActionDetail">
        <i class="ti ti-eye text-primary"></i> Detayları Görüntüle
    </a>
    <a href="javascript:void(0)" class="cm-item" id="cmActionReuse">
        <i class="ti ti-copy text-info"></i> Bu Maili Yeniden Gönder
    </a>
</div>

<!-- ==================== JAVASCRIPT LOGIC ==================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const apiUrl = '/api/mail-islemleri/index.php';
    const composeForm = document.getElementById('mailComposeForm');
    const sendButton = document.getElementById('mailSendButton');
    const csrfToken = composeForm.querySelector('input[name="csrf_token"]').value;
    const giftTemplateHtml = <?php echo json_encode($giftTemplateHtml, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    let inboxPage = 1;
    let inboxPageCount = 1;
    let inboxLoaded = false;
    let inboxSearchTimer = null;
    let currentInboxMessage = null;
    let lastLoadedDetailData = null;

    // --- Select2 & Summernote Başlatımı ---
    $('#mailSystemUsers').select2({
        dropdownParent: $('#mailComposeModal'),
        placeholder: 'Kullanıcı seçin...',
        closeOnSelect: false,
        width: '100%'
    });

    $('#mailBody').summernote({
        height: 240,
        lang: 'tr-TR',
        placeholder: 'Mail içeriğinizi buraya yazın...',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link']],
            ['view', ['codeview']]
        ]
    });

    // --- Yardımcı Fonksiyonlar ---
    const escapeHtml = function (value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    };

    const formatDate = function (value) {
        if (!value) return '—';
        const parts = String(value).split(/[-T :]/);
        return parts.length >= 5 ? `${parts[2]}.${parts[1]}.${parts[0]} ${parts[3]}:${parts[4]}` : escapeHtml(value);
    };

    const statusBadge = function (status) {
        const statuses = {
            tamamlandi: ['success', 'Tamamlandı'],
            kismi: ['warning', 'Kısmi'],
            basarisiz: ['danger', 'Başarısız'],
            gonderiliyor: ['azure', 'Gönderiliyor'],
            basarili: ['success', 'Başarılı'],
            bekliyor: ['secondary', 'Bekliyor']
        };
        const item = statuses[status] || ['secondary', status || '—'];
        return `<span class="badge bg-${item[0]}-lt">${escapeHtml(item[1])}</span>`;
    };

    const recipientLabels = {
        secili: 'Seçili Kullanıcılar',
        tumu: 'Tüm Sistem Kullanıcıları',
        harici: 'Harici Adresler'
    };

    function formatBytes(bytes) {
        const value = Number(bytes) || 0;
        if (value < 1024) return `${value} B`;
        if (value < 1048576) return `${(value / 1024).toFixed(1)} KB`;
        return `${(value / 1048576).toFixed(1)} MB`;
    }

    // --- DataTable Başlatımı ---
    const table = $('#mailHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: apiUrl, data: { action: 'list' } },
        pageLength: 25,
        order: [],
        dom: '<"top">rt<"bottom d-flex flex-wrap align-items-center justify-content-between p-2"lip><"clear">',
        columns: [
            { data: 'created_at', render: formatDate },
            {
                data: null,
                render: function (data) {
                    return `<div><strong>${escapeHtml(data.gonderen_adi || '—')}</strong></div><div class="text-secondary font-11">${escapeHtml(data.gonderen_email)}</div>`;
                }
            },
            { data: 'alici_turu', render: function (value) { return `<span class="badge bg-secondary-lt">${escapeHtml(recipientLabels[value] || value)}</span>`; } },
            { data: 'konu', render: function (value) { return `<span class="fw-semibold" title="${escapeHtml(value)}">${escapeHtml(value)}</span>`; } },
            {
                data: null,
                searchable: false,
                render: function (data) {
                    return `<span class="text-success fw-bold"><i class="ti ti-check me-1"></i>${Number(data.basarili_sayisi)}</span><span class="text-danger ms-2 fw-bold"><i class="ti ti-x me-1"></i>${Number(data.basarisiz_sayisi)}</span><span class="text-secondary ms-1 small">/ ${Number(data.toplam_alici)}</span>`;
                }
            },
            { data: 'durum', searchable: false, render: statusBadge },
            {
                data: 'id',
                searchable: false,
                orderable: false,
                className: 'text-end',
                render: function (id) {
                    return `<button type="button" class="btn btn-sm btn-outline-primary mail-action-btn mail-detail-button" data-id="${Number(id)}" title="Detayları Görüntüle" aria-label="Detayları Görüntüle"><i class="ti ti-eye"></i></button>`;
                }
            }
        ],
        language: {
            url: 'src/tr.json'
        },
        initComplete: function () {
            buildColvisMenu();
        }
    });

    // --- Sütun Görünürlüğü (Colvis) Menüsü ---
    function buildColvisMenu() {
        const colvisMenu = document.getElementById('mailColvisMenu');
        if (!colvisMenu) return;
        colvisMenu.innerHTML = '';
        table.columns().every(function (index) {
            const header = this.header();
            const title = header.textContent.trim();
            if (!title || index === 6) return; // İşlem sütunu hariç

            const isVisible = this.visible();
            const item = document.createElement('label');
            item.className = 'dropdown-item d-flex align-items-center gap-2 cursor-pointer mb-1';
            item.innerHTML = `
                <input type="checkbox" class="form-check-input m-0 colvis-checkbox" data-col="${index}" ${isVisible ? 'checked' : ''}>
                <span class="font-12">${escapeHtml(title)}</span>
            `;
            colvisMenu.appendChild(item);
        });

        $(colvisMenu).find('.colvis-checkbox').on('change', function () {
            const colIdx = Number(this.dataset.col);
            const isChecked = this.checked;
            table.column(colIdx).visible(isChecked);
        });
    }

    // --- Hızlı Arama & Temizleme ---
    const fastSearchInput = document.getElementById('mail-fast-search');
    const fastSearchClear = document.getElementById('mail-search-clear');

    fastSearchInput?.addEventListener('input', function () {
        const val = this.value;
        if (fastSearchClear) fastSearchClear.classList.toggle('d-none', !val);
        table.search(val).draw();
    });

    fastSearchClear?.addEventListener('click', function () {
        if (fastSearchInput) {
            fastSearchInput.value = '';
            fastSearchInput.focus();
            this.classList.add('d-none');
            table.search('').draw();
        }
    });

    // --- İstatistikleri Yükle ---
    function loadStats() {
        fetch(`${apiUrl}?action=stats`)
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'success') return;
                document.getElementById('mailStatTotal').textContent = Number(data.stats.toplam_gonderim || 0).toLocaleString('tr-TR');
                document.getElementById('mailStatSuccess').textContent = Number(data.stats.basarili_mail || 0).toLocaleString('tr-TR');
                document.getElementById('mailStatFailed').textContent = Number(data.stats.basarisiz_mail || 0).toLocaleString('tr-TR');
                document.getElementById('mailStatToday').textContent = Number(data.stats.bugun_alici || 0).toLocaleString('tr-TR');
            })
            .catch(() => {});
    }

    // --- Özet Kartları Toggle ---
    const toggleSummaryBtn = document.getElementById('toggleMailSummary');
    toggleSummaryBtn?.addEventListener('click', function () {
        const isCollapsed = document.documentElement.classList.toggle('mail-summary-collapsed');
        try {
            localStorage.setItem('puantor_mail_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
    });

    // --- Genel Yenile Butonu ---
    document.getElementById('btnRefreshAll')?.addEventListener('click', function () {
        table.ajax.reload(null, false);
        loadStats();
        if (inboxLoaded) loadInbox(false);
        Swal.fire({ icon: 'success', title: 'Yenilendi', timer: 1000, showConfirmButton: false });
    });

    // --- Şablon Yükleme Aksiyonları ---
    function applyGiftTemplate() {
        composeForm.querySelector('[name="konu"]').value = "Puantor'dan Size 1 Aylık Hediye Abonelik 🎁";
        $('#mailBody').summernote('code', giftTemplateHtml);
        const generalTabBtn = document.getElementById('tab-compose-general');
        if (generalTabBtn) bootstrap.Tab.getOrCreateInstance(generalTabBtn).show();
        Swal.fire({
            icon: 'success',
            title: 'Şablon Yüklendi',
            text: 'Mesaj alanı hazırlandı. Alıcıları seçerek gönderebilirsiniz.',
            timer: 2000,
            showConfirmButton: false
        });
    }

    document.getElementById('btnLoadGiftTemplate')?.addEventListener('click', applyGiftTemplate);

    document.getElementById('btnOpenGiftCompose')?.addEventListener('click', function () {
        applyGiftTemplate();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('mailComposeModal')).show();
    });

    // --- Alıcı Türü Değişimi ---
    document.querySelectorAll('input[name="alici_turu"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const type = document.querySelector('input[name="alici_turu"]:checked').value;
            document.getElementById('selectedUsersArea').classList.toggle('d-none', type !== 'secili');
            document.getElementById('externalEmailsArea').classList.toggle('d-none', type !== 'harici');
        });
    });

    // --- Mail Gönderim Formu Submit ---
    composeForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const type = document.querySelector('input[name="alici_turu"]:checked').value;
        if (type === 'secili' && !$('#mailSystemUsers').val().length) {
            Swal.fire({ icon: 'warning', title: 'Alıcı Seçin', text: 'En az bir sistem kullanıcısı seçmelisiniz.' });
            return;
        }
        if ($('#mailBody').summernote('isEmpty')) {
            Swal.fire({ icon: 'warning', title: 'Mesaj Gerekli', text: 'Mail içeriğini yazmalısınız.' });
            return;
        }

        sendButton.disabled = true;
        sendButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gönderiliyor...';
        try {
            const response = await fetch(apiUrl, { method: 'POST', body: new FormData(composeForm) });
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message || 'Gönderim başarısız.');
            Swal.fire({ icon: 'success', title: 'Gönderim Tamamlandı', text: data.message });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mailComposeModal')).hide();
            composeForm.reset();
            $('#mailSystemUsers').val(null).trigger('change');
            $('#mailBody').summernote('reset');
            document.getElementById('selectedUsersArea').classList.remove('d-none');
            document.getElementById('externalEmailsArea').classList.add('d-none');
            table.ajax.reload(null, false);
            loadStats();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Mail Gönderilemedi', text: error.message || 'Bağlantı hatası oluştu.' });
        } finally {
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="ti ti-send me-1"></i> Maili Gönder';
        }
    });

    // --- Gönderim Detayını Aç ---
    async function openMailDetail(id) {
        const body = document.getElementById('mailDetailRecipients');
        const contentFrame = document.getElementById('mailDetailBodyFrame');
        document.getElementById('mailDetailSummary').textContent = 'Gönderim bilgileri yükleniyor...';
        contentFrame.srcdoc = '<p style="color:#667382;font-family:Arial,sans-serif;padding:16px">Mail içeriği yükleniyor...</p>';
        body.innerHTML = '<tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...</td></tr>';
        
        // İlk sekmeye dön
        const firstTab = document.getElementById('tab-detail-recipients');
        if (firstTab) bootstrap.Tab.getOrCreateInstance(firstTab).show();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('mailDetailModal')).show();

        try {
            const response = await fetch(`${apiUrl}?action=detail&id=${id}`);
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message);
            lastLoadedDetailData = data;
            document.getElementById('mailDetailSummary').textContent = `${data.send.konu} · ${data.send.toplam_alici} alıcı`;
            contentFrame.srcdoc = data.send.icerik || '<p style="color:#667382;font-family:Arial,sans-serif;padding:16px">Mail içeriği bulunamadı.</p>';
            body.innerHTML = data.recipients.map(function (recipient) {
                return `<tr><td><strong>${escapeHtml(recipient.alici_adi || '—')}</strong></td><td>${escapeHtml(recipient.email)}</td><td>${statusBadge(recipient.durum)}</td><td>${formatDate(recipient.gonderilme_tarihi)}</td></tr>`;
            }).join('') || '<tr><td colspan="4" class="text-center text-secondary py-4">Alıcı kaydı yok.</td></tr>';
        } catch (error) {
            document.getElementById('mailDetailSummary').textContent = 'Detay yüklenemedi';
            contentFrame.srcdoc = '<p style="color:#d63939;font-family:Arial,sans-serif;padding:16px">Mail içeriği yüklenemedi.</p>';
            body.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${escapeHtml(error.message || 'Detay yüklenemedi.')}</td></tr>`;
        }
    }

    $('#mailHistoryTable').on('click', '.mail-detail-button', function () {
        const id = Number(this.dataset.id);
        if (id > 0) openMailDetail(id);
    });

    // --- Detaydan Mail İçeriğini Yeniden Kullanma ---
    document.getElementById('btnReuseMailContent')?.addEventListener('click', function () {
        if (!lastLoadedDetailData || !lastLoadedDetailData.send) return;
        const send = lastLoadedDetailData.send;
        composeForm.reset();
        $('#mailSystemUsers').val(null).trigger('change');
        composeForm.querySelector('[name="konu"]').value = send.konu || '';
        $('#mailBody').summernote('code', send.icerik || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('mailDetailModal')).hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('mailComposeModal')).show();
    });

    // --- Sağ Tık Bağlam Menüsü (Context Menu) ---
    const contextMenu = document.getElementById('mailContextMenu');
    let contextActiveRowId = null;
    let contextActiveSubject = '';

    $('#mailHistoryTable tbody').on('contextmenu', 'tr', function (e) {
        e.preventDefault();
        const rowData = table.row(this).data();
        if (!rowData) return;

        contextActiveRowId = Number(rowData.id);
        contextActiveSubject = rowData.konu || 'Mail';

        $('#mailHistoryTable tbody tr').removeClass('context-menu-active');
        $(this).addClass('context-menu-active');

        document.getElementById('cmSubjectTitle').textContent = contextActiveSubject;

        const posX = Math.min(e.pageX, window.innerWidth - 220);
        const posY = Math.min(e.pageY, window.innerHeight - 150);

        contextMenu.style.left = posX + 'px';
        contextMenu.style.top = posY + 'px';
        contextMenu.style.display = 'block';
    });

    document.addEventListener('click', function (e) {
        if (!contextMenu.contains(e.target)) {
            contextMenu.style.display = 'none';
            $('#mailHistoryTable tbody tr').removeClass('context-menu-active');
        }
    });

    window.addEventListener('scroll', function () { contextMenu.style.display = 'none'; }, true);
    window.addEventListener('resize', function () { contextMenu.style.display = 'none'; });

    document.getElementById('cmActionDetail')?.addEventListener('click', function () {
        contextMenu.style.display = 'none';
        if (contextActiveRowId > 0) openMailDetail(contextActiveRowId);
    });

    document.getElementById('cmActionReuse')?.addEventListener('click', async function () {
        contextMenu.style.display = 'none';
        if (contextActiveRowId > 0) {
            try {
                const response = await fetch(`${apiUrl}?action=detail&id=${contextActiveRowId}`);
                const data = await response.json();
                if (data.status === 'success' && data.send) {
                    composeForm.reset();
                    $('#mailSystemUsers').val(null).trigger('change');
                    composeForm.querySelector('[name="konu"]').value = data.send.konu || '';
                    $('#mailBody').summernote('code', data.send.icerik || '');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('mailComposeModal')).show();
                }
            } catch (e) {}
        }
    });

    // ==================== GELEN KUTUSU (IMAP) ====================
    async function loadInbox(resetPage) {
        if (resetPage) inboxPage = 1;
        const account = document.getElementById('inboxAccount').value;
        const search = document.getElementById('inboxSearch').value.trim();
        const rows = document.getElementById('inboxRows');
        rows.innerHTML = '<tr><td colspan="5" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Gelen kutusu yükleniyor...</td></tr>';
        document.getElementById('inboxPrevious').disabled = true;
        document.getElementById('inboxNext').disabled = true;

        try {
            const query = new URLSearchParams({ action: 'inbox', account: account, page: inboxPage, per_page: 25, search: search });
            const response = await fetch(`${apiUrl}?${query.toString()}`);
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message || 'Gelen kutusu yüklenemedi.');

            inboxPage = Number(data.page) || 1;
            inboxPageCount = Number(data.page_count) || 1;
            document.getElementById('inboxAccountLabel').textContent = `${data.account_email} · ${Number(data.total)} mail`;
            document.getElementById('inboxPaginationInfo').textContent = `${Number(data.total)} mail · Sayfa ${inboxPage} / ${inboxPageCount}`;
            document.getElementById('inboxPrevious').disabled = inboxPage <= 1;
            document.getElementById('inboxNext').disabled = inboxPage >= inboxPageCount;

            rows.innerHTML = data.rows.map(function (message) {
                const sender = message.from_name || message.from_email || 'Bilinmeyen Gönderen';
                const answered = message.answered ? '<i class="ti ti-arrow-back-up text-primary ms-1" title="Yanıtlandı"></i>' : '';
                const seenLabel = message.seen ? 'Okunmadı Yap' : 'Okundu Yap';
                const seenIcon = message.seen ? 'ti-mail' : 'ti-mail-opened';
                const replyDisabled = message.from_email ? '' : ' disabled';
                return `<tr class="inbox-message-row ${message.seen ? '' : 'is-unread'}" data-uid="${Number(message.uid)}" data-seen="${message.seen ? '1' : '0'}" data-from-email="${escapeHtml(message.from_email || '')}" data-subject="${escapeHtml(message.subject || '(Konu yok)')}">
                    <td class="text-center">${message.seen ? '<i class="ti ti-mail-opened text-secondary"></i>' : '<i class="ti ti-mail text-primary"></i>'}</td>
                    <td><div><strong>${escapeHtml(sender)}</strong>${answered}</div><div class="text-secondary font-11">${escapeHtml(message.from_email || '')}</div></td>
                    <td><span class="fw-semibold">${escapeHtml(message.subject || '(Konu yok)')}</span></td>
                    <td class="text-nowrap">${formatDate(message.date)}</td>
                    <td class="text-end text-nowrap inbox-action-cell">
                        <span class="inbox-message-size text-muted small">${formatBytes(message.size)}</span>
                        <div class="inbox-row-actions">
                            <button type="button" class="btn btn-icon inbox-row-action" data-action="seen" title="${seenLabel}" aria-label="${seenLabel}"><i class="ti ${seenIcon}"></i></button>
                            <button type="button" class="btn btn-icon inbox-row-action" data-action="reply" title="Yanıtla" aria-label="Yanıtla"${replyDisabled}><i class="ti ti-arrow-back-up"></i></button>
                            <button type="button" class="btn btn-icon inbox-row-action" data-action="delete" title="Sil" aria-label="Sil"><i class="ti ti-trash text-danger"></i></button>
                        </div>
                    </td>
                </tr>`;
            }).join('') || '<tr><td colspan="5" class="text-center text-secondary py-5">Bu gelen kutusunda mail bulunamadı.</td></tr>';
            inboxLoaded = true;
        } catch (error) {
            rows.innerHTML = `<tr><td colspan="5" class="text-center py-5"><div class="text-danger mb-2"><i class="ti ti-alert-circle me-1"></i>${escapeHtml(error.message || 'Gelen kutusu yüklenemedi.')}</div><a class="btn btn-sm btn-outline-primary" href="/ayarlar?view=system&tab=smtp">IMAP Ayarlarını Kontrol Edin</a></td></tr>`;
            document.getElementById('inboxPaginationInfo').textContent = 'Bağlantı kurulamadı';
        }
    }

    async function openInboxMessage(uid, wasSeen) {
        const account = document.getElementById('inboxAccount').value;
        const modalElement = document.getElementById('inboxMessageModal');
        document.getElementById('inboxMessageLoading').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mail yükleniyor...';
        document.getElementById('inboxMessageLoading').classList.remove('d-none');
        document.getElementById('inboxMessageContent').classList.add('d-none');
        document.getElementById('inboxMessageSubject').textContent = 'Mail yükleniyor...';
        bootstrap.Modal.getOrCreateInstance(modalElement).show();

        try {
            const query = new URLSearchParams({ action: 'message', account: account, uid: uid });
            const response = await fetch(`${apiUrl}?${query.toString()}`);
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message || 'Mail yüklenemedi.');

            const message = data.message_data;
            currentInboxMessage = { account: account, uid: Number(message.uid), from_email: message.from_email, subject: message.subject, seen: wasSeen };
            document.getElementById('inboxMessageSubject').textContent = message.subject || '(Konu yok)';
            document.getElementById('inboxMessageMeta').textContent = `${message.from_name || message.from_email} · ${formatDate(message.date)}`;
            document.getElementById('inboxMessageFrom').textContent = message.from_name && message.from_name !== message.from_email ? `${message.from_name} <${message.from_email}>` : message.from_email;
            document.getElementById('inboxMessageTo').textContent = (message.to || []).map(function (item) { return item.name && item.name !== item.email ? `${item.name} <${item.email}>` : item.email; }).join(', ') || '—';
            document.getElementById('inboxMessageDate').textContent = formatDate(message.date);
            document.getElementById('inboxMessageFrame').srcdoc = message.body || '<p>İçerik yok.</p>';

            const attachments = document.getElementById('inboxAttachments');
            if (message.attachments && message.attachments.length) {
                attachments.classList.remove('d-none');
                attachments.innerHTML = `<div class="fw-semibold mb-2"><i class="ti ti-paperclip me-1"></i>Ekler</div><div class="btn-list">${message.attachments.map(function (attachment) {
                    const query = new URLSearchParams({ account: account, uid: message.uid, part: attachment.part });
                    return `<a class="btn btn-sm btn-outline-secondary" href="/api/mail-islemleri/attachment.php?${query.toString()}"><i class="ti ti-download me-1"></i>${escapeHtml(attachment.filename)} <span class="text-secondary ms-1">(${formatBytes(attachment.size)})</span></a>`;
                }).join('')}</div>`;
            } else {
                attachments.classList.add('d-none');
                attachments.innerHTML = '';
            }

            document.getElementById('inboxMessageLoading').classList.add('d-none');
            document.getElementById('inboxMessageContent').classList.remove('d-none');
            if (wasSeen) {
                document.getElementById('inboxToggleSeen').innerHTML = '<i class="ti ti-mail me-1"></i>Okunmadı Yap';
            } else {
                try {
                    await updateInboxSeen(true);
                } catch (error) {
                    document.getElementById('inboxToggleSeen').innerHTML = '<i class="ti ti-mail-opened me-1"></i>Okundu Yap';
                }
            }
        } catch (error) {
            document.getElementById('inboxMessageSubject').textContent = 'Mail yüklenemedi';
            document.getElementById('inboxMessageLoading').innerHTML = `<div class="text-danger"><i class="ti ti-alert-circle me-1"></i>${escapeHtml(error.message || 'Mail yüklenemedi.')}</div>`;
        }
    }

    async function updateInboxSeen(seen, targetMessage) {
        const message = targetMessage || currentInboxMessage;
        if (!message) return;
        const formData = new FormData();
        formData.append('action', 'seen');
        formData.append('csrf_token', csrfToken);
        formData.append('account', message.account);
        formData.append('uid', message.uid);
        formData.append('seen', seen ? '1' : '0');
        const response = await fetch(apiUrl, { method: 'POST', body: formData });
        const data = await response.json();
        if (data.status !== 'success') throw new Error(data.message || 'Mail durumu güncellenemedi.');
        message.seen = seen;
        if (currentInboxMessage && currentInboxMessage.account === message.account && currentInboxMessage.uid === message.uid) {
            currentInboxMessage.seen = seen;
            document.getElementById('inboxToggleSeen').innerHTML = seen
                ? '<i class="ti ti-mail me-1"></i>Okunmadı Yap'
                : '<i class="ti ti-mail-opened me-1"></i>Okundu Yap';
        }
        const row = document.querySelector(`#inboxRows tr[data-uid="${message.uid}"]`);
        row?.classList.toggle('is-unread', !seen);
        if (row) {
            row.dataset.seen = seen ? '1' : '0';
            row.querySelector('td:first-child').innerHTML = seen
                ? '<i class="ti ti-mail-opened text-secondary"></i>'
                : '<i class="ti ti-mail text-primary"></i>';
            const action = row.querySelector('[data-action="seen"]');
            if (action) {
                const label = seen ? 'Okunmadı Yap' : 'Okundu Yap';
                action.title = label;
                action.setAttribute('aria-label', label);
                action.innerHTML = seen ? '<i class="ti ti-mail"></i>' : '<i class="ti ti-mail-opened"></i>';
            }
        }
    }

    function getInboxRowMessage(row) {
        return {
            account: document.getElementById('inboxAccount').value,
            uid: Number(row.dataset.uid),
            from_email: row.dataset.fromEmail || '',
            subject: row.dataset.subject || '(Konu yok)',
            seen: row.dataset.seen === '1'
        };
    }

    async function deleteInboxMessage(message, deleteButton, closeDetail) {
        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'Mail silinsin mi?',
            text: 'Bu işlem maili sunucudan kalıcı olarak silecek ve geri alınamayacaktır.',
            showCancelButton: true,
            confirmButtonText: 'Evet, sil',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#d63939'
        });
        if (!confirmation.isConfirmed) return;

        deleteButton.disabled = true;
        const originalContent = deleteButton.innerHTML;
        deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        try {
            const formData = new FormData();
            formData.append('action', 'delete_message');
            formData.append('csrf_token', csrfToken);
            formData.append('account', message.account);
            formData.append('uid', message.uid);
            const response = await fetch(apiUrl, { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message || 'Mail silinemedi.');

            if (currentInboxMessage && currentInboxMessage.account === message.account && currentInboxMessage.uid === message.uid) {
                currentInboxMessage = null;
            }
            if (closeDetail) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('inboxMessageModal')).hide();
            }
            await loadInbox(false);
            Swal.fire({ icon: 'success', title: 'Mail Silindi', timer: 1400, showConfirmButton: false });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Mail Silinemedi', text: error.message || 'İşlem sırasında bir hata oluştu.' });
        } finally {
            deleteButton.disabled = false;
            deleteButton.innerHTML = originalContent;
        }
    }

    function replyToInboxMessage(message, closeDetail) {
        if (!message || !message.from_email) return;
        composeForm.reset();
        $('#mailSystemUsers').val(null).trigger('change');
        document.getElementById('mailSenderAccount').value = message.account;
        const externalRadio = composeForm.querySelector('input[name="alici_turu"][value="harici"]');
        externalRadio.checked = true;
        externalRadio.dispatchEvent(new Event('change'));
        composeForm.querySelector('[name="harici_emailler"]').value = message.from_email;
        const subject = /^(re|ynt):/i.test(message.subject) ? message.subject : `Ynt: ${message.subject}`;
        composeForm.querySelector('[name="konu"]').value = subject;
        $('#mailBody').summernote('code', '<p><br></p>');

        const generalTabBtn = document.getElementById('tab-compose-general');
        if (generalTabBtn) bootstrap.Tab.getOrCreateInstance(generalTabBtn).show();

        if (closeDetail) {
            const inboxModalElement = document.getElementById('inboxMessageModal');
            inboxModalElement.addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('mailComposeModal')).show();
            }, { once: true });
            bootstrap.Modal.getOrCreateInstance(inboxModalElement).hide();
        } else {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mailComposeModal')).show();
        }
    }

    document.getElementById('inbox-mails-tab')?.addEventListener('shown.bs.tab', function () {
        if (!inboxLoaded) loadInbox(true);
    });

    document.getElementById('inboxAccount')?.addEventListener('change', function () {
        inboxLoaded = false;
        loadInbox(true);
    });

    document.getElementById('inboxRefresh')?.addEventListener('click', function () {
        loadInbox(false);
    });

    const inboxSearchInput = document.getElementById('inboxSearch');
    const inboxSearchClear = document.getElementById('inbox-search-clear');

    inboxSearchInput?.addEventListener('input', function () {
        const val = this.value;
        if (inboxSearchClear) inboxSearchClear.classList.toggle('d-none', !val);
        clearTimeout(inboxSearchTimer);
        inboxSearchTimer = setTimeout(function () { loadInbox(true); }, 450);
    });

    inboxSearchClear?.addEventListener('click', function () {
        if (inboxSearchInput) {
            inboxSearchInput.value = '';
            inboxSearchInput.focus();
            this.classList.add('d-none');
            loadInbox(true);
        }
    });

    document.getElementById('inboxPrevious')?.addEventListener('click', function () {
        if (inboxPage > 1) {
            inboxPage--;
            loadInbox(false);
        }
    });

    document.getElementById('inboxNext')?.addEventListener('click', function () {
        if (inboxPage < inboxPageCount) {
            inboxPage++;
            loadInbox(false);
        }
    });

    document.getElementById('inboxRows')?.addEventListener('click', async function (event) {
        const row = event.target.closest('.inbox-message-row');
        if (!row) return;
        const actionButton = event.target.closest('.inbox-row-action');
        if (!actionButton) {
            openInboxMessage(Number(row.dataset.uid), row.dataset.seen === '1');
            return;
        }

        event.stopPropagation();
        const message = getInboxRowMessage(row);
        try {
            if (actionButton.dataset.action === 'seen') {
                actionButton.disabled = true;
                await updateInboxSeen(!message.seen, message);
                actionButton.disabled = false;
            } else if (actionButton.dataset.action === 'reply') {
                replyToInboxMessage(message, false);
            } else if (actionButton.dataset.action === 'delete') {
                await deleteInboxMessage(message, actionButton, false);
            }
        } catch (error) {
            actionButton.disabled = false;
            Swal.fire({ icon: 'error', title: 'İşlem Başarısız', text: error.message || 'İşlem sırasında bir hata oluştu.' });
        }
    });

    document.getElementById('inboxToggleSeen')?.addEventListener('click', async function () {
        if (!currentInboxMessage) return;
        try {
            await updateInboxSeen(!currentInboxMessage.seen);
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'İşlem Başarısız', text: error.message });
        }
    });

    document.getElementById('inboxDelete')?.addEventListener('click', async function () {
        if (!currentInboxMessage) return;
        const deleteButton = document.getElementById('inboxDelete');
        await deleteInboxMessage(currentInboxMessage, deleteButton, true);
    });

    document.getElementById('inboxReply')?.addEventListener('click', function () {
        replyToInboxMessage(currentInboxMessage, true);
    });

    // İlk istatistik yüklemesi
    loadStats();
});
</script>
