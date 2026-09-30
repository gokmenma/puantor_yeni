<?php
$today      = date('d.m.Y');
$year_end   = '31.12.' . (date('Y') + 5);
?>

<div class="modal modal-blur fade" id="bulk-wages-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content shadow-lg border" style="border-radius: 12px; border-color: #dbe3ec !important; overflow: hidden;">

      <!-- Modal Header -->
      <div class="modal-header bg-white px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-md rounded-3 bg-azure-lt text-azure shadow-sm" style="width: 42px; height: 42px;">
            <i class="ti ti-trending-up" style="font-size: 22px;"></i>
          </div>
          <div>
            <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.1rem; letter-spacing: -0.3px;">Toplu Ücret Güncelleme</h4>
            <div class="text-secondary small mt-0.5" style="font-size: 12.5px;">Zam oranı uygulama, sabit ücret veya bireysel ücret belirleme</div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-0 bg-white">
        <div class="row g-0" style="min-height: 540px;">

          <!-- Sol Panel -->
          <div class="col-md-4 border-end p-3 bg-light" style="overflow-y: auto; max-height: 80vh; background-color: #f8fafc; border-color: #e2e8f0 !important;">

            <!-- Zam Değerleri -->
            <div class="card mb-3 shadow-xs border" style="border-radius: 10px; border-color: #e2e8f0;">
              <div class="card-header bg-white py-2 px-3" style="border-bottom: 1px solid #e2e8f0;">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center" style="font-size: 13px;">
                  <i class="ti ti-percentage me-1.5 text-warning" style="font-size: 16px;"></i> Zam Değerleri
                </h6>
              </div>
              <div class="card-body p-3">
                <div class="mb-2">
                  <label class="form-label required fw-semibold text-secondary small mb-1">Zam Oranı (%)</label>
                  <input type="number" id="bw-raise-pct" class="form-control" placeholder="Örn: 15.5 veya 10" min="0.01" step="0.01">
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label required fw-semibold text-secondary small mb-1">Başlangıç</label>
                    <input type="text" id="bw-raise-start" class="form-control bw-flatpickr" placeholder="<?= $today ?>" value="<?= $today ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label required fw-semibold text-secondary small mb-1">Bitiş</label>
                    <input type="text" id="bw-raise-end" class="form-control bw-flatpickr" placeholder="<?= $year_end ?>" value="<?= $year_end ?>">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-secondary small mb-1">Açıklama</label>
                  <input type="text" id="bw-raise-desc" class="form-control" placeholder="Örn: Haziran Enflasyon Zammı">
                </div>
                <button type="button" id="bw-btn-raise" class="btn btn-dark w-100 shadow-sm" style="height: 34px; font-size: 13px;">
                  <i class="ti ti-check me-1"></i> Zam Uygula
                </button>
              </div>
            </div>

            <!-- Sabit Ücret -->
            <div class="card mb-3 shadow-xs border" style="border-radius: 10px; border-color: #e2e8f0;">
              <div class="card-header bg-white py-2 px-3" style="border-bottom: 1px solid #e2e8f0;">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center" style="font-size: 13px;">
                  <i class="ti ti-currency-lira me-1.5 text-primary" style="font-size: 16px;"></i> Sabit Ücret Belirle
                </h6>
              </div>
              <div class="card-body p-3">
                <div class="mb-2">
                  <label class="form-label required fw-semibold text-secondary small mb-1">Yeni Ücret Tutarı (₺)</label>
                  <input type="text" id="bw-fixed-amount" class="form-control bw-money" placeholder="Örn: 25000 veya 22500,50">
                </div>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label required fw-semibold text-secondary small mb-1">Başlangıç</label>
                    <input type="text" id="bw-fixed-start" class="form-control bw-flatpickr" placeholder="<?= $today ?>" value="<?= $today ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label required fw-semibold text-secondary small mb-1">Bitiş</label>
                    <input type="text" id="bw-fixed-end" class="form-control bw-flatpickr" placeholder="<?= $year_end ?>" value="<?= $year_end ?>">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-secondary small mb-1">Açıklama</label>
                  <input type="text" id="bw-fixed-desc" class="form-control" placeholder="Örn: Taban Ücret Güncellemesi">
                </div>
                <button type="button" id="bw-btn-fixed" class="btn btn-dark w-100 shadow-sm" style="height: 34px; font-size: 13px;">
                  <i class="ti ti-check me-1"></i> Ücreti Güncelle
                </button>
              </div>
            </div>

            <!-- Bireysel Ücret -->
            <div class="card shadow-xs border" style="border-radius: 10px; border-color: #e2e8f0;">
              <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #e2e8f0;">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center" style="font-size: 13px;">
                  <i class="ti ti-users me-1.5 text-success" style="font-size: 16px;"></i> Bireysel Ücret
                </h6>
                <span class="badge bg-success-lt" id="bw-individual-badge" style="display:none!important;">Aktif</span>
              </div>
              <div class="card-body p-3">
                <p class="text-secondary small mb-2">Tabloda her personel için ayrı ücret girin.</p>
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label required fw-semibold text-secondary small mb-1">Başlangıç</label>
                    <input type="text" id="bw-ind-start" class="form-control bw-flatpickr" placeholder="<?= $today ?>" value="<?= $today ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label required fw-semibold text-secondary small mb-1">Bitiş</label>
                    <input type="text" id="bw-ind-end" class="form-control bw-flatpickr" placeholder="<?= $year_end ?>" value="<?= $year_end ?>">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold text-secondary small mb-1">Açıklama</label>
                  <input type="text" id="bw-ind-desc" class="form-control" placeholder="Açıklama (opsiyonel)">
                </div>
                <button type="button" id="bw-btn-individual-toggle" class="btn btn-outline-success w-100 mb-2 shadow-xs" style="height: 34px; font-size: 13px;">
                  <i class="ti ti-table-column me-1"></i> Bireysel Giriş Modunu Aç
                </button>
                <button type="button" id="bw-btn-individual-save" class="btn btn-success w-100 shadow-sm" style="display:none; height: 34px; font-size: 13px;">
                  <i class="ti ti-check me-1"></i> Toplu Kaydet
                </button>
              </div>
            </div>

          </div>

          <!-- Sağ Panel: Personel Tablosu -->
          <div class="col-md-8 p-3" style="overflow-y: auto; max-height: 80vh;">

            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold text-dark" style="font-size: 13.5px;">Personel Seçin:</span>
                <span class="badge bg-secondary-lt fw-bold" id="bw-selected-count">0 seçildi</span>
              </div>
              <div class="d-flex gap-2 flex-wrap align-items-center">
                <div class="input-icon" style="width: 180px;">
                  <span class="input-icon-addon">
                    <i class="ti ti-search text-muted"></i>
                  </span>
                  <input type="text" id="bw-search" class="form-control form-control-sm" placeholder="Personel ara...">
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="bw-select-all">Tümünü Seç</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="bw-clear-all">Temizle</button>
              </div>
            </div>

            <!-- Tablo -->
            <div class="table-responsive border rounded-2" style="border-color: #e2e8f0 !important;">
              <table class="table table-vcenter card-table table-hover table-sm mb-0" id="bw-persons-table">
                <thead class="bg-light">
                  <tr>
                    <th style="width:36px;" class="ps-3"></th>
                    <th class="text-secondary fw-semibold small">Ad Soyad</th>
                    <th class="text-secondary fw-semibold small">Ünvan</th>
                    <th class="text-secondary fw-semibold small">İşe Başlama</th>
                    <th class="text-end text-secondary fw-semibold small pe-3">Mevcut Ücret</th>
                    <th class="text-end bw-col-individual text-secondary fw-semibold small pe-3" style="display:none;">Yeni Ücret</th>
                  </tr>
                </thead>
                <tbody id="bw-persons-tbody">
                  <tr>
                    <td colspan="6" class="text-center py-4 text-secondary">
                      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                      <span class="ms-2">Yükleniyor...</span>
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
                <button class="btn btn-sm btn-icon btn-outline-secondary" id="bw-page-first" title="İlk Sayfa"><i class="ti ti-chevrons-left"></i></button>
                <button class="btn btn-sm btn-icon btn-outline-secondary" id="bw-page-prev" title="Önceki"><i class="ti ti-chevron-left"></i></button>
                <span class="text-secondary small px-2" id="bw-page-label">Sayfa 1 / 1</span>
                <button class="btn btn-sm btn-icon btn-outline-secondary" id="bw-page-next" title="Sonraki"><i class="ti ti-chevron-right"></i></button>
                <button class="btn btn-sm btn-icon btn-outline-secondary" id="bw-page-last" title="Son Sayfa"><i class="ti ti-chevrons-right"></i></button>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid #e2e8f0;">
        <button type="button" class="btn btn-outline-secondary px-3 py-1.5 ms-auto" data-bs-dismiss="modal" style="height: 34px; font-size: 13px;">Kapat</button>
      </div>

    </div>
  </div>
</div>
