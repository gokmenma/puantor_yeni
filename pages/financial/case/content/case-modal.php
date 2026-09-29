<?php
if (!defined("ROOT") || !isset($_SESSION['user'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Erişim Engellendi");
}
use App\Helper\Helper;
?>
<div class="modal modal-blur fade" id="case-modal" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2" id="caseModalTitle">
                    <i class="ti ti-wallet text-primary"></i>
                    <span>Yeni Kasa</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="caseForm">
                    <input type="hidden" name="id" id="case_id_input" value="0">
                    <input type="hidden" name="action" value="saveCase">
                    
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label mb-0 fw-bold">Varsayılan Kasa</label>
                        </div>
                        <div class="col-md-9">
                            <label class="form-check form-switch mb-0">
                                <input class="form-check-input" name="default_case" id="default_case" type="checkbox">
                                <span class="form-check-label form-check-label-on fw-medium text-success">Varsayılan Kasa Olarak Ayarla</span>
                                <span class="form-check-label form-check-label-off text-muted">Varsayılan Değil</span>
                            </label>
                            <small class="text-muted d-block mt-1">Varsayılan kasa seçildiğinde, sistemdeki diğer kasa varsayılan olmaktan çıkarılır.</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Bağlı Firma</label>
                            <?php echo $company->myCompanySelect("firm_company", $_SESSION['firm_id'], "disabled"); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kasa Adı <span class="text-danger">(*)</span></label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-wallet"></i></span>
                                <input type="text" name="case_name" id="case_name" class="form-control" placeholder="Örn: Merkez TL Kasası" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Banka Adı</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-building-bank"></i></span>
                                <input type="text" name="bank_name" id="bank_name" class="form-control" placeholder="Örn: Garanti BBVA (Elden kasa ise boş bırakın)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banka Şubesi / IBAN</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-building"></i></span>
                                <input type="text" name="branch_name" id="branch_name" class="form-control" placeholder="Örn: Kadıköy Şb. veya TR..">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kasa Para Birimi <span class="text-danger">(*)</span></label>
                            <?php echo Helper::moneySelect('case_money_unit', '1'); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Yetkili Kullanıcılar</label>
                            <div id="modal-user-ids-container">
                                <?php echo $userHelper->userSelectMultiple("user_ids[]", []); ?>
                            </div>
                            <span class="form-text text-muted" style="font-size: 0.75rem;">Firma sahibi, kasayı görmesini ve işlem yapmasını istediği kullanıcıları seçebilir.</span>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label class="form-label">Açıklama</label>
                            <textarea name="description" id="description" class="form-control" rows="2" placeholder="Kasa hakkında notlar veya açıklama..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Vazgeç
                </button>
                <button type="button" class="btn btn-primary" id="saveCase">
                    <i class="ti ti-device-floppy me-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>
