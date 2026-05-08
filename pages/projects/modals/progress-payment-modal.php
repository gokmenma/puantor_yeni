 <?php
    require_once 'App/Helper/date.php';

    use App\Helper\Date;

    ?>


 <div class="modal modal-blur fade" id="progress-payment-modal" tabindex="-1" style="display: none;" aria-hidden="true">
     <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
         <div class="modal-content">
             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             <div class="modal-status bg-success"></div>
             <div class="modal-body text-center pb-0">
                 <i class="ti ti-building-estate icon-lg text-success"></i>
                 <h3 id="progress_payment_project_name">
                 </h3>
                 <p>
                     <small class="text-success">Hakediş eklemek için aşağıdaki bilgileri doldurunuz</small>
                 </p>
             </div>
             <div class="container ps-4 pe-4 py-4">
                 <form action="" id="progress_payment_modalForm">
                     <input type="hidden" class="form-control" name="progress_payment_id" value="0">
                     <input type="hidden" class="form-control" name="progress_payment_project_id" id="progress_payment_project_id" value="0">

                     <div class="text-secondary mt-3">
                         <label for="">Hakediş Tutarı</label>
                         <input type="text" name="progress_payment_amount" class="form-control mt-1 money">
                     </div>
                     <div class="text-secondary mt-3">

                         <label for="">Hakediş Tarihi</label>
                         <input type="text" name="progress_payment_date" value="<?php echo date('d.m.Y') ?>" class="form-control flatpickr mt-1">
                     </div>
                      <!-- Kasa seçiniz -->
                    <div class="text-secondary mt-3">
                    <label for="">Kasa</label>
                        <?php  echo $financialHelper->getCasesSelectByUser("progress_payment_cases",$case_id); ?>
                    </div>


                     <div class="text-secondary mt-3">
                         <label for="">Açıklama</label>
                         <textarea name="progress_payment_description" class="form-control mt-1"
                             placeholder="Hakediş hakkında açıklama yazınız" style="min-height:100px"></textarea>
                     </div>
                 </form>
             </div>
             <div class="modal-footer">
                 <div class="w-100">
                     <div class="row">
                         <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">
                                 Vazgeç
                             </a></div>
                         <div class="col">
                             <a href="#" class="btn btn-success w-100" id="progress_payment_addButton">
                                 Hakediş Ekle
                             </a>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 </div>