<div class="modal modal-blur fade" id="bulk-income-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
    <div class="modal-content shadow-lg border" style="border-radius: 12px; border-color: #dbe3ec !important; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header bg-white px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-md rounded-3 bg-success-lt text-success shadow-sm" style="width: 42px; height: 42px;">
            <i class="ti ti-file-spreadsheet" style="font-size: 22px;"></i>
          </div>
          <div>
            <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.1rem; letter-spacing: -0.3px;">Toplu Gelir Ekle (Excel)</h4>
            <div class="text-secondary small mt-0.5" style="font-size: 12.5px;">Excel şablonu ile personellere toplu prim / ek gelir yükleyin</div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4 bg-white">
        <div class="mb-3 text-center">
          <p class="text-secondary small mb-3">
            Aşağıdaki butona tıklayarak personellerinizin listesini içeren özel Excel şablonunu indirin, gelir miktarlarını doldurun ve dosyayı buraya yükleyin.
          </p>
          <a href="#" id="download-income-template" class="btn btn-outline-success w-100 py-2 d-flex align-items-center justify-content-center shadow-xs" style="gap: 8px; border-radius: 8px; font-weight: 600;">
            <i class="ti ti-file-download" style="font-size: 1.2rem;"></i>
            <span>Gelir Şablonu İndir (.xls)</span>
          </a>
        </div>

        <div class="dropzone-area" id="dropzone-income">
          <div class="dropzone-icon">
            <i class="ti ti-cloud-upload text-success" style="font-size: 2.8rem; transition: transform 0.2s;"></i>
          </div>
          <div class="dropzone-text text-center">
            <span class="dropzone-title fw-semibold text-dark">Excel dosyasını buraya sürükleyin veya tıklayın</span>
            <span class="dropzone-sub text-muted small d-block mt-1">Sadece .xls ve .xlsx dosyaları desteklenir (Maks. 5MB)</span>
          </div>
        </div>
        <input type="file" id="bulk-income-file" accept=".xls,.xlsx" style="display: none;">

        <div class="dropzone-preview mt-3" id="preview-income">
          <div class="preview-icon">
            <i class="ti ti-file-spreadsheet text-success fs-1"></i>
          </div>
          <div class="preview-details">
            <span class="preview-name fw-medium text-dark" id="preview-name-income">dosya-adi.xlsx</span>
            <span class="preview-size text-muted small" id="preview-size-income">0 KB</span>
          </div>
          <div class="preview-remove cursor-pointer text-danger p-1" id="remove-income" title="Dosyayı Kaldır">
            <i class="ti ti-trash fs-2"></i>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid #e2e8f0;">
        <button type="button" class="btn btn-outline-secondary px-3 py-1.5" data-bs-dismiss="modal" style="height: 34px; font-size: 13px;">Kapat</button>
        <button type="button" class="btn btn-success shadow-sm px-4 py-1.5 d-inline-flex align-items-center gap-1.5" id="btn-upload-income" disabled style="height: 34px; font-size: 13px;">
          <i class="ti ti-upload" style="font-size: 16px;"></i> Yükle
        </button>
      </div>

    </div>
  </div>
</div>

<style>
#bulk-income-modal .dropzone-area {
  border: 2px dashed #86efac;
  border-radius: 10px;
  padding: 2rem 1.25rem;
  text-align: center;
  background: #f0fdf4;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}
#bulk-income-modal .dropzone-area:hover, 
#bulk-income-modal .dropzone-area.dragover {
  border-color: #16a34a;
  background: #dcfce7;
}
#bulk-income-modal .dropzone-area:hover .dropzone-icon i, 
#bulk-income-modal .dropzone-area.dragover .dropzone-icon i {
  transform: translateY(-4px);
}
#bulk-income-modal .dropzone-preview {
  display: none;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
#bulk-income-modal .dropzone-preview .preview-details {
  flex-grow: 1;
  text-align: left;
}
#bulk-income-modal .dropzone-preview .preview-name {
  display: block;
  word-break: break-all;
  font-size: 13px;
}
</style>
