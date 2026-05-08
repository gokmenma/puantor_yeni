 <?php 

use App\Helper\Date;


?> 


<div class="modal modal-blur fade" id="income_modal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-success"></div>
            <div class="modal-body text-center pb-0">
                <!-- Download SVG icon from http://tabler-icons.io/i/alert-triangle -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon mb-2 text-green icon-lg">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>
                    <path d="M9 12l2 2l4 -4"></path>
                </svg>
                <h3 id="person_name_income">
                </h3>
                <p>
                    <small class="text-success">Gelir eklemek için aşağıdaki bilgileri doldurunuz</small>
                </p>
            </div>
            <div class="container ps-4 pe-4 py-4">
                <form action="" id="income_modalForm">
                    <input type="hidden" class="form-control" name="id" value="0">
                    <input type="hidden" class="form-control" name="person_id_income" id="person_id_income" value="0">

                    <div class="text-secondary mt-3">
                        <label for="">Gelir Türü</label>
                        <input type="text" name="income_type" class="form-control mt-1">
                    </div>
                    <div class="text-secondary mt-3">
                        <label for="">Gelir Tutarı</label>
                        <input type="text" name="income_amount" class="form-control mt-1 money">
                    </div>
                    <div class="text-secondary mt-3">
                        <label for="">Gelir Eklenecek Dönem</label>
                        <div class="row d-flex">
                            <div class="col-6">

                                <?php echo Date::getMonthsSelect("income_month"); ?>
                            </div>
                            <div class="col-6">

                                <?php echo Date::getYearsSelect("income_year"); ?>

                            </div>
                        </div>
                    </div>
                    
                    <div class="text-secondary mt-3">
                        <label for="">Açıklama</label>
                        <textarea name="income_description" class="form-control mt-1"
                            placeholder="Gelir hakkında açıklama yazınız"></textarea>
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
                            <a href="#" class="btn btn-success w-100" id="income_addButton">
                                Gelir Ekle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>