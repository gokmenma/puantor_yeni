<div class="modal modal-blur fade" id="bulk-income-modal" tabindex="-1" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-success text-white py-3">
        <h5 class="modal-title d-flex align-items-center text-white font-weight-bold">
          <i class="ti ti-circle-plus icon me-2" style="font-size: 1.4rem;"></i>
          Toplu Gelir Ekle (Excel)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-4 text-center">
          <p class="text-secondary small">
            Aşağıdaki butona tıklayarak personellerinizin listesini içeren özel Excel şablonunu indirin, gelir miktarlarını doldurun ve dosyayı buraya yükleyin.
          </p>
          <a href="#" id="download-income-template" class="btn btn-outline-success btn-pill w-100 py-2 d-flex align-items-center justify-content-center" style="gap: 8px; border-width: 2px;">
            <i class="ti ti-file-download" style="font-size: 1.2rem;"></i>
            <strong>Gelir Şablonu İndir (.xls)</strong>
          </a>
        </div>

        <div class="dropzone-area" id="dropzone-income">
          <div class="dropzone-icon">
            <i class="ti ti-cloud-upload text-success" style="font-size: 3rem; transition: transform 0.2s;"></i>
          </div>
          <div class="dropzone-text">
            <span class="dropzone-title">Excel dosyasını buraya sürükleyin veya tıklayın</span>
            <span class="dropzone-sub">Sadece .xls ve .xlsx dosyaları desteklenir (Max 5MB)</span>
          </div>
          <input type="file" id="bulk-income-file" accept=".xls,.xlsx" style="display: none;">
        </div>

        <div class="dropzone-preview" id="preview-income">
          <div class="preview-icon">
            <i class="ti ti-file-spreadsheet"></i>
          </div>
          <div class="preview-details">
            <span class="preview-name" id="preview-name-income">dosya-adi.xlsx</span>
            <span class="preview-size" id="preview-size-income">0 KB</span>
          </div>
          <div class="preview-remove" id="remove-income" title="Dosyayı Kaldır">
            <i class="ti ti-trash"></i>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-0 p-3">
        <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Kapat</button>
        <button type="button" class="btn btn-success px-4" id="btn-upload-income" disabled>Yükle</button>
      </div>
    </div>
  </div>
</div>

<style>
.dropzone-area {
  border: 2px dashed #a3e635;
  border-radius: 12px;
  padding: 2.5rem 1.5rem;
  text-align: center;
  background: #fcfdf9;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}
.dropzone-area:hover, .dropzone-area.dragover {
  border-color: #15803d;
  background: #f0fdf4;
}
.dropzone-area:hover .dropzone-icon i, .dropzone-area.dragover .dropzone-icon i {
  transform: translateY(-5px);
}
.dropzone-title {
  display: block;
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
}
.dropzone-sub {
  display: block;
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.25rem;
}
.dropzone-preview {
  margin-top: 1.25rem;
  display: none;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.dropzone-preview .preview-icon {
  font-size: 1.8rem;
  color: #15803d;
  display: flex;
  align-items: center;
}
.dropzone-preview .preview-details {
  flex-grow: 1;
  text-align: left;
}
.dropzone-preview .preview-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: #1e293b;
  display: block;
  word-break: break-all;
}
.dropzone-preview .preview-size {
  font-size: 0.75rem;
  color: #64748b;
}
.dropzone-preview .preview-remove {
  cursor: pointer;
  color: #ef4444;
  font-size: 1.25rem;
  transition: color 0.2s;
  display: flex;
  align-items: center;
}
.dropzone-preview .preview-remove:hover {
  color: #b91c1c;
}
</style>
