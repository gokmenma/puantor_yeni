<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/App/Helper/bordroHelper.php";
$bordroHelper = new BordroHelper();
?>
<div class="modal modal-blur fade" id="load-payment-modal" tabindex="-1" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-info text-white py-3">
        <h5 class="modal-title d-flex align-items-center text-white font-weight-bold">
          <i class="ti ti-table-import icon me-2" style="font-size: 1.4rem;"></i>
          Ödeme Yükle (Excel)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-4 text-center">
          <p class="text-secondary small">
            Aşağıdaki butona tıklayarak personellerinizin listesini içeren özel Excel şablonunu indirin, ödeme miktarlarını doldurun ve dosyayı buraya yükleyin.
          </p>
          <a href="pages/payroll/xls/payment-load.php" class="btn btn-outline-info btn-pill w-100 py-2 d-flex align-items-center justify-content-center" style="gap: 8px; border-width: 2px;">
            <i class="ti ti-file-download" style="font-size: 1.2rem;"></i>
            <strong>Ödeme Şablonu İndir (.xls)</strong>
          </a>
        </div>

        <div class="mb-4">
          <label for="payment_inc_exp_type" class="form-label font-weight-bold text-dark">Kategori / Ödeme Türü:</label>
          <?php echo $bordroHelper->getIncExpSelectByFirmAndType("payment_inc_exp_type") ?>
        </div>

        <div class="dropzone-area" id="dropzone-payment-load" style="border-color: #93c5fd; background: #f8fafc;">
          <div class="dropzone-icon">
            <i class="ti ti-cloud-upload text-info" style="font-size: 3rem; transition: transform 0.2s;"></i>
          </div>
          <div class="dropzone-text">
            <span class="dropzone-title">Excel dosyasını buraya sürükleyin veya tıklayın</span>
            <span class="dropzone-sub">Sadece .xls ve .xlsx dosyaları desteklenir (Max 5MB)</span>
          </div>
          <input type="file" id="bulk-payment-load-file" accept=".xls,.xlsx" style="display: none;">
        </div>

        <div class="dropzone-preview" id="preview-payment-load">
          <div class="preview-icon">
            <i class="ti ti-file-spreadsheet" style="color: #0ea5e9;"></i>
          </div>
          <div class="preview-details">
            <span class="preview-name" id="preview-name-payment-load">dosya-adi.xlsx</span>
            <span class="preview-size" id="preview-size-payment-load">0 KB</span>
          </div>
          <div class="preview-remove" id="remove-payment-load" title="Dosyayı Kaldır">
            <i class="ti ti-trash"></i>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-0 p-3">
        <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Kapat</button>
        <button type="button" class="btn btn-info px-4 text-white" id="btn-upload-payment-load" disabled>Yükle</button>
      </div>
    </div>
  </div>
</div>

<style>
#dropzone-payment-load:hover, #dropzone-payment-load.dragover {
  border-color: #0284c7 !important;
  background: #f0f9ff !important;
}
</style>