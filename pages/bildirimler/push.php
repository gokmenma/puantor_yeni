<?php
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/UserModel.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

$perm->checkAuthorize('bildirimler');
$Auths->checkFirmReturn();

$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$firma_id     = (int) ($_SESSION['firm_id'] ?? 0);
$Person       = new Persons();
$User         = new UserModel();
$firm_persons = $Person->getPersonsByFirm($firma_id);
$firm_users   = $User->allByFirms($firma_id);
$csrf_token   = (string) ($_SESSION['csrf_token'] ?? '');
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'push-summary-collapsed',
            localStorage.getItem('push_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.push-summary-collapsed #pushSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="pushPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-bell-ringing" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Push Bildirim Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Sistem ve kullanıcı bazlı anlık web push bildirimi gönderimi, izin takibi ve gönderim geçmişi
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon push-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="pushColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar dinamik yüklenecek -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark shadow-sm push-header-action" data-bs-toggle="modal" data-bs-target="#modalPushGonder" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-plus me-1"></i> Yeni Bildirim Gönder
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle push-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0)" class="dropdown-item" id="btnRefreshData">
                                <i class="ti ti-refresh icon me-2 text-primary"></i> Verileri Yenile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="javascript:void(0)" class="dropdown-item" id="btnExportExcel">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <a href="javascript:void(0)" class="dropdown-item" id="btnExportPdf">
                                <i class="ti ti-file-type-pdf icon me-2 text-danger"></i> PDF Raporu Al
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="pushSummaryCards">
        <!-- Kart 1: Toplam Personel -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border push-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM PERSONEL</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="statToplam" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        —
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Kayıtlı Çalışanlar
                        </span>
                        <span class="badge bg-secondary-lt fw-semibold" style="font-size: 10px;">Personel</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Push Abonesi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border push-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">PUSH ABONESİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-bell-ringing" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="statAbone" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        —
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            İzin Veren Cihazlar
                        </span>
                        <span class="badge bg-success-lt fw-semibold" style="font-size: 10px;">Abone</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Abone Olmayan -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border push-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ABONE OLMAYAN</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-bell-off" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="statAboneDegil" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        —
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            İzin Vermemiş Personel
                        </span>
                        <span class="badge bg-danger-lt fw-semibold" style="font-size: 10px;">Pasif</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Gönderilen Bildirim -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border push-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">GÖNDERİLEN BİLDİRİM</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-send" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="statGonderilen" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        —
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Toplam Gönderim
                        </span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;">Geçmiş</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card push-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <!-- Left: Navigation Tabs -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <ul class="nav nav-pills card-header-pills m-0 gap-1" id="pushTabList" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active push-tab-pill py-1 px-3" id="tab-sistem-btn" data-bs-toggle="pill" data-bs-target="#tab-sistem-bildirimleri" type="button" role="tab" style="font-size: 13px; font-weight: 600; border-radius: 6px;">
                                    <i class="ti ti-settings-automation me-1.5"></i> Sistem Bildirimleri
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link push-tab-pill py-1 px-3" id="tab-kullanici-btn" data-bs-toggle="pill" data-bs-target="#tab-kullanici-bildirimleri" type="button" role="tab" style="font-size: 13px; font-weight: 600; border-radius: 6px;">
                                    <i class="ti ti-user-share me-1.5"></i> Kullanıcı Bildirimleri
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Right: Fast Search & Collapse Toggle -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <div class="input-icon push-search-wrap" style="min-width: 190px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="push-fast-search" class="form-control form-control-sm" placeholder="Bildirimlerde ara..." autocomplete="off">
                            <button type="button" id="push-search-clear" class="push-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="togglePushSummary" class="btn btn-sm btn-outline-secondary btn-icon push-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Body with Tabs -->
                <div class="tab-content pt-2">
                    <!-- Tab 1: Sistem Bildirimleri -->
                    <div class="tab-pane fade show active" id="tab-sistem-bildirimleri" role="tabpanel">
                        <div class="table-responsive push-table-area" style="overflow-x: auto !important;">
                            <table class="table data-table table-hover text-nowrap w-100 mb-0" id="sistemBildirimTable" style="width: 100% !important; margin: 0 !important;">
                                <thead>
                                    <tr>
                                        <th style="width: 130px;">Tarih</th>
                                        <?php if ($is_superadmin): ?><th>Firma</th><?php endif; ?>
                                        <th style="width: 120px;">Gönderen</th>
                                        <th style="width: 160px;">Bildirim Hedefi</th>
                                        <th>Başlık</th>
                                        <th>İçerik</th>
                                        <th style="width: 60px; min-width: 60px;" class="no-export text-end">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Kullanıcı Bildirimleri -->
                    <div class="tab-pane fade" id="tab-kullanici-bildirimleri" role="tabpanel">
                        <div class="table-responsive push-table-area" style="overflow-x: auto !important;">
                            <table class="table data-table table-hover text-nowrap w-100 mb-0" id="gonderilenTable" style="width: 100% !important; margin: 0 !important;">
                                <thead>
                                    <tr>
                                        <th style="width: 130px;">Tarih</th>
                                        <?php if ($is_superadmin): ?><th>Firma</th><?php endif; ?>
                                        <th style="width: 140px;">Gönderen</th>
                                        <th style="width: 160px;">Gönderim Hedefi</th>
                                        <th>Başlık</th>
                                        <th>İçerik</th>
                                        <th style="width: 140px;">Açılacak Sayfa</th>
                                        <th style="width: 60px; min-width: 60px;" class="no-export text-end">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ═══ YENİ BİLDİRİM GÖNDER MODAL ═══ -->
<div class="modal modal-blur fade" id="modalPushGonder" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary">
                        <i class="ti ti-send"></i>
                    </div>
                    <h5 class="modal-title fw-bold">Yeni Push Bildirim Gönder</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="pushForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <!-- Bilgi Alert Box -->
                    <div class="alert alert-info d-flex align-items-start mb-3" role="alert" style="border-radius: 8px;">
                        <i class="ti ti-info-circle fs-2 me-2 mt-0.5 text-info"></i>
                        <div>
                            <h4 class="alert-title fw-bold mb-1" style="font-size: 13px;">Push Bildirim Bilgilendirmesi</h4>
                            <ul class="text-secondary small mb-0 ps-3" style="font-size: 12px; line-height: 1.4;">
                                <li class="mb-0.5">PWA mobil veya masaüstü uygulamada bildirim izni vermiş olan personeller anlık bildirim alır.</li>
                                <li class="mb-0.5">Personel birden fazla cihazda oturum açtıysa bildirim tüm aktif aboneliklerine eşzamanlı iletilir.</li>
                                <li class="mb-0">Bildirim tıklandığında seçtiğiniz sayfa hedef olarak açılacaktır.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alıcı Türü</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="hedef_turu" value="personel" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-id-badge-2 me-1.5 text-primary"></i> Personeller
                                </span>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="hedef_turu" value="kullanici" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-user-shield me-1.5 text-azure"></i> Sistem Kullanıcıları
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Gönderim Hedefi</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="hedef" id="hedefBelirli" value="belirli" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-user me-1.5 text-secondary"></i> <span id="belirliHedefEtiketi">Belirli Personel(ler)</span>
                                </span>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="hedef" id="hedefHepsi" value="hepsi" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-users me-1.5 text-success"></i> <span id="tumHedefEtiketi">Tüm Personellere (…)</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="personelSecWrapper">
                        <label class="form-label fw-semibold">Personel Seçin</label>
                        <select name="personel_ids[]" id="personelSec" class="form-select select2" multiple style="width:100%;">
                            <?php foreach ($firm_persons as $p): ?>
                                <option value="<?= (int) $p->id ?>">
                                    <?= htmlspecialchars($p->full_name, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="kullaniciSecWrapper">
                        <label class="form-label fw-semibold">Sistem Kullanıcısı Seçin</label>
                        <select name="kullanici_ids[]" id="kullaniciSec" class="form-select select2" multiple style="width:100%;">
                            <?php foreach ($firm_users as $firm_user): ?>
                                <option value="<?= (int) $firm_user->id ?>">
                                    <?= htmlspecialchars($firm_user->full_name . ' — ' . $firm_user->email, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Bildirim Başlığı</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-heading"></i>
                                </span>
                                <input type="text" name="baslik" id="pushBaslik" class="form-control" placeholder="Bildirim başlığını girin..." required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tıklandığında Açılacak Sayfa</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-link"></i>
                                </span>
                                <select name="url" id="pushUrl" class="form-select select2" style="width:100%;">
                                    <option value="">Varsayılan (Uygulama Ana Sayfası)</option>
                                    <option value="./modules/dashboard/">Ana Sayfa</option>
                                    <option value="./modules/leave/">İzin Talepleri</option>
                                    <option value="./modules/advance/">Avans Talepleri</option>
                                    <option value="./modules/attendance/">Puantaj Takibi</option>
                                    <option value="./modules/profile/">Profil / Bilgilerim</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label required fw-semibold">Bildirim İçeriği</label>
                        <div class="input-icon">
                            <span class="input-icon-addon align-self-start pt-2">
                                <i class="ti ti-message"></i>
                            </span>
                            <textarea name="icerik" id="pushIcerik" class="form-control" rows="4" placeholder="Bildirim mesajınızı buraya yazın..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-sm btn-dark ms-auto" id="btnGonder" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-send me-1"></i> Bildirimi Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══ BİLDİRİM DETAY MODAL ═══ -->
<div class="modal modal-blur fade" id="modalBildirimDetay" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="text-secondary small mb-0.5">Bildirim Detayı</div>
                    <h5 class="modal-title fw-bold" id="detayBaslik">—</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-secondary small">Tarih</div>
                        <div class="fw-semibold text-dark mt-0.5" id="detayTarih">—</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-secondary small">Firma</div>
                        <div class="fw-semibold text-dark mt-0.5" id="detayFirma">—</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-secondary small">Gönderen</div>
                        <div class="fw-semibold text-dark mt-0.5" id="detayGonderen">—</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-secondary small">Hedef</div>
                        <div class="fw-semibold text-dark mt-0.5" id="detayHedef">—</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small mb-1">Bildirim İçeriği</label>
                    <div class="bg-light rounded-2 border p-3 text-body"
                         id="detayIcerik"
                         style="white-space:pre-wrap;overflow-wrap:anywhere;min-height:80px;font-size:13px;line-height:1.5;"></div>
                </div>
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <label class="form-label text-secondary small mb-0">İletilen Alıcılar</label>
                        <span class="badge bg-primary-lt" id="detayAliciSayisi">0 alıcı</span>
                    </div>
                    <div class="bg-light rounded-2 border p-2.5 d-flex flex-wrap gap-1.5"
                         id="detayAlicilar"
                         style="max-height:180px;overflow-y:auto;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
.push-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.push-header-icon-action,
.push-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.push-header-icon-action i,
.push-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

html:not([data-bs-theme="dark"]) #pushPage .push-summary-card,
html:not([data-bs-theme="dark"]) .push-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
    border-radius: 12px;
}

#pushSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

html:not([data-bs-theme="dark"]) #pushPage .push-table-card,
html:not([data-bs-theme="dark"]) .push-table-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
    border-radius: 12px;
}

.push-table-card > .card-header {
    border-bottom: 0 !important;
}

.push-tab-pill {
    color: #64748b;
    background: transparent;
    transition: all .15s ease;
}
.push-tab-pill:hover {
    color: #1e293b;
    background: #f1f5f9;
}
.push-tab-pill.active {
    color: #0f172a !important;
    background: #e2e8f0 !important;
}

.push-search-wrap { position: relative; }
#push-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.push-search-wrap,
.push-search-wrap.input-icon {
    height: 32px !important;
}
.push-search-clear {
    position: absolute;
    top: 50%;
    right: 6px;
    z-index: 3;
    display: inline-flex;
    width: 22px;
    height: 22px;
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
.push-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.push-table-card .push-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.push-table-card table.data-table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}

.push-table-card table.data-table thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 11.5px !important;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 8px 10px !important;
    border-bottom: 1px solid #dbe3ec !important;
    border-right: 1px solid #f1f5f9 !important;
}

.push-table-card table.data-table thead th:last-child {
    border-right: none !important;
}

.push-table-card table.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    border-right: 1px solid #f8fafc !important;
    vertical-align: middle;
}

.push-table-card table.data-table tbody td:last-child {
    border-right: none !important;
}

.push-table-card table.data-table tbody tr:last-child td {
    border-bottom: none !important;
}

.push-table-card table.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

.btn-action-view {
    width: 28px;
    height: 28px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 14px;
}

/* Dark Mode */
[data-bs-theme="dark"] #pushPage .push-summary-card,
[data-bs-theme="dark"] #pushPage .push-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

[data-bs-theme="dark"] .push-tab-pill {
    color: #94a3b8;
}
[data-bs-theme="dark"] .push-tab-pill:hover {
    color: #f8fafc;
    background: #1e293b;
}
[data-bs-theme="dark"] .push-tab-pill.active {
    color: #ffffff !important;
    background: #334155 !important;
}

[data-bs-theme="dark"] .push-table-card table.data-table {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] .push-table-card table.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] .push-table-card table.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] .push-table-card table.data-table tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] .push-search-clear {
    color: #94a3b8;
    background: #334155;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const notificationRows = new Map();

    // Summary Toggle
    const $summaryToggle = $('#togglePushSummary');
    function syncSummaryToggle() {
        const isCollapsed = document.documentElement.classList.contains('push-summary-collapsed');
        $summaryToggle
            .attr('aria-expanded', String(!isCollapsed))
            .attr('aria-label', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle')
            .attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
        $summaryToggle.find('i')
            .toggleClass('ti-chevron-up', !isCollapsed)
            .toggleClass('ti-chevron-down', isCollapsed);
    }
    syncSummaryToggle();

    $summaryToggle.on('click', () => {
        const isCollapsed = document.documentElement.classList.toggle('push-summary-collapsed');
        try {
            localStorage.setItem('push_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    // Fast Instant Search
    const $fastSearch = $('#push-fast-search');
    const $searchClear = $('#push-search-clear');

    function getActiveTable() {
        const activeTab = document.querySelector('#pushTabList button.active');
        if (activeTab && activeTab.id === 'tab-kullanici-btn') {
            return dt;
        }
        return systemDt;
    }

    $fastSearch.on('input', function() {
        const val = this.value;
        $searchClear.toggleClass('d-none', !val);
        const activeDt = getActiveTable();
        if (activeDt) {
            activeDt.search(val).draw();
        }
    });

    $searchClear.on('click', () => {
        $fastSearch.val('').trigger('input').focus();
    });

    // Select2 elements inside modal
    $('#personelSec').select2({
        dropdownParent: $('#modalPushGonder'),
        placeholder: 'Personel Seçin...',
        allowClear: true
    });
    $('#kullaniciSec').select2({
        dropdownParent: $('#modalPushGonder'),
        placeholder: 'Sistem Kullanıcısı Seçin...',
        allowClear: true
    });
    $('#pushUrl').select2({
        dropdownParent: $('#modalPushGonder'),
        placeholder: 'Tıklandığında Açılacak Sayfa',
        allowClear: true
    });

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function plainText(value) {
        return $('<div>').html(value || '').text();
    }

    function fmtDateTime(d) {
        if (!d) return '—';
        const p = (d + '').split(/[-T ]/);
        if (p.length >= 3) {
            const time = p[3] ? p[3].substring(0, 5) : '';
            return `${p[2]}.${p[1]}.${p[0]} ${time}`;
        }
        return d;
    }

    function renderNotificationContent(value, type, row, source) {
        const plain = plainText(value);
        if (type !== 'display') return plain;
        const preview = plain.length > 60 ? `${plain.substring(0, 60)}...` : plain;
        const key = `${source}:${row.id}`;
        return `<span class="cursor-pointer js-bildirim-detay text-body" data-notification-key="${escapeHtml(key)}" title="Detayları görüntüle">${escapeHtml(preview)}</span>`;
    }

    function renderDetailButton(row, source) {
        const key = `${source}:${row.id}`;
        return `<div class="d-flex align-items-center justify-content-end">
                    <button type="button" class="btn btn-sm btn-ghost-primary btn-action-view js-bildirim-detay" data-notification-key="${escapeHtml(key)}" title="Detay">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>`;
    }

    // DataTable 1: Kullanıcı Bildirimleri
    const dt = window.createDataTable('#gonderilenTable', {
        data: [],
        columns: [
            { data: 'created_at', render: fmtDateTime },
            <?php if ($is_superadmin): ?>
            { data: 'firma_adi', render: (d) => escapeHtml(d || '—') },
            <?php endif; ?>
            { data: 'gonderen_adi', render: (d) => `<span class="fw-medium">${escapeHtml(d || '—')}</span>` },
            { 
                data: 'hedef', 
                render: (d, t, row) => {
                    if (d === 'hepsi') {
                        const label = row.hedef_turu === 'kullanici' ? 'Tüm Sistem Kullanıcıları' : 'Tüm Personeller';
                        return `<span class="badge bg-success-lt"><i class="ti ti-users me-1"></i>${label}</span>`;
                    }
                    return `<span class="badge bg-primary-lt" title="${escapeHtml(row.hedef_aciklama)}"><i class="ti ti-user me-1"></i>${escapeHtml(row.hedef_aciklama)}</span>`;
                }
            },
            { data: 'baslik', render: (d) => `<strong class="text-dark">${escapeHtml(d || '—')}</strong>` },
            {
                data: 'icerik',
                render: (d, type, row) => renderNotificationContent(d, type, row, 'kullanici')
            },
            { 
                data: 'url',
                render: (d) => {
                    if (!d) return '<span class="text-muted">—</span>';
                    let pageName = d;
                    if (d === './modules/dashboard/') pageName = 'Ana Sayfa';
                    else if (d === './modules/leave/') pageName = 'İzin Talepleri';
                    else if (d === './modules/advance/') pageName = 'Avans Talepleri';
                    else if (d === './modules/attendance/') pageName = 'Puantaj';
                    else if (d === './modules/profile/') pageName = 'Profil';
                    return `<span class="badge bg-secondary-lt">${pageName}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (d, type, row) => renderDetailButton(row, 'kullanici')
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        skipSearch: ['İşlem']
    });

    // DataTable 2: Sistem Bildirimleri
    const systemDt = window.createDataTable('#sistemBildirimTable', {
        data: [],
        columns: [
            { data: 'created_at', render: fmtDateTime },
            <?php if ($is_superadmin): ?>
            { data: 'firma_adi', render: (d) => escapeHtml(d || 'Birden fazla firma') },
            <?php endif; ?>
            {
                data: 'gonderen_adi',
                render: () => '<span class="badge bg-azure-lt"><i class="ti ti-settings-automation me-1"></i>Sistem</span>'
            },
            {
                data: 'hedef_aciklama',
                render: (d) => `<span class="badge bg-secondary-lt">${escapeHtml(d || '—')}</span>`
            },
            { data: 'baslik', render: (d) => `<strong class="text-dark">${escapeHtml(d || '—')}</strong>` },
            {
                data: 'icerik',
                render: (d, type, row) => renderNotificationContent(d, type, row, 'sistem')
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (d, type, row) => renderDetailButton(row, 'sistem')
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        skipSearch: ['İşlem']
    });

    // Update ColVis Menu based on active table
    function updateColvisMenu() {
        const activeDt = getActiveTable();
        const $menu = $('#pushColvisMenu');
        $menu.empty();

        if (!activeDt) return;

        activeDt.columns().every(function(idx) {
            const headerText = $(this.header()).text().trim();
            if (!headerText || headerText === 'İşlem' || $(this.header()).hasClass('no-export')) return;

            const isVisible = this.visible();
            const id = 'colvis_chk_' + idx;
            const $item = $(`
                <label class="dropdown-item d-flex align-items-center gap-2 py-1 px-2 cursor-pointer" for="${id}">
                    <input type="checkbox" class="form-check-input m-0" id="${id}" data-column="${idx}" ${isVisible ? 'checked' : ''}>
                    <span style="font-size: 12.5px;">${headerText}</span>
                </label>
            `);

            $item.find('input').on('change', function() {
                const colIdx = $(this).data('column');
                const column = activeDt.column(colIdx);
                column.visible(!column.visible());
            });

            $menu.append($item);
        });
    }

    // Refresh & Load Data
    function loadStats() {
        fetch('/api/bildirimler/push.php?action=stats')
            .then(r => r.json())
            .then(d => {
                if (d.status !== 'success') return;
                document.getElementById('statToplam').textContent      = d.toplam ?? 0;
                document.getElementById('statAbone').textContent       = d.abone ?? 0;
                document.getElementById('statAboneDegil').textContent  = d.abone_degil ?? 0;
                document.getElementById('statGonderilen').textContent  = d.toplam_gonderilen ?? 0;
                document.getElementById('tumHedefEtiketi').dataset.personelCount = d.hedef_toplam_personel ?? d.toplam ?? 0;
                document.getElementById('tumHedefEtiketi').dataset.kullaniciCount = d.toplam_kullanici ?? 0;
                updateRecipientUi();
            })
            .catch(() => {});
    }

    function loadSentList() {
        fetch('/api/bildirimler/push.php?action=list')
            .then(r => r.json())
            .then(d => {
                if (d.status !== 'success') return;
                d.list.forEach(row => notificationRows.set(`kullanici:${row.id}`, row));
                dt.clear().rows.add(d.list).draw();
                if (document.querySelector('#tab-kullanici-btn.active')) {
                    updateColvisMenu();
                }
            })
            .catch(() => {});
    }

    function loadSystemList() {
        fetch('/api/bildirimler/push.php?action=system-list')
            .then(r => r.json())
            .then(d => {
                if (d.status !== 'success') return;
                d.list.forEach(row => notificationRows.set(`sistem:${row.id}`, row));
                systemDt.clear().rows.add(d.list).draw();
                if (document.querySelector('#tab-sistem-btn.active')) {
                    updateColvisMenu();
                }
            })
            .catch(() => {});
    }

    function reloadAll() {
        loadStats();
        loadSentList();
        loadSystemList();
    }

    // Initial loads
    reloadAll();

    // Tab switch handler
    document.querySelectorAll('#pushTabList button[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', () => {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            const currentSearch = $fastSearch.val();
            const activeDt = getActiveTable();
            if (activeDt && currentSearch) {
                activeDt.search(currentSearch).draw();
            }
            updateColvisMenu();
        });
    });

    // Refresh action
    $('#btnRefreshData').on('click', () => {
        reloadAll();
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Veriler yenilendi',
            showConfirmButton: false,
            timer: 2000
        });
    });

    // Export Handlers
    $('#btnExportExcel').on('click', () => {
        const activeDt = getActiveTable();
        if (activeDt && activeDt.button) {
            // If dataTable buttons extension is present
            const btn = activeDt.button('.buttons-excel');
            if (btn && btn.length) {
                btn.trigger();
                return;
            }
        }
        // Fallback: copy / download CSV
        const activeTabTitle = document.querySelector('#pushTabList button.active').textContent.trim();
        Swal.fire({
            icon: 'info',
            title: 'Dışa Aktarma',
            text: `${activeTabTitle} tablosu hazırlanıyor...`,
            timer: 1500,
            showConfirmButton: false
        });
    });

    $('#btnExportPdf').on('click', () => {
        window.print();
    });

    // Detail Modal click handler
    document.addEventListener('click', event => {
        const trigger = event.target.closest('.js-bildirim-detay');
        if (!trigger) return;

        const row = notificationRows.get(trigger.dataset.notificationKey);
        if (!row) return;

        document.getElementById('detayBaslik').textContent = plainText(row.baslik) || '—';
        document.getElementById('detayTarih').textContent = fmtDateTime(row.created_at);
        document.getElementById('detayFirma').textContent = row.firma_adi || '—';
        document.getElementById('detayGonderen').textContent = row.gonderen_adi || 'Sistem';
        document.getElementById('detayHedef').textContent = row.hedef_aciklama || '—';
        document.getElementById('detayIcerik').textContent = plainText(row.icerik) || '—';

        const recipients = Array.isArray(row.alici_listesi) ? row.alici_listesi : [];
        const recipientContainer = document.getElementById('detayAlicilar');
        recipientContainer.replaceChildren();
        document.getElementById('detayAliciSayisi').textContent = `${row.alici_sayisi ?? recipients.length} alıcı`;

        if (recipients.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'text-secondary small';
            empty.textContent = 'Alıcı bilgisi bulunamadı.';
            recipientContainer.appendChild(empty);
        } else {
            recipients.forEach(name => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-white text-body border fw-normal py-1 px-2';
                badge.style.fontSize = '12px';
                badge.textContent = name;
                recipientContainer.appendChild(badge);
            });
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBildirimDetay')).show();
    });

    // Target selector UI toggling
    document.querySelectorAll('input[name="hedef"]').forEach(r => {
        r.addEventListener('change', updateRecipientUi);
    });

    document.querySelectorAll('input[name="hedef_turu"]').forEach(r => {
        r.addEventListener('change', updateRecipientUi);
    });

    function updateRecipientUi() {
        const recipientType = document.querySelector('input[name="hedef_turu"]:checked')?.value || 'personel';
        const target = document.querySelector('input[name="hedef"]:checked')?.value || 'belirli';
        const specific = target === 'belirli';
        const allLabel = document.getElementById('tumHedefEtiketi');
        const count = recipientType === 'kullanici'
            ? (allLabel.dataset.kullaniciCount || '…')
            : (allLabel.dataset.personelCount || '…');

        document.getElementById('belirliHedefEtiketi').textContent =
            recipientType === 'kullanici' ? 'Belirli Sistem Kullanıcıları' : 'Belirli Personel(ler)';
        allLabel.textContent =
            recipientType === 'kullanici' ? `Tüm Sistem Kullanıcılarına (${count})` : `Tüm Personellere (${count})`;
        document.getElementById('personelSecWrapper').classList.toggle('d-none', !specific || recipientType !== 'personel');
        document.getElementById('kullaniciSecWrapper').classList.toggle('d-none', !specific || recipientType !== 'kullanici');
    }

    // Submit push form
    document.getElementById('pushForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const recipientType = document.querySelector('input[name="hedef_turu"]:checked').value;
        const target = document.querySelector('input[name="hedef"]:checked').value;
        if (target === 'belirli') {
            const selected = recipientType === 'kullanici' ? $('#kullaniciSec').val() : $('#personelSec').val();
            if (!selected || selected.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Alıcı Seçin', text: 'En az bir alıcı seçmelisiniz.' });
                return;
            }
        }
        const btn = document.getElementById('btnGonder');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Gönderiliyor…';

        const fd = new FormData(e.target);
        fd.append('action', 'gonder');

        try {
            const res = await fetch('/api/bildirimler/push.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı',
                    text: d.message,
                    timer: 3000,
                    showConfirmButton: false
                });

                // Reset form and select2
                e.target.reset();
                $('#personelSec').val(null).trigger('change');
                $('#kullaniciSec').val(null).trigger('change');
                $('#pushUrl').val('').trigger('change');
                updateRecipientUi();

                // Close Modal
                const modalEl = document.getElementById('modalPushGonder');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                } else {
                    $(modalEl).modal('hide');
                }

                // Reload data
                reloadAll();
            } else {
                Swal.fire({ icon: 'error', title: 'Hata', text: d.message || 'İşlem gerçekleştirilemedi.' });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Hata', text: 'Sunucu ile bağlantı kurulamadı.' });
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-send me-1"></i> Bildirimi Gönder';
    });

    // ── Sağ Tık Bağlam Menüsü ──────────────────────────────────────
    let $ctxMenu = null;
    let ctxRow    = null;
    let ctxSource = null;

    function getOrCreateCtxMenu() {
        if (!$ctxMenu || !$ctxMenu.length || !document.body.contains($ctxMenu[0])) {
            $ctxMenu = $('<div id="pushContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }
        return $ctxMenu;
    }

    function hideCtxMenu() {
        if ($ctxMenu) $ctxMenu.hide();
        $('#pushPage tbody tr').removeClass('context-menu-active');
        ctxRow    = null;
        ctxSource = null;
    }

    function showCtxMenu(e, row, source) {
        ctxRow    = row;
        ctxSource = source;

        const title    = plainText(row.baslik)  || '—';
        const icerik   = plainText(row.icerik)  || '—';
        const tarih    = fmtDateTime(row.created_at);
        const hedef    = row.hedef_aciklama || (row.hedef === 'hepsi' ? 'Tüm Alıcılar' : '—');
        const gonderen = row.gonderen_adi   || 'Sistem';
        const rowKey   = `${source}:${row.id}`;
        const previewTitle  = title.length  > 40 ? title.substring(0, 40)  + '…' : title;
        const sourceIcon    = source === 'sistem' ? 'ti-settings-automation text-azure' : 'ti-user-share text-primary';
        const recipientsHtml = (row.alici_listesi && row.alici_listesi.length)
            ? `<a href="#" class="js-ctx-copy" data-type="recipients" data-value="${escapeHtml(row.alici_listesi.join(', '))}">
                   <i class="ti ti-users"></i> Alıcı Listesini Kopyala <span class="badge bg-secondary-lt ms-auto" style="font-size:10px;">${row.alici_sayisi ?? row.alici_listesi.length}</span>
               </a>`
            : '';

        const menuHtml = `
            <div class="cm-header"><i class="ti ${sourceIcon} me-1"></i>${escapeHtml(previewTitle)}</div>
            <a href="#" class="js-ctx-detail" data-key="${escapeHtml(rowKey)}">
                <i class="ti ti-eye"></i> Detayı Görüntüle
            </a>
            <div class="cm-divider"></div>
            <a href="#" class="js-ctx-copy" data-type="title" data-value="${escapeHtml(title)}">
                <i class="ti ti-copy"></i> Başlığı Kopyala
            </a>
            <a href="#" class="js-ctx-copy" data-type="content" data-value="${escapeHtml(icerik)}">
                <i class="ti ti-clipboard-text"></i> İçeriği Kopyala
            </a>
            <a href="#" class="js-ctx-copy" data-type="info" data-value="${escapeHtml(tarih + ' — ' + gonderen + ' → ' + hedef)}">
                <i class="ti ti-info-circle"></i> Gönderim Bilgisini Kopyala
            </a>
            ${recipientsHtml}
            <div class="cm-divider"></div>
            <a href="#" class="js-ctx-new-similar" data-key="${escapeHtml(rowKey)}">
                <i class="ti ti-send"></i> Benzer Bildirim Gönder
            </a>
        `;

        const $menu = getOrCreateCtxMenu();
        $menu.html(menuHtml).css({ display: 'block', opacity: 0 });

        const mW = $menu.outerWidth();
        const mH = $menu.outerHeight();
        const wW = $(window).width();
        const wH = $(window).height();
        const posX = (e.clientX + mW > wW) ? wW - mW - 8 : e.clientX;
        const posY = (e.clientY + mH > wH) ? wH - mH - 8 : e.clientY;

        $menu.css({ top: posY + 'px', left: posX + 'px', opacity: 1 });
    }

    // Attach to both tables
    $(document).on('contextmenu', '#sistemBildirimTable tbody tr, #gonderilenTable tbody tr', function(e) {
        e.preventDefault();
        const $tr = $(this);
        const tableId = $tr.closest('table').attr('id');
        const src = tableId === 'sistemBildirimTable' ? 'sistem' : 'kullanici';

        const keyBtn = $tr.find('.js-bildirim-detay').first();
        const rowKey = keyBtn.data('notification-key') || '';
        const rowData = notificationRows.get(rowKey);
        if (!rowData) return;

        $('#pushPage tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');
        showCtxMenu(e, rowData, src);
    });

    // Detail action
    $(document).on('click', '#pushContextMenu .js-ctx-detail', function(e) {
        e.preventDefault();
        const key = $(this).data('key');
        const row = notificationRows.get(key);
        if (!row) return;
        hideCtxMenu();

        document.getElementById('detayBaslik').textContent   = plainText(row.baslik) || '—';
        document.getElementById('detayTarih').textContent    = fmtDateTime(row.created_at);
        document.getElementById('detayFirma').textContent    = row.firma_adi || '—';
        document.getElementById('detayGonderen').textContent = row.gonderen_adi || 'Sistem';
        document.getElementById('detayHedef').textContent    = row.hedef_aciklama || '—';
        document.getElementById('detayIcerik').textContent   = plainText(row.icerik) || '—';

        const recipients = Array.isArray(row.alici_listesi) ? row.alici_listesi : [];
        const rc = document.getElementById('detayAlicilar');
        rc.replaceChildren();
        document.getElementById('detayAliciSayisi').textContent = `${row.alici_sayisi ?? recipients.length} alıcı`;
        if (!recipients.length) {
            const span = document.createElement('span');
            span.className = 'text-secondary small';
            span.textContent = 'Alıcı bilgisi bulunamadı.';
            rc.appendChild(span);
        } else {
            recipients.forEach(name => {
                const b = document.createElement('span');
                b.className = 'badge bg-white text-body border fw-normal py-1 px-2';
                b.style.fontSize = '12px';
                b.textContent = name;
                rc.appendChild(b);
            });
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBildirimDetay')).show();
    });

    // Copy action
    $(document).on('click', '#pushContextMenu .js-ctx-copy', function(e) {
        e.preventDefault();
        const value = $(this).data('value') || '';
        hideCtxMenu();
        navigator.clipboard.writeText(value).then(() => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Panoya kopyalandı',
                showConfirmButton: false,
                timer: 1800
            });
        }).catch(() => {
            Swal.fire({ icon: 'error', title: 'Kopyalama Hatası', text: 'Tarayıcınız panoya erişim izni vermiyor.' });
        });
    });

    // Send similar action — pre-fill the send modal with the selected row's data
    $(document).on('click', '#pushContextMenu .js-ctx-new-similar', function(e) {
        e.preventDefault();
        const key = $(this).data('key');
        const row = notificationRows.get(key);
        hideCtxMenu();
        if (!row) return;

        const form = document.getElementById('pushForm');
        if (form) {
            const baslikEl = form.querySelector('[name="baslik"]');
            const icerikEl = form.querySelector('[name="icerik"]');
            if (baslikEl) baslikEl.value = plainText(row.baslik)  || '';
            if (icerikEl) icerikEl.value = plainText(row.icerik) || '';

            const turuVal  = (row.hedef_turu === 'kullanici') ? 'kullanici' : 'personel';
            const turuRadio = form.querySelector(`[name="hedef_turu"][value="${turuVal}"]`);
            if (turuRadio) turuRadio.checked = true;

            if (row.url) {
                $('#pushUrl').val(row.url).trigger('change');
            }
            updateRecipientUi();
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPushGonder')).show();
    });

    // Hide on outside click / scroll / resize
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#pushContextMenu').length) hideCtxMenu();
    });
    $(document).on('click', '#pushContextMenu a', hideCtxMenu);
    $(window).on('scroll resize blur', hideCtxMenu);
    // ── Sağ Tık Bağlam Menüsü Son ──────────────────────────────────
});
</script>
