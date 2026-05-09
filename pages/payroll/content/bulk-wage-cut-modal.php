<div class="modal modal-blur fade" id="bulk-wage-cut-modal" tabindex="-1" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-danger text-white py-3">
        <h5 class="modal-title d-flex align-items-center text-white font-weight-bold">
          <i class="ti ti-circle-minus icon me-2" style="font-size: 1.4rem;"></i>
          Toplu Kesinti Ekle (Excel)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-4 text-center">
          <p class="text-secondary small">
            Aşağıdaki butona tıklayarak personellerinizin listesini içeren özel Excel şablonunu indirin, kesinti miktarlarını doldurun ve dosyayı buraya yükleyin.
          </p>
          <a href="#" id="download-wage-cut-template" class="btn btn-outline-danger btn-pill w-100 py-2 d-flex align-items-center justify-content-center" style="gap: 8px; border-width: 2px;">
            <i class="ti ti-file-download" style="font-size: 1.2rem;"></i>
            <strong>Kesinti Şablonu İndir (.xls)</strong>
          </a>
        </div>

        <div class="dropzone-area" id="dropzone-wage-cut" style="border-color: #fca5a5; background: #fffdfd;">
          <div class="dropzone-icon">
            <i class="ti ti-cloud-upload text-danger" style="font-size: 3rem; transition: transform 0.2s;"></i>
          </div>
          <div class="dropzone-text">
            <span class="dropzone-title">Excel dosyasını buraya sürükleyin veya tıklayın</span>
            <span class="dropzone-sub">Sadece .xls ve .xlsx dosyaları desteklenir (Max 5MB)</span>
          </div>
          <input type="file" id="bulk-wage-cut-file" accept=".xls,.xlsx" style="display: none;">
        </div>

        <div class="dropzone-preview" id="preview-wage-cut">
          <div class="preview-icon">
            <i class="ti ti-file-spreadsheet" style="color: #ef4444;"></i>
          </div>
          <div class="preview-details">
            <span class="preview-name" id="preview-name-wage-cut">dosya-adi.xlsx</span>
            <span class="preview-size" id="preview-size-wage-cut">0 KB</span>
          </div>
          <div class="preview-remove" id="remove-wage-cut" title="Dosyayı Kaldır">
            <i class="ti ti-trash"></i>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-0 p-3">
        <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Kapat</button>
        <button type="button" class="btn btn-danger px-4" id="btn-upload-wage-cut" disabled>Yükle</button>
      </div>
    </div>
  </div>
</div>

<style>
#dropzone-wage-cut:hover, #dropzone-wage-cut.dragover {
  border-color: #dc2626 !important;
  background: #fef2f2 !important;
}
</style>
