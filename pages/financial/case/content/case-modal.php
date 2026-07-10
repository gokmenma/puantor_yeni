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
                <h5 class="modal-title" id="caseModalTitle">Yeni Kasa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="caseForm">
                    <input type="hidden" name="id" id="case_id_input" value="0">
                    <input type="hidden" name="action" value="saveCase">
                    
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label mb-0">Varsayılan Kasa</label>
                        </div>
                        <div class="col-md-9">
                            <label class="form-check form-switch mb-0">
                                <input class="form-check-input" name="default_case" id="default_case" type="checkbox">
                                <span class="form-check-label form-check-label-on">Varsayılan</span>
                                <span class="form-check-label form-check-label-off">Varsayılan Değil</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Firması</label>
                            <?php echo $company->myCompanySelect("firm_company", $_SESSION['firm_id'], "disabled"); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kasa Adı <font class="text-danger">(*)</font></label>
                            <input type="text" name="case_name" id="case_name" class="form-control" placeholder="Kasa Adı giriniz">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Bankası</label>
                            <input type="text" name="bank_name" id="bank_name" class="form-control" placeholder="Banka adı giriniz">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şubesi</label>
                            <input type="text" name="branch_name" id="branch_name" class="form-control" placeholder="Şube adı giriniz">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Açıklama</label>
                            <input type="text" name="description" id="description" class="form-control" placeholder="Açıklama giriniz">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kasa Para Birimi</label>
                            <?php echo Helper::moneySelect('case_money_unit', '1'); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Yetkili Kullanıcılar</label>
                            <div id="modal-user-ids-container">
                                <?php echo $userHelper->userSelectMultiple("user_ids[]", []); ?>
                            </div>
                            <span class="form-text text-muted" style="font-size: 0.75rem;">Firma Sahibi; Kasayı Görmesini İstediği Kullanıcıları Ekleyebilir...</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="saveCase">Kaydet</button>
            </div>
        </div>
    </div>
</div>
