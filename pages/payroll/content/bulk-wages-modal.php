<?php
$today      = date('d.m.Y');
$year_end   = '31.12.' . (date('Y') + 5);
?>

<div class="modal modal-blur fade" id="bulk-wages-modal" tabindex="-1" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ti ti-trending-up icon me-2 text-primary"></i> Ücret Güncelleme
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>

      <div class="modal-body p-0">
        <div class="row g-0" style="min-height: 540px;">

          <!-- Sol Panel -->
          <div class="col-md-4 border-end bg-light p-4" style="overflow-y: auto; max-height: 80vh;">

            <!-- Zam Değerleri -->
            <div class="card mb-3 shadow-sm">
              <div class="card-header bg-white py-2">
                <h6 class="card-title mb-0">
                  <i class="ti ti-percentage icon me-1 text-warning"></i> Zam Değerleri
                </h6>
              </div>
              <div class="card-body">
                <div class="mb-2">
                  <label class="form-label required">Zam Oranı (%)</label>
                  <input type="number" id="bw-raise-pct" class="form-control" placeholder="Örn: 15.5 veya 10" min="0.01" step="0.01">
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label required">Başlangıç</label>
                    <input type="text" id="bw-raise-start" class="form-control bw-flatpickr" placeholder="<?= $today ?>" value="<?= $today ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label required">Bitiş</label>
                    <input type="text" id="bw-raise-end" class="form-control bw-flatpickr" placeholder="<?= $year_end ?>" value="<?= $year_end ?>">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Açıklama</label>
                  <input type="text" id="bw-raise-desc" class="form-control" placeholder="Örn: Haziran Enflasyon Zammı">
                </div>
                <button type="button" id="bw-btn-raise" class="btn btn-dark w-100">
                  <i class="ti ti-check icon me-1"></i> Zam Uygula
                </button>
              </div>
            </div>

            <!-- Sabit Ücret -->
            <div class="card mb-3 shadow-sm">
              <div class="card-header bg-white py-2">
                <h6 class="card-title mb-0">
                  <i class="ti ti-currency-lira icon me-1 text-primary"></i> Sabit Ücret Belirle
                </h6>
              </div>
              <div class="card-body">
                <div class="mb-2">
                  <label class="form-label required">Yeni Ücret Tutarı (₺)</label>
                  <input type="text" id="bw-fixed-amount" class="form-control bw-money" placeholder="Örn: 25000 veya 22500,50">
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label required">Başlangıç</label>
                    <input type="text" id="bw-fixed-start" class="form-control bw-flatpickr" placeholder="<?= $today ?>" value="<?= $today ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label required">Bitiş</label>
                    <input type="text" id="bw-fixed-end" class="form-control bw-flatpickr" placeholder="<?= $year_end ?>" value="<?= $year_end ?>">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Açıklama</label>
                  <input type="text" id="bw-fixed-desc" class="form-control" placeholder="Örn: Haziran Taban Ücret Güncellemesi">
                </div>
                <button type="button" id="bw-btn-fixed" class="btn btn-dark w-100">
                  <i class="ti ti-check icon me-1"></i> Ücreti Güncelle
                </button>
              </div>
            </div>

            <!-- Bireysel Ücret -->
            <div class="card shadow-sm">
              <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0">
                  <i class="ti ti-users icon me-1 text-success"></i> Bireysel Ücret
                </h6>
                <span class="badge bg-success-lt" id="bw-individual-badge" style="display:none!important;">Aktif</span>
              </div>
              <div class="card-body">
                <p class="text-secondary small mb-2">Tabloda her personel için ayrı ücret girin.</p>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label required">Başlangıç</label>
                    <input type="text" id="bw-ind-start" class="form-control bw-flatpickr" placeholder="<?= $today ?>" value="<?= $today ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label required">Bitiş</label>
                    <input type="text" id="bw-ind-end" class="form-control bw-flatpickr" placeholder="<?= $year_end ?>" value="<?= $year_end ?>">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Açıklama</label>
                  <input type="text" id="bw-ind-desc" class="form-control" placeholder="Açıklama (opsiyonel)">
                </div>
                <button type="button" id="bw-btn-individual-toggle" class="btn btn-outline-success w-100 mb-2">
                  <i class="ti ti-table-column icon me-1"></i> Bireysel Giriş Modunu Aç
                </button>
                <button type="button" id="bw-btn-individual-save" class="btn btn-success w-100" style="display:none;">
                  <i class="ti ti-check icon me-1"></i> Toplu Kaydet
                </button>
              </div>
            </div>

          </div>

          <!-- Sağ Panel: Personel Tablosu -->
          <div class="col-md-8 p-4" style="overflow-y: auto; max-height: 80vh;">

            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <h6 class="mb-0">
                Personel Seçin
                <span class="badge bg-secondary ms-1" id="bw-selected-count">0</span>
              </h6>
              <div class="d-flex gap-2 flex-wrap align-items-center">
                <input type="text" id="bw-search" class="form-control form-control-sm" placeholder="Personel ara..." style="width: 180px;">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="bw-select-all">Tümünü Seç</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="bw-clear-all">Temizle</button>
              </div>
            </div>

            <!-- Tablo -->
            <div class="table-responsive">
              <table class="table table-vcenter card-table table-hover" id="bw-persons-table">
                <thead>
                  <tr>
                    <th style="width:36px;"></th>
                    <th>Ad Soyad</th>
                    <th>Ünvan</th>
                    <th>İşe Başlama</th>
                    <th class="text-end">Mevcut Ücret</th>
                    <th class="text-end bw-col-individual" style="display:none;">Yeni Ücret</th>
                  </tr>
                </thead>
                <tbody id="bw-persons-tbody">
                  <tr>
                    <td colspan="6" class="text-center py-4 text-secondary">
                      <div class="spinner-border spinner-border-sm" role="status"></div>
                      Yükleniyor...
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Alt Bar -->
            <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="text-secondary small">Sayfa başına:</span>
                <select id="bw-per-page" class="form-select form-select-sm" style="width:80px;">
                  <option value="50">50</option>
                  <option value="100" selected>100</option>
                  <option value="250">250</option>
                </select>
                <span class="text-secondary small" id="bw-total-label"></span>
              </div>
              <div class="d-flex align-items-center gap-1">
                <button class="btn btn-sm btn-icon" id="bw-page-first" title="İlk Sayfa"><i class="ti ti-chevrons-left"></i></button>
                <button class="btn btn-sm btn-icon" id="bw-page-prev" title="Önceki"><i class="ti ti-chevron-left"></i></button>
                <span class="text-secondary small" id="bw-page-label">Sayfa 1 / 1</span>
                <button class="btn btn-sm btn-icon" id="bw-page-next" title="Sonraki"><i class="ti ti-chevron-right"></i></button>
                <button class="btn btn-sm btn-icon" id="bw-page-last" title="Son Sayfa"><i class="ti ti-chevrons-right"></i></button>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Kapat</button>
      </div>

    </div>
  </div>
</div>
