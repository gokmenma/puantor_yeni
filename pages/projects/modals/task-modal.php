<style>#task-modal .select2-container { width: 100% !important; }</style>
<div class="modal modal-blur fade" id="task-modal" tabindex="-1" style="display:none;" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="task-modal-title">Yeni Görev</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="task-modal-form">
                <input type="hidden" name="action" value="save_task">
                <input type="hidden" name="task_id" id="task_id" value="0">
                <input type="hidden" name="project_id" id="task_project_id" value="0">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Görev Adı <span class="text-danger">*</span></label>
                            <input type="text" name="task_name" id="task_name" class="form-control" placeholder="Görev adını giriniz">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sorumlu Kişi</label>
                            <input type="text" name="responsible" id="task_responsible" class="form-control" placeholder="Sorumlu adı">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durum</label>
                            <select name="status" id="task_status" class="form-select select2">
                                <option value="0">Bekliyor</option>
                                <option value="1">Devam Ediyor</option>
                                <option value="2">Tamamlandı</option>
                                <option value="3">İptal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="text" name="start_date" id="task_start_date" class="form-control flatpickr" placeholder="gg.aa.yyyy">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bitiş Tarihi</label>
                            <input type="text" name="end_date" id="task_end_date" class="form-control flatpickr" placeholder="gg.aa.yyyy">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tamamlanma Oranı: <span id="task_percent_label">0</span>%</label>
                            <input type="range" name="completion_percent" id="task_completion_percent" class="form-range" min="0" max="100" step="5" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea name="description" id="task_description" class="form-control" rows="3" placeholder="Görev hakkında açıklama"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted me-auto" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary" id="task-save-btn">
                        <i class="ti ti-device-floppy icon me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
