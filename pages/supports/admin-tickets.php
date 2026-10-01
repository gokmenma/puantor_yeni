<?php

require_once 'Model/SupportsModel.php';
require_once 'Model/UserModel.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/security.php';
require_once 'App/Helper/helper.php';

use App\Helper\Date;
use App\Helper\Security;
use App\Helper\Helper;

// Yetki kontrolü (Sadece superadmin girebilir)
if (($_SESSION['user']->superadmin ?? 0) != 1) {
    header("Location: /yetkisiz-erisim");
    exit();
}

$Supports = new SupportsModel();
$UserModel = new UserModel();

$supports = $Supports->getAllSupportsForAdmin();
$allUsers = $UserModel->all();

// İstatistikler
$total_tickets = count($supports);
$open_tickets = 0;
$closed_tickets = 0;
$unread_tickets = 0;

foreach ($supports as $s) {
    if ($s->status == 0) {
        $open_tickets++;
    } else {
        $closed_tickets++;
    }
    if (!empty($s->has_unread)) {
        $unread_tickets++;
    }
}
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'admin-tickets-summary-collapsed',
            localStorage.getItem('admin_tickets_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.admin-tickets-summary-collapsed #adminTicketsSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}

.admin-tickets-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.admin-tickets-header-icon-action,
.admin-tickets-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}

.admin-tickets-header-icon-action i,
.admin-tickets-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

html:not([data-bs-theme="dark"]) #adminTicketsPage .admin-tickets-summary-card,
html:not([data-bs-theme="dark"]) .admin-tickets-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#adminTicketsSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

html:not([data-bs-theme="dark"]) #adminTicketsPage .admin-tickets-table-card,
html:not([data-bs-theme="dark"]) .admin-tickets-table-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.admin-tickets-table-card > .admin-tickets-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.admin-tickets-table-card > .card-header {
    border-bottom: 0 !important;
}

.status-summary-filter { cursor: pointer; }
.status-summary-filter input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.status-summary-filter span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 24px;
    padding: 2px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #64748b;
    background: #fff;
    font-size: 10.5px;
    font-weight: 600;
    transition: all 0.15s ease-in-out;
}
.status-summary-filter input:checked + span {
    color: #0284c7;
    background: #e0f2fe;
    border-color: #0284c7;
}

.admin-tickets-search-wrap {
    position: relative;
    min-width: 180px;
}
.admin-tickets-search-wrap input {
    height: 32px;
    border-radius: 6px;
    font-size: 12.5px;
    padding-right: 26px;
}
.admin-tickets-search-clear {
    position: absolute;
    right: 7px;
    top: 50%;
    transform: translateY(-50%);
    background: #e2e8f0;
    border: 0;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 11px;
    cursor: pointer;
    padding: 0;
}
.admin-tickets-search-clear:hover {
    background: #cbd5e1;
    color: #1e293b;
}

table#adminTicketsTable.dataTable,
table#adminTicketsTable.table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    overflow: hidden !important;
}

table#adminTicketsTable thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.4px !important;
    padding: 8px 10px !important;
    border-bottom: 1px solid #dbe3ec !important;
    border-right: 1px solid #edf2f7 !important;
}
table#adminTicketsTable thead th:last-child {
    border-right: none !important;
}

table#adminTicketsTable tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #334155 !important;
    border-bottom: 1px solid #edf2f7 !important;
    border-right: 1px solid #edf2f7 !important;
    vertical-align: middle !important;
}
table#adminTicketsTable tbody td:last-child {
    border-right: none !important;
}
table#adminTicketsTable tbody tr:last-child td {
    border-bottom: none !important;
}
table#adminTicketsTable tbody tr:hover td {
    background-color: #f1f5f9 !important;
}

/* Custom Context Menu */
.custom-context-menu {
    display: none;
    position: fixed !important;
    z-index: 999999 !important;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.16), 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    padding: 6px 0;
    min-width: 220px;
    backdrop-filter: blur(8px);
    user-select: none;
}
.custom-context-menu .cm-header {
    padding: 6px 14px 8px;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 260px;
}
.custom-context-menu a {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    background: transparent;
    border: none;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.12s ease, color 0.12s ease;
}
.custom-context-menu a:hover {
    background: #f1f5f9;
    color: #206bc4;
}
.custom-context-menu a.cm-danger {
    color: #e11d48;
}
.custom-context-menu a.cm-danger:hover {
    background: #fff1f2;
    color: #be123c;
}
.custom-context-menu i {
    width: 18px;
    font-size: 15px;
    margin-right: 10px;
    text-align: center;
}
.custom-context-menu .cm-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 4px 0;
}
tbody tr.context-menu-active td {
    background-color: rgba(32, 107, 196, 0.08) !important;
}

/* Dark Mode */
[data-bs-theme="dark"] #adminTicketsPage .admin-tickets-summary-card,
[data-bs-theme="dark"] .admin-tickets-summary-card,
[data-bs-theme="dark"] #adminTicketsPage .admin-tickets-table-card,
[data-bs-theme="dark"] .admin-tickets-table-card {
    background: #182433 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}
[data-bs-theme="dark"] table#adminTicketsTable thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#adminTicketsTable.dataTable,
[data-bs-theme="dark"] table#adminTicketsTable.table {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#adminTicketsTable tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#adminTicketsTable tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] .status-summary-filter span {
    color: #94a3b8;
    background: #1e293b;
    border-color: #334155;
}
[data-bs-theme="dark"] .status-summary-filter input:checked + span {
    color: #60a5fa;
    background: rgba(59, 130, 246, .14);
    border-color: #3b82f6;
}
[data-bs-theme="dark"] .admin-tickets-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .custom-context-menu {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}
[data-bs-theme="dark"] .custom-context-menu .cm-header {
    color: #94a3b8;
    border-bottom-color: #334155;
}
[data-bs-theme="dark"] .custom-context-menu a {
    color: #e2e8f0;
}
[data-bs-theme="dark"] .custom-context-menu a:hover {
    background: #334155;
    color: #60a5fa;
}
[data-bs-theme="dark"] .custom-context-menu .cm-divider {
    background: #334155;
}
</style>

<div class="container-xl mt-1" id="adminTicketsPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-headset" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Destek Talepleri Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Sistemdeki tüm kullanıcı destek bildirimleri, durumları, yanıt geçmişi ve yönetim işlemleri
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon admin-tickets-header-icon-action" id="adminTicketsColvisDropdownBtn" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="adminTicketsColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- JS dinamik render -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark shadow-sm admin-tickets-header-action" data-bs-toggle="modal" data-bs-target="#newAdminTicketModal">
                        <i class="ti ti-plus me-1"></i> Yeni Destek Talebi
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle admin-tickets-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm">
                            <a class="dropdown-item" href="javascript:void(0);" id="btn_mark_all_read">
                                <i class="ti ti-checks icon me-2 text-info"></i> Tümünü Okundu İşaretle
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item route-link" href="#" data-page="supports/tickets">
                                <i class="ti ti-messages icon me-2 text-primary"></i> Destek Taleplerim
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="adminTicketsSummaryCards">
        <!-- Kart 1: Toplam Talep -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border admin-tickets-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM TALEP</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-headset" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_tickets, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Açık: <strong class="text-warning"><?= $open_tickets ?></strong> | Kapalı: <strong class="text-success"><?= $closed_tickets ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm talepleri göster">
                            <input type="radio" name="admin_ticket_status_filter" value="" class="status-filter" checked>
                            <span><i class="ti ti-list"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Açık / Yanıt Bekleyen -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border admin-tickets-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">AÇIK / YANIT BEKLEYEN</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-clock" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-warning" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($open_tickets, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Çözüm Bekleyen: <strong class="text-warning"><?= $open_tickets ?> Talep</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece açık talepleri göster">
                            <input type="radio" name="admin_ticket_status_filter" value="Açık" class="status-filter">
                            <span><i class="ti ti-hourglass-low"></i> Açık</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Tamamlanan / Kapalı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border admin-tickets-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TAMAMLANAN / KAPALI</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-circle-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-success" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($closed_tickets, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Çözümlenmiş Bildirimler
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece kapalı talepleri göster">
                            <input type="radio" name="admin_ticket_status_filter" value="Kapalı" class="status-filter">
                            <span><i class="ti ti-check"></i> Kapalı</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Yeni / Okunmamış Yanıtlar -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border admin-tickets-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">YENİ / OKUNMAMIŞ</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-bell-ringing" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-info" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($unread_tickets, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Cevap Bekleyen Yeni Mesajlar
                        </span>
                        <label class="status-summary-filter mb-0" title="Yeni mesajı olan talepleri göster">
                            <input type="radio" name="admin_ticket_status_filter" value="Yeni" class="status-filter">
                            <span><i class="ti ti-bell"></i> Yeni</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Tablo Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card admin-tickets-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-list" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Destek Talepleri Listesi</h4>
                                <span id="selected-tickets-count" class="badge bg-primary-lt d-none font-11">0 seçildi</span>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.2;">Sistemdeki tüm müşteri destek bildirimleri, durumları ve yanıt geçmişi</p>
                        </div>
                    </div>

                    <!-- Aksiyonlar ve Arama -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <button type="button" id="btn-bulk-close" class="btn btn-sm btn-outline-success d-none admin-tickets-header-action shadow-sm">
                            <i class="ti ti-check icon me-1"></i> Seçilenleri Kapat
                        </button>
                        <button type="button" id="btn-bulk-delete" class="btn btn-sm btn-outline-danger d-none admin-tickets-header-action shadow-sm">
                            <i class="ti ti-trash icon me-1"></i> Seçilenleri Sil
                        </button>

                        <!-- Hızlı Arama -->
                        <div class="input-icon admin-tickets-search-wrap">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="admin-tickets-fast-search" class="form-control form-control-sm" placeholder="Talep veya kullanıcı ara..." autocomplete="off">
                            <button type="button" id="admin-tickets-search-clear" class="admin-tickets-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleAdminTicketsSummary" class="btn btn-sm btn-outline-secondary btn-icon admin-tickets-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Tablo Kapsayıcısı -->
                <div class="table-responsive admin-tickets-table-area">
                    <table class="table table-hover text-nowrap w-100 mb-0" id="adminTicketsTable">
                        <thead>
                            <tr>
                                <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false">
                                    <input type="checkbox" id="select-all-admin-tickets" class="form-check-input m-0" style="width: 18px; height: 18px; cursor: pointer;">
                                </th>
                                <th style="width: 75px;" class="text-center">Talep No</th>
                                <th>Kullanıcı</th>
                                <th>Konu & Mesaj</th>
                                <th style="width: 90px;" class="text-center">Yanıt Sayısı</th>
                                <th style="width: 130px;">Son Hareket</th>
                                <th style="width: 110px;">Durum</th>
                                <th style="width: 100px;" class="text-center no-export" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supports as $support):
                                $words = explode(" ", trim($support->user_name ?? ''));
                                $initials = "";
                                foreach ($words as $w) {
                                    $initials .= mb_substr($w, 0, 1, 'UTF-8');
                                }
                                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'));
                                if (empty($initials)) {
                                    $initials = "DK";
                                }

                                $encryptedId = Security::encrypt($support->id);
                                $cleanSubject = htmlspecialchars($support->subject ?? '');
                                $cleanMessagePreview = htmlspecialchars(mb_substr(strip_tags($support->message ?? ''), 0, 80, 'UTF-8'));
                                $cleanUserName = htmlspecialchars($support->user_name ?? 'Bilinmeyen Kullanıcı');
                                $cleanUserEmail = htmlspecialchars($support->user_email ?? '');
                                $cleanUserPhone = htmlspecialchars($support->user_phone ?? '');
                                
                                $messageCount = (int)($support->message_count ?? 1);
                                $lastDate = !empty($support->last_reply_at) ? $support->last_reply_at : $support->created_at;
                                $hasUnread = !empty($support->has_unread);
                                $isClosed = ($support->status == 1);
                            ?>
                                <tr data-id="<?= $support->id ?>" data-encrypted-id="<?= $encryptedId ?>" data-subject="<?= $cleanSubject ?>" data-user="<?= $cleanUserName ?>" data-email="<?= $cleanUserEmail ?>" data-status="<?= $support->status ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input admin-ticket-checkbox m-0" value="<?= $support->id ?>" data-encrypted-id="<?= $encryptedId ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-dark">
                                        #<?= $support->id ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-sm rounded-circle bg-blue-lt text-primary fw-bold" style="width: 32px; height: 32px; font-size: 11px;">
                                                <?= $initials ?>
                                            </span>
                                            <div>
                                                <div class="fw-bold text-dark font-13" style="line-height: 1.2;"><?= $cleanUserName ?></div>
                                                <div class="text-muted small d-flex align-items-center gap-2 mt-0.5" style="font-size: 11px;">
                                                    <?php if ($cleanUserEmail): ?>
                                                        <span><i class="ti ti-mail me-0.5"></i><?= $cleanUserEmail ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($cleanUserPhone): ?>
                                                        <span><i class="ti ti-phone me-0.5"></i><?= $cleanUserPhone ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="#" class="fw-semibold text-dark route-link text-decoration-none d-block font-13" data-page="supports/admin-ticket-view&id=<?= $encryptedId ?>" title="Talebi Görüntüle">
                                            <?= $cleanSubject ?>
                                        </a>
                                        <div class="text-muted small text-truncate mt-0.5" style="max-width: 360px; font-size: 11.5px;" title="<?= $cleanMessagePreview ?>">
                                            <?= $cleanMessagePreview ?: '-' ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-azure-lt fw-semibold" style="font-size: 11px;">
                                            <i class="ti ti-messages me-1"></i><?= $messageCount ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-12 text-dark fw-medium" style="line-height: 1.2;">
                                            <?= date('d.m.Y', strtotime($lastDate)) ?>
                                        </div>
                                        <div class="text-muted small" style="font-size: 10.5px;">
                                            <?= date('H:i', strtotime($lastDate)) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($isClosed): ?>
                                            <span class="badge bg-success-lt fw-semibold font-11">
                                                <i class="ti ti-check me-1"></i>Kapalı
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-lt fw-semibold font-11">
                                                <i class="ti ti-clock me-1"></i>Açık
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($hasUnread): ?>
                                            <span class="badge bg-info-lt fw-semibold font-10 ms-1" title="Yeni kullanıcı mesajı var">
                                                <i class="ti ti-bell me-0.5"></i>Yeni
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <a href="#" class="btn btn-sm btn-icon btn-primary route-link" data-page="supports/admin-ticket-view&id=<?= $encryptedId ?>" title="Görüntüle / Cevapla" style="width: 28px; height: 28px;">
                                                <i class="ti ti-message-dots font-13"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary toggle-status-btn" data-id="<?= $support->id ?>" data-encrypted-id="<?= $encryptedId ?>" data-status="<?= $support->status ?>" title="<?= $isClosed ? 'Talebi Yeniden Aç' : 'Talebi Kapat' ?>" style="width: 28px; height: 28px;">
                                                <i class="ti ti-<?= $isClosed ? 'rotate-2 text-warning' : 'check text-success' ?> font-13"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-ticket-btn" data-id="<?= $support->id ?>" data-encrypted-id="<?= $encryptedId ?>" data-title="<?= $cleanSubject ?>" title="Talebi Sil" style="width: 28px; height: 28px;">
                                                <i class="ti ti-trash font-13"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Yeni Destek Talebi (Tabler ERP Standartlarında) -->
<div class="modal modal-blur fade" id="newAdminTicketModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="border-radius: 14px; overflow: hidden;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-3 px-4 bg-light-subtle border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 42px; height: 42px;">
                        <i class="ti ti-headset" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.2px;">Yeni Destek Talebi Oluştur</h4>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">Kullanıcı adına sisteme yeni bir destek bildirimi veya not kaydedin</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            
            <form id="newAdminTicketForm" autocomplete="off">
                <input type="hidden" name="action" value="saveSupportTicket">
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required fw-semibold" for="ticket_user_id">Kullanıcı Seçimi</label>
                            <select name="user_id" id="ticket_user_id" class="form-select" required style="width: 100%;">
                                <option value="">-- Kullanıcı Seçin --</option>
                                <?php foreach ($allUsers as $u): ?>
                                    <option value="<?= $u->id ?>" data-email="<?= htmlspecialchars($u->email ?? '') ?>">
                                        <?= htmlspecialchars($u->full_name) ?> (<?= htmlspecialchars($u->email ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label required fw-semibold" for="ticket_subject">Destek Konusu</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-heading text-muted"></i>
                                </span>
                                <input type="text" name="subject" id="ticket_subject" class="form-control" placeholder="Örn: Bordro hesaplama hatası veya modül talebi..." required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label required fw-semibold" for="ticket_message">Talep / Mesaj İçeriği</label>
                            <textarea name="message" id="ticket_message" class="form-control" rows="6" placeholder="Kullanıcı bildirimini veya destek detaylarını buraya yazın..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" id="btn-save-admin-ticket" class="btn btn-primary px-4 shadow-sm fw-semibold">
                        <i class="ti ti-device-floppy icon me-1"></i> Talebi Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom Context Menu -->
<div id="customContextMenu" class="custom-context-menu">
    <div class="cm-header">
        <i class="ti ti-headset me-1 text-primary"></i> <span id="cm-ticket-title">Destek Talebi</span>
    </div>
    <a href="#" id="cm-action-view" class="route-link">
        <i class="ti ti-message-dots text-primary"></i> Görüntüle / Cevapla
    </a>
    <a href="javascript:void(0);" id="cm-action-toggle">
        <i class="ti ti-toggle-right text-warning"></i> Durumu Değiştir
    </a>
    <a href="#" id="cm-action-mail">
        <i class="ti ti-mail text-info"></i> Kullanıcıya E-posta Gönder
    </a>
    <div class="cm-divider"></div>
    <a href="javascript:void(0);" id="cm-action-delete" class="cm-danger">
        <i class="ti ti-trash"></i> Destek Talebini Sil
    </a>
</div>

<script>
$(document).ready(function() {

    // Toggle Summary Cards Collapse
    var toggleSummaryBtn = document.getElementById('toggleAdminTicketsSummary');
    if (toggleSummaryBtn) {
        toggleSummaryBtn.addEventListener('click', function () {
            var isCollapsed = document.documentElement.classList.toggle('admin-tickets-summary-collapsed');
            localStorage.setItem('admin_tickets_summary_collapsed', isCollapsed ? '1' : '0');
            this.setAttribute('aria-expanded', !isCollapsed);
            this.querySelector('i').className = isCollapsed ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
            this.title = isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle';
        });

        if (document.documentElement.classList.contains('admin-tickets-summary-collapsed')) {
            toggleSummaryBtn.setAttribute('aria-expanded', 'false');
            toggleSummaryBtn.querySelector('i').className = 'ti ti-chevron-down';
            toggleSummaryBtn.title = 'Özet kartlarını göster';
        }
    }

    // Modal Select2 Init
    $('#newAdminTicketModal').on('shown.bs.modal', function () {
        $('#ticket_user_id').select2({
            dropdownParent: $('#newAdminTicketModal'),
            placeholder: '-- Kullanıcı Seçin --',
            allowClear: true,
            width: '100%'
        });
        $('#ticket_subject').focus();
    });

    // Colvis Menu Render Helper
    function renderColvisMenu(dtApi) {
        var $colvisMenu = $('#adminTicketsColvisMenu');
        $colvisMenu.empty();
        dtApi.columns().every(function(idx) {
            if (idx === 0 || idx === 7) return; // Skip Checkbox and Action
            var headerEl = this.header();
            if (!headerEl) return;
            var headerText = $(headerEl).text().trim();
            if (!headerText) return;
            var isVisible = this.visible();
            var item = $('<label class="dropdown-item d-flex align-items-center gap-2 py-1 px-2 cursor-pointer mb-0 font-12">' +
                '<input type="checkbox" class="form-check-input m-0 colvis-toggle" data-column="' + idx + '" ' + (isVisible ? 'checked' : '') + '>' +
                '<span>' + headerText + '</span>' +
                '</label>');
            $colvisMenu.append(item);
        });

        $colvisMenu.off('change', '.colvis-toggle').on('change', '.colvis-toggle', function (e) {
            e.stopPropagation();
            var colIdx = $(this).data('column');
            var column = dtApi.column(colIdx);
            column.visible($(this).is(':checked'));
        });
    }

    // DataTable Init
    var $ticketsTable = $('#adminTicketsTable');
    var table = $ticketsTable.DataTable({
        language: {
            url: '/src/tr.json'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]],
        order: [[1, 'desc']],
        dom: '<"d-none"f>rt<"d-flex justify-content-between align-items-center p-2 border-top"lip>',
        columnDefs: [
            { orderable: false, targets: [0, 7] }
        ],
        initComplete: function() {
            var api = this.api();
            if (typeof window.initDataTableColumnFilters === 'function') {
                window.initDataTableColumnFilters($('#adminTicketsTable'), api);
            }
            renderColvisMenu(api);
        }
    });

    // Fast Search with Debounce & Clear Button
    var $fastSearch = $('#admin-tickets-fast-search');
    var $searchClear = $('#admin-tickets-search-clear');

    $fastSearch.on('input', function () {
        var val = $(this).val();
        table.search(val).draw();
        $searchClear.toggleClass('d-none', !val);
    });

    $searchClear.on('click', function () {
        $fastSearch.val('').trigger('input').focus();
    });

    // Radio Status Filter
    $('input[name="admin_ticket_status_filter"]').on('change', function () {
        var filterVal = $(this).val();
        // Column 6: Durum sütunu
        table.column(6).search(filterVal ? filterVal : '', true, false).draw();
    });

    // Checkboxes & Bulk UI
    var selectAll = document.getElementById('select-all-admin-tickets');
    var btnBulkClose = document.getElementById('btn-bulk-close');
    var btnBulkDelete = document.getElementById('btn-bulk-delete');
    var selectedCountEl = document.getElementById('selected-tickets-count');

    function getChecked() {
        return document.querySelectorAll('.admin-ticket-checkbox:checked');
    }

    function updateBulkUI() {
        var count = getChecked().length;
        if (count > 0) {
            btnBulkClose.classList.remove('d-none');
            btnBulkDelete.classList.remove('d-none');
            selectedCountEl.classList.remove('d-none');
            selectedCountEl.textContent = count + ' seçildi';
        } else {
            btnBulkClose.classList.add('d-none');
            btnBulkDelete.classList.add('d-none');
            selectedCountEl.classList.add('d-none');
        }
        var allBoxes = document.querySelectorAll('.admin-ticket-checkbox');
        if (selectAll) {
            selectAll.checked = allBoxes.length > 0 && count === allBoxes.length;
            selectAll.indeterminate = count > 0 && count < allBoxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.admin-ticket-checkbox').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateBulkUI();
        });
    }

    $(document).on('change', '.admin-ticket-checkbox', function () {
        updateBulkUI();
    });

    // Bulk Close Action
    $('#btn-bulk-close').on('click', function () {
        var checked = getChecked();
        var ids = Array.from(checked).map(function(cb) { return cb.value; });
        if (ids.length === 0) return;

        Swal.fire({
            title: 'Talepleri Kapat?',
            text: 'Seçilen ' + ids.length + ' adet destek talebini kapatmak istediğinize emin misiniz?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet, Kapat',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#2fb344'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/api/supports/tickets.php', { action: 'bulkCloseTickets', ids: ids }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Başarılı', res.message, 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata', res.message || 'İşlem gerçekleştirilemedi.', 'error');
                    }
                }, 'json');
            }
        });
    });

    // Bulk Delete Action
    $('#btn-bulk-delete').on('click', function () {
        var checked = getChecked();
        var ids = Array.from(checked).map(function(cb) { return cb.value; });
        if (ids.length === 0) return;

        Swal.fire({
            title: 'Talepleri Sil?',
            text: 'Seçilen ' + ids.length + ' adet destek talebi ve tüm mesajları kalıcı olarak silinecektir. Bu işlem geri alınamaz!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Kalıcı Olarak Sil',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#d63939'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/api/supports/tickets.php', { action: 'bulkDeleteTickets', ids: ids }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Silindi', res.message, 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata', res.message || 'Silme işlemi gerçekleştirilemedi.', 'error');
                    }
                }, 'json');
            }
        });
    });

    // Single Toggle Status Action
    $(document).on('click', '.toggle-status-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('encrypted-id');
        var currentStatus = $(this).data('status');
        var newStatus = (currentStatus == 0) ? 1 : 0;
        var actionText = (newStatus == 1) ? 'kapatmak' : 'yeniden açmak';

        Swal.fire({
            title: 'Durumu Değiştir?',
            text: 'Bu destek talebini ' + actionText + ' istediğinize emin misiniz?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet',
            cancelButtonText: 'Vazgeç'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/api/supports/tickets.php', { action: 'toggleTicketStatus', id: id, status: newStatus }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Başarılı', res.message, 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata', res.message || 'Durum güncellenemedi.', 'error');
                    }
                }, 'json');
            }
        });
    });

    // Single Delete Action
    $(document).on('click', '.delete-ticket-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('encrypted-id');
        var title = $(this).data('title') || 'Destek Talebi';

        Swal.fire({
            title: 'Talebi Sil?',
            html: '<strong>' + title + '</strong> başlıklı destek talebi ve tüm mesajları silinecektir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#d63939'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/api/supports/tickets.php', { action: 'deleteTicket', id: id }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Silindi', res.message, 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata', res.message || 'Silme işlemi gerçekleştirilemedi.', 'error');
                    }
                }, 'json');
            }
        });
    });

    // Mark All As Read
    $('#btn_mark_all_read').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Tümünü Okundu Yap?',
            text: 'Tüm destek bildirimleri okundu olarak işaretlenecektir.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet, İşaretle',
            cancelButtonText: 'Vazgeç'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/api/supports/tickets.php', { action: 'markAllAsRead' }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Başarılı', res.message, 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata', res.message || 'İşlem gerçekleştirilemedi.', 'error');
                    }
                }, 'json');
            }
        });
    });

    // New Ticket Form Submit
    $('#newAdminTicketForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btn-save-admin-ticket');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Kaydediliyor...');

        var formData = $(this).serialize();

        $.post('/api/supports/tickets.php', formData, function(res) {
            btn.prop('disabled', false).html('<i class="ti ti-device-floppy icon me-1"></i> Talebi Kaydet');
            if (res.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('newAdminTicketModal')).hide();
                Swal.fire('Başarılı', res.message, 'success').then(function() {
                    location.reload();
                });
            } else {
                Swal.fire('Hata', res.message || 'Destek talebi oluşturulamadı.', 'error');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html('<i class="ti ti-device-floppy icon me-1"></i> Talebi Kaydet');
            Swal.fire('Hata', 'Sunucu ile iletişim kurulurken bir sorun oluştu.', 'error');
        });
    });

    // Custom Context Menu Handler
    var $cm = $('#customContextMenu');
    var activeRowData = null;

    $('#adminTicketsTable tbody').on('contextmenu', 'tr', function(e) {
        e.preventDefault();
        var $row = $(this);
        var id = $row.data('id');
        var encryptedId = $row.data('encrypted-id');
        var subject = $row.data('subject');
        var user = $row.data('user');
        var email = $row.data('email');
        var status = $row.data('status');

        if (!id) return;

        activeRowData = { id: id, encryptedId: encryptedId, subject: subject, user: user, email: email, status: status };

        $('#adminTicketsTable tbody tr').removeClass('context-menu-active');
        $row.addClass('context-menu-active');

        $('#cm-ticket-title').text('#' + id + ' ' + (subject.length > 22 ? subject.substring(0, 22) + '...' : subject));
        $('#cm-action-view').attr('data-page', 'supports/admin-ticket-view&id=' + encryptedId);
        
        if (email) {
            $('#cm-action-mail').attr('href', 'mailto:' + email).removeClass('d-none');
        } else {
            $('#cm-action-mail').addClass('d-none');
        }

        var posX = e.clientX;
        var posY = e.clientY;
        var menuWidth = 230;
        var menuHeight = 180;
        var winWidth = $(window).width();
        var winHeight = $(window).height();

        if (posX + menuWidth > winWidth) posX = winWidth - menuWidth - 10;
        if (posY + menuHeight > winHeight) posY = winHeight - menuHeight - 10;

        $cm.css({ top: posY + 'px', left: posX + 'px' }).fadeIn(100);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $cm.hide();
            $('#adminTicketsTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(window).on('scroll resize', function() {
        $cm.hide();
        $('#adminTicketsTable tbody tr').removeClass('context-menu-active');
    });

    $('#cm-action-toggle').on('click', function(e) {
        e.preventDefault();
        $cm.hide();
        if (!activeRowData) return;
        var newStatus = (activeRowData.status == 0) ? 1 : 0;
        var actionText = (newStatus == 1) ? 'kapatmak' : 'yeniden açmak';

        Swal.fire({
            title: 'Durumu Değiştir?',
            text: 'Bu destek talebini ' + actionText + ' istediğinize emin misiniz?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet',
            cancelButtonText: 'Vazgeç'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/api/supports/tickets.php', { action: 'toggleTicketStatus', id: activeRowData.encryptedId, status: newStatus }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Başarılı', res.message, 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata', res.message || 'Durum güncellenemedi.', 'error');
                    }
                }, 'json');
            }
        });
    });

    $('#cm-action-delete').on('click', function(e) {
        e.preventDefault();
        $cm.hide();
        if (!activeRowData) return;

        Swal.fire({
            title: 'Talebi Sil?',
            html: '<strong>' + activeRowData.subject + '</strong> başlıklı destek talebi silinecektir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#d63939'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('/api/supports/tickets.php', { action: 'deleteTicket', id: activeRowData.encryptedId }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Silindi', res.message, 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata', res.message || 'Silme işlemi gerçekleştirilemedi.', 'error');
                    }
                }, 'json');
            }
        });
    });

});
</script>
