<?php
require_once ROOT . '/Model/IzinHakedis.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

$perm->checkAuthorize('izin_hakedisler');
$Auths->checkFirmReturn();

$firma_id    = (int) ($_SESSION['firm_id'] ?? 0);
$personeller = (new Persons())->getPersonsByFirm($firma_id);
?>

<div class="page-header d-print-none mb-0">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Yıllık İzin Hakedişleri</h2>
                <div class="text-muted small mt-1">
                    <i class="ti ti-info-circle text-info me-1"></i> Yıllık izin hakedişleri her gece saat 01:00'da otomatik olarak hesaplanmaktadır.
                </div>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalManuel">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M12 5l0 14"></path>
                        <path d="M5 12l14 0"></path>
                    </svg>
                    Yeni Ekle
                </button>
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-dots-vertical me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                            <path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                            <path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                        </svg>
                        İşlemler
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius:12px; min-width:200px;">
                        <a class="dropdown-item py-2" href="#" id="btn-devir-kullanim-modal-top">
                            <i class="ti ti-history me-2 text-purple fs-3"></i> Devir Kullanım Ekle
                        </a>
                        <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#modalExcel">
                            <i class="ti ti-file-import me-2 text-primary fs-3"></i> Excel'den Aktar
                        </a>
                        <div class="dropdown-divider my-1"></div>
                        <a class="dropdown-item py-2" href="#" id="btn-hesapla-hepsi">
                            <i class="ti ti-calculator me-2 text-info fs-3"></i> Hakedişleri Hesapla
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1 small">Personel Filtrele</label>
                        <select id="filter-personel" class="form-select select2-filter">
                            <option value="">Tüm Personel</option>
                            <?php foreach ($personeller as $p): ?>
                                <option value="<?= Security::encrypt($p->id) ?>"><?= htmlspecialchars($p->full_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" id="btn-filtrele">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                <path d="M21 21l-6 -6" />
                            </svg>
                            Filtrele
                        </button>
                        <button class="btn btn-outline-secondary ms-1" id="btn-temizle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                            </svg>
                            Temizle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table" id="hakedis-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;"></th>
                                <th>Personel</th>
                                <th class="text-center">Hakediş Süresi</th>
                                <th class="text-center">Toplam Hakedilen</th>
                                <th class="text-center">Toplam Kullanılan</th>
                                <th class="text-center">Toplam Kalan</th>
                                <th class="text-center" style="width: 60px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Devir Kullanım Ekle -->
<div class="modal modal-blur fade" id="modalDevirKullanim" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-white py-3 border-bottom">
                <h5 class="modal-title d-flex align-items-center fw-bold text-dark fs-3">
                    <i class="ti ti-history me-2 text-purple fs-2"></i>
                    Devir Kullanım Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-fill bg-light border-bottom mb-0" id="devirTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold py-3" id="devir-tekil-tab" data-bs-toggle="tab" data-bs-target="#devir-tekil-pane" type="button" role="tab">Tekil Ekle</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold py-3" id="devir-excel-tab" data-bs-toggle="tab" data-bs-target="#devir-excel-pane" type="button" role="tab">Excel ile Toplu Yükle</button>
                    </li>
                </ul>

                <div class="tab-content" id="devirTabContent">
                    <!-- Tab 1: Tekil Ekle -->
                    <div class="tab-pane fade show active p-4" id="devir-tekil-pane" role="tabpanel">
                        <!-- Personel İzin Özet Kartı -->
                        <div id="devir-personel-summary-card" class="card card-sm border bg-white shadow-sm p-2 mb-4 rounded-3" style="display:none;">
                            <div class="card-body p-2">
                                <div class="row text-center g-1 align-items-center">
                                    <div class="col-4 border-end">
                                        <div class="text-muted small fw-medium" style="font-size:0.75rem;">Hakedilen İzin</div>
                                        <div class="fw-bold fs-3 text-primary" id="summary-hakedilen">0 Gün</div>
                                    </div>
                                    <div class="col-4 border-end">
                                        <div class="text-muted small fw-medium" style="font-size:0.75rem;">Kullanılan İzin</div>
                                        <div class="fw-bold fs-3 text-danger" id="summary-kullanilan">0 Gün</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted small fw-medium" style="font-size:0.75rem;">Kalan İzin</div>
                                        <div class="fw-bold fs-3 text-success" id="summary-kalan">0 Gün</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form id="form-devir-tekil">
                            <div class="mb-3">
                                <label class="form-label required">Personel <span class="text-danger">*</span></label>
                                <select id="devir-personel" class="form-select select2-devir-modal">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($personeller as $p): ?>
                                        <option value="<?= Security::encrypt($p->id) ?>" data-personel-id="<?= (int)$p->id ?>"><?= htmlspecialchars($p->full_name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Kullanılan Gün Sayısı <span class="text-danger">*</span></label>
                                <input type="number" id="devir-gun" class="form-control" min="1" placeholder="Örn: 10">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea id="devir-aciklama" class="form-control" rows="3" placeholder="Örn: 2022-2023 döneminde kullanılan izin"></textarea>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Excel ile Toplu Yükle -->
                    <div class="tab-pane fade p-4" id="devir-excel-pane" role="tabpanel">
                        <div class="mb-4 text-center">
                            <p class="text-secondary small mb-3">Aşağıdaki butona tıklayarak personel listesini içeren Excel şablonunu indirin, devir kullanım bilgilerini doldurun ve dosyayı buraya yükleyin.</p>
                            <button type="button" id="btn-devir-sablon-indir" class="btn btn-outline-purple btn-pill w-100 py-2 d-flex align-items-center justify-content-center" style="gap:8px;border-width:2px;">
                                <i class="ti ti-file-download" style="font-size:1.2rem;"></i>
                                <strong>Devir Kullanım Şablonu İndir (.xlsx)</strong>
                            </button>
                        </div>

                        <div class="dropzone-area" id="devir-dropzone">
                            <div class="dropzone-icon">
                                <i class="ti ti-cloud-upload text-purple" style="font-size:3rem;transition:transform .2s;"></i>
                            </div>
                            <div class="dropzone-text">
                                <span class="dropzone-title">Excel dosyasını buraya sürükleyin veya tıklayın</span>
                                <span class="dropzone-sub">Sadece .xls ve .xlsx dosyaları desteklenir (Max 5MB)</span>
                            </div>
                        </div>
                        <input type="file" id="devir-excel-dosya" accept=".xlsx,.xls" style="display:none;">

                        <div class="dropzone-preview" id="devir-dropzone-preview">
                            <div class="preview-icon">
                                <i class="ti ti-file-spreadsheet text-purple"></i>
                            </div>
                            <div class="preview-details">
                                <span class="preview-name" id="devir-preview-name">dosya.xlsx</span>
                                <span class="preview-size" id="devir-preview-size">0 KB</span>
                            </div>
                            <div class="preview-remove" id="devir-preview-remove" title="Dosyayı Kaldır">
                                <i class="ti ti-trash"></i>
                            </div>
                        </div>

                        <div id="devir-excel-onizleme" style="display:none;" class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold small text-secondary" id="devir-excel-satir-sayisi"></span>
                            </div>
                            <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
                                <table class="table table-sm table-bordered table-vcenter mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th>Personel Adı</th>
                                            <th>TC Kimlik</th>
                                            <th class="text-center">Gün</th>
                                            <th>Açıklama</th>
                                        </tr>
                                    </thead>
                                    <tbody id="devir-excel-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-secondary me-auto" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-dark px-4" id="btn-devir-kaydet">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Manuel Hakediş -->
<div class="modal modal-blur fade" id="modalManuel" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manuel Hakediş Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Personel</label>
                    <select id="manuel-personel" class="form-select select2-modal">
                        <option value="">Seçiniz</option>
                        <?php foreach ($personeller as $p): ?>
                            <option value="<?= Security::encrypt($p->id) ?>"><?= htmlspecialchars($p->full_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hakediş Yılı</label>
                    <input type="number" id="manuel-yil" class="form-control" min="1" placeholder="Örn: 3">
                </div>
                <div class="mb-3">
                    <label class="form-label">Gün Sayısı</label>
                    <input type="number" id="manuel-gun" class="form-control" min="1" placeholder="Örn: 14">
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <input type="text" id="manuel-aciklama" class="form-control" placeholder="Opsiyonel">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M18 6l-12 12"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>
                    İptal
                </button>
                <button type="button" class="btn btn-success" id="btn-manuel-kaydet">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M5 12l5 5l10 -10"></path>
                    </svg>
                    Ekle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Excel İmport -->
<div class="modal modal-blur fade" id="modalExcel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header text-white py-3" style="background:linear-gradient(135deg,#2fb344,#1a7a2e);">
                <h5 class="modal-title d-flex align-items-center text-white fw-bold">
                    <i class="ti ti-file-import me-2" style="font-size:1.4rem;"></i>
                    Hakediş Yükle (Excel)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4 text-center">
                    <p class="text-secondary small mb-3">Aşağıdaki butona tıklayarak personel listesini içeren Excel şablonunu indirin, hakediş bilgilerini doldurun ve dosyayı buraya yükleyin.</p>
                    <button type="button" id="btn-sablon-indir-modal" class="btn btn-outline-success btn-pill w-100 py-2 d-flex align-items-center justify-content-center" style="gap:8px;border-width:2px;">
                        <i class="ti ti-file-download" style="font-size:1.2rem;"></i>
                        <strong>Hakediş Şablonu İndir (.xlsx)</strong>
                    </button>
                </div>

                <div class="dropzone-area" id="hakedis-dropzone">
                    <div class="dropzone-icon">
                        <i class="ti ti-cloud-upload text-success" style="font-size:3rem;transition:transform .2s;"></i>
                    </div>
                    <div class="dropzone-text">
                        <span class="dropzone-title">Excel dosyasını buraya sürükleyin veya tıklayın</span>
                        <span class="dropzone-sub">Sadece .xls ve .xlsx dosyaları desteklenir (Max 5MB)</span>
                    </div>
                </div>
                <input type="file" id="excel-dosya" accept=".xlsx,.xls" style="display:none;">

                <div class="dropzone-preview" id="hakedis-dropzone-preview">
                    <div class="preview-icon">
                        <i class="ti ti-file-spreadsheet" style="color:#2fb344;"></i>
                    </div>
                    <div class="preview-details">
                        <span class="preview-name" id="hakedis-preview-name">dosya.xlsx</span>
                        <span class="preview-size" id="hakedis-preview-size">0 KB</span>
                    </div>
                    <div class="preview-remove" id="hakedis-preview-remove" title="Dosyayı Kaldır">
                        <i class="ti ti-trash"></i>
                    </div>
                </div>

                <div id="excel-onizleme" style="display:none;" class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-secondary" id="excel-satir-sayisi"></span>
                    </div>
                    <div class="table-responsive" style="max-height:240px;overflow-y:auto;">
                        <table class="table table-sm table-bordered table-vcenter mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Personel Adı</th>
                                    <th>TC Kimlik</th>
                                    <th class="text-center">Yıl</th>
                                    <th class="text-center">Gün</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody id="excel-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M18 6l-12 12"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>
                    Kapat
                </button>
                <button type="button" class="btn btn-success px-4" id="btn-excel-aktar" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M5 12l5 5l10 -10"></path>
                    </svg>
                    Aktar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Hakediş Düzenle -->
<div class="modal modal-blur fade" id="modalDuzenle" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manuel Hakediş Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="duzenle-id">
                <div class="mb-3">
                    <label class="form-label">Personel</label>
                    <input type="text" id="duzenle-personel" class="form-control" readonly disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hakediş Yılı</label>
                    <input type="text" id="duzenle-yil" class="form-control" readonly disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gün Sayısı</label>
                    <input type="number" id="duzenle-gun" class="form-control" min="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <input type="text" id="duzenle-aciklama" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M18 6l-12 12"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>
                    İptal
                </button>
                <button type="button" class="btn btn-primary" id="btn-duzenle-kaydet">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M5 12l5 5l10 -10"></path>
                    </svg>
                    Güncelle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Kullanılan İzinler -->
<div class="modal modal-blur fade" id="modalKullanilanIzinler" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-event me-2 text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"></path>
                        <path d="M16 3l0 4"></path>
                        <path d="M8 3l0 4"></path>
                        <path d="M4 11l16 0"></path>
                        <path d="M8 15h2v2h-2z"></path>
                    </svg>
                    <span id="kullanilan-izinler-title">Kullanılan İzinler</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table mb-0" id="kullanilan-izinler-table">
                        <thead class="table-light">
                            <tr>
                                <th>İzin Türü</th>
                                <th class="text-center">Başlangıç</th>
                                <th class="text-center">Bitiş</th>
                                <th class="text-center">Süre</th>
                                <th>Açıklama</th>
                                <th>Onaylayan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Yükleniyor...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Context Menu for Table Rows -->
<div id="hakedis-context-menu" class="dropdown-menu shadow-lg border rounded-3 p-1" style="display:none; position:fixed; z-index:9999; min-width:200px;">
    <a class="dropdown-item py-2" href="#" id="ctx-personel-karti">
        <i class="ti ti-user me-2 text-primary"></i> Personel Kartına Git
    </a>
    <a class="dropdown-item py-2" href="#" id="ctx-devir-ekle">
        <i class="ti ti-history me-2 text-purple"></i> Devir Kullanım Ekle
    </a>
    <div class="dropdown-divider my-1"></div>
    <a class="dropdown-item py-2" href="#" id="ctx-detay-toggle">
        <i class="ti ti-layout-list me-2 text-secondary"></i> Hakediş Detayları
    </a>
</div>

<style>
#hakedis-table tbody tr { cursor: pointer; }
#hakedis-table tbody tr.shown { background-color: rgba(var(--tblr-primary-rgb), 0.02); }
#hakedis-table td.dt-control i { transition: transform 0.2s ease; }
#hakedis-table td.dt-control::before { display: none !important; }
.dropzone-area { border:2px dashed #86efac; border-radius:12px; padding:2.5rem 1.5rem; text-align:center; background:#f0fdf4; cursor:pointer; transition:all .2s ease-in-out; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.75rem; }
.dropzone-area:hover, .dropzone-area.dragover { border-color:#2fb344; background:#dcfce7; }
.dropzone-area:hover .dropzone-icon i, .dropzone-area.dragover .dropzone-icon i { transform:translateY(-5px); }
.dropzone-title { display:block; font-size:1rem; font-weight:600; color:#1e293b; }
.dropzone-sub { display:block; font-size:.8rem; color:#64748b; margin-top:.25rem; }
.dropzone-preview { margin-top:1.25rem; display:none; align-items:center; gap:.75rem; padding:.75rem 1rem; background:#fff; border:1px solid #e2e8f0; border-radius:8px; }
.dropzone-preview .preview-icon { font-size:1.8rem; display:flex; align-items:center; }
.dropzone-preview .preview-details { flex-grow:1; text-align:left; }
.dropzone-preview .preview-name { font-weight:600; font-size:.9rem; color:#1e293b; display:block; word-break:break-all; }
.dropzone-preview .preview-size { font-size:.75rem; color:#64748b; }
.dropzone-preview .preview-remove { cursor:pointer; color:#ef4444; font-size:1.25rem; transition:color .2s; display:flex; align-items:center; }
.dropzone-preview .preview-remove:hover { color:#b91c1c; }
#hakedis-context-menu { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border-color: rgba(0,0,0,0.08) !important; background: #ffffff; }
#hakedis-context-menu .dropdown-item:hover { background-color: #f1f5f9; color: #0f172a; }
</style>

<script>
$(document).ready(function() {
    const HAKEDIS_API = 'api/izin/hakedis.php';

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Click handler for showing used leaves in modal
    $('#hakedis-table').on('click', '.show-leaves', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const personelId = $(this).data('personel-id');
        const personelName = $(this).data('personel-name');
        
        $('#kullanilan-izinler-title').text(`${personelName} - Kullanılan İzinler`);
        
        const tbody = $('#kullanilan-izinler-table tbody');
        tbody.html('<tr><td colspan="6" class="text-center py-4"><span class="spinner-border spinner-border-sm text-secondary me-2"></span> Yükleniyor...</td></tr>');
        
        const modalEl = document.getElementById('modalKullanilanIzinler');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        
        $.get('api/izin/talep.php', { action: 'list', personel_id: personelId, durum: 'onaylandi' }, function(res) {
            if (res.status !== 'success') {
                tbody.html(`<tr><td colspan="6" class="text-center text-danger py-4">Hata: ${res.message}</td></tr>`);
                return;
            }
            
            if (!res.list || res.list.length === 0) {
                tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">Kullanılmış izin kaydı bulunamadı.</td></tr>');
                return;
            }
            
            let html = '';
            res.list.forEach(item => {
                const aciklama = item.aciklama ? escapeHtml(item.aciklama) : '—';
                const onaylayan = item.onaylayan_adi ? escapeHtml(item.onaylayan_adi) : '—';
                html += `
                    <tr>
                        <td>
                            <span class="badge bg-blue-lt">${escapeHtml(item.tur_adi)}</span>
                        </td>
                        <td class="text-center">${fmtDate(item.baslangic_tarihi)}</td>
                        <td class="text-center">${fmtDate(item.bitis_tarihi)}</td>
                        <td class="text-center font-weight-bold">${item.gun_sayisi} Gün</td>
                        <td><small class="text-muted">${aciklama}</small></td>
                        <td><small>${onaylayan}</small></td>
                    </tr>
                `;
            });
            tbody.html(html);
        }).fail(function() {
            tbody.html('<tr><td colspan="6" class="text-center text-danger py-4">İzin listesi yüklenirken bir hata oluştu.</td></tr>');
        });
    });

    function swalSuccess(msg) {
        Swal.fire({
            icon: 'success',
            title: 'Başarılı!',
            text: msg,
            confirmButtonText: 'Tamam',
            confirmButtonColor: '#2fb344'
        });
    }
    function swalError(msg) {
        Swal.fire({ icon: 'error', title: 'Hata!', text: msg });
    }
    function swalWarning(msg) {
        Swal.fire({ icon: 'warning', title: 'Uyarı', text: msg });
    }

    $('.select2-filter').select2({ width: '100%', allowClear: true, placeholder: 'Seçiniz' });
    $('.select2-modal').select2({ width: '100%', allowClear: true, placeholder: 'Seçiniz', dropdownParent: $('#modalManuel') });
    $('.select2-devir-modal').select2({ width: '100%', allowClear: true, placeholder: 'Seçiniz', dropdownParent: $('#modalDevirKullanim') });

    function fmtDate(d) {
        if (!d) return '—';
        const p = (d + '').split(/[-T ]/);
        return p.length >= 3 ? `${p[2]}.${p[1]}.${p[0]}` : d;
    }

    function formatChildRow(d) {
        let rowsHtml = '';
        d.details.forEach(h => {
            const kalan = (parseInt(h.gun_sayisi) || 0) - (parseInt(h.kullanilan_gun) || 0);
            const badge = `<span class="badge ${h.tip === 'manuel' ? 'bg-orange-lt' : 'bg-blue-lt'}">${h.tip}</span>`;
            
            let actions = '—';
            if (h.tip === 'manuel') {
                const rowEscaped = encodeURIComponent(JSON.stringify(h));
                actions = `
                    <button class="btn btn-icon btn-sm btn-ghost-secondary me-1" onclick="event.stopPropagation(); duzenleHakedis('${rowEscaped}')" title="Düzenle" style="width: 24px; height: 24px; padding:0; display: inline-flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0;">
                            <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4"></path>
                            <path d="M13.5 6.5l4 4"></path>
                        </svg>
                    </button>
                    <button class="btn btn-icon btn-sm btn-ghost-danger" onclick="event.stopPropagation(); silHakedis(${h.id})" title="Sil" style="width: 24px; height: 24px; padding:0; display: inline-flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0;">
                            <path d="M4 7l16 0"></path>
                            <path d="M10 11l0 6"></path>
                            <path d="M14 11l0 6"></path>
                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                        </svg>
                    </button>
                `;
            }

            const kullanilanGunHtml = parseInt(h.kullanilan_gun) > 0 
                ? `<span class="show-leaves cursor-pointer text-decoration-underline fw-bold text-danger" data-personel-id="${h.personel_id}" data-personel-name="${d.personel_adi}">${h.kullanilan_gun} Gün</span>` 
                : `<span class="text-danger">${h.kullanilan_gun} Gün</span>`;

            rowsHtml += `
                <tr>
                    <td class="ps-3 font-weight-bold text-muted">${h.yil}${h.yil < 100 ? '. Yıl' : ''}</td>
                    <td>${fmtDate(h.hakedis_tarihi)}</td>
                    <td class="text-center font-weight-medium">${h.gun_sayisi} Gün</td>
                    <td class="text-center">${kullanilanGunHtml}</td>
                    <td class="text-center font-weight-bold text-success">${kalan} Gün</td>
                    <td class="text-center">${badge}</td>
                    <td><small class="text-muted">${h.aciklama || '—'}</small></td>
                    <td class="text-center">${actions}</td>
                </tr>
            `;
        });

        setTimeout(() => loadDevirListChildRow(d.personel_id, d.personel_enc_id), 50);

        return `
            <div class="p-3 bg-light rounded-3 border border-dashed border-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="mb-0 font-weight-bold text-secondary">Hakediş Detayları</h4>
                    <button class="btn btn-sm btn-outline-purple" onclick="event.stopPropagation(); openDevirModal('${d.personel_enc_id}')">
                        <i class="ti ti-plus me-1"></i> Devir Kullanım Ekle
                    </button>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered table-vcenter bg-white mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Yıl</th>
                                <th>Hakediş Tarihi</th>
                                <th class="text-center">Hak Edilen</th>
                                <th class="text-center">Kullanılan</th>
                                <th class="text-center">Kalan</th>
                                <th class="text-center">Tür</th>
                                <th>Açıklama</th>
                                <th class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>

                <div id="devir-child-wrapper-${d.personel_id}">
                    <div class="text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span> Devir kullanımları yükleniyor...</div>
                </div>
            </div>
        `;
    }

    function loadDevirListChildRow(personelId, encId) {
        $.get(HAKEDIS_API, { action: 'list_devir', personel_id: encId }, function(res) {
            const container = $(`#devir-child-wrapper-${personelId}`);
            if (!container.length) return;

            if (res.status !== 'success' || !res.list || res.list.length === 0) {
                container.html('<div class="small text-muted italic">Bu personele ait devir kullanımı bulunmuyor.</div>');
                return;
            }

            let rows = '';
            res.list.forEach(item => {
                rows += `
                    <tr>
                        <td><span class="badge bg-purple-lt">Devir Kullanımı</span></td>
                        <td class="text-center font-weight-bold text-danger">${item.kullanilan_gun} Gün</td>
                        <td><small class="text-muted">${escapeHtml(item.aciklama || '—')}</small></td>
                        <td><small class="text-muted">${fmtDate(item.olusturma_tarihi)}</small></td>
                        <td class="text-center">
                            <button class="btn btn-icon btn-sm btn-ghost-danger" onclick="event.stopPropagation(); silDevirKullanim(${item.id})" title="Sil" style="width: 24px; height: 24px; padding:0; display: inline-flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0;">
                                    <path d="M4 7l16 0"></path>
                                    <path d="M10 11l0 6"></path>
                                    <path d="M14 11l0 6"></path>
                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                `;
            });

            container.html(`
                <h5 class="mb-2 font-weight-bold text-purple d-flex align-items-center">
                    <i class="ti ti-history me-1"></i> Devir Kullanım Kayıtları
                </h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-vcenter bg-white mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tür</th>
                                <th class="text-center">Kullanılan Gün</th>
                                <th>Açıklama</th>
                                <th>Kayıt Tarihi</th>
                                <th class="text-center" style="width:50px;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `);
        });
    }

    const dt = window.createDataTable('#hakedis-table', {
        data: [],
        columns: [
            {
                className: 'dt-control text-center',
                orderable: false,
                data: null,
                defaultContent: '<i class="ti ti-chevron-right text-muted cursor-pointer" style="font-size: 1.2rem;"></i>'
            },
            { data: 'personel_adi', className: 'fw-bold' },
            { 
                data: 'yil_sayisi', 
                className: 'text-center', 
                render: (d, t, row) => {
                    const minYear = Math.min(...row.details.map(x => x.yil));
                    const maxYear = Math.max(...row.details.map(x => x.yil));
                    if (minYear === maxYear) {
                        return `<strong>${minYear}${minYear < 100 ? '. Yıl' : ''}</strong>`;
                    }
                    return `<strong>${minYear} - ${maxYear}${maxYear < 100 ? '. Yıl' : ''} (${d} Adet)</strong>`;
                }
            },
            { data: 'total_hakedis', className: 'text-center fw-medium', render: d => `${d} Gün` },
            { 
                data: 'total_kullanilan', 
                className: 'text-center text-danger', 
                render: (d, t, row) => {
                    if (parseInt(d) > 0) {
                        return `<span class="show-leaves cursor-pointer text-decoration-underline fw-bold" data-personel-id="${row.personel_id}" data-personel-name="${row.personel_adi}">${d} Gün</span>`;
                    }
                    return `${d} Gün`;
                }
            },
            {
                data: null, className: 'text-center fw-bold',
                render: (d, t, row) => {
                    const k = row.total_hakedis - row.total_kullanilan;
                    return `<span class="${k > 0 ? 'text-success' : 'text-muted'}">${k} Gün</span>`;
                }
            },
            {
                data: null, className: 'text-center', orderable: false,
                render: (d, t, row) => {
                    return `
                        <div class="dropdown" onclick="event.stopPropagation();">
                            <button class="btn btn-icon btn-ghost-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-dots-vertical" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                   <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                   <path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                   <path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                </svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm">
                                <a class="dropdown-item py-2" href="index.php?p=persons/manage&id=${row.personel_enc_id}">
                                    <i class="ti ti-user me-2 text-primary"></i> Personel Kartına Git
                                </a>
                                <a class="dropdown-item py-2" href="#" onclick="event.preventDefault(); event.stopPropagation(); openDevirModal('${row.personel_enc_id}')">
                                    <i class="ti ti-history me-2 text-purple"></i> Devir Kullanım Ekle
                                </a>
                            </div>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 50,
        skipSearch: []
    });

    // Add event listener for opening and closing details
    $('#hakedis-table tbody').on('click', 'td.dt-control, tr', function (e) {
        if ($(e.target).closest('button, a, input, select, .show-leaves, .dropdown').length) {
            return;
        }

        const tr = $(this).closest('tr');
        const row = dt.row(tr);
 
        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            tr.find('td.dt-control i').removeClass('ti-chevron-down').addClass('ti-chevron-right');
        } else {
            row.child(formatChildRow(row.data())).show();
            tr.addClass('shown');
            tr.find('td.dt-control i').removeClass('ti-chevron-right').addClass('ti-chevron-down');
        }
    });

    // Right-click Context Menu
    let selectedContextRowData = null;

    $('#hakedis-table tbody').on('contextmenu', 'tr', function(e) {
        if ($(e.target).closest('button, a, input, select, .show-leaves').length) {
            return;
        }
        e.preventDefault();

        const tr = $(this);
        const rowData = dt.row(tr).data();
        if (!rowData) return;

        selectedContextRowData = rowData;

        const menu = $('#hakedis-context-menu');
        menu.css({
            display: 'block',
            left: e.clientX + 'px',
            top: e.clientY + 'px'
        });
    });

    $(document).on('click scroll', function(e) {
        if (!$(e.target).closest('#hakedis-context-menu').length) {
            $('#hakedis-context-menu').hide();
        }
    });

    $('#ctx-personel-karti').on('click', function(e) {
        e.preventDefault();
        $('#hakedis-context-menu').hide();
        if (selectedContextRowData && selectedContextRowData.personel_enc_id) {
            window.location.href = 'index.php?p=persons/manage&id=' + selectedContextRowData.personel_enc_id;
        }
    });

    $('#ctx-devir-ekle').on('click', function(e) {
        e.preventDefault();
        $('#hakedis-context-menu').hide();
        if (selectedContextRowData) {
            openDevirModal(selectedContextRowData.personel_id || selectedContextRowData.personel_enc_id);
        }
    });

    $('#ctx-detay-toggle').on('click', function(e) {
        e.preventDefault();
        $('#hakedis-context-menu').hide();
        if (selectedContextRowData) {
            const tr = $('#hakedis-table tbody tr').filter(function() {
                const rData = dt.row(this).data();
                return rData && rData.personel_id === selectedContextRowData.personel_id;
            });
            if (tr.length) {
                const row = dt.row(tr);
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    tr.find('td.dt-control i').removeClass('ti-chevron-down').addClass('ti-chevron-right');
                } else {
                    row.child(formatChildRow(row.data())).show();
                    tr.addClass('shown');
                    tr.find('td.dt-control i').removeClass('ti-chevron-right').addClass('ti-chevron-down');
                }
            }
        }
    });

    function loadList() {
        const enc = $('#filter-personel').val();
        const params = new URLSearchParams({ action: 'list' });
        if (enc) params.append('personel_id', enc);
        $.get(HAKEDIS_API + '?' + params.toString(), function(res) {
            if (res.status !== 'success') { swalError(res.message); return; }
            
            // Group by employee
            const grouped = {};
            res.list.forEach(item => {
                const pid = item.personel_id;
                if (!grouped[pid]) {
                    grouped[pid] = {
                        personel_id: pid,
                        personel_enc_id: item.personel_enc_id,
                        personel_adi: item.personel_adi,
                        total_hakedis: 0,
                        total_kullanilan: 0,
                        yil_sayisi: 0,
                        details: []
                    };
                }
                grouped[pid].total_hakedis += parseInt(item.gun_sayisi) || 0;
                grouped[pid].total_kullanilan += parseInt(item.kullanilan_gun) || 0;
                grouped[pid].yil_sayisi++;
                grouped[pid].details.push(item);
            });
            
            // Sort details by yil
            const groupedList = Object.values(grouped);
            groupedList.forEach(g => {
                g.details.sort((a, b) => (parseInt(a.yil) || 0) - (parseInt(b.yil) || 0));
            });
            
            dt.clear().rows.add(groupedList).draw();
        });
    }

    function updateDevirPersonelSummary() {
        const val = $('#devir-personel').val();
        const $opt = $('#devir-personel option:selected');
        const pid = $opt.data('personel-id');

        if (!val && !pid) {
            $('#devir-personel-summary-card').slideUp(150);
            return;
        }

        let found = null;
        if (typeof dt !== 'undefined' && dt.rows) {
            dt.rows().data().each(function(row) {
                if (row.personel_enc_id === val || row.personel_id == pid) {
                    found = row;
                }
            });
        }

        if (found) {
            const kalan = found.total_hakedis - found.total_kullanilan;
            $('#summary-hakedilen').text(found.total_hakedis + ' Gün');
            $('#summary-kullanilan').text(found.total_kullanilan + ' Gün');
            $('#summary-kalan').text(kalan + ' Gün')
                .removeClass('text-success text-danger text-muted')
                .addClass(kalan > 0 ? 'text-success' : (kalan < 0 ? 'text-danger' : 'text-muted'));
            $('#devir-personel-summary-card').slideDown(150);
        } else {
            $('#devir-personel-summary-card').slideUp(150);
        }
    }

    $('#devir-personel').on('change', updateDevirPersonelSummary);

    // Devir Kullanım Modal işlemleri
    window.openDevirModal = function(targetId) {
        $('#form-devir-tekil')[0].reset();
        if (targetId) {
            let $opt = $('#devir-personel option').filter(function() {
                return $(this).val() === targetId || $(this).data('personel-id') == targetId;
            });
            if ($opt.length) {
                $('#devir-personel').val($opt.val()).trigger('change');
            } else {
                $('#devir-personel').val(null).trigger('change');
            }
        } else {
            $('#devir-personel').val(null).trigger('change');
        }
        updateDevirPersonelSummary();
        $('#devir-tekil-tab').tab('show');
        resetDevirDropzone();
        const modalEl = document.getElementById('modalDevirKullanim');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    };

    $('#btn-devir-kullanim-modal-top').on('click', function() {
        openDevirModal(null);
    });

    window.silDevirKullanim = function(id) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: 'Bu devir kullanımı kaydı silinecektir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#d33'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.post(HAKEDIS_API, { action: 'delete_devir', id: id }, function(res) {
                if (res.status === 'success') {
                    swalSuccess(res.message);
                    loadList();
                } else {
                    swalError(res.message);
                }
            });
        });
    };

    $('#btn-devir-kaydet').on('click', function() {
        const activeTab = $('#devirTabs .active').attr('id');
        if (activeTab === 'devir-tekil-tab') {
            const pid = $('#devir-personel').val();
            const gun = $('#devir-gun').val();
            const ack = $('#devir-aciklama').val();

            if (!pid || !gun || gun <= 0) {
                swalWarning('Lütfen personel seçin ve geçerli bir gün sayısı girin.');
                return;
            }

            $.post(HAKEDIS_API, {
                action: 'add_devir',
                personel_id: pid,
                kullanilan_gun: gun,
                aciklama: ack
            }, function(res) {
                if (res.status === 'success') {
                    swalSuccess(res.message);
                    bootstrap.Modal.getInstance('#modalDevirKullanim').hide();
                    loadList();
                } else {
                    swalError(res.message);
                }
            });
        } else if (activeTab === 'devir-excel-tab') {
            if (!parsedDevirRows.length) {
                swalWarning('Lütfen geçerli bir Excel dosyası yükleyin.');
                return;
            }
            const $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Aktarılıyor...');

            fetch(HAKEDIS_API + '?action=bulk_add_devir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(parsedDevirRows)
            })
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') { swalError(res.message); return; }

                const basarili = res.sonuclar.filter(s => s.status === 'success').length;
                const hatali   = res.sonuclar.filter(s => s.status === 'error').length;
                const detay    = res.sonuclar.filter(s => s.status !== 'success')
                    .map(s => `<li class="text-danger">${s.message}</li>`)
                    .join('');

                Swal.fire({
                    icon: hatali > 0 ? 'warning' : 'success',
                    title: 'Devir Kullanımları Aktarıldı',
                    html: `<strong>${basarili}</strong> eklendi, <strong>${hatali}</strong> hata.` +
                          (detay ? `<ul class="text-start mt-2 small" style="max-height:200px;overflow-y:auto">${detay}</ul>` : ''),
                });

                bootstrap.Modal.getInstance('#modalDevirKullanim').hide();
                loadList();
            })
            .finally(() => {
                $btn.prop('disabled', false).html('Kaydet');
            });
        }
    });

    window.duzenleHakedis = function(rowJsonStr) {
        const row = JSON.parse(decodeURIComponent(rowJsonStr));
        $('#duzenle-id').val(row.id);
        $('#duzenle-personel').val(row.personel_adi);
        $('#duzenle-yil').val(row.yil + (row.yil < 100 ? '. Yıl' : ''));
        $('#duzenle-gun').val(row.gun_sayisi);
        $('#duzenle-aciklama').val(row.aciklama || '');
        new bootstrap.Modal('#modalDuzenle').show();
    };

    $('#btn-duzenle-kaydet').on('click', function() {
        const id = $('#duzenle-id').val();
        const gun = $('#duzenle-gun').val();
        const aciklama = $('#duzenle-aciklama').val();

        if (!gun || gun <= 0) {
            swalWarning('Lütfen geçerli bir gün sayısı girin.');
            return;
        }

        $.post(HAKEDIS_API, {
            action: 'update',
            id: id,
            gun_sayisi: gun,
            aciklama: aciklama
        }, function(res) {
            if (res.status === 'success') {
                swalSuccess(res.message);
                bootstrap.Modal.getInstance('#modalDuzenle').hide();
                loadList();
            } else {
                swalError(res.message);
            }
        });
    });

    window.silHakedis = function(id) {
        Swal.fire({
            title: 'Emin misiniz?', text: 'Bu hakediş silinecektir.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Evet, Sil', cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#d33'
        }).then(r => {
            if (!r.isConfirmed) return;
            $.post(HAKEDIS_API, { action: 'delete', id: id }, function(res) {
                if (res.status === 'success') { swalSuccess(res.message); loadList(); }
                else swalError(res.message);
            });
        });
    };

    $('#btn-filtrele').on('click', loadList);
    $('#btn-temizle').on('click', function() {
        $('#filter-personel').val(null).trigger('change');
        loadList();
    });

    $('#btn-hesapla-hepsi').on('click', function() {
        Swal.fire({
            title: 'Hakediş Hesapla',
            text: 'Tüm personellerin eksik yıllık izin hakedişleri otomatik hesaplanıp kaydedilecektir. Devam etmek istiyor musunuz?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet, Hesapla',
            cancelButtonText: 'Vazgeç',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.post(HAKEDIS_API, { action: 'calculate_all' })
                    .then(res => {
                        if (res.status !== 'success') {
                            throw new Error(res.message);
                        }
                        return res;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Hata: ${error.message || error}`);
                    });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                swalSuccess(result.value.message);
                loadList();
            }
        });
    });

    $('#btn-manuel-kaydet').on('click', function() {
        const data = {
            action:      'add',
            personel_id: $('#manuel-personel').val(),
            yil:         $('#manuel-yil').val(),
            gun_sayisi:  $('#manuel-gun').val(),
            aciklama:    $('#manuel-aciklama').val(),
        };
        if (!data.personel_id || !data.yil || !data.gun_sayisi) {
            swalWarning('Personel, yıl ve gün sayısı zorunludur.');
            return;
        }
        $.post(HAKEDIS_API, data, function(res) {
            if (res.status === 'success') {
                swalSuccess(res.message);
                bootstrap.Modal.getInstance('#modalManuel').hide();
                loadList();
            } else {
                swalError(res.message);
            }
        });
    });

    // Şablon indir (Hakediş Excel)
    const personelListesi = <?= json_encode(array_map(fn($p) => [
        'ad' => $p->full_name,
        'tc' => \App\Helper\Security::safeDecrypt($p->kimlik_no ?? '')
    ], $personeller), JSON_UNESCAPED_UNICODE) ?>;

    function sablonIndir() {
        const wb = XLSX.utils.book_new();
        const satirlar = [['Personel Adı', 'TC Kimlik', 'Hakediş Yılı', 'Gün Sayısı', 'Açıklama']];
        personelListesi.forEach(p => satirlar.push([p.ad, p.tc, '', '', '']));
        const ws = XLSX.utils.aoa_to_sheet(satirlar);
        ws['!cols'] = [{ wch: 35 }, { wch: 14 }, { wch: 14 }, { wch: 12 }, { wch: 30 }];
        XLSX.utils.book_append_sheet(wb, ws, 'Hakedişler');
        XLSX.writeFile(wb, 'hakedis_sablonu.xlsx');
    }
    $('#btn-sablon-indir-modal').on('click', sablonIndir);

    // Devir Kullanım Şablon İndir
    function devirSablonIndir() {
        const wb = XLSX.utils.book_new();
        const satirlar = [['Personel Adı', 'TC Kimlik', 'Kullanılan Gün Sayısı', 'Açıklama']];
        personelListesi.forEach(p => satirlar.push([p.ad, p.tc, '', '']));
        const ws = XLSX.utils.aoa_to_sheet(satirlar);
        ws['!cols'] = [{ wch: 35 }, { wch: 14 }, { wch: 20 }, { wch: 35 }];
        XLSX.utils.book_append_sheet(wb, ws, 'Devir Kullanımları');
        XLSX.writeFile(wb, 'devir_kullanim_sablonu.xlsx');
    }
    $('#btn-devir-sablon-indir').on('click', devirSablonIndir);

    // Dropzone Hakediş Excel
    let parsedRows = [];
    const $zone    = $('#hakedis-dropzone');
    const $fileIn  = $('#excel-dosya');
    const $preview = $('#hakedis-dropzone-preview');

    $zone.on('click', () => $fileIn.trigger('click'));

    $zone.on('dragover dragenter', function(e) {
        e.preventDefault(); e.stopPropagation();
        $zone.addClass('dragover');
    });
    $zone.on('dragleave drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        $zone.removeClass('dragover');
    });
    $zone.on('drop', function(e) {
        const file = e.originalEvent.dataTransfer.files[0];
        if (file) parseFile(file);
    });

    $fileIn.on('change', function() {
        if (this.files[0]) parseFile(this.files[0]);
    });

    $('#hakedis-preview-remove').on('click', resetDropzone);

    function resetDropzone() {
        $fileIn.val('');
        $preview.hide();
        $('#excel-onizleme').hide();
        $('#excel-tbody').empty();
        $('#btn-excel-aktar').prop('disabled', true);
        parsedRows = [];
    }

    function parseFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xls','xlsx'].includes(ext)) { swalWarning('Sadece .xls ve .xlsx dosyaları desteklenir.'); return; }
        if (file.size > 5 * 1024 * 1024) { swalWarning('Dosya boyutu 5MB\'ı geçemez.'); return; }

        $('#hakedis-preview-name').text(file.name);
        $('#hakedis-preview-size').text((file.size / 1024).toFixed(1) + ' KB');
        $preview.css('display', 'flex');

        const reader = new FileReader();
        reader.onload = function(ev) {
            const wb   = XLSX.read(ev.target.result, { type: 'binary' });
            const ws   = wb.Sheets[wb.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            if (rows.length < 2) { swalWarning('Dosyada veri bulunamadı.'); return; }

            parsedRows = [];
            const tbody = $('#excel-tbody').empty();
            let gecerli = 0;

            rows.slice(1).forEach((r, i) => {
                const ad  = String(r[0] || '').trim();
                const tc  = String(r[1] || '').trim();
                const yil = parseInt(r[2]) || 0;
                const gun = parseInt(r[3]) || 0;
                const ack = String(r[4] || '').trim();
                if (!ad && !tc && !yil && !gun) return;

                parsedRows.push({ personel_adi: ad, tc_no: tc, yil, gun_sayisi: gun, aciklama: ack });
                const hata = (!ad || yil <= 0 || gun <= 0);
                if (!hata) gecerli++;
                tbody.append(`<tr class="${hata ? 'table-danger' : ''}">
                    <td class="text-center">${i + 2}</td>
                    <td>${ad || '<em class="text-danger">Boş</em>'}</td>
                    <td>${tc || '—'}</td>
                    <td class="text-center">${yil || '<em class="text-danger">—</em>'}</td>
                    <td class="text-center">${gun || '<em class="text-danger">—</em>'}</td>
                    <td>${ack || '—'}</td>
                </tr>`);
            });

            $('#excel-satir-sayisi').text(`${parsedRows.length} satır okundu, ${gecerli} geçerli`);
            $('#excel-onizleme').show();
            $('#btn-excel-aktar').prop('disabled', gecerli === 0);
        };
        reader.readAsBinaryString(file);
    }

    $('#btn-excel-aktar').on('click', function() {
        if (!parsedRows.length) return;
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Aktarılıyor...');

        fetch(HAKEDIS_API + '?action=bulk_add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(parsedRows)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') { swalError(res.message); return; }

            const basarili = res.sonuclar.filter(s => s.status === 'success').length;
            const atlanan  = res.sonuclar.filter(s => s.status === 'skip').length;
            const hatali   = res.sonuclar.filter(s => s.status === 'error').length;

            const detay = res.sonuclar.filter(s => s.status !== 'success')
                .map(s => `<li class="${s.status === 'error' ? 'text-danger' : 'text-muted'}">${s.message}</li>`)
                .join('');

            Swal.fire({
                icon: hatali > 0 ? 'warning' : 'success',
                title: 'Aktarım Tamamlandı',
                html: `<strong>${basarili}</strong> eklendi, <strong>${atlanan}</strong> atlandı, <strong>${hatali}</strong> hata.` +
                      (detay ? `<ul class="text-start mt-2 small" style="max-height:200px;overflow-y:auto">${detay}</ul>` : ''),
            });

            bootstrap.Modal.getInstance('#modalExcel').hide();
            loadList();
        })
        .finally(() => {
            $('#btn-excel-aktar').prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Aktar');
        });
    });

    $('#modalExcel').on('hidden.bs.modal', resetDropzone);

    // Dropzone Devir Kullanımı Excel
    let parsedDevirRows = [];
    const $devirZone    = $('#devir-dropzone');
    const $devirFileIn  = $('#devir-excel-dosya');
    const $devirPreview = $('#devir-dropzone-preview');

    $devirZone.on('click', () => $devirFileIn.trigger('click'));

    $devirZone.on('dragover dragenter', function(e) {
        e.preventDefault(); e.stopPropagation();
        $devirZone.addClass('dragover');
    });
    $devirZone.on('dragleave drop', function(e) {
        e.preventDefault(); e.stopPropagation();
        $devirZone.removeClass('dragover');
    });
    $devirZone.on('drop', function(e) {
        const file = e.originalEvent.dataTransfer.files[0];
        if (file) parseDevirFile(file);
    });

    $devirFileIn.on('change', function() {
        if (this.files[0]) parseDevirFile(this.files[0]);
    });

    $('#devir-preview-remove').on('click', resetDevirDropzone);

    function resetDevirDropzone() {
        $devirFileIn.val('');
        $devirPreview.hide();
        $('#devir-excel-onizleme').hide();
        $('#devir-excel-tbody').empty();
        parsedDevirRows = [];
    }

    function parseDevirFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xls','xlsx'].includes(ext)) { swalWarning('Sadece .xls ve .xlsx dosyaları desteklenir.'); return; }
        if (file.size > 5 * 1024 * 1024) { swalWarning('Dosya boyutu 5MB\'ı geçemez.'); return; }

        $('#devir-preview-name').text(file.name);
        $('#devir-preview-size').text((file.size / 1024).toFixed(1) + ' KB');
        $devirPreview.css('display', 'flex');

        const reader = new FileReader();
        reader.onload = function(ev) {
            const wb   = XLSX.read(ev.target.result, { type: 'binary' });
            const ws   = wb.Sheets[wb.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            if (rows.length < 2) { swalWarning('Dosyada veri bulunamadı.'); return; }

            parsedDevirRows = [];
            const tbody = $('#devir-excel-tbody').empty();
            let gecerli = 0;

            rows.slice(1).forEach((r, i) => {
                const ad  = String(r[0] || '').trim();
                const tc  = String(r[1] || '').trim();
                const gun = parseInt(r[2]) || 0;
                const ack = String(r[3] || '').trim();
                if (!ad && !tc && !gun) return;

                parsedDevirRows.push({ personel_adi: ad, tc_no: tc, kullanilan_gun: gun, aciklama: ack });
                const hata = (!ad && !tc) || (gun <= 0);
                if (!hata) gecerli++;
                tbody.append(`<tr class="${hata ? 'table-danger' : ''}">
                    <td class="text-center">${i + 2}</td>
                    <td>${ad || '<em class="text-danger">Boş</em>'}</td>
                    <td>${tc || '—'}</td>
                    <td class="text-center">${gun || '<em class="text-danger">—</em>'}</td>
                    <td>${ack || '—'}</td>
                </tr>`);
            });

            $('#devir-excel-satir-sayisi').text(`${parsedDevirRows.length} satır okundu, ${gecerli} geçerli`);
            $('#devir-excel-onizleme').show();
        };
        reader.readAsBinaryString(file);
    }

    $('#modalDevirKullanim').on('hidden.bs.modal', resetDevirDropzone);

    loadList();
});
</script>
