<?php
$user_id = $_SESSION['user']->id;
require_once "Model/MyFirmModel.php";
require_once "App/Helper/security.php";
require_once "App/Helper/helper.php";
require_once "Model/Company.php";
require_once "Model/UserModel.php";

use App\Helper\Security;
use App\Helper\Helper;

$perm->checkAuthorize("my_companies_page");
$Auths->checkFirmReturn();

$MyFirmModel = new MyFirmModel();
$myfirms = $MyFirmModel->getMyFirmByUserId();

$companyObj = new Company();
$User = new UserModel();
$owner_id = $_SESSION["user"]->parent_id == 0 ? $_SESSION["user"]->id : $_SESSION["user"]->parent_id;
$subDetails = $User->getActiveSubscriptionDetails($owner_id);
$current_firm_count = $companyObj->countMyFirms($owner_id);
$isSuperadmin = ($_SESSION["user"]->superadmin ?? 0) == 1;

$limitReached = !$isSuperadmin && ($current_firm_count >= $subDetails['firma_hakki']);
$default_firm_id = (int)($_SESSION['user']->default_firm_id ?? 0);

// Özet İstatistikleri
$total_firms = count($myfirms);
$default_firm_name = '';
$firms_with_contact = 0;

foreach ($myfirms as $mf) {
    if ((int)$mf->id === $default_firm_id) {
        $default_firm_name = $mf->firm_name;
    }
    $hasPhone = !empty($mf->phone) && $mf->phone !== '0';
    $hasEmail = !empty($mf->email) && $mf->email !== '0';
    if ($hasPhone || $hasEmail) {
        $firms_with_contact++;
    }
}
?>

<script>
    // Sayfa render olmadan önce flicker'ı önlemek için gizlilik durumunu uygula
    (function() {
        var isSummaryVisible = localStorage.getItem('mycompany_summary_cards_visible');
        if (isSummaryVisible === 'false') {
            document.write('<style>#mycompanySummaryCards { display: none !important; }</style>');
        }
    })();
</script>

<div class="container-xl mt-3">
    <?php if (isset($_GET['limit_reached']) && $_GET['limit_reached'] == 1): ?>
        <div class="alert alert-warning alert-dismissible mb-3 shadow-sm border-0" role="alert" style="border-radius: 10px; font-weight: 500;">
            <div class="d-flex align-items-center">
                <i class="ti ti-alert-triangle icon me-3 text-warning" style="font-size: 1.5rem;"></i>
                <div>Paketinizin firma limiti dolduğu için yeni firma ekleme sayfasına erişiminiz engellenmiştir.</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    <?php endif; ?>

    <!-- Özet Kartları (Tıklanabilir Toggle Filtreler) -->
    <div id="mycompanySummaryCards" class="summary-cards-wrapper mb-2">
        <div class="row row-cards g-2">
            <!-- 1. Toplam Firma -->
            <div class="col-sm-6 col-lg-3">
                <div class="mycompany-stat-card active" data-filter-type="Tümü" data-tooltip="Tüm Firmaları Göster">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="stat-card-title">TOPLAM FİRMA</div>
                        <div class="stat-card-avatar text-primary">
                            <i class="ti ti-building"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="stat-card-value"><?php echo number_format($total_firms, 0, ',', '.'); ?></div>
                        <span class="badge bg-secondary-lt text-secondary">Tüm Kayıtlar</span>
                    </div>
                    <div class="stat-card-subtext">
                        <span class="text-truncate">Sahip Olduğunuz Şirketler</span>
                    </div>
                </div>
            </div>

            <!-- 2. Varsayılan Firma -->
            <div class="col-sm-6 col-lg-3">
                <div class="mycompany-stat-card" data-filter-type="Varsayılan" data-tooltip="Varsayılan Olarak Ayarlanan Firmayı Filtrele">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="stat-card-title">VARSAYILAN FİRMA</div>
                        <div class="stat-card-avatar text-warning">
                            <i class="ti ti-star"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="stat-card-value text-warning fs-4" style="line-height: 1.2;">
                            <?php echo !empty($default_firm_name) ? htmlspecialchars(Helper::short($default_firm_name, 16), ENT_QUOTES, 'UTF-8') : 'Seçilmedi'; ?>
                        </div>
                        <span class="badge bg-warning-lt text-warning">Varsayılan</span>
                    </div>
                    <div class="stat-card-subtext">
                        <span class="text-truncate"><?php echo !empty($default_firm_name) ? 'Sisteme Giriş Tercihi' : 'Tanımlanmamış'; ?></span>
                    </div>
                </div>
            </div>

            <!-- 3. Firma Hakkı / Limiti -->
            <div class="col-sm-6 col-lg-3">
                <div class="mycompany-stat-card" data-filter-type="Tümü" data-tooltip="Paket Kapsamındaki Firma Kullanımı">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="stat-card-title">FİRMA LİMİTİ</div>
                        <div class="stat-card-avatar text-success">
                            <i class="ti ti-award"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="stat-card-value text-success">
                            <?php echo $isSuperadmin ? 'Sınırsız' : ($current_firm_count . ' / ' . ($subDetails['firma_hakki'] ?? 1)); ?>
                        </div>
                        <span class="badge bg-success-lt text-success">Paket Hakkı</span>
                    </div>
                    <div class="stat-card-subtext">
                        <span class="text-truncate">
                            <?php echo $isSuperadmin ? 'Limitsiz Kullanım' : ('Kalan: ' . max(0, ($subDetails['firma_hakki'] ?? 1) - $current_firm_count) . ' Firma'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- 4. İletişim Bilgisi Tanımlı -->
            <div class="col-sm-6 col-lg-3">
                <div class="mycompany-stat-card" data-filter-type="İletişim" data-tooltip="Telefon/E-posta Tanımlı Olan Firmaları Filtrele">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="stat-card-title">İLETİŞİM KAYITLI</div>
                        <div class="stat-card-avatar text-info">
                            <i class="ti ti-phone-call"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="stat-card-value text-info"><?php echo number_format($firms_with_contact, 0, ',', '.'); ?></div>
                        <span class="badge bg-info-lt text-info">İletişim</span>
                    </div>
                    <div class="stat-card-subtext">
                        <span class="text-truncate">Telefon / E-posta Girilmiş</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Firma Tablo Kartı -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="card-header-icon">
                            <i class="ti ti-building-skyscraper"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Firmalarım Listesi</h4>
                                <?php if ($limitReached): ?>
                                    <a href="#" class="btn-card-header-add btn-new-firm-limit" data-limit="<?php echo $subDetails['firma_hakki']; ?>" data-tooltip="Yeni Firma Ekle">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn-card-header-add" id="btn-new-mycompany-header" data-tooltip="Yeni Firma Ekle">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Sahip olduğunuz firmaların yönetimi, varsayılan firma seçimi ve şirket detayları</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <a href="#" class="btn btn-sm btn-outline-secondary" id="export_excel"
                            data-tooltip="Excele Aktar" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-file-excel text-success me-1"></i> Excel
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                                <i class="ti ti-layout-columns me-1"></i> Sütunlar
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" id="mycompaniesColvisMenu"
                                style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                                <!-- Checkboxlar JS ile dinamik yüklenecek -->
                            </div>
                        </div>
                        <?php if ($limitReached): ?>
                            <button type="button" class="btn btn-sm btn-primary btn-new-firm-limit" data-limit="<?php echo $subDetails['firma_hakki']; ?>" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                                <i class="ti ti-plus me-1"></i> Yeni Firma
                            </button>
                        <?php else: ?>
                            <a href="#" class="btn btn-sm btn-primary" id="btn-new-mycompany" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                                <i class="ti ti-plus me-1"></i> Yeni Firma
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" id="toggleSummaryCards" data-tooltip="Özet Kartlarını Gizle / Göster" style="height: 32px; width: 32px;">
                            <i class="ti ti-chevron-up" id="toggleSummaryIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="padding: 4px !important;">
                    <table class="table data-table table-hover text-nowrap w-100" id="myCompaniesTable" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th style="width: 5%; min-width: 45px;" class="text-center" data-orderable="false">Sıra</th>
                                <th>Firma Adı</th>
                                <th>Yetkili</th>
                                <th>Telefon</th>
                                <th>E-posta</th>
                                <th>Vergi Bilgisi</th>
                                <th>Açıklama</th>
                                <th style="width: 10%; min-width: 110px;">Oluşturulma Tarihi</th>
                                <th class="no-export text-end" style="width: 7%; min-width: 80px;" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 0;
                            foreach ($myfirms as $myfirm): 
                                $i++;
                                $id = Security::encrypt($myfirm->id);
                                $isDefault = ((int)$myfirm->id === $default_firm_id);
                                $hasPhone = !empty($myfirm->phone) && $myfirm->phone !== '0';
                                $hasEmail = !empty($myfirm->email) && $myfirm->email !== '0';
                                $hasContact = ($hasPhone || $hasEmail) ? 'İletişim' : 'Yok';
                                
                                $taxOffice = (!empty($myfirm->tax_office) && $myfirm->tax_office !== '0') ? $myfirm->tax_office : '';
                                $taxNumber = (!empty($myfirm->tax_number) && $myfirm->tax_number !== '0') ? $myfirm->tax_number : '';
                                $taxInfo = trim($taxOffice . ' ' . $taxNumber);

                                $yetkili = (!empty($myfirm->yetkili_adi) && $myfirm->yetkili_adi !== '0') ? $myfirm->yetkili_adi : '';
                                $description = (!empty($myfirm->description) && $myfirm->description !== '0') ? $myfirm->description : '';
                            ?>
                                <tr data-firm-id="<?php echo $id; ?>" 
                                    data-firm-name="<?php echo htmlspecialchars($myfirm->firm_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-is-default="<?php echo $isDefault ? 'Varsayılan' : 'Normal'; ?>"
                                    data-has-contact="<?php echo $hasContact; ?>">
                                    
                                    <td class="text-center fw-medium text-muted"><?php echo $i; ?></td>
                                    
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($myfirm->brand_logo) && $myfirm->brand_logo !== '0' && file_exists('uploads/' . $myfirm->brand_logo)): ?>
                                                <span class="avatar avatar-sm me-2 rounded-2 border" style="background-image: url(/uploads/<?php echo htmlspecialchars($myfirm->brand_logo, ENT_QUOTES, 'UTF-8'); ?>); background-size: contain; background-repeat: no-repeat; background-position: center;"></span>
                                            <?php else: ?>
                                                <span class="avatar avatar-sm me-2 bg-primary-lt text-primary fw-bold rounded-2">
                                                    <?php echo mb_strtoupper(mb_substr($myfirm->firm_name ?? 'F', 0, 1, 'UTF-8'), 'UTF-8'); ?>
                                                </span>
                                            <?php endif; ?>
                                            <div>
                                                <a href="#" class="route-link fw-bold text-primary" data-page="mycompany/manage&id=<?php echo $id; ?>" data-tooltip="Firma Detayları">
                                                    <?php echo htmlspecialchars($myfirm->firm_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                                <?php if ($isDefault): ?>
                                                    <span class="badge bg-amber-lt text-amber fw-medium ms-2" data-tooltip="Varsayılan Şirketiniz">
                                                        <i class="ti ti-star-filled text-warning me-1"></i> Varsayılan
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if (!empty($yetkili)): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-user text-muted me-1 small"></i>
                                                <span><?php echo htmlspecialchars($yetkili, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($hasPhone): ?>
                                            <a href="tel:<?php echo htmlspecialchars($myfirm->phone, ENT_QUOTES, 'UTF-8'); ?>" class="text-body text-decoration-none d-inline-flex align-items-center">
                                                <i class="ti ti-phone text-muted me-1 small"></i>
                                                <span><?php echo htmlspecialchars($myfirm->phone, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($hasEmail): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($myfirm->email, ENT_QUOTES, 'UTF-8'); ?>" class="text-body text-decoration-none d-inline-flex align-items-center">
                                                <i class="ti ti-mail text-muted me-1 small"></i>
                                                <span><?php echo htmlspecialchars($myfirm->email, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($taxInfo)): ?>
                                            <span class="small text-muted font-monospace"><?php echo htmlspecialchars($taxInfo, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($description)): ?>
                                            <span class="text-muted small text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars(Helper::short($description, 35), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($myfirm->created_at)): ?>
                                            <span class="small text-muted">
                                                <i class="ti ti-calendar me-1 small"></i><?php echo date('d.m.Y H:i', strtotime($myfirm->created_at)); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                İşlem
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link" data-page="mycompany/manage&id=<?php echo $id; ?>" href="#">
                                                    <i class="ti ti-eye icon me-2 text-primary"></i> Firma Detayları
                                                </a>
                                                <?php if ($isDefault): ?>
                                                    <a class="dropdown-item btn-unset-default-firm text-muted" data-id="<?php echo $id; ?>" href="#">
                                                        <i class="ti ti-star-off icon me-2 text-secondary"></i> Varsayılanı Kaldır
                                                    </a>
                                                <?php else: ?>
                                                    <a class="dropdown-item btn-set-default-firm text-amber" data-id="<?php echo $id; ?>" href="#">
                                                        <i class="ti ti-star icon me-2 text-warning"></i> Varsayılan Yap
                                                    </a>
                                                <?php endif; ?>
                                                <a class="dropdown-item mycompany-edit-btn" data-id="<?php echo $id; ?>" href="#">
                                                    <i class="ti ti-edit icon me-2 text-warning"></i> Bilgileri Güncelle
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item delete-mycompany text-danger" data-id="<?php echo $id; ?>" href="#">
                                                    <i class="ti ti-trash icon me-2"></i> Firmayı Sil
                                                </a>
                                            </div>
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

<!-- Yeni & Düzenleme Firma Modalı -->
<div class="modal modal-blur fade" id="mycompany-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-surface-primary border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-xs">
                        <i class="ti ti-building-skyscraper fs-2" id="mycompany-modal-icon"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="mycompany-modal-title">Yeni Firma Ekle</h4>
                        <div class="text-secondary small mt-0" id="mycompany-modal-subtitle" style="font-size: 11.5px;">Sisteme yeni şirket tanımlayabilir ve firma detaylarını düzenleyebilirsiniz.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="myFirmForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="id" id="myfirm_id" value="0">
                <input type="hidden" name="action" value="saveMyCompany">
                
                <div class="modal-body p-3 p-md-4">
                    <div class="row g-3">
                        <!-- Sol Kolon: Temel, İletişim ve Vergi Bilgileri -->
                        <div class="col-lg-7 d-flex flex-column gap-3">
                            
                            <!-- Bölüm 1: Temel Bilgiler -->
                            <div class="modal-section-card p-3 rounded-3 border">
                                <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                    <span class="badge bg-primary-lt p-1 rounded-2 me-2">
                                        <i class="ti ti-id fs-3"></i>
                                    </span>
                                    <span class="fw-bold text-uppercase font-11 tracking-wider text-muted">Temel Bilgiler</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label required small fw-semibold mb-1">Firma Adı</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-building text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control" name="firm_name" id="firm_name" placeholder="Örn: ABC Teknoloji San. ve Tic. Ltd. Şti." required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label required small fw-semibold mb-1">Yetkili Adı & Soyadı</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-user-check text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control" name="yetkili_adi" id="yetkili_adi" placeholder="Örn: Ahmet Yılmaz" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bölüm 2: İletişim Bilgileri -->
                            <div class="modal-section-card p-3 rounded-3 border">
                                <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                    <span class="badge bg-success-lt p-1 rounded-2 me-2">
                                        <i class="ti ti-phone-call fs-3"></i>
                                    </span>
                                    <span class="fw-bold text-uppercase font-11 tracking-wider text-muted">İletişim Bilgileri</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold mb-1">Telefon</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-phone text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control" name="phone" id="phone" placeholder="0 (5XX) XXX XX XX">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold mb-1">E-posta</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-mail text-muted"></i>
                                            </span>
                                            <input type="email" class="form-control" name="email" id="email" placeholder="ornek@firma.com">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bölüm 3: Vergi Bilgileri -->
                            <div class="modal-section-card p-3 rounded-3 border">
                                <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                    <span class="badge bg-warning-lt p-1 rounded-2 me-2">
                                        <i class="ti ti-receipt-tax fs-3"></i>
                                    </span>
                                    <span class="fw-bold text-uppercase font-11 tracking-wider text-muted">Vergi & Yasal Bilgiler</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold mb-1">Vergi Dairesi</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-building-bank text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control" name="vergi_dairesi" id="vergi_dairesi" placeholder="Vergi Dairesi Adı">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold mb-1">Vergi Numarası</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <i class="ti ti-file-text text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control font-monospace" name="vergi_no" id="vergi_no" placeholder="Vergi No / TCKN" maxlength="20">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sağ Kolon: Logo ve Açıklama -->
                        <div class="col-lg-5 d-flex flex-column gap-3">
                            <!-- Logo Yükleme Kartı -->
                            <div class="modal-section-card p-3 rounded-3 border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                        <span class="badge bg-info-lt p-1 rounded-2 me-2">
                                            <i class="ti ti-photo fs-3"></i>
                                        </span>
                                        <span class="fw-bold text-uppercase font-11 tracking-wider text-muted">Firma Logosu</span>
                                    </div>

                                    <!-- Modern Logo Upload & Preview Zone -->
                                    <div class="logo-upload-box text-center p-3 rounded-3 mb-3 position-relative" id="logoUploadDropzone">
                                        <div class="logo-preview-container mb-2 mx-auto position-relative" style="width: 80px; height: 80px;">
                                            <div id="logo-preview-placeholder" class="w-100 h-100 rounded-3 border bg-light d-flex align-items-center justify-content-center text-muted shadow-xs">
                                                <i class="ti ti-building-skyscraper fs-1 text-secondary opacity-40"></i>
                                            </div>
                                            <img src="" id="logo-preview-img" class="w-100 h-100 rounded-3 border bg-white p-1 shadow-xs" style="object-fit: contain; display: none;" alt="Firma Logosu">
                                            <button type="button" class="btn btn-sm btn-icon btn-danger position-absolute top-0 end-0 translate-middle rounded-circle shadow-sm" id="btn-remove-logo" style="display: none; width: 22px; height: 22px; padding: 0;" title="Logoyu Kaldır">
                                                <i class="ti ti-x font-10"></i>
                                            </button>
                                        </div>

                                        <div class="small fw-semibold text-dark mb-1">Kurumsal Logo</div>
                                        <p class="text-muted small mb-2" style="font-size: 11px;">PNG, JPG, SVG veya WEBP (Maks. 2MB)</p>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-2" id="btn-browse-logo">
                                            <i class="ti ti-upload me-1"></i> Logo Seç / Yükle
                                        </button>
                                        <input type="file" name="brand_logo" id="brand_logo" class="d-none" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                                    </div>
                                </div>

                                <!-- Firma Açıklaması -->
                                <div>
                                    <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                        <span class="badge bg-purple-lt p-1 rounded-2 me-2">
                                            <i class="ti ti-notes fs-3"></i>
                                        </span>
                                        <span class="fw-bold text-uppercase font-11 tracking-wider text-muted">Ek Açıklama</span>
                                    </div>
                                    <textarea class="form-control" name="description" id="description" rows="3" placeholder="Firma hakkında kısa not veya adres bilgisi..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light-subtle border-top py-2 px-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> İptal
                    </button>
                    <button type="button" class="btn btn-primary px-4 fw-semibold shadow-sm d-inline-flex align-items-center" id="saveMyFirm">
                        <i class="ti ti-device-floppy me-2"></i>
                        <span>Değişiklikleri Kaydet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Özet Kartları Stilleri */
.summary-cards-wrapper {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mycompany-stat-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    padding: 8px 12px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    position: relative;
    user-select: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.mycompany-stat-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}
.mycompany-stat-card.active {
    border-color: #206bc4 !important;
    background: #f8fafc;
    box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.15);
}
[data-bs-theme="dark"] .mycompany-stat-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}
[data-bs-theme="dark"] .mycompany-stat-card:hover {
    border-color: #475569;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
}
[data-bs-theme="dark"] .mycompany-stat-card.active {
    border-color: #3b82f6 !important;
    background: #0f172a;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
}

.stat-card-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
}
.stat-card-value {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
}
[data-bs-theme="dark"] .stat-card-value {
    color: #f8fafc;
}
.stat-card-subtext {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
    line-height: 1.2;
}
.stat-card-avatar {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    font-size: 13px;
}
[data-bs-theme="dark"] .stat-card-avatar {
    background: #334155;
}
.mycompany-stat-card .badge {
    font-size: 10px;
    font-weight: 600;
    padding: 1px 5px;
}

/* Tablo Dış Kenar ve Yuvarlak Köşeler */
#myCompaniesTable_wrapper {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

table#myCompaniesTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    overflow: hidden !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
}

table#myCompaniesTable thead tr:first-child th:first-child { border-top-left-radius: 9px !important; }
table#myCompaniesTable thead tr:first-child th:last-child { border-top-right-radius: 9px !important; }
table#myCompaniesTable tbody tr:last-child td:first-child { border-bottom-left-radius: 9px !important; }
table#myCompaniesTable tbody tr:last-child td:last-child { border-bottom-right-radius: 9px !important; }

/* Başlık hücreleri ve iç kenarlıklar */
table#myCompaniesTable thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 12px !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}
table#myCompaniesTable thead th:last-child {
    border-right: none !important;
}

/* Gövde satırları */
table#myCompaniesTable tbody td {
    padding: 8px 12px !important;
    font-size: 13.5px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#myCompaniesTable tbody td:last-child {
    border-right: none !important;
}
table#myCompaniesTable tbody tr:last-child td {
    border-bottom: none !important;
}
table#myCompaniesTable tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Dark Mode */
[data-bs-theme="dark"] table#myCompaniesTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#myCompaniesTable thead th {
    background: #1e293b !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#myCompaniesTable tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#myCompaniesTable tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}

#myCompaniesTable th:last-child,
#myCompaniesTable td:last-child {
    width: 90px !important;
    min-width: 90px !important;
    text-align: right !important;
    white-space: nowrap;
}

#myCompaniesTable_wrapper .dt-layout-table,
#myCompaniesTable_wrapper .table-responsive {
    overflow-x: auto;
}

/* Context Menu */
.custom-context-menu {
    position: fixed;
    z-index: 1050;
    display: none;
    min-width: 180px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    padding: 6px;
    font-size: 13px;
    user-select: none;
}
[data-bs-theme="dark"] .custom-context-menu {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
}
.custom-context-menu .cm-header {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
[data-bs-theme="dark"] .custom-context-menu .cm-header {
    border-bottom-color: #334155;
    color: #94a3b8;
}
.custom-context-menu a {
    display: flex;
    align-items: center;
    padding: 6px 10px;
    color: #334155;
    text-decoration: none;
    border-radius: 6px;
    transition: background-color 0.15s;
    font-weight: 500;
}
[data-bs-theme="dark"] .custom-context-menu a {
    color: #e2e8f0;
}
.custom-context-menu a i {
    font-size: 15px;
    margin-right: 8px;
    width: 16px;
    text-align: center;
}
.custom-context-menu a:hover {
    background-color: #f1f5f9;
    color: #206bc4;
}
[data-bs-theme="dark"] .custom-context-menu a:hover {
    background-color: #334155;
    color: #38bdf8;
}
.custom-context-menu .cm-divider {
    height: 1px;
    background-color: #e2e8f0;
    margin: 4px 0;
}
[data-bs-theme="dark"] .custom-context-menu .cm-divider {
    background-color: #334155;
}
.custom-context-menu a.cm-danger {
    color: #ef4444;
}
.custom-context-menu a.cm-danger:hover {
    background-color: #fef2f2;
    color: #dc2626;
}
[data-bs-theme="dark"] .custom-context-menu a.cm-danger:hover {
    background-color: rgba(239, 68, 68, 0.15);
    color: #f87171;
}
tbody tr.context-menu-active td {
    background-color: rgba(32, 107, 196, 0.08) !important;
}

/* Modal responsive and styling improvements */
#mycompany-modal .modal-content {
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

#mycompany-modal .modal-header {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] #mycompany-modal .modal-header {
    background-color: #1e293b;
    border-bottom-color: #334155;
}

#mycompany-modal .modal-section-card {
    background-color: #f8fafc;
    border-color: #e2e8f0 !important;
    transition: all 0.2s ease-in-out;
}
#mycompany-modal .modal-section-card:focus-within {
    border-color: #cbd5e1 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}
[data-bs-theme="dark"] #mycompany-modal .modal-section-card {
    background-color: #1e293b;
    border-color: #334155 !important;
}

#mycompany-modal .logo-upload-box {
    border: 1.5px dashed #cbd5e1;
    background-color: #ffffff;
    transition: all 0.2s ease-in-out;
}
#mycompany-modal .logo-upload-box:hover,
#mycompany-modal .logo-upload-box.dragover {
    border-color: #206bc4;
    background-color: #f0f7ff;
}
[data-bs-theme="dark"] #mycompany-modal .logo-upload-box {
    border-color: #475569;
    background-color: #0f172a;
}
[data-bs-theme="dark"] #mycompany-modal .logo-upload-box:hover,
[data-bs-theme="dark"] #mycompany-modal .logo-upload-box.dragover {
    border-color: #38bdf8;
    background-color: #1e293b;
}

#mycompany-modal .form-label.required:after {
    content: " *";
    color: #ef4444;
}

#mycompany-modal .form-control {
    border-color: #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
}
#mycompany-modal .form-control:focus {
    border-color: #206bc4;
    box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.12);
}
[data-bs-theme="dark"] #mycompany-modal .form-control {
    background-color: #0f172a;
    border-color: #334155;
    color: #f8fafc;
}
[data-bs-theme="dark"] #mycompany-modal .form-control:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
}

#mycompany-modal .input-icon-addon {
    color: #94a3b8;
}

#mycompany-modal .modal-body {
    max-height: calc(85vh - 130px);
    overflow-y: auto;
}
#mycompany-modal .modal-body::-webkit-scrollbar {
    width: 6px;
}
#mycompany-modal .modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
#mycompany-modal .modal-body::-webkit-scrollbar-track {
    background: transparent;
}
[data-bs-theme="dark"] #mycompany-modal .modal-body::-webkit-scrollbar-thumb {
    background: #475569;
}

#mycompany-modal .modal-footer {
    background-color: #f8fafc;
    border-top: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] #mycompany-modal .modal-footer {
    background-color: #1e293b;
    border-top-color: #334155;
}
</style>

<script>
$(document).ready(function() {
    var table = null;

    var columnConfig = {
        1: { label: 'Firma Adı', default: true },
        2: { label: 'Yetkili', default: true },
        3: { label: 'Telefon', default: true },
        4: { label: 'E-posta', default: true },
        5: { label: 'Vergi Bilgisi', default: true },
        6: { label: 'Açıklama', default: true },
        7: { label: 'Oluşturulma Tarihi', default: true }
    };

    var savedVisibility = {};
    try {
        savedVisibility = JSON.parse(localStorage.getItem('mycompanies_column_visibility') || '{}');
    } catch(e) {
        savedVisibility = {};
    }

    function completeTableInitialization(api) {
        $.each(columnConfig, function(colIdx, conf) {
            colIdx = parseInt(colIdx, 10);
            var isVisible = Object.prototype.hasOwnProperty.call(savedVisibility, colIdx)
                ? !!savedVisibility[colIdx]
                : conf.default;
            api.column(colIdx).visible(isVisible, false);
        });

        api.columns.adjust().draw(false);
    }

    if (typeof window.createDataTable === 'function') {
        table = window.createDataTable('#myCompaniesTable', {
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [],
            columnDefs: [
                { targets: '_all', defaultContent: '' },
                { targets: [0, 8], orderable: false, searchable: false },
                { targets: [0], className: 'text-center' },
                { targets: 8, width: '90px', className: 'text-end no-export actions-column' }
            ],
            initComplete: function() {
                completeTableInitialization(this.api());
            }
        });
    } else if ($.fn.DataTable) {
        table = $('#myCompaniesTable').DataTable({
            autoWidth: false,
            pageLength: 25,
            order: [],
            columnDefs: [
                { targets: '_all', defaultContent: '' },
                { targets: [0, 8], orderable: false, searchable: false },
                { targets: [0], className: 'text-center' },
                { targets: 8, width: '90px', className: 'text-end no-export actions-column' }
            ],
            language: { url: 'src/tr.json' },
            initComplete: function() {
                completeTableInitialization(this.api());
            }
        });
    }

    var $colvisMenu = $('#mycompaniesColvisMenu');
    $colvisMenu.empty();

    if (table) {
        $.each(columnConfig, function(colIdx, conf) {
            colIdx = parseInt(colIdx, 10);
            var isVisible = savedVisibility.hasOwnProperty(colIdx) ? !!savedVisibility[colIdx] : conf.default;

            var $item = $(
                '<label class="dropdown-item d-flex align-items-center py-1 px-2 cursor-pointer">' +
                '<input type="checkbox" class="form-check-input me-2 mt-0 col-toggle-cb" data-column="' + colIdx + '"' + (isVisible ? ' checked' : '') + '>' +
                '<span style="font-size: 13px;">' + conf.label + '</span>' +
                '</label>'
            );
            $colvisMenu.append($item);
        });

    }

    // Checkbox değiştiğinde sütunu göster/gizle ve kaydet
    $colvisMenu.on('change', '.col-toggle-cb', function(e) {
        e.stopPropagation();
        var colIdx = parseInt($(this).data('column'), 10);
        var isChecked = $(this).is(':checked');

        table.column(colIdx).visible(isChecked, true);
        syncMyCompanySearchVisibility();

        savedVisibility[colIdx] = isChecked;
        try {
            localStorage.setItem('mycompanies_column_visibility', JSON.stringify(savedVisibility));
        } catch(err) {}
    });

    $colvisMenu.on('click', function(e) {
        e.stopPropagation();
    });

    // Özet Kartları Görünürlük Durumu & Aç/Kapa (Toggle)
    var isSummaryVisible = localStorage.getItem('mycompany_summary_cards_visible');
    if (isSummaryVisible === 'false') {
        $('#mycompanySummaryCards').hide();
        $('#toggleSummaryIcon').removeClass('ti-chevron-up').addClass('ti-chevron-down');
    } else {
        $('#mycompanySummaryCards').show();
        $('#toggleSummaryIcon').removeClass('ti-chevron-down').addClass('ti-chevron-up');
    }

    $('#toggleSummaryCards').on('click', function(e) {
        e.preventDefault();
        var $wrapper = $('#mycompanySummaryCards');
        var $icon = $('#toggleSummaryIcon');

        $wrapper.slideToggle(250, function() {
            var visible = $wrapper.is(':visible');
            localStorage.setItem('mycompany_summary_cards_visible', visible);
            if (visible) {
                $icon.removeClass('ti-chevron-down').addClass('ti-chevron-up');
            } else {
                $icon.removeClass('ti-chevron-up').addClass('ti-chevron-down');
            }
        });
    });

    // Özet Kartlarına Tıklayarak Filtreleme (Toggle Buton Davranışı)
    var activeStatFilter = 'Tümü';

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!settings || settings.sTableId !== 'myCompaniesTable') return true;
        if (activeStatFilter === 'Tümü') return true;
        if (!table) return true;

        var rowNode = table.row(dataIndex).node();
        if (!rowNode) return true;

        var $row = $(rowNode);
        if (activeStatFilter === 'Varsayılan') {
            return $row.attr('data-is-default') === 'Varsayılan';
        }
        if (activeStatFilter === 'İletişim') {
            return $row.attr('data-has-contact') === 'İletişim';
        }
        return true;
    });

    $('.mycompany-stat-card').on('click', function() {
        var filterType = $(this).attr('data-filter-type');
        if (!filterType || !table) return;

        $('.mycompany-stat-card').removeClass('active');
        $(this).addClass('active');

        activeStatFilter = filterType;
        table.draw();
    });

    // Excel Export Butonu
    $('#export_excel').off('click').on('click', function(e) {
        e.preventDefault();
        if (table && table.button) {
            table.button('.buttons-excel').trigger();
        }
    });

    // Header Yeni Firma Butonu
    $('#btn-new-mycompany-header').on('click', function(e) {
        e.preventDefault();
        $('#btn-new-mycompany').trigger('click');
    });

    // Tabloda Sağ Tık (Custom Context Menu)
    $(document).on('contextmenu', '#myCompaniesTable tbody tr', function(e) {
        var $tr = $(this);
        var firmId = $tr.attr('data-firm-id');
        var firmName = $tr.attr('data-firm-name') || 'Firma İşlemleri';
        var isDefault = $tr.attr('data-is-default') === 'Varsayılan';

        if (!firmId) return;

        e.preventDefault();

        $('#myCompaniesTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var defaultActionHtml = isDefault 
            ? `<a href="#" class="btn-unset-default-firm text-muted" data-id="${firmId}"><i class="ti ti-star-off text-secondary"></i> Varsayılanı Kaldır</a>`
            : `<a href="#" class="btn-set-default-firm text-amber" data-id="${firmId}"><i class="ti ti-star text-warning"></i> Varsayılan Yap</a>`;

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-building-skyscraper me-1"></i> ${$('<div>').text(firmName).html()}</div>
            <a href="#" class="route-link" data-page="mycompany/manage&id=${firmId}"><i class="ti ti-eye text-primary"></i> Firma Detayları</a>
            ${defaultActionHtml}
            <a href="#" class="mycompany-edit-btn" data-id="${firmId}"><i class="ti ti-edit text-warning"></i> Bilgileri Güncelle</a>
            <div class="cm-divider"></div>
            <a href="#" class="cm-danger delete-mycompany" data-id="${firmId}"><i class="ti ti-trash"></i> Firmayı Sil</a>
        `;

        $contextMenu.html(menuHtml);
        $contextMenu.css({ display: 'block', opacity: 0 });

        var menuWidth = $contextMenu.outerWidth();
        var menuHeight = $contextMenu.outerHeight();
        var clickX = e.clientX;
        var clickY = e.clientY;
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();

        var posX = (clickX + menuWidth > windowWidth) ? windowWidth - menuWidth - 10 : clickX;
        var posY = (clickY + menuHeight > windowHeight) ? windowHeight - menuHeight - 10 : clickY;

        $contextMenu.css({
            top: posY + 'px',
            left: posX + 'px',
            opacity: 1
        });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#myCompaniesTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#myCompaniesTable tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#myCompaniesTable tbody tr').removeClass('context-menu-active');
    });
});
</script>
